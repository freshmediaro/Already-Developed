// App Registry - Module registration and management system
import type { App } from './Types';
import { BaseApp } from '../Apps/BaseApp';

type AppConstructor = new () => BaseApp;

interface RegisteredApp {
  info: App;
  constructor: AppConstructor;
  singleton?: boolean;
  instance?: BaseApp;
}

class AppRegistry {
  private apps: Map<string, RegisteredApp> = new Map();
  private categories: Map<string, string[]> = new Map();

  /**
   * Register an app class
   */
  register(
    appId: string,
    appConstructor: AppConstructor,
    options: { singleton?: boolean } = {}
  ): void {
    // Create temporary instance to get app info
    const tempInstance = new appConstructor();
    const appInfo = tempInstance.appInfo;
    
    // Clean up temporary instance
    if (tempInstance.isMounted()) {
      tempInstance.close();
    }

    const registeredApp: RegisteredApp = {
      info: appInfo,
      constructor: appConstructor,
      singleton: options.singleton || false,
    };

    this.apps.set(appId, registeredApp);

    // Update categories
    if (!this.categories.has(appInfo.category)) {
      this.categories.set(appInfo.category, []);
    }
    
    const categoryApps = this.categories.get(appInfo.category)!;
    if (!categoryApps.includes(appId)) {
      categoryApps.push(appId);
    }

    if (import.meta.env.VITE_APP_ENV === 'local') {
      console.log(`Registered app: ${appId} (${appInfo.name})`);
    }
  }

  /**
   * Unregister an app
   */
  unregister(appId: string): void {
    const registeredApp = this.apps.get(appId);
    if (!registeredApp) return;

    // Close existing instance if singleton
    if (registeredApp.instance?.isMounted()) {
      registeredApp.instance.close();
    }

    // Remove from categories
    const categoryApps = this.categories.get(registeredApp.info.category);
    if (categoryApps) {
      const index = categoryApps.indexOf(appId);
      if (index !== -1) {
        categoryApps.splice(index, 1);
      }
    }

    this.apps.delete(appId);
    if (import.meta.env.VITE_APP_ENV === 'local') {
      console.log(`Unregistered app: ${appId}`);
    }
  }

  /**
   * Create app instance
   */
  async createInstance(appId: string, teamId?: string): Promise<BaseApp | null> {
    const registeredApp = this.apps.get(appId);
    if (!registeredApp) {
      if (import.meta.env.VITE_APP_ENV === 'local') {
        console.error(`App not registered: ${appId}`);
      }
      return null;
    }

    // For singleton apps, return existing instance or create new one
    if (registeredApp.singleton) {
      if (!registeredApp.instance || !registeredApp.instance.isMounted()) {
        registeredApp.instance = new registeredApp.constructor();
      }
      return registeredApp.instance;
    }

    // For non-singleton apps, always create new instance
    return new registeredApp.constructor();
  }

  /**
   * Launch an app
   */
  async launchApp(appId: string, teamId?: string): Promise<string | null> {
    try {
      const instance = await this.createInstance(appId, teamId);
      if (!instance) return null;

      const windowId = await instance.launch(teamId);
      return windowId;
    } catch (error) {
      if (import.meta.env.VITE_APP_ENV === 'local') {
        console.error(`Failed to launch app ${appId}:`, error);
      }
      return null;
    }
  }

  /**
   * Get app info by ID
   */
  getAppInfo(appId: string): App | undefined {
    return this.apps.get(appId)?.info;
  }

  /**
   * Get all registered apps
   */
  getAllApps(): App[] {
    return Array.from(this.apps.values()).map(app => app.info);
  }

  /**
   * Get apps by category
   */
  getAppsByCategory(category: string): App[] {
    const appIds = this.categories.get(category) || [];
    return appIds
      .map(id => this.apps.get(id)?.info)
      .filter(Boolean) as App[];
  }

  /**
   * Get all categories
   */
  getCategories(): string[] {
    return Array.from(this.categories.keys());
  }

  /**
   * Get system apps (pre-installed)
   */
  getSystemApps(): App[] {
    return Array.from(this.apps.values())
      .filter(app => app.info.system)
      .map(app => app.info);
  }

  /**
   * Get installed apps for a team
   */
  getInstalledApps(teamId?: string): App[] {
    // For now, return all registered apps
    // In a real implementation, this would check against the database
    return Array.from(this.apps.values())
      .filter(app => app.info.installed)
      .map(app => app.info);
  }

  /**
   * Get available apps for installation
   */
  getAvailableApps(teamId?: string): App[] {
    return Array.from(this.apps.values())
      .filter(app => !app.info.installed)
      .map(app => app.info);
  }

  /**
   * Search apps by name or description
   */
  searchApps(query: string): App[] {
    const lowerQuery = query.toLowerCase();
    return Array.from(this.apps.values())
      .filter(app => 
        app.info.name.toLowerCase().includes(lowerQuery) ||
        app.info.description?.toLowerCase().includes(lowerQuery)
      )
      .map(app => app.info);
  }

  /**
   * Check if app is registered
   */
  isRegistered(appId: string): boolean {
    return this.apps.has(appId);
  }

  /**
   * Get running instances
   */
  getRunningInstances(): Array<{ appId: string; instance: BaseApp }> {
    const running: Array<{ appId: string; instance: BaseApp }> = [];
    
    this.apps.forEach((registeredApp, appId) => {
      if (registeredApp.instance?.isMounted()) {
        running.push({ appId, instance: registeredApp.instance });
      }
    });

    return running;
  }

  /**
   * Close all running instances
   */
  async closeAllInstances(): Promise<void> {
    const running = this.getRunningInstances();
    
    await Promise.all(
      running.map(({ instance }) => instance.close())
    );
  }

  /**
   * Close instances for a specific team
   */
  async closeTeamInstances(teamId: string): Promise<void> {
    const running = this.getRunningInstances();
    
    await Promise.all(
      running
        .filter(({ instance }) => instance.getContext()?.teamId === teamId)
        .map(({ instance }) => instance.close())
    );
  }

  /**
   * Register multiple apps at once
   */
  registerBatch(apps: Array<{
    appId: string;
    constructor: AppConstructor;
    options?: { singleton?: boolean };
  }>): void {
    apps.forEach(({ appId, constructor, options }) => {
      this.register(appId, constructor, options);
    });
  }

  /**
   * Get app statistics
   */
  getStats(): {
    total: number;
    system: number;
    installed: number;
    running: number;
    categories: Record<string, number>;
  } {
    const allApps = Array.from(this.apps.values());
    const stats = {
      total: allApps.length,
      system: allApps.filter(app => app.info.system).length,
      installed: allApps.filter(app => app.info.installed).length,
      running: this.getRunningInstances().length,
      categories: {} as Record<string, number>,
    };

    this.categories.forEach((appIds, category) => {
      stats.categories[category] = appIds.length;
    });

    return stats;
  }

  /**
   * Get installed apps only
   */
  getInstalledApps(): App[] {
    return this.getAllApps().filter(app => app.installed);
  }

  /**
   * Install an app (mark as installed)
   */
  async installApp(appId: string, teamId?: string): Promise<boolean> {
    const registeredApp = this.apps.get(appId);
    if (!registeredApp) return false;

    // Update app info
    registeredApp.info.installed = true;

    // Here you would typically make an API call to persist the installation
    // For now, we just update the local state
    if (import.meta.env.VITE_APP_ENV === 'local') {
      console.log(`Installed app: ${appId} for team: ${teamId || 'personal'}`);
    }
    
    return true;
  }

  /**
   * Uninstall an app
   */
  async uninstallApp(appId: string, teamId?: string): Promise<boolean> {
    const registeredApp = this.apps.get(appId);
    if (!registeredApp || registeredApp.info.system) return false;

    // Close running instance
    if (registeredApp.instance?.isMounted()) {
      await registeredApp.instance.close();
    }

    // Update app info
    registeredApp.info.installed = false;

    // Here you would typically make an API call to persist the uninstallation
    if (import.meta.env.VITE_APP_ENV === 'local') {
      console.log(`Uninstalled app: ${appId} for team: ${teamId || 'personal'}`);
    }
    
    return true;
  }

  /**
   * Update app info
   */
  updateAppInfo(appId: string, updates: Partial<App>): boolean {
    const registeredApp = this.apps.get(appId);
    if (!registeredApp) return false;

    Object.assign(registeredApp.info, updates);
    return true;
  }

  /**
   * Validate app before registration
   */
  private validateApp(appInfo: App): boolean {
    const required = ['id', 'name', 'component', 'category'];
    for (const field of required) {
      if (!(field in appInfo) || !appInfo[field as keyof App]) {
        if (import.meta.env.VITE_APP_ENV === 'local') {
          console.error(`App validation failed: missing ${field}`);
        }
        return false;
      }
    }
    return true;
  }
}

// Create singleton instance
export const appRegistry = new AppRegistry();

// Type for app registration helper
export type { AppConstructor };

export default appRegistry; 