<template>
  <div class="contacts-app">
    <!-- Mobile profile top bar -->
    <div class="mobile-profile-top-bar">
      <div class="mobile-profile-top-bar-header">
        <div class="mobile-profile-top-bar-header-left">
          <img src="img/avatar.png" alt="Site Logo" class="site-logo-round">
          <span>Contacts</span>
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
          <button class="btn btn-secondary" @click="exportCustomers">
            <i class="fas fa-download"></i> Export
          </button>
          <button class="btn btn-secondary" @click="showBulkActions">
            <i class="fas fa-tasks"></i> Bulk Actions
          </button>
          <button class="btn btn-primary" @click="showCreateCustomer">
            <i class="fas fa-plus"></i> Add Customer
          </button>
        </div>
      </div>

      <!-- Two Column Layout -->
      <div class="product-content-columns">
        <!-- Left Column - Customer List -->
        <div class="product-main-column">
          <!-- Customer Statistics -->
          <div class="client-financial-section" style="margin-bottom: 40px;">
            <div class="financial-block">
              <div class="financial-icon">
                <i class="fas fa-users"></i>
              </div>
              <div class="financial-details">
                <div class="financial-amount">{{ customerStats.totalCustomers }}</div>
                <div class="financial-label">Total Customers</div>
              </div>
            </div>
            <div class="financial-block">
              <div class="financial-icon">
                <i class="fas fa-user-plus"></i>
              </div>
              <div class="financial-details">
                <div class="financial-amount">{{ customerStats.newThisMonth }}</div>
                <div class="financial-label">New This Month</div>
              </div>
            </div>
            <div class="financial-block">
              <div class="financial-icon">
                <i class="fas fa-eye"></i>
              </div>
              <div class="financial-details">
                <div class="financial-amount">{{ customerStats.activeCustomers }}</div>
                <div class="financial-label">Active Customers</div>
              </div>
            </div>
            <div class="financial-block">
              <div class="financial-icon">
                <i class="fas fa-euro-sign"></i>
              </div>
              <div class="financial-details">
                <div class="financial-amount">{{ customerStats.avgOrderValue }}</div>
                <div class="financial-label">Avg Order Value</div>
              </div>
            </div>
          </div>

          <div class="product-section">
            <div class="section-header-with-link">
              <h2><i class="fas fa-address-book"></i> Customer Directory</h2>
              <div class="customer-actions">
                <button class="btn-link" @click="syncCustomers">
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
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="searchQuery"
                    @input="debouncedSearch"
                    placeholder="Search by name, email, or company..."
                  >
                </div>
              </div>
              <div class="form-group half">
                <label>Customer Status</label>
                <div class="select-container">
                  <select 
                    class="form-control" 
                    v-model="statusFilter"
                    @change="loadCustomers"
                  >
                    <option value="">All Customers</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                    <option value="-1">Pending</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Customer Group</label>
                <div class="select-container">
                  <select 
                    class="form-control" 
                    v-model="groupFilter"
                    @change="loadCustomers"
                  >
                    <option value="">All Groups</option>
                    <option v-for="group in customerGroups" :key="group.id" :value="group.id">
                      {{ group.label }}
                    </option>
                  </select>
                </div>
              </div>
              <div class="form-group half">
                <label>Registration Date</label>
                <div class="form-row-top-input">
                  <input 
                    type="date" 
                    class="form-control" 
                    v-model="dateFromFilter"
                    @change="loadCustomers"
                    style="width: 48%;"
                  >
                  <input 
                    type="date" 
                    class="form-control" 
                    v-model="dateToFilter"
                    @change="loadCustomers"
                    style="width: 48%;"
                  >
                </div>
              </div>
            </div>

            <!-- Customer List -->
            <div class="customer-list-container">
              <div class="contacts-oder-list-header-item">
                <div class="customer-id">Customer ID</div>
                <div class="customer-name">Name</div>
                <div class="customer-email">Email</div>
                <div class="customer-group">Group</div>
                <div class="customer-status">Status</div>
                <div class="customer-orders">Orders</div>
                <div class="customer-actions">Actions</div>
              </div>
              
              <div class="customers-list">
                <div v-if="loading" class="loading-state" style="text-align: center; padding: 40px;">
                  <i class="fas fa-spinner fa-spin"></i> Loading customers...
                </div>
                
                <div v-else-if="customers.length === 0" style="text-align: center; padding: 40px; color: #8A8A9E;">
                  <i class="fas fa-users" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                  <p>No customers found</p>
                  <button v-if="searchQuery" class="btn-link" @click="clearSearch">Clear search</button>
                </div>

                <div 
                  v-else
                  v-for="customer in customers" 
                  :key="customer.id"
                  class="customer-item"
                  :class="{ active: selectedCustomer?.id === customer.id }"
                  @click="selectCustomer(customer)"
                >
                  <div class="customer-id">
                    <strong>#{{ customer.attributes['customer.code'] }}</strong>
                    <i class="fas fa-external-link-alt"></i>
                  </div>
                  <div class="customer-name">
                    {{ getCustomerFullName(customer) }}
                  </div>
                  <div class="customer-email">
                    {{ customer.attributes['customer.email'] }}
                  </div>
                  <div class="customer-group">
                    {{ getCustomerGroup(customer) }}
                  </div>
                  <div class="customer-status">
                    <span :class="getStatusClass(customer.attributes['customer.status'])">
                      {{ getStatusText(customer.attributes['customer.status']) }}
                    </span>
                  </div>
                  <div class="customer-orders">
                    {{ customer.orderCount || 0 }}
                  </div>
                  <div class="customer-actions">
                    <button class="btn-link" @click.stop="viewCustomer(customer)" title="View">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-link" @click.stop="editCustomer(customer)" title="Edit">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-link" @click.stop="viewCustomerOrders(customer)" title="Orders">
                      <i class="fas fa-shopping-cart"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div class="footer-pagination">
              <div class="total-label">
                <span class="total-amount">{{ totalCustomers }}</span> customers found
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

        <!-- Right Column - Customer Details -->
        <div class="product-product-publish-column">
          <div class="sidebar-title">Customer Details</div>
          
          <div v-if="!selectedCustomer" class="customer-details-empty" style="text-align: center; padding: 40px; color: #8A8A9E;">
            <i class="fas fa-user" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
            <p>Select a customer to view details</p>
          </div>

          <div v-else class="contact-content-wrapper">
            <!-- Customer Header -->
            <div class="client-header">
              <div class="contact-info-with-id">
                <div class="client-id-with-online-status">
                  <div class="client-id">{{ getCustomerFullName(selectedCustomer) }}</div>
                  <div class="online-indicator" :style="{ background: getStatusColor(selectedCustomer.attributes['customer.status']) }"></div>
                </div>
                <div class="client-meta">
                  <span>ID: {{ selectedCustomer.attributes['customer.code'] }}</span>
                  <span class="separator">•</span>
                  <span>Member since: {{ formatDate(selectedCustomer.attributes['customer.ctime']) }}</span>
                </div>
              </div>
            </div>

            <!-- Customer Details -->
            <div class="customer-details-container">
              <div class="detail-section">
                <div class="detail-section-header">
                  <i class="fas fa-user"></i>
                  <h3>Personal Information</h3>
                </div>
                <div class="detail-value">
                  <strong>Full Name:</strong> {{ getCustomerFullName(selectedCustomer) }}<br>
                  <strong>Email:</strong> {{ selectedCustomer.attributes['customer.email'] }}<br>
                  <strong>Company:</strong> {{ selectedCustomer.attributes['customer.company'] || 'N/A' }}<br>
                  <strong>VAT ID:</strong> {{ selectedCustomer.attributes['customer.vatid'] || 'N/A' }}
                </div>
              </div>

              <div class="detail-section">
                <div class="detail-section-header">
                  <i class="fas fa-map-marker-alt"></i>
                  <h3>Address Information</h3>
                </div>
                <div class="detail-value">
                  <strong>Address:</strong> {{ getFullAddress(selectedCustomer) }}<br>
                  <strong>City:</strong> {{ selectedCustomer.attributes['customer.city'] || 'N/A' }}<br>
                  <strong>Postal Code:</strong> {{ selectedCustomer.attributes['customer.postal'] || 'N/A' }}<br>
                  <strong>Country:</strong> {{ selectedCustomer.attributes['customer.countryid'] || 'N/A' }}
                </div>
              </div>

              <div class="detail-section">
                <div class="detail-section-header">
                  <i class="fas fa-info-circle"></i>
                  <h3>Account Information</h3>
                </div>
                <div class="detail-value">
                  <strong>Status:</strong> {{ getStatusText(selectedCustomer.attributes['customer.status']) }}<br>
                  <strong>Language:</strong> {{ selectedCustomer.attributes['customer.languageid'] || 'N/A' }}<br>
                  <strong>Birthday:</strong> {{ selectedCustomer.attributes['customer.birthday'] || 'N/A' }}
                </div>
              </div>

              <div class="detail-section">
                <div class="detail-section-header">
                  <i class="fas fa-clock"></i>
                  <h3>Timestamps</h3>
                </div>
                <div class="detail-value">
                  <strong>Created:</strong> {{ formatDate(selectedCustomer.attributes['customer.ctime']) }}<br>
                  <strong>Modified:</strong> {{ formatDate(selectedCustomer.attributes['customer.mtime']) }}<br>
                  <strong>Editor:</strong> {{ selectedCustomer.attributes['customer.editor'] }}
                </div>
              </div>
            </div>

            <!-- Quick Actions -->
            <div class="generate-invoice">
              <div class="invoice-header">
                <div class="invoice-icon">
                  <i class="fas fa-shopping-cart"></i>
                </div>
                <div>
                  <div class="invoice-text-title">View Orders</div>
                  <div class="invoice-text">See all orders from this customer</div>
                </div>
              </div>
              <button class="invoice-button-text" @click="viewCustomerOrders(selectedCustomer)">
                View Orders <i class="fas fa-arrow-right"></i>
              </button>
            </div>

            <div class="generate-shipping">
              <div class="shipping-header">
                <div class="shipping-icon">
                  <i class="fas fa-edit"></i>
                </div>
                <div>
                  <div class="shipping-text-title">Edit Customer</div>
                  <div class="shipping-text">Update customer information</div>
                </div>
              </div>
              <button class="shipping-button-text" @click="editCustomer(selectedCustomer)">
                Edit Customer <i class="fas fa-arrow-right"></i>
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
    'customer.vdate': string;
    'customer.password': string;
    'customer.ctime': string;
    'customer.mtime': string;
    'customer.editor': string;
  };
  orderCount?: number;
}

interface CustomerGroup {
  id: string;
  label: string;
}

interface CustomerStats {
  totalCustomers: number;
  newThisMonth: number;
  activeCustomers: number;
  avgOrderValue: string;
}

// Reactive state
const customers = ref<AimeosCustomer[]>([])
const selectedCustomer = ref<AimeosCustomer | null>(null)
const customerGroups = ref<CustomerGroup[]>([])
const loading = ref(false)
const currentPage = ref(1)
const totalPages = ref(1)
const totalCustomers = ref(0)
const searchQuery = ref('')
const statusFilter = ref('')
const groupFilter = ref('')
const dateFromFilter = ref('')
const dateToFilter = ref('')

const customerStats = reactive<CustomerStats>({
  totalCustomers: 0,
  newThisMonth: 0,
  activeCustomers: 0,
  avgOrderValue: '€0.00'
})

// Computed properties
const paginationPages = computed(() => {
  const pages = []
  const start = Math.max(1, (currentPage.value || 1) - 2)
  const end = Math.min((totalPages.value || 1), (currentPage.value || 1) + 2)
  
  for (let i = start; i <= end; i++) {
    pages.push(i)
  }
  
  return pages
})

// Debounced search function
const debouncedSearch = debounce(() => {
  currentPage.value = 1
  loadCustomers()
}, 300)

// Methods
const loadCustomers = async () => {
  loading.value = true
  try {
    const params: any = {
      'page[offset]': ((currentPage.value || 1) - 1) * 20,
      'page[limit]': 20,
      'include': 'customer/property,customer/address',
      'sort': '-customer.ctime'
    }

    if (searchQuery.value) {
      params['filter[customer.email]'] = `*${searchQuery.value}*`
      params['filter[customer.firstname]'] = `*${searchQuery.value}*`
      params['filter[customer.lastname]'] = `*${searchQuery.value}*`
      params['filter[customer.company]'] = `*${searchQuery.value}*`
    }

    if (statusFilter.value) {
      params['filter[customer.status]'] = statusFilter.value
    }

    if (groupFilter.value) {
      params['filter[customer.groups.id]'] = groupFilter.value
    }

    if (dateFromFilter.value) {
      params['filter[customer.ctime][>=]'] = dateFromFilter.value
    }

    if (dateToFilter.value) {
      params['filter[customer.ctime][<=]'] = dateToFilter.value + ' 23:59:59'
    }

    const response = await makeAimeosRequest('/jsonapi/customer', {
      method: 'GET',
      params
    })

    if (response.data) {
      customers.value = Array.isArray(response.data) ? response.data : [response.data]
      totalPages.value = Math.ceil((response.meta?.total || (customers.value?.length || 0)) / 20)
      totalCustomers.value = response.meta?.total || (customers.value?.length || 0)
      
      // Load order counts for each customer
      await loadCustomerOrderCounts()
    }
  } catch (error) {
    console.error('Error loading customers:', error)
  } finally {
    loading.value = false
  }
}

const loadCustomerOrderCounts = async () => {
  // This would be a batch request to get order counts for each customer
  // For now, we'll set dummy data
  customers.value?.forEach(customer => {
    customer.orderCount = Math.floor(Math.random() * 20)
  })
}

const loadCustomerStats = async () => {
  try {
    const response = await makeAimeosRequest('/admin/customers/stats', {
      method: 'GET'
    })

    if (response.data) {
      Object.assign(customerStats, response.data)
    }
  } catch (error) {
    console.error('Error loading customer stats:', error)
    // Set default values if stats can't be loaded
    customerStats.totalCustomers = customers.value?.length || 0
    customerStats.newThisMonth = 0
    customerStats.activeCustomers = customers.value?.filter((c: any) => c.attributes['customer.status'] === 1).length || 0
    customerStats.avgOrderValue = '€0.00'
  }
}

const loadCustomerGroups = async () => {
  try {
    const response = await makeAimeosRequest('/jsonapi/group', {
      method: 'GET'
    })

    if (response.data) {
      customerGroups.value = Array.isArray(response.data) ? response.data : [response.data]
    }
  } catch (error) {
    console.error('Error loading customer groups:', error)
  }
}

const selectCustomer = (customer: AimeosCustomer) => {
  selectedCustomer.value = customer
}

const changePage = (page: number) => {
  if (page >= 1 && page <= (totalPages.value || 1)) {
    currentPage.value = page
    loadCustomers()
  }
}

const clearSearch = () => {
  searchQuery.value = ''
  statusFilter.value = ''
  groupFilter.value = ''
  dateFromFilter.value = ''
  dateToFilter.value = ''
  currentPage.value = 1
  loadCustomers()
}

const syncCustomers = () => {
  loadCustomers()
  loadCustomerStats()
}

const getCustomerFullName = (customer: AimeosCustomer): string => {
  const attrs = customer.attributes
  const parts = []
  
  if (attrs['customer.title']) parts.push(attrs['customer.title'])
  if (attrs['customer.firstname']) parts.push(attrs['customer.firstname'])
  if (attrs['customer.lastname']) parts.push(attrs['customer.lastname'])
  
  return parts.length > 0 ? parts.join(' ') : attrs['customer.label'] || attrs['customer.email']
}

const getFullAddress = (customer: AimeosCustomer): string => {
  const attrs = customer.attributes
  const parts = []
  
  if (attrs['customer.address1']) parts.push(attrs['customer.address1'])
  if (attrs['customer.address2']) parts.push(attrs['customer.address2'])
  if (attrs['customer.address3']) parts.push(attrs['customer.address3'])
  
  return parts.join(', ') || 'N/A'
}

const getCustomerGroup = (customer: AimeosCustomer): string => {
  // This would need to be resolved from the relationships or a separate API call
  return 'Default'
}

const getStatusText = (status: number): string => {
  const statusMap = {
    [-1]: 'Pending',
    [0]: 'Inactive',
    [1]: 'Active',
    [2]: 'Blocked'
  }
  return statusMap[status as keyof typeof statusMap] || 'Unknown'
}

const getStatusClass = (status: number): string => {
  const classMap = {
    [-1]: 'status-pending',
    [0]: 'status-draft',
    [1]: 'status-completed',
    [2]: 'status-pending'
  }
  return `customer-status ${classMap[status as keyof typeof classMap] || 'status-draft'}`
}

const getStatusColor = (status: number): string => {
  if (status === 1) return '#4CAF50' // Active - Green
  if (status === -1) return '#FFC107' // Pending - Yellow
  return '#e57373' // Inactive/Blocked - Red
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
const showCreateCustomer = () => {
  console.log('Show create customer modal')
}

const viewCustomer = (customer: AimeosCustomer) => {
  console.log('View customer:', customer.id)
}

const editCustomer = (customer: AimeosCustomer) => {
  console.log('Edit customer:', customer.id)
}

const viewCustomerOrders = (customer: AimeosCustomer) => {
  console.log('View customer orders:', customer.id)
}

const exportCustomers = () => {
  console.log('Export customers')
}

const showBulkActions = () => {
  console.log('Show bulk actions')
}

// Lifecycle
onMounted(() => {
  loadCustomers()
  loadCustomerStats()
  loadCustomerGroups()
})
</script>

<style scoped>
.contacts-app {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.customer-item {
  cursor: pointer;
  transition: background-color 0.2s;
}

.customer-item:hover {
  background-color: #f5f5f5;
}

.customer-item.active {
  background-color: #e3f2fd;
  border-left: 3px solid #2196F3;
}

.customer-status {
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