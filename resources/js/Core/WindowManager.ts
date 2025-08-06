// Window Management System - Extracted and refactored from monolithic app.js
import type { Window, WindowPosition, UseWindowOptions } from './Types';
import { eventSystem, windowEvents } from './EventSystem';
import { popoutService } from './PopoutService';

/**
 * Window data structure for internal window tracking
 */
interface WindowData {
  id: string;
  element: HTMLElement;
  name: string;
  title: string;
  taskbarIcon?: HTMLElement;
  minimized: boolean;
  maximized: boolean;
  zIndex: number;
  teamId?: string;
  data?: Record<string, any>;
}

/**
 * Window Manager - Core system for managing application windows
 * 
 * This class handles the complete lifecycle of application windows including
 * creation, positioning, state management, and user interactions. It provides
 * a desktop-like window management experience with support for multiple
 * applications, window states, and team-based isolation.
 * 
 * Key features:
 * - Window creation and destruction
 * - Window state management (minimize, maximize, restore)
 * - Z-index management and window activation
 * - Taskbar integration
 * - Drag and drop functionality
 * - Mobile-responsive behavior
 * - Team-based window isolation
 * 
 * @since 1.0.0
 */
class WindowManager {
  /** Map of all open windows indexed by window ID */
  private openWindows: Map<string, WindowData> = new Map();
  
  /** Counter for generating unique window IDs */
  private windowIdCounter = 0;
  
  /** Currently active window element */
  private activeWindow: HTMLElement | null = null;
  
  /** Base z-index for window stacking */
  private windowZIndex = 1000;
  
  /** Mobile breakpoint for responsive behavior */
  private readonly MOBILE_BREAKPOINT = 767;
  
  /** Container element for all windows */
  private windowsContainer: HTMLElement;
  
  /** Container element for taskbar icons */
  private taskbarContainer: HTMLElement;

  /**
   * Initialize the Window Manager
   * 
   * Sets up the window management system by finding required DOM elements
   * and establishing global event listeners for window interactions.
   */
  constructor() {
    this.windowsContainer = this.getWindowsContainer();
    this.taskbarContainer = this.getTaskbarContainer();
    this.setupGlobalEventListeners();
  }

  /**
   * Create a new application window and manage its lifecycle
   * 
   * This method handles the complete window creation process including
   * element creation, positioning, event binding, and taskbar integration.
   * It supports single-instance apps and provides fallback behavior for
   * existing windows.
   * 
   * @param appId - Unique identifier for the application
   * @param title - Display title for the window
   * @param options - Window configuration options (size, position, behavior)
   * @returns Promise<string> - Window ID for future reference and management
   * @throws Error - When window creation fails or required elements are missing
   */
  async createWindow(
    appId: string,
    title: string,
    options: UseWindowOptions = {}
  ): Promise<string> {
    const windowId = `window-${++this.windowIdCounter}`;
    
    // Check for existing window (unless app allows multiple instances)
    const allowMultiple = this.appAllowsMultiple(appId);
    if (!allowMultiple) {
      const existing = this.findWindowByApp(appId);
      if (existing) {
        if (existing.minimized) {
          this.restoreWindow(existing.id);
        } else {
          this.activateWindow(existing.id);
        }
        return existing.id;
      }
    }

    // Create window element
    const windowElement = this.createWindowElement(windowId, title, options);
    
    // Create window data
    const windowData: WindowData = {
      id: windowId,
      element: windowElement,
      name: appId,
      title,
      minimized: false,
      maximized: false,
      zIndex: ++this.windowZIndex,
      teamId: this.getCurrentTeamId(),
    };

    // Setup window interactions
    this.setupWindowControls(windowElement, windowId);
    this.positionWindow(windowElement, options);
    
    // Make window draggable on desktop
    if (!this.isMobile()) {
      this.makeWindowDraggable(windowElement);
    }

    // Create taskbar icon
    const taskbarIcon = this.createTaskbarIcon(windowId, appId, title);
    windowData.taskbarIcon = taskbarIcon;

    // Store window data
    this.openWindows.set(windowId, windowData);

    // Add to DOM
    this.windowsContainer.appendChild(windowElement);

    // Activate the new window
    this.activateWindow(windowId);

    // Emit event
    windowEvents.created({
      windowId,
      appId,
      title,
      teamId: windowData.teamId,
    });

    return windowId;
  }

  /**
   * Close a window
   */
  closeWindow(windowId: string): void {
    const windowData = this.openWindows.get(windowId);
    if (!windowData) return;

    const { element, taskbarIcon } = windowData;

    // Mark as closing to prevent interactions
    (element as any)._isClosing = true;

    // Animate close
    element.classList.add('window-anim-close');
    
    element.addEventListener('animationend', (e) => {
      if ((e as AnimationEvent).animationName === 'windowClose') {
        // Remove from DOM
        element.remove();
        
        // Remove taskbar icon if not pinned
        if (taskbarIcon && !taskbarIcon.classList.contains('pinned-only')) {
          taskbarIcon.remove();
        }

        // Update active window
        if (this.activeWindow === element) {
          this.activeWindow = null;
          this.updateTaskbarActiveState();
        }

        // Clean up window data
        this.openWindows.delete(windowId);

        // Emit event
        windowEvents.closed(windowId);
      }
    }, { once: true });
  }

  /**
   * Minimize a window
   */
  minimizeWindow(windowId: string): void {
    const windowData = this.openWindows.get(windowId);
    if (!windowData || windowData.minimized) return;

    const { element, taskbarIcon } = windowData;

    // Animate to taskbar
    this.animateWindowToTaskbar(element, taskbarIcon, () => {
      element.style.display = 'none';
      element.classList.add('minimized');
      windowData.minimized = true;

      if (taskbarIcon) {
        taskbarIcon.classList.add('minimized');
        taskbarIcon.classList.remove('active');
      }

      if (this.activeWindow === element) {
        this.activeWindow = null;
        this.updateTaskbarActiveState();
      }

      windowEvents.minimized(windowId);
    });
  }

  /**
   * Restore a minimized window
   */
  restoreWindow(windowId: string): void {
    const windowData = this.openWindows.get(windowId);
    if (!windowData || !windowData.minimized) return;

    const { element, taskbarIcon } = windowData;

    element.style.display = 'flex';
    element.classList.remove('minimized');
    windowData.minimized = false;

    if (taskbarIcon) {
      taskbarIcon.classList.remove('minimized');
    }

    // Bring to front and animate from taskbar
    this.activateWindow(windowId);
    this.animateWindowFromTaskbar(element, taskbarIcon, () => {
      windowEvents.restored(windowId);
    });
  }

  /**
   * Maximize a window
   */
  maximizeWindow(windowId: string): void {
    const windowData = this.openWindows.get(windowId);
    if (!windowData || windowData.maximized || this.isMobile()) return;

    const { element } = windowData;

    // Store current dimensions
    element.dataset.prevWidth = element.style.width || element.offsetWidth + 'px';
    element.dataset.prevHeight = element.style.height || element.offsetHeight + 'px';
    element.dataset.prevLeft = element.style.left || element.offsetLeft + 'px';
    element.dataset.prevTop = element.style.top || element.offsetTop + 'px';

    // Maximize
    const rect = this.windowsContainer.getBoundingClientRect();
    element.style.width = '100%';
    element.style.height = '100%';
    element.style.left = '0px';
    element.style.top = '0px';
    element.style.resize = 'none';
    element.classList.add('maximized');

    windowData.maximized = true;

    // Update maximize button
    const maximizeBtn = element.querySelector('.window-maximize');
    if (maximizeBtn) {
      maximizeBtn.innerHTML = '<i class="fas fa-compress"></i>';
      (maximizeBtn as HTMLElement).title = 'Restore';
    }

    windowEvents.maximized(windowId);
  }

  /**
   * Restore a maximized window
   */
  restoreMaximizedWindow(windowId: string): void {
    const windowData = this.openWindows.get(windowId);
    if (!windowData || !windowData.maximized) return;

    const { element } = windowData;

    // Restore previous dimensions
    element.style.width = element.dataset.prevWidth || '800px';
    element.style.height = element.dataset.prevHeight || '600px';
    element.style.left = element.dataset.prevLeft || '100px';
    element.style.top = element.dataset.prevTop || '100px';
    element.style.resize = '';
    element.classList.remove('maximized');

    windowData.maximized = false;

    // Update maximize button
    const maximizeBtn = element.querySelector('.window-maximize');
    if (maximizeBtn) {
      maximizeBtn.innerHTML = '<i class="fas fa-expand"></i>';
      (maximizeBtn as HTMLElement).title = 'Maximize';
    }

    windowEvents.restored(windowId);
  }

  /**
   * Activate a window (bring to front)
   */
  activateWindow(windowId: string): void {
    const windowData = this.openWindows.get(windowId);
    if (!windowData) return;

    const { element, taskbarIcon } = windowData;

    // Don't activate if closing or already active
    if ((element as any)._isClosing) return;
    if (this.activeWindow === element && !element.classList.contains('minimized')) return;

    // Deactivate current window
    if (this.activeWindow) {
      this.activeWindow.classList.remove('active');
      const activeData = Array.from(this.openWindows.values())
        .find(w => w.element === this.activeWindow);
      if (activeData?.taskbarIcon) {
        activeData.taskbarIcon.classList.remove('active');
      }
    }

    // Activate new window
    element.style.zIndex = String(++this.windowZIndex);
    element.classList.add('active');
    element.style.display = 'flex';
    element.classList.remove('minimized');
    windowData.minimized = false;

    this.activeWindow = element;

    if (taskbarIcon) {
      taskbarIcon.classList.add('active');
      taskbarIcon.classList.remove('minimized');
    }

    this.updateTaskbarActiveState();
  }

  /**
   * Get window by ID
   */
  getWindow(windowId: string): WindowData | undefined {
    return this.openWindows.get(windowId);
  }

  /**
   * Get all open windows
   */
  getAllWindows(): WindowData[] {
    return Array.from(this.openWindows.values());
  }

  /**
   * Get windows by app
   */
  getWindowsByApp(appId: string): WindowData[] {
    return Array.from(this.openWindows.values())
      .filter(w => w.name === appId);
  }

  /**
   * Get active window
   */
  getActiveWindow(): WindowData | null {
    if (!this.activeWindow) return null;
    return Array.from(this.openWindows.values())
      .find(w => w.element === this.activeWindow) || null;
  }

  /**
   * Update window position
   */
  updateWindowPosition(windowId: string, position: Partial<WindowPosition>): void {
    const windowData = this.openWindows.get(windowId);
    if (!windowData || windowData.maximized) return;

    const { element } = windowData;

    if (position.x !== undefined) element.style.left = position.x + 'px';
    if (position.y !== undefined) element.style.top = position.y + 'px';
    if (position.width !== undefined) element.style.width = position.width + 'px';
    if (position.height !== undefined) element.style.height = position.height + 'px';

    windowEvents.moved(windowId, {
      x: parseInt(element.style.left) || 0,
      y: parseInt(element.style.top) || 0,
    });

    if (position.width !== undefined || position.height !== undefined) {
      windowEvents.resized(windowId, {
        width: parseInt(element.style.width) || 800,
        height: parseInt(element.style.height) || 600,
      });
    }
  }

  /**
   * Close all windows
   */
  closeAllWindows(): void {
    Array.from(this.openWindows.keys()).forEach(windowId => {
      this.closeWindow(windowId);
    });
  }

  /**
   * Close all windows for a specific team
   */
  closeTeamWindows(teamId: string): void {
    Array.from(this.openWindows.values())
      .filter(w => w.teamId === teamId)
      .forEach(w => this.closeWindow(w.id));
  }

  // Private methods

  private createWindowElement(
    windowId: string,
    title: string,
    options: UseWindowOptions
  ): HTMLElement {
    const element = document.createElement('div');
    element.className = 'window';
    element.id = windowId;
    
    // Set dimensions
    element.style.width = options.maxWidth ? Math.min(800, options.maxWidth) + 'px' : '800px';
    element.style.height = options.maxHeight ? Math.min(600, options.maxHeight) + 'px' : '600px';
    
    if (options.minWidth) element.style.minWidth = options.minWidth + 'px';
    if (options.minHeight) element.style.minHeight = options.minHeight + 'px';

    element.innerHTML = `
      <div class="window-header">
        <button class="menu-toggle">
          <i class="fas fa-bars"></i>
        </button>
        <div class="window-title">
          <div class="window-icon">
            <i class="fas fa-window-maximize"></i>
          </div>
          <span>${title}</span>
        </div>
        <div class="window-controls">
          <button class="window-minimize" title="Minimize">
            <i class="fas fa-minus"></i>
          </button>
          <button class="window-popout" title="Pop out">
            <i class="fas fa-up-right-from-square"></i>
          </button>
          <button class="window-maximize" title="Maximize">
            <i class="fas fa-expand"></i>
          </button>
          <button class="window-close" title="Close">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
      <div class="window-content">
        <div class="sidebar-overlay"></div>
        <!-- App content will be injected here -->
      </div>
    `;

    return element;
  }

  private setupWindowControls(element: HTMLElement, windowId: string): void {
    const closeBtn = element.querySelector('.window-close');
    const minimizeBtn = element.querySelector('.window-minimize');
    const maximizeBtn = element.querySelector('.window-maximize');
    const popoutBtn = element.querySelector('.window-popout');

    closeBtn?.addEventListener('click', (e) => {
      e.stopPropagation();
      this.closeWindow(windowId);
    });

    minimizeBtn?.addEventListener('click', (e) => {
      e.stopPropagation();
      this.minimizeWindow(windowId);
    });

    maximizeBtn?.addEventListener('click', (e) => {
      e.stopPropagation();
      const windowData = this.openWindows.get(windowId);
      if (!windowData || this.isMobile()) return;

      if (windowData.maximized) {
        this.restoreMaximizedWindow(windowId);
      } else {
        this.maximizeWindow(windowId);
      }
    });

    popoutBtn?.addEventListener('click', (e) => {
      e.stopPropagation();
      this.popoutWindow(windowId);
    });
  }

  private positionWindow(element: HTMLElement, options: UseWindowOptions): void {
    if (this.isMobile()) {
      // Mobile: fullscreen
      element.style.left = '0px';
      element.style.top = '0px';
      element.style.width = '100%';
      element.style.height = '100%';
      return;
    }

    if (options.centered) {
      this.centerWindow(element);
      return;
    }

    // Desktop: cascade positioning
    const openCount = this.openWindows.size;
    const offset = (openCount % 10) * 20;
    
    element.style.left = (100 + offset) + 'px';
    element.style.top = (100 + offset) + 'px';
  }

  private centerWindow(element: HTMLElement): void {
    const rect = this.windowsContainer.getBoundingClientRect();
    const width = element.offsetWidth;
    const height = element.offsetHeight;
    
    const left = Math.max(0, (rect.width - width) / 2);
    const top = Math.max(0, (rect.height - height) / 2);
    
    element.style.left = left + 'px';
    element.style.top = top + 'px';
  }

  private makeWindowDraggable(element: HTMLElement): void {
    if (this.isMobile()) return;

    const header = element.querySelector('.window-header') as HTMLElement;
    if (!header) return;

    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let startLeft = 0;
    let startTop = 0;

    header.addEventListener('mousedown', (e) => {
      // Don't drag if clicking on controls
      if ((e.target as HTMLElement).closest('.window-controls')) return;

      e.preventDefault();
      isDragging = true;
      
      startX = e.clientX;
      startY = e.clientY;
      startLeft = parseInt(element.style.left) || 0;
      startTop = parseInt(element.style.top) || 0;

      this.activateWindow(element.id);

      header.style.cursor = 'grabbing';
    });

    document.addEventListener('mousemove', (e) => {
      if (!isDragging) return;

      const deltaX = e.clientX - startX;
      const deltaY = e.clientY - startY;
      
      const newLeft = startLeft + deltaX;
      const newTop = Math.max(0, startTop + deltaY);

      element.style.left = newLeft + 'px';
      element.style.top = newTop + 'px';
    });

    document.addEventListener('mouseup', () => {
      if (isDragging) {
        isDragging = false;
        header.style.cursor = '';
      }
    });
  }

  private createTaskbarIcon(windowId: string, appId: string, title: string): HTMLElement {
    const icon = document.createElement('div');
    icon.className = 'taskbar-app-icon';
    icon.dataset.windowId = windowId;
    icon.dataset.app = appId;
    icon.title = title;

    icon.innerHTML = `
      <div class="taskbar-app-icon-inner">
        <i class="fas fa-window-maximize"></i>
      </div>
    `;

    icon.addEventListener('click', () => {
      const windowData = this.openWindows.get(windowId);
      if (!windowData) return;

      if (windowData.minimized) {
        this.restoreWindow(windowId);
      } else if (this.activeWindow === windowData.element) {
        this.minimizeWindow(windowId);
      } else {
        this.activateWindow(windowId);
      }
    });

    this.taskbarContainer.appendChild(icon);
    return icon;
  }

  private animateWindowToTaskbar(element: HTMLElement, taskbarIcon?: HTMLElement, callback?: () => void): void {
    // Simple fade animation for now
    element.style.transition = 'opacity 0.2s ease-out';
    element.style.opacity = '0';
    
    setTimeout(() => {
      element.style.transition = '';
      element.style.opacity = '';
      callback?.();
    }, 200);
  }

  private animateWindowFromTaskbar(element: HTMLElement, taskbarIcon?: HTMLElement, callback?: () => void): void {
    element.style.opacity = '0';
    element.style.transition = 'opacity 0.2s ease-in';
    
    setTimeout(() => {
      element.style.opacity = '1';
      setTimeout(() => {
        element.style.transition = '';
        element.style.opacity = '';
        callback?.();
      }, 200);
    }, 10);
  }

  private updateTaskbarActiveState(): void {
    // Remove active state from all icons
    this.taskbarContainer.querySelectorAll('.taskbar-app-icon')
      .forEach(icon => icon.classList.remove('active'));

    // Add active state to current window's icon
    if (this.activeWindow) {
      const windowData = Array.from(this.openWindows.values())
        .find(w => w.element === this.activeWindow);
      if (windowData?.taskbarIcon) {
        windowData.taskbarIcon.classList.add('active');
      }
    }
  }

  private findWindowByApp(appId: string): WindowData | undefined {
    return Array.from(this.openWindows.values())
      .find(w => w.name === appId && !w.minimized);
  }

  private appAllowsMultiple(appId: string): boolean {
    // Apps that allow multiple instances
    const multipleInstanceApps = ['my-files', 'browser'];
    return multipleInstanceApps.includes(appId);
  }

  private getCurrentTeamId(): string | undefined {
    // This would be implemented to get current team from global state
    return undefined;
  }

  private isMobile(): boolean {
    return window.innerWidth <= this.MOBILE_BREAKPOINT;
  }

  private getWindowsContainer(): HTMLElement {
    return document.getElementById('windows-container') || 
           document.querySelector('.windows-container') ||
           document.body;
  }

  private getTaskbarContainer(): HTMLElement {
    return document.getElementById('taskbar-app-icons-container') || 
           document.querySelector('.taskbar-app-icons') ||
           document.body;
  }

  private popoutWindow(windowId: string): void {
    const windowData = this.openWindows.get(windowId);
    if (!windowData) return;

    // Use the popout service to handle the popout
    popoutService.popoutWindow(windowData.element, windowId);
  }

  private setupGlobalEventListeners(): void {
    // Handle window resize
    window.addEventListener('resize', () => {
      this.openWindows.forEach(windowData => {
        if (this.isMobile()) {
          // Make all windows fullscreen on mobile
          const { element } = windowData;
          element.style.left = '0px';
          element.style.top = '0px';
          element.style.width = '100%';
          element.style.height = '100%';
        }
      });
    });

    // Handle click outside windows to deactivate
    document.addEventListener('click', (e) => {
      const target = e.target as HTMLElement;
      if (!target.closest('.window') && !target.closest('.taskbar-app-icon')) {
        // Clicked outside all windows - could deactivate current window
        // For now, we'll keep the current window active
      }
    });

    // Handle popout window events
    eventSystem.on('window:popout', (payload) => {
      const { windowId } = payload.data;
      this.closeWindow(windowId);
    });
  }
}

// Create singleton instance
export const windowManager = new WindowManager();

export default windowManager; 