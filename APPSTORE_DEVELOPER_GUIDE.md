# 🚀 AppStore Developer Guide

**Complete Guide for Building Apps for the Multi-Tenant AppStore**

This comprehensive guide will help you understand how to develop, publish, and maintain applications for our multi-tenant AppStore system. As a tenant, you'll work through a web interface to upload structured app packages without requiring server access or terminal commands.

---

## 📋 Table of Contents

- [Overview](#overview)
- [Development Environment Setup](#development-environment-setup)
- [App Types](#app-types)
- [App Package Structure](#app-package-structure)
- [Web Interface Submission](#web-interface-submission)
- [Module Development](#module-development)
- [Vue Component Apps](#vue-component-apps)
- [Iframe Applications](#iframe-applications)
- [API Integration Apps](#api-integration-apps)
- [App Publishing Workflow](#app-publishing-workflow)
- [Testing & Quality Assurance](#testing--quality-assurance)
- [Best Practices](#best-practices)
- [Advanced Features](#advanced-features)
- [Troubleshooting](#troubleshooting)

---

## 🎯 Overview

### What is the AppStore?

Our AppStore is a multi-tenant marketplace where tenant developers can:
- Publish applications for their tenant or other tenants
- Monetize apps with flexible pricing models (free, one-time, monthly, freemium)
- Leverage the platform's multi-tenant architecture
- Access tenant-specific APIs and services
- Submit apps through a simple web interface

### How It Works for Tenants

1. **No Server Access Required**: Develop apps locally, package them, and upload through the web interface
2. **Structured Upload**: Submit your app as a ZIP archive with a defined file structure
3. **Web-Based Submission**: Use the AppStore's "Add App" interface to provide app details and upload files
4. **Automatic Processing**: The platform handles installation, configuration, and distribution
5. **Publishing Options**: Make apps private (tenant-only) or public (with admin approval)
6. **AI Security Scanning**: Automatic security analysis of your uploaded code

### Platform Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    AppStore Platform                        │
├─────────────────────────────────────────────────────────────┤
│  Multi-Tenant Architecture                                 │
│  ├─ Tenant Isolation (Database, Storage, Cache)            │
│  ├─ Domain-based Tenant Identification                     │
│  └─ Tenant-aware Broadcasting & Sessions                   │
├─────────────────────────────────────────────────────────────┤
│  Modular App System                                        │
│  ├─ Dynamic App Loading & Enabling                         │
│  ├─ Tenant-specific App Configuration                      │
│  └─ Automatic Package Processing                           │
├─────────────────────────────────────────────────────────────┤
│  Frontend Stack                                            │
│  ├─ Modern JavaScript Framework                            │
│  ├─ Single Page Application Experience                     │
│  └─ Responsive Design System                               │
├─────────────────────────────────────────────────────────────┤
│  Storage & File Management                                 │
│  ├─ Cloud Storage (S3-compatible)                         │
│  ├─ Tenant-specific Storage Buckets                       │
│  └─ Integrated File Explorer                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔧 Development Environment Setup

### Local Development Requirements

Since you'll be developing apps that run within the platform, you only need:

- **Text Editor/IDE**: VS Code, PHPStorm, or any code editor
- **Web Browser**: For testing and accessing the AppStore interface
- **Archive Tool**: For creating ZIP packages of your apps
- **Optional**: Local web server for testing (XAMPP, WAMP, or similar)

### No Server Access Needed

✅ **What You DON'T Need:**
- Server access or SSH
- Database management
- Package managers or command-line tools
- Complex setup procedures
- Server deployment knowledge

✅ **What The Platform Handles:**
- App installation and configuration
- Database setup and migrations
- File processing and storage
- Security and sandboxing
- Multi-tenant isolation

### Getting Started

1. **Access the AppStore**: Log into your tenant dashboard and open the AppStore
2. **Click "Add App"**: Use the web interface to submit your application
3. **Prepare Your Files**: Package your app according to the required structure
4. **Upload and Configure**: Provide app details and upload your ZIP package
5. **Test and Publish**: Test your app and choose publishing options

---

## 📱 App Types

Our AppStore supports multiple application types, each with specific use cases and development approaches:

### 1. Laravel Modules (Recommended)
**Full-featured backend applications with complete Laravel functionality**

- ✅ **Use Cases**: Complex business logic, database operations, API integrations
- ✅ **Features**: Full Laravel framework access, tenant-aware, scalable
- ✅ **Examples**: CRM systems, inventory management, advanced analytics

### 2. Vue Components
**Frontend-focused applications using Vue 3**

- ✅ **Use Cases**: Interactive dashboards, data visualization, UI-heavy apps
- ✅ **Features**: Reactive UI, TypeScript support, component composition
- ✅ **Examples**: Charts, calculators, interactive forms

### 3. Iframe Applications
**External web applications embedded in the platform**

- ✅ **Use Cases**: Third-party integrations, existing web apps
- ✅ **Features**: Isolated execution, external hosting, easy integration
- ✅ **Examples**: External tools, specialized software, web-based applications

### 4. WordPress Plugins
**WordPress plugins adapted for the platform**

- ✅ **Use Cases**: WordPress-specific functionality, content management
- ✅ **Features**: WordPress ecosystem access, plugin architecture
- ✅ **Examples**: Content editors, blog management, WordPress tools

### 5. API Integration Apps
**Applications that connect external APIs**

- ✅ **Use Cases**: Service integrations, data synchronization
- ✅ **Features**: OAuth flows, webhook handling, data transformation
- ✅ **Examples**: Social media integrations, payment gateways, communication tools

### 6. External Links
**Simple redirects to external services**

- ✅ **Use Cases**: Quick access to external tools
- ✅ **Features**: Simple configuration, direct links
- ✅ **Examples**: External documentation, tools, services

---

## 📦 App Package Structure

Your app must be packaged as a ZIP file with a specific structure. The platform will automatically process and install your app based on this structure.

### Module App Structure (Recommended)

```
your-app-name.zip
├── app.json                    # App metadata and configuration
├── icon.png                   # App icon (256x256px recommended)
├── screenshots/               # App screenshots (optional)
│   ├── screenshot1.png
│   ├── screenshot2.png
│   └── screenshot3.png
├── src/                       # Application source code
│   ├── Controllers/           # HTTP controllers
│   ├── Models/               # Eloquent models
│   ├── Views/                # Blade templates
│   ├── Routes/               # Route definitions
│   │   ├── web.php
│   │   └── api.php
│   ├── Migrations/           # Database migrations
│   ├── Assets/               # Frontend assets
│   │   ├── js/
│   │   ├── css/
│   │   └── images/
│   └── Config/               # Configuration files
├── composer.json             # Dependencies (optional)
└── README.md                 # Installation/usage instructions
```

### Vue Component App Structure

```
your-vue-app.zip
├── app.json                  # App metadata
├── icon.png                  # App icon
├── screenshots/              # Screenshots
├── src/
│   ├── Component.vue         # Main Vue component
│   ├── components/          # Additional components
│   ├── composables/         # Vue composables
│   ├── types/               # TypeScript definitions
│   └── assets/              # Static assets
├── package.json             # NPM dependencies
└── README.md
```

### Iframe App Structure

```
your-iframe-app.zip
├── app.json                 # App metadata with iframe config
├── icon.png                 # App icon
├── screenshots/             # Screenshots
└── README.md               # Configuration instructions
```

### Required: app.json Configuration

Every app package must include an `app.json` file with metadata:

```json
{
  "name": "Advanced Analytics Dashboard",
  "slug": "advanced-analytics-dashboard",
  "version": "1.0.0",
  "description": "Comprehensive analytics and reporting for your business",
  "detailed_description": "A powerful analytics dashboard that provides deep insights into your business performance with customizable charts, real-time data, and advanced filtering capabilities.",
  "app_type": "module",
  "developer": {
    "name": "Your Company Name",
    "email": "support@yourcompany.com",
    "website": "https://yourcompany.com"
  },
  "pricing": {
    "type": "monthly",
    "price": 49.99,
    "currency": "USD"
  },
  "requirements": {
    "minimum_version": "1.0.0"
  },
  "permissions": [
    "read_analytics",
    "export_data",
    "manage_dashboards"
  ],
  "categories": ["analytics", "business-tools"],
  "tags": ["analytics", "dashboard", "reporting", "business"],
  "icon_class": "fa-chart-line",
  "icon_bg_class": "blue-icon",
  "routes": {
    "api": "api.php",
    "web": "web.php"
  },
  "assets": {
    "css": ["assets/css/app.css"],
    "js": ["assets/js/app.js"]
  },
  "configuration": {
    "data_sources": {
      "type": "array",
      "description": "Configure data sources"
    },
    "refresh_interval": {
      "type": "integer",
      "description": "Data refresh interval in minutes",
      "default": 15,
      "min": 1,
      "max": 1440
    }
  }
}
```

### App Type Specific Configurations

#### Module Apps
```json
{
  "app_type": "module",
  "entry_point": "src/Controllers/MainController.php",
  "migrations": ["src/Migrations/"],
  "models": ["src/Models/"],
  "routes": {
    "web": "src/Routes/web.php",
    "api": "src/Routes/api.php"
  }
}
```

#### Vue Component Apps
```json
{
  "app_type": "vue",
  "entry_component": "src/Component.vue",
  "dependencies": ["package.json"],
  "build_command": "npm run build"
}
```

#### Iframe Apps
```json
{
  "app_type": "iframe",
  "iframe_config": {
    "url": "https://your-external-app.com/embed",
    "width": "100%",
    "height": "600px",
    "sandbox": ["allow-scripts", "allow-same-origin", "allow-forms"],
    "allow_fullscreen": true
  }
}
```

---

## 🌐 Web Interface Submission

### Accessing the App Submission Interface

1. **Open AppStore**: In your tenant dashboard, click on the AppStore app
2. **Find Add App Button**: Look for the "Add App" or "+" button in the AppStore interface
3. **App Submission Form**: Fill out the web form with your app details

### Submission Form Fields

The web interface will request the following information:

#### Basic Information
- **App Name**: Display name for your application
- **App Icon**: Upload PNG/JPG icon (256x256px recommended)
- **Category**: Select from available categories
- **Short Description**: Brief app description (160 characters max)
- **Detailed Description**: Comprehensive app description with features

#### Pricing & Availability
- **Pricing Type**: 
  - Free
  - One-time payment
  - Monthly subscription
  - Freemium (in-app purchases)
- **Price**: Amount (if paid)
- **Currency**: USD, EUR, etc.
- **Availability**: 
  - Private (your tenant only)
  - Public (requires admin approval)

#### Technical Details
- **App Type**: Module, Vue Component, Iframe, API Integration, External Link
- **Version**: Semantic version (e.g., 1.0.0)
- **Requirements**: Minimum platform version
- **Permissions**: Required app permissions

#### Files & Assets
- **App Package**: Upload your ZIP file
- **Screenshots**: Upload 1-5 screenshots (PNG/JPG)
- **Additional Files**: Documentation, configuration files

#### Publishing Options
- **Auto-update**: Enable automatic updates
- **Beta Testing**: Release as beta version first
- **Admin Approval**: Submit for review (required for public apps)

### Upload Process

1. **Prepare Package**: Create your ZIP file following the structure guidelines
2. **Fill Form**: Complete all required fields in the web interface
3. **Upload Files**: Upload your app package and screenshots
4. **Review**: Preview your app listing before submission
5. **Submit**: Click submit to process your app

### AI Security Scanning

Every uploaded app is automatically scanned by our **tenant-aware AI security system**:

#### **Enhanced Tenant-Aware Analysis**
Our AI security scanner has been enhanced to understand the multi-tenant architecture and provide more accurate security assessments:

- **🏢 Tenant Context Awareness**: Analyzes apps within the context of your tenant environment
- **📱 Installed Apps Analysis**: Considers your existing apps to reduce compatibility false positives  
- **🔒 Multi-Database Isolation Understanding**: Recognizes legitimate tenant operations vs. security threats
- **⚡ Reduced False Positives**: Smart filtering prevents legitimate tenant operations from being flagged
- **🎯 Environment-Specific Recommendations**: Provides recommendations tailored to your tenant setup

#### **What Gets Scanned**
- **Source Code Analysis**: AI reviews all code files for security vulnerabilities with tenant context
- **Tenancy Compliance**: Validates proper use of tenant isolation features (database, storage, cache)
- **Malware Detection**: Automated scanning for malicious patterns while understanding legitimate tenant operations
- **Dependency Check**: Analysis of third-party packages for known vulnerabilities with severity adjustment based on your environment
- **Configuration Review**: Security assessment of app configuration and permissions with tenant best practices
- **Pattern Recognition**: AI identifies potentially dangerous code patterns while allowing safe tenant operations

#### **Tenant-Aware Security Features**
✅ **Legitimate Operations (NOT flagged as vulnerabilities):**
- `tenant('id')` - Getting current tenant ID
- `Storage::disk('tenant')` - Using tenant storage disk  
- `->where('tenant_id', tenant('id'))` - Tenant scoping queries
- `cache()->tags(['tenant:'.tenant('id')])` - Tenant cache tags
- `Model::tenantScoped()` - Tenant-aware model scoping
- `tenancy()->initialized` - Checking tenant context

❌ **Actual Security Concerns (WILL be flagged):**
- Direct central database access bypassing tenant isolation
- Cross-tenant data access attempts
- Raw SQL without tenant scoping
- File operations outside tenant boundaries  
- Cache operations without tenant isolation
- Hardcoded credentials or secrets
- Unvalidated user input leading to XSS/SQLi
- Command injection or code execution vulnerabilities

#### **Security Risk Levels**
- 🟢 **Low Risk**: Minor issues, app approved automatically with recommendations
- 🟡 **Medium Risk**: Some concerns, app approved with tenant-specific recommendations
- 🟠 **High Risk**: Significant issues, may require manual review or automatic rejection
- 🔴 **Critical Risk**: Serious security threats or tenancy violations, app blocked automatically

#### **Intelligent Recommendations**
The AI provides context-aware recommendations based on:
- **Your Tenant Environment**: Existing apps, team plan, user count
- **Multi-Tenant Best Practices**: Proper use of tenant isolation features
- **Compatibility Guidance**: How to integrate with your existing app ecosystem
- **Security Improvements**: Specific, actionable security enhancements
- **Performance Optimization**: Tenant-aware performance suggestions

#### **If Your App Is Blocked**
1. **Immediate Notification**: You'll receive detailed security report with tenant context
2. **Vulnerability Details**: Specific issues found with tenant isolation impact assessment
3. **Smart Recommendations**: AI-generated suggestions tailored to your tenant environment
4. **False Positive Filtering**: Reduced chance of legitimate tenant operations being flagged
5. **Admin Review Option**: Appeal process through central administration
6. **Resubmission**: Upload fixed version after addressing tenant-aware recommendations

#### **Tenant-Specific Security Benefits**
- **Environment Understanding**: AI knows your existing apps and their permissions
- **Plan-Aware Analysis**: Considers your subscription plan limitations and features
- **Team Context**: Understands your team size and collaboration patterns
- **Integration Guidance**: Recommendations for working with your existing app ecosystem
- **Tenancy Compliance**: Ensures your app follows multi-tenant best practices

#### **Admin Override Process**
- Central administrators can review blocked apps with full tenant context
- Manual security assessment includes tenant isolation review
- Approval possible with tenant-specific conditions or monitoring
- Clear documentation of override reasons and tenant impact

### Processing & Approval

After submission:

1. **File Upload**: Package uploaded and extracted for analysis
2. **Automatic Processing**: Platform extracts and validates your package
3. **AI Security & Compatibility Scan**: Automatic compatibility checks - A.i. runs a Comprehensive automated security analysis and compatiblity 3. 4. **Compatibility errors**: AI determines errors and tells user about them and how to fix them
5. **Risk Assessment**: AI determines security risk level and threat score
6. **Automatic Decision**: 
   - ✅ **Low/Medium Risk**: App approved automatically
   - ⚠️ **High Risk**: Flagged for review but may be approved
   - ❌ **Critical Risk**: App blocked, requires admin intervention
7. **Admin Review**: (If blocked by security) Manual review by security team for privat/public apps. 
8. **Admin Review**: (If Security check passes - Public apps only) Manual review by platform administrators
9. **Final Approval**: App published or permanently rejected
10. **Tenant Notification**: Email and in-app notifications about status
11. **Publication**: App becomes available in the AppStore


---

## 🔌 API Documentation

### Core AppStore APIs

All API endpoints are tenant-aware and require authentication via Laravel Sanctum.

#### Base URL Structure
```
https://{tenant-domain}/api/app-store/
```

### 📊 Browse Apps

**GET** `/api/app-store/browse`

Retrieve apps with filtering and pagination.

```typescript
interface BrowseRequest {
  category?: string          // Filter by category slug
  search?: string           // Search term
  sort?: 'popular' | 'newest' | 'rating' | 'price_low' | 'price_high' | 'name'
  pricing?: 'free' | 'paid' | 'freemium'
  page?: number            // Page number
  per_page?: number        // Items per page (max 50)
}

interface BrowseResponse {
  apps: AppStoreApp[]
  pagination: {
    current_page: number
    last_page: number
    per_page: number
    total: number
    has_more: boolean
  }
}
```

**Example Request:**
```bash
curl -X GET "https://tenant.example.com/api/app-store/browse?category=ecommerce&sort=popular" \
  -H "Authorization: Bearer {token}" \
  -H "Accept: application/json"
```

### 📱 App Details

**GET** `/api/app-store/{slug}`

Get detailed information about a specific app.

```typescript
interface AppDetailsResponse {
  app: AppStoreApp
  has_purchased: boolean
  is_installed: boolean
  can_install: boolean
  installation_price: number
  related_apps: AppStoreApp[]
}
```

### 💰 Purchase App

**POST** `/api/app-store/{slug}/purchase`

Purchase a paid application.

```typescript
interface PurchaseRequest {
  payment_method: string
  subscription_type?: 'monthly' | 'yearly'
}

interface PurchaseResponse {
  purchase_id: number
  payment_intent: {
    id: string
    client_secret: string
    amount: number
    currency: string
  }
}
```

### 📥 Install App

**POST** `/api/app-store/{slug}/install`

Install an application to the current tenant.

```typescript
interface InstallResponse {
  message: string
  installation: {
    id: number
    app_id: string
    app_type: string
    module_name?: string
    installation_data: object
    created_at: string
  }
}
```

### 🗑️ Uninstall App

**DELETE** `/api/app-store/{slug}/uninstall`

Remove an installed application.

```typescript
interface UninstallResponse {
  message: string
}
```

### ⭐ Submit Review

**POST** `/api/app-store/{slug}/review`

Submit a review for an installed app.

```typescript
interface ReviewRequest {
  rating: number      // 1-5 stars
  title?: string      // Optional review title
  review?: string     // Optional review text
}

interface ReviewResponse {
  message: string
  review: {
    id: number
    rating: number
    title: string
    review: string
    is_verified_purchase: boolean
    created_at: string
  }
}
```

---

## 🧩 Module Development

Modules are the most powerful app type, providing full framework access within tenant context. You develop these locally and upload as structured packages.

### Local Module Development Structure

Create your module with this structure locally, then package and upload:

```
your-ecommerce-app/
├── app.json                   # App metadata (required)
├── icon.png                  # App icon
├── screenshots/              # App screenshots
├── src/
│   ├── Controllers/          # HTTP controllers
│   │   └── ProductController.php
│   ├── Models/               # Eloquent models
│   │   └── Product.php
│   ├── Views/                # Templates
│   │   └── dashboard.blade.php
│   ├── Routes/               # Route definitions
│   │   ├── web.php
│   │   └── api.php
│   ├── Migrations/           # Database migrations
│   │   └── create_products_table.php
│   ├── Assets/               # Frontend assets
│   │   ├── css/
│   │   │   └── app.css
│   │   ├── js/
│   │   │   └── app.js
│   │   └── images/
│   └── Config/               # Configuration files
│       └── config.php
├── composer.json             # Dependencies (optional)
└── README.md
```

### Development Workflow

1. **Create Local Directory**: Set up your app structure locally
2. **Develop Your Code**: Write controllers, models, views, etc.
3. **Test Locally**: Use a local web server to test functionality
4. **Package for Upload**: Create ZIP file with proper structure
5. **Submit via Web Interface**: Upload and configure through AppStore

### Module Configuration

**Modules/EcommerceManager/Config/config.php**
```php
<?php

return [
    'name' => 'EcommerceManager',
    'tenant_aware' => true,
    'requires_ai_tokens' => false,
    'permissions' => [
        'manage_products',
        'view_orders',
        'export_data'
    ],
    'api_endpoints' => [
        'products' => '/api/ecommerce/products',
        'orders' => '/api/ecommerce/orders'
    ],
    'webhooks' => [
        'order_created' => '/webhooks/order-created',
        'payment_completed' => '/webhooks/payment-completed'
    ],
    'dependencies' => [
        'php' => '>=8.1',
        'laravel' => '>=10.0'
    ]
];
```

### Tenant-Aware Model

**Modules/EcommerceManager/Entities/Product.php**
```php
<?php

namespace Modules\EcommerceManager\Entities;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Product extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'description',
        'price',
        'sku',
        'stock_quantity',
        'team_id'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer'
    ];

    // Tenant isolation through team relationship
    public function team()
    {
        return $this->belongsTo(\App\Models\Team::class);
    }

    // Global scope for tenant isolation
    protected static function booted()
    {
        static::addGlobalScope('team', function ($query) {
            if (auth()->check() && auth()->user()->currentTeam) {
                $query->where('team_id', auth()->user()->currentTeam->id);
            }
        });
    }
}
```

### Controller with Tenant Context

**Modules/EcommerceManager/Http/Controllers/ProductController.php**
```php
<?php

namespace Modules\EcommerceManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\EcommerceManager\Entities\Product;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'ilike', "%{$search}%");
            })
            ->paginate($request->per_page ?? 20);

        return response()->json($products);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'sku' => 'required|string|unique:products,sku',
            'stock_quantity' => 'required|integer|min:0'
        ]);

        $product = Product::create([
            ...$request->validated(),
            'team_id' => auth()->user()->currentTeam->id
        ]);

        return response()->json($product, 201);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json($product);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'stock_quantity' => 'sometimes|integer|min:0'
        ]);

        $product->update($request->validated());

        return response()->json($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
```

### API Routes

**Modules/EcommerceManager/Routes/api.php**
```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\EcommerceManager\Http\Controllers\ProductController;
use Modules\EcommerceManager\Http\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| Module API Routes
|--------------------------------------------------------------------------
|
| Tenant-aware API routes for the EcommerceManager module
|
*/

Route::middleware(['auth:sanctum', 'tenant-aware'])->prefix('ecommerce')->group(function () {
    // Products
    Route::apiResource('products', ProductController::class);
    
    // Orders
    Route::apiResource('orders', OrderController::class);
    Route::post('orders/{order}/fulfill', [OrderController::class, 'fulfill']);
    Route::post('orders/{order}/refund', [OrderController::class, 'refund']);
    
    // Analytics
    Route::get('analytics/sales', [AnalyticsController::class, 'sales']);
    Route::get('analytics/products', [AnalyticsController::class, 'products']);
});
```

### Frontend Integration (Vue Component)

**Modules/EcommerceManager/Resources/assets/components/ProductManager.vue**
```vue
<template>
  <div class="product-manager">
    <div class="header">
      <h2>Product Management</h2>
      <button @click="showCreateModal = true" class="btn-primary">
        Add Product
      </button>
    </div>

    <div class="search-filters">
      <input
        v-model="searchTerm"
        type="text"
        placeholder="Search products..."
        class="search-input"
        @input="debouncedSearch"
      />
    </div>

    <div class="products-grid">
      <div
        v-for="product in products"
        :key="product.id"
        class="product-card"
        @click="editProduct(product)"
      >
        <h3>{{ product.name }}</h3>
        <p class="description">{{ product.description }}</p>
        <div class="product-meta">
          <span class="price">${{ product.price }}</span>
          <span class="stock">Stock: {{ product.stock_quantity }}</span>
        </div>
      </div>
    </div>

    <!-- Create/Edit Modal -->
    <ProductModal
      v-if="showCreateModal || selectedProduct"
      :product="selectedProduct"
      @saved="handleProductSaved"
      @cancelled="closeModal"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { debounce } from 'lodash-es'
import { apiService } from '@/Tenant/ApiService'

interface Product {
  id: number
  name: string
  description: string
  price: number
  sku: string
  stock_quantity: number
}

const products = ref<Product[]>([])
const searchTerm = ref('')
const showCreateModal = ref(false)
const selectedProduct = ref<Product | null>(null)

const loadProducts = async (search = '') => {
  try {
    const response = await apiService.get('/api/ecommerce/products', {
      params: { search }
    })
    products.value = response.data.data
  } catch (error) {
    console.error('Failed to load products:', error)
  }
}

const debouncedSearch = debounce(() => {
  loadProducts(searchTerm.value)
}, 300)

const editProduct = (product: Product) => {
  selectedProduct.value = product
}

const handleProductSaved = (product: Product) => {
  if (selectedProduct.value) {
    // Update existing
    const index = products.value.findIndex(p => p.id === product.id)
    if (index !== -1) {
      products.value[index] = product
    }
  } else {
    // Add new
    products.value.unshift(product)
  }
  closeModal()
}

const closeModal = () => {
  showCreateModal.value = false
  selectedProduct.value = null
}

onMounted(() => {
  loadProducts()
})
</script>
```

---

## 🎨 Vue Component Apps

Vue component apps are perfect for interactive UI elements and data visualization.

### Component Structure

```typescript
// resources/js/Components/Apps/ChartDashboard.vue
<template>
  <div class="chart-dashboard">
    <div class="dashboard-header">
      <h2>Analytics Dashboard</h2>
      <div class="date-selector">
        <select v-model="selectedPeriod" @change="loadData">
          <option value="7d">Last 7 days</option>
          <option value="30d">Last 30 days</option>
          <option value="90d">Last 90 days</option>
        </select>
      </div>
    </div>

    <div class="charts-grid">
      <div class="chart-card">
        <h3>Sales Overview</h3>
        <LineChart :data="salesData" :options="chartOptions" />
      </div>
      
      <div class="chart-card">
        <h3>Top Products</h3>
        <BarChart :data="productsData" :options="chartOptions" />
      </div>
      
      <div class="chart-card">
        <h3>Revenue Distribution</h3>
        <DoughnutChart :data="revenueData" :options="chartOptions" />
      </div>
    </div>

    <div class="metrics-grid">
      <div class="metric-card">
        <div class="metric-value">{{ formatCurrency(totalRevenue) }}</div>
        <div class="metric-label">Total Revenue</div>
      </div>
      
      <div class="metric-card">
        <div class="metric-value">{{ totalOrders }}</div>
        <div class="metric-label">Total Orders</div>
      </div>
      
      <div class="metric-card">
        <div class="metric-value">{{ averageOrderValue }}</div>
        <div class="metric-label">Avg Order Value</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { Line as LineChart, Bar as BarChart, Doughnut as DoughnutChart } from 'vue-chartjs'
import { apiService } from '@/Tenant/ApiService'

interface Props {
  windowId: string
  windowData?: Record<string, any>
}

const props = defineProps<Props>()

const emit = defineEmits<{
  updateTitle: [title: string]
  updateData: [data: Record<string, any>]
}>()

// Reactive data
const selectedPeriod = ref('30d')
const salesData = ref<any>({})
const productsData = ref<any>({})
const revenueData = ref<any>({})
const totalRevenue = ref(0)
const totalOrders = ref(0)

// Computed properties
const averageOrderValue = computed(() => {
  return totalOrders.value > 0 
    ? (totalRevenue.value / totalOrders.value).toFixed(2)
    : '0.00'
})

// Chart configuration
const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      labels: {
        color: '#fff'
      }
    }
  },
  scales: {
    x: {
      ticks: { color: '#ccc' },
      grid: { color: '#333' }
    },
    y: {
      ticks: { color: '#ccc' },
      grid: { color: '#333' }
    }
  }
}

// Methods
const loadData = async () => {
  try {
    const [salesResponse, productsResponse, revenueResponse] = await Promise.all([
      apiService.get(`/api/analytics/sales?period=${selectedPeriod.value}`),
      apiService.get(`/api/analytics/products?period=${selectedPeriod.value}`),
      apiService.get(`/api/analytics/revenue?period=${selectedPeriod.value}`)
    ])

    salesData.value = salesResponse.data.chart_data
    productsData.value = productsResponse.data.chart_data
    revenueData.value = revenueResponse.data.chart_data
    totalRevenue.value = salesResponse.data.total_revenue
    totalOrders.value = salesResponse.data.total_orders

  } catch (error) {
    console.error('Failed to load analytics data:', error)
  }
}

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(amount)
}

onMounted(() => {
  emit('updateTitle', 'Analytics Dashboard')
  loadData()
})
</script>

<style scoped>
.chart-dashboard {
  @apply p-6 h-full overflow-y-auto;
}

.dashboard-header {
  @apply flex justify-between items-center mb-6;
}

.charts-grid {
  @apply grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6 mb-6;
}

.chart-card {
  @apply bg-gray-800 rounded-lg p-4 h-64;
}

.metrics-grid {
  @apply grid grid-cols-1 md:grid-cols-3 gap-6;
}

.metric-card {
  @apply bg-gray-800 rounded-lg p-6 text-center;
}

.metric-value {
  @apply text-3xl font-bold text-blue-400 mb-2;
}

.metric-label {
  @apply text-gray-400 text-sm;
}
</style>
```

### App Registration

**resources/js/Apps/ChartDashboardApp.ts**
```typescript
import { createApp } from 'vue'
import ChartDashboard from '@/Components/Apps/ChartDashboard.vue'

export class ChartDashboardApp {
  private app: any

  constructor(
    private container: HTMLElement,
    private windowId: string,
    private windowData?: Record<string, any>
  ) {}

  mount() {
    this.app = createApp(ChartDashboard, {
      windowId: this.windowId,
      windowData: this.windowData
    })

    this.app.mount(this.container)
  }

  unmount() {
    if (this.app) {
      this.app.unmount()
    }
  }

  updateData(data: Record<string, any>) {
    // Update app data if needed
    this.windowData = { ...this.windowData, ...data }
  }
}
```

---

## 🌐 Iframe Applications

Iframe apps allow integration of external web applications within the platform.

### Configuration Schema

```typescript
interface IframeConfig {
  url: string                    // Application URL
  width?: string                // Width (e.g., "100%", "800px")
  height?: string               // Height (e.g., "600px", "100vh")
  sandbox?: string[]            // Sandbox permissions
  allowFullscreen?: boolean     // Allow fullscreen
  allowedOrigins?: string[]     // CORS allowed origins
  authentication?: {
    method: 'oauth' | 'apikey' | 'basic' | 'none'
    credentials?: Record<string, any>
  }
  postMessage?: {
    enabled: boolean
    allowedMethods: string[]
  }
}
```

### AppStore Registration

```php
// Database record for iframe app
$iframeApp = AppStoreApp::create([
    'name' => 'External Design Tool',
    'description' => 'Professional design tool for creating graphics',
    'app_type' => 'iframe',
    'pricing_type' => 'monthly',
    'monthly_price' => 29.99,
    'iframe_config' => [
        'url' => 'https://external-design-tool.com/embed',
        'width' => '100%',
        'height' => '100vh',
        'sandbox' => [
            'allow-scripts',
            'allow-same-origin',
            'allow-forms',
            'allow-popups'
        ],
        'allowFullscreen' => true,
        'authentication' => [
            'method' => 'oauth',
            'provider' => 'external_design_tool'
        ],
        'postMessage' => [
            'enabled' => true,
            'allowedMethods' => ['save', 'export', 'import']
        ]
    ]
]);
```

### Frontend Implementation

```typescript
// resources/js/Apps/IframeApp.ts
export class IframeApp {
  private iframe: HTMLIFrameElement
  private messageHandlers: Map<string, Function> = new Map()

  constructor(
    private container: HTMLElement,
    private config: IframeConfig,
    private authToken?: string
  ) {
    this.createIframe()
    this.setupMessageHandling()
  }

  private createIframe() {
    this.iframe = document.createElement('iframe')
    
    // Configure iframe
    this.iframe.src = this.buildUrl()
    this.iframe.width = this.config.width || '100%'
    this.iframe.height = this.config.height || '600px'
    this.iframe.frameBorder = '0'
    
    // Apply sandbox permissions
    if (this.config.sandbox) {
      this.iframe.sandbox.add(...this.config.sandbox)
    }
    
    // Allow fullscreen if enabled
    if (this.config.allowFullscreen) {
      this.iframe.allowFullscreen = true
    }

    this.container.appendChild(this.iframe)
  }

  private buildUrl(): string {
    const url = new URL(this.config.url)
    
    // Add authentication if configured
    if (this.config.authentication?.method === 'apikey' && this.authToken) {
      url.searchParams.set('token', this.authToken)
    }
    
    // Add tenant context
    url.searchParams.set('tenant', window.tenantId)
    url.searchParams.set('user', window.userId)
    
    return url.toString()
  }

  private setupMessageHandling() {
    if (!this.config.postMessage?.enabled) return

    window.addEventListener('message', (event) => {
      // Verify origin
      const allowedOrigins = this.config.allowedOrigins || [new URL(this.config.url).origin]
      if (!allowedOrigins.includes(event.origin)) return

      // Handle message
      const { method, data } = event.data
      if (this.config.postMessage?.allowedMethods.includes(method)) {
        this.handleMessage(method, data)
      }
    })
  }

  private handleMessage(method: string, data: any) {
    const handler = this.messageHandlers.get(method)
    if (handler) {
      handler(data)
    }
  }

  public onMessage(method: string, handler: Function) {
    this.messageHandlers.set(method, handler)
  }

  public sendMessage(method: string, data: any) {
    this.iframe.contentWindow?.postMessage({ method, data }, this.config.url)
  }

  public destroy() {
    this.iframe.remove()
    this.messageHandlers.clear()
  }
}
```

---

## 🔗 API Integration Apps

API integration apps connect external services and APIs to the platform.

### OAuth Flow Implementation

```php
// Modules/ApiIntegration/Http/Controllers/OAuthController.php
<?php

namespace Modules\ApiIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\ApiIntegration\Services\OAuthService;

class OAuthController extends Controller
{
    public function __construct(private OAuthService $oauthService)
    {
    }

    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => 'required|string|in:github,google,slack,shopify',
            'scopes' => 'array'
        ]);

        $authUrl = $this->oauthService->getAuthorizationUrl(
            $request->provider,
            $request->scopes ?? []
        );

        return response()->json([
            'auth_url' => $authUrl,
            'state' => session('oauth_state')
        ]);
    }

    public function callback(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'state' => 'required|string',
            'provider' => 'required|string'
        ]);

        try {
            $token = $this->oauthService->exchangeCodeForToken(
                $request->provider,
                $request->code,
                $request->state
            );

            // Store token for tenant
            $this->oauthService->storeTokenForTenant(
                auth()->user()->currentTeam,
                $request->provider,
                $token
            );

            return response()->json([
                'success' => true,
                'message' => 'Integration connected successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect integration'
            ], 400);
        }
    }

    public function disconnect(Request $request): JsonResponse
    {
        $request->validate([
            'provider' => 'required|string'
        ]);

        $this->oauthService->disconnectProvider(
            auth()->user()->currentTeam,
            $request->provider
        );

        return response()->json([
            'success' => true,
            'message' => 'Integration disconnected successfully'
        ]);
    }
}
```

### API Service Implementation

```php
// Modules/ApiIntegration/Services/ExternalApiService.php
<?php

namespace Modules\ApiIntegration\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class ExternalApiService
{
    public function __construct(
        private string $provider,
        private array $credentials
    ) {
    }

    public function makeRequest(string $endpoint, array $params = []): array
    {
        $client = $this->getHttpClient();
        
        $response = $client->get($endpoint, $params);
        
        if ($response->failed()) {
            throw new \Exception("API request failed: {$response->body()}");
        }
        
        return $response->json();
    }

    public function postData(string $endpoint, array $data): array
    {
        $client = $this->getHttpClient();
        
        $response = $client->post($endpoint, $data);
        
        if ($response->failed()) {
            throw new \Exception("API request failed: {$response->body()}");
        }
        
        return $response->json();
    }

    private function getHttpClient(): PendingRequest
    {
        $client = Http::timeout(30)
            ->retry(3, 100)
            ->withUserAgent('TenantApp/1.0');

        // Add authentication
        switch ($this->credentials['type']) {
            case 'bearer':
                $client->withToken($this->credentials['token']);
                break;
            case 'basic':
                $client->withBasicAuth(
                    $this->credentials['username'],
                    $this->credentials['password']
                );
                break;
            case 'api_key':
                $client->withHeaders([
                    $this->credentials['header'] => $this->credentials['key']
                ]);
                break;
        }

        return $client;
    }

    public function getCachedData(string $key, callable $callback, int $ttl = 3600): mixed
    {
        $cacheKey = "api_integration:{$this->provider}:{$key}";
        
        return Cache::remember($cacheKey, $ttl, $callback);
    }
}
```

---

## 📝 App Publishing Workflow

### 1. Development Phase

1. **Create Local Directory**: Set up your app structure locally
2. **Develop Your Code**: Write your app functionality
3. **Test Locally**: Test with a local development environment
4. **Package Your App**: Create a ZIP file following the required structure

### 2. App Metadata

Create comprehensive app metadata for the AppStore:

```php
// database/seeders/YourAppSeeder.php
<?php

use App\Models\AppStoreApp;
use App\Models\AppStoreCategory;

class YourAppSeeder extends Seeder
{
    public function run()
    {
        $app = AppStoreApp::create([
            'name' => 'Advanced Analytics Dashboard',
            'slug' => 'advanced-analytics-dashboard',
            'description' => 'Comprehensive analytics and reporting for your business',
            'detailed_description' => 'A powerful analytics dashboard that provides deep insights into your business performance with customizable charts, real-time data, and advanced filtering capabilities.',
            'app_type' => 'laravel_module',
            'module_name' => 'AnalyticsDashboard',
            'version' => '1.0.0',
            'developer_name' => 'Your Company Name',
            'developer_email' => 'support@yourcompany.com',
            'developer_website' => 'https://yourcompany.com',
            'pricing_type' => 'monthly',
            'monthly_price' => 49.99,
            'currency' => 'USD',
            'minimum_php_version' => '8.1',
            'minimum_laravel_version' => '10.0',
            'license' => 'Commercial',
            'tags' => ['analytics', 'dashboard', 'reporting', 'business'],
            'screenshots' => [
                '/img/apps/analytics/screenshot1.png',
                '/img/apps/analytics/screenshot2.png',
                '/img/apps/analytics/screenshot3.png'
            ],
            'icon' => '/img/apps/analytics/icon.png',
            'changelog' => [
                '1.0.0' => [
                    'Initial release',
                    'Real-time analytics',
                    'Customizable dashboards',
                    'Export functionality'
                ]
            ],
            'configuration_schema' => [
                'data_sources' => [
                    'type' => 'array',
                    'description' => 'Configure data sources',
                    'items' => [
                        'name' => ['type' => 'string', 'required' => true],
                        'type' => ['type' => 'string', 'enum' => ['database', 'api', 'file']],
                        'config' => ['type' => 'object']
                    ]
                ],
                'refresh_interval' => [
                    'type' => 'integer',
                    'description' => 'Data refresh interval in minutes',
                    'default' => 15,
                    'min' => 1,
                    'max' => 1440
                ]
            ],
            'permissions_required' => [
                'read_analytics',
                'export_data',
                'manage_dashboards'
            ],
            'requires_admin_approval' => false,
            'auto_update_enabled' => true,
            'is_published' => false, // Will be set to true after approval
            'approval_status' => 'pending'
        ]);

        // Associate with categories
        $categories = AppStoreCategory::whereIn('slug', ['analytics', 'business-tools'])->get();
        $app->categories()->attach($categories);
    }
}
```

### 3. Web Submission Process

1. **Access AppStore**: Open the AppStore in your tenant dashboard
2. **Click "Add App"**: Use the submission form interface
3. **Fill App Details**: Provide name, description, pricing, categories
4. **Upload Files**: Submit your ZIP package, icon, and screenshots
5. **Choose Visibility**: 
   - **Private**: Available only in your tenant (auto-approved)
   - **Public**: Requires admin approval for marketplace publication
6. **Submit for Processing**: Click submit to upload and process your app

### 4. Review & Approval

The platform administrators will review your app based on:

- ✅ **Code Quality**: Clean, well-documented code
- ✅ **Security**: No security vulnerabilities
- ✅ **Performance**: Efficient resource usage
- ✅ **Tenant Isolation**: Proper multi-tenant implementation
- ✅ **User Experience**: Intuitive interface and functionality
- ✅ **Documentation**: Complete API documentation and user guide

### 5. Publication

Once approved, your app will be available in the AppStore:

- **Automatic Processing**: Platform validates and processes your package
- **Security Checks**: Automated security scanning and compatibility verification  
- **Installation Testing**: Platform tests app installation in isolated environment
- **Approval Notification**: You'll receive email notification of approval status
- **Live Publication**: Approved apps become available immediately in the marketplace

---

## 🧪 Testing & Quality Assurance

### Unit Testing

```php
// Tests/Feature/ProductControllerTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Team;
use Modules\EcommerceManager\Entities\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->team = Team::factory()->create();
        $this->user->teams()->attach($this->team);
        $this->user->switchTeam($this->team);
    }

    public function test_can_create_product()
    {
        $productData = [
            'name' => 'Test Product',
            'description' => 'A test product',
            'price' => 29.99,
            'sku' => 'TEST-001',
            'stock_quantity' => 100
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/ecommerce/products', $productData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'description',
                'price',
                'sku',
                'stock_quantity',
                'team_id'
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'team_id' => $this->team->id
        ]);
    }

    public function test_cannot_access_other_team_products()
    {
        $otherTeam = Team::factory()->create();
        $otherProduct = Product::factory()->create(['team_id' => $otherTeam->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/ecommerce/products/{$otherProduct->id}");

        $response->assertStatus(404);
    }

    public function test_product_search_is_tenant_isolated()
    {
        // Create products for current team
        Product::factory()->count(3)->create(['team_id' => $this->team->id]);
        
        // Create products for other team
        $otherTeam = Team::factory()->create();
        Product::factory()->count(5)->create(['team_id' => $otherTeam->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/ecommerce/products');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data'));
    }
}
```

### Integration Testing

```php
// Tests/Feature/AppStoreIntegrationTest.php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\AppStoreApp;
use App\Models\InstalledApp;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AppStoreIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_installation_flow()
    {
        $app = AppStoreApp::factory()->create([
            'app_type' => 'laravel_module',
            'module_name' => 'TestModule',
            'pricing_type' => 'free'
        ]);

        $user = User::factory()->create();
        $team = Team::factory()->create();
        $user->teams()->attach($team);

        // Install app
        $response = $this->actingAs($user)
            ->postJson("/api/app-store/{$app->slug}/install");

        $response->assertStatus(200);

        // Verify installation record
        $this->assertDatabaseHas('installed_apps', [
            'app_store_app_id' => $app->id,
            'team_id' => $team->id,
            'is_active' => true
        ]);

        // Verify app statistics updated
        $app->refresh();
        $this->assertEquals(1, $app->download_count);
        $this->assertEquals(1, $app->active_installs);
    }
}
```

### Frontend Testing

```typescript
// tests/unit/ProductManager.spec.ts
import { mount } from '@vue/test-utils'
import { vi } from 'vitest'
import ProductManager from '@/Components/Apps/ProductManager.vue'

// Mock API service
vi.mock('@/Tenant/ApiService', () => ({
  apiService: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn()
  }
}))

describe('ProductManager', () => {
  it('loads products on mount', async () => {
    const mockProducts = [
      { id: 1, name: 'Product 1', price: 29.99 },
      { id: 2, name: 'Product 2', price: 39.99 }
    ]

    apiService.get.mockResolvedValue({
      data: { data: mockProducts }
    })

    const wrapper = mount(ProductManager)
    await wrapper.vm.$nextTick()

    expect(apiService.get).toHaveBeenCalledWith('/api/ecommerce/products', {
      params: { search: '' }
    })
    expect(wrapper.vm.products).toEqual(mockProducts)
  })

  it('filters products by search term', async () => {
    const wrapper = mount(ProductManager)
    
    await wrapper.find('.search-input').setValue('test')
    
    // Wait for debounced search
    await new Promise(resolve => setTimeout(resolve, 350))
    
    expect(apiService.get).toHaveBeenCalledWith('/api/ecommerce/products', {
      params: { search: 'test' }
    })
  })
})
```

---

## 🚀 Deployment & Distribution

### Production Deployment

The platform handles all production deployment automatically:

✅ **Automatic Processing**:
- Package extraction and validation
- Dependency installation and optimization  
- Asset compilation and minification
- Database migration execution
- Cache optimization

✅ **No Manual Deployment Required**:
- Upload your ZIP package through the web interface
- Platform handles all server-side processing
- Automatic scaling and load balancing
- Built-in CDN and asset optimization

### App Distribution

Apps are automatically distributed through the platform's AppStore:

✅ **Marketplace Distribution**:
- Public apps appear in the main AppStore
- Private apps available only to your tenant
- Automatic version management and updates
- Built-in analytics and download tracking

✅ **Monetization Support**:
- Multiple pricing models (free, paid, subscription)
- Automated payment processing  
- Revenue tracking and reporting
- Subscription management

✅ **User Management**:
- Automatic installation and uninstallation
- User reviews and ratings system
- Update notifications
- Usage analytics and insights

---

## 💡 Best Practices

### 1. Tenant Isolation

```php
// Always use tenant-aware models
class Product extends Model
{
    use BelongsToTenant;

    protected static function booted()
    {
        static::addGlobalScope('team', function ($query) {
            if (auth()->check() && auth()->user()->currentTeam) {
                $query->where('team_id', auth()->user()->currentTeam->id);
            }
        });
    }
}

// Use middleware for API routes
Route::middleware(['auth:sanctum', 'tenant-aware'])->group(function () {
    // Your routes here
});
```

### 2. Performance Optimization

```php
// Use eager loading
$products = Product::with(['category', 'variants'])
    ->paginate(20);

// Cache expensive operations
$analytics = Cache::remember("analytics:team:{$teamId}", 3600, function () {
    return $this->calculateAnalytics();
});

// Use database indexing
Schema::table('products', function (Blueprint $table) {
    $table->index(['team_id', 'created_at']);
    $table->index(['team_id', 'sku']);
});
```

### 3. Security Guidelines

```php
// Validate all inputs
$request->validate([
    'name' => 'required|string|max:255',
    'price' => 'required|numeric|min:0|max:999999.99'
]);

// Use authorization policies
Gate::define('manage-products', function (User $user) {
    return $user->hasPermission('manage_products');
});

// Sanitize output
echo e($product->name); // Escape HTML
echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP); // Safe JSON
```

### 4. Error Handling

```php
try {
    $product = Product::create($data);
    return response()->json($product, 201);
} catch (ValidationException $e) {
    return response()->json(['errors' => $e->errors()], 422);
} catch (\Exception $e) {
    Log::error('Product creation failed', [
        'error' => $e->getMessage(),
        'data' => $data,
        'user_id' => auth()->id(),
        'team_id' => auth()->user()->currentTeam->id
    ]);
    
    return response()->json(['error' => 'Failed to create product'], 500);
}
```

### 5. Security Best Practices

To avoid AI security scan blocks, follow these security guidelines:

#### **Input Validation**
```php
// ✅ Good: Proper validation
$request->validate([
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'price' => 'required|numeric|min:0|max:999999.99'
]);

// ❌ Bad: No validation, direct usage
$name = $_POST['name']; // Direct user input usage
```

#### **SQL Injection Prevention**
```php
// ✅ Good: Use query builder or prepared statements
$users = DB::table('users')->where('email', $email)->get();
$product = Product::where('id', $id)->first();

// ❌ Bad: Raw SQL with user input
$users = DB::select("SELECT * FROM users WHERE email = '$email'");
```

#### **XSS Prevention**
```php
// ✅ Good: Escape output
echo e($userInput);
echo htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

// ❌ Bad: Direct output
echo $userInput; // Vulnerable to XSS
```

#### **File Upload Security**
```php
// ✅ Good: Validate file types and sizes
$request->validate([
    'file' => 'required|file|mimes:jpeg,png,pdf|max:2048'
]);

// ❌ Bad: No validation
move_uploaded_file($_FILES['file']['tmp_name'], $destination);
```

#### **Authentication & Authorization**
```php
// ✅ Good: Proper authorization checks
if (!auth()->user()->can('edit', $product)) {
    abort(403);
}

// ❌ Bad: No authorization
// Direct access without permission checks
```

#### **Avoid Dangerous Functions**
```php
// ❌ These will trigger security alerts:
eval($code);              // Dynamic code execution
exec($command);           // System command execution
system($command);         // System command execution
shell_exec($command);     // Shell execution
file_get_contents($url);  // Remote file inclusion (if URL)
```

#### **Secure Configuration**
```json
// ✅ Good app.json permissions
{
  "permissions": [
    "read_products",
    "create_products",
    "edit_own_products"
  ]
}

// ❌ Bad: Excessive permissions
{
  "permissions": [
    "admin_access",
    "file_system_write",
    "database_admin",
    "system_commands"
  ]
}
```

---

## 🔮 Advanced Features

### Real-time Updates with Broadcasting

```php
// Broadcast product updates to team members
class ProductUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Product $product)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tenant.{$this->product->team->tenant_id}.team.{$this->product->team_id}.products")
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'product' => $this->product->toArray(),
            'timestamp' => now()->toISOString()
        ];
    }
}

// Listen for updates in Vue component
Echo.private(`tenant.${tenantId}.team.${teamId}.products`)
    .listen('ProductUpdated', (event) => {
        // Update product in local state
        const index = products.value.findIndex(p => p.id === event.product.id)
        if (index !== -1) {
            products.value[index] = event.product
        }
    })
```

### AI Integration

```php
// AI-powered features
class AiProductDescriptionService
{
    public function generateDescription(Product $product): string
    {
        $prompt = "Generate a compelling product description for: {$product->name}";
        
        return $this->aiService->generateText($prompt, [
            'max_tokens' => 150,
            'temperature' => 0.7
        ]);
    }

    public function suggestTags(Product $product): array
    {
        $prompt = "Suggest relevant tags for this product: {$product->name} - {$product->description}";
        
        return $this->aiService->generateTags($prompt);
    }
}
```

### Webhook Integration

```php
// Handle external webhooks
Route::post('/webhooks/{provider}', function (Request $request, string $provider) {
    $handler = app("App\\Webhooks\\{$provider}Handler");
    
    return $handler->handle($request);
})->middleware(['webhook.signature']);

class ShopifyWebhookHandler
{
    public function handle(Request $request): Response
    {
        $event = $request->header('X-Shopify-Topic');
        $payload = $request->json();

        match ($event) {
            'orders/create' => $this->handleOrderCreated($payload),
            'products/update' => $this->handleProductUpdated($payload),
            default => Log::info("Unhandled webhook: {$event}")
        };

        return response('OK');
    }
}
```

---

## 🛠️ Troubleshooting

### Common Issues

#### App Not Loading
**Possible Solutions:**
- Verify your app.json configuration is valid
- Check that all required files are included in your ZIP package
- Ensure your app type matches the package structure
- Contact platform support if app remains inactive after upload

#### Tenant Isolation Issues
**Best Practices:**
- Always include team_id in your database models
- Use proper foreign key relationships
- Test your app with multiple tenants
- Verify data isolation in your app's functionality

#### Frontend Integration Issues
**Debugging Tips:**
- Verify API endpoints are correctly configured in your app.json
- Check browser console for JavaScript errors
- Ensure CSS and JavaScript assets are properly included
- Test responsive design on different screen sizes

### Performance Optimization

**Platform Monitoring:**
- Built-in performance monitoring and alerts
- Automatic scaling based on usage
- Database query optimization
- CDN and caching optimization

**App Optimization Tips:**
- Minimize database queries in your code
- Optimize images and assets before packaging
- Use efficient algorithms and data structures
- Test with realistic data volumes

### Getting Help

**Platform Support:**
- Built-in support ticket system
- Community forums and documentation
- Video tutorials and guides
- Developer support chat

---

## 📞 Support & Resources

### Documentation
- Platform Developer Docs (Built-in)
- API Reference Guide
- Video Tutorial Library
- Code Examples Repository
- Best Practices Guide

### Community
- Developer Community Forums
- Discord Development Channel
- Monthly Developer Meetups
- App Showcase Gallery

### Getting Help
- **Technical Issues**: Use the built-in support system
- **Security Vulnerabilities**: Report via secure channels
- **Business Inquiries**: Contact through tenant dashboard
- **Feature Requests**: Submit via the feedback system

---

## 📄 License

This documentation and the AppStore platform are proprietary software. Please review the license agreement before developing applications.

---

**Happy Coding! 🚀**

Build amazing applications for our multi-tenant platform and help businesses grow with powerful, scalable solutions.