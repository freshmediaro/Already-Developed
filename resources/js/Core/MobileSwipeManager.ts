// Mobile Swipe Manager - 3-screen swipe system for mobile devices
import { eventSystem } from './EventSystem';

interface TouchState {
  startX: number;
  startY: number;
  startTime: number;
  lastX: number;
  isDragging: boolean;
  hasMoved: boolean;
}

type MobileState = 'notifications' | 'desktop' | 'widgets';

class MobileSwipeManager {
  private currentState: MobileState = 'desktop';
  private isTransitioning: boolean = false;
  private touchState: TouchState = {
    startX: 0,
    startY: 0,
    startTime: 0,
    lastX: 0,
    isDragging: false,
    hasMoved: false
  };
  
  private appSidebarOpen: boolean = false;
  
  // Configuration constants
  private readonly SWIPE_THRESHOLD = 50;
  private readonly SWIPE_VELOCITY_THRESHOLD = 0.3;
  private readonly MAX_VERTICAL_DRIFT = 100;
  private readonly TRANSITION_DURATION = 350;
  private readonly MOBILE_BREAKPOINT = 1024;
  
  private elements: {
    mainContentArea?: HTMLElement;
    desktopArea?: HTMLElement;
    widgetsScreen?: HTMLElement;
    notificationsScreen?: HTMLElement;
  } = {};

  constructor() {
    this.initialize();
  }

  private initialize(): void {
    // Only initialize on mobile devices
    if (!this.isMobile()) {
      console.log('Mobile swipe: Not mobile device, skipping initialization');
      return;
    }

    this.findElements();
    this.setupEventListeners();
    this.updateMobileState('desktop');
  }

  private isMobile(): boolean {
    return window.innerWidth <= this.MOBILE_BREAKPOINT;
  }

  private findElements(): void {
    this.elements = {
      mainContentArea: document.querySelector('.main-content-area') as HTMLElement,
      desktopArea: document.querySelector('.desktop-area') as HTMLElement,
      widgetsScreen: document.getElementById('widgets-screen') as HTMLElement,
      notificationsScreen: document.getElementById('notifications-screen') as HTMLElement
    };

    const missing = Object.entries(this.elements).filter(([key, element]) => !element);
    
    if (missing.length > 0) {
      console.warn('Mobile swipe: Required elements not found', missing.map(([key]) => key));
      return;
    }

    console.log('Mobile swipe: All required elements found, initializing...');
  }

  private setupEventListeners(): void {
    if (!this.elements.mainContentArea) return;

    // Touch events for swipe detection
    this.elements.mainContentArea.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: false });
    this.elements.mainContentArea.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
    this.elements.mainContentArea.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: false });

    // Window resize handler
    window.addEventListener('resize', this.handleResize.bind(this));
    
    // Prevent default touch behaviors that could interfere
    document.addEventListener('touchmove', (e) => {
      if (this.touchState.isDragging) {
        e.preventDefault();
      }
    }, { passive: false });
  }

  private handleTouchStart(e: TouchEvent): void {
    if (this.isTransitioning) {
      console.log('Mobile swipe: Touch start ignored - transitioning');
      return;
    }

    const touch = e.touches[0];
    this.touchState = {
      startX: touch.clientX,
      startY: touch.clientY,
      startTime: Date.now(),
      lastX: touch.clientX,
      isDragging: false,
      hasMoved: false
    };

    console.log(`Mobile swipe: Touch start at (${this.touchState.startX}, ${this.touchState.startY}) in state: ${this.currentState}`);
  }

  private handleTouchMove(e: TouchEvent): void {
    if (this.isTransitioning || !this.touchState.startTime) return;

    const touch = e.touches[0];
    const currentX = touch.clientX;
    const currentY = touch.clientY;
    
    const deltaX = currentX - this.touchState.startX;
    const deltaY = currentY - this.touchState.startY;

    // Check if this is a horizontal swipe
    if (!this.touchState.isDragging && Math.abs(deltaX) > 10) {
      if (Math.abs(deltaY) < this.MAX_VERTICAL_DRIFT) {
        this.touchState.isDragging = true;
        this.touchState.hasMoved = true;
        console.log(`Mobile swipe: Started dragging - deltaX: ${deltaX}, deltaY: ${deltaY}`);
      }
    }

    this.touchState.lastX = currentX;
  }

  private handleTouchEnd(e: TouchEvent): void {
    if (!this.touchState.hasMoved || this.isTransitioning) {
      console.log('Mobile swipe: Touch end ignored - transitioning or no movement');
      return;
    }

    const touchEndTime = Date.now();
    const touchDuration = touchEndTime - this.touchState.startTime;
    const deltaX = this.touchState.lastX - this.touchState.startX;
    const deltaY = Math.abs((e.changedTouches[0]?.clientY || 0) - this.touchState.startY);
    
    // Calculate velocity
    const velocity = Math.abs(deltaX) / touchDuration;
    
    // Check if it's a valid swipe
    const isValidSwipe = (Math.abs(deltaX) > this.SWIPE_THRESHOLD || velocity > this.SWIPE_VELOCITY_THRESHOLD) 
                       && deltaY < this.MAX_VERTICAL_DRIFT;

    if (isValidSwipe) {
      console.log(`Mobile swipe: Valid swipe detected - deltaX: ${deltaX}, velocity: ${velocity}`);
      
      // Check if we have an app sidebar open and should prioritize it
      const currentWindow = this.getCurrentOpenWindow();
      const shouldToggleSidebar = this.appSidebarOpen || (currentWindow && this.canToggleAppSidebar(currentWindow, deltaX));
      
      if (shouldToggleSidebar && currentWindow) {
        // Handle app sidebar toggle
        if (deltaX > 0 && !this.appSidebarOpen) {
          // Swipe right to open sidebar
          this.toggleAppSidebar(currentWindow, true);
          this.appSidebarOpen = true;
        } else if (deltaX < 0 && this.appSidebarOpen) {
          // Swipe left to close sidebar
          this.toggleAppSidebar(currentWindow, false);
          this.appSidebarOpen = false;
        }
      } else {
        // Handle normal screen navigation
        if (deltaX > 0) {
          // Swipe right
          if (this.currentState === 'widgets') {
            this.updateMobileState('desktop');
          } else if (this.currentState === 'desktop') {
            this.updateMobileState('notifications');
          }
        } else if (deltaX < 0) {
          // Swipe left
          if (this.currentState === 'notifications') {
            this.updateMobileState('desktop');
          } else if (this.currentState === 'desktop') {
            this.updateMobileState('widgets');
          }
        }
      }
    } else {
      console.log('Mobile swipe: Not a valid swipe');
    }

    // Reset touch state
    this.resetTouchState();
  }

  private handleResize(): void {
    // Re-initialize if switching between mobile/desktop
    const wasMobile = !!this.elements.mainContentArea;
    const isMobileNow = this.isMobile();
    
    if (isMobileNow && !wasMobile) {
      this.initialize();
    } else if (!isMobileNow && wasMobile) {
      this.cleanup();
    }
  }

  private updateMobileState(state: MobileState): void {
    if (this.isTransitioning) {
      console.log('Mobile swipe: Transition in progress, ignoring state change');
      return;
    }

    console.log(`Mobile swipe: Updating state from ${this.currentState} to ${state}`);
    this.isTransitioning = true;
    this.currentState = state;

    // Remove existing state classes
    document.body.classList.remove('mobile-icons-active', 'mobile-widgets-active', 'mobile-notifications-active');

    // Add new state class
    switch (state) {
      case 'widgets':
        document.body.classList.add('mobile-widgets-active');
        console.log('Mobile swipe: Added mobile-widgets-active class');
        break;
      case 'notifications':
        document.body.classList.add('mobile-notifications-active');
        console.log('Mobile swipe: Added mobile-notifications-active class');
        break;
      default:
        document.body.classList.add('mobile-icons-active');
        console.log('Mobile swipe: Added mobile-icons-active class');
        break;
    }

    // Emit event for other components
    eventSystem.emit('mobile:state-changed', { state });

    // Reset transition flag after animation completes
    setTimeout(() => {
      this.isTransitioning = false;
      console.log('Mobile swipe: Transition completed');
    }, this.TRANSITION_DURATION);

    // Safety timeout in case transition gets stuck
    setTimeout(() => {
      if (this.isTransitioning) {
        this.isTransitioning = false;
        console.log('Mobile swipe: Transition safety timeout triggered');
      }
    }, this.TRANSITION_DURATION * 2);
  }

  private getCurrentOpenWindow(): HTMLElement | null {
    // Get the topmost window
    const openWindows = document.querySelectorAll('.window:not(.minimized)');
    if (openWindows.length === 0) return null;
    
    let topWindow: HTMLElement | null = null;
    let highestZIndex = -1;
    
    openWindows.forEach((window) => {
      const zIndex = parseInt(getComputedStyle(window as HTMLElement).zIndex || '0');
      if (zIndex > highestZIndex) {
        highestZIndex = zIndex;
        topWindow = window as HTMLElement;
      }
    });
    
    return topWindow;
  }

  private canToggleAppSidebar(windowElement: HTMLElement, deltaX: number): boolean {
    // Don't toggle sidebar for settings app - it has its own navigation
    if (windowElement.classList.contains('settings-app-window')) {
      return false;
    }

    const sidebar = windowElement.querySelector('.window-sidebar');
    if (!sidebar) return false;

    // Check if we're at the right edge for opening (swipe right)
    if (deltaX > 0 && !this.appSidebarOpen) {
      return true;
    }

    // Check if sidebar is open for closing (swipe left)
    if (deltaX < 0 && this.appSidebarOpen) {
      return true;
    }

    return false;
  }

  private toggleAppSidebar(windowElement: HTMLElement, show: boolean): boolean {
    const sidebar = windowElement.querySelector('.window-sidebar') as HTMLElement;
    const overlay = windowElement.querySelector('.sidebar-overlay') as HTMLElement;
    const contentArea = windowElement.querySelector('.window-main-content, .settings-content, .app-store-main-content') as HTMLElement;

    if (sidebar && overlay) {
      if (show) {
        sidebar.classList.add('show');
        overlay.classList.add('show');
        if (contentArea) {
          contentArea.classList.add('sidebar-push-active');
        }
        windowElement.classList.add('sidebar-block-interaction');
        console.log('Mobile swipe: App sidebar opened');
        return true;
      } else {
        sidebar.classList.remove('show');
        overlay.classList.remove('show');
        if (contentArea) {
          contentArea.classList.remove('sidebar-push-active');
        }
        windowElement.classList.remove('sidebar-block-interaction');
        console.log('Mobile swipe: App sidebar closed');
        return true;
      }
    }

    return false;
  }

  private resetTouchState(): void {
    this.touchState = {
      startX: 0,
      startY: 0,
      startTime: 0,
      lastX: 0,
      isDragging: false,
      hasMoved: false
    };
    console.log('Mobile swipe: Touch state reset');
  }

  private cleanup(): void {
    if (this.elements.mainContentArea) {
      this.elements.mainContentArea.removeEventListener('touchstart', this.handleTouchStart.bind(this));
      this.elements.mainContentArea.removeEventListener('touchmove', this.handleTouchMove.bind(this));
      this.elements.mainContentArea.removeEventListener('touchend', this.handleTouchEnd.bind(this));
    }
    
    window.removeEventListener('resize', this.handleResize.bind(this));
    
    // Reset state
    document.body.classList.remove('mobile-icons-active', 'mobile-widgets-active', 'mobile-notifications-active');
    this.elements = {};
  }

  // Public methods
  public goToScreen(state: MobileState): void {
    this.updateMobileState(state);
  }

  public getCurrentState(): MobileState {
    return this.currentState;
  }

  public isInTransition(): boolean {
    return this.isTransitioning;
  }
}

// Create singleton instance
export const mobileSwipeManager = new MobileSwipeManager();
export default mobileSwipeManager; 