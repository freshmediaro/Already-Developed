// Email Application - Extracted from original app.js
import { BaseApp, type AppContext } from './BaseApp';
import { eventSystem } from '../Core/EventSystem';
import { apiService } from '../Tenant/ApiService';
import type { Window, App } from '../Core/Types';

interface EmailItem {
  id: number;
  sender: string;
  avatar: string;
  subject: string;
  preview: string;
  date: string;
  bigdate: string;
  size: string;
  unread: boolean;
  icon: string;
  color: string;
  day: string;
  content: string;
  attachedfile?: string;
}

interface EmailGrouped {
  [day: string]: EmailItem[];
}

export class EmailApp extends BaseApp {
  public readonly id = 'email';
  public readonly name = 'Email';
  public readonly iconClass = 'fa-envelope';
  public readonly iconBgClass = 'blue-icon';

  private emails: EmailItem[] = [];
  private groupedEmails: EmailGrouped = {};
  private selectedEmailId: number | null = null;
  private emailListSection?: HTMLElement;
  private emailList?: HTMLElement;
  private emailContentSection?: HTMLElement;
  private isMobileView: boolean = false;

  constructor() {
    const appInfo: App = {
      id: 'email',
      name: 'Email',
      icon: 'fas fa-envelope',
      iconType: 'fontawesome',
      iconBackground: 'blue-icon',
      component: 'EmailApp',
      category: 'communication',
      permissions: ['read', 'write'],
      installed: true,
      system: false,
      teamScoped: true,
      version: '1.0.0',
      description: 'Email client for managing and composing emails',
    };
    super('email', appInfo);
  }

  async onMount(context: AppContext): Promise<void> {
    this.initializeElements(context.windowElement);
    this.loadEmailData();
    this.setupSidebar();
    this.renderEmailList();
    this.checkMobileView();
    this.setupResizeObserver();
  }

  async onUnmount(): Promise<void> {
    // Cleanup any modal panels or listeners
    this.cleanupMorePanels();
  }

  async onFocus(): Promise<void> {
    // Refresh email list if needed
    await this.refreshEmails();
  }

  async onBlur(): Promise<void> {
    // Close any open panels
    this.cleanupMorePanels();
  }

  private initializeElements(windowElement: HTMLElement): void {
    this.emailListSection = windowElement.querySelector('.email-list-section') as HTMLElement;
    this.emailList = windowElement.querySelector('.email-list') as HTMLElement;
    this.emailContentSection = windowElement.querySelector('.email-content-section .email-content') as HTMLElement;

    if (!this.emailListSection || !this.emailList) {
      console.error('Email App: Required elements not found');
    }
  }

  private loadEmailData(): void {
    // Sample email data - in real implementation, this would come from API
    this.emails = [
      {
        id: 1,
        sender: 'Andrei Antoniade',
        avatar: 'fa-fire',
        subject: 'Subiectul si predicat iti explic ce este de explicat',
        preview: 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.',
        date: '17:30',
        bigdate: '04:30 AM • Sunday, April 11, 2025',
        size: '6.1 KB',
        unread: true,
        icon: 'fa-fire',
        color: '#FF6B6B',
        day: 'Today',
        content: `<b>Hello dear testmail</b><br><br>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.<br><br>It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged.`
      },
      {
        id: 2,
        sender: 'Andrei Antoniade',
        avatar: 'fa-apple',
        subject: 'Inca un subiect despre care nu vreau sa vorbesc cu nimeni si nim...',
        preview: 'Another preview text for this email.',
        date: '22:41',
        bigdate: '04:30 AM • Sunday, April 11, 2025',
        size: '6.1 MB',
        unread: true,
        icon: 'fa-apple',
        color: '#fff',
        day: 'Today',
        content: 'This is the content of the second email.'
      },
      {
        id: 3,
        sender: 'Andrei Antoniade',
        avatar: 'fa-facebook',
        subject: 'Inca un subiect despre care nu a putut sa vorbesc cu nimeni s.a fie afis...',
        preview: 'Preview for facebook email.',
        date: '21 jul',
        bigdate: '04:30 AM • Sunday, April 11, 2025',
        size: '7.5 MB',
        unread: false,
        icon: 'fa-facebook',
        color: '#1877f3',
        day: 'Today',
        content: 'This is the content of the facebook email.'
      },
      {
        id: 4,
        sender: 'Andrei Antoniade',
        avatar: 'fa-dribbble',
        subject: 'Subiectul si predicat iti explic ce este de explicat',
        preview: 'Dribbble preview.',
        date: '23 April',
        bigdate: '04:30 AM • Sunday, April 11, 2025',
        size: '12 MB',
        unread: false,
        icon: 'fa-dribbble',
        color: '#ea4c89',
        day: 'Today',
        content: 'This is the content of the dribbble email.'
      },
      // Yesterday group
      {
        id: 6,
        sender: 'Andrei Antoniade',
        avatar: 'fa-dribbble',
        subject: 'Subiectul si predicat iti explic ce este de explicat',
        preview: 'Yesterday preview.',
        date: '17:30',
        bigdate: '04:30 AM • Sunday, April 11, 2025',
        size: '6.1 KB',
        unread: false,
        icon: 'fa-dribbble',
        color: '#ea4c89',
        day: 'Yesterday',
        content: 'This is the content of the yesterday email.',
        attachedfile: '<i class="fas fa-paperclip"></i>'
      },
      {
        id: 7,
        sender: 'Andrei Antoniade',
        avatar: 'fa-arrow-down',
        subject: 'Subiectul si predicat iti explic ce este de explicat',
        preview: 'Yesterday preview 2.',
        date: '22:41',
        bigdate: '04:30 AM • Sunday, April 11, 2025',
        size: '6.1 KB',
        unread: false,
        icon: 'fa-arrow-down',
        color: '#8A8A9E',
        day: 'Yesterday',
        content: 'This is the content of the yesterday email 2.'
      }
    ];

    // Group emails by day
    this.groupedEmails = this.emails.reduce((acc, email) => {
      if (!acc[email.day]) acc[email.day] = [];
      acc[email.day].push(email);
      return acc;
    }, {} as EmailGrouped);
  }

  private setupSidebar(): void {
    if (!this.windowElement) return;

    // Ensure sidebar toggle and overlay exist
    this.ensureSidebarElements();

    // Update sidebar for this window if global function exists
    if (typeof (window as any).updateSidebarForWindow === 'function') {
      (window as any).updateSidebarForWindow(this.windowElement);
    }

    if (typeof (window as any).attachSidebarResizeObserver === 'function') {
      (window as any).attachSidebarResizeObserver(this.windowElement);
    }
  }

  private ensureSidebarElements(): void {
    // Implementation for ensuring sidebar elements exist
    // This would be similar to the original ensureSidebarElements function
  }

  private renderEmailList(selectedId?: number): void {
    if (!this.emailList) return;

    this.emailList.innerHTML = '';
    
    Object.keys(this.groupedEmails).forEach(day => {
      // Day header
      const dayHeader = document.createElement('li');
      dayHeader.className = 'email-list-day-header';
      const messageCount = this.groupedEmails[day].length;
      dayHeader.textContent = `${day}${day === 'Today' ? ` - ${messageCount} messages` : day === 'Yesterday' ? ` - ${messageCount} messages` : ''}`;
      this.emailList!.appendChild(dayHeader);

      // Email items
      this.groupedEmails[day].forEach(email => {
        const li = document.createElement('li');
        li.className = `email-list-item${email.id === selectedId ? ' selected' : ''}${email.unread ? ' unread' : ''}`;
        
        li.innerHTML = `
          <div class="email-list-avatar-meta">
            <div class="email-list-avatar" style="color:${email.color};"><i class="fab ${email.icon}"></i></div>
            <span class="email-list-size">${email.size}</span>
          </div>
          <div class="email-list-main" style="flex:1; min-width:0;">
            <div class="email-list-sender-meta">
              <div class="email-list-sender">${email.sender}</div>
              <div class="email-list-meta">
                ${email.attachedfile ? `<span class="email-list-attachedfile">${email.attachedfile}</span>` : ''}
                <span class="email-list-date" style="display:block; color:#8A8A9E; font-size:13px;">${email.date}</span>
              </div>
            </div>
            <div class="email-list-subject">${email.subject}</div>
            <div class="email-list-preview">${email.preview}</div>
          </div>
        `;

        // Single click handler
        li.onclick = () => {
          console.log('Email clicked:', email.id, email.subject);
          
          if (this.isMobileView) {
            this.showEmailContentPanel();
          }
          
          this.selectedEmailId = email.id;
          this.renderEmailList(email.id);
          this.renderEmailContent(email);
          
          eventSystem.emit('email:selected', { emailId: email.id, email });
        };

        // Double click handler - open in new window
        li.ondblclick = () => {
          this.openEmailInNewWindow(email);
        };

        this.emailList!.appendChild(li);
      });
    });
  }

  private renderEmailContent(email: EmailItem): void {
    const section = this.windowElement?.querySelector('.email-content-section');
    if (!section) {
      console.log('No .email-content-section found!');
      return;
    }

    // Clear existing content
    section.innerHTML = '';

    // Create toolbar
    const toolbar = this.createEmailToolbar();
    section.appendChild(toolbar);

    // Create content
    const content = document.createElement('div');
    content.className = 'email-content';
    content.innerHTML = `
      <div class="email-content-header">
        <div style="display:flex; align-items:center; gap:24px;">
          <div class="email-content-avatar"><i class="fab ${email.icon}"></i></div>
          <div>
            <div class="email-content-sender">${email.sender}</div>
            <div class="email-content-from">From: <span class='email-content-from-address'>info@yourdomain.ro</span></div>
            <div class="email-content-to">To: <span class='email-content-to-address'>info@mydomain.ro</span></div>
          </div>
        </div>
        <div class="opened-email-meta">
          <div class="opened-email-meta-date"><div>${email.bigdate}</div> <i class="fa-regular fa-star"></i></div>
          <div class="opened-email-meta-size">${email.size}</div>
        </div>
      </div>
      <div class="email-content-subject">${email.subject}</div>
      <div class="email-content-body">${email.content}</div>
      <div class="email-footer-controls">
        <button class="toolbar-button" title="Reply"><i class="fas fa-reply"></i><span>Reply</span></button>
        <button class="toolbar-button" title="Reply All"><i class="fas fa-reply-all"></i><span>Reply all</span></button>
        <button class="toolbar-button" title="Forward"><i class="fas fa-share"></i><span>Forward</span></button>
      </div>
    `;
    section.appendChild(content);

    // Setup toolbar handlers
    this.setupEmailToolbarHandlers(section);
  }

  private createEmailToolbar(): HTMLElement {
    const toolbar = document.createElement('div');
    toolbar.className = 'window-toolbar';

    if (this.isMobileView) {
      toolbar.innerHTML = `
        <div class="toolbar-buttons-left">
          <button class="toolbar-button mailback-btn" title="Back"><i class="fas fa-arrow-left"></i> <span>Back</span></button>
          <button class="toolbar-button" title="Reply"><i class="fas fa-reply"></i><span>Reply</span></button>
          <button class="toolbar-button" title="Reply All"><i class="fas fa-reply-all"></i><span>Reply all</span></button>
          <button class="toolbar-button" title="Forward"><i class="fas fa-share"></i><span>Forward</span></button>
        </div>
        <div class="toolbar-buttons-right">
          <button class="toolbar-button" title="Delete"><i class="fas fa-trash"></i> <span>Delete</span></button>
          <button class="toolbar-button mailmore-btn" title="More"><i class="fas fa-ellipsis-h"></i></button>
        </div>
      `;
    } else {
      toolbar.innerHTML = `
        <div class="toolbar-buttons-left">
          <button class="toolbar-button mailback-btn" title="Back"><i class="fas fa-arrow-left"></i> <span>Back</span></button>
          <button class="toolbar-button" title="Reply"><i class="fas fa-reply"></i><span>Reply</span></button>
          <button class="toolbar-button" title="Reply All"><i class="fas fa-reply-all"></i><span>Reply all</span></button>
          <button class="toolbar-button" title="Forward"><i class="fas fa-share"></i><span>Forward</span></button>
        </div>
        <div class="toolbar-buttons-right">
          <button class="toolbar-button" title="Move"><i class="fas fa-folder-open"></i> <span>Move</span></button>
          <button class="toolbar-button" title="Spam"><i class="fas fa-shield-virus"></i> <span>Spam</span></button>
          <button class="toolbar-button" title="Delete"><i class="fas fa-trash"></i> <span>Delete</span></button>
          <button class="toolbar-button mailmore-btn" title="More"><i class="fas fa-ellipsis-h"></i></button>
        </div>
      `;
    }

    return toolbar;
  }

  private setupEmailToolbarHandlers(section: Element): void {
    // Back button handler
    const mailbackBtn = section.querySelector('.mailback-btn');
    if (mailbackBtn) {
      mailbackBtn.addEventListener('click', () => {
        this.showEmailListPanel();
      });
    }

    // More button handler
    const mailmoreBtn = section.querySelector('.mailmore-btn');
    if (mailmoreBtn) {
      mailmoreBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        this.toggleMorePanel(mailmoreBtn as HTMLElement);
      });
    }

    // Reply buttons
    const replyBtns = section.querySelectorAll('[title="Reply"], [title="Reply All"], [title="Forward"]');
    replyBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        const action = btn.getAttribute('title')?.toLowerCase().replace(' ', '-');
        this.handleEmailAction(action || 'reply');
      });
    });

    // Action buttons
    const actionBtns = section.querySelectorAll('[title="Move"], [title="Spam"], [title="Delete"]');
    actionBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        const action = btn.getAttribute('title')?.toLowerCase();
        this.handleEmailAction(action || 'unknown');
      });
    });
  }

  private toggleMorePanel(button: HTMLElement): void {
    // Check if panel already exists
    const existingPanel = document.querySelector('.email-more-panel');
    if (existingPanel) {
      this.hideMorePanel(existingPanel as HTMLElement);
      return;
    }

    // Create new panel
    const panel = this.createMorePanel();
    document.body.appendChild(panel);

    // Position panel
    const rect = button.getBoundingClientRect();
    panel.style.right = (window.innerWidth - rect.right) + 'px';
    panel.style.top = (rect.bottom + 8) + 'px';

    // Add animation
    panel.classList.add('context-menu-anim-pop');

    // Setup click handlers
    this.setupMorePanelHandlers(panel);

    // Hide on outside click
    setTimeout(() => {
      document.addEventListener('mousedown', (e) => {
        if (!panel.contains(e.target as Node) && e.target !== button) {
          this.hideMorePanel(panel);
        }
      }, { once: true });
    }, 0);
  }

  private createMorePanel(): HTMLElement {
    const panel = document.createElement('div');
    panel.className = 'email-more-panel context-menu';
    panel.style.cssText = `
      position: absolute;
      z-index: 10000;
      padding: 4px 0;
      min-width: 200px;
      font-size: 14px;
      animation-duration: 0.15s;
      transform-origin: top right;
    `;

    if (this.isMobileView) {
      const mobileOptions = [
        { icon: 'fa-folder-open', label: 'Move' },
        { icon: 'fa-shield-virus', label: 'Spam' }
      ];

      panel.innerHTML = mobileOptions.map(item => 
        `<div class="context-menu-item"><i class="fas ${item.icon}"></i><span>${item.label}</span></div>`
      ).join('');
    } else {
      const desktopOptions = [
        { icon: 'fa-tag', label: 'Add a tag' },
        { icon: 'fa-download', label: 'Download message' },
        { icon: 'fa-file-archive', label: 'Download message (zip)' },
        { icon: 'fa-code', label: 'View Message Source' },
        { icon: 'fa-file-code', label: 'View raw message' },
        { icon: 'fa-print', label: 'Print' },
        { type: 'separator' },
        { icon: 'fa-tasks', label: 'Convert to Task' },
        { type: 'separator' },
        { icon: 'fa-address-book', label: 'Open Contact' }
      ];

      panel.innerHTML = desktopOptions.map(item => {
        if (item.type === 'separator') {
          return '<div class="context-menu-separator"></div>';
        }
        return `<div class="context-menu-item"><i class="fas ${item.icon}"></i><span>${item.label}</span></div>`;
      }).join('');
    }

    return panel;
  }

  private setupMorePanelHandlers(panel: HTMLElement): void {
    const menuItems = panel.querySelectorAll('.context-menu-item');
    menuItems.forEach(item => {
      item.addEventListener('click', () => {
        const label = item.textContent?.trim() || '';
        console.log('Clicked option:', label);
        this.handleEmailAction(label.toLowerCase().replace(/\s+/g, '-'));
        this.hideMorePanel(panel);
      });
    });
  }

  private hideMorePanel(panel: HTMLElement): void {
    panel.classList.add('context-menu-anim-close');
    panel.addEventListener('animationend', () => {
      if (panel.parentNode) {
        panel.parentNode.removeChild(panel);
      }
    }, { once: true });
  }

  private openEmailInNewWindow(email: EmailItem): void {
    // Generate unique window ID
    let emailContentWindowCount = 1;
    const openWindows = (window as any).openWindows || {};
    while (openWindows[`email-content-window-${emailContentWindowCount}`]) {
      emailContentWindowCount++;
    }
    const windowId = `email-content-window-${emailContentWindowCount}`;

    // Emit event to create new window
    eventSystem.emit('window:create', {
      windowId,
      templateId: 'email-content-window',
      title: email.subject || email.sender || 'Email',
      iconClass: 'fa-envelope-open',
      iconBgClass: 'blue-icon',
      content: this.generateEmailWindowContent(email)
    });
  }

  private generateEmailWindowContent(email: EmailItem): string {
    return `
      <section class="email-content-section">
        <div class="window-toolbar">
          <div class="toolbar-buttons-left">
            <button class="toolbar-button" title="Reply"><i class="fas fa-reply"></i><span>Reply</span></button>
            <button class="toolbar-button" title="Reply All"><i class="fas fa-reply-all"></i><span>Reply all</span></button>
            <button class="toolbar-button" title="Forward"><i class="fas fa-share"></i><span>Forward</span></button>
          </div>
          <div class="toolbar-buttons-right">
            <button class="toolbar-button" title="Move"><i class="fas fa-folder-open"></i> <span>Move</span></button>
            <button class="toolbar-button" title="Spam"><i class="fas fa-shield-virus"></i> <span>Spam</span></button>
            <button class="toolbar-button" title="Delete"><i class="fas fa-trash"></i> <span>Delete</span></button>
            <button class="toolbar-button mailmore-btn" title="More"><i class="fas fa-ellipsis-h"></i></button>
          </div>
        </div>
        <div class="email-content">
          <div class="email-content-header">
            <div style="display:flex; align-items:center; gap:24px;">
              <div class="email-content-avatar"><i class="fab ${email.icon}"></i></div>
              <div>
                <div class="email-content-sender">${email.sender}</div>
                <div class="email-content-from">From: <span class='email-content-from-address'>info@yourdomain.ro</span></div>
                <div class="email-content-to">To: <span class='email-content-to-address'>info@mydomain.ro</span></div>
              </div>
            </div>
            <div class="opened-email-meta">
              <div class="opened-email-meta-date"><div>${email.bigdate}</div> <i class="fa-regular fa-star"></i></div>
              <div class="opened-email-meta-size">${email.size}</div>
            </div>
          </div>
          <div class="email-content-subject">${email.subject}</div>
          <div class="email-content-body">${email.content}</div>
        </div>
      </section>
    `;
  }

  private checkMobileView(): void {
    // Check if the email app should be in mobile view based on window size
    if (this.windowElement) {
      const width = this.windowElement.clientWidth;
      this.isMobileView = width < 768; // Adjust breakpoint as needed
    }
  }

  private setupResizeObserver(): void {
    if (!this.windowElement) return;

    const resizeObserver = new ResizeObserver(() => {
      this.checkMobileView();
    });

    resizeObserver.observe(this.windowElement);
  }

  private showEmailListPanel(): void {
    if (this.isMobileView && this.emailListSection) {
      this.emailListSection.style.display = 'block';
      const contentSection = this.windowElement?.querySelector('.email-content-section');
      if (contentSection) {
        (contentSection as HTMLElement).style.display = 'none';
      }
    }
  }

  private showEmailContentPanel(): void {
    if (this.isMobileView && this.emailListSection) {
      this.emailListSection.style.display = 'none';
      const contentSection = this.windowElement?.querySelector('.email-content-section');
      if (contentSection) {
        (contentSection as HTMLElement).style.display = 'block';
      }
    }
  }

  private handleEmailAction(action: string): void {
    console.log('Email action:', action);
    
    switch (action) {
      case 'reply':
        this.composeReply();
        break;
      case 'reply-all':
        this.composeReplyAll();
        break;
      case 'forward':
        this.composeForward();
        break;
      case 'delete':
        this.deleteEmail();
        break;
      case 'move':
        this.moveEmail();
        break;
      case 'spam':
        this.markAsSpam();
        break;
      default:
        eventSystem.emit('email:action', { action, emailId: this.selectedEmailId });
    }
  }

  private composeReply(): void {
    eventSystem.emit('email:compose', { 
      type: 'reply', 
      emailId: this.selectedEmailId 
    });
  }

  private composeReplyAll(): void {
    eventSystem.emit('email:compose', { 
      type: 'reply-all', 
      emailId: this.selectedEmailId 
    });
  }

  private composeForward(): void {
    eventSystem.emit('email:compose', { 
      type: 'forward', 
      emailId: this.selectedEmailId 
    });
  }

  private deleteEmail(): void {
    if (!this.selectedEmailId) return;

    // Implementation for deleting email
    eventSystem.emit('email:delete', { emailId: this.selectedEmailId });
  }

  private moveEmail(): void {
    if (!this.selectedEmailId) return;

    // Implementation for moving email
    eventSystem.emit('email:move', { emailId: this.selectedEmailId });
  }

  private markAsSpam(): void {
    if (!this.selectedEmailId) return;

    // Implementation for marking as spam
    eventSystem.emit('email:spam', { emailId: this.selectedEmailId });
  }

  private async refreshEmails(): Promise<void> {
    try {
      // In real implementation, fetch from API
      // const emails = await apiService.get<EmailItem[]>('/api/emails');
      // this.emails = emails;
      // this.groupEmails();
      this.renderEmailList(this.selectedEmailId || undefined);
    } catch (error) {
      console.error('Failed to refresh emails:', error);
    }
  }

  private cleanupMorePanels(): void {
    const panels = document.querySelectorAll('.email-more-panel');
    panels.forEach(panel => {
      if (panel.parentNode) {
        panel.parentNode.removeChild(panel);
      }
    });
  }

  private windowElement?: HTMLElement;
}

export default EmailApp; 