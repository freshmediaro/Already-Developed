// Main Application Entry Point - Modular Desktop System
import './bootstrap';

// Core system imports
import { windowManager } from './Core/WindowManager';
import { appRegistry } from './Core/AppRegistry';
import { appLoader } from './Core/AppLoader';
import { apiService } from './Tenant/ApiService';
import { eventSystem } from './Core/EventSystem';
import { orientationManager } from './Core/OrientationManager';
import { volumeManager } from './Core/VolumeManager';
import { globalStateManager } from './Core/GlobalStateManager';
import AppInitializer from './Core/AppInitializer';
import { mobileSwipeManager } from './Core/MobileSwipeManager';

// Essential app imports (preloaded for performance)
// Note: Apps are now registered via AppInitializer

/**
 * Desktop Application - Main application controller for the modular desktop system
 *
 * This class serves as the main entry point and controller for the desktop application,
 * managing initialization, app launching, team switching, and global system state.
 * It provides a comprehensive desktop environment with window management, app loading,
 * and multi-tenant support.
 *
 * Key features:
 * - Application initialization and bootstrap
 * - Multi-tenant team switching
 * - App launching and management
 * - Desktop UI initialization
 * - Global event handling
 * - System state management
 * - Critical app preloading
 * - Maintenance and cleanup tasks
 * - Keyboard shortcuts and hotkeys
 * - System tray and taskbar management
 *
 * Supported operations:
 * - Application initialization
 * - Team context switching
 * - App launching and management
 * - Desktop UI setup
 * - Global event handling
 * - System state management
 * - Maintenance tasks
 *
 * The application provides:
 * - Complete desktop environment
 * - Multi-tenant isolation
 * - App management and loading
 * - Window management integration
 * - Event system integration
 * - API service integration
 * - State management
 * - UI component management
 *
 * @class DesktopApplication
 * @since 1.0.0
 */
class DesktopApplication {
  /** @type {boolean} Whether the application has been initialized */
  private initialized = false;
  
  /** @type {string|undefined} Current team ID for multi-tenant context */
  private currentTeamId?: string;
  
  /** @type {number|undefined} Current user ID for user context */
  private currentUserId?: number;

  /**
   * Initialize the desktop application
   *
   * This method performs comprehensive initialization of the desktop application,
   * including tenant context setup, core app registration, UI initialization,
   * and global event listener configuration.
   *
   * @async
   * @returns {Promise<void>} Promise that resolves when initialization is complete
   * @throws {Error} When initialization fails
   */
  async initialize(): Promise<void> {
    if (this.initialized) {
      console.warn('Desktop application already initialized');
      return;
    }

    try {
      console.log('Initializing Desktop Application...');

      // Initialize tenant context
      await this.initializeTenantContext();

      // Initialize and register core apps
      await AppInitializer.initialize();

      // Setup app loader with critical apps
      this.setupAppLoader();

      // Initialize desktop UI
      await this.initializeDesktop();

      // Setup global event listeners
      this.setupGlobalEventListeners();

      // Preload critical apps
      await this.preloadCriticalApps();

      // Setup automatic cleanup
      this.setupMaintenanceTasks();

      this.initialized = true;
      console.log('Desktop Application initialized successfully');

      // Emit initialization complete event
      eventSystem.emit('desktop:initialized', {
        teamId: this.currentTeamId,
        userId: this.currentUserId,
      });
    } catch (error) {
      console.error('Failed to initialize desktop application:', error);
      throw error;
    }
  }

  /**
   * Launch an application
   *
   * This method launches a specific application by ID, loading it if necessary
   * and creating a window instance for the app.
   *
   * @async
   * @param {string} appId The ID of the application to launch
   * @returns {Promise<string|null>} Promise that resolves to the window ID or null if failed
   * @throws {Error} When the desktop is not initialized or app launch fails
   */
  async launchApp(appId: string): Promise<string | null> {
    if (!this.initialized) {
      throw new Error('Desktop application not initialized');
    }

    console.log(`Launching app: ${appId}`);
    const appInstance = await appLoader.loadAndLaunch(appId, this.currentTeamId);
    const windowId = await appInstance.launch(this.currentTeamId);
    return windowId;
  }

  /**
   * Switch to a different team
   *
   * This method switches the current team context, updating API services
   * and emitting appropriate events for team switching.
   *
   * @async
   * @param {string} teamId The team ID to switch to
   * @returns {Promise<void>} Promise that resolves when team switch is complete
   */
  async switchTeam(teamId: string): Promise<void> {
    if (!this.initialized) return;

    const previousTeamId = this.currentTeamId;
    this.currentTeamId = teamId;

    // Update API service context
    apiService.setTenantContext({ teamId: parseInt(teamId) });

    // Emit team switch event
    eventSystem.emit('team:switched', {
      teamId: parseInt(teamId),
      previousTeamId: previousTeamId ? parseInt(previousTeamId) : undefined,
    });

    console.log(`Switched to team: ${teamId}`);
  }

  /**
   * Get current application state
   */
  getState(): {
    initialized: boolean;
    teamId?: string;
    userId?: number;
    openWindows: number;
    runningApps: number;
  } {
    return {
      initialized: this.initialized,
      teamId: this.currentTeamId,
      userId: this.currentUserId,
      openWindows: windowManager.getAllWindows().length,
      runningApps: appRegistry.getRunningInstances().length,
    };
  }

  /**
   * Shutdown the application gracefully
   */
  async shutdown(): Promise<void> {
    if (!this.initialized) return;

    console.log('Shutting down desktop application...');

    // Close all windows
    windowManager.closeAllWindows();

    // Close all app instances
    await appRegistry.closeAllInstances();

    // Clear app loader cache
    appLoader.clearOldModules(0);

    this.initialized = false;
    console.log('Desktop application shutdown complete');
  }

  // Private initialization methods

  private async initializeTenantContext(): Promise<void> {
    // Extract tenant information from current domain
    const hostname = window.location.hostname;
    const subdomain = hostname.split('.')[0];
    
    if (subdomain && subdomain !== 'www' && !hostname.includes('localhost')) {
      apiService.setTenantContext({ 
        domain: hostname,
      });
    }

    // Get current user and team information
    try {
      const userResponse = await fetch('/api/user', {
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (userResponse.ok) {
        const userData = await userResponse.json();
        this.currentUserId = userData.id;
        this.currentTeamId = userData.current_team_id?.toString();

        apiService.setTenantContext({
          userId: userData.id,
          teamId: userData.current_team_id,
        });
      }
    } catch (error) {
      console.warn('Failed to load user context:', error);
    }
  }

  private setupAppLoader(): void {
    // Setup automatic cleanup for memory management
    appLoader.setupAutoCleanup();

    // Preload critical apps for better performance
    appLoader.preloadApps(AppInitializer.getRegisteredApps());
  }

  private async initializeDesktop(): Promise<void> {
    // Initialize desktop UI components
    await this.setupDesktopUI();
    
    // Setup taskbar functionality
    this.setupTaskbar();
    
    // Setup start menu/app launcher
    this.setupStartMenu();
  }

  private async setupDesktopUI(): Promise<void> {
    // Ensure required DOM elements exist
    if (!document.getElementById('windows-container')) {
      console.warn('Windows container not found - desktop functionality may be limited');
    }

    if (!document.getElementById('taskbar-app-icons-container')) {
      console.warn('Taskbar container not found - taskbar functionality may be limited');
    }

    // Initialize desktop background and basic UI
    document.body.classList.add('desktop-initialized');
  }

  private setupTaskbar(): void {
    // Setup taskbar icon interactions
    const taskbar = document.querySelector('.taskbar');
    if (!taskbar) return;

    // Setup app launcher button
    const startButton = document.querySelector('#start-menu-btn, .start-btn');
    if (startButton) {
      startButton.addEventListener('click', () => {
        this.toggleStartMenu();
      });
    }

    // Setup system tray interactions
    this.setupSystemTray();
  }

  private setupStartMenu(): void {
    // Setup start menu/app launcher functionality
    const startMenu = document.querySelector('.start-menu, .app-launcher');
    if (!startMenu) return;

    // Setup app icons in start menu
    this.setupStartMenuApps();
  }

  private setupStartMenuApps(): void {
    const startMenu = document.querySelector('.start-menu, .app-launcher');
    if (!startMenu) return;

    // Find app icon containers
    const appContainers = startMenu.querySelectorAll('.app-icon, .desktop-icon');
    
    appContainers.forEach(container => {
      const appId = container.getAttribute('data-app');
      if (!appId) return;

      container.addEventListener('click', async (e) => {
        e.preventDefault();
        await this.launchApp(appId);
        this.hideStartMenu();
      });
    });
  }

  private setupSystemTray(): void {
    // Setup volume controls
    const volumeBtn = document.querySelector('#volume-btn');
    if (volumeBtn) {
      volumeBtn.addEventListener('click', () => {
        this.toggleVolumePanel();
      });
    }

    // Setup notifications
    const notificationsBtn = document.querySelector('#notifications-btn');
    if (notificationsBtn) {
      notificationsBtn.addEventListener('click', () => {
        this.toggleNotificationsPanel();
      });
    }
  }

  private setupGlobalEventListeners(): void {
    // Setup keyboard shortcuts
    document.addEventListener('keydown', (e) => {
      this.handleGlobalKeydown(e);
    });

    // Setup context menu prevention (allow in inputs and content areas)
    document.addEventListener('contextmenu', (event) => {
      const target = event.target as HTMLElement;
      // Allow context menu in email content areas, all inputs, textareas, and contenteditables
      if (
        target.closest('.email-content') ||
        target.closest('.email-content-section') ||
        target.tagName === 'INPUT' ||
        target.tagName === 'TEXTAREA' ||
        target.isContentEditable
      ) return;
      event.preventDefault(); // This disables the browser's context menu elsewhere
    });

    // Setup window management events
    eventSystem.on('window:created', (payload) => {
      console.log('Window created:', payload.data);
    });

    eventSystem.on('app:launched', (payload) => {
      console.log('App launched:', payload.data);
    });

    // Setup team switching events
    eventSystem.on('team:switched', (payload) => {
      console.log('Team switched:', payload.data);
      this.handleTeamSwitch(payload.data);
    });
  }

  private async preloadCriticalApps(): Promise<void> {
    await appLoader.preloadCriticalApps();
    console.log('Critical apps preloaded');
  }

  private setupMaintenanceTasks(): void {
    // Clean up old app modules every 15 minutes
    setInterval(() => {
      appLoader.clearOldModules();
    }, 15 * 60 * 1000);

    // Log system statistics every 5 minutes in development
    if (import.meta.env.VITE_APP_ENV === 'local') {
      setInterval(() => {
        console.log('Desktop Stats:', {
          windows: windowManager.getAllWindows().length,
          apps: appRegistry.getStats(),
          loader: appLoader.getStats(),
        });
      }, 5 * 60 * 1000);
    }
  }

  // Event handlers

  private handleGlobalKeydown(e: KeyboardEvent): void {
    // Alt + Tab: Window switching (not implemented yet)
    if (e.altKey && e.key === 'Tab') {
      e.preventDefault();
      // TODO: Implement window switching
    }

    // Windows + R: Quick launcher (not implemented yet)
    if (e.metaKey && e.key === 'r') {
      e.preventDefault();
      // TODO: Implement quick launcher
    }

    // Windows key: Toggle start menu
    if (e.key === 'Meta' || e.key === 'Super') {
      e.preventDefault();
      this.toggleStartMenu();
    }
  }

  private async handleTeamSwitch(data: { teamId: number; previousTeamId?: number }): Promise<void> {
    // Close team-specific windows if needed
    if (data.previousTeamId) {
      await appRegistry.closeTeamInstances(data.previousTeamId.toString());
    }

    // Refresh desktop to reflect team-specific apps and data
    await this.refreshDesktop();
  }

  // UI interaction methods

  private toggleStartMenu(): void {
    const startMenu = document.querySelector('.start-menu, .app-launcher');
    if (!startMenu) return;

    startMenu.classList.toggle('active');
  }

  private hideStartMenu(): void {
    const startMenu = document.querySelector('.start-menu, .app-launcher');
    if (!startMenu) return;

    startMenu.classList.remove('active');
  }

  private toggleVolumePanel(): void {
    const volumePanel = document.querySelector('#volume-panel, .volume-panel');
    if (!volumePanel) return;

    volumePanel.classList.toggle('active');
  }

  private toggleNotificationsPanel(): void {
    const notificationsPanel = document.querySelector('#notifications-panel, .notifications-panel');
    if (!notificationsPanel) return;

    notificationsPanel.classList.toggle('active');
  }

  private async refreshDesktop(): Promise<void> {
    // Refresh app availability based on current team
    // This would typically involve reloading the desktop with team-specific apps
    console.log('Refreshing desktop for team context');
  }
}

// Create global desktop application instance
const desktopApp = new DesktopApplication();

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', async () => {
    await desktopApp.initialize();
  });
} else {
  // DOM already loaded
  desktopApp.initialize();
}

// Make desktop app globally available for debugging
(window as any).desktopApp = desktopApp;

// Export for module usage
export default desktopApp; 