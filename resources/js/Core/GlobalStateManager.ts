// Global State Management - Extracted from original app.js
import { eventSystem } from './EventSystem';
import { SystemNotifications } from '../Utils/AlertHelpers';

interface NotificationSettings {
  isMuted: boolean;
  stackingMode: 'one' | 'three' | 'all';
  badgeCount: number;
}

interface GlobalSettings {
  volume: {
    isMuted: boolean;
    previousVolume: number;
    currentVolume: number;
  };
  notifications: NotificationSettings;
  desktop: {
    theme: 'light' | 'dark' | 'auto';
    layout: 'windows11' | 'windows10' | 'classic';
    iconSize: 'small' | 'medium' | 'large';
    showDesktopIcons: boolean;
  };
  taskbar: {
    position: 'bottom' | 'top' | 'left' | 'right';
    autoHide: boolean;
    showLabels: boolean;
    size: 'small' | 'medium' | 'large';
    style: 'default' | 'windows11' | 'left' | 'text';
    showSearchBar: boolean;
    showAppLauncher: boolean;
    showGlobalSearch: boolean;
    showWallet: boolean;
    showFullscreen: boolean;
    showVolume: boolean;
  };
  orientation: {
    locked: boolean;
    preferred: 'portrait' | 'landscape' | 'auto';
  };
  interface: {
    mode: 'desktop' | 'app-launcher' | 'easy';
    previousMode: 'desktop' | 'app-launcher' | 'easy';
    showWidgets: boolean;
    isFullscreen: boolean;
  };
  wallet: {
    balance: number;
    currency: string;
    transactions: any[];
  };
}

class GlobalStateManager {
  private state: GlobalSettings = {
    volume: {
      isMuted: false,
      previousVolume: 75,
      currentVolume: 75
    },
    notifications: {
      isMuted: false,
      stackingMode: 'three',
      badgeCount: 0
    },
    desktop: {
      theme: 'dark',
      layout: 'windows11',
      iconSize: 'medium',
      showDesktopIcons: true
    },
    taskbar: {
      position: 'bottom',
      autoHide: false,
      showLabels: true,
      size: 'medium',
      style: 'default',
      showSearchBar: true,
      showAppLauncher: true,
      showGlobalSearch: true,
      showWallet: true,
      showFullscreen: true,
      showVolume: true
    },
    orientation: {
      locked: false,
      preferred: 'auto'
    },
    interface: {
      mode: 'desktop',
      previousMode: 'desktop',
      showWidgets: false,
      isFullscreen: false
    },
    wallet: {
      balance: 0,
      currency: 'USD',
      transactions: []
    }
  };

  private subscribers: Map<string, Set<(value: any) => void>> = new Map();

  constructor() {
    this.loadFromStorage();
    this.setupEventListeners();
  }

  // Volume state management
  setVolumeState(volume: number, isMuted: boolean): void {
    const oldState = { ...this.state.volume };
    
    if (!isMuted && this.state.volume.isMuted) {
      this.state.volume.previousVolume = this.state.volume.currentVolume;
    }
    
    this.state.volume.currentVolume = volume;
    this.state.volume.isMuted = isMuted;
    
    this.saveToStorage();
    this.notify('volume', this.state.volume);
    
    eventSystem.emit('global:volume:changed', {
      oldState,
      newState: this.state.volume
    });
  }

  getVolumeState() {
    return { ...this.state.volume };
  }

  // Notification state management
  setNotificationMuted(isMuted: boolean): void {
    const oldState = this.state.notifications.isMuted;
    this.state.notifications.isMuted = isMuted;
    
    this.saveToStorage();
    this.notify('notifications.isMuted', isMuted);
    
    eventSystem.emit('global:notifications:mute:changed', {
      oldState,
      newState: isMuted
    });
  }

  setNotificationStackingMode(mode: 'one' | 'three' | 'all'): void {
    const oldState = this.state.notifications.stackingMode;
    this.state.notifications.stackingMode = mode;
    
    this.saveToStorage();
    this.notify('notifications.stackingMode', mode);
    
    // Show notification feedback
    this.showNotificationModeNotification(mode);
    
    eventSystem.emit('global:notifications:stacking:changed', {
      oldState,
      newState: mode
    });
  }

  setNotificationBadgeCount(count: number): void {
    const oldState = this.state.notifications.badgeCount;
    this.state.notifications.badgeCount = Math.max(0, count);
    
    this.notify('notifications.badgeCount', this.state.notifications.badgeCount);
    
    eventSystem.emit('global:notifications:badge:changed', {
      oldState,
      newState: this.state.notifications.badgeCount
    });
    
    // Update notification button badge
    this.updateNotificationBadgeUI();
  }

  incrementNotificationBadge(): void {
    this.setNotificationBadgeCount(this.state.notifications.badgeCount + 1);
  }

  clearNotificationBadge(): void {
    this.setNotificationBadgeCount(0);
  }

  getNotificationSettings(): NotificationSettings {
    return { ...this.state.notifications };
  }

  // Desktop theme management
  setDesktopTheme(theme: 'light' | 'dark' | 'auto'): void {
    const oldState = this.state.desktop.theme;
    this.state.desktop.theme = theme;
    
    this.applyTheme(theme);
    this.saveToStorage();
    this.notify('desktop.theme', theme);
    
    eventSystem.emit('global:theme:changed', {
      oldState,
      newState: theme
    });
  }

  setDesktopLayout(layout: 'windows11' | 'windows10' | 'classic'): void {
    const oldState = this.state.desktop.layout;
    this.state.desktop.layout = layout;
    
    this.applyLayout(layout);
    this.saveToStorage();
    this.notify('desktop.layout', layout);
    
    eventSystem.emit('global:layout:changed', {
      oldState,
      newState: layout
    });
  }

  getDesktopSettings() {
    return { ...this.state.desktop };
  }

  // Taskbar management
  setTaskbarPosition(position: 'bottom' | 'top' | 'left' | 'right'): void {
    const oldState = this.state.taskbar.position;
    this.state.taskbar.position = position;
    
    this.applyTaskbarPosition(position);
    this.saveToStorage();
    this.notify('taskbar.position', position);
    
    eventSystem.emit('global:taskbar:position:changed', {
      oldState,
      newState: position
    });
  }

  setTaskbarAutoHide(autoHide: boolean): void {
    const oldState = this.state.taskbar.autoHide;
    this.state.taskbar.autoHide = autoHide;
    
    this.applyTaskbarAutoHide(autoHide);
    this.saveToStorage();
    this.notify('taskbar.autoHide', autoHide);
    
    eventSystem.emit('global:taskbar:autohide:changed', {
      oldState,
      newState: autoHide
    });
  }

  getTaskbarSettings() {
    return { ...this.state.taskbar };
  }

  // Taskbar customization methods
  setTaskbarStyle(style: 'default' | 'windows11' | 'left' | 'text'): void {
    const oldState = { ...this.state.taskbar };
    this.state.taskbar.style = style;
    
    this.applyTaskbarStyle(style);
    this.saveToStorage();
    this.notify('taskbar.style', style);
    
    // Show notification feedback
    this.showTaskbarStyleNotification(style);
    
    eventSystem.emit('global:taskbar:style:changed', {
      oldState,
      newState: this.state.taskbar
    });
  }

  toggleSearchBar(): void {
    const oldState = this.state.taskbar.showSearchBar;
    this.state.taskbar.showSearchBar = !this.state.taskbar.showSearchBar;
    
    this.saveToStorage();
    this.notify('taskbar.showSearchBar', this.state.taskbar.showSearchBar);
    
    eventSystem.emit('global:taskbar:searchbar:changed', {
      oldState,
      newState: this.state.taskbar.showSearchBar
    });
  }

  toggleSystemTrayIcon(iconType: 'appLauncher' | 'globalSearch' | 'wallet' | 'fullscreen' | 'volume'): void {
    const key = `show${iconType.charAt(0).toUpperCase() + iconType.slice(1)}` as keyof typeof this.state.taskbar;
    const oldState = this.state.taskbar[key];
    // @ts-ignore - We know this is a boolean property
    this.state.taskbar[key] = !this.state.taskbar[key];
    const newState = this.state.taskbar[key];
    
    this.saveToStorage();
    this.notify(`taskbar.${key}`, newState);
    
    // Show notification feedback
    this.showSystemTrayIconNotification(iconType, newState);
    
    eventSystem.emit('global:taskbar:icon:changed', {
      iconType,
      oldState,
      newState
    });
  }

  showDesktop(): void {
    // This will be handled by the window manager
    eventSystem.emit('global:desktop:show', {});
    SystemNotifications.allWindowsMinimized();
  }

  private showSystemTrayIconNotification(iconType: string, isVisible: boolean): void {
    switch (iconType) {
      case 'appLauncher':
        isVisible ? SystemNotifications.appLauncherShown() : SystemNotifications.appLauncherHidden();
        break;
      case 'globalSearch':
        isVisible ? SystemNotifications.searchIconShown() : SystemNotifications.searchIconHidden();
        break;
      case 'wallet':
        isVisible ? SystemNotifications.walletButtonShown() : SystemNotifications.walletButtonHidden();
        break;
      case 'fullscreen':
        isVisible ? SystemNotifications.fullscreenButtonShown() : SystemNotifications.fullscreenButtonHidden();
        break;
      case 'volume':
        isVisible ? SystemNotifications.volumeButtonShown() : SystemNotifications.volumeButtonHidden();
        break;
    }
  }

  private showTaskbarStyleNotification(style: string): void {
    switch (style) {
      case 'default':
        SystemNotifications.taskbarStyleDefault();
        break;
      case 'windows11':
        SystemNotifications.taskbarStyleWindows11();
        break;
      case 'left':
        SystemNotifications.taskbarIconsLeft();
        break;
      case 'text':
        SystemNotifications.taskbarIconsAndText();
        break;
    }
  }

  private showNotificationModeNotification(mode: string): void {
    switch (mode) {
      case 'one':
        SystemNotifications.notificationsOnly1();
        break;
      case 'three':
        SystemNotifications.notificationsOnly3();
        break;
      case 'all':
        SystemNotifications.notificationsAll();
        break;
    }
  }

  // Orientation management
  setOrientationLocked(locked: boolean, preferred?: 'portrait' | 'landscape'): void {
    const oldState = { ...this.state.orientation };
    this.state.orientation.locked = locked;
    
    if (preferred) {
      this.state.orientation.preferred = preferred;
    }
    
    this.saveToStorage();
    this.notify('orientation', this.state.orientation);
    
    eventSystem.emit('global:orientation:changed', {
      oldState,
      newState: this.state.orientation
    });
  }

  getOrientationSettings() {
    return { ...this.state.orientation };
  }

  // Interface mode management
  async setInterfaceMode(mode: 'desktop' | 'app-launcher' | 'easy'): Promise<void> {
    if (this.state.interface.mode === mode) return;
    
    const oldState = { ...this.state.interface };
    const previousMode = this.state.interface.mode;
    this.state.interface.previousMode = previousMode;
    this.state.interface.mode = mode;
    
    // Apply mode switch with animations
    await this.switchToModeWithAnimations(mode, previousMode);
    
    this.saveToStorage();
    this.notify('interface.mode', mode);
    
    eventSystem.emit('global:interface:mode:changed', {
      oldState,
      newState: this.state.interface
    });
  }

  setWidgetsVisible(visible: boolean): void {
    const oldState = this.state.interface.showWidgets;
    this.state.interface.showWidgets = visible;
    
    this.saveToStorage();
    this.notify('interface.showWidgets', visible);
    
    eventSystem.emit('global:widgets:visibility:changed', {
      oldState,
      newState: visible
    });
  }

  setFullscreenMode(isFullscreen: boolean): void {
    const oldState = this.state.interface.isFullscreen;
    this.state.interface.isFullscreen = isFullscreen;
    
    this.applyFullscreenMode(isFullscreen);
    this.saveToStorage();
    this.notify('interface.isFullscreen', isFullscreen);
    
    eventSystem.emit('global:fullscreen:changed', {
      oldState,
      newState: isFullscreen
    });
  }

  getInterfaceSettings() {
    return { ...this.state.interface };
  }

  // General getters
  getAllSettings(): GlobalSettings {
    return JSON.parse(JSON.stringify(this.state));
  }

  // Subscription system for reactive updates
  subscribe(path: string, callback: (value: any) => void): () => void {
    if (!this.subscribers.has(path)) {
      this.subscribers.set(path, new Set());
    }
    
    this.subscribers.get(path)!.add(callback);
    
    // Return unsubscribe function
    return () => {
      const subs = this.subscribers.get(path);
      if (subs) {
        subs.delete(callback);
        if (subs.size === 0) {
          this.subscribers.delete(path);
        }
      }
    };
  }

  private notify(path: string, value: any): void {
    const subscribers = this.subscribers.get(path);
    if (subscribers) {
      subscribers.forEach(callback => {
        try {
          callback(value);
        } catch (error) {
          console.error('Error in global state subscriber:', error);
        }
      });
    }
  }

  // UI application methods
  private applyTheme(theme: 'light' | 'dark' | 'auto'): void {
    const body = document.body;
    
    // Remove existing theme classes
    body.classList.remove('light-theme', 'dark-theme', 'light-windows');
    
    if (theme === 'auto') {
      // Use system preference
      const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      theme = prefersDark ? 'dark' : 'light';
    }
    
    if (theme === 'light') {
      body.classList.add('light-theme', 'light-windows');
    } else {
      body.classList.add('dark-theme');
    }
  }

  private applyLayout(layout: 'windows11' | 'windows10' | 'classic'): void {
    const body = document.body;
    
    // Remove existing layout classes
    body.classList.remove('layout-windows11', 'layout-windows10', 'layout-classic');
    body.classList.add(`layout-${layout}`);
    
    // Apply layout-specific taskbar styles
    const taskbar = document.querySelector('.taskbar');
    if (taskbar) {
      taskbar.classList.remove('taskbar-windows11-style', 'taskbar-windows10-style', 'taskbar-classic-style');
      taskbar.classList.add(`taskbar-${layout}-style`);
    }
  }

  private applyTaskbarPosition(position: 'bottom' | 'top' | 'left' | 'right'): void {
    const body = document.body;
    
    // Remove existing position classes
    body.classList.remove('taskbar-bottom', 'taskbar-top', 'taskbar-left', 'taskbar-right');
    body.classList.add(`taskbar-${position}`);
  }

  private applyTaskbarAutoHide(autoHide: boolean): void {
    const taskbar = document.querySelector('.taskbar');
    if (taskbar) {
      if (autoHide) {
        taskbar.classList.add('taskbar-auto-hide');
      } else {
        taskbar.classList.remove('taskbar-auto-hide');
      }
    }
  }

  private applyTaskbarStyle(style: 'default' | 'windows11' | 'left' | 'text'): void {
    const taskbar = document.querySelector('.taskbar');
    if (taskbar) {
      taskbar.classList.remove('taskbar-windows11-style', 'taskbar-windows10-style', 'taskbar-classic-style', 'taskbar-left-style', 'taskbar-text-style');
      taskbar.classList.add(`taskbar-${style}-style`);
    }
  }

  private async switchToModeWithAnimations(mode: 'desktop' | 'app-launcher' | 'easy', previousMode: 'desktop' | 'app-launcher' | 'easy'): Promise<void> {
    // Animate out current mode elements
    await this.animateOutMode(previousMode);
    
    // Apply the new mode
    this.applyInterfaceMode(mode);
    
    // Animate in new mode elements
    await this.animateInMode(mode);
  }
  
  private async animateOutMode(mode: 'desktop' | 'app-launcher' | 'easy'): Promise<void> {
    const animations: Promise<void>[] = [];
    
    switch (mode) {
      case 'desktop':
        // Animate out desktop icons
        const desktopIcons = document.querySelector('.desktop-icons');
        if (desktopIcons && (desktopIcons as HTMLElement).style.display !== 'none') {
          animations.push(this.animateElement(desktopIcons as HTMLElement, 'anim-slide-left'));
        }
        
        // Animate out widgets
        const widgets = document.querySelector('#widgets-screen');
        if (widgets && (widgets as HTMLElement).style.display !== 'none' && !widgets.classList.contains('widgets-hidden')) {
          animations.push(this.animateElement(widgets as HTMLElement, 'anim-slide-right'));
        }
        
        // Animate out taskbar
        const taskbar = document.querySelector('.taskbar');
        if (taskbar && (taskbar as HTMLElement).style.display !== 'none') {
          animations.push(this.animateElement(taskbar as HTMLElement, 'anim-slide-down'));
        }
        break;
        
      case 'app-launcher':
        // Animate out app launcher grid
        const appGrid = document.querySelector('.app-launcher-grid');
        if (appGrid) {
          animations.push(this.animateElement(appGrid as HTMLElement, 'anim-zoom-out'));
        }
        
        // Animate out top bar
        const topBar = document.querySelector('.app-launcher-top-bar');
        if (topBar) {
          animations.push(this.animateElement(topBar as HTMLElement, 'anim-slide-up'));
        }
        
        // Animate out taskbar
        const appTaskbar = document.querySelector('.taskbar');
        if (appTaskbar && (appTaskbar as HTMLElement).style.display !== 'none') {
          animations.push(this.animateElement(appTaskbar as HTMLElement, 'anim-slide-down'));
        }
        break;
        
      case 'easy':
        // Same as app launcher for easy mode
        const easyGrid = document.querySelector('.app-launcher-grid');
        if (easyGrid) {
          animations.push(this.animateElement(easyGrid as HTMLElement, 'anim-zoom-out'));
        }
        
        const easyTopBar = document.querySelector('.app-launcher-top-bar');
        if (easyTopBar) {
          animations.push(this.animateElement(easyTopBar as HTMLElement, 'anim-slide-up'));
        }
        break;
    }
    
    // Wait for all animations to complete
    if (animations.length > 0) {
      await Promise.all(animations);
    }
  }
  
  private async animateInMode(mode: 'desktop' | 'app-launcher' | 'easy'): Promise<void> {
    const animations: Promise<void>[] = [];
    
    switch (mode) {
      case 'desktop':
        // Animate in desktop icons
        const desktopIcons = document.querySelector('.desktop-icons');
        if (desktopIcons) {
          (desktopIcons as HTMLElement).style.display = '';
          animations.push(this.animateElement(desktopIcons as HTMLElement, 'anim-slide-right'));
        }
        
        // Animate in taskbar
        const taskbar = document.querySelector('.taskbar');
        if (taskbar) {
          (taskbar as HTMLElement).style.display = '';
          animations.push(this.animateElement(taskbar as HTMLElement, 'anim-slide-up'));
        }
        
        // Animate in widgets if they were visible
        const widgets = document.querySelector('#widgets-screen');
        if (widgets && !widgets.classList.contains('widgets-hidden')) {
          (widgets as HTMLElement).style.display = '';
          animations.push(this.animateElement(widgets as HTMLElement, 'anim-slide-in-right'));
        }
        break;
        
      case 'app-launcher':
      case 'easy':
        // Create and animate in app launcher UI
        await this.createAppLauncherUI(mode);
        
        const newTopBar = document.querySelector('.app-launcher-top-bar');
        if (newTopBar) {
          animations.push(this.animateElement(newTopBar as HTMLElement, 'anim-slide-down'));
        }
        
        const newGrid = document.querySelector('.app-launcher-grid');
        if (newGrid) {
          animations.push(this.animateElement(newGrid as HTMLElement, 'anim-zoom-in'));
        }
        
        // Show taskbar in app launcher mode only
        if (mode === 'app-launcher') {
          const appTaskbar = document.querySelector('.taskbar');
          if (appTaskbar) {
            (appTaskbar as HTMLElement).style.display = '';
            animations.push(this.animateElement(appTaskbar as HTMLElement, 'anim-slide-up'));
          }
        }
        break;
    }
    
    // Wait for all animations to complete
    if (animations.length > 0) {
      await Promise.all(animations);
    }
  }
  
  private animateElement(element: HTMLElement, animationClass: string): Promise<void> {
    return new Promise<void>((resolve) => {
      const handleAnimationEnd = () => {
        element.classList.remove(animationClass);
        element.removeEventListener('animationend', handleAnimationEnd);
        resolve();
      };
      
      element.addEventListener('animationend', handleAnimationEnd);
      element.classList.add(animationClass);
      
      // Fallback timeout in case animation doesn't fire
      setTimeout(() => {
        element.classList.remove(animationClass);
        element.removeEventListener('animationend', handleAnimationEnd);
        resolve();
      }, 1000);
    });
  }
  
  private async createAppLauncherUI(mode: 'app-launcher' | 'easy'): Promise<void> {
    // Remove any existing app launcher desktop
    const existingLauncher = document.getElementById('app-launcher-desktop');
    if (existingLauncher) {
      existingLauncher.remove();
    }
    
    // Create the app launcher container
    const launcherDesktop = document.createElement('div');
    launcherDesktop.id = 'app-launcher-desktop';
    launcherDesktop.style.position = 'absolute';
    launcherDesktop.style.top = '0';
    launcherDesktop.style.left = '0';
    launcherDesktop.style.width = '100%';
    launcherDesktop.style.height = '100%';
    launcherDesktop.style.display = 'flex';
    launcherDesktop.style.flexDirection = 'column';
    
    // Create top bar
    const topBar = this.createAppLauncherTopBar();
    launcherDesktop.appendChild(topBar);
    
    // Create app grid
    const grid = this.createAppLauncherGrid();
    launcherDesktop.appendChild(grid);
    
    // Add to desktop area
    const desktopArea = document.querySelector('.desktop-container');
    if (desktopArea) {
      desktopArea.appendChild(launcherDesktop);
    }
  }
  
  private createAppLauncherTopBar(): HTMLElement {
    const topBar = document.createElement('div');
    topBar.className = 'app-launcher-top-bar';
    topBar.style.display = 'flex';
    topBar.style.justifyContent = 'space-between';
    topBar.style.alignItems = 'center';
    topBar.style.padding = '16px 24px';
    topBar.style.background = 'rgba(0, 0, 0, 0.1)';
    topBar.style.backdropFilter = 'blur(20px)';
    topBar.style.borderBottom = '1px solid rgba(255, 255, 255, 0.1)';
    
    // Title
    const title = document.createElement('h1');
    title.textContent = 'Applications';
    title.style.color = 'white';
    title.style.margin = '0';
    title.style.fontSize = '24px';
    title.style.fontWeight = '600';
    
    // User info (placeholder)
    const userInfo = document.createElement('div');
    userInfo.style.display = 'flex';
    userInfo.style.alignItems = 'center';
    userInfo.style.gap = '12px';
    userInfo.innerHTML = `
      <div style="width: 32px; height: 32px; border-radius: 50%; background: rgba(59, 130, 246, 0.8); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px;">U</div>
      <span style="color: white; font-size: 16px;">User</span>
    `;
    
    topBar.appendChild(title);
    topBar.appendChild(userInfo);
    
    return topBar;
  }
  
  private createAppLauncherGrid(): HTMLElement {
    const grid = document.createElement('div');
    grid.className = 'app-launcher-grid';
    grid.style.display = 'grid';
    grid.style.gridTemplateColumns = 'repeat(auto-fill, minmax(90px, 1fr))';
    grid.style.gap = '32px';
    grid.style.width = 'min(90vw, 900px)';
    grid.style.maxWidth = '100vw';
    grid.style.margin = 'auto';
    grid.style.justifyItems = 'center';
    grid.style.alignItems = 'start';
    grid.style.padding = '32px 0 90px 0';
    grid.style.flex = '1 1 0';
    grid.style.alignContent = 'center';
    grid.style.justifyContent = 'center';
    
    // Sample apps (this should come from a real app registry)
    const sampleApps = [
      { id: 'calculator', name: 'Calculator', iconClass: 'fa-calculator', iconBgClass: 'blue-icon' },
      { id: 'file-explorer', name: 'Files', iconClass: 'fa-folder', iconBgClass: 'yellow-icon' },
      { id: 'settings', name: 'Settings', iconClass: 'fa-cog', iconBgClass: 'gray-icon' },
      { id: 'browser', name: 'Browser', iconClass: 'fa-globe', iconBgClass: 'green-icon' },
      { id: 'email', name: 'Email', iconClass: 'fa-envelope', iconBgClass: 'red-icon' },
      { id: 'photos', name: 'Photos', iconClass: 'fa-image', iconBgClass: 'purple-icon' }
    ];
    
    sampleApps.forEach(app => {
      const appItem = document.createElement('div');
      appItem.className = 'app-launcher-app';
      appItem.style.display = 'flex';
      appItem.style.flexDirection = 'column';
      appItem.style.alignItems = 'center';
      appItem.style.justifyContent = 'center';
      appItem.style.minHeight = '120px';
      appItem.style.cursor = 'pointer';
      appItem.style.userSelect = 'none';
      appItem.tabIndex = 0;
      appItem.setAttribute('data-app', app.id);
      
      appItem.innerHTML = `
        <div class="icon-container ${app.iconBgClass}" style="width:64px;height:64px;border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:28px;box-shadow:0 4px 16px rgba(0,0,0,0.18);margin-bottom:10px;">
          <i class="fas ${app.iconClass}"></i>
        </div>
        <span style="font-size:14px;color:#fff;margin-top:5px;text-shadow:0 1px 4px #222;text-align:center;width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:block;">${app.name}</span>
      `;
      
             // Add click handler
       appItem.addEventListener('click', () => {
         // This should emit an event to launch the app
         eventSystem.emit('app:launched', { appId: app.id, appName: app.name });
       });
      
      grid.appendChild(appItem);
    });
    
    return grid;
  }

  private applyInterfaceMode(mode: 'desktop' | 'app-launcher' | 'easy'): void {
    const body = document.body;
    const desktopArea = document.querySelector('.desktop-container');
    
    // Remove existing mode classes
    body.classList.remove('desktop-mode', 'app-launcher-mode', 'easy-mode');
    if (desktopArea) {
      desktopArea.classList.remove('desktop-mode', 'app-launcher-mode', 'easy-mode');
    }
    
    // Apply new mode class
    body.classList.add(`${mode}-mode`);
    if (desktopArea) {
      desktopArea.classList.add(`${mode}-mode`);
    }
    
    // Mode-specific logic (synchronous cleanup)
    switch (mode) {
      case 'desktop':
        this.applyDesktopMode();
        break;
      case 'app-launcher':
        this.applyAppLauncherMode();
        break;
      case 'easy':
        this.applyEasyMode();
        break;
    }
  }

  private applyDesktopMode(): void {
    // Show desktop icons
    const desktopIcons = document.querySelector('.desktop-icons');
    if (desktopIcons) {
      (desktopIcons as HTMLElement).style.display = '';
    }
    
    // Show taskbar
    const taskbar = document.querySelector('.taskbar');
    if (taskbar) {
      (taskbar as HTMLElement).style.display = '';
    }
    
    // Show widgets toggle if enabled
    const widgetsToggle = document.querySelector('.widgets-toggle');
    if (widgetsToggle) {
      (widgetsToggle as HTMLElement).style.display = '';
    }
    
    // Reset window state - allow normal window controls
    this.resetWindowControls();
  }

  private applyAppLauncherMode(): void {
    // Hide desktop icons
    const desktopIcons = document.querySelector('.desktop-icons');
    if (desktopIcons) {
      (desktopIcons as HTMLElement).style.display = 'none';
    }
    
    // Show taskbar but modified
    const taskbar = document.querySelector('.taskbar');
    if (taskbar) {
      (taskbar as HTMLElement).style.display = '';
      taskbar.classList.add('app-launcher-taskbar');
    }
    
    // Show app launcher grid (would need to be implemented)
    this.showAppLauncher();
  }

  private applyEasyMode(): void {
    // Hide desktop icons
    const desktopIcons = document.querySelector('.desktop-icons');
    if (desktopIcons) {
      (desktopIcons as HTMLElement).style.display = 'none';
    }
    
    // Hide taskbar completely in easy mode
    const taskbar = document.querySelector('.taskbar');
    if (taskbar) {
      (taskbar as HTMLElement).style.display = 'none';
    }
    
    // Hide widgets
    this.setWidgetsVisible(false);
    
    // Modify window controls for easy mode
    this.applyEasyModeWindowControls();
    
    // Show app launcher with simplified interface
    this.showEasyModeAppLauncher();
  }

  private applyFullscreenMode(isFullscreen: boolean): void {
    if (isFullscreen) {
      document.documentElement.requestFullscreen?.();
    } else {
      document.exitFullscreen?.();
    }
  }

  private resetWindowControls(): void {
    // Reset all windows to show normal controls
    const windows = document.querySelectorAll('.window');
    windows.forEach(window => {
      window.classList.remove('easy-mode-window');
      const controls = window.querySelector('.window-controls');
      if (controls) {
        (controls as HTMLElement).style.display = '';
      }
    });
  }

  private applyEasyModeWindowControls(): void {
    // Modify all windows for easy mode
    const windows = document.querySelectorAll('.window');
    windows.forEach(window => {
      window.classList.add('easy-mode-window');
      
      // Hide minimize/maximize buttons, keep only close
      const minimizeBtn = window.querySelector('.window-minimize');
      const maximizeBtn = window.querySelector('.window-maximize');
      
      if (minimizeBtn) (minimizeBtn as HTMLElement).style.display = 'none';
      if (maximizeBtn) (maximizeBtn as HTMLElement).style.display = 'none';
      
      // Maximize the window
      window.classList.add('window-maximized');
    });
  }

  private showAppLauncher(): void {
    // Implementation for app launcher mode
    const appLauncher = document.querySelector('.app-launcher');
    if (appLauncher) {
      (appLauncher as HTMLElement).style.display = 'flex';
    }
  }

  private showEasyModeAppLauncher(): void {
    // Implementation for easy mode app launcher
    const easyLauncher = document.querySelector('.easy-mode-launcher');
    if (easyLauncher) {
      (easyLauncher as HTMLElement).style.display = 'flex';
    }
  }

  private updateNotificationBadgeUI(): void {
    const badge = document.querySelector('#notifications-badge');
    const count = this.state.notifications.badgeCount;
    
    if (badge) {
      if (count > 0) {
        badge.textContent = count > 99 ? '99+' : count.toString();
        badge.classList.add('visible');
        badge.classList.remove('hidden');
      } else {
        badge.classList.add('hidden');
        badge.classList.remove('visible');
      }
    }
    
    // Update opacity based on count
    const notificationsBtn = document.querySelector('#notifications-btn');
    if (notificationsBtn) {
      if (count > 0) {
        notificationsBtn.classList.add('has-notifications');
        (notificationsBtn as HTMLElement).style.opacity = '1';
      } else {
        notificationsBtn.classList.remove('has-notifications');
        (notificationsBtn as HTMLElement).style.opacity = '0.6';
      }
    }
  }

  // Storage management
  private loadFromStorage(): void {
    try {
      const stored = localStorage.getItem('desktop-global-state');
      if (stored) {
        const parsed = JSON.parse(stored);
        this.state = { ...this.state, ...parsed };
      }
    } catch (error) {
      console.warn('Failed to load global state from storage:', error);
    }
    
    // Apply initial settings
    this.applyTheme(this.state.desktop.theme);
    this.applyLayout(this.state.desktop.layout);
    this.applyTaskbarPosition(this.state.taskbar.position);
    this.applyTaskbarAutoHide(this.state.taskbar.autoHide);
    this.applyTaskbarStyle(this.state.taskbar.style);
    this.applyInterfaceMode(this.state.interface.mode);
    this.updateNotificationBadgeUI();
  }

  private saveToStorage(): void {
    try {
      localStorage.setItem('desktop-global-state', JSON.stringify(this.state));
    } catch (error) {
      console.warn('Failed to save global state to storage:', error);
    }
  }

  private setupEventListeners(): void {
    // Listen for system theme changes
    if (window.matchMedia) {
      const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
      mediaQuery.addEventListener('change', (e) => {
        if (this.state.desktop.theme === 'auto') {
          this.applyTheme('auto');
        }
      });
    }
    
    // Save state before page unload
    window.addEventListener('beforeunload', () => {
      this.saveToStorage();
    });
  }

  // Debug methods
  debug(): Record<string, any> {
    return {
      state: this.getAllSettings(),
      subscribers: Array.from(this.subscribers.keys()),
      subscriberCounts: Object.fromEntries(
        Array.from(this.subscribers.entries()).map(([key, set]) => [key, set.size])
      )
    };
  }

  reset(): void {
    // Reset to default state
    this.state = {
      volume: {
        isMuted: false,
        previousVolume: 75,
        currentVolume: 75
      },
      notifications: {
        isMuted: false,
        stackingMode: 'three',
        badgeCount: 0
      },
      desktop: {
        theme: 'dark',
        layout: 'windows11',
        iconSize: 'medium',
        showDesktopIcons: true
      },
      taskbar: {
        position: 'bottom',
        autoHide: false,
        showLabels: true,
        size: 'medium',
        style: 'default',
        showSearchBar: true,
        showAppLauncher: true,
        showGlobalSearch: true,
        showWallet: true,
        showFullscreen: true,
        showVolume: true
      },
      orientation: {
        locked: false,
        preferred: 'auto'
      },
      interface: {
        mode: 'desktop',
        previousMode: 'desktop',
        showWidgets: false,
        isFullscreen: false
      },
      wallet: {
        balance: 0,
        currency: 'USD',
        transactions: []
      }
    };
    
    this.saveToStorage();
    this.applyTheme(this.state.desktop.theme);
    this.applyLayout(this.state.desktop.layout);
    this.applyTaskbarPosition(this.state.taskbar.position);
    this.applyTaskbarAutoHide(this.state.taskbar.autoHide);
    this.applyTaskbarStyle(this.state.taskbar.style);
    this.applyInterfaceMode(this.state.interface.mode);
    this.updateNotificationBadgeUI();
    
    eventSystem.emit('global:state:reset', {});
  }
}

// Create singleton instance
export const globalStateManager = new GlobalStateManager();

// Expose debug function globally for development
if (typeof window !== 'undefined') {
  (window as any).debugGlobalState = () => globalStateManager.debug();
  (window as any).resetGlobalState = () => globalStateManager.reset();
}

export default globalStateManager; 