import { IframeBaseApp, IframeAppConfig, IframeSecurityConfig } from './IframeBaseApp';
import type { App, UseWindowOptions } from '../Core/Types';

export interface MailServerConfig {
  serverUrl: string;
  protocol: 'sogo' | 'mailcow' | 'roundcube' | 'custom';
  domain?: string;
  port?: number;
  ssl?: boolean;
}

export class MailApp extends IframeBaseApp {
  private mailServerConfig?: MailServerConfig;

  constructor(mailServerConfig?: MailServerConfig) {
    // App configuration
    const appInfo: App = {
      id: 'mail',
      name: 'Mail',
      description: 'Professional email client',
      icon: 'fas fa-envelope',
      iconType: 'fontawesome',
      iconBackground: 'cyan-icon',
      category: 'communication',
      version: '1.0.0',
      permissions: ['mail.read', 'mail.write', 'mail.send'],
      teamScoped: true,
      component: 'MailApp',
      installed: true,
      system: false
    };

    // Default to demo server if no config provided
    const defaultConfig: MailServerConfig = {
      serverUrl: 'https://demo.sogo.nu/SOGo/',
      protocol: 'sogo',
      ssl: true,
    };

    const config = mailServerConfig || defaultConfig;

    // Iframe configuration for mail server
    const iframeConfig: IframeAppConfig = {
      url: config.serverUrl,
      allowedOrigins: [
        config.serverUrl,
        `https://${config.domain || 'localhost'}`,
        // Add common mail server origins
        'https://sogo.nu',
        'https://demo.sogo.nu',
      ],
      sandbox: [
        'allow-scripts',
        'allow-same-origin',
        'allow-forms',
        'allow-downloads',
        'allow-modals',
        'allow-popups',
        'allow-storage-access-by-user-activation',
        'allow-popups-to-escape-sandbox'
      ],
      allowFullscreen: false,
      loadingMessage: 'Loading Mail Client...',
      errorMessage: 'Failed to load mail client. Please check your mail server configuration.',
      communicationEnabled: true,
    };

    // Security configuration for mail client
    const securityConfig: IframeSecurityConfig = {
      referrerPolicy: 'strict-origin-when-cross-origin',
      allowedFeatures: [
        'clipboard-read',
        'clipboard-write',
        'encrypted-media',
        'fullscreen',
        'payment'
      ],
      // More restrictive CSP for mail content
      csp: "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self' https:; frame-src 'self';"
    };

    super('mail', appInfo, iframeConfig, securityConfig);

    this.mailServerConfig = config;
    this.setupMailMessageHandlers();
  }

  protected getWindowOptions(): UseWindowOptions {
    return {
      minWidth: 900,
      minHeight: 600,
      resizable: true,
      draggable: true,
      centered: true,
    };
  }

  protected onIframeLoaded(): void {
    super.onIframeLoaded();
    
    // Update window title
    this.updateWindowTitle('Mail - Ready');
    
    // Auto-login if credentials are available
    this.attemptAutoLogin();
    
    // Setup mail-specific features
    this.setupMailFeatures();
  }

  private setupMailMessageHandlers(): void {
    // Handle authentication status
    this.registerMessageHandler('auth', (data) => {
      this.handleAuthStatus(data);
    });

    // Handle unread count updates
    this.registerMessageHandler('unread', (data) => {
      this.handleUnreadCount(data);
    });

    // Handle compose requests
    this.registerMessageHandler('compose', (data) => {
      this.handleComposeRequest(data);
    });

    // Handle mail actions (send, delete, etc.)
    this.registerMessageHandler('action', (data) => {
      this.handleMailAction(data);
    });

    // Handle error messages
    this.registerMessageHandler('error', (data) => {
      this.handleMailError(data);
    });
  }

  private async attemptAutoLogin(): Promise<void> {
    if (!this.context?.teamId) return;

    try {
      // Get team's mail credentials
      const credentials = await this.getTeamMailCredentials();
      
      if (credentials) {
        // Send auto-login message to mail client
        this.sendMessageToIframe({
          type: 'login',
          payload: {
            username: credentials.username,
            password: credentials.password,
            domain: credentials.domain,
          }
        });
      }
    } catch (error) {
      console.error('Auto-login failed:', error);
      // Don't show error to user, let them login manually
    }
  }

  private setupMailFeatures(): void {
    // Setup periodic unread count checking
    setInterval(() => {
      this.requestUnreadCount();
    }, 30000); // Check every 30 seconds

    // Setup notification permissions
    this.requestNotificationPermissions();
  }

  private handleAuthStatus(data: any): void {
    if (data.authenticated) {
      this.updateWindowTitle('Mail - Inbox');
      this.showNotification('Successfully connected to mail server', 'success');
    } else {
      this.updateWindowTitle('Mail - Login Required');
      if (data.error) {
        this.showNotification(`Login failed: ${data.error}`, 'error');
      }
    }
  }

  private handleUnreadCount(data: any): void {
    const count = data.count || 0;
    
    // Update window title with unread count
    if (count > 0) {
      this.updateWindowTitle(`Mail (${count})`);
      
      // Update taskbar badge
      this.updateTaskbarBadge(count);
      
      // Show desktop notification for new mail
      if (data.newMail) {
        this.showNewMailNotification(data.newMail);
      }
    } else {
      this.updateWindowTitle('Mail');
      this.updateTaskbarBadge(0);
    }
  }

  private handleComposeRequest(data: any): void {
    // Handle compose mail requests from external sources
    const { to, subject, body } = data;
    
    this.sendMessageToIframe({
      type: 'compose',
      payload: { to, subject, body }
    });
    
    // Focus the mail app
    this.focus();
  }

  private handleMailAction(data: any): void {
    const { action, result, error } = data;
    
    switch (action) {
      case 'send':
        if (result === 'success') {
          this.showNotification('Email sent successfully', 'success');
        } else {
          this.showNotification(`Failed to send email: ${error}`, 'error');
        }
        break;
        
      case 'delete':
        if (result === 'success') {
          this.showNotification('Email deleted', 'info');
        }
        break;
        
      case 'move':
        if (result === 'success') {
          this.showNotification('Email moved', 'info');
        }
        break;
    }
  }

  private handleMailError(data: any): void {
    console.error('Mail client error:', data);
    this.showNotification(`Mail error: ${data.message}`, 'error');
  }

  private async getTeamMailCredentials(): Promise<any> {
    if (!this.context?.teamId) return null;

    try {
      // This would fetch from your API
      // const response = await fetch(`/api/teams/${this.context.teamId}/mail-credentials`);
      // return await response.json();
      
      // For demo, return null to require manual login
      return null;
    } catch (error) {
      console.error('Failed to get mail credentials:', error);
      return null;
    }
  }

  private requestUnreadCount(): void {
    this.sendMessageToIframe({
      type: 'getUnreadCount'
    });
  }

  private async requestNotificationPermissions(): Promise<void> {
    if ('Notification' in window && Notification.permission === 'default') {
      await Notification.requestPermission();
    }
  }

  private updateTaskbarBadge(count: number): void {
    // Update taskbar icon badge
    // This would integrate with your taskbar system
    console.log(`Update mail badge: ${count}`);
  }

  private showNewMailNotification(mailData: any): void {
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification(`New mail from ${mailData.from}`, {
        body: mailData.subject,
        icon: '/icons/mail-icon.png',
        tag: 'new-mail',
        requireInteraction: false,
      });
    }
  }

  private focus(): void {
    if (!this.context) return;
    
    // Focus the window and iframe
    this.context.windowElement.focus();
    this.iframe?.focus();
  }

  // Public methods for external integration
  public composeEmail(to?: string, subject?: string, body?: string): void {
    this.sendMessageToIframe({
      type: 'compose',
      payload: { to, subject, body }
    });
  }

  public showInbox(): void {
    this.sendMessageToIframe({
      type: 'navigate',
      payload: { view: 'inbox' }
    });
  }

  public showFolder(folderName: string): void {
    this.sendMessageToIframe({
      type: 'navigate',
      payload: { view: 'folder', folder: folderName }
    });
  }

  // Mail app specific context menu
  protected setupContextMenu(): void {
    if (!this.context) return;

    this.addEventListener(this.context.contentElement, 'contextmenu', (e) => {
      e.preventDefault();
      this.showMailContextMenu(e as MouseEvent);
    });
  }

  private showMailContextMenu(event: MouseEvent): void {
    const menu = [
      { label: 'Compose New Email', action: () => this.composeEmail() },
      { label: 'Refresh Inbox', action: () => this.refreshMail() },
      { label: 'Show Inbox', action: () => this.showInbox() },
      { label: 'Mail Settings', action: () => this.showMailSettings() },
      { label: 'Reload Mail Client', action: () => this.reloadIframe() },
    ];

    console.log('Show mail context menu at:', event.clientX, event.clientY, menu);
  }

  private refreshMail(): void {
    this.sendMessageToIframe({
      type: 'refresh'
    });
  }

  private showMailSettings(): void {
    this.sendMessageToIframe({
      type: 'navigate',
      payload: { view: 'preferences' }
    });
  }

  private showNotification(message: string, type: 'success' | 'error' | 'info' = 'info'): void {
    console.log(`${type.toUpperCase()}: ${message}`);
    
    // Integrate with desktop notification system
    // notificationSystem.show({
    //   title: 'Mail',
    //   message,
    //   type,
    //   icon: 'fa-envelope'
    // });
  }

  // Lifecycle hooks
  async onMount(): Promise<void> {
    console.log('Mail app mounted');
    this.setupContextMenu();
  }

  async onUnmount(): Promise<void> {
    console.log('Mail app unmounted');
  }

  onActivate(): void {
    console.log('Mail app activated');
    this.iframe?.focus();
    
    // Refresh unread count when activated
    this.requestUnreadCount();
  }

  onDeactivate(): void {
    console.log('Mail app deactivated');
  }

  onTeamSwitch(context: any, newTeamId: string, oldTeamId?: string): void {
    // Reload mail client for new team
    console.log(`Mail app switching from team ${oldTeamId} to ${newTeamId}`);
    
    // Clear current session and reload
    this.reloadIframe();
  }
}

// Factory function to create mail app with specific server config
export function createMailApp(serverConfig: MailServerConfig): MailApp {
  return new MailApp(serverConfig);
}

// Preset configurations for common mail servers
export const MailServerPresets = {
  sogo: (domain: string): MailServerConfig => ({
    serverUrl: `https://mail.${domain}/SOGo/`,
    protocol: 'sogo',
    domain,
    ssl: true,
  }),
  
  mailcow: (domain: string): MailServerConfig => ({
    serverUrl: `https://mail.${domain}/`,
    protocol: 'mailcow',
    domain,
    ssl: true,
  }),
  
  roundcube: (domain: string): MailServerConfig => ({
    serverUrl: `https://mail.${domain}/roundcube/`,
    protocol: 'roundcube',
    domain,
    ssl: true,
  }),
};

export default MailApp; 