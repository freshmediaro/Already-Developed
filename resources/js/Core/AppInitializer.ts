// App Initializer - Registers all default applications with the AppRegistry
import { appRegistry } from './AppRegistry';

// Core default apps
import CalculatorApp from '../Apps/CalculatorApp';
import FileExplorerApp from '../Apps/FileExplorerApp';
import SettingsApp from '../Apps/SettingsApp';
import EmailApp from '../Apps/EmailApp';
import SiteBuilderApp from '../Apps/SiteBuilderApp';
import AppStoreApp from '../Apps/AppStoreApp';
import CalendarApp from '../Apps/CalendarApp';
import PhotoshopApp from '../Apps/PhotoshopApp';
import MailApp from '../Apps/MailApp';

// Business apps
import MessagesApp from '../Apps/MessagesApp';
import OrdersManagerApp from '../Apps/OrdersManagerApp';
import ProductsManagerApp from '../Apps/ProductsManagerApp';
import ContactsApp from '../Apps/ContactsApp';
import PointOfSaleApp from '../Apps/PointOfSaleApp';

export class AppInitializer {
  static async initialize(): Promise<void> {
    try {
      this.registerDefaultApps();
      this.registerBusinessApps();
      console.log('App initialization completed successfully');
    } catch (error) {
      console.error('Failed to initialize apps:', error);
      throw error;
    }
  }

  private static registerDefaultApps(): void {
    // Core system applications that come pre-installed with every tenant
    appRegistry.register('calculator', CalculatorApp, { singleton: false });
    appRegistry.register('file-explorer', FileExplorerApp, { singleton: false });
    appRegistry.register('settings', SettingsApp, { singleton: true });
    appRegistry.register('app-store', AppStoreApp, { singleton: true });
    
    // Communication & Productivity apps
    appRegistry.register('email', EmailApp, { singleton: true });
    appRegistry.register('mail', MailApp, { singleton: true });
    appRegistry.register('calendar', CalendarApp, { singleton: true });
    
    // Creative & Business apps
    appRegistry.register('site-builder', SiteBuilderApp, { singleton: true });
    appRegistry.register('photoshop', PhotoshopApp, { singleton: true });
    
    console.log('Default apps registered with AppRegistry');
  }

  private static registerBusinessApps(): void {
    // Business applications for SaaS functionality
    appRegistry.register('messages', MessagesApp, { singleton: true });
    appRegistry.register('orders-manager', OrdersManagerApp, { singleton: true });
    appRegistry.register('products-manager', ProductsManagerApp, { singleton: true });
    appRegistry.register('contacts', ContactsApp, { singleton: true });
    appRegistry.register('point-of-sale', PointOfSaleApp, { singleton: true });
    
    console.log('Business apps registered with AppRegistry');
  }

  static getRegisteredApps(): string[] {
    return [
      // Core system apps
      'calculator',
      'file-explorer',
      'settings',
      'app-store',
      
      // Communication & Productivity apps
      'email',
      'mail',
      'calendar',
      
      // Creative & Business apps
      'site-builder',
      'photoshop',
      
      // Business management apps
      'messages',
      'orders-manager',
      'products-manager',
      'contacts',
      'point-of-sale'
    ];
  }

  static getCoreApps(): string[] {
    return [
      'calculator',
      'file-explorer',
      'settings',
      'email',
      'site-builder',
      'app-store',
      'calendar',
      'photoshop',
      'mail'
    ];
  }

  static getBusinessApps(): string[] {
    return [
      'messages',
      'orders-manager',
      'products-manager',
      'contacts',
      'point-of-sale',
      'mail'
    ];
  }

  /**
   * Get apps that should be preloaded for better performance
   */
  static getCriticalApps(): string[] {
    return [
      'calculator',
      'file-explorer',
      'settings',
      'app-store'
    ];
  }

  /**
   * Get apps that should be lazy-loaded when needed
   */
  static getLazyLoadedApps(): string[] {
    return [
      'email',
      'site-builder',
      'messages',
      'orders-manager',
      'products-manager',
      'contacts',
      'point-of-sale',
      'photoshop',
      'mail'
    ];
  }
}

export default AppInitializer; 