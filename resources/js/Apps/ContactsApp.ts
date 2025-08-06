// Contacts Application - Comprehensive Aimeos customer relationship management
import { BaseApp } from './BaseApp';
import type { App } from '../Core/Types';

interface AimeosCustomer {
  id: string;
  attributes: {
    'customer.id': string;
    'customer.code': string;
    'customer.label': string;
    'customer.salutation': string;
    'customer.company': string;
    'customer.vatid': string;
    'customer.title': string;
    'customer.firstname': string;
    'customer.lastname': string;
    'customer.address1': string;
    'customer.address2': string;
    'customer.address3': string;
    'customer.postal': string;
    'customer.city': string;
    'customer.state': string;
    'customer.countryid': string;
    'customer.languageid': string;
    'customer.telephone': string;
    'customer.telefax': string;
    'customer.email': string;
    'customer.website': string;
    'customer.longitude': number;
    'customer.latitude': number;
    'customer.birthday': string;
    'customer.status': number;
    'customer.dateverified': string;
    'customer.mtime': string;
    'customer.ctime': string;
    'customer.editor': string;
  };
  relationships?: {
    product?: any[];
    order?: any[];
    address?: any[];
    property?: any[];
  };
}

interface AimeosOrder {
  id: string;
  attributes: {
    'order.id': string;
    'order.currencyid': string;
    'order.price': string;
    'order.statuspayment': number;
    'order.statusdelivery': number;
    'order.datepayment': string;
    'order.datedelivery': string;
    'order.ctime': string;
    'order.mtime': string;
  };
}

export class ContactsApp extends BaseApp {
  private customers: AimeosCustomer[] = [];
  private selectedCustomer: AimeosCustomer | null = null;
  private currentPage = 1;
  private totalPages = 1;
  private searchQuery = '';
  private statusFilter = '';

  constructor() {
    const appInfo: App = {
      id: 'contacts',
      name: 'Contacts',
      icon: 'fas fa-address-book',
      iconType: 'fontawesome',
      iconBackground: 'orange-icon',
      component: 'ContactsApp',
      category: 'productivity',
      permissions: ['contacts.view', 'contacts.edit'],
      installed: true,
      system: false,
      teamScoped: true,
      version: '1.0.0',
      description: 'Manage customer contacts and relationships',
      author: 'System'
    };
    
    super('contacts', appInfo);
  }

  protected async render(): Promise<void> {
    if (!this.context) return;
    
    this.context.contentElement.innerHTML = this.createContent();
    this.setupContactsEventListeners();
    this.loadCustomers();
  }

  private createContent(): string {
    return `
      <div class="contacts-app">
        <!-- Mobile profile top bar -->
        <div class="mobile-profile-top-bar">
          <div class="mobile-profile-top-bar-header">
            <div class="mobile-profile-top-bar-header-left">
              <img src="img/avatar.png" alt="Site Logo" class="site-logo-round">
              <span>Contacts Manager</span>
            </div>
            <div class="mobile-profile-header-right">
              <button class="toolbar-search-btn" title="Search"><i class="fas fa-search"></i></button>
            </div>
          </div>
        </div>

        <div class="product-edit-container">
          <!-- Page Header -->
          <div class="page-header">
            <h1>Customer Management</h1>
            <div class="header-actions">
              <button class="btn btn-secondary export-customers-btn">
                <i class="fas fa-download"></i> Export
              </button>
              <button class="btn btn-primary add-customer-btn">
                <i class="fas fa-plus"></i> Add Customer
              </button>
            </div>
          </div>

          <!-- Two Column Layout -->
          <div class="product-content-columns">
            <!-- Left Column - Customer List -->
            <div class="product-main-column">
              <div class="product-section">
                <div class="section-header-with-link">
                  <h2><i class="fas fa-users"></i> Customer Directory</h2>
                  <div class="contact-actions">
                    <button class="btn-link sync-customers-btn">
                      <i class="fas fa-sync"></i> Sync with Aimeos
                    </button>
                  </div>
                </div>
                
                <!-- Search and Filters -->
                <div class="form-row">
                  <div class="form-group half">
                    <label>Search Customers</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <i class="fas fa-search"></i>
                      </div>
                      <input type="text" class="form-control customer-search" placeholder="Search by name, email, or company...">
                    </div>
                  </div>
                  <div class="form-group half">
                    <label>Filter by Status</label>
                    <div class="select-container">
                      <select class="form-control status-filter">
                        <option value="">All Customers</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                        <option value="-1">Review</option>
                        <option value="-2">Archived</option>
                      </select>
                    </div>
                  </div>
                </div>

                <!-- Customer List -->
                <div class="contact-list-container">
                  <div class="contacts-oder-list-header-item">
                    <div class="contact-list-name">Customer</div>
                    <div class="order-date">Registered</div>
                    <div class="order-status">Status</div>
                    <div class="order-amount">Orders</div>
                    <div class="order-actions">Actions</div>
                  </div>
                  
                  <div class="orders-list" id="customers-list">
                    <div class="loading-state" style="text-align: center; padding: 40px;">
                      <i class="fas fa-spinner fa-spin"></i> Loading customers...
                    </div>
                  </div>
                </div>

                <!-- Pagination -->
                <div class="footer-pagination">
                  <div class="total-label">
                    <span class="total-amount" id="total-customers">0</span> customers found
                  </div>
                  <div class="pagination" id="pagination-controls">
                    <!-- Pagination will be dynamically generated -->
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column - Customer Details -->
            <div class="product-product-publish-column">
              <div class="sidebar-title">Customer Details</div>
              
              <div id="customer-details-panel" class="customer-details-empty" style="text-align: center; padding: 40px; color: #8A8A9E;">
                <i class="fas fa-user-circle" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                <p>Select a customer to view details</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  private setupContactsEventListeners(): void {
    if (!this.context) return;
    
    const container = this.context.contentElement;

    // Search functionality
    const searchInput = container.querySelector('.customer-search') as HTMLInputElement;
    if (searchInput) {
      let searchTimeout: number;
      this.addEventListener(searchInput, 'input', (e: Event) => {
        clearTimeout(searchTimeout);
        const query = (e.target as HTMLInputElement).value;
        searchTimeout = window.setTimeout(() => {
          this.searchQuery = query;
          this.currentPage = 1;
          this.loadCustomers();
        }, 300);
      });
    }

    // Status filter
    const statusFilter = container.querySelector('.status-filter') as HTMLSelectElement;
    if (statusFilter) {
      this.addEventListener(statusFilter, 'change', (e: Event) => {
        this.statusFilter = (e.target as HTMLSelectElement).value;
        this.currentPage = 1;
        this.loadCustomers();
      });
    }

    // Button handlers
    this.addEventListener(container, 'click', (e: Event) => {
      const target = e.target as HTMLElement;
      
      if (target.closest('.add-customer-btn')) {
        this.showAddCustomerModal();
      } else if (target.closest('.sync-customers-btn')) {
        this.loadCustomers();
      } else if (target.closest('.export-customers-btn')) {
        this.exportCustomers();
      } else if (target.closest('.edit-customer-btn')) {
        this.editCustomer();
      } else if (target.closest('.view-orders-btn')) {
        this.viewCustomerOrders();
      } else if (target.closest('.delete-customer-btn')) {
        this.deleteCustomer();
      } else if (target.closest('.customer-item')) {
        const customerId = target.closest('.customer-item')?.getAttribute('data-customer-id');
        if (customerId) {
          this.selectCustomer(customerId);
        }
      } else if (target.closest('.pagination-item')) {
        const page = parseInt(target.getAttribute('data-page') || '1');
        this.currentPage = page;
        this.loadCustomers();
      }
    });
  }

  private async loadCustomers(): Promise<void> {
    try {
      const response = await this.makeAimeosRequest('/jsonapi/customer', {
        method: 'GET',
        params: {
          'page[offset]': (this.currentPage - 1) * 20,
          'page[limit]': 20,
          'include': 'customer/address,customer/property',
          'sort': 'customer.lastname,customer.firstname',
          ...(this.searchQuery && {
            'filter[customer.email]': `*${this.searchQuery}*`,
            'filter[customer.firstname]': `*${this.searchQuery}*`,
            'filter[customer.lastname]': `*${this.searchQuery}*`,
            'filter[customer.company]': `*${this.searchQuery}*`
          }),
          ...(this.statusFilter && {
            'filter[customer.status]': this.statusFilter
          })
        }
      });

      if (response.data) {
        this.customers = Array.isArray(response.data) ? response.data : [response.data];
        this.totalPages = Math.ceil((response.meta?.total || this.customers.length) / 20);
        this.renderCustomersList();
        this.renderPagination();
        this.updateTotalCount(response.meta?.total || this.customers.length);
      }
    } catch (error) {
      console.error('Error loading customers:', error);
      this.showError('Failed to load customers. Please try again.');
    }
  }

  private renderCustomersList(): void {
    const listContainer = document.getElementById('customers-list');
    if (!listContainer) return;

    if (this.customers.length === 0) {
      listContainer.innerHTML = `
        <div style="text-align: center; padding: 40px; color: #8A8A9E;">
          <i class="fas fa-users" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
          <p>No customers found</p>
          ${this.searchQuery ? '<button class="btn-link" onclick="this.clearSearch()">Clear search</button>' : ''}
        </div>
      `;
      return;
    }

    listContainer.innerHTML = this.customers.map(customer => {
      const attrs = customer.attributes;
      const name = `${attrs['customer.firstname'] || ''} ${attrs['customer.lastname'] || ''}`.trim();
      const company = attrs['customer.company'] || '';
      const email = attrs['customer.email'] || '';
      const status = this.getStatusBadge(attrs['customer.status']);
      const registeredDate = new Date(attrs['customer.ctime']).toLocaleDateString();
      
      return `
        <div class="order-item customer-item" data-customer-id="${customer.id}">
          <div class="order-icon">
            <div class="contact-list-avatar">
              ${this.getCustomerAvatar(name, company)}
            </div>
          </div>
          <div class="contact-list-name">
            <div class="contact-list-name">
              <strong>${name || company || 'Unnamed Customer'}</strong>
              <div style="font-size: 12px; color: #8A8A9E;">${email}</div>
              ${company && name ? `<div style="font-size: 11px; color: #8A8A9E;">${company}</div>` : ''}
            </div>
          </div>
          <div class="order-date">${registeredDate}</div>
          <div class="order-status">${status}</div>
          <div class="order-amount">
            <button class="btn-link view-orders-btn" data-customer-id="${customer.id}">
              <i class="fas fa-shopping-cart"></i> Orders
            </button>
          </div>
          <div class="order-actions">
            <button class="btn-link edit-customer-btn" data-customer-id="${customer.id}" title="Edit">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn-link delete-customer-btn" data-customer-id="${customer.id}" title="Delete">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      `;
    }).join('');
  }

  private getCustomerAvatar(name: string, company: string): string {
    const displayName = name || company || 'U';
    const initials = displayName.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
    
    return `
      <div class="avatar-placeholder" style="background: #${this.stringToColor(displayName)}">
        ${initials}
      </div>
    `;
  }

  private stringToColor(str: string): string {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
      hash = str.charCodeAt(i) + ((hash << 5) - hash);
    }
    const color = ((hash & 0x00FFFFFF) >>> 0).toString(16).padStart(6, '0');
    return color;
  }

  private getStatusBadge(status: number): string {
    const statusMap = {
      1: { label: 'Active', class: 'status-completed' },
      0: { label: 'Inactive', class: 'status-draft' },
      [-1]: { label: 'Review', class: 'status-pending' },
      [-2]: { label: 'Archived', class: 'status-draft' }
    };
    
    const statusInfo = statusMap[status as keyof typeof statusMap] || { label: 'Unknown', class: 'status-draft' };
    return `<span class="order-status ${statusInfo.class}">${statusInfo.label}</span>`;
  }

  private async selectCustomer(customerId: string): Promise<void> {
    try {
      // Highlight selected customer
      const container = this.context?.contentElement;
      if (container) {
        container.querySelectorAll('.customer-item').forEach((item: Element) => {
          item.classList.remove('active');
        });
        const selectedItem = container.querySelector(`[data-customer-id="${customerId}"]`);
        if (selectedItem) {
          selectedItem.classList.add('active');
        }
      }

      // Load detailed customer data
      const response = await this.makeAimeosRequest(`/jsonapi/customer?id=${customerId}`, {
        method: 'GET',
        params: {
          'include': 'customer/address,customer/property,customer/review'
        }
      });

      if (response.data) {
        this.selectedCustomer = response.data;
        this.renderCustomerDetails();
      }
    } catch (error) {
      console.error('Error loading customer details:', error);
      this.showError('Failed to load customer details.');
    }
  }

  private renderCustomerDetails(): void {
    const detailsPanel = document.getElementById('customer-details-panel');
    if (!detailsPanel || !this.selectedCustomer) return;

    const attrs = this.selectedCustomer.attributes;
    const name = `${attrs['customer.firstname'] || ''} ${attrs['customer.lastname'] || ''}`.trim();
    const company = attrs['customer.company'];

    detailsPanel.innerHTML = `
      <div class="contact-content-wrapper">
        <!-- Customer Header -->
        <div class="client-header">
          <div class="contact-info-with-id">
            <div class="client-id-with-online-status">
              <div class="client-id">${name || company || 'Unnamed Customer'}</div>
              <div class="online-indicator"></div>
            </div>
            <div class="client-meta">
              <span>ID: ${attrs['customer.id']}</span>
              <span class="separator">•</span>
              <span>Registered: ${new Date(attrs['customer.ctime']).toLocaleDateString()}</span>
            </div>
          </div>
        </div>

        <!-- Customer Details -->
        <div class="contact-details-container">
          <div class="detail-section">
            <div class="detail-section-header">
              <i class="fas fa-user"></i>
              <h3>Personal Information</h3>
            </div>
            <div class="detail-value">
              <strong>Full Name:</strong> ${name || 'Not provided'}<br>
              <strong>Company:</strong> ${company || 'Not provided'}<br>
              <strong>Title:</strong> ${attrs['customer.title'] || 'Not provided'}<br>
              <strong>Salutation:</strong> ${attrs['customer.salutation'] || 'Not provided'}
            </div>
          </div>

          <div class="detail-section">
            <div class="detail-section-header">
              <i class="fas fa-envelope"></i>
              <h3>Contact Information</h3>
            </div>
            <div class="detail-value">
              <strong>Email:</strong> ${attrs['customer.email'] || 'Not provided'}<br>
              <strong>Phone:</strong> ${attrs['customer.telephone'] || 'Not provided'}<br>
              <strong>Fax:</strong> ${attrs['customer.telefax'] || 'Not provided'}<br>
              <strong>Website:</strong> ${attrs['customer.website'] || 'Not provided'}
            </div>
          </div>

          <div class="detail-section">
            <div class="detail-section-header">
              <i class="fas fa-map-marker-alt"></i>
              <h3>Address</h3>
            </div>
            <div class="detail-value">
              ${this.formatAddress(attrs)}
            </div>
          </div>

          <div class="detail-section">
            <div class="detail-section-header">
              <i class="fas fa-info-circle"></i>
              <h3>Additional Details</h3>
            </div>
            <div class="detail-value">
              <strong>VAT ID:</strong> ${attrs['customer.vatid'] || 'Not provided'}<br>
              <strong>Birthday:</strong> ${attrs['customer.birthday'] || 'Not provided'}<br>
              <strong>Language:</strong> ${attrs['customer.languageid'] || 'Not provided'}<br>
              <strong>Country:</strong> ${attrs['customer.countryid'] || 'Not provided'}
            </div>
          </div>
        </div>

        <!-- Financial Overview -->
        <div class="client-financial-section">
          <div class="financial-block">
            <div class="financial-icon">
              <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="financial-details">
              <div class="financial-amount">12</div>
              <div class="financial-label">Total Orders</div>
            </div>
          </div>
          <div class="financial-block">
            <div class="financial-icon">
              <i class="fas fa-euro-sign"></i>
            </div>
            <div class="financial-details">
              <div class="financial-amount">€2,847.50</div>
              <div class="financial-label">Total Spent</div>
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
              <div class="invoice-text-title">Edit Customer</div>
              <div class="invoice-text">Update customer information and settings</div>
            </div>
          </div>
          <button class="invoice-button-text edit-customer-btn" data-customer-id="${this.selectedCustomer.id}">
            Edit <i class="fas fa-arrow-right"></i>
          </button>
        </div>

        <div class="generate-shipping">
          <div class="shipping-header">
            <div class="shipping-icon">
              <i class="fas fa-history"></i>
            </div>
            <div>
              <div class="shipping-text-title">Order History</div>
              <div class="shipping-text">View all orders and purchase history</div>
            </div>
          </div>
          <button class="shipping-button-text view-orders-btn" data-customer-id="${this.selectedCustomer.id}">
            View Orders <i class="fas fa-arrow-right"></i>
          </button>
        </div>
      </div>
    `;
  }

  private formatAddress(attrs: any): string {
    const parts = [
      attrs['customer.address1'],
      attrs['customer.address2'],
      attrs['customer.address3']
    ].filter(Boolean);
    
    const cityLine = [
      attrs['customer.postal'],
      attrs['customer.city'],
      attrs['customer.state']
    ].filter(Boolean).join(' ');
    
    if (cityLine) parts.push(cityLine);
    if (attrs['customer.countryid']) parts.push(attrs['customer.countryid']);
    
    return parts.length ? parts.join('<br>') : 'No address provided';
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
    const totalElement = document.getElementById('total-customers');
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

  private showAddCustomerModal(): void {
    // Implementation for add customer modal
    console.log('Show add customer modal');
  }

  private editCustomer(): void {
    if (this.selectedCustomer) {
      console.log('Edit customer:', this.selectedCustomer.id);
    }
  }

  private async deleteCustomer(): Promise<void> {
    if (this.selectedCustomer && confirm('Are you sure you want to delete this customer?')) {
      try {
        await this.makeAimeosRequest(`/jsonapi/customer?id=${this.selectedCustomer.id}`, {
          method: 'DELETE'
        });
        this.loadCustomers();
        this.selectedCustomer = null;
        const detailsPanel = document.getElementById('customer-details-panel');
        if (detailsPanel) {
          detailsPanel.innerHTML = `
            <div style="text-align: center; padding: 40px; color: #8A8A9E;">
              <i class="fas fa-user-circle" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
              <p>Select a customer to view details</p>
            </div>
          `;
        }
      } catch (error) {
        this.showError('Failed to delete customer.');
      }
    }
  }

  private viewCustomerOrders(): void {
    if (this.selectedCustomer) {
      console.log('View orders for customer:', this.selectedCustomer.id);
      // This could open the Orders Manager app with a filter for this customer
    }
  }

  private exportCustomers(): void {
    console.log('Export customers');
    // Implementation for CSV/Excel export
  }
}

export default ContactsApp;