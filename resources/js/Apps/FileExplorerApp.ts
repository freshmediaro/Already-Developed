// File Explorer Application - ElFinder Integration with Custom UI
import { BaseApp, type AppContext } from './BaseApp';
import { eventSystem } from '../Core/EventSystem';
import { apiService } from '../Tenant/ApiService';
import type { Window, App } from '../Core/Types';

// ElFinder integration
declare global {
  interface Window {
    elFinder: unknown;
  }
}

interface FileItem {
  id: string;
  name: string;
  type: 'file' | 'folder';
  size?: number;
  modified?: string;
  url?: string;
  thumbnail?: string;
  path: string;
  hash?: string;
  iconClass?: string;
  iconBgClass?: string;
  modifiedDate?: Date;
}

interface FolderContent {
  items: FileItem[];
  path: string;
  totalSize: number;
  totalFiles: number;
  totalFolders: number;
}

interface ElFinderOptions {
  url: string;
  lang: string;
  height: string;
  customData: {
    tenant_id: string;
    team_id: string;
  };
  ui: string[];
  uiOptions: {
    toolbar: string[][];
    tree: {
      openRootOnLoad: boolean;
      syncTree: boolean;
    };
    navbar: {
      minWidth: number;
      maxWidth: number;
    };
  };
  contextmenu: {
    navbar: string[];
    cwd: string[];
    files: string[];
  };
  resizable: boolean;
  rememberLastDir: boolean;
  onlyMimes: string[];
}

export class FileExplorerApp extends BaseApp {
  private files: FileItem[] = [];
  private folders: FileItem[] = [];
  private currentPath: string = '/';
  private selectedItems: Set<string> = new Set();
  private isDraggingSelector: boolean = false;
  private selectorStartX: number = 0;
  private selectorStartY: number = 0;
  private contentArea?: HTMLElement;
  private pathBreadcrumb?: HTMLElement;
  private fileGrid?: HTMLElement;
  private dragSelector?: HTMLElement;
  private viewMode: 'grid' | 'list' = 'grid';
  private elFinderInstance?: unknown;
  private elFinderContainer?: HTMLElement;
  private isElFinderMode: boolean = false;

  constructor() {
    const appInfo: App = {
      id: 'file-explorer',
      name: 'File Explorer',
      icon: 'fas fa-folder',
      iconType: 'fontawesome',
      iconBackground: 'yellow-icon',
      component: 'FileExplorerApp',
      category: 'system',
      permissions: ['read', 'write'],
      installed: true,
      system: true,
      teamScoped: true,
      version: '2.0.0',
      description: 'File management with ElFinder integration and Cloudflare R2 storage',
    };

    super('file-explorer', appInfo);
  }

  async onMount(context: AppContext): Promise<void> {
    await this.loadElFinderAssets();
    this.initializeElements(context.windowElement);
    this.setupFileSelection();
    this.setupContextMenu();
    this.setupToolbar();
    this.setupSidebar();
    await this.initializeElFinder();
    await this.loadFolderContent(this.currentPath);
  }

  async onUnmount(): Promise<void> {
    if (this.elFinderInstance) {
      // this.elFinderInstance.destroy(); // Original code had this line commented out
      this.elFinderInstance = null;
    }
    this.cleanupDragSelector();
    this.selectedItems.clear();
  }

  async onFocus(): Promise<void> {
    // Refresh current folder if needed
    if (this.contentArea && !this.isElFinderMode) {
      await this.loadFolderContent(this.currentPath);
    } else if (this.elFinderInstance) {
      // this.elFinderInstance.exec('reload'); // Original code had this line commented out
    }
  }

  async onBlur(): Promise<void> {
    // Clear selections when window loses focus
    if (!this.isElFinderMode) {
      this.clearFileSelection();
    }
  }

  private async loadElFinderAssets(): Promise<void> {
    // Load ElFinder CSS and JS assets
    return new Promise((resolve, reject) => {
      if (window.elFinder) {
        resolve();
        return;
      }

      // Load ElFinder CSS
      const cssLink = document.createElement('link');
      cssLink.rel = 'stylesheet';
      cssLink.href = '/css/elfinder.min.css';
      document.head.appendChild(cssLink);

      // Load ElFinder JS
      const script = document.createElement('script');
      script.src = '/js/elfinder.min.js';
      script.onload = () => resolve();
      script.onerror = () => reject(new Error('Failed to load ElFinder'));
      document.head.appendChild(script);
    });
  }

  private async initializeElFinder(): Promise<void> {
    if (!this.elFinderContainer) return;

    try {
      const tenantContext = await this.getTenantContext();
      
      const options: ElFinderOptions = {
        url: '/api/files/elfinder',
        lang: 'en',
        height: '100%',
        customData: {
          tenant_id: tenantContext.tenantId,
          team_id: tenantContext.teamId,
        },
        ui: ['tree', 'toolbar', 'path', 'stat'],
        uiOptions: {
          toolbar: [
            ['back', 'forward'],
            ['reload'],
            ['home', 'up'],
            ['mkdir', 'mkfile', 'upload'],
            ['open', 'download', 'getfile'],
            ['info'],
            ['quicklook'],
            ['copy', 'cut', 'paste'],
            ['rm'],
            ['duplicate', 'rename', 'edit'],
            ['extract', 'archive'],
            ['search'],
            ['view'],
            ['sort'],
            ['help']
          ],
          tree: {
            openRootOnLoad: true,
            syncTree: true,
          },
          navbar: {
            minWidth: 200,
            maxWidth: 500,
          },
        },
        contextmenu: {
          navbar: ['open', '|', 'copy', 'cut', 'paste', 'duplicate', '|', 'rm', '|', 'info'],
          cwd: ['reload', 'back', '|', 'upload', 'mkdir', 'mkfile', 'paste', '|', 'sort', '|', 'info'],
          files: ['getfile', '|', 'open', 'quicklook', '|', 'download', '|', 'copy', 'cut', 'paste', 'duplicate', '|', 'rm', '|', 'edit', 'rename', 'resize', '|', 'archive', 'extract', '|', 'info']
        },
        resizable: false,
        rememberLastDir: true,
        onlyMimes: [], // Allow all file types
      };

      this.elFinderInstance = (window.elFinder as any)(this.elFinderContainer, options);
      
      // Listen to ElFinder events
      // this.elFinderInstance.bind('open', (e: any) => { // Original code had this line commented out
      //   eventSystem.emit('file-explorer:folder-changed', { 
      //     path: e.data.cwd.path, 
      //     content: e.data 
      //   });
      // });

      // this.elFinderInstance.bind('select', (e: any) => { // Original code had this line commented out
      //   const selectedFiles = e.data.selected || [];
      //   eventSystem.emit('file-explorer:selection-changed', { 
      //     selectedItems: selectedFiles 
      //   });
      // });

    } catch (error) {
      console.error('Failed to initialize ElFinder:', error);
      this.showError('Failed to initialize file manager');
    }
  }

  private async getTenantContext(): Promise<{ tenantId: string; teamId: string }> {
    try {
      const response = await apiService.get('/api/tenant/context');
      return {
        tenantId: response.data.tenant_id || 'default',
        teamId: response.data.team_id || 'default',
      };
    } catch (error) {
      console.error('Failed to get tenant context:', error);
      return {
        tenantId: 'default',
        teamId: 'default',
      };
    }
  }

  private initializeElements(windowElement: HTMLElement): void {
    this.contentArea = windowElement.querySelector('.file-explorer-content') as HTMLElement;
    this.pathBreadcrumb = windowElement.querySelector('.file-path-breadcrumb') as HTMLElement;
    this.fileGrid = windowElement.querySelector('.file-grid') as HTMLElement;
    
    // Create ElFinder container
    this.elFinderContainer = document.createElement('div');
    this.elFinderContainer.className = 'elfinder-container hidden';
    this.elFinderContainer.style.cssText = `
      width: 100%;
      height: 100%;
      position: relative;
    `;
    
    if (this.contentArea) {
      this.contentArea.appendChild(this.elFinderContainer);
    }
    
    if (!this.contentArea || !this.fileGrid) {
      console.error('File Explorer: Required elements not found');
      return;
    }

    // Create drag selector if it doesn't exist
    this.createDragSelector();
  }

  private createDragSelector(): void {
    if (!this.contentArea) return;

    this.dragSelector = document.createElement('div');
    this.dragSelector.className = 'file-drag-selector hidden';
    this.dragSelector.style.cssText = `
      position: absolute;
      border: 1px solid var(--accent-color);
      background: rgba(var(--accent-color-rgb), 0.1);
      pointer-events: none;
      z-index: 1000;
    `;
    this.contentArea.appendChild(this.dragSelector);
  }

  private setupFileSelection(): void {
    if (!this.contentArea || !this.fileGrid) return;

    // Handle file grid clicks
    this.fileGrid.addEventListener('click', (e) => {
      const fileItem = (e.target as HTMLElement).closest('.file-item');
      if (fileItem) {
        const fileId = fileItem.getAttribute('data-file-id');
        if (fileId) {
          if (e.ctrlKey || e.metaKey) {
            this.toggleFileSelection(fileId);
          } else {
            this.selectSingleFile(fileId);
          }
        }
      } else {
        // Click on empty space - clear selection
        this.clearFileSelection();
      }
    });

    // Handle double-click to open files/folders
    this.fileGrid.addEventListener('dblclick', async (e) => {
      const fileItem = (e.target as HTMLElement).closest('.file-item');
      if (fileItem) {
        const fileId = fileItem.getAttribute('data-file-id');
        const fileType = fileItem.getAttribute('data-file-type');
        const filePath = fileItem.getAttribute('data-file-path');
        
        if (fileType === 'folder' && filePath) {
          await this.navigateToFolder(filePath);
        } else if (fileType === 'file' && fileId) {
          await this.openFile(fileId);
        }
      }
    });

    // Handle drag selection
    this.contentArea.addEventListener('mousedown', (e) => {
      if ((e.target as HTMLElement).closest('.file-item')) return;
      this.startDragSelection(e);
    });

    this.contentArea.addEventListener('mousemove', (e) => {
      if (this.isDraggingSelector) {
        this.updateDragSelection(e);
      }
    });

    this.contentArea.addEventListener('mouseup', () => {
      if (this.isDraggingSelector) {
        this.endDragSelection();
      }
    });
  }

  private startDragSelection(e: MouseEvent): void {
    if (!this.contentArea || !this.dragSelector) return;

    this.isDraggingSelector = true;
    this.clearFileSelection();

    const rect = this.contentArea.getBoundingClientRect();
    this.selectorStartX = e.clientX - rect.left;
    this.selectorStartY = e.clientY - rect.top;

    this.dragSelector.style.left = this.selectorStartX + 'px';
    this.dragSelector.style.top = this.selectorStartY + 'px';
    this.dragSelector.style.width = '0px';
    this.dragSelector.style.height = '0px';
    this.dragSelector.classList.remove('hidden');

    e.preventDefault();
  }

  private updateDragSelection(e: MouseEvent): void {
    if (!this.contentArea || !this.dragSelector) return;

    const rect = this.contentArea.getBoundingClientRect();
    const currentX = e.clientX - rect.left;
    const currentY = e.clientY - rect.top;

    const left = Math.min(this.selectorStartX, currentX);
    const top = Math.min(this.selectorStartY, currentY);
    const width = Math.abs(currentX - this.selectorStartX);
    const height = Math.abs(currentY - this.selectorStartY);

    this.dragSelector.style.left = left + 'px';
    this.dragSelector.style.top = top + 'px';
    this.dragSelector.style.width = width + 'px';
    this.dragSelector.style.height = height + 'px';

    // Select files within the selection rectangle
    this.selectFilesInRectangle(left, top, width, height);
  }

  private endDragSelection(): void {
    if (!this.dragSelector) return;

    this.isDraggingSelector = false;
    this.dragSelector.classList.add('hidden');
  }

  private selectFilesInRectangle(left: number, top: number, width: number, height: number): void {
    if (!this.fileGrid) return;

    const fileItems = this.fileGrid.querySelectorAll('.file-item');
    const selectionRect = { left, top, right: left + width, bottom: top + height };

    fileItems.forEach(item => {
      const itemRect = (item as HTMLElement).getBoundingClientRect();
      const contentRect = this.contentArea!.getBoundingClientRect();
      
      const itemRelativeRect = {
        left: itemRect.left - contentRect.left,
        top: itemRect.top - contentRect.top,
        right: itemRect.right - contentRect.left,
        bottom: itemRect.bottom - contentRect.top
      };

      const isIntersecting = !(
        itemRelativeRect.right < selectionRect.left ||
        itemRelativeRect.left > selectionRect.right ||
        itemRelativeRect.bottom < selectionRect.top ||
        itemRelativeRect.top > selectionRect.bottom
      );

      const fileId = (item as HTMLElement).getAttribute('data-file-id');
      if (fileId) {
        if (isIntersecting) {
          this.addToSelection(fileId);
        } else {
          this.removeFromSelection(fileId);
        }
      }
    });
  }

  private selectSingleFile(fileId: string): void {
    this.clearFileSelection();
    this.addToSelection(fileId);
  }

  private toggleFileSelection(fileId: string): void {
    if (this.selectedItems.has(fileId)) {
      this.removeFromSelection(fileId);
    } else {
      this.addToSelection(fileId);
    }
  }

  private addToSelection(fileId: string): void {
    this.selectedItems.add(fileId);
    this.updateFileItemVisualState(fileId, true);
    eventSystem.emit('file-explorer:selection-changed', { 
      selectedItems: Array.from(this.selectedItems) 
    });
  }

  private removeFromSelection(fileId: string): void {
    this.selectedItems.delete(fileId);
    this.updateFileItemVisualState(fileId, false);
    eventSystem.emit('file-explorer:selection-changed', { 
      selectedItems: Array.from(this.selectedItems) 
    });
  }

  private clearFileSelection(): void {
    this.selectedItems.forEach(fileId => {
      this.updateFileItemVisualState(fileId, false);
    });
    this.selectedItems.clear();
    eventSystem.emit('file-explorer:selection-changed', { selectedItems: [] });
  }

  private updateFileItemVisualState(fileId: string, selected: boolean): void {
    if (!this.fileGrid) return;

    const fileItem = this.fileGrid.querySelector(`[data-file-id="${fileId}"]`);
    if (fileItem) {
      if (selected) {
        fileItem.classList.add('selected');
      } else {
        fileItem.classList.remove('selected');
      }
    }
  }

  private setupContextMenu(): void {
    if (!this.contentArea) return;

    this.contentArea.addEventListener('contextmenu', (e) => {
      e.preventDefault();
      
      const fileItem = (e.target as HTMLElement).closest('.file-item');
      if (fileItem) {
        const fileId = fileItem.getAttribute('data-file-id');
        if (fileId && !this.selectedItems.has(fileId)) {
          this.selectSingleFile(fileId);
        }
        this.showFileContextMenu(e.clientX, e.clientY);
      } else {
        this.showFolderContextMenu(e.clientX, e.clientY);
      }
    });
  }

  private showFileContextMenu(x: number, y: number): void {
    const menu = [
      { label: 'Open', action: () => this.openSelectedFiles() },
      { label: 'Copy', action: () => this.copySelectedFiles() },
      { label: 'Cut', action: () => this.cutSelectedFiles() },
      { label: 'Delete', action: () => this.deleteSelectedFiles() },
      { label: 'Rename', action: () => this.renameSelectedFile() },
      { label: 'Properties', action: () => this.showFileProperties() }
    ];

    this.showContextMenu(x, y, menu);
  }

  private showFolderContextMenu(x: number, y: number): void {
    const menu = [
      { label: 'New Folder', action: () => this.createNewFolder() },
      { label: 'New File', action: () => this.createNewFile() },
      { label: 'Paste', action: () => this.pasteFiles() },
      { label: 'Refresh', action: () => this.refreshCurrentFolder() }
    ];

    this.showContextMenu(x, y, menu);
  }

  private showContextMenu(x: number, y: number, items: Array<{ label: string; action: () => void }>): void {
    // Implementation for context menu display
    eventSystem.emit('context-menu:show', { x, y, items });
  }

  private setupToolbar(): void {
    // Setup toolbar buttons (back, forward, up, view mode, etc.)
    const toolbar = this.windowElement?.querySelector('.file-explorer-toolbar');
    if (!toolbar) return;

    const backBtn = toolbar.querySelector('.back-btn');
    const forwardBtn = toolbar.querySelector('.forward-btn');
    const upBtn = toolbar.querySelector('.up-btn');
    const refreshBtn = toolbar.querySelector('.refresh-btn');
    const viewToggleBtn = toolbar.querySelector('.view-toggle-btn');
    const elFinderToggleBtn = toolbar.querySelector('.elfinder-toggle-btn');

    backBtn?.addEventListener('click', () => this.navigateBack());
    forwardBtn?.addEventListener('click', () => this.navigateForward());
    upBtn?.addEventListener('click', () => this.navigateUp());
    refreshBtn?.addEventListener('click', () => this.refreshCurrentFolder());
    viewToggleBtn?.addEventListener('click', () => this.toggleViewMode());
    elFinderToggleBtn?.addEventListener('click', () => this.toggleElFinderMode());
  }

  private setupSidebar(): void {
    // Setup sidebar with quick access folders
    const sidebar = this.windowElement?.querySelector('.file-explorer-sidebar');
    if (!sidebar) return;

    const quickAccessItems = sidebar.querySelectorAll('.quick-access-item');
    quickAccessItems.forEach(item => {
      item.addEventListener('click', () => {
        const path = (item as HTMLElement).getAttribute('data-path');
        if (path) {
          this.navigateToFolder(path);
        }
      });
    });
  }

  private async loadFolderContent(path: string): Promise<void> {
    if (this.isElFinderMode) return; // ElFinder handles its own content loading
    
    try {
      const response = await apiService.get<FolderContent>(`/api/files/folder`, { 
        params: { path } 
      });
      const content = response.data;
      this.currentPath = content.path;
      this.renderFolderContent(content);
              const pathToUpdate = content.path || path;
        this.updatePathBreadcrumb(pathToUpdate);
      eventSystem.emit('file-explorer:folder-changed', { path: content.path, content });
    } catch (error) {
      console.error('Failed to load folder content:', error);
      this.showError('Failed to load folder content');
    }
  }

  private renderFolderContent(content: FolderContent): void {
    if (!this.fileGrid) return;

    this.fileGrid.innerHTML = '';
    
    content.items.forEach(item => {
      const fileElement = this.createFileElement(item);
      this.fileGrid!.appendChild(fileElement);
    });
  }

  private createFileElement(item: FileItem): HTMLElement {
    const element = document.createElement('div');
    element.className = `file-item ${this.viewMode}-view`;
    element.setAttribute('data-file-id', item.id);
    element.setAttribute('data-file-type', item.type);
    element.setAttribute('data-file-path', item.path);

    const iconClass = item.iconClass || (item.type === 'folder' ? 'fa-folder' : 'fa-file');
    const iconBgClass = item.iconBgClass || (item.type === 'folder' ? 'blue-icon' : 'gray-icon');

    element.innerHTML = `
      <div class="file-icon">
        <div class="icon-container ${iconBgClass}">
          <i class="fas ${iconClass}"></i>
        </div>
      </div>
      <div class="file-info">
        <div class="file-name">${item.name}</div>
        ${this.viewMode === 'list' ? `
          <div class="file-details">
            <span class="file-size">${this.formatFileSize(item.size || 0)}</span>
            <span class="file-date">${this.formatDate(item.modifiedDate)}</span>
          </div>
        ` : ''}
      </div>
    `;

    return element;
  }

  private updatePathBreadcrumb(path: string): void {
    if (!this.pathBreadcrumb) return;

    const parts = path.split('/').filter(part => part);
    let currentPath = '';
    
    let breadcrumbHTML = `<span class="breadcrumb-item" data-path="/">Home</span>`;
    
    parts.forEach((part, index) => {
      currentPath += '/' + part;
      breadcrumbHTML += ` <i class="fas fa-chevron-right"></i> `;
      breadcrumbHTML += `<span class="breadcrumb-item" data-path="${currentPath}">${part}</span>`;
    });

    this.pathBreadcrumb.innerHTML = breadcrumbHTML;

    // Add click handlers to breadcrumb items
    this.pathBreadcrumb.querySelectorAll('.breadcrumb-item').forEach(item => {
      item.addEventListener('click', () => {
        const targetPath = (item as HTMLElement).getAttribute('data-path');
        if (targetPath) {
          this.navigateToFolder(targetPath);
        }
      });
    });
  }

  // Navigation methods
  private async navigateToFolder(path: string): Promise<void> {
    await this.loadFolderContent(path);
  }

  private async navigateBack(): Promise<void> {
    // Implementation for navigation history
    console.log('Navigate back');
  }

  private async navigateForward(): Promise<void> {
    // Implementation for navigation history
    console.log('Navigate forward');
  }

  private async navigateUp(): Promise<void> {
    const parentPath = this.currentPath.split('/').slice(0, -1).join('/') || '/';
    await this.navigateToFolder(parentPath);
  }

  private async refreshCurrentFolder(): Promise<void> {
    await this.loadFolderContent(this.currentPath);
  }

  private toggleViewMode(): void {
    this.viewMode = this.viewMode === 'grid' ? 'list' : 'grid';
    this.refreshCurrentFolder();
    eventSystem.emit('file-explorer:view-mode-changed', { viewMode: this.viewMode });
  }

  private toggleElFinderMode(): void {
    this.isElFinderMode = !this.isElFinderMode;
    
    if (this.isElFinderMode) {
      // Switch to ElFinder mode
      this.fileGrid?.classList.add('hidden');
      this.elFinderContainer?.classList.remove('hidden');
    } else {
      // Switch to custom UI mode
      this.elFinderContainer?.classList.add('hidden');
      this.fileGrid?.classList.remove('hidden');
    }
    
    // Custom event - not in the predefined DesktopEvent types yet
    eventSystem.emit('file-explorer:folder-changed', { 
      path: this.currentPath,
      content: { isElFinderMode: this.isElFinderMode }
    });
  }

  // File operations
  private async openSelectedFiles(): Promise<void> {
    for (const fileId of this.selectedItems) {
      await this.openFile(fileId);
    }
  }

  private async openFile(fileId: string): Promise<void> {
    try {
      const fileInfo = await apiService.get(`/api/files/${fileId}`);
      eventSystem.emit('file-explorer:file-open', { fileId, fileInfo });
    } catch (error) {
      console.error('Failed to open file:', error);
    }
  }

  private copySelectedFiles(): void {
    eventSystem.emit('file-explorer:selection-changed', { 
      selectedItems: Array.from(this.selectedItems),
      action: 'copy'
    });
  }

  private cutSelectedFiles(): void {
    eventSystem.emit('file-explorer:selection-changed', { 
      selectedItems: Array.from(this.selectedItems),
      action: 'cut'
    });
  }

  private async deleteSelectedFiles(): Promise<void> {
    if (this.selectedItems.size === 0) return;

    const confirmed = confirm(`Delete ${this.selectedItems.size} item(s)?`);
    if (!confirmed) return;

    try {
      const fileIds = Array.from(this.selectedItems);
      await apiService.delete(`/api/files/${fileIds.join(',')}`);
      await this.refreshCurrentFolder();
      eventSystem.emit('file-explorer:selection-changed', { 
        selectedItems: [],
        action: 'delete'
      });
    } catch (error) {
      console.error('Failed to delete files:', error);
      this.showError('Failed to delete files');
    }
  }

  private renameSelectedFile(): void {
    if (this.selectedItems.size !== 1) return;
    
    const fileId = Array.from(this.selectedItems)[0];
    // Implementation for inline rename
    console.log('Rename file:', fileId);
  }

  private showFileProperties(): void {
    eventSystem.emit('file-explorer:selection-changed', { 
      selectedItems: Array.from(this.selectedItems),
      action: 'properties'
    });
  }

  private createNewFolder(): void {
    // Implementation for creating new folder
    console.log('Create new folder');
  }

  private createNewFile(): void {
    // Implementation for creating new file
    console.log('Create new file');
  }

  private pasteFiles(): void {
    // Implementation for pasting files
    console.log('Paste files');
  }

  // Utility methods
  private formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  private formatDate(date?: Date): string {
    if (!date) return '';
    return new Intl.DateTimeFormat('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    }).format(date);
  }

  protected showError(message: string): void {
    eventSystem.emit('notification:show', {
      type: 'error',
      title: 'File Explorer Error',
      message,
      duration: 5000,
    });
  }

  private cleanupDragSelector(): void {
    if (this.dragSelector && this.dragSelector.parentNode) {
      this.dragSelector.parentNode.removeChild(this.dragSelector);
    }
    this.dragSelector = undefined;
  }

  private windowElement?: HTMLElement;
}

export default FileExplorerApp; 