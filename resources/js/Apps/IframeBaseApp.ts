import { BaseApp, AppContext } from './BaseApp';
import type { App, UseWindowOptions } from '../Core/Types';

export interface IframeAppConfig {
  url: string;
  allowedOrigins?: string[];
  sandbox?: string[];
  allowFullscreen?: boolean;
  loadingMessage?: string;
  errorMessage?: string;
  communicationEnabled?: boolean;
}

export interface IframeSecurityConfig {
  csp?: string;
  referrerPolicy?: ReferrerPolicy;
  allowedFeatures?: string[];
}

export abstract class IframeBaseApp extends BaseApp {
  protected iframe?: HTMLIFrameElement;
  protected iframeConfig: IframeAppConfig;
  protected securityConfig: IframeSecurityConfig;
  protected messageHandlers = new Map<string, (data: any) => void>();

  constructor(
    appId: string,
    appInfo: App,
    iframeConfig: IframeAppConfig,
    securityConfig: IframeSecurityConfig = {}
  ) {
    super(appId, appInfo);
    this.iframeConfig = iframeConfig;
    this.securityConfig = securityConfig;
  }

  protected async render(): Promise<void> {
    if (!this.context) return;

    // Show loading state
    this.showLoading(this.iframeConfig.loadingMessage || 'Loading application...');

    try {
      // Create iframe element
      this.iframe = this.createSecureIframe();
      
      // Replace loading content with iframe
      this.context.contentElement.innerHTML = '';
      this.context.contentElement.appendChild(this.iframe);

      // Setup iframe event listeners
      this.setupIframeEventListeners();

      // Setup security policies
      this.applySecurity();

      // Load the iframe source
      await this.loadIframe();

    } catch (error) {
      console.error(`Failed to load iframe app ${this.appId}:`, error);
      this.showError(
        this.iframeConfig.errorMessage || 'Failed to load application',
        () => this.render()
      );
    }
  }

  private createSecureIframe(): HTMLIFrameElement {
    const iframe = document.createElement('iframe');
    
    // Basic iframe attributes
    iframe.style.width = '100%';
    iframe.style.height = '100%';
    iframe.style.border = 'none';
    iframe.style.display = 'block';
    
    // Security attributes
    if (this.iframeConfig.sandbox) {
      iframe.sandbox.add(...this.iframeConfig.sandbox);
    } else {
      // Default secure sandbox
      iframe.sandbox.add(
        'allow-scripts',
        'allow-same-origin',
        'allow-forms',
        'allow-downloads'
      );
    }

    if (this.iframeConfig.allowFullscreen) {
      iframe.setAttribute('allowfullscreen', 'true');
    }

    // Referrer policy for privacy
    iframe.referrerPolicy = this.securityConfig.referrerPolicy || 'strict-origin-when-cross-origin';

    // Feature policy / permissions policy
    if (this.securityConfig.allowedFeatures) {
      iframe.setAttribute('allow', this.securityConfig.allowedFeatures.join('; '));
    }

    return iframe;
  }

  private async loadIframe(): Promise<void> {
    return new Promise((resolve, reject) => {
      if (!this.iframe) {
        reject(new Error('Iframe not created'));
        return;
      }

      const timeout = setTimeout(() => {
        reject(new Error('Iframe load timeout'));
      }, 30000); // 30 second timeout

      this.iframe.onload = () => {
        clearTimeout(timeout);
        this.onIframeLoaded();
        resolve();
      };

      this.iframe.onerror = () => {
        clearTimeout(timeout);
        reject(new Error('Failed to load iframe'));
      };

      // Set the source to trigger loading
      this.iframe.src = this.iframeConfig.url;
    });
  }

  private setupIframeEventListeners(): void {
    if (!this.iframe || !this.iframeConfig.communicationEnabled) return;

    // Setup secure message passing
    const messageHandler: EventListener = (event: Event) => {
      const messageEvent = event as MessageEvent;
      
      // Verify origin if allowed origins are specified
      if (this.iframeConfig.allowedOrigins && 
          !this.iframeConfig.allowedOrigins.includes(messageEvent.origin)) {
        console.warn(`Message from untrusted origin: ${messageEvent.origin}`);
        return;
      }

      this.handleIframeMessage(messageEvent.data, messageEvent.origin);
    };

    window.addEventListener('message', messageHandler);
    
    // Store cleanup function
    this.eventListeners.push(() => window.removeEventListener('message', messageHandler));
  }

  private applySecurity(): void {
    if (!this.context || !this.iframe) return;

    // Apply CSP if specified
    if (this.securityConfig.csp) {
      const metaCSP = document.createElement('meta');
      metaCSP.httpEquiv = 'Content-Security-Policy';
      metaCSP.content = this.securityConfig.csp;
      document.head.appendChild(metaCSP);
    }
  }

  protected onIframeLoaded(): void {
    // Override in subclasses for custom loading behavior
    console.log(`Iframe app ${this.appId} loaded successfully`);
  }

  protected handleIframeMessage(data: any, origin: string): void {
    // Override in subclasses for custom message handling
    if (typeof data === 'object' && data.type) {
      const handler = this.messageHandlers.get(data.type);
      if (handler) {
        handler(data.payload);
      }
    }
  }

  protected registerMessageHandler(type: string, handler: (data: any) => void): void {
    this.messageHandlers.set(type, handler);
  }

  protected sendMessageToIframe(message: any): void {
    if (!this.iframe || !this.iframe.contentWindow) return;

    try {
      this.iframe.contentWindow.postMessage(message, '*');
    } catch (error) {
      console.error('Failed to send message to iframe:', error);
    }
  }

  protected getWindowOptions(): UseWindowOptions {
    return {
      minWidth: 800,
      minHeight: 600,
      resizable: true,
      draggable: true,
      centered: true,
      ...super.getWindowOptions(),
    };
  }

  // Override cleanup to handle iframe-specific cleanup
  async close(): Promise<void> {
    if (this.iframe) {
      // Clean up iframe
      this.iframe.src = 'about:blank';
      this.iframe.remove();
      this.iframe = undefined;
    }

    await super.close();
  }

  // Utility methods for subclasses
  protected reloadIframe(): void {
    if (this.iframe) {
      this.iframe.src = this.iframe.src;
    }
  }

  protected navigateIframe(url: string): void {
    if (this.iframe && this.iframeConfig.allowedOrigins?.some(origin => url.startsWith(origin))) {
      this.iframe.src = url;
    } else {
      console.warn('Navigation to unauthorized URL blocked:', url);
    }
  }

  protected setIframeLoading(loading: boolean): void {
    if (!this.iframe) return;
    
    if (loading) {
      this.iframe.style.opacity = '0.5';
      this.iframe.style.pointerEvents = 'none';
    } else {
      this.iframe.style.opacity = '1';
      this.iframe.style.pointerEvents = 'auto';
    }
  }
}

export default IframeBaseApp; 