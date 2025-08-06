// Settings Application - Extracted from original app.js
import { BaseApp, type AppContext } from './BaseApp';
import { eventSystem } from '../Core/EventSystem';
import type { Window, App } from '../Core/Types';

interface SettingsSection {
  id: string;
  name: string;
  content: string;
}

interface ThemeSettings {
  mode: 'light' | 'dark' | 'auto';
  accentColor: string;
  borderRadius: number;
  animationSpeed: 'slow' | 'normal' | 'fast';
}

export class SettingsApp extends BaseApp {
  private settings: Record<string, any> = {};
  private currentSection: string = 'general';
  private themeSettings: ThemeSettings = {
    mode: 'dark',
    accentColor: '#3576f6',
    borderRadius: 8,
    animationSpeed: 'normal',
  };

  constructor() {
    const appInfo: App = {
      id: 'settings',
      name: 'Settings',
      icon: 'fas fa-cog',
      iconType: 'fontawesome',
      iconBackground: 'gray-icon',
      component: 'SettingsApp',
      category: 'system',
      permissions: ['read', 'write'],
      installed: true,
      system: true,
      teamScoped: false,
      version: '1.0.0',
      description: 'System settings and preferences',
    };

    super('settings', appInfo);
  }

  public readonly id = 'settings';
  public readonly name = 'Settings';
  public readonly iconClass = 'fa-cog';
  public readonly iconBgClass = 'blue-icon';

  private sections: SettingsSection[] = [
    { id: 'appearance', name: 'Appearance', content: 'appearance-content' },
    { id: 'privacy', name: 'Privacy', content: 'privacy-content' },
    { id: 'accounts', name: 'Accounts', content: 'accounts-content' },
    { id: 'notifications', name: 'Notifications', content: 'notifications-content' },
    { id: 'system', name: 'System', content: 'system-content' }
  ];

  private accentColorMap = {
    multicolor: '#ff1b6b', // fallback to default accent
    blue: '#1a73ce',
    purple: '#310671',
    pink: '#ac1c72',
    red: '#d71c07',
    orange: '#eb8a17',
    yellow: '#f89e00',
    green: '#176848',
    gray: '#435564',
  };

  private autoThemeListener: ((e: MediaQueryListEvent) => void) | null = null;

  async onMount(context: AppContext): Promise<void> {
    this.windowElement = context.windowElement;
    this.setupSidebarNavigation(context.windowElement);
    this.setupAppearanceSettings(context.windowElement);
    this.setupCloseButton(context.windowElement);
    this.setActiveSection('appearance');
    
    // Restore settings on mount (like app.js does)
    this.restoreSettings();
  }

  async onUnmount(): Promise<void> {
    // Clean up theme listener
    if (this.autoThemeListener) {
      window.matchMedia('(prefers-color-scheme: dark)').removeEventListener('change', this.autoThemeListener);
      this.autoThemeListener = null;
    }
  }

  async onFocus(): Promise<void> {
    // Restore settings from localStorage
    this.restoreSettings();
  }

  async onBlur(): Promise<void> {
    // Auto-save settings
    this.saveSettings();
  }

  private setupSidebarNavigation(windowElement: HTMLElement): void {
    const sidebarNavLinks = windowElement.querySelectorAll('.settings-nav li a');
    const contentHeaderTitle = windowElement.querySelector('.settings-content-header h2') as HTMLElement;
    const settingsHeaderTitleElem = windowElement.querySelector('.settings-header-title') as HTMLElement;
    const allContentSections = windowElement.querySelectorAll('.settings-content > .settings-section-content');

    sidebarNavLinks.forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const sectionName = (link as HTMLElement).getAttribute('data-section');
        if (sectionName) {
          this.setActiveSection(sectionName);
        }
      });
    });

    // Store references for later use
    this.windowElement = windowElement;
    this.sidebarNavLinks = sidebarNavLinks;
    this.contentHeaderTitle = contentHeaderTitle;
    this.settingsHeaderTitleElem = settingsHeaderTitleElem;
    this.allContentSections = allContentSections;
  }

  private setActiveSection(sectionName: string): void {
    if (!this.sidebarNavLinks || !this.allContentSections) return;

    this.sidebarNavLinks.forEach(link => {
      link.classList.remove('active');
      if ((link as HTMLElement).getAttribute('data-section') === sectionName) {
        link.classList.add('active');
        const linkText = link.textContent?.replace(/\d+$/, '').trim() || '';
        if (this.contentHeaderTitle) this.contentHeaderTitle.textContent = linkText;
        if (this.settingsHeaderTitleElem) this.settingsHeaderTitleElem.textContent = linkText;
      }
    });

    this.allContentSections.forEach(section => {
      const sectionElement = section as HTMLElement;
      sectionElement.style.display = sectionElement.classList.contains(`${sectionName}-content`) ? 'block' : 'none';
    });

    // Emit section change event
    eventSystem.emit('settings:section-changed', { section: sectionName });
  }

  private setupAppearanceSettings(windowElement: HTMLElement): void {
    const appearanceContent = windowElement.querySelector('.appearance-content');
    if (!appearanceContent) return;

    this.setupThemeOptions(appearanceContent);
    this.setupAccentColors(appearanceContent);
    this.setupPopoutBehavior(appearanceContent);
  }

  private setupThemeOptions(appearanceContent: Element): void {
    const themeOptions = appearanceContent.querySelectorAll('.appearance-option');
    const appearanceOptionsContainer = appearanceContent.querySelector('.appearance-options');

    if (appearanceOptionsContainer) {
      appearanceOptionsContainer.addEventListener('click', (e) => {
        const optionEl = (e.target as HTMLElement).closest('.appearance-option');
        if (!optionEl) return;

        themeOptions.forEach(opt => opt.classList.remove('active'));
        optionEl.classList.add('active');

        const theme = optionEl.getAttribute('data-theme') as 'light' | 'dark' | 'auto';
        this.applyTheme(theme);
      });
    }
  }

  private applyTheme(theme: 'light' | 'dark' | 'auto'): void {
    if (theme === 'light') {
      document.body.classList.add('light-windows');
      localStorage.setItem('themeMode', 'light');
      this.removeAutoThemeListener();
    } else if (theme === 'dark') {
      document.body.classList.remove('light-windows');
      localStorage.setItem('themeMode', 'dark');
      this.removeAutoThemeListener();
    } else if (theme === 'auto') {
      localStorage.setItem('themeMode', 'auto');
      this.setupAutoTheme();
    }

    eventSystem.emit('settings:theme-changed', { theme });
  }

  private setupAutoTheme(): void {
    const applySystemTheme = (e?: MediaQueryListEvent) => {
      const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      if (isDark) {
        document.body.classList.remove('light-windows');
      } else {
        document.body.classList.add('light-windows');
      }
    };

    // Initial apply
    applySystemTheme();

    // Remove previous listener if any
    this.removeAutoThemeListener();

    // Save and add new listener
    this.autoThemeListener = applySystemTheme;
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', applySystemTheme);
  }

  private removeAutoThemeListener(): void {
    if (this.autoThemeListener) {
      window.matchMedia('(prefers-color-scheme: dark)').removeEventListener('change', this.autoThemeListener);
      this.autoThemeListener = null;
    }
  }

  private setupAccentColors(appearanceContent: Element): void {
    const accentSwatches = appearanceContent.querySelectorAll('.accent-color-options .color-swatch');

    accentSwatches.forEach(swatch => {
      swatch.addEventListener('click', () => {
        let colorKey = 'multicolor';
        for (const key in this.accentColorMap) {
          if (swatch.classList.contains(key)) {
            colorKey = key;
            break;
          }
        }
        this.setAccentColor(colorKey);
      });
    });
  }

  private setAccentColor(colorKey: string): void {
    const color = this.accentColorMap[colorKey as keyof typeof this.accentColorMap] || this.accentColorMap.multicolor;
    
    // Set the main accent color
    document.documentElement.style.setProperty('--accent-color', color);
    
    // Calculate and set the light variant (40% lighter)
    const lightColor = this.lightenColor(color, 40);
    document.documentElement.style.setProperty('--accent-color-light', lightColor);

    localStorage.setItem('accentColor', colorKey);

    // Update active border for color swatches
    if (this.windowElement) {
      const accentSwatches = this.windowElement.querySelectorAll('.accent-color-options .color-swatch');
      accentSwatches.forEach(s => s.classList.remove('active'));
      const selectedSwatch = Array.from(accentSwatches).find(s => s.classList.contains(colorKey));
      if (selectedSwatch) selectedSwatch.classList.add('active');
    }

    eventSystem.emit('settings:accent-color-changed', { colorKey, color });
  }

  private lightenColor(color: string, percent: number): string {
    const num = parseInt(color.replace('#', ''), 16);
    const amt = Math.round(2.55 * percent);
    const R = (num >> 16) + amt;
    const G = (num >> 8 & 0x00FF) + amt;
    const B = (num & 0x0000FF) + amt;
    
    return '#' + (0x1000000 + (R < 255 ? R < 1 ? 0 : R : 255) * 0x10000 +
      (G < 255 ? G < 1 ? 0 : G : 255) * 0x100 +
      (B < 255 ? B < 1 ? 0 : B : 255)).toString(16).slice(1);
  }

  private setupPopoutBehavior(appearanceContent: Element): void {
    const popoutCloseDropdown = appearanceContent.querySelector('#popout-close-behavior') as HTMLSelectElement;
    if (popoutCloseDropdown) {
      // Set initial value from localStorage or default
      const savedBehavior = localStorage.getItem('popoutCloseBehavior') || 'close';
      popoutCloseDropdown.value = savedBehavior;
      
      popoutCloseDropdown.addEventListener('change', () => {
        localStorage.setItem('popoutCloseBehavior', popoutCloseDropdown.value);
        eventSystem.emit('settings:popout-behavior-changed', { behavior: popoutCloseDropdown.value });
      });
    }
  }

  private setupCloseButton(windowElement: HTMLElement): void {
    const closeButtonRed = windowElement.querySelector('.window-control-btn.red');
    if (closeButtonRed) {
      closeButtonRed.addEventListener('click', () => {
        this.requestClose();
      });
    }
  }

  private restoreSettings(): void {
    // Restore theme and UI state (like app.js)
    const savedTheme = localStorage.getItem('themeMode') || 'auto';
    
    if (this.windowElement) {
      const themeOptions = this.windowElement.querySelectorAll('.appearance-option');
      const initialOption = Array.from(themeOptions).find(opt => opt.getAttribute('data-theme') === savedTheme);
      if (initialOption) {
        themeOptions.forEach(opt => opt.classList.remove('active'));
        initialOption.classList.add('active');
      }
    }
    
    this.applyTheme(savedTheme as 'light' | 'dark' | 'auto');

    // Restore accent color
    const savedAccent = localStorage.getItem('accentColor') || 'multicolor';
    this.setAccentColor(savedAccent);
  }

  private saveSettings(): void {
    // Settings are auto-saved on change, but we can add any final cleanup here
    console.log('Settings auto-saved');
  }

  // Private properties to store element references
  private windowElement?: HTMLElement;
  private sidebarNavLinks?: NodeListOf<Element>;
  private contentHeaderTitle?: HTMLElement;
  private settingsHeaderTitleElem?: HTMLElement;
  private allContentSections?: NodeListOf<Element>;
}

export default SettingsApp; 