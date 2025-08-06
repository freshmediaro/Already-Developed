// App Store Application - Extracted from original app.js
import { BaseApp, type AppContext } from './BaseApp';
import { eventSystem } from '../Core/EventSystem';
import { apiService } from '../Tenant/ApiService';
import type { Window, App } from '../Core/Types';

interface FeaturedApp {
  title: string;
  subtitle: string;
  section: string;
  image: string;
}

interface AppItem {
  name: string;
  subtitle: string;
  price: string;
  priceDescription: string;
  iconClass: string;
  iconBgClass: string;
  author: string;
}

interface AppSection {
  title: string;
  seeAll: string;
  apps: AppItem[];
}

export class AppStoreApp extends BaseApp {
  public readonly id = 'app-store';
  public readonly name = 'AppStore';
  public readonly iconClass = 'fa-store';
  public readonly iconBgClass = 'green-icon';

  private featuredApps: FeaturedApp[] = [];
  private appSections: AppSection[] = [];
  private lastScrollPosition: number = 0;
  private currentDetailPanel?: HTMLElement;

  constructor() {
    const appInfo: App = {
      id: 'app-store',
      name: 'AppStore',
      icon: 'fas fa-store',
      iconType: 'fontawesome',
      iconBackground: 'green-icon',
      component: 'AppStoreApp',
      category: 'system',
      permissions: ['read', 'write'],
      installed: true,
      system: true,
      teamScoped: false,
      version: '1.0.0',
      description: 'Browse and install applications for your workspace',
    };
    super('app-store', appInfo);
  }

  async onMount(context: AppContext): Promise<void> {
    this.initializeElements(context.windowElement);
    this.loadAppStoreData();
    this.setupSidebar();
    this.renderFeaturedApps();
    this.renderAppSections();
    this.setupAppCardClickHandlers();
  }

  async onUnmount(): Promise<void> {
    this.removeAppDetailPanel();
  }

  async onFocus(): Promise<void> {
    // Refresh app store content if needed
    await this.refreshAppStore();
  }

  async onBlur(): Promise<void> {
    // Close any open detail panels
    this.removeAppDetailPanel();
  }

  private initializeElements(windowElement: HTMLElement): void {
    this.featuredContainer = windowElement.querySelector('#featured-cards') as HTMLElement;
    this.sectionsContainer = windowElement.querySelector('#appstore-sections') as HTMLElement;
    this.mainContent = windowElement.querySelector('.window-main-content') as HTMLElement;

    if (!this.featuredContainer || !this.sectionsContainer || !this.mainContent) {
      console.error('App Store: Required elements not found');
    }
  }

  private loadAppStoreData(): void {
    // Sample featured apps data
    this.featuredApps = [
      {
        title: 'Best Photo Editing Apps',
        subtitle: 'Professional editing tools',
        section: 'Photography',
        image: 'img/featured/photo-editing.jpg'
      },
      {
        title: 'Top Productivity Apps',
        subtitle: 'Get more done',
        section: 'Productivity',
        image: 'img/featured/productivity.jpg'
      }
    ];

    // Sample app sections data
    this.appSections = [
      {
        title: 'Essential Apps',
        seeAll: '#all-essential',
        apps: [
          {
            name: 'Photo Editor Pro',
            subtitle: 'Professional photo editing',
            price: 'Get App',
            priceDescription: 'Free',
            iconClass: 'fa-camera',
            iconBgClass: 'blue-icon',
            author: 'Photo Apps Inc.'
          },
          {
            name: 'Task Manager',
            subtitle: 'Organize your work',
            price: '$9.99',
            priceDescription: 'One-time purchase',
            iconClass: 'fa-tasks',
            iconBgClass: 'green-icon',
            author: 'Productivity LLC'
          },
          {
            name: 'Note Taking',
            subtitle: 'Capture your thoughts',
            price: 'Installed',
            priceDescription: 'Already installed',
            iconClass: 'fa-sticky-note',
            iconBgClass: 'yellow-icon',
            author: 'Notes Co.'
          }
        ]
      },
      {
        title: 'Creative Tools',
        seeAll: '#all-creative',
        apps: [
          {
            name: 'Vector Designer',
            subtitle: 'Create stunning graphics',
            price: '$19.99',
            priceDescription: 'Professional license',
            iconClass: 'fa-vector-square',
            iconBgClass: 'purple-icon',
            author: 'Design Studio'
          },
          {
            name: 'Music Maker',
            subtitle: 'Compose and edit music',
            price: 'Get App',
            priceDescription: 'Free trial',
            iconClass: 'fa-music',
            iconBgClass: 'pink-icon',
            author: 'Audio Pro'
          }
        ]
      },
      {
        title: 'Business Apps',
        seeAll: '#all-business',
        apps: [
          {
            name: 'Invoice Generator',
            subtitle: 'Create professional invoices',
            price: '$4.99',
            priceDescription: 'Monthly subscription',
            iconClass: 'fa-file-invoice',
            iconBgClass: 'blue-icon',
            author: 'Business Tools'
          },
          {
            name: 'CRM Manager',
            subtitle: 'Manage customer relationships',
            price: '$29.99',
            priceDescription: 'Monthly subscription',
            iconClass: 'fa-users',
            iconBgClass: 'green-icon',
            author: 'CRM Solutions'
          }
        ]
      }
    ];
  }

  private setupSidebar(): void {
    if (!this.windowElement) return;

    // Ensure sidebar toggle and overlay exist
    this.ensureSidebarElements();

    // Update sidebar for this window if global function exists
    if (typeof (window as any).updateSidebarForWindow === 'function') {
      (window as any).updateSidebarForWindow(this.windowElement);
    }
  }

  private ensureSidebarElements(): void {
    // Implementation for ensuring sidebar elements exist
    // This would be similar to the original ensureSidebarElements function
  }

  private renderFeaturedApps(): void {
    if (!this.featuredContainer) return;

    this.featuredContainer.innerHTML = '';
    
    this.featuredApps.forEach(card => {
      const cardElement = document.createElement('div');
      cardElement.className = 'featured-card';
      cardElement.innerHTML = `
        <div class="featured-card-content">
          <span class="section-link">${card.section}</span>
          <h2>${card.title}</h2>
          <p>${card.subtitle}</p>
        </div>
        <img src="${card.image}" alt="${card.title}">
      `;
      
      this.featuredContainer?.appendChild(cardElement);
    });
  }

  private renderAppSections(): void {
    if (!this.sectionsContainer) return;

    this.sectionsContainer.innerHTML = '';
    
    this.appSections.forEach(section => {
      const sectionElement = document.createElement('div');
      sectionElement.className = 'appstore-section';
      
      // Create apps HTML
      const appsHTML = section.apps.map((app, idx) => {
        const priceLower = app.price.toLowerCase();
        const isFree = priceLower === 'get app';
        const isInstalled = priceLower === 'installed';
        const priceClass = isInstalled
          ? 'app-price-button--installed'
          : isFree
            ? 'app-price-button--free'
            : 'app-price-button--paid';
            
        return `
          <div class="app-card" data-app-section="${section.title}" data-app-index="${idx}">
            <div class="app-card-content">
              <div class="icon-container ${app.iconBgClass}">
                <i class="fas ${app.iconClass}"></i>
              </div>
              <h3>${app.name}</h3>
              <p>${app.subtitle}</p>
            </div>
            <div class="app-card-footer">
              <button class="app-price-button ${priceClass}">${app.price}</button>
              <p class="app-price-description">${app.priceDescription}</p>
            </div>
          </div>
        `;
      }).join('');

      sectionElement.innerHTML = `
        <div class="section-header">
          <h2>${section.title}</h2>
          <a href="${section.seeAll}">See All</a>
        </div>
        <div class="appstore-grid">
          ${appsHTML}
        </div>
      `;
      
      this.sectionsContainer?.appendChild(sectionElement);
    });
  }

  private setupAppCardClickHandlers(): void {
    if (!this.sectionsContainer) return;

    this.sectionsContainer.querySelectorAll('.app-card').forEach(card => {
      card.addEventListener('click', (e) => {
        // Prevent double opening
        if (this.mainContent?.querySelector('.app-detail-panel')) return;

        const sectionTitle = (card as HTMLElement).getAttribute('data-app-section');
        const appIdx = parseInt((card as HTMLElement).getAttribute('data-app-index') || '0', 10);
        
        const section = this.appSections.find(s => s.title === sectionTitle);
        if (!section) return;
        
        const app = section.apps[appIdx];
        if (!app) return;

        this.showAppDetailPanel(app);
      });
    });
  }

  private showAppDetailPanel(app: AppItem): void {
    if (!this.mainContent) return;

    // Save scroll position before opening panel
    this.lastScrollPosition = this.mainContent.scrollTop;

    // Create panel
    const panel = document.createElement('div');
    panel.className = 'app-detail-panel slide-in';
    
    // Set panel position and size to match visible area
    panel.style.cssText = `
      position: absolute;
      top: ${this.mainContent.scrollTop}px;
      left: 0;
      width: 100%;
      height: 100%;
      background: var(--window-bg);
      z-index: 1000;
    `;

    const priceLower = app.price.toLowerCase();
    const isFree = priceLower === 'get app';
    const isInstalled = priceLower === 'installed';
    const priceClass = isInstalled
      ? 'app-price-button--installed'
      : isFree
        ? 'app-price-button--free'
        : 'app-price-button--paid';

    panel.innerHTML = this.createAppDetailContent(app, priceClass);

    // Append panel to main content
    this.mainContent.appendChild(panel);
    this.currentDetailPanel = panel;

    // Setup panel event handlers
    this.setupAppDetailHandlers(panel, app);

    // Mark main content as having detail panel open
    this.mainContent.classList.add('app-detail-open');
    this.mainContent.style.overflow = 'hidden';

    eventSystem.emit('app-store:app-detail-opened', { app });
  }

  private createAppDetailContent(app: AppItem, priceClass: string): string {
    return `
      <div class="window-toolbar">
        <button class="back-btn" title="Back"><i class="fas fa-arrow-left"></i></button>
        <span class="toolbar-title">${app.name}</span>
      </div>
      <div class="app-detail-content">
        <div class="app-detail-content-container">
          <div class="app-detail-content-header">
            <div class="app-detail-content-header-icon-title">
              <div class="app-detail-content-header-icon">
                <div class="icon-container ${app.iconBgClass}">
                  <i class="fas ${app.iconClass}"></i>
                </div>
              </div>
              <div class="app-detail-content-header-text">
                <h2>${app.name}</h2>
                <p class="appstore-app-author">By ${app.author}</p>
              </div>
            </div>
            <div class="app-detail-price">
              <button class="app-price-button ${priceClass}">${app.price}</button>
              <p class="app-price-description">${app.priceDescription}</p>
            </div>
          </div>

          <div class="app-detail-description">
            More information about <b>${app.name}</b> will go here. (Add your own details!)
            Lorem Ipsum is simply dummy text of the printing and typesetting industry. <br><br> 
            Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. <br><br>
            It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.
          </div>
          
          <div class="app-detail-description-images">
            <img src="img/appsimg/windows-list.jpg" alt="${app.name}">
            <img src="img/appsimg/touch.jpg" alt="${app.name}">
            <img src="img/appsimg/windows-icons.jpg" alt="${app.name}">
            <img src="img/appsimg/windows-11.jpg" alt="${app.name}">
            <img src="img/appsimg/windows-classic.jpg" alt="${app.name}">
          </div>
          
          <div class="app-detail-moreinfo">
            <h3>More information about <b>${app.name}</b> will go here. (Add your own details!)</h3>
            <p>Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.</p>
            
            <div class="app-detail-moreinfo-list">
              <h4>Features</h4>
              <div class="app-detail-moreinfo-list-items">
                <div class="app-detail-moreinfo-list-item">
                  <i class="fas fa-wallet"></i>
                  <div class="app-detail-moreinfo-list-item-text">
                    <h5>Feature 1</h5>
                    <p>Lorem Ipsum has been the industry's standard dummy</p>
                  </div>
                </div>
                <div class="app-detail-moreinfo-list-item">
                  <i class="fas fa-earth-asia"></i>
                  <div class="app-detail-moreinfo-list-item-text">
                    <h5>Feature 2</h5>
                    <p>Lorem Ipsum has been the industry's standard dummy</p>
                  </div>
                </div>
                <div class="app-detail-moreinfo-list-item">
                  <i class="fas fa-shield-alt"></i>
                  <div class="app-detail-moreinfo-list-item-text">
                    <h5>Feature 3</h5>
                    <p>Lorem Ipsum has been the industry's standard dummy</p>
                  </div>
                </div>
                <div class="app-detail-moreinfo-list-item">
                  <i class="fas fa-cog"></i>
                  <div class="app-detail-moreinfo-list-item-text">
                    <h5>Feature 4</h5>
                    <p>Lorem Ipsum has been the industry's standard dummy</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  private setupAppDetailHandlers(panel: HTMLElement, app: AppItem): void {
    // Back button handler
    const backBtn = panel.querySelector('.back-btn');
    if (backBtn) {
      backBtn.addEventListener('click', () => {
        this.removeAppDetailPanel();
      });
    }

    // Price button handler
    const priceBtn = panel.querySelector('.app-price-button');
    if (priceBtn) {
      priceBtn.addEventListener('click', () => {
        this.handleAppInstall(app);
      });
    }
  }

  private removeAppDetailPanel(): void {
    if (!this.currentDetailPanel || !this.mainContent) return;

    this.currentDetailPanel.classList.remove('slide-in');
    this.currentDetailPanel.classList.add('slide-out');
    
    this.currentDetailPanel.addEventListener('animationend', () => {
      if (this.currentDetailPanel && this.currentDetailPanel.parentNode) {
        this.currentDetailPanel.parentNode.removeChild(this.currentDetailPanel);
      }
      this.currentDetailPanel = undefined;
    }, { once: true });

    this.mainContent.classList.remove('app-detail-open');
    
    // Restore scroll position
    this.mainContent.scrollTop = this.lastScrollPosition;
    this.mainContent.style.overflow = '';

    eventSystem.emit('app-store:app-detail-closed', null);
  }

  private async handleAppInstall(app: AppItem): Promise<void> {
    const priceLower = app.price.toLowerCase();
    
    if (priceLower === 'installed') {
      // App is already installed, open it
      eventSystem.emit('app-store:open-app', { appName: app.name });
      return;
    }

    if (priceLower === 'get app') {
      // Free app, install directly
      await this.installApp(app);
    } else {
      // Paid app, show payment dialog
      await this.showPaymentDialog(app);
    }
  }

  private async installApp(app: AppItem): Promise<void> {
    try {
      // Show installing state
      const priceBtn = this.currentDetailPanel?.querySelector('.app-price-button');
      if (priceBtn) {
        priceBtn.textContent = 'Installing...';
        priceBtn.setAttribute('disabled', 'true');
      }

      // Simulate installation via API
      await apiService.post('/api/app-store/install', { 
        appName: app.name,
        appId: this.generateAppId(app.name)
      });

      // Update button to installed state
      if (priceBtn) {
        priceBtn.textContent = 'Installed';
        priceBtn.className = 'app-price-button app-price-button--installed';
        priceBtn.removeAttribute('disabled');
      }

      // Show success notification
      eventSystem.emit('notification:show', {
        type: 'success',
        message: `${app.name} installed successfully!`,
        duration: 3000
      });

      eventSystem.emit('app-store:app-installed', { app });

    } catch (error) {
      console.error('Failed to install app:', error);
      
      // Reset button state
      const priceBtn = this.currentDetailPanel?.querySelector('.app-price-button');
      if (priceBtn) {
        priceBtn.textContent = app.price;
        priceBtn.removeAttribute('disabled');
      }

      eventSystem.emit('notification:show', {
        type: 'error',
        message: `Failed to install ${app.name}`,
        duration: 5000
      });
    }
  }

  private async showPaymentDialog(app: AppItem): Promise<void> {
    // Implementation for payment dialog
    console.log('Show payment dialog for:', app.name, app.price);
    
    eventSystem.emit('app-store:payment-dialog', { 
      app, 
      onSuccess: () => this.installApp(app) 
    });
  }

  private generateAppId(appName: string): string {
    return appName.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
  }

  private async refreshAppStore(): Promise<void> {
    try {
      // In real implementation, fetch latest data from API
      // const featured = await apiService.get<FeaturedApp[]>('/api/app-store/featured');
      // const sections = await apiService.get<AppSection[]>('/api/app-store/sections');
      
      // this.featuredApps = featured;
      // this.appSections = sections;
      
      this.renderFeaturedApps();
      this.renderAppSections();
      this.setupAppCardClickHandlers();
      
    } catch (error) {
      console.error('Failed to refresh app store:', error);
    }
  }

  // Private properties
  private windowElement?: HTMLElement;
  private featuredContainer?: HTMLElement;
  private sectionsContainer?: HTMLElement;
  private mainContent?: HTMLElement;
}

export default AppStoreApp; 