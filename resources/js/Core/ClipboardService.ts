import { eventSystem } from './EventSystem';

interface ClipboardData {
  text: string;
  type: 'text' | 'html' | 'file';
  source?: HTMLElement;
  timestamp: number;
}

class ClipboardService {
  private clipboardData: ClipboardData | null = null;
  private selectedText: string = '';
  private lastTarget: HTMLElement | null = null;

  constructor() {
    this.setupGlobalListeners();
  }

  private setupGlobalListeners() {
    // Handle text selection globally
    document.addEventListener('selectionchange', () => {
      this.handleSelectionChange();
    });

    // Handle keyboard shortcuts
    document.addEventListener('keydown', (e) => {
      if (e.ctrlKey || e.metaKey) {
        switch (e.key.toLowerCase()) {
          case 'c':
            e.preventDefault();
            this.copy();
            break;
          case 'x':
            e.preventDefault();
            this.cut();
            break;
          case 'v':
            e.preventDefault();
            this.paste();
            break;
          case 'a':
            e.preventDefault();
            this.selectAll();
            break;
        }
      }
    });

    // Track focused elements for better paste targeting
    document.addEventListener('focusin', (e) => {
      this.lastTarget = e.target as HTMLElement;
    });
  }

  private handleSelectionChange() {
    const selection = window.getSelection();
    if (selection && selection.toString().trim()) {
      this.selectedText = selection.toString();
      eventSystem.emit('clipboard:selection-changed', {
        text: this.selectedText,
        range: selection.getRangeAt(0)
      });
    } else {
      this.selectedText = '';
    }
  }

  async copy(target?: HTMLElement): Promise<boolean> {
    try {
      let textToCopy = '';
      const currentTarget = target || this.lastTarget;

      // Handle different input types
      if (currentTarget && (
        currentTarget.tagName === 'INPUT' || 
        currentTarget.tagName === 'TEXTAREA'
      )) {
        const input = currentTarget as HTMLInputElement | HTMLTextAreaElement;
        const start = input.selectionStart || 0;
        const end = input.selectionEnd || 0;
        textToCopy = input.value.substring(start, end);
      } else if (currentTarget && currentTarget.contentEditable === 'true') {
        // Handle contentEditable elements
        const selection = window.getSelection();
        if (selection && selection.toString()) {
          textToCopy = selection.toString();
        }
      } else {
        // Handle regular text selection
        const selection = window.getSelection();
        if (selection && selection.toString()) {
          textToCopy = selection.toString();
        }
      }

      if (textToCopy) {
        // Store in internal clipboard
        this.clipboardData = {
          text: textToCopy,
          type: 'text',
          source: currentTarget || undefined,
          timestamp: Date.now()
        };

        // Try to use system clipboard
        try {
          await navigator.clipboard.writeText(textToCopy);
        } catch (err) {
          // Fallback for older browsers
          this.fallbackCopy(textToCopy);
        }

        eventSystem.emit('clipboard:copied', { text: textToCopy });
        this.showClipboardNotification('Copied to clipboard');
        return true;
      }
    } catch (err) {
      console.warn('Copy failed:', err);
      return false;
    }
    return false;
  }

  async cut(target?: HTMLElement): Promise<boolean> {
    try {
      const currentTarget = target || this.lastTarget;
      let textToCut = '';

      // Handle different input types
      if (currentTarget && (
        currentTarget.tagName === 'INPUT' || 
        currentTarget.tagName === 'TEXTAREA'
      )) {
        const input = currentTarget as HTMLInputElement | HTMLTextAreaElement;
        const start = input.selectionStart || 0;
        const end = input.selectionEnd || 0;
        textToCut = input.value.substring(start, end);
        
        if (textToCut) {
          // Remove the selected text
          const newValue = input.value.substring(0, start) + input.value.substring(end);
          input.value = newValue;
          input.setSelectionRange(start, start);
          
          // Trigger input event
          input.dispatchEvent(new Event('input', { bubbles: true }));
        }
      } else if (currentTarget && currentTarget.contentEditable === 'true') {
        const selection = window.getSelection();
        if (selection && selection.toString()) {
          textToCut = selection.toString();
          selection.deleteFromDocument();
        }
      } else {
        // Handle regular text selection
        const selection = window.getSelection();
        if (selection && selection.toString()) {
          textToCut = selection.toString();
          selection.deleteFromDocument();
        }
      }

      if (textToCut) {
        // Store in internal clipboard
        this.clipboardData = {
          text: textToCut,
          type: 'text',
          source: currentTarget || undefined,
          timestamp: Date.now()
        };

        // Try to use system clipboard
        try {
          await navigator.clipboard.writeText(textToCut);
        } catch (err) {
          this.fallbackCopy(textToCut);
        }

        eventSystem.emit('clipboard:cut', { text: textToCut });
        this.showClipboardNotification('Cut to clipboard');
        return true;
      }
    } catch (err) {
      console.warn('Cut failed:', err);
      return false;
    }
    return false;
  }

  async paste(target?: HTMLElement): Promise<boolean> {
    try {
      const currentTarget = target || this.lastTarget;
      let textToPaste = '';

      // Try to get text from system clipboard first
      try {
        textToPaste = await navigator.clipboard.readText();
      } catch (err) {
        // Fallback to internal clipboard
        if (this.clipboardData) {
          textToPaste = this.clipboardData.text;
        }
      }

      if (textToPaste && currentTarget) {
        // Handle different input types
        if (currentTarget.tagName === 'INPUT' || currentTarget.tagName === 'TEXTAREA') {
          const input = currentTarget as HTMLInputElement | HTMLTextAreaElement;
          const start = input.selectionStart || 0;
          const end = input.selectionEnd || 0;
          const currentValue = input.value;
          
          // Insert text at cursor position
          const newValue = currentValue.slice(0, start) + textToPaste + currentValue.slice(end);
          input.value = newValue;
          
          // Set cursor position after pasted text
          const newCursorPos = start + textToPaste.length;
          input.setSelectionRange(newCursorPos, newCursorPos);
          
          // Trigger input event
          input.dispatchEvent(new Event('input', { bubbles: true }));
        } else if (currentTarget.contentEditable === 'true') {
          // Handle contentEditable elements
          const selection = window.getSelection();
          if (selection && selection.rangeCount > 0) {
            const range = selection.getRangeAt(0);
            range.deleteContents();
            const textNode = document.createTextNode(textToPaste);
            range.insertNode(textNode);
            
            // Move cursor to end of inserted text
            range.setStartAfter(textNode);
            range.setEndAfter(textNode);
            selection.removeAllRanges();
            selection.addRange(range);
          }
        }

        eventSystem.emit('clipboard:pasted', { text: textToPaste });
        this.showClipboardNotification('Pasted from clipboard');
        return true;
      }
    } catch (err) {
      console.warn('Paste failed:', err);
      return false;
    }
    return false;
  }

  selectAll(target?: HTMLElement): boolean {
    try {
      const currentTarget = target || this.lastTarget;

      if (currentTarget && (
        currentTarget.tagName === 'INPUT' || 
        currentTarget.tagName === 'TEXTAREA'
      )) {
        (currentTarget as HTMLInputElement | HTMLTextAreaElement).select();
      } else if (currentTarget && currentTarget.contentEditable === 'true') {
        // Select all content in contentEditable element
        const range = document.createRange();
        range.selectNodeContents(currentTarget);
        const selection = window.getSelection();
        if (selection) {
          selection.removeAllRanges();
          selection.addRange(range);
        }
      } else {
        // Select all content on page
        const selection = window.getSelection();
        if (selection) {
          selection.selectAllChildren(document.body);
        }
      }

      eventSystem.emit('clipboard:select-all', { target: currentTarget });
      return true;
    } catch (err) {
      console.warn('Select all failed:', err);
      return false;
    }
  }

  private fallbackCopy(text: string) {
    // Create a temporary textarea element for copying
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.left = '-9999px';
    textarea.style.opacity = '0';
    
    document.body.appendChild(textarea);
    textarea.select();
    
    try {
      document.execCommand('copy');
    } catch (err) {
      console.warn('Fallback copy failed:', err);
    } finally {
      document.body.removeChild(textarea);
    }
  }

  private showClipboardNotification(message: string) {
    // Create a small notification for clipboard operations
    const notification = document.createElement('div');
    notification.className = 'clipboard-notification';
    notification.textContent = message;
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      background: rgba(0, 0, 0, 0.8);
      color: white;
      padding: 8px 12px;
      border-radius: 4px;
      font-size: 12px;
      z-index: 10000;
      opacity: 0;
      transition: opacity 0.3s ease;
      pointer-events: none;
    `;

    document.body.appendChild(notification);

    // Show animation
    setTimeout(() => {
      notification.style.opacity = '1';
    }, 10);

    // Hide and remove after delay
    setTimeout(() => {
      notification.style.opacity = '0';
      setTimeout(() => {
        if (notification.parentNode) {
          notification.parentNode.removeChild(notification);
        }
      }, 300);
    }, 2000);
  }

  // Public methods for accessing clipboard data
  getClipboardData(): ClipboardData | null {
    return this.clipboardData;
  }

  getSelectedText(): string {
    return this.selectedText;
  }

  hasClipboardData(): boolean {
    return this.clipboardData !== null;
  }

  clearClipboard(): void {
    this.clipboardData = null;
    eventSystem.emit('clipboard:cleared', {});
  }

  // Enhanced text selection utilities
  selectText(element: HTMLElement, start?: number, end?: number): boolean {
    try {
      if (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA') {
        const input = element as HTMLInputElement | HTMLTextAreaElement;
        if (start !== undefined && end !== undefined) {
          input.setSelectionRange(start, end);
        } else {
          input.select();
        }
        return true;
      } else {
        const range = document.createRange();
        if (start !== undefined && end !== undefined && element.firstChild) {
          range.setStart(element.firstChild, start);
          range.setEnd(element.firstChild, end);
        } else {
          range.selectNodeContents(element);
        }
        
        const selection = window.getSelection();
        if (selection) {
          selection.removeAllRanges();
          selection.addRange(range);
        }
        return true;
      }
    } catch (err) {
      console.warn('Text selection failed:', err);
      return false;
    }
  }

  getSelectionInfo(): { text: string; start: number; end: number; element: HTMLElement | null } {
    const selection = window.getSelection();
    const element = this.lastTarget;

    if (element && (element.tagName === 'INPUT' || element.tagName === 'TEXTAREA')) {
      const input = element as HTMLInputElement | HTMLTextAreaElement;
      return {
        text: input.value.substring(input.selectionStart || 0, input.selectionEnd || 0),
        start: input.selectionStart || 0,
        end: input.selectionEnd || 0,
        element
      };
    } else if (selection && selection.toString()) {
      return {
        text: selection.toString(),
        start: 0,
        end: selection.toString().length,
        element
      };
    }

    return { text: '', start: 0, end: 0, element: null };
  }

  // Check if there's currently selected text
  hasTextSelection(target?: HTMLElement): boolean {
    const selection = window.getSelection();
    if (selection && !selection.isCollapsed && selection.toString().trim().length > 0) {
      return true;
    }
    
    if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA')) {
      const input = target as HTMLInputElement | HTMLTextAreaElement;
      return (input.selectionStart || 0) !== (input.selectionEnd || 0);
    }
    
    return false;
  }

  // Check if the target element is editable
  isTextEditable(target: HTMLElement): boolean {
    if (!target) return false;
    
    return (
      target.tagName === 'INPUT' ||
      target.tagName === 'TEXTAREA' ||
      target.isContentEditable ||
      target.getAttribute('contenteditable') === 'true'
    );
  }

  // Get context menu items for text operations
  getTextContextMenuItems(target: HTMLElement): Array<{ label: string; action: string; icon: string; disabled?: boolean }> {
    const hasSelection = this.hasTextSelection(target);
    const isEditable = this.isTextEditable(target);
    const hasClipboardData = this.clipboardData !== null;
    
    const items = [];
    
    if (hasSelection && isEditable) {
      items.push(
        { label: 'Cut', action: 'text-cut', icon: 'fa-cut' },
        { label: 'Copy', action: 'text-copy', icon: 'fa-copy' }
      );
    } else if (hasSelection) {
      items.push({ label: 'Copy', action: 'text-copy', icon: 'fa-copy' });
    }
    
    if (isEditable && hasClipboardData) {
      items.push({ label: 'Paste', action: 'text-paste', icon: 'fa-paste' });
    }
    
    if (hasSelection && isEditable) {
      items.push({ label: 'Delete', action: 'text-delete', icon: 'fa-trash' });
    }
    
    if (isEditable) {
      items.push({ label: 'Select All', action: 'text-select-all', icon: 'fa-select-all' });
    }
    
    return items;
  }

  // Delete selected text
  delete(target?: HTMLElement): void {
    const currentTarget = target || this.lastTarget;
    
    if (currentTarget && (currentTarget.tagName === 'INPUT' || currentTarget.tagName === 'TEXTAREA')) {
      const input = currentTarget as HTMLInputElement | HTMLTextAreaElement;
      const start = input.selectionStart || 0;
      const end = input.selectionEnd || 0;
      
      if (start !== end) {
        const newValue = input.value.substring(0, start) + input.value.substring(end);
        input.value = newValue;
        input.setSelectionRange(start, start);
        input.dispatchEvent(new Event('input', { bubbles: true }));
      }
    } else if (currentTarget && currentTarget.contentEditable === 'true') {
      const selection = window.getSelection();
      if (selection && selection.toString()) {
        selection.deleteFromDocument();
      }
    } else {
      const selection = window.getSelection();
      if (selection && selection.toString()) {
        selection.deleteFromDocument();
      }
    }
  }
}

export const clipboardService = new ClipboardService();

// Make it globally available for compatibility
(window as any).clipboardService = clipboardService; 