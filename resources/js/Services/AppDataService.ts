// App Data Service - Provides consistent app data to all UI components
import { appRegistry } from '../Core/AppRegistry';
import type { App } from '../Core/Types';

export interface UIAppData {
  id: string;
  appId: string; // Alias for compatibility
  name: string;
  title: string; // Alias for compatibility
  icon: string;
  iconType?: 'image' | 'fontawesome' | 'css';
  iconBackground?: string;
  iconImage?: string;
  category: string;
  description?: string;
  isActive?: boolean;
  hasNotification?: boolean;
  windowId?: string;
  isPinned?: boolean;
  lastUsed?: Date;
  installed: boolean;
  system: boolean;
  teamScoped: boolean;
  version: string;
  author?: string;
  permissions: string[];
}

class AppDataService {
  /**
   * Get all apps formatted for UI components
   */
  getAllAppsForUI(): UIAppData[] {
    const registeredApps = appRegistry.getAllApps();
    return registeredApps.map(this.formatAppForUI);
  }

  /**
   * Get installed apps only
   */
  getInstalledAppsForUI(): UIAppData[] {
    const installedApps = appRegistry.getInstalledApps();
    return installedApps.map(this.formatAppForUI);
  }

  /**
   * Get apps by category
   */
  getAppsByCategoryForUI(category: string): UIAppData[] {
    const categoryApps = appRegistry.getAppsByCategory(category);
    return categoryApps.map(this.formatAppForUI);
  }

  /**
   * Get system apps
   */
  getSystemAppsForUI(): UIAppData[] {
    const systemApps = appRegistry.getSystemApps();
    return systemApps.map(this.formatAppForUI);
  }

  /**
   * Get app data for taskbar (running apps with window info)
   */
  getTaskbarApps(activeWindows: any[] = []): UIAppData[] {
    const installedApps = this.getInstalledAppsForUI();
    
    return installedApps.map(app => {
      const activeWindow = activeWindows.find(w => w.appId === app.id);
      return {
        ...app,
        isActive: !!activeWindow,
        hasNotification: false, // TODO: Implement notification system
        windowId: activeWindow?.id
      };
    });
  }

  /**
   * Get app data for start menu with categories
   */
  getStartMenuData(): {
    pinnedApps: UIAppData[];
    systemApps: UIAppData[];
    businessApps: UIAppData[];
    productivityApps: UIAppData[];
    allApps: UIAppData[];
  } {
    const allApps = this.getInstalledAppsForUI();

    return {
      pinnedApps: allApps.filter(app => app.isPinned),
      systemApps: allApps.filter(app => 
        ['system', 'utilities'].includes(app.category) ||
        ['file-explorer', 'settings', 'calculator'].includes(app.id)
      ),
      businessApps: allApps.filter(app => 
        ['business', 'ecommerce'].includes(app.category) ||
        ['contacts', 'orders-manager', 'products-manager', 'point-of-sale'].includes(app.id)
      ),
      productivityApps: allApps.filter(app => 
        ['productivity', 'communication'].includes(app.category) ||
        ['email', 'calendar', 'messages', 'site-builder'].includes(app.id)
      ),
      allApps
    };
  }

  /**
   * Format app data for UI components
   */
  private formatAppForUI(app: App): UIAppData {
    return {
      id: app.id,
      appId: app.id, // Compatibility alias
      name: app.name,
      title: app.name, // Compatibility alias
      icon: app.icon,
      iconType: app.iconType || 'fontawesome',
      iconBackground: app.iconBackground,
      iconImage: app.iconImage,
      category: app.category,
      description: app.description,
      installed: app.installed,
      system: app.system,
      teamScoped: app.teamScoped,
      version: app.version,
      author: app.author,
      permissions: app.permissions,
      // Default UI states
      isActive: false,
      hasNotification: false,
      isPinned: false, // TODO: Load from user preferences
    };
  }

  /**
   * Refresh data when app registry changes
   */
  refreshAppData(): void {
    // This could emit events to notify components of data changes
    // For now, components should call the get methods when they need fresh data
  }
}

// Export singleton instance
export const appDataService = new AppDataService();
export default appDataService;