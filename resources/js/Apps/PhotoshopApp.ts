import { IframeBaseApp, IframeAppConfig, IframeSecurityConfig } from './IframeBaseApp';
import type { App, UseWindowOptions } from '../Core/Types';

export class PhotoshopApp extends IframeBaseApp {
  constructor() {
    // App configuration
    const appInfo: App = {
      id: 'photoshop',
      name: 'Photoshop',
      description: 'Professional photo editing powered by Photopea',
      icon: 'fas fa-palette',
      iconType: 'fontawesome',
      iconBackground: 'purple-icon',
      category: 'media',
      version: '1.0.0',
      permissions: ['files.read', 'files.write'],
      teamScoped: true,
      component: 'PhotoshopApp',
      installed: true,
      system: false
    };

    // Iframe configuration for Photopea
    const iframeConfig: IframeAppConfig = {
      url: 'https://www.photopea.com/',
      allowedOrigins: [
        'https://www.photopea.com',
        'https://photopea.com'
      ],
      sandbox: [
        'allow-scripts',
        'allow-same-origin',
        'allow-forms',
        'allow-downloads',
        'allow-modals',
        'allow-popups',
        'allow-storage-access-by-user-activation'
      ],
      allowFullscreen: true,
      loadingMessage: 'Loading Photoshop (Photopea)...',
      errorMessage: 'Failed to load Photoshop. Please check your internet connection.',
      communicationEnabled: true,
    };

    // Security configuration
    const securityConfig: IframeSecurityConfig = {
      referrerPolicy: 'strict-origin-when-cross-origin',
      allowedFeatures: [
        'accelerometer',
        'camera',
        'encrypted-media',
        'fullscreen',
        'gyroscope',
        'magnetometer',
        'microphone',
        'payment',
        'picture-in-picture',
        'usb'
      ],
    };

    super('photoshop', appInfo, iframeConfig, securityConfig);

    // Setup message handlers for Photopea communication
    this.setupPhotoshhopMessageHandlers();
  }

  protected getWindowOptions(): UseWindowOptions {
    return {
      minWidth: 1000,
      minHeight: 700,
      resizable: true,
      draggable: true,
      centered: true,
    };
  }

  protected onIframeLoaded(): void {
    super.onIframeLoaded();
    
    // Update window title to show it's ready
    this.updateWindowTitle('Photoshop - Ready');
    
    // Send initial configuration to Photopea if needed
    this.sendInitialConfig();
  }

  private setupPhotoshhopMessageHandlers(): void {
    // Handle file save events from Photopea
    this.registerMessageHandler('save', (data) => {
      this.handleFileSave(data);
    });

    // Handle file open requests
    this.registerMessageHandler('open', (data) => {
      this.handleFileOpen(data);
    });

    // Handle title updates
    this.registerMessageHandler('title', (data) => {
      if (data.title) {
        this.updateWindowTitle(`Photoshop - ${data.title}`);
      }
    });

    // Handle status updates
    this.registerMessageHandler('status', (data) => {
      this.handleStatusUpdate(data);
    });
  }

  private sendInitialConfig(): void {
    // Send configuration to Photopea
    const config = {
      type: 'config',
      payload: {
        theme: this.getUserTheme(),
        language: this.getUserLanguage(),
        teamId: this.context?.teamId,
      }
    };

    this.sendMessageToIframe(config);
  }

  private handleFileSave(data: any): void {
    console.log('Photopea file save:', data);
    
    // Here you would typically:
    // 1. Show save dialog or use team's file system
    // 2. Save to the tenant's file storage
    // 3. Update file permissions and metadata
    
    // For now, just log the save action
    if (this.context?.teamId) {
      // Could integrate with FileExplorerApp or direct file API
      this.saveToTeamStorage(data);
    }
  }

  private handleFileOpen(data: any): void {
    console.log('Photopea file open request:', data);
    
    // Here you would typically:
    // 1. Show file picker from team's file system
    // 2. Load file from tenant storage
    // 3. Send file data back to Photopea
    
    this.openFromTeamStorage();
  }

  private handleStatusUpdate(data: any): void {
    // Update app status based on Photopea's state
    if (data.busy) {
      this.setIframeLoading(true);
    } else {
      this.setIframeLoading(false);
    }

    // Could update taskbar icon or show progress
    if (data.progress) {
      this.updateProgress(data.progress);
    }
  }

  private async saveToTeamStorage(fileData: any): Promise<void> {
    if (!this.context?.teamId) return;

    try {
      // This would integrate with your file API
      // const response = await fileApi.save({
      //   teamId: this.context.teamId,
      //   fileName: fileData.name,
      //   data: fileData.blob,
      //   mimeType: fileData.type,
      //   folder: 'photoshop-projects'
      // });

      console.log('File saved to team storage:', fileData.name);
      
      // Show success notification
      this.showNotification('File saved successfully', 'success');
      
    } catch (error) {
      console.error('Failed to save file:', error);
      this.showNotification('Failed to save file', 'error');
    }
  }

  private async openFromTeamStorage(): Promise<void> {
    if (!this.context?.teamId) return;

    try {
      // This would show file picker integrated with FileExplorerApp
      // const file = await fileApi.pickFile({
      //   teamId: this.context.teamId,
      //   types: ['image/*', '.psd', '.psb'],
      //   folder: 'photoshop-projects'
      // });

      // For demo, simulate file selection
      console.log('Opening file from team storage');
      
      // Send file to Photopea
      // this.sendMessageToIframe({
      //   type: 'loadFile',
      //   payload: {
      //     name: file.name,
      //     data: file.blob
      //   }
      // });
      
    } catch (error) {
      console.error('Failed to open file:', error);
      this.showNotification('Failed to open file', 'error');
    }
  }

  private getUserTheme(): string {
    // Get user's current theme preference
    // This would integrate with your settings system
    return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
  }

  private getUserLanguage(): string {
    // Get user's language preference
    return navigator.language || 'en-US';
  }

  private updateProgress(progress: number): void {
    // Update progress indicator if needed
    console.log(`Photoshop progress: ${progress}%`);
  }

  private showNotification(message: string, type: 'success' | 'error' | 'info' = 'info'): void {
    // This would integrate with your notification system
    console.log(`${type.toUpperCase()}: ${message}`);
    
    // Could emit event for desktop notification system
    // notificationSystem.show({
    //   title: 'Photoshop',
    //   message,
    //   type,
    //   icon: 'fa-palette'
    // });
  }

  // Override to add Photoshop-specific context menu
  protected setupContextMenu(): void {
    // Add right-click context menu for Photoshop-specific actions
    if (!this.context) return;

    this.addEventListener(this.context.contentElement, 'contextmenu', (e) => {
      e.preventDefault();
      
      // Show custom context menu
      this.showPhotoshopContextMenu(e as MouseEvent);
    });
  }

  private showPhotoshopContextMenu(event: MouseEvent): void {
    // Create context menu with Photoshop-specific options
    const menu = [
      { label: 'Reload Photoshop', action: () => this.reloadIframe() },
      { label: 'Save Project', action: () => this.triggerSave() },
      { label: 'Open from Files', action: () => this.openFromTeamStorage() },
      { label: 'Help', action: () => this.showHelp() },
    ];

    // This would integrate with your context menu system
    console.log('Show context menu at:', event.clientX, event.clientY, menu);
  }

  private triggerSave(): void {
    // Trigger save action in Photopea
    this.sendMessageToIframe({
      type: 'action',
      payload: { action: 'save' }
    });
  }

  private showHelp(): void {
    // Open help documentation
    window.open('https://www.photopea.com/learn', '_blank');
  }

  // Lifecycle hooks specific to Photoshop
  async onMount(): Promise<void> {
    console.log('Photoshop app mounted');
    this.setupContextMenu();
  }

  async onUnmount(): Promise<void> {
    console.log('Photoshop app unmounted');
    // Any cleanup specific to Photoshop
  }

  onActivate(): void {
    console.log('Photoshop app activated');
    // Focus iframe if needed
    this.iframe?.focus();
  }

  onDeactivate(): void {
    console.log('Photoshop app deactivated');
  }
}

export default PhotoshopApp; 