// Orders Manager Application - Comprehensive Aimeos order management
import { BaseApp } from './BaseApp';
import type { App } from '../Core/Types';

interface AimeosOrder {
  id: string;
  attributes: {
    'order.id': string;
    'order.type': string;
    'order.currencyid': string;
    'order.price': string;
    'order.costs': string;
    'order.rebate': string;
    'order.tax': string;
    'order.taxflag': number;
    'order.statusdelivery': number;
    'order.statuspayment': number;
    'order.datepayment': string;
    'order.datedelivery': string;
    'order.relatedid': string;
    'order.comment': string;
    'order.customerid': string;
    'order.customerref': string;
    'order.ctime': string;
    'order.mtime': string;
    'order.editor': string;
  };
  relationships?: {
    customer?: Record<string, unknown>;
    address?: Record<string, unknown>[];
    service?: Record<string, unknown>[];
    product?: Record<string, unknown>[];
    coupon?: Record<string, unknown>[];
  };
}

interface AimeosCustomer {
  id: string;
  attributes: {
    'customer.id': string;
    'customer.code': string;
    'customer.label': string;
    'customer.firstname': string;
    'customer.lastname': string;
    'customer.email': string;
    'customer.company': string;
  };
}

interface OrderStats {
  totalOrders: number;
  totalRevenue: string;
  pendingOrders: number;
  completedOrders: number;
  averageOrderValue: string;
}

export class OrdersManagerApp extends BaseApp {
  private orders: AimeosOrder[] = [];
  private selectedOrder: AimeosOrder | null = null;
  private currentPage = 1;
  private totalPages = 1;
  private searchQuery = '';
  private statusFilter = '';
  private paymentStatusFilter = '';
  private deliveryStatusFilter = '';
  private dateFromFilter = '';
  private dateToFilter = '';
  private orderStats: OrderStats | null = null;

  constructor() {
    const appInfo: App = {
      id: 'orders-manager',
      name: 'Orders Manager',
      icon: 'fas fa-shopping-cart',
      iconType: 'fontawesome',
      iconBackground: 'green-icon',
      component: 'OrdersManagerApp',
      category: 'ecommerce',
      permissions: ['orders.view', 'orders.edit', 'orders.create', 'orders.delete'],
      installed: true,
      system: false,
      teamScoped: true,
      version: '1.0.0',
      description: 'Manage customer orders, payments, and shipping',
      author: 'System'
    };
    
    super('orders-manager', appInfo);
  }

  protected async render(): Promise<void> {
    if (!this.context) return;
    
    this.context.contentElement.innerHTML = this.createContent();
    this.setupOrdersEventListeners();
    this.loadOrders();
    this.loadOrderStats();
  }

  private createContent(): string {
    return `
      <div class="orders-manager-app">
        <!-- Mobile profile top bar -->
        <div class="mobile-profile-top-bar">
          <div class="mobile-profile-top-bar-header">
            <div class="mobile-profile-top-bar-header-left">
              <img src="img/avatar.png" alt="Site Logo" class="site-logo-round">
              <span>Orders Manager</span>
            </div>
            <div class="mobile-profile-header-right">
              <button class="toolbar-search-btn" title="Search"><i class="fas fa-search"></i></button>
            </div>
          </div>
        </div>

        <div class="product-edit-container">
          <!-- Page Header -->
          <div class="page-header">
            <h1>Order Management</h1>
            <div class="header-actions">
              <button class="btn btn-secondary export-orders-btn">
                <i class="fas fa-download"></i> Export
              </button>
              <button class="btn btn-secondary bulk-actions-btn">
                <i class="fas fa-tasks"></i> Bulk Actions
              </button>
              <button class="btn btn-primary create-order-btn">
                <i class="fas fa-plus"></i> Create Order
              </button>
            </div>
          </div>

          <!-- Two Column Layout -->
          <div class="product-content-columns">
            <!-- Left Column - Orders List -->
            <div class="product-main-column">
              <!-- Order Statistics -->
              <div class="client-financial-section" style="margin-bottom: 40px;">
                <div class="financial-block">
                  <div class="financial-icon">
                    <i class="fas fa-shopping-cart"></i>
                  </div>
                  <div class="financial-details">
                    <div class="financial-amount" id="total-orders-stat">0</div>
                    <div class="financial-label">Total Orders</div>
                  </div>
                </div>
                <div class="financial-block">
                  <div class="financial-icon">
                    <i class="fas fa-euro-sign"></i>
                  </div>
                  <div class="financial-details">
                    <div class="financial-amount" id="total-revenue-stat">€0.00</div>
                    <div class="financial-label">Total Revenue</div>
                  </div>
                </div>
                <div class="financial-block">
                  <div class="financial-icon">
                    <i class="fas fa-clock"></i>
                  </div>
                  <div class="financial-details">
                    <div class="financial-amount" id="pending-orders-stat">0</div>
                    <div class="financial-label">Pending Orders</div>
                  </div>
                </div>
                <div class="financial-block">
                  <div class="financial-icon">
                    <i class="fas fa-check-circle"></i>
                  </div>
                  <div class="financial-details">
                    <div class="financial-amount" id="avg-order-value-stat">€0.00</div>
                    <div class="financial-label">Avg Order Value</div>
                  </div>
                </div>
              </div>

              <div class="product-section">
                <div class="section-header-with-link">
                  <h2><i class="fas fa-receipt"></i> Orders Directory</h2>
                  <div class="order-actions">
                    <button class="btn-link sync-orders-btn">
                      <i class="fas fa-sync"></i> Sync with Aimeos
                    </button>
                  </div>
                </div>
                
                <!-- Search and Filters -->
                <div class="form-row">
                  <div class="form-group half">
                    <label>Search Orders</label>
                    <div class="input-group">
                      <div class="input-group-prepend">
                        <i class="fas fa-search"></i>
                      </div>
                      <input type="text" class="form-control order-search" placeholder="Search by order ID, customer name, or email...">
                    </div>
                  </div>
                  <div class="form-group half">
                    <label>Payment Status</label>
                    <div class="select-container">
                      <select class="form-control payment-status-filter">
                        <option value="">All Payment Status</option>
                        <option value="-1">Unfinished</option>
                        <option value="0">Deleted</option>
                        <option value="1">Pending</option>
                        <option value="2">Progress</option>
                        <option value="3">Authorized</option>
                        <option value="4">Received</option>
                        <option value="5">Refused</option>
                        <option value="6">Refund</option>
                        <option value="7">Cancelled</option>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="form-row">
                  <div class="form-group half">
                    <label>Delivery Status</label>
                    <div class="select-container">
                      <select class="form-control delivery-status-filter">
                        <option value="">All Delivery Status</option>
                        <option value="-1">Unfinished</option>
                        <option value="0">Deleted</option>
                        <option value="1">Pending</option>
                        <option value="2">Progress</option>
                        <option value="3">Dispatched</option>
                        <option value="4">Delivered</option>
                        <option value="5">Lost</option>
                        <option value="6">Refused</option>
                        <option value="7">Returned</option>
                      </select>
                    </div>
                  </div>
                  <div class="form-group half">
                    <label>Date Range</label>
                    <div class="form-row-top-input">
                      <input type="date" class="form-control date-from-filter" style="width: 48%;">
                      <input type="date" class="form-control date-to-filter" style="width: 48%;">
                    </div>
                  </div>
                </div>

                <!-- Orders List -->
                <div class="order-list-container">
                  <div class="contacts-oder-list-header-item">
                    <div class="order-id">Order ID</div>
                    <div class="order-date">Date</div>
                    <div class="order-type">Customer</div>
                    <div class="order-payment">Payment</div>
                    <div class="order-shipping">Delivery</div>
                    <div class="order-amount">Total</div>
                    <div class="order-actions">Actions</div>
                  </div>
                  
                  <div class="orders-list" id="orders-list">
                    <div class="loading-state" style="text-align: center; padding: 40px;">
                      <i class="fas fa-spinner fa-spin"></i> Loading orders...
                    </div>
                  </div>
                </div>

                <!-- Pagination -->
                <div class="footer-pagination">
                  <div class="total-label">
                    <span class="total-amount" id="total-orders">0</span> orders found
                  </div>
                  <div class="pagination" id="pagination-controls">
                    <!-- Pagination will be dynamically generated -->
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column - Order Details -->
            <div class="product-product-publish-column">
              <div class="sidebar-title">Order Details</div>
              
              <div id="order-details-panel" class="order-details-empty" style="text-align: center; padding: 40px; color: #8A8A9E;">
                <i class="fas fa-receipt" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                <p>Select an order to view details</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  private setupOrdersEventListeners(): void {
    if (!this.context) return;
    
    const container = this.context.contentElement;

    // Search functionality
    const searchInput = container.querySelector('.order-search') as HTMLInputElement;
    if (searchInput) {
      let searchTimeout: number;
      this.addEventListener(searchInput, 'input', (e: Event) => {
        clearTimeout(searchTimeout);
        const query = (e.target as HTMLInputElement).value;
        searchTimeout = window.setTimeout(() => {
          this.searchQuery = query;
          this.currentPage = 1;
          this.loadOrders();
        }, 300);
      });
    }

    // Payment status filter
    const paymentStatusFilter = container.querySelector('.payment-status-filter') as HTMLSelectElement;
    if (paymentStatusFilter) {
      this.addEventListener(paymentStatusFilter, 'change', (e: Event) => {
        this.paymentStatusFilter = (e.target as HTMLSelectElement).value;
        this.currentPage = 1;
        this.loadOrders();
      });
    }

    // Delivery status filter
    const deliveryStatusFilter = container.querySelector('.delivery-status-filter') as HTMLSelectElement;
    if (deliveryStatusFilter) {
      this.addEventListener(deliveryStatusFilter, 'change', (e: Event) => {
        this.deliveryStatusFilter = (e.target as HTMLSelectElement).value;
        this.currentPage = 1;
        this.loadOrders();
      });
    }

    // Date filters
    const dateFromFilter = container.querySelector('.date-from-filter') as HTMLInputElement;
    const dateToFilter = container.querySelector('.date-to-filter') as HTMLInputElement;
    
    if (dateFromFilter) {
      this.addEventListener(dateFromFilter, 'change', (e: Event) => {
        this.dateFromFilter = (e.target as HTMLInputElement).value;
        this.currentPage = 1;
        this.loadOrders();
      });
    }

    if (dateToFilter) {
      this.addEventListener(dateToFilter, 'change', (e: Event) => {
        this.dateToFilter = (e.target as HTMLInputElement).value;
        this.currentPage = 1;
        this.loadOrders();
      });
    }

    // Button handlers
    this.addEventListener(container, 'click', (e: Event) => {
      const target = e.target as HTMLElement;
      
      if (target.closest('.create-order-btn')) {
        this.showCreateOrderModal();
      } else if (target.closest('.sync-orders-btn')) {
        this.loadOrders();
        this.loadOrderStats();
      } else if (target.closest('.export-orders-btn')) {
        this.exportOrders();
      } else if (target.closest('.bulk-actions-btn')) {
        this.showBulkActionsModal();
      } else if (target.closest('.view-order-btn')) {
        this.viewOrder();
      } else if (target.closest('.edit-order-btn')) {
        this.editOrder();
      } else if (target.closest('.update-status-btn')) {
        this.updateOrderStatus();
      } else if (target.closest('.order-item')) {
        const orderId = target.closest('.order-item')?.getAttribute('data-order-id');
        if (orderId) {
          this.selectOrder(orderId);
        }
      } else if (target.closest('.pagination-item')) {
        const page = parseInt(target.getAttribute('data-page') || '1');
        this.currentPage = page;
        this.loadOrders();
      }
    });
  }

  private async loadOrders(): Promise<void> {
    try {
      const response = await this.makeAimeosRequest('/jsonapi/order', {
        method: 'GET',
        params: {
          'page[offset]': (this.currentPage - 1) * 20,
          'page[limit]': 20,
          'include': 'order/address,order/service,order/product',
          'sort': '-order.ctime',
          ...(this.searchQuery && {
            'filter[order.id]': `*${this.searchQuery}*`,
            'filter[order.customerref]': `*${this.searchQuery}*`
          }),
          ...(this.paymentStatusFilter && {
            'filter[order.statuspayment]': this.paymentStatusFilter
          }),
          ...(this.deliveryStatusFilter && {
            'filter[order.statusdelivery]': this.deliveryStatusFilter
          }),
          ...(this.dateFromFilter && {
            'filter[order.ctime][>=]': this.dateFromFilter
          }),
          ...(this.dateToFilter && {
            'filter[order.ctime][<=]': this.dateToFilter + ' 23:59:59'
          })
        }
      });

      if (response.data) {
        this.orders = Array.isArray(response.data) ? response.data : [response.data];
        this.totalPages = Math.ceil((response.meta?.total || this.orders.length) / 20);
        this.renderOrdersList();
        this.renderPagination();
        this.updateTotalCount(response.meta?.total || this.orders.length);
      }
    } catch (error) {
      console.error('Error loading orders:', error);
      this.showError('Failed to load orders. Please try again.');
    }
  }

  private async loadOrderStats(): Promise<void> {
    try {
      // This would be a custom endpoint that aggregates order statistics
      const response = await this.makeAimeosRequest('/admin/orders/stats', {
        method: 'GET'
      });

      if (response.data) {
        this.orderStats = response.data;
        this.updateStatsDisplay();
      }
    } catch (error) {
      console.error('Error loading order stats:', error);
      // Set default values if stats can't be loaded
      this.orderStats = {
        totalOrders: this.orders.length,
        totalRevenue: '€0.00',
        pendingOrders: 0,
        completedOrders: 0,
        averageOrderValue: '€0.00'
      };
      this.updateStatsDisplay();
    }
  }

  private updateStatsDisplay(): void {
    if (!this.orderStats) return;

    const totalOrdersElement = document.getElementById('total-orders-stat');
    const totalRevenueElement = document.getElementById('total-revenue-stat');
    const pendingOrdersElement = document.getElementById('pending-orders-stat');
    const avgOrderValueElement = document.getElementById('avg-order-value-stat');

    if (totalOrdersElement) totalOrdersElement.textContent = this.orderStats.totalOrders.toString();
    if (totalRevenueElement) totalRevenueElement.textContent = this.orderStats.totalRevenue;
    if (pendingOrdersElement) pendingOrdersElement.textContent = this.orderStats.pendingOrders.toString();
    if (avgOrderValueElement) avgOrderValueElement.textContent = this.orderStats.averageOrderValue;
  }

  private renderOrdersList(): void {
    const listContainer = document.getElementById('orders-list');
    if (!listContainer) return;

    if (this.orders.length === 0) {
      listContainer.innerHTML = `
        <div style="text-align: center; padding: 40px; color: #8A8A9E;">
          <i class="fas fa-receipt" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
          <p>No orders found</p>
          ${this.searchQuery ? '<button class="btn-link" onclick="this.clearSearch()">Clear search</button>' : ''}
        </div>
      `;
      return;
    }

    listContainer.innerHTML = this.orders.map(order => {
      const attrs = order.attributes;
      const orderId = attrs['order.id'];
      const orderDate = new Date(attrs['order.ctime']).toLocaleDateString();
      const customerInfo = this.getCustomerInfo(order);
      const paymentStatus = this.getPaymentStatusBadge(attrs['order.statuspayment']);
      const deliveryStatus = this.getDeliveryStatusBadge(attrs['order.statusdelivery']);
      const total = this.formatPrice(attrs['order.price'], attrs['order.currencyid']);
      
      return `
        <div class="order-item" data-order-id="${order.id}">
          <div class="order-id">
            <strong>#${orderId}</strong>
            <i class="fas fa-external-link-alt"></i>
          </div>
          <div class="order-date">${orderDate}</div>
          <div class="order-type">${customerInfo}</div>
          <div class="order-payment">${paymentStatus}</div>
          <div class="order-shipping">${deliveryStatus}</div>
          <div class="order-amount">${total}</div>
          <div class="order-actions">
            <button class="btn-link view-order-btn" data-order-id="${order.id}" title="View">
              <i class="fas fa-eye"></i>
            </button>
            <button class="btn-link edit-order-btn" data-order-id="${order.id}" title="Edit">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn-link update-status-btn" data-order-id="${order.id}" title="Update Status">
              <i class="fas fa-sync"></i>
            </button>
          </div>
        </div>
      `;
    }).join('');
  }

  private getCustomerInfo(order: AimeosOrder): string {
    const customerRef = order.attributes['order.customerref'];
    const customerId = order.attributes['order.customerid'];
    
    if (customerRef) {
      return customerRef;
    } else if (customerId) {
      return `Customer #${customerId}`;
    } else {
      return 'Guest Customer';
    }
  }

  private getPaymentStatusBadge(status: number): string {
    const statusMap = {
      [-1]: { label: 'Unfinished', class: 'status-draft' },
      [0]: { label: 'Deleted', class: 'status-draft' },
      [1]: { label: 'Pending', class: 'status-pending' },
      [2]: { label: 'Progress', class: 'status-pending' },
      [3]: { label: 'Authorized', class: 'status-pending' },
      [4]: { label: 'Received', class: 'status-completed' },
      [5]: { label: 'Refused', class: 'status-pending' },
      [6]: { label: 'Refund', class: 'status-pending' },
      [7]: { label: 'Cancelled', class: 'status-draft' }
    };
    
    const statusInfo = statusMap[status as keyof typeof statusMap] || { label: 'Unknown', class: 'status-draft' };
    return `<span class="order-status ${statusInfo.class}">${statusInfo.label}</span>`;
  }

  private getDeliveryStatusBadge(status: number): string {
    const statusMap = {
      [-1]: { label: 'Unfinished', class: 'status-draft' },
      [0]: { label: 'Deleted', class: 'status-draft' },
      [1]: { label: 'Pending', class: 'status-pending' },
      [2]: { label: 'Progress', class: 'status-pending' },
      [3]: { label: 'Dispatched', class: 'status-pending' },
      [4]: { label: 'Delivered', class: 'status-completed' },
      [5]: { label: 'Lost', class: 'status-pending' },
      [6]: { label: 'Refused', class: 'status-pending' },
      [7]: { label: 'Returned', class: 'status-draft' }
    };
    
    const statusInfo = statusMap[status as keyof typeof statusMap] || { label: 'Unknown', class: 'status-draft' };
    return `<span class="order-status ${statusInfo.class}">${statusInfo.label}</span>`;
  }

  private formatPrice(price: string, currency: string): string {
    const formatter = new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency || 'EUR'
    });
    return formatter.format(parseFloat(price));
  }

  private async selectOrder(orderId: string): Promise<void> {
    try {
      // Highlight selected order
      const container = this.context?.contentElement;
      if (container) {
        container.querySelectorAll('.order-item').forEach((item: Element) => {
          item.classList.remove('active');
        });
        const selectedItem = container.querySelector(`[data-order-id="${orderId}"]`);
        if (selectedItem) {
          selectedItem.classList.add('active');
        }
      }

      // Load detailed order data
      const response = await this.makeAimeosRequest(`/jsonapi/order?id=${orderId}`, {
        method: 'GET',
        params: {
          'include': 'order/address,order/service,order/product,order/coupon'
        }
      });

      if (response.data) {
        this.selectedOrder = response.data;
        this.renderOrderDetails();
      }
    } catch (error) {
      console.error('Error loading order details:', error);
      this.showError('Failed to load order details.');
    }
  }

  private renderOrderDetails(): void {
    const detailsPanel = document.getElementById('order-details-panel');
    if (!detailsPanel || !this.selectedOrder) return;

    const attrs = this.selectedOrder.attributes;
    const orderId = attrs['order.id'];
    const customerInfo = this.getCustomerInfo(this.selectedOrder);

    detailsPanel.innerHTML = `
      <div class="contact-content-wrapper">
        <!-- Order Header -->
        <div class="client-header">
          <div class="contact-info-with-id">
            <div class="client-id-with-online-status">
              <div class="client-id">Order #${orderId}</div>
              <div class="online-indicator" style="background: ${this.getOrderStatusColor(attrs['order.statuspayment'])}"></div>
            </div>
            <div class="client-meta">
              <span>Customer: ${customerInfo}</span>
              <span class="separator">•</span>
              <span>Created: ${new Date(attrs['order.ctime']).toLocaleDateString()}</span>
            </div>
          </div>
        </div>

        <!-- Order Details -->
        <div class="order-details-container">
          <div class="detail-section">
            <div class="detail-section-header">
              <i class="fas fa-info-circle"></i>
              <h3>Order Information</h3>
            </div>
            <div class="detail-value">
              <strong>Order ID:</strong> #${orderId}<br>
              <strong>Type:</strong> ${attrs['order.type']}<br>
              <strong>Currency:</strong> ${attrs['order.currencyid']}<br>
              <strong>Customer Ref:</strong> ${attrs['order.customerref'] || 'N/A'}
            </div>
          </div>

          <div class="detail-section">
            <div class="detail-section-header">
              <i class="fas fa-euro-sign"></i>
              <h3>Order Totals</h3>
            </div>
            <div class="detail-value">
              <strong>Subtotal:</strong> ${this.formatPrice(attrs['order.price'], attrs['order.currencyid'])}<br>
              <strong>Costs:</strong> ${this.formatPrice(attrs['order.costs'] || '0', attrs['order.currencyid'])}<br>
              <strong>Tax:</strong> ${this.formatPrice(attrs['order.tax'] || '0', attrs['order.currencyid'])}<br>
              <strong>Rebate:</strong> ${this.formatPrice(attrs['order.rebate'] || '0', attrs['order.currencyid'])}
            </div>
          </div>

          <div class="detail-section">
            <div class="detail-section-header">
              <i class="fas fa-truck"></i>
              <h3>Status Information</h3>
            </div>
            <div class="detail-value">
              <strong>Payment Status:</strong> ${this.getPaymentStatusText(attrs['order.statuspayment'])}<br>
              <strong>Delivery Status:</strong> ${this.getDeliveryStatusText(attrs['order.statusdelivery'])}<br>
              <strong>Payment Date:</strong> ${attrs['order.datepayment'] || 'Not paid'}<br>
              <strong>Delivery Date:</strong> ${attrs['order.datedelivery'] || 'Not delivered'}
            </div>
          </div>

          <div class="detail-section">
            <div class="detail-section-header">
              <i class="fas fa-clock"></i>
              <h3>Timestamps</h3>
            </div>
            <div class="detail-value">
              <strong>Created:</strong> ${new Date(attrs['order.ctime']).toLocaleDateString()}<br>
              <strong>Modified:</strong> ${new Date(attrs['order.mtime']).toLocaleDateString()}<br>
              <strong>Editor:</strong> ${attrs['order.editor']}
            </div>
          </div>

          ${attrs['order.comment'] ? `
          <div class="detail-section">
            <div class="detail-section-header">
              <i class="fas fa-comment"></i>
              <h3>Comments</h3>
            </div>
            <div class="detail-value">
              ${attrs['order.comment']}
            </div>
          </div>
          ` : ''}
        </div>

        <!-- Quick Actions -->
        <div class="generate-invoice">
          <div class="invoice-header">
            <div class="invoice-icon">
              <i class="fas fa-file-invoice"></i>
            </div>
            <div>
              <div class="invoice-text-title">Generate Invoice</div>
              <div class="invoice-text">Create and download invoice for this order</div>
            </div>
          </div>
          <button class="invoice-button-text generate-invoice-btn" data-order-id="${this.selectedOrder.id}">
            Generate <i class="fas fa-arrow-right"></i>
          </button>
        </div>

        <div class="generate-shipping">
          <div class="shipping-header">
            <div class="shipping-icon">
              <i class="fas fa-shipping-fast"></i>
            </div>
            <div>
              <div class="shipping-text-title">Update Status</div>
              <div class="shipping-text">Change payment or delivery status</div>
            </div>
          </div>
          <button class="shipping-button-text update-status-btn" data-order-id="${this.selectedOrder.id}">
            Update <i class="fas fa-arrow-right"></i>
          </button>
        </div>
      </div>
    `;
  }

  private getOrderStatusColor(paymentStatus: number): string {
    if (paymentStatus === 4) return '#4CAF50'; // Received - Green
    if (paymentStatus === 1 || paymentStatus === 2 || paymentStatus === 3) return '#FFC107'; // Pending states - Yellow
    return '#e57373'; // Error states - Red
  }

  private getPaymentStatusText(status: number): string {
    const statusMap = {
      [-1]: 'Unfinished',
      [0]: 'Deleted',
      [1]: 'Pending',
      [2]: 'Progress',
      [3]: 'Authorized',
      [4]: 'Received',
      [5]: 'Refused',
      [6]: 'Refund',
      [7]: 'Cancelled'
    };
    return statusMap[status as keyof typeof statusMap] || 'Unknown';
  }

  private getDeliveryStatusText(status: number): string {
    const statusMap = {
      [-1]: 'Unfinished',
      [0]: 'Deleted',
      [1]: 'Pending',
      [2]: 'Progress',
      [3]: 'Dispatched',
      [4]: 'Delivered',
      [5]: 'Lost',
      [6]: 'Refused',
      [7]: 'Returned'
    };
    return statusMap[status as keyof typeof statusMap] || 'Unknown';
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
    const totalElement = document.getElementById('total-orders');
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

  private showCreateOrderModal(): void {
    console.log('Show create order modal');
    // Implementation for create order modal
  }

  private viewOrder(): void {
    if (this.selectedOrder) {
      console.log('View order:', this.selectedOrder.id);
      // Implementation to open detailed order view
    }
  }

  private editOrder(): void {
    if (this.selectedOrder) {
      console.log('Edit order:', this.selectedOrder.id);
      // Implementation for order editing
    }
  }

  private updateOrderStatus(): void {
    if (this.selectedOrder) {
      console.log('Update order status:', this.selectedOrder.id);
      // Implementation for status update modal
    }
  }

  private showBulkActionsModal(): void {
    console.log('Show bulk actions modal');
    // Implementation for bulk operations
  }

  private exportOrders(): void {
    console.log('Export orders');
    // Implementation for CSV/Excel export
  }
}

export default OrdersManagerApp;