<template>
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
          <button class="btn btn-secondary" @click="exportProducts">
            <i class="fas fa-download"></i> Export
          </button>
          <button class="btn btn-secondary" @click="showBulkActions">
            <i class="fas fa-tasks"></i> Bulk Actions
          </button>
          <button class="btn btn-primary" @click="showCreateProduct">
            <i class="fas fa-plus"></i> Add Product
          </button>
        </div>
      </div>

      <!-- Two Column Layout -->
      <div class="product-content-columns">
        <!-- Left Column - Products List -->
        <div class="product-main-column">
          <!-- Product Statistics -->
          <div class="client-financial-section" style="margin-bottom: 40px;">
            <div class="financial-block">
              <div class="financial-icon">
                <i class="fas fa-box"></i>
              </div>
              <div class="financial-details">
                <div class="financial-amount">{{ productStats.totalProducts }}</div>
                <div class="financial-label">Total Products</div>
              </div>
            </div>
            <div class="financial-block">
              <div class="financial-icon">
                <i class="fas fa-check-circle"></i>
              </div>
              <div class="financial-details">
                <div class="financial-amount">{{ productStats.activeProducts }}</div>
                <div class="financial-label">Active Products</div>
              </div>
            </div>
            <div class="financial-block">
              <div class="financial-icon">
                <i class="fas fa-warehouse"></i>
              </div>
              <div class="financial-details">
                <div class="financial-amount">{{ productStats.inStockProducts }}</div>
                <div class="financial-label">In Stock</div>
              </div>
            </div>
            <div class="financial-block">
              <div class="financial-icon">
                <i class="fas fa-euro-sign"></i>
              </div>
              <div class="financial-details">
                <div class="financial-amount">{{ productStats.avgPrice }}</div>
                <div class="financial-label">Avg Price</div>
              </div>
            </div>
          </div>

          <div class="product-section">
            <div class="section-header-with-link">
              <h2><i class="fas fa-boxes"></i> Product Catalog</h2>
              <div class="product-actions">
                <button class="btn-link" @click="syncProducts">
                  <i class="fas fa-sync"></i> Sync with Aimeos
                </button>
              </div>
            </div>
            
            <!-- Search and Filters -->
            <div class="form-row">
              <div class="form-group half">
                <label>Search Products</label>
                <div class="input-group">
                  <div class="input-group-prepend">
                    <i class="fas fa-search"></i>
                  </div>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="searchQuery"
                    @input="debouncedSearch"
                    placeholder="Search by name, code, or description..."
                  >
                </div>
              </div>
              <div class="form-group half">
                <label>Product Type</label>
                <div class="select-container">
                  <select 
                    class="form-control" 
                    v-model="typeFilter"
                    @change="loadProducts"
                  >
                    <option value="">All Types</option>
                    <option value="default">Default</option>
                    <option value="select">Selection</option>
                    <option value="bundle">Bundle</option>
                    <option value="voucher">Voucher</option>
                    <option value="event">Event</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group half">
                <label>Status</label>
                <div class="select-container">
                  <select 
                    class="form-control" 
                    v-model="statusFilter"
                    @change="loadProducts"
                  >
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                    <option value="-1">Draft</option>
                  </select>
                </div>
              </div>
              <div class="form-group half">
                <label>Stock Status</label>
                <div class="select-container">
                  <select 
                    class="form-control" 
                    v-model="stockFilter"
                    @change="loadProducts"
                  >
                    <option value="">All Stock</option>
                    <option value="instock">In Stock</option>
                    <option value="outofstock">Out of Stock</option>
                    <option value="lowstock">Low Stock</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Products List -->
            <div class="product-list-container">
              <div class="contacts-oder-list-header-item">
                <div class="product-image">Image</div>
                <div class="product-name">Product</div>
                <div class="product-code">Code</div>
                <div class="product-type">Type</div>
                <div class="product-price">Price</div>
                <div class="product-stock">Stock</div>
                <div class="product-status">Status</div>
                <div class="product-actions">Actions</div>
              </div>
              
              <div class="products-list">
                <div v-if="loading" class="loading-state" style="text-align: center; padding: 40px;">
                  <i class="fas fa-spinner fa-spin"></i> Loading products...
                </div>
                
                <div v-else-if="products.length === 0" style="text-align: center; padding: 40px; color: #8A8A9E;">
                  <i class="fas fa-box" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                  <p>No products found</p>
                  <button v-if="searchQuery" class="btn-link" @click="clearSearch">Clear search</button>
                </div>

                <div 
                  v-else
                  v-for="product in products" 
                  :key="product.id"
                  class="product-item"
                  :class="{ active: selectedProduct?.id === product.id }"
                  @click="selectProduct(product)"
                >
                  <div class="product-image">
                    <img 
                      :src="getProductImage(product)" 
                      :alt="product.attributes['product.label']"
                      class="product-thumbnail"
                    >
                  </div>
                  <div class="product-name">
                    <strong>{{ product.attributes['product.label'] }}</strong>
                  </div>
                  <div class="product-code">
                    {{ product.attributes['product.code'] }}
                  </div>
                  <div class="product-type">
                    {{ product.attributes['product.type'] }}
                  </div>
                  <div class="product-price">
                    {{ formatPrice(product) }}
                  </div>
                  <div class="product-stock">
                    <span :class="getStockClass(product.attributes['product.instock'])">
                      {{ product.attributes['product.instock'] || 0 }}
                    </span>
                  </div>
                  <div class="product-status">
                    <span :class="getStatusClass(product.attributes['product.status'])">
                      {{ getStatusText(product.attributes['product.status']) }}
                    </span>
                  </div>
                  <div class="product-actions">
                    <button class="btn-link" @click.stop="viewProduct(product)" title="View">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-link" @click.stop="editProduct(product)" title="Edit">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-link" @click.stop="duplicateProduct(product)" title="Duplicate">
                      <i class="fas fa-copy"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div class="footer-pagination">
              <div class="total-label">
                <span class="total-amount">{{ totalProducts }}</span> products found
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

        <!-- Right Column - Product Details -->
        <div class="product-product-publish-column">
          <div class="sidebar-title">Product Details</div>
          
          <div v-if="!selectedProduct" class="product-details-empty" style="text-align: center; padding: 40px; color: #8A8A9E;">
            <i class="fas fa-box" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
            <p>Select a product to view details</p>
          </div>

          <div v-else class="contact-content-wrapper">
            <!-- Product Header -->
            <div class="client-header">
              <div class="contact-info-with-id">
                <div class="client-id-with-online-status">
                  <div class="client-id">{{ selectedProduct.attributes['product.label'] }}</div>
                  <div class="online-indicator" :style="{ background: getStatusColor(selectedProduct.attributes['product.status']) }"></div>
                </div>
                <div class="client-meta">
                  <span>Code: {{ selectedProduct.attributes['product.code'] }}</span>
                  <span class="separator">•</span>
                  <span>Type: {{ selectedProduct.attributes['product.type'] }}</span>
                </div>
              </div>
            </div>

            <!-- Product Image -->
            <div class="product-image-section" style="margin-bottom: 20px;">
              <img 
                :src="getProductImage(selectedProduct)" 
                :alt="selectedProduct.attributes['product.label']"
                style="width: 100%; max-height: 200px; object-fit: cover; border-radius: 8px;"
              >
            </div>

            <!-- Product Details -->
            <div class="product-details-container">
              <div class="detail-section">
                <div class="detail-section-header">
                  <i class="fas fa-info-circle"></i>
                  <h3>Basic Information</h3>
                </div>
                <div class="detail-value">
                  <strong>Label:</strong> {{ selectedProduct.attributes['product.label'] }}<br>
                  <strong>Code:</strong> {{ selectedProduct.attributes['product.code'] }}<br>
                  <strong>Type:</strong> {{ selectedProduct.attributes['product.type'] }}<br>
                  <strong>URL:</strong> {{ selectedProduct.attributes['product.url'] || 'N/A' }}
                </div>
              </div>

              <div class="detail-section">
                <div class="detail-section-header">
                  <i class="fas fa-euro-sign"></i>
                  <h3>Pricing & Stock</h3>
                </div>
                <div class="detail-value">
                  <strong>Price:</strong> {{ formatPrice(selectedProduct) }}<br>
                  <strong>In Stock:</strong> {{ selectedProduct.attributes['product.instock'] || 0 }}<br>
                  <strong>Scale:</strong> {{ selectedProduct.attributes['product.scale'] || 1 }}<br>
                  <strong>Boost:</strong> {{ selectedProduct.attributes['product.boost'] || 1 }}
                </div>
              </div>

              <div class="detail-section">
                <div class="detail-section-header">
                  <i class="fas fa-calendar"></i>
                  <h3>Availability</h3>
                </div>
                <div class="detail-value">
                  <strong>Start Date:</strong> {{ selectedProduct.attributes['product.datestart'] || 'Always' }}<br>
                  <strong>End Date:</strong> {{ selectedProduct.attributes['product.dateend'] || 'Never' }}<br>
                  <strong>Status:</strong> {{ getStatusText(selectedProduct.attributes['product.status']) }}
                </div>
              </div>

              <div class="detail-section">
                <div class="detail-section-header">
                  <i class="fas fa-clock"></i>
                  <h3>Timestamps</h3>
                </div>
                <div class="detail-value">
                  <strong>Created:</strong> {{ formatDate(selectedProduct.attributes['product.ctime']) }}<br>
                  <strong>Modified:</strong> {{ formatDate(selectedProduct.attributes['product.mtime']) }}<br>
                  <strong>Editor:</strong> {{ selectedProduct.attributes['product.editor'] }}
                </div>
              </div>

              <div v-if="selectedProduct.attributes['product.target']" class="detail-section">
                <div class="detail-section-header">
                  <i class="fas fa-external-link-alt"></i>
                  <h3>Target</h3>
                </div>
                <div class="detail-value">
                  {{ selectedProduct.attributes['product.target'] }}
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
                  <div class="invoice-text">Update product information and settings</div>
                </div>
              </div>
              <button class="invoice-button-text" @click="editProduct(selectedProduct)">
                Edit Product <i class="fas fa-arrow-right"></i>
              </button>
            </div>

            <div class="generate-shipping">
              <div class="shipping-header">
                <div class="shipping-icon">
                  <i class="fas fa-chart-line"></i>
                </div>
                <div>
                  <div class="shipping-text-title">View Analytics</div>
                  <div class="shipping-text">See sales data and performance metrics</div>
                </div>
              </div>
              <button class="shipping-button-text" @click="viewProductAnalytics(selectedProduct)">
                View Analytics <i class="fas fa-arrow-right"></i>
              </button>
            </div>

            <div class="generate-shipping">
              <div class="shipping-header">
                <div class="shipping-icon">
                  <i class="fas fa-copy"></i>
                </div>
                <div>
                  <div class="shipping-text-title">Duplicate Product</div>
                  <div class="shipping-text">Create a copy of this product</div>
                </div>
              </div>
              <button class="shipping-button-text" @click="duplicateProduct(selectedProduct)">
                Duplicate <i class="fas fa-arrow-right"></i>
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
    'product.config': any;
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
    price?: Record<string, unknown>[];
    media?: Record<string, unknown>[];
    text?: Record<string, unknown>[];
    attribute?: Record<string, unknown>[];
    property?: Record<string, unknown>[];
    catalog?: Record<string, unknown>[];
    supplier?: Record<string, unknown>[];
    stock?: Record<string, unknown>[];
  };
}

interface ProductStats {
  totalProducts: number;
  activeProducts: number;
  inStockProducts: number;
  avgPrice: string;
}

// Reactive state
const products = ref<AimeosProduct[]>([])
const selectedProduct = ref<AimeosProduct | null>(null)
const loading = ref(false)
const currentPage = ref(1)
const totalPages = ref(1)
const totalProducts = ref(0)
const searchQuery = ref('')
const typeFilter = ref('')
const statusFilter = ref('')
const stockFilter = ref('')

const productStats = reactive<ProductStats>({
  totalProducts: 0,
  activeProducts: 0,
  inStockProducts: 0,
  avgPrice: '€0.00'
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
  loadProducts()
}, 300)

// Methods
const loadProducts = async () => {
  loading.value = true
  try {
    const params: any = {
      'page[offset]': ((currentPage.value || 1) - 1) * 20,
      'page[limit]': 20,
      'include': 'product/property,price,media,text',
      'sort': '-product.ctime'
    }

    if (searchQuery.value) {
      params['filter[product.label]'] = `*${searchQuery.value}*`
      params['filter[product.code]'] = `*${searchQuery.value}*`
    }

    if (typeFilter.value) {
      params['filter[product.type]'] = typeFilter.value
    }

    if (statusFilter.value) {
      params['filter[product.status]'] = statusFilter.value
    }

    if (stockFilter.value) {
      if (stockFilter.value === 'instock') {
        params['filter[product.instock][>]'] = '0'
      } else if (stockFilter.value === 'outofstock') {
        params['filter[product.instock]'] = '0'
      } else if (stockFilter.value === 'lowstock') {
        params['filter[product.instock][<=]'] = '10'
        params['filter[product.instock][>]'] = '0'
      }
    }

    const response = await makeAimeosRequest('/jsonapi/product', {
      method: 'GET',
      params
    })

    if (response.data) {
      products.value = Array.isArray(response.data) ? response.data : [response.data]
      totalPages.value = Math.ceil((response.meta?.total || products.value?.length || 0) / 20)
      totalProducts.value = response.meta?.total || products.value?.length || 0
    }
  } catch (error) {
    console.error('Error loading products:', error)
  } finally {
    loading.value = false
  }
}

const loadProductStats = async () => {
  try {
    const response = await makeAimeosRequest('/admin/products/stats', {
      method: 'GET'
    })

    if (response.data) {
      Object.assign(productStats, response.data)
    }
  } catch (error) {
    console.error('Error loading product stats:', error)
    // Set default values if stats can't be loaded
    productStats.totalProducts = products.value?.length || 0
    productStats.activeProducts = products.value?.filter((p: any) => p.attributes['product.status'] === 1).length || 0
    productStats.inStockProducts = products.value?.filter((p: any) => (p.attributes['product.instock'] || 0) > 0).length || 0
    productStats.avgPrice = '€0.00'
  }
}

const selectProduct = (product: AimeosProduct) => {
  selectedProduct.value = product
}

const changePage = (page: number) => {
  if (page >= 1 && page <= (totalPages.value || 1)) {
    currentPage.value = page
    loadProducts()
  }
}

const clearSearch = () => {
  searchQuery.value = ''
  typeFilter.value = ''
  statusFilter.value = ''
  stockFilter.value = ''
  currentPage.value = 1
  loadProducts()
}

const syncProducts = () => {
  loadProducts()
  loadProductStats()
}

const getProductImage = (product: AimeosProduct): string => {
  // This would resolve from the media relationships
  return 'img/placeholder-auto-theme.png'
}

const formatPrice = (product: AimeosProduct): string => {
  // This would need to resolve from the price relationships
  // For now, return a placeholder
  return '€0.00'
}

const getStockClass = (stock: number): string => {
  if (stock === 0) return 'stock-out'
  if (stock <= 10) return 'stock-low'
  return 'stock-normal'
}

const getStatusText = (status: number): string => {
  const statusMap = {
    [-1]: 'Draft',
    [0]: 'Inactive',
    [1]: 'Active',
    [2]: 'Review'
  }
  return statusMap[status as keyof typeof statusMap] || 'Unknown'
}

const getStatusClass = (status: number): string => {
  const classMap = {
    [-1]: 'status-draft',
    [0]: 'status-pending',
    [1]: 'status-completed',
    [2]: 'status-pending'
  }
  return `product-status ${classMap[status as keyof typeof classMap] || 'status-draft'}`
}

const getStatusColor = (status: number): string => {
  if (status === 1) return '#4CAF50' // Active - Green
  if (status === -1 || status === 2) return '#FFC107' // Draft/Review - Yellow
  return '#e57373' // Inactive - Red
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
const showCreateProduct = () => {
  console.log('Show create product modal')
}

const viewProduct = (product: AimeosProduct) => {
  console.log('View product:', product.id)
}

const editProduct = (product: AimeosProduct) => {
  console.log('Edit product:', product.id)
}

const duplicateProduct = (product: AimeosProduct) => {
  console.log('Duplicate product:', product.id)
}

const viewProductAnalytics = (product: AimeosProduct) => {
  console.log('View product analytics:', product.id)
}

const exportProducts = () => {
  console.log('Export products')
}

const showBulkActions = () => {
  console.log('Show bulk actions')
}

// Lifecycle
onMounted(() => {
  loadProducts()
  loadProductStats()
})
</script>

<style scoped>
.products-manager-app {
  height: 100%;
  display: flex;
  flex-direction: column;
}

.product-item {
  cursor: pointer;
  transition: background-color 0.2s;
}

.product-item:hover {
  background-color: #f5f5f5;
}

.product-item.active {
  background-color: #e3f2fd;
  border-left: 3px solid #2196F3;
}

.product-thumbnail {
  width: 40px;
  height: 40px;
  object-fit: cover;
  border-radius: 4px;
}

.product-status {
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

.stock-normal {
  color: #4caf50;
  font-weight: bold;
}

.stock-low {
  color: #ff9800;
  font-weight: bold;
}

.stock-out {
  color: #f44336;
  font-weight: bold;
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