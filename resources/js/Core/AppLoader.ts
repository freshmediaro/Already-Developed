// App Loader - Lazy loading and code splitting for applications
import type { BaseApp } from '../Apps/BaseApp';

// Dynamic import type for app modules
type AppConstructor = new () => BaseApp;

interface AppModule {
  default: AppConstructor;
}

class AppLoader {
  private loadedModules: Map<string, AppModule> = new Map();
  private loadingPromises: Map<string, Promise<AppModule>> = new Map();
  private preloadedApps: Set<string> = new Set();

  /**
   * Dynamically import and load an application module
   */
  async loadApp(appId: string): Promise<BaseApp> {
    try {
      // Check if already loaded
      const cachedModule = this.loadedModules.get(appId);
      if (cachedModule) {
        return new cachedModule.default();
      }

      // Check if currently loading
      const loadingPromise = this.loadingPromises.get(appId);
      if (loadingPromise) {
        const module = await loadingPromise;
        return new module.default();
      }

      // Start loading
      const promise = this.importAppModule(appId);
      this.loadingPromises.set(appId, promise);

      const module = await promise;
      
      // Cache the loaded module
      this.loadedModules.set(appId, module);
      this.loadingPromises.delete(appId);

      return new module.default();
    } catch (error) {
      this.loadingPromises.delete(appId);
      if (process.env.NODE_ENV === 'development') {
        console.error(`Failed to load app "${appId}":`, error);
      }
      throw new Error(`App "${appId}" could not be loaded`);
    }
  }

  /**
   * Import the actual module based on app ID
   */
  private async importAppModule(appId: string): Promise<AppModule> {
    // Map app IDs to their module paths - only include apps that actually exist
    switch (appId) {
      case 'calculator':
        return import('../Apps/CalculatorApp');
      case 'file-explorer':
        return import('../Apps/FileExplorerApp');
      case 'settings':
        return import('../Apps/SettingsApp');
      case 'email':
        return import('../Apps/EmailApp');
      case 'site-builder':
        return import('../Apps/SiteBuilderApp');
      case 'app-store':
        return import('../Apps/AppStoreApp');
      case 'calendar':
        return import('../Apps/CalendarApp');
      case 'messages':
        return import('../Apps/MessagesApp');
      case 'orders-manager':
        return import('../Apps/OrdersManagerApp');
      case 'products-manager':
        return import('../Apps/ProductsManagerApp');
      case 'contacts':
        return import('../Apps/ContactsApp');
      case 'point-of-sale':
        return import('../Apps/PointOfSaleApp');
      case 'photoshop':
        return import('../Apps/PhotoshopApp');
      case 'mail':
        return import('../Apps/MailApp');
      default:
        throw new Error(`Unknown app ID: ${appId}`);
    }
  }

  /**
   * Preload critical applications for better performance
   */
  async preloadApps(appIds: string[]): Promise<void> {
    const preloadPromises = appIds.map(async (appId) => {
      if (!this.preloadedApps.has(appId)) {
        try {
          await this.loadApp(appId);
          this.preloadedApps.add(appId);
          console.log(`Preloaded app: ${appId}`);
        } catch (error) {
          console.warn(`Failed to preload app "${appId}":`, error);
        }
      }
    });

    await Promise.all(preloadPromises);
    console.log(`Preloaded ${appIds.length} apps`);
  }

  /**
   * Check if an app is available for loading
   */
  isAppAvailable(appId: string): boolean {
    const availableApps = [
      'calculator',
      'file-explorer',
      'settings',
      'email',
      'site-builder',
      'app-store',
      'messages',
      'orders-manager',
      'products-manager',
      'contacts',
      'point-of-sale',
      'photoshop',
      'mail',
    ];

    return availableApps.includes(appId);
  }

  /**
   * Get list of available app IDs
   */
  getAvailableApps(): string[] {
    return [
      'calculator',
      'file-explorer', 
      'settings',
      'email',
      'site-builder',
      'app-store',
      'messages',
      'orders-manager',
      'products-manager',
      'contacts',
      'point-of-sale',
      'photoshop',
      'mail'
    ];
  }

  /**
   * Clear loaded modules (useful for testing or memory management)
   */
  clearCache(): void {
    this.loadedModules.clear();
    this.loadingPromises.clear();
    this.preloadedApps.clear();
    console.log('App loader cache cleared');
  }

  /**
   * Setup automatic cleanup for memory management
   */
  setupAutoCleanup(): void {
    // Clear cache periodically to prevent memory leaks
    setInterval(() => {
      const cacheSize = this.loadedModules.size;
      if (cacheSize > 10) { // Arbitrary threshold
        console.log(`App cache size: ${cacheSize}, clearing old modules`);
        // Keep only the most recently used modules
        // This is a simple implementation - could be more sophisticated
        this.clearCache();
      }
    }, 5 * 60 * 1000); // Every 5 minutes
  }

  /**
   * Get cache statistics
   */
  getCacheStats(): { loaded: number; loading: number; preloaded: number } {
    return {
      loaded: this.loadedModules.size,
      loading: this.loadingPromises.size,
      preloaded: this.preloadedApps.size
    };
  }

  /**
   * Load and launch an application (alias for loadApp for backward compatibility)
   */
  async loadAndLaunch(appId: string, teamId?: string): Promise<BaseApp> {
    console.log(`Loading and launching app: ${appId}${teamId ? ` for team ${teamId}` : ''}`);
    return await this.loadApp(appId);
  }

  /**
   * Clear old modules based on age/usage (with threshold parameter)
   */
  clearOldModules(maxAge?: number): void {
    if (maxAge === 0) {
      this.clearCache();
      return;
    }
    
    // Simple implementation - clear cache if too many modules
    if (this.loadedModules.size > (maxAge || 10)) {
      this.clearCache();
    }
  }

  /**
   * Preload critical applications for faster startup
   */
  async preloadCriticalApps(): Promise<void> {
    const criticalApps = ['calculator', 'settings', 'file-explorer'];
    
    console.log('Preloading critical apps:', criticalApps);
    
    await Promise.all(
      criticalApps.map(async (appId) => {
        try {
          await this.preloadApps([appId]);
        } catch (error) {
          console.warn(`Failed to preload critical app "${appId}":`, error);
        }
      })
    );
  }

  /**
   * Get loader statistics (alias for getCacheStats)
   */
  getStats(): { loaded: number; loading: number; preloaded: number } {
    return this.getCacheStats();
  }
}

// Export singleton instance
export const appLoader = new AppLoader();
export default appLoader; 