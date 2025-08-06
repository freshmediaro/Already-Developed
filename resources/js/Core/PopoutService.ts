// Popout Window Service - Extracted from original app.js
import { eventSystem } from './EventSystem';

interface PopoutInfo {
  appName: string;
  appTitle: string;
  iconClass: string;
  iconBgClass: string;
}

export class PopoutService {
  private static instance: PopoutService;
  private allPopoutWindows: Window[] = [];

  static getInstance(): PopoutService {
    if (!PopoutService.instance) {
      PopoutService.instance = new PopoutService();
    }
    return PopoutService.instance;
  }

  private constructor() {
    // Listen for logout to close all popout windows
    eventSystem.on('user:logout', () => {
      (window as any)._loggingOut = true;
      this.closeAllPopoutWindows();
      setTimeout(() => { (window as any)._loggingOut = false; }, 2000);
    });
  }

  popoutWindow(windowElement: HTMLElement, windowId: string): void {
    // Get original window position and size
    const rect = windowElement.getBoundingClientRect();
    const screenLeft = window.screenX || window.screenLeft || 0;
    const screenTop = window.screenY || window.screenTop || 0;
    const chromeHeight = (window.outerHeight - window.innerHeight) || 0;
    const left = Math.round(screenLeft + rect.left);
    const top = Math.round(screenTop + rect.top + chromeHeight);
    const width = Math.round(rect.width);
    const height = Math.round(rect.height);

    // Open a new window with matching position and size
    const popoutWin = window.open('', '_blank', 
      `width=${width},height=${height},left=${left},top=${top},menubar=no,toolbar=no,location=no,status=no`);
    
    if (!popoutWin) return;

    // Track the popout window
    this.allPopoutWindows.push(popoutWin);

    // --- Cross-browser: Write HTML immediately after open ---
    const doc = popoutWin.document;
    doc.open();
    
    const windowTitle = windowElement.querySelector('.window-title span')?.textContent || 'App Popout';
    doc.write(`<!DOCTYPE html><html><head><title>${windowTitle}</title>`);
    
    // Copy stylesheets
    Array.from(document.styleSheets).forEach(sheet => {
      if (sheet.href) {
        doc.write(`<link rel="stylesheet" href="${sheet.href}">`);
      }
    });
    
    // Inject main JS file for popout functionality
    doc.write('<script src="js/app.js"></script>');
    doc.write('</head><body style="background: var(--primary-bg); margin:0;">');
    
    // Clone window element and remove positioning styles
    const clonedWindow = windowElement.cloneNode(true) as HTMLElement;
    clonedWindow.style.position = '';
    clonedWindow.style.left = '';
    clonedWindow.style.top = '';
    clonedWindow.style.width = '';
    clonedWindow.style.height = '';
    
    // Write the .window element
    doc.write(clonedWindow.outerHTML);
    
    // Add style to make .window fill the viewport in the popout and hide the window header
    doc.write(`<style>
      .window {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        max-width: none !important;
        max-height: none !important;
        min-width: 0 !important;
        min-height: 0 !important;
        z-index: 1 !important;
      }
      .window-header {
        display: none !important;
      }
    </style>`);
    
    doc.write('</body></html>');
    doc.close();

    // --- Save icon and bg class for restore ---
    let iconClass = null, iconBgClass = null;
    const iconElem = windowElement.querySelector('.window-title .window-icon i');
    const iconBgElem = windowElement.querySelector('.window-title .window-icon');
    if (iconElem) {
      iconClass = Array.from(iconElem.classList).find(cls => cls.startsWith('fa-'));
    }
    if (iconBgElem) {
      iconBgClass = Array.from(iconBgElem.classList).find(cls => cls.endsWith('-icon'));
    }

    // --- Save appName and appTitle BEFORE removing window ---
    const restoreAppName = windowElement.getAttribute('data-app') || '';
    const restoreAppTitle = windowElement.querySelector('.window-title span')?.textContent || '';

    // Emit event to close original window
    eventSystem.emit('window:popout', { 
      windowId, 
      popoutWindow: popoutWin,
      appName: restoreAppName,
      appTitle: restoreAppTitle,
      iconClass,
      iconBgClass
    });

    // --- Popout close behavior: restore if needed ---
    const popoutBehavior = localStorage.getItem('popoutCloseBehavior') || 'close';
    if (popoutBehavior === 'restore') {
      // Pass info to the popout window so it can notify us on close
      try {
        (popoutWin as any)._poppedOutAppInfo = {
          appName: restoreAppName,
          appTitle: restoreAppTitle,
          iconClass: iconClass,
          iconBgClass: iconBgClass
        };
      } catch (e) {}

      // Listen for popout window close
      const restoreApp = () => {
        const info = (popoutWin as any)._poppedOutAppInfo;
        if (!(window as any)._loggingOut && info && info.appName) {
          eventSystem.emit('app:launch', { 
            appId: info.appName, 
            appTitle: info.appTitle,
            iconClass: info.iconClass,
            iconBgClass: info.iconBgClass
          });
        }
      };

      // Use polling to detect close (since onbeforeunload in popout is unreliable cross-origin)
      const pollInterval = setInterval(() => {
        if (popoutWin.closed) {
          clearInterval(pollInterval);
          this.removePopoutWindow(popoutWin);
          restoreApp();
        }
      }, 500);
    } else {
      // Just track for cleanup, no restore
      const pollInterval = setInterval(() => {
        if (popoutWin.closed) {
          clearInterval(pollInterval);
          this.removePopoutWindow(popoutWin);
        }
      }, 500);
    }
  }

  private removePopoutWindow(popoutWin: Window): void {
    const index = this.allPopoutWindows.indexOf(popoutWin);
    if (index > -1) {
      this.allPopoutWindows.splice(index, 1);
    }
  }

  private closeAllPopoutWindows(): void {
    this.allPopoutWindows.forEach(win => {
      try {
        if (!win.closed) {
          win.close();
        }
      } catch (e) {
        // Ignore errors when closing popout windows
      }
    });
    this.allPopoutWindows = [];
  }
}

export const popoutService = PopoutService.getInstance(); 