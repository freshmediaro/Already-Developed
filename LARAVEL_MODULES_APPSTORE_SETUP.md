# Laravel Modules AppStore Integration Setup

This document provides complete instructions for setting up the modular AppStore system with Laravel Modules integration. This system allows tenants to browse, purchase, and install various types of applications including Laravel modules, WordPress plugins, iframe apps, and API integrations.

## 📋 Table of Contents

- [Requirements](#requirements)
- [Laravel Modules Installation](#laravel-modules-installation)
- [Database Setup](#database-setup)
- [Configuration](#configuration)
- [AppStore Models](#appstore-models)
- [Controllers & Services](#controllers--services)
- [Frontend Integration](#frontend-integration)
- [Publishing Apps](#publishing-apps)
- [Development Workflow](#development-workflow)
- [Deployment](#deployment)

---

## 🔧 Requirements

### System Requirements
- PHP 8.1+
- Laravel 10.x/11.x
- PostgreSQL 13+
- Redis (for caching and queues)
- Composer 2.x

### Required Packages
Based on the [Laravel Modules documentation](https://github.com/nWidart/laravel-modules):

```bash
# Core Laravel Modules package
composer require nwidart/laravel-modules

# Required for file operations
composer require symfony/filesystem

# Optional but recommended
composer require barryvdh/laravel-ide-helper --dev
```

### Existing Stack Dependencies
- `stancl/tenancy` v4 (already installed)
- `barryvdh/laravel-elfinder` (for file management)
- `vidwanco/tenant-buckets` (for tenant storage)
- Laravel [Vue Starter Kit](https://github.com/laravel/vue-starter-kit/)
- Inertia.js + Vue 3

---

## 📦 Laravel Modules Installation

### 1. Install the Package

```bash
composer require nwidart/laravel-modules
```

### 2. Publish Configuration

```bash
# Publish the module configuration
php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"

# Optional: Publish module stubs for customization
php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider" --tag="stubs"
```

### 3. Configure Auto-Discovery

Add to `composer.json`:

```json
{
    "extra": {
        "laravel": {
            "dont-discover": []
        },
        "merge-plugin": {
            "include": [
                "Modules/*/composer.json"
            ]
        }
    }
}
```

### 4. Update Composer Autoload

```bash
composer dump-autoload
```

---

## 🗄️ Database Setup

### 1. Run AppStore Migrations

```bash
# Create the new AppStore tables
php artisan migrate

# The following migrations will be applied:
# - 2024_01_20_000000_create_app_store_apps_table.php
# - 2024_01_21_000000_create_app_store_categories_table.php  
# - 2024_01_22_000000_create_app_store_app_categories_table.php
# - 2024_01_23_000000_create_app_store_dependencies_table.php
# - 2024_01_24_000000_create_app_store_reviews_table.php
# - 2024_01_25_000000_create_app_store_purchases_table.php
# - 2024_01_26_000000_update_installed_apps_table.php
```

### 2. Seed Default Categories

Create and run a seeder for default app categories:

```bash
php artisan make:seeder AppStoreCategorySeeder
```

```php
<?php

namespace Database\Seeders;

use App\Models\AppStoreCategory;
use Illuminate\Database\Seeder;

class AppStoreCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Productivity',
                'slug' => 'productivity',
                'description' => 'Apps to boost your productivity',
                'icon' => 'fa-rocket',
                'color' => '#3B82F6',
                'sort_order' => 1,
            ],
            [
                'name' => 'E-commerce',
                'slug' => 'ecommerce',
                'description' => 'Online store and commerce tools',
                'icon' => 'fa-shopping-cart',
                'color' => '#10B981',
                'sort_order' => 2,
            ],
            [
                'name' => 'Communication',
                'slug' => 'communication',
                'description' => 'Communication and collaboration tools',
                'icon' => 'fa-comments',
                'color' => '#8B5CF6',
                'sort_order' => 3,
            ],
            [
                'name' => 'Analytics',
                'slug' => 'analytics',
                'description' => 'Data analysis and reporting tools',
                'icon' => 'fa-chart-bar',
                'color' => '#F59E0B',
                'sort_order' => 4,
            ],
            [
                'name' => 'Integration',
                'slug' => 'integration',
                'description' => 'API integrations and connectors',
                'icon' => 'fa-plug',
                'color' => '#EF4444',
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $category) {
            AppStoreCategory::create($category);
        }
    }
}
```

Run the seeder:

```bash
php artisan db:seed --class=AppStoreCategorySeeder
```

---

## ⚙️ Configuration

### 1. Module Configuration

Update `config/modules.php`:

```php
<?php

return [
    'namespace' => 'Modules',
    'stubs' => [
        'enabled' => true,
        'path' => base_path('vendor/nwidart/laravel-modules/src/Commands/stubs'),
        'files' => [
            'routes/web' => 'Routes/web.php',
            'routes/api' => 'Routes/api.php',
            'views/index' => 'Resources/views/index.blade.php',
            'views/master' => 'Resources/views/layouts/master.blade.php',
            'scaffold/config' => 'Config/config.php',
            'composer' => 'composer.json',
            'assets/js/app' => 'Resources/assets/js/app.js',
            'assets/sass/app' => 'Resources/assets/sass/app.scss',
            'webpack' => 'webpack.mix.js',
            'package' => 'package.json',
        ],
        'replacements' => [
            'routes/web' => ['LOWER_NAME', 'STUDLY_NAME'],
            'routes/api' => ['LOWER_NAME'],
            'webpack' => ['LOWER_NAME'],
            'json' => ['LOWER_NAME', 'STUDLY_NAME', 'MODULE_NAMESPACE', 'PROVIDER_NAMESPACE'],
            'views/index' => ['LOWER_NAME'],
            'views/master' => ['LOWER_NAME', 'STUDLY_NAME'],
            'scaffold/config' => ['STUDLY_NAME'],
            'composer' => [
                'LOWER_NAME',
                'STUDLY_NAME',
                'VENDOR',
                'AUTHOR_NAME',
                'AUTHOR_EMAIL',
                'MODULE_NAMESPACE',
                'PROVIDER_NAMESPACE',
            ],
        ],
        'gitkeep' => true,
    ],
    'paths' => [
        'modules' => base_path('Modules'),
        'assets' => public_path('modules'),
        'migration' => base_path('database/migrations'),
        'generator' => [
            'config' => ['path' => 'Config', 'generate' => true],
            'command' => ['path' => 'Console', 'generate' => true],
            'migration' => ['path' => 'Database/Migrations', 'generate' => true],
            'seeder' => ['path' => 'Database/Seeders', 'generate' => true],
            'factory' => ['path' => 'Database/factories', 'generate' => true],
            'model' => ['path' => 'Entities', 'generate' => true],
            'routes' => ['path' => 'Routes', 'generate' => true],
            'controller' => ['path' => 'Http/Controllers', 'generate' => true],
            'filter' => ['path' => 'Http/Middleware', 'generate' => true],
            'request' => ['path' => 'Http/Requests', 'generate' => true],
            'provider' => ['path' => 'Providers', 'generate' => true],
            'assets' => ['path' => 'Resources/assets', 'generate' => true],
            'lang' => ['path' => 'Resources/lang', 'generate' => true],
            'views' => ['path' => 'Resources/views', 'generate' => true],
            'test' => ['path' => 'Tests/Unit', 'generate' => true],
            'test-feature' => ['path' => 'Tests/Feature', 'generate' => true],
            'repository' => ['path' => 'Repositories', 'generate' => false],
            'event' => ['path' => 'Events', 'generate' => false],
            'listener' => ['path' => 'Listeners', 'generate' => false],
            'policies' => ['path' => 'Policies', 'generate' => false],
            'rules' => ['path' => 'Rules', 'generate' => false],
            'jobs' => ['path' => 'Jobs', 'generate' => false],
            'emails' => ['path' => 'Emails', 'generate' => false],
            'notifications' => ['path' => 'Notifications', 'generate' => false],
            'resource' => ['path' => 'Transformers', 'generate' => false],
            'component-view' => ['path' => 'Resources/views/components', 'generate' => false],
            'component-class' => ['path' => 'View/Components', 'generate' => false],
        ],
    ],
    'scan' => [
        'enabled' => true,
        'paths' => [
            base_path('vendor/*/*'),
        ],
    ],
    'composer' => [
        'vendor' => 'nwidart',
        'author' => [
            'name' => env('MODULE_AUTHOR_NAME', 'Nicolas Widart'),
            'email' => env('MODULE_AUTHOR_EMAIL', 'n.widart@gmail.com'),
        ],
    ],
    'composer-output' => false,
    'cache' => [
        'enabled' => false,
        'key' => 'laravel-modules',
        'lifetime' => 60,
    ],
    'register' => [
        'translations' => true,
        'files' => 'register',
    ],
    'activators' => [
        'file' => [
            'class' => Nwidart\Modules\Activators\FileActivator::class,
            'statuses-file' => base_path('modules_statuses.json'),
            'cache-key' => 'activator.installed',
            'cache-lifetime' => 604800,
        ],
    ],
    'activator' => 'file',
];
```

### 2. Environment Variables

Add to `.env`:

```env
# Laravel Modules
MODULE_AUTHOR_NAME="Your Name"
MODULE_AUTHOR_EMAIL="your@email.com"

# AppStore Settings
APPSTORE_APPROVAL_REQUIRED=true
APPSTORE_AUTO_INSTALL_FREE=true
APPSTORE_ENABLE_REVIEWS=true
APPSTORE_ENABLE_DEPENDENCIES=true
```

---

## 🏗️ AppStore Models

The following models have been created to support the AppStore functionality:

### Core Models
- **`AppStoreApp`** - Main app registry with pricing, dependencies, and metadata
- **`AppStoreCategory`** - App categories for organization
- **`AppStoreReview`** - User reviews and ratings
- **`AppStorePurchase`** - Purchase transactions and subscriptions
- **`InstalledApp`** - Enhanced to support AppStore integration

### Key Features
- **Multi-tenant isolation** - All operations are tenant-aware
- **Pricing support** - Free, one-time, monthly, and freemium models
- **Dependency management** - Apps can depend on other apps
- **Review system** - Verified purchase reviews
- **Version management** - Track app versions and updates
- **Security controls** - Approval workflow and permissions

---

## 🎮 Controllers & Services

### AppStoreController
Handles tenant-side operations:
- Browse and search apps
- Purchase and install apps
- Submit reviews
- Manage installed apps

### AppStoreService
Core business logic:
- Payment processing
- App installation
- Module management
- WordPress plugin support

### ModuleInstallationService
Laravel Modules integration:
- Create module structure
- Install/uninstall modules
- Tenant-specific module management
- AI token and API integration support

---

## 🎨 Frontend Integration

### Enhanced AppStore Vue Component

The existing `AppStore.vue` component will be enhanced to support:

1. **App Categories** - Browse by category
2. **Search & Filtering** - Find apps by name, price, rating
3. **App Details** - Screenshots, reviews, dependencies
4. **Purchase Flow** - Stripe integration for payments
5. **Installation Management** - Install/uninstall apps
6. **Review System** - Submit and view reviews

### API Integration

Frontend communicates with the backend via these endpoints:

```typescript
// App browsing
GET /api/app-store/ - Homepage with featured apps
GET /api/app-store/browse - Browse with filters
GET /api/app-store/{slug} - App details

// App management
POST /api/app-store/{slug}/purchase - Purchase app
POST /api/app-store/{slug}/install - Install app
DELETE /api/app-store/{slug}/uninstall - Uninstall app
POST /api/app-store/{slug}/review - Submit review

// User data
GET /api/app-store/installed - Installed apps
GET /api/app-store/purchases - Purchase history
```

---

## 📝 Publishing Apps

### For Central Admins

Create a central admin interface to:

1. **Add new apps** to the store
2. **Set pricing** (free, one-time, monthly)
3. **Manage categories** and featured apps
4. **Review and approve** submitted apps
5. **Handle refunds** and support

### For Tenant Developers

Allow tenants to:

1. **Submit custom apps** for approval
2. **Upload Laravel modules** 
3. **Configure API integrations**
4. **Set app permissions** and dependencies
5. **Manage app versions** and updates

### AI-Powered App Generation

Integrate with AI services to help users:

1. **Generate module templates** based on requirements
2. **Create API integrations** automatically
3. **Generate documentation** and help text
4. **Suggest improvements** and optimizations

---

## 🔄 Development Workflow

### Creating a New Module App

1. **Developer creates app listing**:
```bash
# Through AppStore interface or API
POST /api/admin/app-store/apps
{
    "name": "Customer Portal",
    "app_type": "laravel_module",
    "module_name": "CustomerPortal",
    "pricing_type": "free",
    "description": "Customer self-service portal"
}
```

2. **System generates module structure**:
```bash
# Automatically creates:
Modules/CustomerPortal/
├── Config/config.php
├── Http/Controllers/
├── Routes/web.php
├── Routes/api.php
├── Resources/views/
├── Providers/
└── module.json
```

3. **Developer customizes module**:
- Add business logic
- Create views and API endpoints
- Configure permissions
- Add tests

4. **Testing and approval**:
- Submit for review
- Central admin tests and approves
- App becomes available in store

### Module Installation Flow

1. **Tenant browses AppStore**
2. **Selects and purchases app** (if paid)
3. **System installs module**:
   - Downloads/creates module files
   - Runs migrations
   - Configures permissions
   - Registers with Laravel
4. **Module is available** in tenant's desktop

---

## 🚀 Deployment

### Production Setup

1. **Install Laravel Modules**:
```bash
composer require nwidart/laravel-modules --no-dev
```

2. **Configure permissions**:
```bash
# Ensure Modules directory is writable
chmod -R 755 Modules/
chown -R www-data:www-data Modules/
```

3. **Set up module auto-discovery**:
```bash
composer dump-autoload --optimize
```

4. **Configure caching**:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Environment Variables

```env
# Production settings
APPSTORE_APPROVAL_REQUIRED=true
APPSTORE_AUTO_INSTALL_FREE=false
APPSTORE_ENABLE_SANDBOX=false
MODULE_CACHE_ENABLED=true
```

### Security Considerations

1. **Module isolation** - Ensure modules can't access other tenant data
2. **Permission validation** - Verify module permissions before installation
3. **Code review** - All submitted modules should be reviewed
4. **Resource limits** - Set limits on module resource usage
5. **Update management** - Secure update mechanism for modules

---

## 📚 Additional Resources

### Laravel Modules Documentation
- **Official Docs**: https://laravelmodules.com/docs/12/getting-started/introduction
- **GitHub Repository**: https://github.com/nWidart/laravel-modules
- **Version Compatibility**: https://laravelmodules.com/docs/12/getting-started/requirements

### Integration Examples
- **ElFinder Integration**: See `ELFINDER_INTEGRATION_SETUP.md`
- **Tenancy Reference**: See `STANCL_TENANCY_V4_REFERENCE_GUIDE.md`
- **Payment Integration**: See `WALLET_PAYMENT_SETUP.md`

### Support and Community
- **Discord Community**: https://discord.gg/hkF7BRvRZK
- **GitHub Issues**: Report bugs and feature requests
- **Documentation**: Comprehensive guides and examples

---

## ✅ Setup Checklist

- [ ] Install Laravel Modules package
- [ ] Configure auto-discovery in composer.json
- [ ] Run AppStore migrations
- [ ] Seed default categories
- [ ] Configure module paths and stubs
- [ ] Set up environment variables
- [ ] Test module creation and installation
- [ ] Configure production security
- [ ] Set up admin interface for app management
- [ ] Test tenant app installation flow
- [ ] Configure payment processing
- [ ] Set up review and approval workflow

Once all items are completed, your modular AppStore system will be ready for tenants to browse, purchase, and install applications including Laravel modules, WordPress plugins, and custom integrations. 