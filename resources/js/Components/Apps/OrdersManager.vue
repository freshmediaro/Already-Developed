<template>
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
          <button class="btn btn-secondary" @click="exportOrders">
            <i class="fas fa-download"></i> Export
          </button>
          <button class="btn btn-secondary" @click="showBulkActions">
            <i class="fas fa-tasks"></i> Bulk Actions
          </button>
          <button class="btn btn-primary" @click="showCreateOrder">
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
                <div class="financial-amount">{{ orderStats.totalOrders }}</div>
                <div class="financial-label">Total Orders</div>
              </div>
            </div>
            <div class="financial-block">
              <div class="financial-icon">
                <i class="fas fa-euro-sign"></i>
              </div>
              <div class="financial-details">
                <div class="financial-amount">{{ orderStats.totalRevenue }}</div>
                <div class="financial-label">Total Revenue</div>
              </div>
            </div>
            <div class="financial-block">
              <div class="financial-icon">
                <i class="fas fa-clock"></i>
              </div>
              <div class="financial-details">
                <div class="financial-amount">{{ orderStats.pendingOrders }}</div>
                <div class="financial-label">Pending Orders</div>
              </div>
            </div>
            <div class="financial-block">
              <div class="financial-icon">
                <i class="fas fa-check-circle"></i>
              </div>
              <div class="financial-details">
                <div class="financial-amount">{{ orderStats.averageOrderValue }}</div>
                <div class="financial-label">Avg Order Value</div>
              </div>
            </div>
          </div>

          <div class="product-section">
            <div class="section-header-with-link">
              <h2><i class="fas fa-receipt"></i> Orders Directory</h2>
              <div class="order-actions">
                <button class="btn-link" @click="syncOrders">
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
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="searchQuery"
                    @input="debouncedSearch"
                    placeholder="Search by order ID, customer name, or email..."
                  >
                </div>
              </div>
              <div class="form-group half">
                <label>Payment Status</label>
                <div class="select-container">
                  <select 
                    class="form-control" 
                    v-model="paymentStatusFilter"
                    @change="loadOrders"
                  >
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
                  <select 
                    class="form-control" 
                    v-model="deliveryStatusFilter"
                    @change="loadOrders"
                  >
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
                  <input 
                    type="date" 
                    class="form-control" 
                    v-model="dateFromFilter"
                    @change="loadOrders"
                    style="width: 48%;"
                  >
                  <input 
                    type="date" 
                    class="form-control" 
                    v-model="dateToFilter"
                    @change="loadOrders"
                    style="width: 48%;"
                  >
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
              
              <div class="orders-list">
                <div v-if="loading" class="loading-state" style="text-align: center; padding: 40px;">
                  <i class="fas fa-spinner fa-spin"></i> Loading orders...
                </div>
                
                <div v-else-if="orders.length === 0" style="text-align: center; padding: 40px; color: #8A8A9E;">
                  <i class="fas fa-receipt" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                  <p>No orders found</p>
                  <button v-if="searchQuery" class="btn-link" @click="clearSearch">Clear search</button>
                </div>

                <div 
                  v-else
                  v-for="order in orders" 
                  :key="order.id"
                  class="order-item"
                  :class="{ active: selectedOrder?.id === order.id }"
                  @click="selectOrder(order)"
                >
                  <div class="order-id">
                    <strong>#{{ order.attributes['order.id'] }}</strong>
                    <i class="fas fa-external-link-alt"></i>
                  </div>
                  <div class="order-date">
                    {{ formatDate(order.attributes['order.ctime']) }}
                  </div>
                  <div class="order-type">
                    {{ getCustomerInfo(order) }}
                  </div>
                  <div class="order-payment">
                    <span :class="getPaymentStatusClass(order.attributes['order.statuspayment'])">
                      {{ getPaymentStatusText(order.attributes['order.statuspayment']) }}
                    </span>
                  </div>
                  <div class="order-shipping">
                    <span :class="getDeliveryStatusClass(order.attributes['order.statusdelivery'])">
                      {{ getDeliveryStatusText(order.attributes['order.statusdelivery']) }}
                    </span>
                  </div>
                  <div class="order-amount">
                    {{ formatPrice(order.attributes['order.price'], order.attributes['order.currencyid']) }}
                  </div>
                  <div class="order-actions">
                    <button class="btn-link" @click.stop="viewOrder(order)" title="View">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-link" @click.stop="editOrder(order)" title="Edit">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-link" @click.stop="updateOrderStatus(order)" title="Update Status">
                      <i class="fas fa-sync"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div class="footer-pagination">
              <div class="total-label">
                <span class="total-amount">{{ totalOrders }}</span> orders found
              </div>
              <div class="pagination">
                <div 
                  class="pagination-item"
                  :class="{ disabled: currentPage === 1 }"
                  @click="changePage(currentPage - 1)"
                >
                  <i class="fas fa-chevron-left"></i>
                </div>
                
                <div 
                  v-for="page in paginationPages"
                  :key="page"
                  class="pagination-item"
                  :class="{ active: page === currentPage }"
                  @click="changePage(page)"
                >
                  {{ page }}
                </div>
                
                <div 
                  class="pagination-item"
                  :class="{ disabled: currentPage === totalPages }"
                  @click="changePage(currentPage + 1)"
                >
                  <i class="fas fa-chevron-right"></i>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Order Details -->
        <div class="product-product-publish-column">
          <div class="sidebar-title">Order Details</div>
          
          <div v-if="!selectedOrder" class="order-details-empty" style="text-align: center; padding: 40px; color: #8A8A9E;">
            <i class="fas fa-receipt" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
            <p>Select an order to view details</p>
          </div>

          <div v-else class="contact-content-wrapper">
            <!-- Order Header -->
            <div class="client-header">
              <div class="contact-info-with-id">
                <div class="client-id-with-online-status">
                  <div class="client-id">Order #{{ selectedOrder.attributes['order.id'] }}</div>
                  <div class="online-indicator" :style="{ background: getOrderStatusColor(selectedOrder.attributes['order.statuspayment']) }"></div>
                </div>
                <div class="client-meta">
                  <span>Customer: {{ getCustomerInfo(selectedOrder) }}</span>
                  <span class="separator">•</span>
                  <span>Created: {{ formatDate(selectedOrder.attributes['order.ctime']) }}</span>
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
                  <strong>Order ID:</strong> #{{ selectedOrder.attributes['order.id'] }}<br>
                  <strong>Type:</strong> {{ selectedOrder.attributes['order.type'] }}<br>
                  <strong>Currency:</strong> {{ selectedOrder.attributes['order.currencyid'] }}<br>
                  <strong>Customer Ref:</strong> {{ selectedOrder.attributes['order.customerref'] || 'N/A' }}
                </div>
              </div>

              <div class="detail-section">
                <div class="detail-section-header">
                  <i class="fas fa-euro-sign"></i>
                  <h3>Order Totals</h3>
                </div>
                <div class="detail-value">
                  <strong>Subtotal:</strong> {{ formatPrice(selectedOrder.attributes['order.price'], selectedOrder.attributes['order.currencyid']) }}<br>
                  <strong>Costs:</strong> {{ formatPrice(selectedOrder.attributes['order.costs'] || '0', selectedOrder.attributes['order.currencyid']) }}<br>
                  <strong>Tax:</strong> {{ formatPrice(selectedOrder.attributes['order.tax'] || '0', selectedOrder.attributes['order.currencyid']) }}<br>
                  <strong>Rebate:</strong> {{ formatPrice(selectedOrder.attributes['order.rebate'] || '0', selectedOrder.attributes['order.currencyid']) }}
                </div>
              </div>

              <div class="detail-section">
                <div class="detail-section-header">
                  <i class="fas fa-truck"></i>
                  <h3>Status Information</h3>
                </div>
                <div class="detail-value">
                  <strong>Payment Status:</strong> {{ getPaymentStatusText(selectedOrder.attributes['order.statuspayment']) }}<br>
                  <strong>Delivery Status:</strong> {{ getDeliveryStatusText(selectedOrder.attributes['order.statusdelivery']) }}<br>
                  <strong>Payment Date:</strong> {{ selectedOrder.attributes['order.datepayment'] || 'Not paid' }}<br>
                  <strong>Delivery Date:</strong> {{ selectedOrder.attributes['order.datedelivery'] || 'Not delivered' }}
                </div>
              </div>

              <div class="detail-section">
                <div class="detail-section-header">
                  <i class="fas fa-clock"></i>
                  <h3>Timestamps</h3>
                </div>
                <div class="detail-value">
                  <strong>Created:</strong> {{ formatDate(selectedOrder.attributes['order.ctime']) }}<br>
                  <strong>Modified:</strong> {{ formatDate(selectedOrder.attributes['order.mtime']) }}<br>
                  <strong>Editor:</strong> {{ selectedOrder.attributes['order.editor'] }}
                </div>
              </div>

              <div v-if="selectedOrder.attributes['order.comment']" class="detail-section">
                <div class="detail-section-header">
                  <i class="fas fa-comment"></i>
                  <h3>Comments</h3>
                </div>
                <div class="detail-value">
                  {{ selectedOrder.attributes['order.comment'] }}
                </div>
              </div>
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
              <button class="invoice-button-text" @click="generateInvoice(selectedOrder)">
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
              <button class="shipping-button-text" @click="updateOrderStatus(selectedOrder)">
                Update <i class="fas fa-arrow-right"></i>
              </button>
            </div>

            <div class="generate-shipping">
              <div class="shipping-header">
                <div class="shipping-icon">
                  <i class="fas fa-edit"></i>
                </div>
                <div>
                  <div class="shipping-text-title">Edit Order</div>
                  <div class="shipping-text">Modify order details and items</div>
                </div>
              </div>
              <button class="shipping-button-text" @click="editOrder(selectedOrder)">
                Edit Order <i class="fas fa-arrow-right"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { debounce } from 'lodash-es'

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

interface OrderStats {
  totalOrders: number;
  totalRevenue: string;
  pendingOrders: number;
  completedOrders: number;
  averageOrderValue: string;
}

// Reactive state
const orders = ref<AimeosOrder[]>([])
const selectedOrder = ref<AimeosOrder | null>(null)
const loading = ref(false)
const currentPage = ref(1)
const totalPages = ref(1)
const totalOrders = ref(0)
const searchQuery = ref('')
const paymentStatusFilter = ref('')
const deliveryStatusFilter = ref('')
const dateFromFilter = ref('')
const dateToFilter = ref('')

const orderStats = reactive<OrderStats>({
  totalOrders: 0,
  totalRevenue: '€0.00',
  pendingOrders: 0,
  completedOrders: 0,
  averageOrderValue: '€0.00'
})

// Computed properties
const paginationPages = computed(() => {
  const pages = []
  const start = Math.max(1, (currentPage.value || 1) - 2)
  const end = Math.min(totalPages.value || 1, (currentPage.value || 1) + 2)
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

// Debounced search function
const debouncedSearch = debounce(() => {
  currentPage.value = 1
  loadOrders()
}, 300)

// Methods
const loadOrders = async () => {
  loading.value = true
  try {
    const params: any = {
      'page[offset]': ((currentPage.value || 1) - 1) * 20,
      'page[limit]': 20,
      'include': 'order/address,order/service,order/product',
      'sort': '-order.ctime'
    }

    if (searchQuery.value) {
      params['filter[order.id]'] = `*${searchQuery.value}*`
      params['filter[order.customerref]'] = `*${searchQuery.value}*`
    }

    if (paymentStatusFilter.value) {
      params['filter[order.statuspayment]'] = paymentStatusFilter.value
    }

    if (deliveryStatusFilter.value) {
      params['filter[order.statusdelivery]'] = deliveryStatusFilter.value
    }

    if (dateFromFilter.value) {
      params['filter[order.ctime][>=]'] = dateFromFilter.value
    }

    if (dateToFilter.value) {
      params['filter[order.ctime][<=]'] = dateToFilter.value + ' 23:59:59'
    }

    const response = await makeAimeosRequest('/jsonapi/order', {
      method: 'GET',
      params
    })

    if (response && response.data) {
      orders.value = Array.isArray(response.data) ? response.data : [response.data]
      totalPages.value = Math.ceil((response.meta?.total || orders.value?.length || 0) / 20)
      totalOrders.value = response.meta?.total || orders.value?.length || 0
    }
  } catch (error) {
    console.error('Error loading orders:', error)
  } finally {
    loading.value = false
  }
}

const loadOrderStats = async () => {
  try {
    const response = await makeAimeosRequest('/admin/orders/stats', {
      method: 'GET'
    })

    if (response.data) {
      Object.assign(orderStats, response.data)
    }
  } catch (error) {
    console.error('Error loading order stats:', error)
    // Set default values if stats can't be loaded
    orderStats.totalOrders = orders.value?.length || 0
    orderStats.totalRevenue = '€0.00'
    orderStats.pendingOrders = 0
    orderStats.completedOrders = 0
    orderStats.averageOrderValue = '€0.00'
  }
}

const selectOrder = (order: AimeosOrder) => {
  selectedOrder.value = order
}

const changePage = (page: number) => {
  if (page >= 1 && page <= (totalPages.value || 1)) {
    currentPage.value = page
    loadOrders()
  }
}

const clearSearch = () => {
  searchQuery.value = ''
  paymentStatusFilter.value = ''
  deliveryStatusFilter.value = ''
  dateFromFilter.value = ''
  dateToFilter.value = ''
  currentPage.value = 1
  loadOrders()
}

const syncOrders = () => {
  loadOrders()
  loadOrderStats()
}

const getCustomerInfo = (order: AimeosOrder): string => {
  const customerRef = order.attributes['order.customerref']
  const customerId = order.attributes['order.customerid']
  
  if (customerRef) {
    return customerRef
  } else if (customerId) {
    return `Customer #${customerId}`
  } else {
    return 'Guest Customer'
  }
}

const getPaymentStatusText = (status: number): string => {
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
  }
  return statusMap[status as keyof typeof statusMap] || 'Unknown'
}

const getPaymentStatusClass = (status: number): string => {
  const classMap = {
    [-1]: 'status-draft',
    [0]: 'status-draft',
    [1]: 'status-pending',
    [2]: 'status-pending',
    [3]: 'status-pending',
    [4]: 'status-completed',
    [5]: 'status-pending',
    [6]: 'status-pending',
    [7]: 'status-draft'
  }
  return `order-status ${classMap[status as keyof typeof classMap] || 'status-draft'}`
}

const getDeliveryStatusText = (status: number): string => {
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
  }
  return statusMap[status as keyof typeof statusMap] || 'Unknown'
}

const getDeliveryStatusClass = (status: number): string => {
  const classMap = {
    [-1]: 'status-draft',
    [0]: 'status-draft',
    [1]: 'status-pending',
    [2]: 'status-pending',
    [3]: 'status-pending',
    [4]: 'status-completed',
    [5]: 'status-pending',
    [6]: 'status-pending',
    [7]: 'status-draft'
  }
  return `order-status ${classMap[status as keyof typeof classMap] || 'status-draft'}`
}

const getOrderStatusColor = (paymentStatus: number): string => {
  if (paymentStatus === 4) return '#4CAF50' // Received - Green
  if (paymentStatus === 1 || paymentStatus === 2 || paymentStatus === 3) return '#FFC107' // Pending states - Yellow
  return '#e57373' // Error states - Red
}

const formatPrice = (price: string, currency: string): string => {
  const formatter = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currency || 'EUR'
  })
  return formatter.format(parseFloat(price))
}

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString()
}

const makeAimeosRequest = async (endpoint: string, options: any = {}): Promise<any> => {
  const baseUrl = '/admin/aimeos'
  const url = new URL(baseUrl + endpoint, window.location.origin)
  
  if (options.params) {
    Object.keys(options.params).forEach(key => {
      url.searchParams.append(key, options.params[key])
    })
  }

  const response = await fetch(url.toString(), {
    method: options.method || 'GET',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      ...options.headers
    },
    body: options.body ? JSON.stringify(options.body) : undefined
  })

  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`)
  }

  return await response.json()
}

// Action methods
const showCreateOrder = () => {
  console.log('Show create order modal')
}

const viewOrder = (order: AimeosOrder) => {
  console.log('View order:', order.id)
}

const editOrder = (order: AimeosOrder) => {
  console.log('Edit order:', order.id)
}

const updateOrderStatus = (order: AimeosOrder) => {
  console.log('Update order status:', order.id)
}

const generateInvoice = (order: AimeosOrder) => {
  console.log('Generate invoice for order:', order.id)
}

const exportOrders = () => {
  console.log('Export orders')
}

const showBulkActions = () => {
  console.log('Show bulk actions')
}

// Lifecycle
onMounted(() => {
  loadOrders()
  loadOrderStats()
})
</script>

<style scoped>
.orders-manager-app {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.order-item {
  cursor: pointer;
  transition: background-color 0.2s;
}

.order-item:hover {
  background-color: #f5f5f5;
}

.order-item.active {
  background-color: #e3f2fd;
  border-left: 3px solid #2196F3;
}

.order-status {
  display: inline-block;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: bold;
}

.status-completed {
  background-color: #e8f5e8;
  color: #4caf50;
}

.status-pending {
  background-color: #fff3e0;
  color: #ff9800;
}

.status-draft {
  background-color: #fce4ec;
  color: #e91e63;
}

.loading-state {
  padding: 40px;
  text-align: center;
  color: #666;
}

.pagination-item.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-item.active {
  background-color: #2196F3;
  color: white;
}

.detail-section {
  margin-bottom: 20px;
}

.detail-section-header {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
  color: #333;
}

.detail-section-header i {
  margin-right: 8px;
  color: #2196F3;
}

.detail-section-header h3 {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
}

.detail-value {
  font-size: 13px;
  line-height: 1.6;
  color: #666;
}
</style> 