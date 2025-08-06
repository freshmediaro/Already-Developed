// Alert Service - Extracted from original app.js
// Handles two types of alerts:
// 1. Confirmation dialogs for user actions (logout, uninstall, etc.)
// 2. Toast notifications for system feedback (pinned to taskbar, etc.)

import { eventSystem } from './EventSystem';

interface ConfirmDialogOptions {
  title: string;
  message: string;
  iconClass?: string;
  okText?: string;
  cancelText?: string;
  style?: 'desktop' | 'logout';
  showCheckboxes?: boolean;
  checkboxes?: Array<{
    id: string;
    label: string;
    checked?: boolean;
  }>;
}

interface ToastNotificationOptions {
  title?: string;
  description?: string;
  iconClass?: string;
  iconBgClass?: string;
  avatar?: string;
  meta?: string;
  content?: string; // Custom HTML content
}

type NotificationMode = 'one' | 'three' | 'all';

export class AlertService {
  private static instance: AlertService;
  private notificationMode: NotificationMode = 'three';
  private isNotificationsMuted = false;

  static getInstance(): AlertService {
    if (!AlertService.instance) {
      AlertService.instance = new AlertService();
    }
    return AlertService.instance;
  }

  private constructor() {
    // Listen to notification settings changes
    eventSystem.on('global:notifications:mute:changed', (payload) => {
      this.isNotificationsMuted = payload.data.muted;
    });

    eventSystem.on('global:notifications:stacking:changed', (payload) => {
      this.notificationMode = payload.data.mode;
    });
  }

  /**
   * Show confirmation dialog for user actions (logout, uninstall, etc.)
   */
  showConfirmDialog(options: ConfirmDialogOptions): Promise<boolean> {
    return new Promise((resolve) => {
      // Remove existing alert if any
      let existing = document.getElementById('os-alert-overlay');
      if (existing) existing.remove();

      const overlay = document.createElement('div');
      overlay.id = 'os-alert-overlay';

      if (options.style === 'desktop') {
        overlay.className = 'alert-overlay desktop-alert-overlay';
        overlay.innerHTML = `
          <div class="alert-dialog desktop-alert-dialog">
            <div class="alert-icon"><i class="fas ${options.iconClass || 'fa-question-circle'}"></i></div>
            <div class="alert-title">${options.title || ''}</div>
            <div class="alert-message">${options.message || ''}</div>
            ${options.showCheckboxes ? this.renderCheckboxes(options.checkboxes || []) : ''}
            <div class="alert-actions">
              <button class="alert-btn alert-cancel desktop-alert-cancel">${options.cancelText || 'Cancel'}</button>
              <button class="alert-btn alert-ok desktop-alert-ok">${options.okText || 'OK'}</button>
            </div>
          </div>
        `;
      } else {
        overlay.className = 'alert-overlay';
        overlay.innerHTML = `
          <div class="alert-dialog">
            <div class="alert-icon"><i class="fas ${options.iconClass || 'fa-question-circle'}"></i></div>
            <div class="alert-title">${options.title || ''}</div>
            <div class="alert-message">${options.message || ''}</div>
            <div class="alert-actions">
              <button class="alert-btn alert-cancel">${options.cancelText || 'Cancel'}</button>
              <button class="alert-btn alert-ok">${options.okText || 'OK'}</button>
            </div>
          </div>
        `;
      }

      document.body.appendChild(overlay);

      // Add background visibility animation for non-desktop style
      if (options.style !== 'desktop') {
        setTimeout(() => overlay.classList.add('alert-overlay-bg-visible'), 10);
      }

      // Focus the OK button
      setTimeout(() => {
        const okBtn = overlay.querySelector('.alert-ok') as HTMLElement;
        okBtn?.focus();
      }, 10);

      const fadeOutAndRemove = (result: boolean) => {
        overlay.classList.add('alert-overlay-fadeout');
        setTimeout(() => {
          overlay.remove();
          resolve(result);
        }, 400);
      };

      // Event handlers
      const cancelBtn = overlay.querySelector('.alert-cancel');
      const okBtn = overlay.querySelector('.alert-ok');

      cancelBtn?.addEventListener('click', () => fadeOutAndRemove(false));
      okBtn?.addEventListener('click', () => {
        // Get checkbox values if any
        const checkboxData: Record<string, boolean> = {};
        if (options.showCheckboxes) {
          const checkboxes = overlay.querySelectorAll('input[type="checkbox"]');
          checkboxes.forEach((checkbox) => {
            const input = checkbox as HTMLInputElement;
            checkboxData[input.id] = input.checked;
          });
        }

        overlay.remove();
        resolve(true);

        // Emit event with checkbox data if applicable
        if (options.showCheckboxes && Object.keys(checkboxData).length > 0) {
          eventSystem.emit('alert:dialog:confirmed', { 
            title: options.title,
            checkboxData 
          });
        }
      });

      // Keyboard support
      overlay.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
          fadeOutAndRemove(false);
        }
      });
    });
  }

  /**
   * Show short toast notification for system feedback
   */
  showShortTopNotification(message: string): void {
    // Remove existing notification
    let existing = document.getElementById('os-short-top-notification');
    if (existing) existing.remove();

    const notif = document.createElement('div');
    notif.id = 'os-short-top-notification';
    notif.textContent = message;
    
    // Apply styles directly (matching app.js exactly)
    notif.style.position = 'fixed';
    notif.style.top = '32px';
    notif.style.left = '50%';
    notif.style.transform = 'translateX(-50%)';
    notif.style.background = 'var(--widget-bg)';
    notif.style.backdropFilter = 'blur(30px)';
    notif.style.color = '#fff';
    notif.style.fontSize = '16px';
    notif.style.fontWeight = '400';
    notif.style.padding = '10px 26px';
    notif.style.borderRadius = '16px';
    notif.style.zIndex = '999999';
    notif.style.boxShadow = '0 4px 24px rgba(0,0,0,0.18)';
    notif.style.opacity = '0';
    notif.style.transition = 'opacity 0.25s';

    document.body.appendChild(notif);

    // Animate in
    setTimeout(() => { notif.style.opacity = '1'; }, 10);

    // Animate out and remove
    setTimeout(() => {
      notif.style.opacity = '0';
      setTimeout(() => notif.remove(), 400);
    }, 1200);
  }

  /**
   * Show Windows 11 style toast notification
   */
  showToastNotification(options: ToastNotificationOptions = {}): void {
    if (this.isNotificationsMuted) return;

    // Default content or custom content
    const notifContent = options.content || `
      <button class="notif-delete-btn" title="Dismiss notification">&times;</button>
      <div class="notif-icon-bg ${options.iconBgClass || 'notif-bg-blue'}">
        <i class="fas ${options.iconClass || 'fa-bell'}"></i>
      </div>
      <div class="notif-content">
        <div class="notif-main-row">
          <span class="notif-main-title">${options.title || 'Notification'}</span>
        </div>
        <div class="notif-desc">${options.description || 'This is a notification'}</div>
        <div class="notif-meta">${options.meta || 'now'}</div>
      </div>
      ${options.avatar ? `<img class="notif-avatar" src="${options.avatar}" />` : ''}
    `;

    // Get or create toast container
    let container = document.getElementById('os-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'os-toast-container';
      container.style.position = 'fixed';
      container.style.top = '32px';
      container.style.right = '32px';
      container.style.width = '340px';
      container.style.zIndex = '999999';
      container.style.pointerEvents = 'none';
      container.style.height = 'auto';
      document.body.appendChild(container);
    }

    // Remove all if mode is 'one'
    if (this.notificationMode === 'one') {
      Array.from(container.children).forEach(child => child.remove());
    }

    // Create toast
    const toast = document.createElement('div');
    toast.className = 'notif-card unread os-toast-notification';
    toast.style.position = 'absolute';
    toast.style.right = '0';
    toast.style.left = 'auto';
    toast.style.margin = '0';
    toast.style.width = '340px';
    toast.style.maxWidth = '90vw';
    toast.style.pointerEvents = 'auto';
    toast.innerHTML = notifContent;

    // Insert at top (index 0)
    container.insertBefore(toast, container.firstChild);

    // Animate in
    toast.style.transform = 'translateX(120%)';
    toast.style.opacity = '0.7';
    setTimeout(() => {
      toast.style.transition = 'top 0.35s cubic-bezier(0.4,0,0.2,1), transform 0.45s cubic-bezier(0.4,0,0.2,1), opacity 0.45s cubic-bezier(0.4,0,0.2,1)';
      toast.style.transform = 'translateX(0)';
      toast.style.opacity = '1';
    }, 10);

    // Dismiss logic
    let dismissTimer: ReturnType<typeof setTimeout>;
    let dismissed = false;

    const dismissToast = () => {
      if (dismissed) return;
      dismissed = true;
      toast.style.opacity = '0.7';
      toast.style.transform = 'translateX(120%)';
      setTimeout(() => {
        toast.remove();
        this.updateToastStackPositions();
      }, 500);
    };

    const startTimer = () => {
      dismissTimer = setTimeout(dismissToast, 4000);
    };

    const clearTimer = () => {
      if (dismissTimer) clearTimeout(dismissTimer);
    };

    toast.addEventListener('mouseenter', clearTimer);
    toast.addEventListener('mouseleave', startTimer);

    const delBtn = toast.querySelector('.notif-delete-btn');
    if (delBtn) {
      delBtn.addEventListener('click', dismissToast);
    }

    startTimer();

    // Stacking limit logic
    this.limitToastStack(container);
    this.updateToastStackPositions();
  }

  private renderCheckboxes(checkboxes: Array<{ id: string; label: string; checked?: boolean }>): string {
    return `
      <div class="alert-dialog-options">
        ${checkboxes.map(checkbox => `
          <label class="alert-dialog-checkbox">
            <input type="checkbox" id="${checkbox.id}" ${checkbox.checked ? 'checked' : ''}> 
            ${checkbox.label}
          </label>
        `).join('')}
      </div>
    `;
  }

  private limitToastStack(container: HTMLElement): void {
    let maxToasts = 1;
    
    if (this.notificationMode === 'three') {
      maxToasts = 3;
    } else if (this.notificationMode === 'all') {
      // Calculate how many fit in viewport
      const taskbarHeight = 60; // px
      const margin = 20; // px
      const toastHeight = 80; // px
      const available = window.innerHeight - taskbarHeight - margin - 32; // 32px top
      maxToasts = Math.floor(available / (toastHeight + 14));
    }

    // Remove excess toasts (from bottom)
    while (container.children.length > maxToasts) {
      container.lastChild?.remove();
    }
  }

  private updateToastStackPositions(): void {
    const container = document.getElementById('os-toast-container');
    if (!container) return;

    const margin = 14; // px
    Array.from(container.children).forEach((toast, idx) => {
      const element = toast as HTMLElement;
      element.style.position = 'absolute';
      element.style.right = '0';
      element.style.left = 'auto';
      element.style.top = `${idx * (80 + margin)}px`; // 80px height + margin
    });
  }

  // Public methods for updating settings
  setNotificationMode(mode: NotificationMode): void {
    this.notificationMode = mode;
  }

  setNotificationsMuted(muted: boolean): void {
    this.isNotificationsMuted = muted;
  }
}

export const alertService = AlertService.getInstance(); 