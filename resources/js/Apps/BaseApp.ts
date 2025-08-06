// Base App Class - Foundation for all desktop applications
import type { App, AppConfig } from '../Core/Types';
import { eventSystem, appEvents } from '../Core/EventSystem';
import { windowManager } from '../Core/WindowManager';

/**
 * Application context containing window and user information
 * 
 * This interface provides the essential context that all applications
 * need to function within the desktop environment, including window
 * references, user permissions, and team isolation.
 */
export interface AppContext {
  /** Unique identifier for the application window */
  windowId: string;
  /** DOM element representing the application window */
  windowElement: HTMLElement;
  /** DOM element where application content should be rendered */
  contentElement: HTMLElement;
  /** Current team ID for tenant isolation */
  teamId?: string;
  /** Current user ID for permission checking */
  userId: number;
  /** Array of permissions granted to the current user */
  permissions: string[];
  /** Application-specific configuration and settings */
  config: AppConfig;
}

/**
 * Lifecycle hooks that applications can implement
 * 
 * These optional methods allow applications to respond to various
 * lifecycle events and state changes in the desktop environment.
 */
export interface AppLifecycleHooks {
  /** Called when the application is first mounted and ready */
  onMount?(context: AppContext): void | Promise<void>;
  /** Called when the application is being unmounted and cleaned up */
  onUnmount?(context: AppContext): void | Promise<void>;
  /** Called when the application window becomes active/focused */
  onActivate?(context: AppContext): void | Promise<void>;
  /** Called when the application window loses focus */
  onDeactivate?(context: AppContext): void | Promise<void>;
  /** Called when the application window is resized */
  onResize?(context: AppContext, size: { width: number; height: number }): void;
  /** Called when the user switches to a different team */
  onTeamSwitch?(context: AppContext, newTeamId: string, oldTeamId?: string): void;
  /** Called when the user attempts to close the application */
  onCloseRequested?(): boolean | Promise<boolean>;
}

/**
 * Base Application Class - Foundation for all desktop applications
 * 
 * This abstract class provides the core functionality that all desktop
 * applications need, including window management, lifecycle handling,
 * event system integration, and common utility methods.
 * 
 * Key features:
 * - Window creation and management
 * - Lifecycle hook system
 * - Event listener management
 * - Permission checking
 * - Error handling and loading states
 * - Team context management
 * 
 * Applications should extend this class and implement the abstract
 * render() method to provide their specific functionality.
 * 
 * @abstract
 * @since 1.0.0
 */
export abstract class BaseApp implements AppLifecycleHooks {
  /** Application context containing window and user information */
  protected context?: AppContext;
  
  /** Whether the application is currently mounted */
  protected mounted = false;
  
  /** Whether the application window is currently active */
  protected active = false;
  
  /** Array of event listener cleanup functions */
  protected eventListeners: Array<() => void> = [];

  // Optional lifecycle hooks that subclasses can implement
  onMount?(context: AppContext): void | Promise<void>;
  onUnmount?(context: AppContext): void | Promise<void>;
  onActivate?(context: AppContext): void | Promise<void>;
  onDeactivate?(context: AppContext): void | Promise<void>;
  onResize?(context: AppContext, size: { width: number; height: number }): void;
  onTeamSwitch?(context: AppContext, newTeamId: string, oldTeamId?: string): void;
  onCloseRequested?(): boolean | Promise<boolean>;

  /**
   * Initialize the base application
   * 
   * @param appId - Unique identifier for the application
   * @param appInfo - Application metadata and configuration
   */
  constructor(
    public readonly appId: string,
    public readonly appInfo: App
  ) {}

  /**
   * Launch the application - creates window and initializes app
   * 
   * This method handles the complete application launch process including
   * window creation, context setup, event binding, and lifecycle management.
   * It ensures proper initialization and error handling for all applications.
   * 
   * @param teamId - Optional team ID for tenant isolation
   * @returns Promise<string> - Window ID for the created application window
   * @throws Error - When window creation fails or initialization errors occur
   */
  async launch(teamId?: string): Promise<string> {
    try {
      // Create window through WindowManager
      const windowId = await windowManager.createWindow(
        this.appId,
        this.appInfo.name,
        this.getWindowOptions()
      );

      // Get window element
      const windowElement = windowManager.getWindow(windowId)?.element;
      if (!windowElement) {
        throw new Error(`Failed to create window for app ${this.appId}`);
      }

      // Setup app context
      this.context = {
        windowId,
        windowElement,
        contentElement: windowElement.querySelector('.window-content') as HTMLElement,
        teamId: teamId || this.getCurrentTeamId(),
        userId: this.getCurrentUserId(),
        permissions: await this.getAppPermissions(),
        config: await this.getAppConfig(),
      };

      // Update window title and icon
      this.updateWindowTitle(this.appInfo.name);
      this.updateWindowIcon(this.appInfo.icon);

      // Setup event listeners
      this.setupEventListeners();

      // Render app content
      await this.render();

      // Call lifecycle hook
      await this.onMount?.(this.context);

      this.mounted = true;

      // Emit app launched event
      appEvents.launched(this.appId, windowId);

      return windowId;
    } catch (error) {
      console.error(`Failed to launch app ${this.appId}:`, error);
      throw error;
    }
  }

  /**
   * Close the application
   */
  async close(): Promise<void> {
    if (!this.context) return;

    try {
      // Call lifecycle hook
      await this.onUnmount?.(this.context);

      // Clean up event listeners
      this.cleanup();

      // Close window
      windowManager.closeWindow(this.context.windowId);

      this.mounted = false;
      this.active = false;
      this.context = undefined;
    } catch (error) {
      console.error(`Error closing app ${this.appId}:`, error);
    }
  }

  /**
   * Check if app is mounted
   */
  isMounted(): boolean {
    return this.mounted;
  }

  /**
   * Check if app is active
   */
  isActive(): boolean {
    return this.active;
  }

  /**
   * Get app context
   */
  getContext(): AppContext | undefined {
    return this.context;
  }

  /**
   * Update app data
   */
  setData(key: string, value: any): void {
    if (!this.context) return;
    
    if (!this.context.config.settings) {
      this.context.config.settings = {};
    }
    
    this.context.config.settings[key] = value;
  }

  /**
   * Get app data
   */
  getData(key: string): any {
    return this.context?.config.settings?.[key];
  }

  /**
   * Force re-render
   */
  async refresh(): Promise<void> {
    if (!this.context) return;
    await this.render();
  }

  /**
   * Request to close the application (can be cancelled by user or app logic)
   */
  async requestClose(): Promise<boolean> {
    try {
      // Allow apps to implement custom close logic (e.g., save prompts)
      const canClose = await this.onCloseRequested?.() ?? true;
      
      if (canClose) {
        await this.close();
        return true;
      }
      
      return false;
    } catch (error) {
      console.error(`Error requesting close for app ${this.appId}:`, error);
      return false;
    }
  }

  // Abstract methods that must be implemented by subclasses

  /**
   * Render the app content into the window
   * Override this method in subclasses to implement app-specific rendering
   */
  protected async render(): Promise<void> {
    // Default implementation - can be overridden
  }

  /**
   * Get window options for this app
   * Override this method in subclasses to customize window behavior
   */
  protected getWindowOptions(): import('../Core/Types').UseWindowOptions {
    // Default implementation - can be overridden
    return {
      minWidth: 400,
      minHeight: 300,
      resizable: true,
      draggable: true,
      centered: true,
    };
  }

  // Virtual methods that can be overridden

  /**
   * Handle app activation
   */
  protected async handleActivate(): Promise<void> {
    this.active = true;
    await this.onActivate?.(this.context!);
  }

  /**
   * Handle app deactivation
   */
  protected async handleDeactivate(): Promise<void> {
    this.active = false;
    await this.onDeactivate?.(this.context!);
  }

  /**
   * Handle window resize
   */
  protected handleResize(size: { width: number; height: number }): void {
    this.onResize?.(this.context!, size);
  }

  /**
   * Handle team switch
   */
  protected async handleTeamSwitch(newTeamId: string, oldTeamId?: string): Promise<void> {
    if (!this.context) return;

    // Update context
    this.context.teamId = newTeamId;
    this.context.permissions = await this.getAppPermissions();
    this.context.config = await this.getAppConfig();

    // Re-render if team-scoped
    if (this.appInfo.teamScoped) {
      await this.render();
    }

    await this.onTeamSwitch?.(this.context, newTeamId, oldTeamId);
  }

  // Protected utility methods

  protected updateWindowTitle(title: string): void {
    if (!this.context) return;
    const titleElement = this.context.windowElement.querySelector('.window-title span');
    if (titleElement) {
      titleElement.textContent = title;
    }
  }

  protected updateWindowIcon(iconClass: string): void {
    if (!this.context) return;
    const iconElement = this.context.windowElement.querySelector('.window-icon i');
    if (iconElement) {
      iconElement.className = `fas ${iconClass}`;
    }
  }

  protected createElement(tag: string, className?: string, innerHTML?: string): HTMLElement {
    const element = document.createElement(tag);
    if (className) element.className = className;
    if (innerHTML) element.innerHTML = innerHTML;
    return element;
  }

  protected createButton(text: string, className = '', onClick?: () => void): HTMLButtonElement {
    const button = document.createElement('button');
    button.textContent = text;
    button.className = className;
    if (onClick) {
      button.addEventListener('click', onClick);
    }
    return button;
  }

  protected showLoading(message = 'Loading...'): void {
    if (!this.context) return;
    this.context.contentElement.innerHTML = `
      <div class="app-loading">
        <div class="loading-spinner"></div>
        <p>${message}</p>
      </div>
    `;
  }

  protected showError(message: string, retry?: () => void): void {
    if (!this.context) return;
    this.context.contentElement.innerHTML = `
      <div class="app-error">
        <div class="error-icon">
          <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h3>Error</h3>
        <p>${message}</p>
        ${retry ? '<button class="btn btn-primary retry-btn">Try Again</button>' : ''}
      </div>
    `;

    if (retry) {
      const retryBtn = this.context.contentElement.querySelector('.retry-btn');
      retryBtn?.addEventListener('click', retry);
    }
  }

  protected hasPermission(permission: string): boolean {
    return this.context?.permissions.includes(permission) || false;
  }

  protected addEventListener(element: HTMLElement, event: string, handler: EventListener): void {
    element.addEventListener(event, handler);
    this.eventListeners.push(() => element.removeEventListener(event, handler));
  }

  // Private methods

  private setupEventListeners(): void {
    if (!this.context) return;

    // Listen for window events
    const unsubscribeActivate = eventSystem.on('window:created', (payload) => {
      if (payload.data.windowId === this.context?.windowId) {
        this.handleActivate();
      }
    });

    const unsubscribeResize = eventSystem.on('window:resized', (payload) => {
      if (payload.data.windowId === this.context?.windowId) {
        this.handleResize({
          width: payload.data.width,
          height: payload.data.height,
        });
      }
    });

    const unsubscribeTeamSwitch = eventSystem.on('team:switched', (payload) => {
      this.handleTeamSwitch(payload.data.teamId, payload.data.previousTeamId);
    });

    this.eventListeners.push(unsubscribeActivate, unsubscribeResize, unsubscribeTeamSwitch);
  }

  private cleanup(): void {
    // Remove all event listeners
    this.eventListeners.forEach(unsubscribe => unsubscribe());
    this.eventListeners = [];
  }

  private getCurrentTeamId(): string | undefined {
    // This would get the current team from global state/store
    // For now, return undefined - will be implemented with Pinia store
    return undefined;
  }

  private getCurrentUserId(): number {
    // This would get the current user from global state/store
    // For now, return 1 - will be implemented with Pinia store
    return 1;
  }

  private async getAppPermissions(): Promise<string[]> {
    // This would fetch permissions from API based on user/team/app
    // For now, return default permissions
    return this.appInfo.permissions || [];
  }

  private async getAppConfig(): Promise<AppConfig> {
    // This would fetch app config from API
    // For now, return basic config
    return {
      id: this.appId,
      settings: {},
      permissions: await this.getAppPermissions(),
      teamAccess: this.appInfo.teamScoped,
    };
  }
}

export default BaseApp; 