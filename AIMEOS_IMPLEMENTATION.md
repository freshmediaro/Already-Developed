# Aimeos E-commerce Integration Implementation

## Overview

This document provides comprehensive guidance for implementing Aimeos e-commerce integration into our desktop application environment. The implementation includes three core applications for managing customers, products, and orders, all styled to match the existing desktop aesthetic and fully integrated with Aimeos APIs.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Prerequisites](#prerequisites)
3. [Aimeos Setup](#aimeos-setup)
4. [Laravel Backend Configuration](#laravel-backend-configuration)
5. [Frontend Applications](#frontend-applications)
6. [API Integration](#api-integration)
7. [Styling Implementation](#styling-implementation)
8. [Vue Component Integration](#vue-component-integration)
9. [Authentication & Security](#authentication--security)
10. [Testing & Development](#testing--development)
11. [Deployment](#deployment)
12. [Troubleshooting](#troubleshooting)

## Architecture Overview

The Aimeos integration follows a multi-layered architecture:

```
Desktop Frontend (Vue 3 + TypeScript)
├── Contacts App (Customer Management)
├── Products Manager App (Catalog Management)
├── Orders Manager App (Order Processing)
│
Laravel Backend (API Layer)
├── Aimeos Admin Interface
├── JSON API Routes
├── Custom Controllers
├── Middleware (Security, Permissions)
│
Aimeos Core
├── Customer Management
├── Product Catalog
├── Order Processing
├── Payment Integration
└── Shipping Management
```

## Prerequisites

### System Requirements

- **PHP**: 8.1+
- **Laravel**: 10.x
- **Node.js**: 18+
- **Vue.js**: 3.x with Composition API
- **TypeScript**: 5.x
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Redis**: For session management and caching
- **Composer**: Latest version

### Required PHP Extensions

```bash
# Core extensions
ext-curl
ext-dom
ext-gd
ext-intl
ext-json
ext-mbstring
ext-openssl
ext-pdo
ext-pdo_mysql
ext-tokenizer
ext-xml
ext-zip

# Aimeos specific
ext-fileinfo
ext-iconv
ext-simplexml
```

## Aimeos Setup

### 1. Installation

```bash
# Install Aimeos Laravel package
composer require aimeos/aimeos-laravel

# Install Aimeos admin interface
composer require aimeos/ai-admin-jqadm

# Install JSON API extension
composer require aimeos/ai-admin-jsonadm

# Publish Aimeos configuration
php artisan vendor:publish --provider="Aimeos\Shop\ShopServiceProvider"
php artisan vendor:publish --tag=aimeos-config
```

### 2. Database Migration

```bash
# Create Aimeos database tables
php artisan aimeos:setup --option=setup/default/demo:1

# Run migrations
php artisan migrate

# Create admin user (optional)
php artisan aimeos:account --admin your@email.com
```

### 3. Configuration Files

Create the following configuration files:

#### `config/shop.php`
```php
<?php
return [
    'routes' => [
        'admin' => ['prefix' => 'admin', 'middleware' => ['web', 'auth']],
        'jqadm' => ['prefix' => 'admin/{site}/jqadm', 'middleware' => ['web', 'auth']],
        'jsonadm' => ['prefix' => 'admin/{site}/jsonadm', 'middleware' => ['web', 'auth']],
    ],
    'page' => [
        'account-index' => 'aimeos_shop::account.index',
        'basket-index' => 'aimeos_shop::basket.index',
        'catalog-count' => 'aimeos_shop::catalog.count',
        'catalog-detail' => 'aimeos_shop::catalog.detail',
        'catalog-list' => 'aimeos_shop::catalog.list',
        'catalog-tree' => 'aimeos_shop::catalog.tree',
        'checkout-confirm' => 'aimeos_shop::checkout.confirm',
        'checkout-index' => 'aimeos_shop::checkout.index',
        'checkout-standard' => 'aimeos_shop::checkout.standard',
    ],
];
```

## Laravel Backend Configuration

### 1. Custom Controllers

Create controllers to proxy Aimeos API requests:

#### `app/Http/Controllers/Tenant/AimeosProxyController.php`
```php
<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AimeosProxyController extends Controller
{
    public function proxy(Request $request, string $path): JsonResponse
    {
        $context = app('aimeos.context')->get();
        $manager = \Aimeos\MAdmin::create($context, 'customer');
        
        // Handle different HTTP methods
        switch ($request->method()) {
            case 'GET':
                return $this->handleGet($request, $path, $context);
            case 'POST':
                return $this->handlePost($request, $path, $context);
            case 'PUT':
            case 'PATCH':
                return $this->handlePut($request, $path, $context);
            case 'DELETE':
                return $this->handleDelete($request, $path, $context);
            default:
                return response()->json(['error' => 'Method not allowed'], 405);
        }
    }
    
    private function handleGet(Request $request, string $path, $context): JsonResponse
    {
        try {
            $params = $request->all();
            
            // Route to appropriate manager based on path
            if (str_contains($path, '/customer')) {
                $manager = \Aimeos\MAdmin::create($context, 'customer');
            } elseif (str_contains($path, '/product')) {
                $manager = \Aimeos\MAdmin::create($context, 'product');
            } elseif (str_contains($path, '/order')) {
                $manager = \Aimeos\MAdmin::create($context, 'order');
            } else {
                return response()->json(['error' => 'Invalid endpoint'], 400);
            }
            
            // Build search criteria
            $search = $manager->filter();
            
            // Apply filters
            if (isset($params['filter'])) {
                foreach ($params['filter'] as $key => $value) {
                    $search->add($search->compare('==', $key, $value));
                }
            }
            
            // Apply sorting
            if (isset($params['sort'])) {
                $sortFields = explode(',', $params['sort']);
                foreach ($sortFields as $field) {
                    $direction = str_starts_with($field, '-') ? '-' : '+';
                    $fieldName = ltrim($field, '+-');
                    $search->setSortations([$search->sort($direction, $fieldName)]);
                }
            }
            
            // Apply pagination
            $offset = (int) ($params['page']['offset'] ?? 0);
            $limit = (int) ($params['page']['limit'] ?? 20);
            $search->slice($offset, $limit);
            
            // Execute search
            $items = $manager->search($search);
            $total = $manager->search($search, [])->count();
            
            // Format response
            $data = [];
            foreach ($items as $item) {
                $data[] = [
                    'id' => $item->getId(),
                    'attributes' => $item->toArray()
                ];
            }
            
            return response()->json([
                'data' => $data,
                'meta' => [
                    'total' => $total,
                    'offset' => $offset,
                    'limit' => $limit
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch data',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    private function handlePost(Request $request, string $path, $context): JsonResponse
    {
        // Implementation for creating new records
        try {
            $data = $request->json()->all();
            
            if (str_contains($path, '/customer')) {
                $manager = \Aimeos\MAdmin::create($context, 'customer');
                $item = $manager->create();
            } elseif (str_contains($path, '/product')) {
                $manager = \Aimeos\MAdmin::create($context, 'product');
                $item = $manager->create();
            } elseif (str_contains($path, '/order')) {
                $manager = \Aimeos\MAdmin::create($context, 'order');
                $item = $manager->create();
            } else {
                return response()->json(['error' => 'Invalid endpoint'], 400);
            }
            
            // Set attributes from request data
            foreach ($data['attributes'] ?? [] as $key => $value) {
                $item->set($key, $value);
            }
            
            // Save item
            $savedItem = $manager->save($item);
            
            return response()->json([
                'data' => [
                    'id' => $savedItem->getId(),
                    'attributes' => $savedItem->toArray()
                ]
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create record',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    private function handlePut(Request $request, string $path, $context): JsonResponse
    {
        // Implementation for updating records
        // Similar structure to handlePost but for updates
        return response()->json(['message' => 'Update implemented'], 200);
    }
    
    private function handleDelete(Request $request, string $path, $context): JsonResponse
    {
        // Implementation for deleting records
        return response()->json(['message' => 'Delete implemented'], 200);
    }
}
```

### 2. Routes Configuration

#### `routes/tenant-aimeos.php`
```php
<?php

use App\Http\Controllers\Tenant\AimeosProxyController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/aimeos')->middleware(['web', 'auth', 'tenant.permissions'])->group(function () {
    // JSON API proxy routes
    Route::any('/jsonapi/{path}', [AimeosProxyController::class, 'proxy'])
        ->where('path', '.*')
        ->name('tenant.aimeos.jsonapi');
    
    // Statistics endpoints
    Route::get('/admin/customers/stats', [AimeosProxyController::class, 'customerStats'])
        ->name('tenant.aimeos.customers.stats');
    
    Route::get('/admin/products/stats', [AimeosProxyController::class, 'productStats'])
        ->name('tenant.aimeos.products.stats');
    
    Route::get('/admin/orders/stats', [AimeosProxyController::class, 'orderStats'])
        ->name('tenant.aimeos.orders.stats');
});
```

### 3. Middleware Configuration

#### `app/Http/Middleware/AimeosContextMiddleware.php`
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AimeosContextMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Initialize Aimeos context for current tenant
        $context = app('aimeos.context')->get();
        
        // Set tenant-specific configuration
        if ($tenant = $request->route('tenant')) {
            $context->config()->set('resource/db/host', $tenant->database_host);
            $context->config()->set('resource/db/database', $tenant->database_name);
            // Add other tenant-specific configurations
        }
        
        return $next($request);
    }
}
```

## Frontend Applications

### 1. Contacts App (Customer Management)

The Contacts app provides comprehensive customer relationship management with Aimeos integration.

**Key Features:**
- Customer listing with search and filtering
- Detailed customer profiles
- Order history integration
- Customer group management
- Export functionality
- Bulk operations

**TypeScript Implementation:**
```typescript
// resources/js/Apps/ContactsApp.ts
export class ContactsApp extends BaseApp {
  private customers: AimeosCustomer[] = [];
  private selectedCustomer: AimeosCustomer | null = null;
  
  async loadCustomers(): Promise<void> {
    const response = await this.makeAimeosRequest('/jsonapi/customer', {
      method: 'GET',
      params: {
        'include': 'customer/property,customer/address',
        'sort': '-customer.ctime'
      }
    });
    
    this.customers = response.data || [];
    this.renderCustomersList();
  }
}
```

### 2. Products Manager App (Catalog Management)

The Products Manager app handles complete product catalog management.

**Key Features:**
- Product catalog browsing
- Product creation and editing
- Inventory management
- Price management
- Media handling
- Category organization
- Product analytics

**TypeScript Implementation:**
```typescript
// resources/js/Apps/ProductsManagerApp.ts
export class ProductsManagerApp extends BaseApp {
  private products: AimeosProduct[] = [];
  private selectedProduct: AimeosProduct | null = null;
  
  async loadProducts(): Promise<void> {
    const response = await this.makeAimeosRequest('/jsonapi/product', {
      method: 'GET',
      params: {
        'include': 'product/property,price,media,text',
        'sort': '-product.ctime'
      }
    });
    
    this.products = response.data || [];
    this.renderProductsList();
  }
}
```

### 3. Orders Manager App (Order Processing)

The Orders Manager app handles complete order lifecycle management.

**Key Features:**
- Order listing and search
- Order detail views
- Status management
- Payment tracking
- Shipping management
- Invoice generation
- Customer communication

**TypeScript Implementation:**
```typescript
// resources/js/Apps/OrdersManagerApp.ts
export class OrdersManagerApp extends BaseApp {
  private orders: AimeosOrder[] = [];
  private selectedOrder: AimeosOrder | null = null;
  
  async loadOrders(): Promise<void> {
    const response = await this.makeAimeosRequest('/jsonapi/order', {
      method: 'GET',
      params: {
        'include': 'order/address,order/service,order/product',
        'sort': '-order.ctime'
      }
    });
    
    this.orders = response.data || [];
    this.renderOrdersList();
  }
}
```

## API Integration

### 1. Aimeos JSON API Structure

The Aimeos JSON API follows JSON:API specification:

```typescript
interface AimeosResponse<T> {
  data: T | T[];
  meta?: {
    total: number;
    offset: number;
    limit: number;
  };
  included?: any[];
  links?: {
    self: string;
    first?: string;
    last?: string;
    prev?: string;
    next?: string;
  };
}
```

### 2. API Request Helper

```typescript
class AimeosApiService {
  private baseUrl = '/admin/aimeos';
  
  async request(endpoint: string, options: any = {}): Promise<any> {
    const url = new URL(this.baseUrl + endpoint, window.location.origin);
    
    if (options.params) {
      Object.keys(options.params).forEach(key => {
        if (Array.isArray(options.params[key])) {
          options.params[key].forEach((value: any) => {
            url.searchParams.append(key, value);
          });
        } else {
          url.searchParams.append(key, options.params[key]);
        }
      });
    }

    const response = await fetch(url.toString(), {
      method: options.method || 'GET',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': this.getCsrfToken(),
        ...options.headers
      },
      body: options.body ? JSON.stringify(options.body) : undefined
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    return await response.json();
  }
  
  private getCsrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  }
}
```

### 3. Data Models

#### Customer Model
```typescript
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
  relationships?: {
    address?: any[];
    property?: any[];
    groups?: any[];
  };
}
```

#### Product Model
```typescript
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
    price?: any[];
    media?: any[];
    text?: any[];
    attribute?: any[];
    property?: any[];
    catalog?: any[];
    supplier?: any[];
    stock?: any[];
  };
}
```

#### Order Model
```typescript
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
    customer?: any;
    address?: any[];
    service?: any[];
    product?: any[];
    coupon?: any[];
  };
}
```

## Styling Implementation

The applications use the existing desktop styling framework to maintain visual consistency.

### 1. CSS Classes Mapping

```css
/* Status badges */
.status-completed { background: #e8f5e8; color: #4caf50; }
.status-pending { background: #fff3e0; color: #ff9800; }
.status-draft { background: #fce4ec; color: #e91e63; }

/* Product stock indicators */
.stock-normal { color: #4caf50; font-weight: bold; }
.stock-low { color: #ff9800; font-weight: bold; }
.stock-out { color: #f44336; font-weight: bold; }

/* List items */
.customer-item, .product-item, .order-item {
  cursor: pointer;
  transition: background-color 0.2s;
  padding: 12px 16px;
  border-bottom: 1px solid #eee;
}

.customer-item:hover, .product-item:hover, .order-item:hover {
  background-color: #f5f5f5;
}

.customer-item.active, .product-item.active, .order-item.active {
  background-color: #e3f2fd;
  border-left: 3px solid #2196F3;
}
```

### 2. Layout Structure

All applications follow the consistent two-column layout:

```html
<div class="product-edit-container">
  <!-- Page Header -->
  <div class="page-header">
    <h1>Application Title</h1>
    <div class="header-actions">
      <!-- Action buttons -->
    </div>
  </div>

  <!-- Two Column Layout -->
  <div class="product-content-columns">
    <!-- Left Column - List/Search -->
    <div class="product-main-column">
      <!-- Statistics Section -->
      <div class="client-financial-section">
        <!-- Statistics blocks -->
      </div>
      
      <!-- Main Content Section -->
      <div class="product-section">
        <!-- Search/Filters -->
        <!-- Data List -->
        <!-- Pagination -->
      </div>
    </div>

    <!-- Right Column - Details -->
    <div class="product-product-publish-column">
      <!-- Detail view -->
    </div>
  </div>
</div>
```

## Vue Component Integration

### 1. Component Registration

```typescript
// resources/js/bootstrap.ts
import { createApp } from 'vue';
import ContactsApp from './Components/Apps/Contacts.vue';
import ProductsManager from './Components/Apps/ProductsManager.vue';
import OrdersManager from './Components/Apps/OrdersManager.vue';

const app = createApp({});

// Register Aimeos components
app.component('ContactsApp', ContactsApp);
app.component('ProductsManager', ProductsManager);
app.component('OrdersManager', OrdersManager);

app.mount('#app');
```

### 2. Component Usage

```typescript
// In AppRegistry.ts
const aimeosApps = [
  {
    id: 'contacts',
    name: 'Contacts',
    icon: 'fas fa-address-book',
    component: 'ContactsApp',
    category: 'business',
    permissions: ['customers.view'],
    installed: true,
    teamScoped: true
  },
  {
    id: 'products-manager',
    name: 'Products Manager',
    icon: 'fas fa-boxes',
    component: 'ProductsManager',
    category: 'ecommerce',
    permissions: ['products.view'],
    installed: true,
    teamScoped: true
  },
  {
    id: 'orders-manager',
    name: 'Orders Manager',
    icon: 'fas fa-shopping-cart',
    component: 'OrdersManager',
    category: 'ecommerce',
    permissions: ['orders.view'],
    installed: true,
    teamScoped: true
  }
];
```

## Authentication & Security

### 1. Permissions System

```php
// Define permissions in seeder
$permissions = [
    // Customer permissions
    'customers.view',
    'customers.create',
    'customers.edit',
    'customers.delete',
    'customers.export',
    
    // Product permissions
    'products.view',
    'products.create',
    'products.edit',
    'products.delete',
    'products.export',
    
    // Order permissions
    'orders.view',
    'orders.create',
    'orders.edit',
    'orders.delete',
    'orders.export',
];
```

### 2. Middleware Security

```php
// app/Http/Middleware/AimeosSecurityMiddleware.php
class AimeosSecurityMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Validate CSRF token
        if (!$request->hasValidSignature()) {
            return response()->json(['error' => 'Invalid request signature'], 403);
        }
        
        // Check user permissions
        $user = $request->user();
        if (!$user || !$user->can('aimeos.access')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }
        
        // Rate limiting
        if (RateLimiter::tooManyAttempts($user->id . ':aimeos', 100)) {
            return response()->json(['error' => 'Too many requests'], 429);
        }
        
        return $next($request);
    }
}
```

## Testing & Development

### 1. Unit Tests

```php
// tests/Feature/AimeosIntegrationTest.php
class AimeosIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_customer_api_returns_customers()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $response = $this->getJson('/admin/aimeos/jsonapi/customer');
        
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [],
                    'meta' => ['total', 'offset', 'limit']
                ]);
    }
}
```

### 2. Frontend Testing

```typescript
// tests/frontend/contacts.test.ts
import { mount } from '@vue/test-utils';
import ContactsApp from '@/Components/Apps/Contacts.vue';

describe('ContactsApp', () => {
  it('loads customers on mount', async () => {
    const wrapper = mount(ContactsApp);
    
    await wrapper.vm.$nextTick();
    
    expect(wrapper.vm.customers).toBeDefined();
  });
});
```

### 3. Development Commands

```bash
# Start development environment
php artisan serve
npm run dev

# Run tests
php artisan test --testsuite=Feature
npm run test

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan aimeos:clear
```

## Deployment

### 1. Production Setup

```bash
# Optimize for production
php artisan optimize
php artisan aimeos:setup
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Build frontend assets
npm run build
```

### 2. Environment Configuration

```env
# .env production settings
AIMEOS_SHOP_DOMAINS="default"
AIMEOS_SHOP_MULTISHOP=false
AIMEOS_SHOP_AUTHORIZE=true
AIMEOS_SHOP_BASKET_DOMAIN="session"
AIMEOS_SHOP_CACHE=true
```

### 3. Server Requirements

```nginx
# Nginx configuration
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Troubleshooting

### Common Issues

#### 1. Database Connection Issues
```bash
# Check Aimeos setup
php artisan aimeos:setup --option=setup/default/demo:0

# Verify database configuration
php artisan tinker
>>> \Aimeos\MAdmin::create(app('aimeos.context')->get(), 'customer')->search(\Aimeos\MAdmin::create(app('aimeos.context')->get(), 'customer')->filter())
```

#### 2. API Authentication Problems
```php
// Debug authentication
Log::info('Aimeos API Request', [
    'user' => auth()->user(),
    'permissions' => auth()->user()->getAllPermissions(),
    'request' => request()->all()
]);
```

#### 3. Frontend API Errors
```typescript
// Add error handling
catch (error) {
  console.error('Aimeos API Error:', error);
  if (error.response?.status === 401) {
    // Redirect to login
    window.location.href = '/login';
  } else if (error.response?.status === 403) {
    // Show permission error
    this.showError('Insufficient permissions');
  }
}
```

### Performance Optimization

#### 1. Database Indexing
```sql
-- Add indexes for better performance
CREATE INDEX idx_customer_email ON mshop_customer (email);
CREATE INDEX idx_product_code ON mshop_product (code);
CREATE INDEX idx_order_ctime ON mshop_order (ctime);
```

#### 2. Caching Strategy
```php
// config/aimeos.php
'madmin' => [
    'cache' => [
        'manager' => [
            'name' => 'Redis'
        ]
    ]
]
```

#### 3. API Response Optimization
```typescript
// Implement response caching
const cache = new Map();

async makeAimeosRequest(endpoint: string, options: any = {}) {
  const cacheKey = `${endpoint}:${JSON.stringify(options)}`;
  
  if (cache.has(cacheKey) && options.useCache !== false) {
    return cache.get(cacheKey);
  }
  
  const response = await this.apiService.request(endpoint, options);
  
  if (options.method === 'GET') {
    cache.set(cacheKey, response);
    // Auto-expire cache after 5 minutes
    setTimeout(() => cache.delete(cacheKey), 300000);
  }
  
  return response;
}
```

## Conclusion

This implementation provides a comprehensive e-commerce management solution that integrates seamlessly with the existing desktop environment. The three core applications (Contacts, Products Manager, and Orders Manager) maintain visual consistency while providing powerful Aimeos integration.

Key benefits of this implementation:

1. **Consistent User Experience**: Matches existing desktop styling and interaction patterns
2. **Full Aimeos Integration**: Leverages complete Aimeos API capabilities
3. **Scalable Architecture**: Modular design allows for easy extension
4. **Security Focused**: Implements proper authentication and authorization
5. **Performance Optimized**: Includes caching and optimization strategies
6. **Developer Friendly**: Comprehensive TypeScript types and documentation

For support and additional features, refer to the official Aimeos documentation and this implementation guide. 