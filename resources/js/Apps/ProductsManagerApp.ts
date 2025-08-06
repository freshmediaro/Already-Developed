// Products Manager Application - Comprehensive Aimeos product catalog management
import { BaseApp } from './BaseApp';
import type { App } from '../Core/Types';

interface AimeosProduct {
  id: string;
  attributes: {
    'product.id': string;
    'product.type': string;
    'product.code': string;
    'product.label': string;
    'product.url': string;
    'product.dataset': string;
    'product.datestart': string;
    'product.dateend': string;
    'product.config': Record<string, unknown>;
    'product.status': number;
    'product.target': string;
    'product.boost': number;
    'product.instock': number;
    'product.scale': number;
    'product.ctime': string;
    'product.mtime': string;
    'product.editor': string;
  };
  relationships?: {
    text?: any[];
    price?: any[];
    media?: any[];
    attribute?: any[];
    property?: any[];
    stock?: any[];
    catalog?: any[];
  };
}

interface AimeosStock {
  id: string;
  attributes: {
    'stock.id': string;
    'stock.productid': string;
    'stock.type': string;
    'stock.stocklevel': number;
    'stock.timeframe': string;
    'stock.dateback': string;
  };
}

interface AimeosPrice {
  id: string;
  attributes: {
    'price.id': string;
    'price.type': string;
    'price.currencyid': string;
    'price.domain': string;
    'price.quantity': number;
    'price.value': string;
    'price.costs': string;
    'price.rebate': string;
    'price.taxrates': string;
    'price.status': number;
  };
}

export class ProductsManagerApp extends BaseApp {
  private products: AimeosProduct[] = [];
  private selectedProduct: AimeosProduct | null = null;
  private currentPage = 1;
  private totalPages = 1;
  private searchQuery = '';
  private categoryFilter = '';
  private statusFilter = '';
  private viewMode: 'grid' | 'list' = 'grid';
  private sortBy = 'name';

  constructor() {
    const appInfo: App = {
      id: 'products-manager',
      name: 'Products Manager',
      icon: 'fas fa-boxes',
      iconType: 'fontawesome',
      iconBackground: 'purple-icon',
      component: 'ProductsManagerApp',
      category: 'ecommerce',
      permissions: ['products.view', 'products.edit', 'products.create', 'products.delete'],
      installed: true,
      system: false,
      teamScoped: true,
      version: '1.0.0',
      description: 'Manage product catalog, pricing, and inventory',
      author: 'System'
    };
    
    super('products-manager', appInfo);
  }

  protected async render(): Promise<void> {
    if (!this.context) return;
    
    this.context.contentElement.innerHTML = this.createContent();
    this.setupProductsEventListeners();
    this.loadProducts();
  }

  private createContent(): string {
    return `
      <div class="products-manager-app">
        <!-- Mobile profile top bar -->
        <div class="mobile-profile-top-bar">
          <div class="mobile-profile-top-bar-header">
            <div class="mobile-profile-top-bar-header-left">
              <img src="img/avatar.png" alt="Site Logo" class="site-logo-round">
              <span>Products Manager</span>
            </div>
            <div class="mobile-profile-header-right">
              <button class="toolbar-search-btn" title="Search"><i class="fas fa-search"></i></button>
            </div>
          </div>
        </div>

        <div class="product-edit-container">
          <!-- Page Header -->
          <div class="page-header">
            <h1>Product Catalog Management</h1>
            <div class="header-actions">
              <button class="btn btn-secondary import-products-btn">
                <i class="fas fa-upload"></i> Import
              </button>
              <button class="btn btn-secondary export-products-btn">
                <i class="fas fa-download"></i> Export
              </button>
              <button class="btn btn-primary add-product-btn">
                <i class="fas fa-plus"></i> Add Product
              </button>
            </div>
          </div>

          <!-- Two Column Layout -->
          <div class="product-content-columns">
            <!-- Left Column - Product List -->
            <div class="product-main-column">
              <div class="product-section">
                <div class="section-header-with-link">
                  <h2><i class="fas fa-cube"></i> Product Catalog</h2>
                  <div class="product-actions">
                    <button class="btn-link sync-products-btn">
                      <i class="fas fa-sync"></i> Sync with Aimeos
                    </button>
                  </div>
                </div>
                
                <!-- Search and Filters -->
                <div class="form-row">
                  <div class="form-group third">
                    <label>Search Products</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <i class="fas fa-search"></i>
                      </div>
                      <input type="text" class="form-control product-search" placeholder="Search by name, code, or description...">
                    </div>
                  </div>
                  <div class="form-group third">
                    <label>Filter by Category</label>
                    <div class="select-container">
                      <select class="form-control category-filter">
                        <option value="">All Categories</option>
                        <option value="electronics">Electronics</option>
                        <option value="clothing">Clothing</option>
                        <option value="books">Books</option>
                        <option value="home">Home & Garden</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group third">
                    <label>Filter by Status</label>
                    <div class="select-container">
                      <select class="form-control status-filter">
                        <option value="">All Products</option>
                        <option value="1">Active</option>
                        <option value="0">Disabled</option>
                        <option value="-1">Review</option>
                        <option value="-2">Archived</option>
                      </select>
                    </div>
                  </div>
                </div>

                <!-- View Controls -->
                <div class="form-row">
                  <div class="form-group half">
                    <label>View Mode</label>
                    <div class="view-controls">
                      <button class="view-btn active" data-view="grid" title="Grid View">
                        <i class="fas fa-th"></i> Grid
                      </button>
                      <button class="view-btn" data-view="list" title="List View">
                        <i class="fas fa-list"></i> List
                      </button>
                    </div>
                  </div>
                  <div class="form-group half">
                    <label>Sort by</label>
                    <div class="select-container">
                      <select class="form-control sort-select">
                        <option value="product.label">Name</option>
                        <option value="product.code">Product Code</option>
                        <option value="product.ctime">Date Added</option>
                        <option value="product.mtime">Last Modified</option>
                        <option value="stock.stocklevel">Stock Level</option>
                      </select>
                    </div>
                  </div>
                </div>

                <!-- Product List -->
                <div class="product-list-container">
                  <div class="contacts-oder-list-header-item">
                    <div class="products-list-featured-icon"></div>
                    <div class="products-list-name">Product</div>
                    <div class="order-date">Modified</div>
                    <div class="order-status">Status</div>
                    <div class="order-amount">Stock</div>
                    <div class="order-actions">Actions</div>
                  </div>
                  
                  <div class="orders-list" id="products-list">
                    <div class="loading-state" style="text-align: center; padding: 40px;">
                      <i class="fas fa-spinner fa-spin"></i> Loading products...
                    </div>
                  </div>
                </div>

                <!-- Product Grid (Alternative View) -->
                <div class="product-grid" id="products-grid" style="display: none;">
                  <!-- Grid items will be dynamically generated -->
                </div>

                <!-- Pagination -->
                <div class="footer-pagination">
                  <div class="total-label">
                    <span class="total-amount" id="total-products">0</span> products found
                  </div>
                  <div class="pagination" id="pagination-controls">
                    <!-- Pagination will be dynamically generated -->
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column - Product Details -->
            <div class="product-product-publish-column">
              <div class="sidebar-title">Product Details</div>
              
              <div id="product-details-panel" class="product-details-empty" style="text-align: center; padding: 40px; color: #8A8A9E;">
                <i class="fas fa-cube" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                <p>Select a product to view details</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  private setupProductsEventListeners(): void {
    if (!this.context) return;
    
    const container = this.context.contentElement;

    // Search functionality
    const searchInput = container.querySelector('.product-search') as HTMLInputElement;
    if (searchInput) {
      let searchTimeout: number;
      this.addEventListener(searchInput, 'input', (e: Event) => {
        clearTimeout(searchTimeout);
        const query = (e.target as HTMLInputElement).value;
        searchTimeout = window.setTimeout(() => {
          this.searchQuery = query;
          this.currentPage = 1;
          this.loadProducts();
        }, 300);
      });
    }

    // Category filter
    const categoryFilter = container.querySelector('.category-filter') as HTMLSelectElement;
    if (categoryFilter) {
      this.addEventListener(categoryFilter, 'change', (e: Event) => {
        this.categoryFilter = (e.target as HTMLSelectElement).value;
        this.currentPage = 1;
        this.loadProducts();
      });
    }

    // Status filter
    const statusFilter = container.querySelector('.status-filter') as HTMLSelectElement;
    if (statusFilter) {
      this.addEventListener(statusFilter, 'change', (e: Event) => {
        this.statusFilter = (e.target as HTMLSelectElement).value;
        this.currentPage = 1;
        this.loadProducts();
      });
    }

    // Sort selection
    const sortSelect = container.querySelector('.sort-select') as HTMLSelectElement;
    if (sortSelect) {
      this.addEventListener(sortSelect, 'change', (e: Event) => {
        this.sortBy = (e.target as HTMLSelectElement).value;
        this.currentPage = 1;
        this.loadProducts();
      });
    }

    // Button handlers
    this.addEventListener(container, 'click', (e: Event) => {
      const target = e.target as HTMLElement;
      
      if (target.closest('.add-product-btn')) {
        this.showAddProductModal();
      } else if (target.closest('.sync-products-btn')) {
        this.loadProducts();
      } else if (target.closest('.import-products-btn')) {
        this.importProducts();
      } else if (target.closest('.export-products-btn')) {
        this.exportProducts();
      } else if (target.closest('.edit-product-btn')) {
        this.editProduct();
      } else if (target.closest('.duplicate-product-btn')) {
        this.duplicateProduct();
      } else if (target.closest('.delete-product-btn')) {
        this.deleteProduct();
      } else if (target.closest('.view-btn')) {
        this.changeView(target.closest('.view-btn') as HTMLElement);
      } else if (target.closest('.product-item') || target.closest('.product-card')) {
        const productId = target.closest('.product-item')?.getAttribute('data-product-id') ||
                         target.closest('.product-card')?.getAttribute('data-product-id');
        if (productId) {
          this.selectProduct(productId);
        }
      } else if (target.closest('.pagination-item')) {
        const page = parseInt(target.getAttribute('data-page') || '1');
        this.currentPage = page;
        this.loadProducts();
      }
    });
  }

  private async loadProducts(): Promise<void> {
    try {
      const response = await this.makeAimeosRequest('/jsonapi/product', {
        method: 'GET',
        params: {
          'page[offset]': (this.currentPage - 1) * 20,
          'page[limit]': 20,
          'include': 'product/price,product/stock,product/text,product/media',
          'sort': this.sortBy,
          ...(this.searchQuery && {
            'filter[product.label]': `*${this.searchQuery}*`,
            'filter[product.code]': `*${this.searchQuery}*`
          }),
          ...(this.statusFilter && {
            'filter[product.status]': this.statusFilter
          }),
          ...(this.categoryFilter && {
            'filter[catalog.code]': this.categoryFilter
          })
        }
      });

      if (response.data) {
        this.products = Array.isArray(response.data) ? response.data : [response.data];
        this.totalPages = Math.ceil((response.meta?.total || this.products.length) / 20);
        this.renderProductsList();
        this.renderPagination();
        this.updateTotalCount(response.meta?.total || this.products.length);
      }
    } catch (error) {
      console.error('Error loading products:', error);
      this.showError('Failed to load products. Please try again.');
    }
  }

  private renderProductsList(): void {
    const listContainer = document.getElementById('products-list');
    const gridContainer = document.getElementById('products-grid');
    
    if (!listContainer || !gridContainer) return;

    if (this.products.length === 0) {
      const emptyState = `
        <div style="text-align: center; padding: 40px; color: #8A8A9E;">
          <i class="fas fa-cube" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
          <p>No products found</p>
          ${this.searchQuery ? '<button class="btn-link" onclick="this.clearSearch()">Clear search</button>' : ''}
        </div>
      `;
      listContainer.innerHTML = emptyState;
      gridContainer.innerHTML = emptyState;
      return;
    }

    // Render list view
    listContainer.innerHTML = this.products.map(product => {
      const attrs = product.attributes;
      const name = attrs['product.label'] || 'Unnamed Product';
      const code = attrs['product.code'] || '';
      const status = this.getProductStatusBadge(attrs['product.status']);
      const stock = this.getStockInfo(product);
      const modifiedDate = new Date(attrs['product.mtime']).toLocaleDateString();
      const isFeatured = attrs['product.boost'] > 0;
      
      return `
        <div class="order-item product-item" data-product-id="${product.id}">
          <div class="products-list-featured-icon">
            ${isFeatured ? '<i class="fas fa-star"></i>' : ''}
          </div>
          <div class="products-list-name">
            <div>
              <strong>${name}</strong>
              <div style="font-size: 12px; color: #8A8A9E;">Code: ${code}</div>
            </div>
          </div>
          <div class="order-date">${modifiedDate}</div>
          <div class="order-status">${status}</div>
          <div class="order-amount">${stock}</div>
          <div class="order-actions">
            <button class="btn-link edit-product-btn" data-product-id="${product.id}" title="Edit">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn-link duplicate-product-btn" data-product-id="${product.id}" title="Duplicate">
              <i class="fas fa-copy"></i>
            </button>
            <button class="btn-link delete-product-btn" data-product-id="${product.id}" title="Delete">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      `;
    }).join('');

    // Render grid view
    gridContainer.innerHTML = this.products.map(product => {
      const attrs = product.attributes;
      const name = attrs['product.label'] || 'Unnamed Product';
      const code = attrs['product.code'] || '';
      const status = this.getProductStatusBadge(attrs['product.status']);
      const stock = this.getStockInfo(product);
      const price = this.getProductPrice(product);
      
      return `
        <div class="product-card" data-product-id="${product.id}">
          <div class="product-image">
            <img src="/img/placeholder-auto-theme.png" alt="${name}">
            <div class="product-overlay">
              <button class="overlay-btn edit-product-btn" data-product-id="${product.id}" title="Edit">
                <i class="fas fa-edit"></i>
              </button>
              <button class="overlay-btn duplicate-product-btn" data-product-id="${product.id}" title="Duplicate">
                <i class="fas fa-copy"></i>
              </button>
              <button class="overlay-btn delete-product-btn" data-product-id="${product.id}" title="Delete">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
          <div class="product-info">
            <h3 class="product-name">${name}</h3>
            <p class="product-code">Code: ${code}</p>
            <div class="product-price">${price}</div>
            <div class="product-stock">${stock}</div>
            <div class="product-status">${status}</div>
          </div>
        </div>
      `;
    }).join('');
  }

  private getProductStatusBadge(status: number): string {
    const statusMap = {
      1: { label: 'Active', class: 'status-completed' },
      0: { label: 'Disabled', class: 'status-draft' },
      [-1]: { label: 'Review', class: 'status-pending' },
      [-2]: { label: 'Archived', class: 'status-draft' }
    };
    
    const statusInfo = statusMap[status as keyof typeof statusMap] || { label: 'Unknown', class: 'status-draft' };
    return `<span class="order-status ${statusInfo.class}">${statusInfo.label}</span>`;
  }

  private getStockInfo(product: AimeosProduct): string {
    const stockLevel = product.attributes['product.instock'] || 0;
    
    if (stockLevel === 0) {
      return '<span class="order-status status-pending">Out of Stock</span>';
    } else if (stockLevel < 10) {
      return `<span class="order-status status-pending">Low Stock (${stockLevel})</span>`;
    } else {
      return `<span class="order-status status-completed">In Stock (${stockLevel})</span>`;
    }
  }

  private getProductPrice(product: AimeosProduct): string {
    // This would normally come from the included price relationship
    // For now, return a placeholder
    return '€19.99';
  }

  private async selectProduct(productId: string): Promise<void> {
    try {
      // Highlight selected product
      const container = this.context?.contentElement;
      if (container) {
        container.querySelectorAll('.product-item, .product-card').forEach((item: Element) => {
          item.classList.remove('active');
        });
        const selectedItem = container.querySelector(`[data-product-id="${productId}"]`);
        if (selectedItem) {
          selectedItem.classList.add('active');
        }
      }

      // Load detailed product data
      const response = await this.makeAimeosRequest(`/jsonapi/product?id=${productId}`, {
        method: 'GET',
        params: {
          'include': 'product/price,product/stock,product/text,product/media,product/attribute,product/property'
        }
      });

      if (response.data) {
        this.selectedProduct = response.data;
        this.renderProductDetails();
      }
    } catch (error) {
      console.error('Error loading product details:', error);
      this.showError('Failed to load product details.');
    }
  }

  private renderProductDetails(): void {
    const detailsPanel = document.getElementById('product-details-panel');
    if (!detailsPanel || !this.selectedProduct) return;

    const attrs = this.selectedProduct.attributes;
    const name = attrs['product.label'] || 'Unnamed Product';
    const code = attrs['product.code'];

    detailsPanel.innerHTML = `
      <div class="contact-content-wrapper">
        <!-- Product Header -->
        <div class="client-header">
          <div class="contact-info-with-id">
            <div class="client-id-with-online-status">
              <div class="client-id">${name}</div>
              <div class="online-indicator" style="background: ${attrs['product.status'] === 1 ? '#4CAF50' : '#e57373'}"></div>
            </div>
            <div class="client-meta">
              <span>Code: ${code}</span>
              <span class="separator">•</span>
              <span>Created: ${new Date(attrs['product.ctime']).toLocaleDateString()}</span>
            </div>
          </div>
        </div>

        <!-- Product Details -->
        <div class="contact-details-container">
          <div class="detail-section">
            <div class="detail-section-header">
              <i class="fas fa-cube"></i>
              <h3>Product Information</h3>
            </div>
            <div class="detail-value">
              <strong>Name:</strong> ${name}<br>
              <strong>Code:</strong> ${code}<br>
              <strong>Type:</strong> ${attrs['product.type']}<br>
              <strong>Status:</strong> ${this.getProductStatusText(attrs['product.status'])}
            </div>
          </div>

          <div class="detail-section">
            <div class="detail-section-header">
              <i class="fas fa-warehouse"></i>
              <h3>Inventory</h3>
            </div>
            <div class="detail-value">
              <strong>Stock Level:</strong> ${attrs['product.instock'] || 0}<br>
              <strong>Scale:</strong> ${attrs['product.scale'] || 1}<br>
              <strong>Date Start:</strong> ${attrs['product.datestart'] || 'Not set'}<br>
              <strong>Date End:</strong> ${attrs['product.dateend'] || 'Not set'}
            </div>
          </div>

          <div class="detail-section">
            <div class="detail-section-header">
              <i class="fas fa-cog"></i>
              <h3>Configuration</h3>
            </div>
            <div class="detail-value">
              <strong>Boost:</strong> ${attrs['product.boost'] || 0}<br>
              <strong>Target:</strong> ${attrs['product.target'] || 'Not set'}<br>
              <strong>URL:</strong> ${attrs['product.url'] || 'Not set'}<br>
              <strong>Dataset:</strong> ${attrs['product.dataset'] || 'Not set'}
            </div>
          </div>

          <div class="detail-section">
            <div class="detail-section-header">
              <i class="fas fa-clock"></i>
              <h3>Timestamps</h3>
            </div>
            <div class="detail-value">
              <strong>Created:</strong> ${new Date(attrs['product.ctime']).toLocaleDateString()}<br>
              <strong>Modified:</strong> ${new Date(attrs['product.mtime']).toLocaleDateString()}<br>
              <strong>Editor:</strong> ${attrs['product.editor']}
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="generate-invoice">
          <div class="invoice-header">
            <div class="invoice-icon">
              <i class="fas fa-edit"></i>
            </div>
            <div>
              <div class="invoice-text-title">Edit Product</div>
              <div class="invoice-text">Update product information, pricing, and inventory</div>
            </div>
          </div>
          <button class="invoice-button-text edit-product-btn" data-product-id="${this.selectedProduct.id}">
            Edit <i class="fas fa-arrow-right"></i>
          </button>
        </div>

        <div class="generate-shipping">
          <div class="shipping-header">
            <div class="shipping-icon">
              <i class="fas fa-copy"></i>
            </div>
            <div>
              <div class="shipping-text-title">Duplicate Product</div>
              <div class="shipping-text">Create a copy of this product with variations</div>
            </div>
          </div>
          <button class="shipping-button-text duplicate-product-btn" data-product-id="${this.selectedProduct.id}">
            Duplicate <i class="fas fa-arrow-right"></i>
          </button>
        </div>
      </div>
    `;
  }

  private getProductStatusText(status: number): string {
    const statusMap = {
      1: 'Active',
      0: 'Disabled',
      [-1]: 'Review',
      [-2]: 'Archived'
    };
    return statusMap[status as keyof typeof statusMap] || 'Unknown';
  }

  private changeView(button: HTMLElement): void {
    const view = button.getAttribute('data-view') as 'grid' | 'list';
    this.viewMode = view;
    
    const container = this.context?.contentElement;
    if (!container) return;

    // Update active button
    container.querySelectorAll('.view-btn').forEach(btn => btn.classList.remove('active'));
    button.classList.add('active');

    // Show/hide appropriate containers
    const listContainer = document.getElementById('products-list')?.parentElement;
    const gridContainer = document.getElementById('products-grid');

    if (listContainer && gridContainer) {
      if (view === 'grid') {
        listContainer.style.display = 'none';
        gridContainer.style.display = 'grid';
      } else {
        listContainer.style.display = 'block';
        gridContainer.style.display = 'none';
      }
    }
  }

  private renderPagination(): void {
    const paginationContainer = document.getElementById('pagination-controls');
    if (!paginationContainer) return;

    const pagination = [];
    
    // Previous button
    pagination.push(`
      <div class="pagination-item ${this.currentPage === 1 ? 'disabled' : ''}" data-page="${this.currentPage - 1}">
        <i class="fas fa-chevron-left"></i>
      </div>
    `);

    // Page numbers
    const startPage = Math.max(1, this.currentPage - 2);
    const endPage = Math.min(this.totalPages, this.currentPage + 2);

    for (let i = startPage; i <= endPage; i++) {
      pagination.push(`
        <div class="pagination-item ${i === this.currentPage ? 'active' : ''}" data-page="${i}">
          ${i}
        </div>
      `);
    }

    // Next button
    pagination.push(`
      <div class="pagination-item ${this.currentPage === this.totalPages ? 'disabled' : ''}" data-page="${this.currentPage + 1}">
        <i class="fas fa-chevron-right"></i>
      </div>
    `);

    paginationContainer.innerHTML = pagination.join('');
  }

  private updateTotalCount(total: number): void {
    const totalElement = document.getElementById('total-products');
    if (totalElement) {
      totalElement.textContent = total.toString();
    }
  }

  private async makeAimeosRequest(endpoint: string, options: any = {}): Promise<any> {
    // This would integrate with your Laravel backend that proxies to Aimeos
    const baseUrl = '/admin/aimeos'; // Your Laravel route prefix
    const url = new URL(baseUrl + endpoint, window.location.origin);
    
    // Add query parameters
    if (options.params) {
      Object.keys(options.params).forEach(key => {
        url.searchParams.append(key, options.params[key]);
      });
    }

    const response = await fetch(url.toString(), {
      method: options.method || 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        ...options.headers
      },
      body: options.body ? JSON.stringify(options.body) : undefined
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    return await response.json();
  }

  private showAddProductModal(): void {
    console.log('Show add product modal');
    // Implementation for add product modal
  }

  private editProduct(): void {
    if (this.selectedProduct) {
      console.log('Edit product:', this.selectedProduct.id);
    }
  }

  private duplicateProduct(): void {
    if (this.selectedProduct) {
      console.log('Duplicate product:', this.selectedProduct.id);
    }
  }

  private async deleteProduct(): Promise<void> {
    if (this.selectedProduct && confirm('Are you sure you want to delete this product?')) {
      try {
        await this.makeAimeosRequest(`/jsonapi/product?id=${this.selectedProduct.id}`, {
          method: 'DELETE'
        });
        this.loadProducts();
        this.selectedProduct = null;
        const detailsPanel = document.getElementById('product-details-panel');
        if (detailsPanel) {
          detailsPanel.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #8A8A9E;">
              <i class="fas fa-cube" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
              <p>Select a product to view details</p>
            </div>
          `;
        }
      } catch (error) {
        this.showError('Failed to delete product.');
      }
    }
  }

  private importProducts(): void {
    console.log('Import products');
    // Implementation for CSV/XML import
  }

  private exportProducts(): void {
    console.log('Export products');
    // Implementation for CSV/XML export
  }
}

export default ProductsManagerApp;