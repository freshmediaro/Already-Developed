import { eventSystem } from './EventSystem';
import { appRegistry } from './AppRegistry';

interface SearchResult {
  id: string;
  title: string;
  description: string;
  category: string;
  icon: string;
  action: () => void;
  score: number;
}

interface SearchCategory {
  name: string;
  icon: string;
  results: SearchResult[];
}

class GlobalSearchService {
  private isVisible = false;
  private searchOverlay: HTMLElement | null = null;
  private searchInput: HTMLInputElement | null = null;
  private searchResults: HTMLElement | null = null;
  private currentQuery = '';
  private selectedIndex = -1;
  private allResults: SearchResult[] = [];
  private searchData: Map<string, SearchResult[]> = new Map();
  private instance: unknown;

  constructor() {
    this.init();
    this.setupEventListeners();
    this.populateSearchData();
  }

  private init() {
    this.createSearchOverlay();
  }

  private createSearchOverlay() {
    // Create overlay if it doesn't exist
    this.searchOverlay = document.getElementById('global-search-overlay');
    if (!this.searchOverlay) {
      this.searchOverlay = document.createElement('div');
      this.searchOverlay.id = 'global-search-overlay';
      this.searchOverlay.className = 'global-search-overlay';
      this.searchOverlay.style.display = 'none';
      
      this.searchOverlay.innerHTML = `
        <div class="global-search-center">
          <div class="global-search-bar">
            <i class="fas fa-search global-search-icon"></i>
            <input type="text" 
                   class="global-search-input" 
                   placeholder="Search apps, files, settings..." 
                   autocomplete="off"
                   spellcheck="false">
            <button class="global-search-mic" title="Voice search">
              <i class="fas fa-microphone"></i>
            </button>
            <div class="global-search-dropdown">
              <button class="global-search-dropdown-btn">
                <span id="global-search-selected">
                  <i class="fas fa-search"></i>
                  All
                </span>
                <i class="fas fa-chevron-down"></i>
              </button>
              <div class="global-search-dropdown-list" style="display: none;">
                <button class="dropdown-item selected" data-filter="all">
                  <i class="fas fa-search"></i>
                  <span>All</span>
                </button>
                <button class="dropdown-item" data-filter="apps">
                  <i class="fas fa-th"></i>
                  <span>Apps</span>
                </button>
                <button class="dropdown-item" data-filter="files">
                  <i class="fas fa-file"></i>
                  <span>Files</span>
                </button>
                <button class="dropdown-item" data-filter="settings">
                  <i class="fas fa-cog"></i>
                  <span>Settings</span>
                </button>
                <button class="dropdown-item" data-filter="web">
                  <i class="fas fa-globe"></i>
                  <span>Web</span>
                </button>
              </div>
            </div>
          </div>
        </div>
        <div class="global-search-results" style="display: none;">
          <div class="global-search-container">
            <div class="global-search-suggestions">
              <div class="global-search-section">
                <h3 class="global-search-section-title">Suggestions</h3>
                <div class="global-search-items" id="search-suggestions">
                  <!-- Dynamic suggestions will be inserted here -->
                </div>
              </div>
            </div>
          </div>
        </div>
      `;

      document.body.appendChild(this.searchOverlay);
    }

    // Get references to elements
    this.searchInput = this.searchOverlay.querySelector('.global-search-input');
    this.searchResults = this.searchOverlay.querySelector('.global-search-results');
  }

  private setupEventListeners() {
    if (!this.searchOverlay || !this.searchInput) return;

    // Search input handling
    this.searchInput.addEventListener('input', (e) => {
      const query = (e.target as HTMLInputElement).value;
      this.handleSearch(query);
    });

    this.searchInput.addEventListener('keydown', (e) => {
      this.handleKeyboardNavigation(e);
    });

    // Close on outside click
    this.searchOverlay.addEventListener('click', (e) => {
      if (e.target === this.searchOverlay) {
        this.hide();
      }
    });

    // Dropdown functionality
    const dropdownBtn = this.searchOverlay.querySelector('.global-search-dropdown-btn');
    const dropdownList = this.searchOverlay.querySelector('.global-search-dropdown-list');
    const dropdownItems = this.searchOverlay.querySelectorAll('.dropdown-item');

    if (dropdownBtn && dropdownList) {
      dropdownBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const isVisible = (dropdownList as HTMLElement).style.display !== 'none';
        (dropdownList as HTMLElement).style.display = isVisible ? 'none' : 'flex';
      });

      dropdownItems.forEach(item => {
        item.addEventListener('click', (e) => {
          e.stopPropagation();
          const filter = item.getAttribute('data-filter') || undefined;
          const icon = item.querySelector('i')?.className || 'fas fa-search';
          const text = item.querySelector('span')?.textContent || 'All';
          
          // Update selected option
          const selectedElement = this.searchOverlay!.querySelector('#global-search-selected');
          if (selectedElement) {
            selectedElement.innerHTML = `<i class="${icon}"></i>${text}`;
          }

          // Update dropdown state
          dropdownItems.forEach(i => i.classList.remove('selected'));
          item.classList.add('selected');
          (dropdownList as HTMLElement).style.display = 'none';

          // Re-search with new filter
          this.handleSearch(this.currentQuery, filter);
        });
      });
    }

    // Close dropdown on outside click
    document.addEventListener('click', () => {
      if (dropdownList) {
        (dropdownList as HTMLElement).style.display = 'none';
      }
    });

    // Global keyboard shortcuts
    document.addEventListener('keydown', (e) => {
      // Ctrl+K or Cmd+K to open search
      if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        this.show();
      }
      
      // Escape to close
      if (e.key === 'Escape' && this.isVisible) {
        this.hide();
      }
    });
  }

  // Force refresh search data from current app registry
  refreshSearchData(): void {
    this.populateSearchData();
  }

  private populateSearchData() {
    // Get apps dynamically from app registry
    const registeredApps = appRegistry.getAllApps();
    const apps: SearchResult[] = registeredApps.map(app => ({
      id: app.id,
      name: app.name,
      icon: app.icon,
      iconType: app.iconType,
      iconBackground: app.iconBackground,
      category: app.category,
      description: app.description
    }));

    // Settings (keep static for now)
    const settings: SearchResult[] = [
      { id: 'display-settings', name: 'Display Settings', icon: 'fas fa-desktop', category: 'Settings' },
      { id: 'theme-settings', name: 'Theme & Personalization', icon: 'fas fa-paint-brush', category: 'Settings' },
      { id: 'sound-settings', name: 'Sound Settings', icon: 'fas fa-volume-up', category: 'Settings' },
      { id: 'notification-settings', name: 'Notifications', icon: 'fas fa-bell', category: 'Settings' },
      { id: 'taskbar-settings', name: 'Taskbar Settings', icon: 'fas fa-tasks', category: 'Settings' }
    ];

    // Files (mock data)
    const files: SearchResult[] = [
      { id: 'documents', name: 'Documents', icon: 'fas fa-folder', category: 'Files' },
      { id: 'downloads', name: 'Downloads', icon: 'fas fa-download', category: 'Files' },
      { id: 'pictures', name: 'Pictures', icon: 'fas fa-images', category: 'Files' },
      { id: 'music', name: 'Music', icon: 'fas fa-music', category: 'Files' }
    ];

    // Actions (dynamic based on apps)
    const actions: SearchResult[] = [
      { id: 'new-window', name: 'New Window', description: 'Open a new window', category: 'Actions', icon: 'fas fa-plus' },
      { id: 'settings', name: 'Open Settings', description: 'Configure system settings', category: 'Actions', icon: 'fas fa-cog' },
      { id: 'shutdown', name: 'Shutdown', description: 'Power off the system', category: 'Actions', icon: 'fas fa-power-off' },
      { id: 'restart', name: 'Restart', description: 'Restart the system', category: 'Actions', icon: 'fas fa-redo' }
    ];

    // Store in search data map
    this.searchData.set('apps', apps);
    this.searchData.set('settings', settings);
    this.searchData.set('files', files);
    this.searchData.set('actions', actions);
  }

  private handleSearch(query: string, filter = 'all') {
    this.currentQuery = query;
    this.selectedIndex = -1;

    if (!query.trim()) {
      this.showDefaultSuggestions();
      return;
    }

    // Filter and score results
    let filteredResults = this.allResults;

    if (filter !== 'all') {
      filteredResults = this.allResults.filter(result => {
        switch (filter) {
          case 'apps':
            return ['System', 'Tools', 'Communication', 'Productivity', 'Development'].includes(result.category);
          case 'files':
            return result.category === 'Files';
          case 'settings':
            return result.category === 'Settings';
          case 'web':
            return false; // Would integrate with web search
          default:
            return true;
        }
      });
    }

    // Score and sort results
    const searchResults = filteredResults
      .map(result => ({
        ...result,
        score: this.calculateScore(result, query)
      }))
      .filter(result => result.score > 0)
      .sort((a, b) => b.score - a.score)
      .slice(0, 8); // Limit to top 8 results

    this.renderResults(searchResults);
  }

  private calculateScore(result: SearchResult, query: string): number {
    const queryLower = query.toLowerCase();
    const titleLower = result.title.toLowerCase();
    const descriptionLower = result.description.toLowerCase();

    let score = 0;

    // Exact title match
    if (titleLower === queryLower) return 100;

    // Title starts with query
    if (titleLower.startsWith(queryLower)) score += 80;

    // Title contains query
    if (titleLower.includes(queryLower)) score += 60;

    // Description contains query
    if (descriptionLower.includes(queryLower)) score += 30;

    // Word boundary matches
    const words = queryLower.split(' ');
    words.forEach(word => {
      if (titleLower.includes(word)) score += 20;
    });

    return score;
  }

  private showDefaultSuggestions() {
    const suggestions = [
      { id: 'recent-files', title: 'Recent files', icon: 'fas fa-clock', description: 'View recently accessed files' },
      { id: 'calculator', title: 'Calculator', icon: 'fas fa-calculator', description: 'Perform calculations' },
      { id: 'settings', title: 'Settings', icon: 'fas fa-cog', description: 'Configure system settings' },
      { id: 'file-explorer', title: 'File Explorer', icon: 'fas fa-folder', description: 'Browse files and folders' }
    ];

    this.renderResults(suggestions.map(s => ({
      ...s,
      category: 'Suggestions',
      action: () => {
        eventSystem.emit('app:launch', { appId: s.id });
        this.hide();
      },
      score: 1
    })));
  }

  private renderResults(results: SearchResult[]) {
    if (!this.searchResults) return;

    const suggestionsContainer = this.searchResults.querySelector('#search-suggestions');
    if (!suggestionsContainer) return;

    if (results.length === 0) {
      suggestionsContainer.innerHTML = `
        <div class="global-search-no-results">
          <i class="fas fa-search"></i>
          <p>No results found for "${this.currentQuery}"</p>
          <small>Try searching for apps, files, or settings</small>
        </div>
      `;
      this.searchResults.style.display = 'block';
      return;
    }

    // Group results by category
    const groupedResults: { [key: string]: SearchResult[] } = {};
    results.forEach(result => {
      if (!groupedResults[result.category]) {
        groupedResults[result.category] = [];
      }
      groupedResults[result.category].push(result);
    });

    // Render grouped results
    let html = '';
    Object.entries(groupedResults).forEach(([category, categoryResults]) => {
      html += `
        <div class="global-search-category">
          <h4 class="global-search-category-title">${category}</h4>
          <div class="global-search-category-items">
      `;

      categoryResults.forEach((result, index) => {
        html += `
          <div class="global-search-item" data-id="${result.id}" data-index="${index}">
            <div class="global-search-item-icon">
              <i class="${result.icon}"></i>
            </div>
            <div class="global-search-item-content">
              <div class="global-search-item-title">${result.title}</div>
              <div class="global-search-item-description">${result.description}</div>
            </div>
            <div class="global-search-shortcut">Enter</div>
          </div>
        `;
      });

      html += `
          </div>
        </div>
      `;
    });

    suggestionsContainer.innerHTML = html;

    // Add click handlers
    const items = suggestionsContainer.querySelectorAll('.global-search-item');
    items.forEach((item, index) => {
      item.addEventListener('click', () => {
        const resultId = item.getAttribute('data-id');
        const result = results.find(r => r.id === resultId);
        if (result) {
          result.action();
        }
      });

      item.addEventListener('mouseenter', () => {
        this.selectedIndex = index;
        this.updateSelection();
      });
    });

    this.searchResults.style.display = 'block';
  }

  private handleKeyboardNavigation(e: KeyboardEvent) {
    if (!this.searchResults) return;

    const items = this.searchResults.querySelectorAll('.global-search-item');

    switch (e.key) {
      case 'ArrowDown':
        e.preventDefault();
        this.selectedIndex = Math.min(this.selectedIndex + 1, items.length - 1);
        this.updateSelection();
        break;

      case 'ArrowUp':
        e.preventDefault();
        this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
        this.updateSelection();
        break;

      case 'Enter':
        e.preventDefault();
        if (this.selectedIndex >= 0 && items[this.selectedIndex]) {
          (items[this.selectedIndex] as HTMLElement).click();
        }
        break;

      case 'Tab':
        if (this.selectedIndex >= 0 && items[this.selectedIndex]) {
          e.preventDefault();
          (items[this.selectedIndex] as HTMLElement).click();
        }
        break;
    }
  }

  private updateSelection() {
    if (!this.searchResults) return;

    const items = this.searchResults.querySelectorAll('.global-search-item');
    items.forEach((item, index) => {
      if (index === this.selectedIndex) {
        item.classList.add('selected');
      } else {
        item.classList.remove('selected');
      }
    });
  }

  show() {
    if (!this.searchOverlay || !this.searchInput) return;

    this.isVisible = true;
    this.searchOverlay.style.display = 'flex';
    
    // Reset state
    this.searchInput.value = '';
    this.currentQuery = '';
    this.selectedIndex = -1;
    
    // Show default suggestions
    this.showDefaultSuggestions();
    
    // Focus input with slight delay for animation
    setTimeout(() => {
      if (this.searchInput) {
        this.searchInput.focus();
      }
    }, 100);

    eventSystem.emit('global-search:show', {});
  }

  hide() {
    if (!this.searchOverlay) return;

    this.isVisible = false;
    this.searchOverlay.style.display = 'none';
    
    if (this.searchResults) {
      this.searchResults.style.display = 'none';
    }

    eventSystem.emit('global-search:hide', {});
  }

  isSearchVisible(): boolean {
    return this.isVisible;
  }

  toggle() {
    if (this.isVisible) {
      this.hide();
    } else {
      this.show();
    }
  }
}

export const globalSearchService = new GlobalSearchService();

// Make it globally available for compatibility with existing code
(window as any).showGlobalSearch = () => {
  globalSearchService.show();
};

(window as any).hideGlobalSearch = () => {
  globalSearchService.hide();
};

(window as any).globalSearchService = globalSearchService; 