import { eventSystem } from './EventSystem';

interface ContextMenuItem {
  action?: string;
  text?: string;
  icon?: string;
  submenu?: ContextMenuItem[];
  separator?: boolean;
  disabled?: boolean;
}

interface ContextMenuOptions {
  x: number;
  y: number;
  items: ContextMenuItem[];
  target?: HTMLElement;
}

class ContextMenuService {
  private contextMenuElement: HTMLElement | null = null;
  private currentTarget: HTMLElement | null = null;
  private isVisible = false;

  constructor() {
    this.init();
    this.setupGlobalListeners();
  }

  private init() {
    // Create context menu element if it doesn't exist
    this.contextMenuElement = document.getElementById('context-menu');
    if (!this.contextMenuElement) {
      this.contextMenuElement = document.createElement('div');
      this.contextMenuElement.id = 'context-menu';
      this.contextMenuElement.className = 'context-menu hidden';
      document.body.appendChild(this.contextMenuElement);
    }
  }

  private setupGlobalListeners() {
    // Disable default context menu globally
    document.addEventListener('contextmenu', (e) => {
      e.preventDefault();
    });

    // Hide context menu on click outside
    document.addEventListener('mousedown', (e) => {
      if (this.contextMenuElement && !this.contextMenuElement.contains(e.target as Node)) {
        this.hide();
      }
    });

    // Hide on escape key
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        this.hide();
      }
    });
  }

  show(options: ContextMenuOptions) {
    if (!this.contextMenuElement) return;

    this.currentTarget = options.target || null;
    this.isVisible = true;

    // Clear existing content
    this.contextMenuElement.innerHTML = '';

    // Populate menu items
    options.items.forEach(item => {
      if (item.separator) {
        const separator = document.createElement('div');
        separator.className = 'context-menu-separator';
        this.contextMenuElement!.appendChild(separator);
      } else {
        const menuItem = this.createMenuItem(item);
        this.contextMenuElement!.appendChild(menuItem);
      }
    });

    // Position menu
    this.positionMenu(options.x, options.y);

    // Show menu
    this.contextMenuElement.classList.remove('hidden');
    this.contextMenuElement.classList.add('context-menu-anim-pop');

    // Remove animation class after animation
    setTimeout(() => {
      if (this.contextMenuElement) {
        this.contextMenuElement.classList.remove('context-menu-anim-pop');
      }
    }, 150);
  }

  private createMenuItem(item: ContextMenuItem): HTMLElement {
    const menuItem = document.createElement('div');
    menuItem.className = `context-menu-item${item.disabled ? ' disabled' : ''}`;
    
    let content = '';
    if (item.icon) {
      content += `<i class="${item.icon}"></i>`;
    }
    content += `<span>${item.text}</span>`;
    
    if (item.submenu) {
      content += `<i class="fas fa-chevron-right submenu-arrow"></i>`;
    }
    
    menuItem.innerHTML = content;

    if (!item.disabled) {
      menuItem.addEventListener('click', (e) => {
        e.stopPropagation();
        if (item.action) {
          this.executeAction(item.action);
        }
        this.hide();
      });
    }

    // Handle submenu
    if (item.submenu) {
      this.setupSubmenu(menuItem, item.submenu);
    }

    return menuItem;
  }

  private setupSubmenu(parentItem: HTMLElement, submenuItems: ContextMenuItem[]) {
    const submenu = document.createElement('div');
    submenu.className = 'context-submenu hidden';
    
    submenuItems.forEach(item => {
      if (item.separator) {
        const separator = document.createElement('div');
        separator.className = 'context-menu-separator';
        submenu.appendChild(separator);
      } else {
        const menuItem = this.createMenuItem(item);
        submenu.appendChild(menuItem);
      }
    });

    parentItem.appendChild(submenu);

    // Show/hide submenu on hover
    parentItem.addEventListener('mouseenter', () => {
      submenu.classList.remove('hidden');
      this.positionSubmenu(parentItem, submenu);
    });

    parentItem.addEventListener('mouseleave', () => {
      setTimeout(() => {
        if (!submenu.matches(':hover') && !parentItem.matches(':hover')) {
          submenu.classList.add('hidden');
        }
      }, 100);
    });
  }

  private positionMenu(x: number, y: number) {
    if (!this.contextMenuElement) return;

    const menu = this.contextMenuElement;
    const menuRect = menu.getBoundingClientRect();
    const windowWidth = window.innerWidth;
    const windowHeight = window.innerHeight;

    // Adjust position to keep menu in viewport
    let adjustedX = x;
    let adjustedY = y;

    if (x + menuRect.width > windowWidth) {
      adjustedX = windowWidth - menuRect.width - 5;
    }

    if (y + menuRect.height > windowHeight) {
      adjustedY = windowHeight - menuRect.height - 5;
    }

    menu.style.left = `${Math.max(0, adjustedX)}px`;
    menu.style.top = `${Math.max(0, adjustedY)}px`;
  }

  private positionSubmenu(parentItem: HTMLElement, submenu: HTMLElement) {
    const parentRect = parentItem.getBoundingClientRect();
    const submenuRect = submenu.getBoundingClientRect();
    const windowWidth = window.innerWidth;
    const windowHeight = window.innerHeight;

    // Position submenu to the right of parent by default
    let x = parentRect.right;
    let y = parentRect.top;

    // If submenu would go off right edge, position to the left
    if (x + submenuRect.width > windowWidth) {
      x = parentRect.left - submenuRect.width;
    }

    // If submenu would go off bottom edge, adjust upward
    if (y + submenuRect.height > windowHeight) {
      y = windowHeight - submenuRect.height - 5;
    }

    submenu.style.left = `${Math.max(0, x)}px`;
    submenu.style.top = `${Math.max(0, y)}px`;
  }

  hide() {
    if (!this.contextMenuElement || !this.isVisible) return;

    this.isVisible = false;
    this.currentTarget = null;

    this.contextMenuElement.classList.add('context-menu-anim-close');
    
    setTimeout(() => {
      if (this.contextMenuElement) {
        this.contextMenuElement.classList.add('hidden');
        this.contextMenuElement.classList.remove('context-menu-anim-close');
      }
    }, 150);
  }

  private executeAction(action: string) {
    // Emit event for the action
    eventSystem.emit('context-menu:action', {
      action,
      target: this.currentTarget
    });

    // Handle built-in actions
    this.handleBuiltInActions(action);
  }

  private handleBuiltInActions(action: string) {
    switch (action) {
      case 'copy':
        this.handleCopy();
        break;
      case 'paste':
        this.handlePaste();
        break;
      case 'cut':
        this.handleCut();
        break;
      case 'select-all':
        this.handleSelectAll();
        break;
      case 'refresh':
        window.location.reload();
        break;
      case 'view-source':
        window.open('view-source:' + window.location.href);
        break;
      default:
        // Custom actions handled by event listeners
        break;
    }
  }

  private handleCopy() {
    const selection = window.getSelection();
    if (selection && selection.toString()) {
      try {
        navigator.clipboard.writeText(selection.toString());
      } catch (err) {
        // Fallback for older browsers
        document.execCommand('copy');
      }
    }
  }

  private handlePaste() {
    if (this.currentTarget && (
      this.currentTarget.tagName === 'INPUT' || 
      this.currentTarget.tagName === 'TEXTAREA' ||
      this.currentTarget.contentEditable === 'true'
    )) {
      try {
        navigator.clipboard.readText().then(text => {
          if (this.currentTarget) {
            if (this.currentTarget.tagName === 'INPUT' || this.currentTarget.tagName === 'TEXTAREA') {
              const input = this.currentTarget as HTMLInputElement | HTMLTextAreaElement;
              const start = input.selectionStart || 0;
              const end = input.selectionEnd || 0;
              const currentValue = input.value;
              input.value = currentValue.slice(0, start) + text + currentValue.slice(end);
              input.setSelectionRange(start + text.length, start + text.length);
            } else if (this.currentTarget.contentEditable === 'true') {
              document.execCommand('insertText', false, text);
            }
          }
        });
      } catch (err) {
        // Fallback for older browsers
        document.execCommand('paste');
      }
    }
  }

  private handleCut() {
    const selection = window.getSelection();
    if (selection && selection.toString()) {
      try {
        navigator.clipboard.writeText(selection.toString());
        selection.deleteFromDocument();
      } catch (err) {
        document.execCommand('cut');
      }
    }
  }

  private handleSelectAll() {
    if (this.currentTarget && (
      this.currentTarget.tagName === 'INPUT' || 
      this.currentTarget.tagName === 'TEXTAREA'
    )) {
      (this.currentTarget as HTMLInputElement | HTMLTextAreaElement).select();
    } else {
      document.execCommand('selectAll');
    }
  }

  // Public methods for different context menu types
  showDesktopContextMenu(x: number, y: number) {
    const items: ContextMenuItem[] = [
      { action: 'refresh', text: 'Refresh', icon: 'fas fa-sync-alt' },
      { separator: true },
      { action: 'new', text: 'New', icon: 'fas fa-plus', submenu: [
        { action: 'new-folder', text: 'Folder', icon: 'fas fa-folder' },
        { action: 'new-file', text: 'Text Document', icon: 'fas fa-file-alt' }
      ]},
      { separator: true },
      { action: 'paste', text: 'Paste', icon: 'fas fa-paste' },
      { separator: true },
      { action: 'display-settings', text: 'Display settings', icon: 'fas fa-desktop' },
      { action: 'personalize', text: 'Personalize', icon: 'fas fa-paint-brush' }
    ];

    this.show({ x, y, items });
  }

  showTaskbarContextMenu(x: number, y: number) {
    const items: ContextMenuItem[] = [
      { action: 'search-toggle', text: 'Search', icon: 'fas fa-search' },
      { action: 'task-view', text: 'Task view', icon: 'fas fa-th-large' },
      { action: 'widgets-toggle', text: 'Widgets', icon: 'fas fa-th' },
      { separator: true },
      { action: 'lock-taskbar', text: 'Lock the taskbar', icon: 'fas fa-lock' },
      { separator: true },
      { action: 'taskbar-settings', text: 'Taskbar settings', icon: 'fas fa-cog' }
    ];

    this.show({ x, y, items });
  }

  showTextContextMenu(x: number, y: number, target: HTMLElement) {
    const selection = window.getSelection();
    const hasSelection = selection && selection.toString().length > 0;
    
    const items: ContextMenuItem[] = [
      { action: 'cut', text: 'Cut', icon: 'fas fa-cut', disabled: !hasSelection },
      { action: 'copy', text: 'Copy', icon: 'fas fa-copy', disabled: !hasSelection },
      { action: 'paste', text: 'Paste', icon: 'fas fa-paste' },
      { separator: true },
      { action: 'select-all', text: 'Select all', icon: 'fas fa-check-square' }
    ];

    this.show({ x, y, items, target });
  }

  getCurrentTarget(): HTMLElement | null {
    return this.currentTarget;
  }

  isMenuVisible(): boolean {
    return this.isVisible;
  }
}

export const contextMenuService = new ContextMenuService();

// Make it globally available for compatibility with existing code
(window as any).populateContextMenu = (menuItems: any[], x: number, y: number) => {
  const items: ContextMenuItem[] = menuItems.map(item => ({
    action: item.action,
    text: item.text,
    icon: item.icon,
    separator: item.separator,
    disabled: item.disabled,
    submenu: item.submenu
  }));
  
  contextMenuService.show({ x, y, items });
};

(window as any).executeContextMenuAction = (action: string) => {
  eventSystem.emit('context-menu:action', { action });
};

(window as any).hideContextMenu = () => {
  contextMenuService.hide();
}; 