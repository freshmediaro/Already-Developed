// Orientation Management System - Extracted from original app.js
import { eventSystem } from './EventSystem';

interface DeviceInfo {
  isMobilePhone: boolean;
  isTablet: boolean;
  isDesktop: boolean;
  isLandscape: boolean;
  isPortrait: boolean;
}

class OrientationManager {
  private initialized = false;
  private resizeTimeout?: NodeJS.Timeout;

  constructor() {
    // Auto-initialize if DOM is ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => this.initialize());
    } else {
      this.initialize();
    }
  }

  initialize(): void {
    if (this.initialized) return;
    
    console.log('Initializing orientation control');
    
    // Handle initial load
    this.handleOrientationChange();
    
    // Listen for orientation changes
    window.addEventListener('orientationchange', () => {
      // Small delay to ensure new dimensions are available
      setTimeout(() => this.handleOrientationChange(), 100);
    });
    
    // Also listen for resize events (for desktop/browser testing)
    window.addEventListener('resize', () => {
      clearTimeout(this.resizeTimeout);
      this.resizeTimeout = setTimeout(() => this.handleOrientationChange(), 250);
    });
    
    // Listen for window focus (in case user switches apps and comes back)
    window.addEventListener('focus', () => this.handleOrientationChange());
    
    // Disable pinch zoom on mobile devices
    window.addEventListener(
      'touchmove',
      (event: TouchEvent) => {
        // TypeScript workaround for scale property
        const touchEvent = event as any;
        if (touchEvent.scale !== 1) {
          event.preventDefault();
          event.stopImmediatePropagation();
        }
      },
      { passive: false }
    );
    
    this.updateViewportMeta();
    this.initialized = true;
  }

  private handleOrientationChange(): void {
    const deviceInfo = this.getDeviceInfo();
    const actualWidth = Math.max(window.innerWidth, window.outerWidth || 0);
    const actualHeight = Math.max(window.innerHeight, window.outerHeight || 0);
    
    console.log('Orientation change detected:', {
      width: actualWidth,
      height: actualHeight,
      innerWidth: window.innerWidth,
      innerHeight: window.innerHeight,
      ...deviceInfo,
      orientation: (screen.orientation as any)?.angle ?? 'unknown'
    });
    
    if (deviceInfo.isMobilePhone) {
      // Mobile phones: Show warning overlay in landscape
      if (deviceInfo.isLandscape) {
        console.log('Mobile phone in landscape - showing warning overlay');
        this.createLandscapeWarning();
        document.body.classList.add('orientation-locked');
        document.body.style.overflow = 'hidden';
        
        // Try to lock orientation
        this.lockToPortrait();
      } else {
        console.log('Mobile phone in portrait - removing overlay');
        this.removeLandscapeWarning();
        this.removePortraitOverlay();
        document.body.classList.remove('orientation-locked');
        document.body.style.overflow = '';
      }
    } else if (deviceInfo.isTablet) {
      // Tablets: Allow both orientations
      console.log('Tablet device - allowing both orientations');
      this.removeLandscapeWarning();
      this.removePortraitOverlay();
      this.unlockOrientation();
      document.body.classList.remove('orientation-locked');
      document.body.style.overflow = '';
      
      // Update tablet-specific responsive layouts
      if (deviceInfo.isLandscape) {
        document.body.classList.add('tablet-landscape');
        document.body.classList.remove('tablet-portrait');
      } else {
        document.body.classList.add('tablet-portrait');
        document.body.classList.remove('tablet-landscape');
      }
    } else {
      // Desktop: No restrictions
      console.log('Desktop device - no orientation restrictions');
      this.removeLandscapeWarning();
      this.removePortraitOverlay();
      this.unlockOrientation();
      document.body.classList.remove('orientation-locked', 'tablet-landscape', 'tablet-portrait');
      document.body.style.overflow = '';
    }

    // Emit orientation change event
    eventSystem.emit('orientation:changed', deviceInfo);
  }

  private getDeviceInfo(): DeviceInfo {
    const width = window.innerWidth;
    const height = window.innerHeight;
    const ratio = width / height;
    
    return {
      isMobilePhone: width <= 767 && height <= 1024,
      isTablet: (width > 767 && width <= 1024) || (height > 767 && height <= 1024),
      isDesktop: width > 1024 && height > 768,
      isLandscape: ratio > 1.0,
      isPortrait: ratio <= 1.0
    };
  }

  private createLandscapeWarning(): HTMLElement {
    let warning = document.querySelector('.force-landscape-warning') as HTMLElement;
    if (warning) return warning;

    warning = document.createElement('div');
    warning.className = 'force-landscape-warning';
    warning.innerHTML = `
      <div class="orientation-warning-content">
        <div class="rotate-icon">📱</div>
        <h2>Please rotate your device</h2>
        <p>This app works best in portrait mode</p>
        <div class="device-outline">
          <div class="device-screen"></div>
        </div>
      </div>
    `;
    
    document.body.appendChild(warning);

    // Add bounce animation
    if (!document.querySelector('#orientation-warning-styles')) {
      const style = document.createElement('style');
      style.id = 'orientation-warning-styles';
      style.textContent = `
        .force-landscape-warning {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background: rgba(0, 0, 0, 0.95);
          color: white;
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 999999;
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        .orientation-warning-content {
          text-align: center;
          padding: 2rem;
        }
        .rotate-icon {
          font-size: 4rem;
          margin-bottom: 1rem;
          animation: bounce 2s infinite;
        }
        @keyframes bounce {
          0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
          40% { transform: translateY(-10px); }
          60% { transform: translateY(-5px); }
        }
      `;
      document.head.appendChild(style);
    }
    return warning;
  }

  private removeLandscapeWarning(): void {
    const warning = document.querySelector('.force-landscape-warning');
    if (warning) {
      warning.remove();
    }
  }

  private removePortraitOverlay(): void {
    const overlay = document.querySelector('.force-portrait-overlay');
    if (overlay) {
      overlay.remove();
    }
  }

  private async lockToPortrait(): Promise<void> {
    if (screen.orientation && (screen.orientation as any).lock) {
      try {
        await (screen.orientation as any).lock('portrait');
      } catch (err) {
        console.log('Cannot lock orientation:', err);
      }
    }
  }

  private unlockOrientation(): void {
    if (screen.orientation && (screen.orientation as any).unlock) {
      try {
        (screen.orientation as any).unlock();
      } catch (err) {
        console.log('Cannot unlock orientation:', err);
      }
    }
  }

  private updateViewportMeta(): void {
    let viewport = document.querySelector('meta[name="viewport"]') as HTMLMetaElement;
    if (!viewport) {
      viewport = document.createElement('meta');
      viewport.name = 'viewport';
      document.head.appendChild(viewport);
    }
    
    const deviceInfo = this.getDeviceInfo();
    
    if (deviceInfo.isMobilePhone) {
      // Lock viewport for mobile phones
      viewport.content = 'width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0, minimum-scale=1.0, orientation=portrait';
    } else if (deviceInfo.isTablet) {
      // Allow scaling for tablets but maintain responsive design
      viewport.content = 'width=device-width, initial-scale=1.0, user-scalable=yes, maximum-scale=2.0, minimum-scale=0.5';
    } else {
      // Standard viewport for desktop
      viewport.content = 'width=device-width, initial-scale=1.0';
    }
  }

  /**
   * Debug function for orientation
   */
  debug(): Record<string, any> {
    const deviceInfo = this.getDeviceInfo();
    return {
      dimensions: {
        width: window.innerWidth,
        height: window.innerHeight,
        ratio: (window.innerWidth / window.innerHeight).toFixed(2)
      },
      device: deviceInfo,
      orientation: {
        angle: (screen.orientation as any)?.angle ?? 'unknown'
      },
      features: {
        orientationLock: !!(screen.orientation && (screen.orientation as any).lock),
        orientationAPI: !!screen.orientation
      },
      classes: {
        orientationLocked: document.body.classList.contains('orientation-locked'),
        tabletLandscape: document.body.classList.contains('tablet-landscape'),
        tabletPortrait: document.body.classList.contains('tablet-portrait')
      }
    };
  }
}

// Create singleton instance
export const orientationManager = new OrientationManager();

// Expose debug function globally for development
if (typeof window !== 'undefined') {
  (window as any).debugOrientation = () => orientationManager.debug();
}

export default orientationManager; 