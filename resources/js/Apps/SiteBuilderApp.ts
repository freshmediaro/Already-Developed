// Site Builder Application - Extracted from original app.js
import { BaseApp, type AppContext } from './BaseApp';
import { eventSystem } from '../Core/EventSystem';
import { apiService } from '../Tenant/ApiService';
import type { Window, App } from '../Core/Types';

interface PageItem {
  id: string;
  title: string;
  slug: string;
  status: 'published' | 'draft' | 'archived';
  lastModified: Date;
  template?: string;
}

interface PageSettings {
  title: string;
  slug: string;
  metaTitle: string;
  metaDescription: string;
  socialImage?: string;
  status: 'published' | 'draft' | 'archived';
  noIndex: boolean;
  noFollow: boolean;
  excludeFromSitemap: boolean;
  scheduledDate?: Date;
}

export class SiteBuilderApp extends BaseApp {
  public readonly id = 'site-builder';
  public readonly name = 'Site Builder';
  public readonly iconClass = 'fa-globe';
  public readonly iconBgClass = 'pink-icon';

  private pages: PageItem[] = [];
  private currentPageId: string | null = null;
  private isSettingsPanelOpen: boolean = false;

  constructor() {
    const appInfo: App = {
      id: 'site-builder',
      name: 'Site Builder',
      icon: 'fas fa-globe',
      iconType: 'fontawesome',
      iconBackground: 'pink-icon',
      component: 'SiteBuilderApp',
      category: 'business',
      permissions: ['read', 'write'],
      installed: true,
      system: false,
      teamScoped: true,
      version: '1.0.0',
      description: 'Visual website builder and content management',
    };
    super('site-builder', appInfo);
  }

  async onMount(context: AppContext): Promise<void> {
    this.setupPageActionsRows(context.windowElement);
    this.setupPageClickHandlers(context.windowElement);
    this.setupSettingsHandlers(context.windowElement);
    this.setupToggleHandlers(context.windowElement);
    await this.loadPages();
  }

  async onUnmount(): Promise<void> {
    // Cleanup any open settings panels
    this.closeAllSettingsPanels();
  }

  async onFocus(): Promise<void> {
    // Refresh pages if needed
    await this.loadPages();
  }

  async onBlur(): Promise<void> {
    // Auto-save any changes and close panels
    this.autoSaveChanges();
    this.closeAllSettingsPanels();
  }

  private setupPageActionsRows(windowElement: HTMLElement): void {
    // Hide page-actions-row by default
    const pageActionsRows = windowElement.querySelectorAll('.page-actions-row');
    pageActionsRows.forEach(row => {
      const rowElement = row as HTMLElement;
      rowElement.style.display = 'none';
      rowElement.style.height = '0';
      rowElement.style.overflow = 'hidden';
      rowElement.style.transition = 'height 0.3s ease-in-out';
    });
  }

  private setupPageClickHandlers(windowElement: HTMLElement): void {
    // Setup click handlers for page items
    const pageItems = windowElement.querySelectorAll('.sitebuilder-iteam-content');
    
    pageItems.forEach(item => {
      const toggleButton = item.querySelector('.page-toggle-btn, .chevron-toggle-btn');
      if (toggleButton) {
        toggleButton.addEventListener('click', (e) => {
          e.stopPropagation();
          this.togglePageActionsRow(toggleButton as HTMLElement);
        });
      }

      // Settings button handler
      const settingsButton = item.querySelector('.action-btn.settings-btn');
      if (settingsButton) {
        settingsButton.addEventListener('click', (e) => {
          e.stopPropagation();
          this.togglePageSettings(settingsButton as HTMLElement);
        });
      }

      // Other action buttons
      const actionButtons = item.querySelectorAll('.action-btn:not(.settings-btn)');
      actionButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.stopPropagation();
          const action = (btn as HTMLElement).getAttribute('data-action') || 
                         (btn as HTMLElement).className.match(/(\w+)-btn/)?.[1] || 'unknown';
          this.handlePageAction(action, item as HTMLElement);
        });
      });
    });
  }

  private setupSettingsHandlers(windowElement: HTMLElement): void {
    // Setup handlers for settings form elements
    const settingsInputs = windowElement.querySelectorAll('.page-settings-section input, .page-settings-section select, .page-settings-section textarea');
    
    settingsInputs.forEach(input => {
      input.addEventListener('change', () => {
        this.handleSettingsChange(input as HTMLFormElement);
      });
    });

    // Setup toggle switches
    const toggleSwitches = windowElement.querySelectorAll('.toggle-switch input[type="checkbox"]');
    toggleSwitches.forEach(toggle => {
      toggle.addEventListener('change', () => {
        this.handleToggleChange(toggle as HTMLInputElement);
      });
    });

    // Setup date picker
    const datePickers = windowElement.querySelectorAll('.date-input');
    datePickers.forEach(picker => {
      picker.addEventListener('change', () => {
        this.handleDateChange(picker as HTMLInputElement);
      });
    });

    // Setup file upload
    const fileUploads = windowElement.querySelectorAll('.settings-image-upload');
    fileUploads.forEach(upload => {
      upload.addEventListener('click', () => {
        this.handleImageUpload(upload as HTMLElement);
      });
    });
  }

  private setupToggleHandlers(windowElement: HTMLElement): void {
    // Setup section toggle handlers
    const sectionToggles = windowElement.querySelectorAll('.section-toggle');
    sectionToggles.forEach(toggle => {
      toggle.addEventListener('change', () => {
        this.handleSectionToggle(toggle as HTMLInputElement);
      });
    });
  }

  private togglePageActionsRow(button: HTMLElement): void {
    const parentItem = button.closest('.sitebuilder-iteam-content');
    if (!parentItem) return;

    const actionsRow = parentItem.querySelector('.page-actions-row') as HTMLElement;
    const settingsSection = parentItem.querySelector('.page-settings-section') as HTMLElement;
    const chevronIcon = button.querySelector('i.fa-chevron-down, i.fa-chevron-up');
    
    // If settings section is open, close it first
    if (settingsSection && settingsSection.style.display === 'block') {
      this.closePageSettings(parentItem as HTMLElement);
    }
    
    // Toggle actions row
    if (actionsRow) {
      if (actionsRow.style.display === 'none' || actionsRow.style.height === '0px') {
        // Show actions row
        actionsRow.style.display = 'flex';
        setTimeout(() => {
          actionsRow.style.height = actionsRow.scrollHeight + 'px';
        }, 10);
        
        // Change chevron icon to up
        if (chevronIcon) {
          chevronIcon.classList.remove('fa-chevron-down');
          chevronIcon.classList.add('fa-chevron-up');
        }
      } else {
        // Hide actions row
        actionsRow.style.height = '0';
        setTimeout(() => {
          actionsRow.style.display = 'none';
        }, 300);
        
        // Change chevron icon to down
        if (chevronIcon) {
          chevronIcon.classList.remove('fa-chevron-up');
          chevronIcon.classList.add('fa-chevron-down');
        }
      }
    }

    eventSystem.emit('site-builder:page-actions-toggled', { 
      pageId: this.getPageIdFromElement(parentItem as HTMLElement),
      isOpen: actionsRow?.style.display !== 'none'
    });
  }

  private togglePageSettings(button: HTMLElement): void {
    const parentItem = button.closest('.sitebuilder-iteam-content');
    if (!parentItem) return;

    const settingsSection = parentItem.querySelector('.page-settings-section') as HTMLElement;
    const settingsIcon = button.querySelector('i');
    const pageId = this.getPageIdFromElement(parentItem as HTMLElement);
    
    if (settingsSection) {
      if (settingsSection.style.display === 'none' || !settingsSection.style.display) {
        // Open settings section
        this.openPageSettings(parentItem as HTMLElement);
        
        // Change icon from gear to X mark
        if (settingsIcon && settingsIcon.classList.contains('fa-gear')) {
          settingsIcon.classList.remove('fa-gear');
          settingsIcon.classList.add('fa-xmark');
        }
        
        this.currentPageId = pageId;
        this.isSettingsPanelOpen = true;
      } else {
        // Close settings section
        this.closePageSettings(parentItem as HTMLElement);
        
        // Change icon from X mark back to gear
        if (settingsIcon && settingsIcon.classList.contains('fa-xmark')) {
          settingsIcon.classList.remove('fa-xmark');
          settingsIcon.classList.add('fa-gear');
        }
        
        this.currentPageId = null;
        this.isSettingsPanelOpen = false;
      }
    }

    eventSystem.emit('site-builder:page-settings-toggled', { 
      pageId,
      isOpen: this.isSettingsPanelOpen
    });
  }

  private openPageSettings(parentItem: HTMLElement): void {
    const settingsSection = parentItem.querySelector('.page-settings-section') as HTMLElement;
    if (!settingsSection) return;

    // Create settings content if it doesn't exist
    if (!settingsSection.innerHTML.trim()) {
      settingsSection.innerHTML = this.createSettingsSectionTemplate();
    }

    settingsSection.style.display = 'block';
    setTimeout(() => {
      settingsSection.style.height = settingsSection.scrollHeight + 'px';
    }, 10);

    // Load current page settings
    const pageId = this.getPageIdFromElement(parentItem);
    this.loadPageSettings(pageId, settingsSection);
  }

  private closePageSettings(parentItem: HTMLElement): void {
    const settingsSection = parentItem.querySelector('.page-settings-section') as HTMLElement;
    if (!settingsSection) return;

    settingsSection.style.height = '0';
    setTimeout(() => {
      settingsSection.style.display = 'none';
    }, 300);
  }

  private createSettingsSectionTemplate(): string {
    return `
      <div class="page-settings-row">
        <div class="page-settings-row-left">
          <div class="settings-group">
            <h3>General</h3>
            <div class="settings-item">
              <label>Page Title</label>
              <input type="text" class="settings-input" name="title" value="Portfolio">
            </div>
            <div class="settings-item">
              <label>URL Slug</label>
              <div class="settings-url-input">
                <span class="settings-url-prefix">mywebsite.com/</span>
                <input type="text" class="settings-input url-slug" name="slug" value="portfolio">
              </div>
            </div>
          </div>
          
          <div class="settings-group">
            <div class="settings-group-header">
              <h3>Search Engine Optimization</h3>
              <div class="toggle-switch">
                <input type="checkbox" class="section-toggle" name="seo-enabled">
                <span class="toggle-slider"></span>
              </div>
            </div>
            
            <div class="publishing-options-section">
              <div class="settings-item">
                <label>Meta Title</label>
                <input type="text" class="settings-input" name="meta-title" value="Portfolio - My Website">
              </div>
              <div class="settings-item">
                <label>Meta Description</label>
                <textarea class="settings-textarea" name="meta-description">Check out my portfolio of recent projects and work.</textarea>
              </div>
              
              <div class="publish-section">  
                <div class="form-row-top-input">
                  <label class="publish-label">SEO Exclusion</label>
                </div>
                <div class="checkbox-option">
                  <input type="checkbox" id="no-index-page" name="no-index">
                  <label for="no-index-page">No index (exclude from search engines)</label>
                </div>
                <div class="checkbox-option" style="margin-top: 10px;">
                  <input type="checkbox" id="no-follow-page" name="no-follow">
                  <label for="no-follow-page">No follow (do not follow links)</label>
                </div>
                <div class="checkbox-option" style="margin-top: 10px;">
                  <input type="checkbox" id="exclude-page-from-sitemap" name="exclude-sitemap">
                  <label for="exclude-page-from-sitemap">Exclude page from sitemap</label>
                </div>
              </div>
            </div>
          </div>
          
          <div class="settings-group">
            <h3>Social Sharing</h3>
            <div class="settings-item">
              <label>Social Image</label>
              <div class="settings-image-upload">
                <i class="fas fa-upload"></i>
                <span>Upload Image</span>
              </div>
              <div class="upload-image-description">
                <span>When a recommended image or an OpenGraph Image is not set for individual posts/pages/CPTs, this image will be used as a fallback thumbnail when your post is shared on Facebook. The recommended image size is 1200 x 630 pixels.</span>
              </div>
            </div>
          </div>
        </div>

        <div class="page-settings-row-right">
          <div class="settings-group">
            <h3>Publishing Options</h3>
            
            <div class="publishing-options-section">
              <div class="publish-section">
                <div class="form-row-top-input">
                  <label class="publish-label">Status</label>
                  <div class="schedule-publish-btn">Schedule</div>
                </div>
                <div class="select-container">
                  <select class="form-control" name="status">
                    <option value="published" selected style="color: #333 !important;">Published</option>
                    <option value="draft" style="color: #333 !important;">Draft</option>
                    <option value="archived" style="color: #333 !important;">Staff Review</option>
                  </select>
                </div>
              </div>
              
              <div class="publish-section">
                <label class="publish-label">Schedule publishing</label>
                <div class="date-picker-container">
                  <input type="text" class="form-control date-input" name="scheduled-date" value="05-01-2025">
                  <button class="calendar-button">
                    <i class="fas fa-calendar-alt"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  private handlePageAction(action: string, pageItem: HTMLElement): void {
    const pageId = this.getPageIdFromElement(pageItem);
    
    console.log('Site Builder action:', action, 'for page:', pageId);

    switch (action) {
      case 'edit':
        this.editPage(pageId);
        break;
      case 'duplicate':
        this.duplicatePage(pageId);
        break;
      case 'delete':
        this.deletePage(pageId);
        break;
      case 'preview':
        this.previewPage(pageId);
        break;
      case 'publish':
        this.publishPage(pageId);
        break;
      default:
        eventSystem.emit('site-builder:page-action', { action, pageId });
    }
  }

  private handleSettingsChange(input: HTMLFormElement): void {
    const name = input.name;
    const value = input.value;
    
    if (this.currentPageId) {
      eventSystem.emit('site-builder:setting-changed', {
        pageId: this.currentPageId,
        setting: name,
        value
      });

      // Auto-save after a delay
      this.debounceAutoSave();
    }
  }

  private handleToggleChange(toggle: HTMLInputElement): void {
    const name = toggle.name;
    const checked = toggle.checked;
    
    if (this.currentPageId) {
      eventSystem.emit('site-builder:toggle-changed', {
        pageId: this.currentPageId,
        setting: name,
        enabled: checked
      });

      this.debounceAutoSave();
    }
  }

  private handleSectionToggle(toggle: HTMLInputElement): void {
    const section = toggle.closest('.settings-group')?.querySelector('.publishing-options-section') as HTMLElement;
    if (section) {
      section.style.display = toggle.checked ? 'block' : 'none';
    }
  }

  private handleDateChange(picker: HTMLInputElement): void {
    const value = picker.value;
    
    if (this.currentPageId) {
      eventSystem.emit('site-builder:date-changed', {
        pageId: this.currentPageId,
        setting: picker.name,
        date: value
      });

      this.debounceAutoSave();
    }
  }

  private handleImageUpload(uploadElement: HTMLElement): void {
    // Create file input
    const fileInput = document.createElement('input');
    fileInput.type = 'file';
    fileInput.accept = 'image/*';
    
    fileInput.onchange = async (e) => {
      const file = (e.target as HTMLInputElement).files?.[0];
      if (file && this.currentPageId) {
        try {
          // Upload image via API
          const formData = new FormData();
          formData.append('image', file);
          formData.append('pageId', this.currentPageId);
          
          const response = await apiService.post('/api/site-builder/upload-image', formData);
          
          eventSystem.emit('site-builder:image-uploaded', {
            pageId: this.currentPageId,
            imageUrl: response.data?.url || response.data
          });

          // Update upload element to show success
          uploadElement.innerHTML = '<i class="fas fa-check"></i><span>Image Uploaded</span>';
          
        } catch (error) {
          console.error('Failed to upload image:', error);
          eventSystem.emit('notification:show', {
            type: 'error',
            message: 'Failed to upload image',
            duration: 5000
          });
        }
      }
    };
    
    fileInput.click();
  }

  private getPageIdFromElement(element: HTMLElement): string {
    return element.getAttribute('data-page-id') || 
           element.querySelector('[data-page-id]')?.getAttribute('data-page-id') || 
           'unknown';
  }

  private async loadPages(): Promise<void> {
    try {
      // In real implementation, fetch from API
      // this.pages = await apiService.get<PageItem[]>('/api/site-builder/pages');
      
      // Sample data for now
      this.pages = [
        {
          id: 'home',
          title: 'Home',
          slug: 'home',
          status: 'published',
          lastModified: new Date(),
          template: 'home-template'
        },
        {
          id: 'about',
          title: 'About',
          slug: 'about',
          status: 'draft',
          lastModified: new Date(),
          template: 'page-template'
        }
      ];

      eventSystem.emit('site-builder:pages-loaded', { pages: this.pages });
    } catch (error) {
      console.error('Failed to load pages:', error);
    }
  }

  private async loadPageSettings(pageId: string, settingsElement: HTMLElement): Promise<void> {
    try {
      // In real implementation, fetch from API
      // const settings = await apiService.get<PageSettings>(`/api/site-builder/pages/${pageId}/settings`);
      
      // Sample settings for now
      const settings: PageSettings = {
        title: 'Portfolio',
        slug: 'portfolio',
        metaTitle: 'Portfolio - My Website',
        metaDescription: 'Check out my portfolio of recent projects and work.',
        status: 'published',
        noIndex: false,
        noFollow: false,
        excludeFromSitemap: false
      };

      // Populate form fields
      this.populateSettingsForm(settingsElement, settings);
      
    } catch (error) {
      console.error('Failed to load page settings:', error);
    }
  }

  private populateSettingsForm(settingsElement: HTMLElement, settings: PageSettings): void {
    const inputs = settingsElement.querySelectorAll('input, select, textarea');
    
    inputs.forEach(input => {
      const inputElement = input as HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;
      const name = inputElement.name;
      
      if (name in settings) {
        const value = settings[name as keyof PageSettings];
        
        if (inputElement.type === 'checkbox') {
          (inputElement as HTMLInputElement).checked = Boolean(value);
        } else {
          inputElement.value = String(value || '');
        }
      }
    });
  }

  // Page actions
  private editPage(pageId: string): void {
    eventSystem.emit('site-builder:edit-page', { pageId });
  }

  private duplicatePage(pageId: string): void {
    eventSystem.emit('site-builder:duplicate-page', { pageId });
  }

  private async deletePage(pageId: string): Promise<void> {
    const confirmed = confirm('Are you sure you want to delete this page?');
    if (!confirmed) return;

    try {
      await apiService.delete(`/api/site-builder/pages/${pageId}`);
      await this.loadPages(); // Refresh pages list
      eventSystem.emit('site-builder:page-deleted', { pageId });
    } catch (error) {
      console.error('Failed to delete page:', error);
    }
  }

  private previewPage(pageId: string): void {
    eventSystem.emit('site-builder:preview-page', { pageId });
  }

  private async publishPage(pageId: string): Promise<void> {
    try {
      await apiService.post(`/api/site-builder/pages/${pageId}/publish`);
      await this.loadPages(); // Refresh pages list
      eventSystem.emit('site-builder:page-published', { pageId });
    } catch (error) {
      console.error('Failed to publish page:', error);
    }
  }

  private debounceAutoSave = this.debounce(() => {
    this.autoSaveChanges();
  }, 1000);

  private autoSaveChanges(): void {
    if (this.currentPageId && this.isSettingsPanelOpen) {
      eventSystem.emit('site-builder:auto-save', { pageId: this.currentPageId });
    }
  }

  private closeAllSettingsPanels(): void {
    if (this.windowElement) {
      const openPanels = this.windowElement.querySelectorAll('.page-settings-section[style*="display: block"]');
      openPanels.forEach(panel => {
        const parentItem = panel.closest('.sitebuilder-iteam-content');
        if (parentItem) {
          this.closePageSettings(parentItem as HTMLElement);
        }
      });
    }
    
    this.isSettingsPanelOpen = false;
    this.currentPageId = null;
  }

  // Utility function for debouncing
  private debounce<T extends (...args: any[]) => any>(func: T, wait: number): (...args: Parameters<T>) => void {
    let timeout: NodeJS.Timeout;
    return (...args: Parameters<T>) => {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), wait);
    };
  }

  private windowElement?: HTMLElement;
}

export default SiteBuilderApp; 