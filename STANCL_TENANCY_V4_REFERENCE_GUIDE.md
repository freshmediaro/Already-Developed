# Stancl/Tenancy v4 Complete Reference Guide

This document serves as a comprehensive reference for implementing **stancl/tenancy v4** in our OS-style desktop application. This guide is based on the official documentation at `https://tenancy-v4.pages.dev/` and provides best practices, patterns, and implementation strategies for our specific stack.

## 📋 Table of Contents

- [Project Stack Overview](#project-stack-overview)
- [Development Commands](#development-commands)
- [Architecture Overview](#architecture-overview)
- [Introduction to Tenancy v4](#introduction-to-tenancy-v4)
- [Getting Started / Installation](#getting-started--installation)
- [Core Concepts](#core-concepts)
- [Tenants](#tenants)
- [Initializing Tenancy](#initializing-tenancy)
- [Tenant Identification](#tenant-identification)
- [Configuration](#configuration)
- [Tenancy Bootstrappers](#tenancy-bootstrappers)
- [Routing](#routing)
- [Universal Routes](#universal-routes)
- [Early Identification](#early-identification)
- [Session Scoping](#session-scoping)
- [Multi-Database Tenancy](#multi-database-tenancy)
- [Broadcasting](#broadcasting)
- [Pending Tenants](#pending-tenants)
- [Feature Classes](#feature-classes)
- [File Management & Storage](#file-management--storage)
- [Integrations](#integrations)
- [Testing](#testing)
- [Production Deployment](#production-deployment)
- [Best Practices](#best-practices)

---

## 🏗️ Project Stack Overview

Our application architecture combines:

- **Backend**: Laravel + [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/) is used for the tenant app, meaning the tenant app is [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/)-based, while the central app is completely custom.)
- **Frontend**: Inertia.js + Vue 3 + TypeScript + Tailwind CSS
- **Tenancy**: stancl/tenancy v4 with domain identification
- **Database**: PostgreSQL with Multi-Database architecture (database per tenant)
- **File Management**: ElFinder + Flysystem + vidwanco/tenant-buckets (tenancy-v4 branch)
- **Storage**: Cloudflare R2 with tenant isolation
- **Build Tool**: Vite

### Key Features
- OS-style desktop interface with windowing system
- Multi-tenant SaaS architecture with complete data isolation
- Team-based user management within tenants
- Real-time notifications via Laravel Reverb
- Comprehensive file management with ElFinder integration
- Tenant-specific storage buckets
- Comprehensive app ecosystem (File Explorer, Settings, E-commerce, etc.)
[Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/) is used for the tenant app, meaning the tenant app is [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/)-based, while the central app is completely custom.

---

## 🛠️ Development Commands

### Testing
- `composer test` - Run tests without coverage using Docker
- `./test tests/TestFile.php` - Run an entire test file
- `./t 'test name'` - Run a specific test
- You can append `-v` to get a full stack trace if a test fails due to an exception

### Code Quality
- `composer phpstan` - Run PHPStan static analysis (level 8)
- `composer cs` - Fix code style using PHP CS Fixer

### Docker Development
- `composer docker-up` - Start Docker environment
- `composer docker-down` - Stop Docker environment
- `composer docker-restart` - Restart Docker environment

### Tenancy-Specific Commands
- `php artisan tenancy:install` - Install tenancy scaffolding
- `php artisan tenants:migrate` - Run migrations for all tenants
- `php artisan tenants:migrate --tenants=tenant_id` - Run migrations for specific tenant
- `php artisan tenants:migrate:fresh --seed` - Fresh migrations with seeding
- `php artisan tenants:seed` - Seed tenant databases
- `php artisan tenants:list` - List all tenants
- `php artisan tenants:run "command"` - Run artisan command for all tenants

---

## 🏛️ Architecture Overview

**Tenancy for Laravel** is a multi-tenancy package that automatically handles tenant isolation without requiring changes to application code.

### Core Components

**Central Classes:**
- `Tenancy` - Main orchestrator class managing tenant context and lifecycle
- `TenancyServiceProvider` - Registers services, commands, and bootstrappers
- `Tenant` (model) - Represents individual tenants with domains and databases
- `Domain` (model) - Maps domains/subdomains to tenants

**Tenant Identification:**
- **Resolvers** (`src/Resolvers/`) - Identify tenants by domain, path, or request data
- **Middleware** (`src/Middleware/`) - Middleware that calls resolvers and initializes tenancy
- **Cached resolvers** - Cached wrapper around resolvers to avoid querying the central database

**Tenancy Bootstrappers (`src/Bootstrappers/`):**
- `DatabaseTenancyBootstrapper` - Switches database connections
- `CacheTenancyBootstrapper` - Isolates cache by tenant
- `FilesystemTenancyBootstrapper` - Manages tenant-specific storage
- `QueueTenancyBootstrapper` - Ensures queued jobs run in correct tenant context
- `RedisTenancyBootstrapper` - Prefixes Redis keys by tenant

**Database Management:**
- **DatabaseManager** - Creates/deletes tenant databases and users
- **TenantDatabaseManagers** - Database-specific implementations (MySQL, PostgreSQL, SQLite, SQL Server)
- **Row Level Security (RLS)** - PostgreSQL-based tenant isolation using policies

**Advanced Features:**
- **Resource Syncing** - Sync central models to tenant databases
- **User Impersonation** - Admin access to tenant contexts
- **Cross-domain redirects** - Handle multi-domain tenant setups
- **Telescope integration** - Tag entries by tenant

### Key Patterns

**Tenant Context Management:**
```php
tenancy()->initialize($tenant);           // Switch to tenant
tenancy()->run($tenant, $callback);      // Atomic tenant execution
tenancy()->runForMultiple($tenants, $callback); // Batch operations
tenancy()->central($callback);           // Run in central context
```

**Tenant Identification Flow:**
1. Middleware identifies tenant from request (domain/subdomain/path)
2. Resolver fetches tenant model from identification data
3. Tenancy initializes and bootstrappers configure tenant context
4. Application runs with tenant-specific database/cache/storage

**Route Middleware Groups:**
All of these work as flags (empty arrays with semantic use):
- `tenant` - Routes requiring tenant context
- `central` - Routes for central/admin functionality
- `universal` - Routes working in both contexts
- `clone` - Tells route cloning logic to clone the route

---

## 🧠 Introduction to Tenancy v4

Based on the official [tenancy-v4.pages.dev documentation](https://tenancy-v4.pages.dev/introduction/):

### Main Features:
* **Automatic mode** - automatically scoping Laravel components to the current tenant
* **Multiple identification methods** out of the box (domains, subdomains, path, request data)
* **Event-based architecture** - lets you hook into tenant creation, tenancy initialization, and dozens of other events
* **Multi-database support** - each tenant gets their own database for complete isolation
* **Tenant-aware bootstrappers** - automatic scoping of cache, filesystem, queues, sessions

### Philosophy
The main idea behind this package is **automatic multi-tenancy**:
1. **Identifying a tenant** (generally done using request middleware)
2. **Making Laravel components scope everything** to that tenant's context
3. **The actual application requiring very few changes** to be made multi-tenant

---

## ⚙️ Getting Started / Installation

Based on the [installation guide](https://tenancy-v4.pages.dev/start-here/getting-started-installation/):

### 1. Install the Package

```bash
composer require stancl/tenancy
```

### 2. Initialize Tenancy

```bash
php artisan tenancy:install
```

This creates:
- `config/tenancy.php` - Main configuration
- `routes/tenant.php` - Tenant-specific routes
- `app/Providers/TenancyServiceProvider.php` - Service provider
- Database migrations for tenants and domains

### 3. Register Service Provider

Add to `config/app.php` or `bootstrap/providers.php`:

```php
App\Providers\TenancyServiceProvider::class,
```

### 4. Configure Database Connections

In `config/database.php`, ensure your central connection is properly configured:

```php
'central' => [
    'driver' => 'pgsql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '5432'),
    'database' => env('DB_DATABASE', 'central_database'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    // ... other config
],
```

### 5. Environment Configuration

```env
# Central Database
DB_CONNECTION=central
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=central_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Tenancy Configuration
TENANCY_CENTRAL_CONNECTION=central
TENANCY_DATABASE_AUTO_DELETE=true
TENANCY_MIGRATE_FRESH=false
```

---

## 🎯 Core Concepts

### What is Multi-Tenancy?
Multi-tenancy is the ability to provide your service to multiple users (tenants) from a single hosted instance of the application.

### Types of Multi-Tenancy
1. **Single-Database**: All tenants share one database with `tenant_id` scoping
2. **Multi-Database**: Each tenant has their own dedicated database *(our approach)*

### How the Package Works
1. **Tenant Identification**: Request middleware identifies the current tenant
2. **Context Switching**: Laravel components are automatically scoped to the tenant
3. **Isolation**: Data is completely isolated between tenants without code changes

---

## 🏢 Tenants

Based on the [tenants documentation](https://tenancy-v4.pages.dev/tenants/):

### Tenant Model Implementation

Create `app/Models/Tenant.php`:

```php
<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * Custom columns that won't be stored in the data JSON column
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'plan',
            'status',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Get tenant's desktop configuration
     */
    public function getDesktopConfig(): array
    {
        return [
            'theme' => $this->data['theme'] ?? 'auto',
            'wallpaper' => $this->data['wallpaper'] ?? 'default',
            'apps' => $this->data['installed_apps'] ?? [],
            'storage_settings' => $this->data['storage_settings'] ?? [],
        ];
    }

    /**
     * Check if tenant has specific feature
     */
    public function hasFeature(string $feature): bool
    {
        $planFeatures = config("plans.{$this->plan}.features", []);
        return in_array($feature, $planFeatures);
    }

    /**
     * Get tenant's storage configuration
     */
    public function getStorageConfig(): array
    {
        return [
            'bucket_name' => "tenant-{$this->id}",
            'max_storage_gb' => $this->data['max_storage_gb'] ?? 100,
            'allowed_extensions' => $this->data['allowed_extensions'] ?? config('elfinder.allowed_extensions'),
        ];
    }
}
```

### Creating Tenants

```php
$tenant = Tenant::create([
    'name' => 'Acme Corporation',
    'plan' => 'pro',
    'status' => 'active',
]);

// Create domain for the tenant
$tenant->domains()->create([
    'domain' => 'acme.yourdomain.com',
    'is_primary' => true,
]);
```

### Running Commands in Tenant Context

```php
$tenant->run(function () {
    // Code here runs in tenant context
    User::create(['name' => 'John', 'email' => 'john@acme.com']);
    
    // Initialize tenant storage structure
    app(TenantBucketService::class)->initializeTenantStorage();
});
```

---

## 🔄 Initializing Tenancy

Based on [tenancy initialization](https://tenancy-v4.pages.dev/initializing-tenancy/):

### Manual Initialization

```php
// Initialize tenancy for a specific tenant
tenancy()->initialize($tenant);

// Check if tenancy is initialized
if (tenancy()->initialized) {
    // Access current tenant
    $currentTenant = tenant();
}

// End tenancy
tenancy()->end();
```

### Using Tenant Context

```php
$tenant->run(function () {
    // Code here runs in tenant context
    // All database queries, file operations, etc. are scoped to this tenant
    
    // Create user in tenant context
    User::create(['name' => 'John']);
    
    // File operations are also tenant-scoped
    Storage::disk('tenant')->put('welcome.txt', 'Hello from tenant!');
});
```

### Advanced Context Management

```php
// Run callback for multiple tenants
tenancy()->runForMultiple($tenants, function ($tenant) {
    // Process each tenant
    echo "Processing tenant: " . $tenant->name;
});

// Run in central context while in tenant
tenancy()->central(function () {
    // This runs in central database context
    CentralModel::create(['data' => 'central data']);
});
```

---

## 🔍 Tenant Identification

Based on [tenant identification](https://tenancy-v4.pages.dev/tenant-identification/):

### Available Identification Methods

- **Domain identification** (`acme.com`)
- **Subdomain identification** (`acme.yoursaas.com`) 
- **Path identification** (`yoursaas.com/acme/dashboard`)
- **Request data identification** (headers or query parameters)

### Domain Identification Setup

```php
// routes/tenant.php
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/', [DesktopController::class, 'index'])->name('desktop');
    
    // Include all tenant-specific routes
    require __DIR__ . '/tenant-desktop.php';
    require __DIR__ . '/tenant-files.php';
    require __DIR__ . '/tenant-iframe-apps.php';
});
```

### Customizing Identification Failure

```php
// In TenancyServiceProvider
InitializeTenancyByDomain::$onFail = function ($exception, $request, $next) {
    return redirect('https://your-central-domain.com/tenant-not-found');
};
```

### Cached Tenant Resolution

```php
// Enable caching for better performance
'cache' => [
    'store' => env('TENANCY_CACHE_STORE', 'redis'),
    'key_prefix' => 'tenancy_',
    'ttl' => 3600, // 1 hour
],
```

---

## 📝 Configuration

Based on [configuration documentation](https://tenancy-v4.pages.dev/configuration/):

### Key Configuration Options

Configure in `config/tenancy.php`:

```php
return [
    'tenant_model' => \App\Models\Tenant::class,
    'domain_model' => \Stancl\Tenancy\Database\Models\Domain::class,
    
    'central_domains' => [
        'localhost',
        'yourdomain.com',
    ],
    
    'bootstrappers' => [
        \Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
        \Stancl\Tenancy\Bootstrappers\RedisTenancyBootstrapper::class,
    ],
    
    'database' => [
        'central_connection' => 'central',
        'template_tenant_connection' => [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],
        'managers' => [
            'pgsql' => \Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
        ],
    ],
    
    'cache' => [
        'tag_base' => 'tenant',
    ],
    
    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'tenant',
            'public',
        ],
        'root_override' => [
            'tenant' => '%storage_path%/app/tenant/',
            'public' => '%storage_path%/app/public/',
        ],
    ],
    
    'features' => [
        \Stancl\Tenancy\Features\UserImpersonation::class,
        \Stancl\Tenancy\Features\TelescopeTags::class,
        \Stancl\Tenancy\Features\TenantConfig::class,
        \Stancl\Tenancy\Features\CrossDomainRedirect::class,
        \Stancl\Tenancy\Features\UniversalRoutes::class,
        \Stancl\Tenancy\Features\ViteBundler::class,
    ],
];
```

---

## 🔄 Tenancy Bootstrappers

Based on [bootstrappers documentation](https://tenancy-v4.pages.dev/bootstrappers/):

### Database Bootstrapper

Based on [database bootstrapper docs](https://tenancy-v4.pages.dev/bootstrappers/database/):

Automatically switches the default database connection to tenant-specific database.

### Cache Bootstrapper  

Based on [cache bootstrapper docs](https://tenancy-v4.pages.dev/bootstrappers/cache/):

Adds tenant-specific tags to all cache operations for isolation.

```php
// Clear cache for specific tenant
php artisan cache:clear --tag=tenant_123
```

### Filesystem Bootstrapper

Based on [filesystem bootstrapper docs](https://tenancy-v4.pages.dev/bootstrappers/filesystem/):

**Critical for our file management system!**

```php
'filesystem' => [
    'suffix_base' => 'tenant',
    'disks' => [
        'tenant' => [
            'driver' => 'local',
            'root' => storage_path('app/tenants/%tenant%'),
            'url' => env('APP_URL').'/storage/tenants/%tenant%',
            'visibility' => 'private',
        ],
        'r2' => [
            'driver' => 's3',
            'key' => env('CLOUDFLARE_R2_ACCESS_KEY_ID'),
            'secret' => env('CLOUDFLARE_R2_SECRET_ACCESS_KEY'),
            'region' => 'auto',
            'bucket' => env('CLOUDFLARE_R2_BUCKET'),
            'endpoint' => env('CLOUDFLARE_R2_ENDPOINT'),
            'url' => env('CLOUDFLARE_R2_URL'),
            'use_path_style_endpoint' => true,
            'root' => 'tenant-%tenant%',
        ],
    ],
],
```

### Queue Bootstrapper

Based on [queue bootstrapper docs](https://tenancy-v4.pages.dev/bootstrappers/queue/):

Ensures queued jobs run in the correct tenant context.

---

## 🛣️ Routing

Based on [routing documentation](https://tenancy-v4.pages.dev/routing/):

### Route Structure

**Central Routes** (`routes/web.php`):
```php
Route::get('/', function () {
    return inertia('Central/Welcome');
});

Route::get('/register-tenant', [TenantRegistrationController::class, 'show']);
Route::post('/register-tenant', [TenantRegistrationController::class, 'store']);
```

**Tenant Routes** (`routes/tenant.php`):
```php
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/', [DesktopController::class, 'index'])->name('desktop');
    
    // Include tenant-specific route files
    require __DIR__ . '/tenant-desktop.php';
    require __DIR__ . '/tenant-files.php';
    require __DIR__ . '/tenant-iframe-apps.php';
});
```

---

## 🌐 Universal Routes

Based on [universal routes docs](https://tenancy-v4.pages.dev/universal-routes/):

Routes that work in both central and tenant contexts.

```php
// Can be accessed from both central and tenant domains
Route::get('/health-check', function () {
    return response()->json(['status' => 'ok']);
})->name('health-check');
```

---

## ⚡ Early Identification

Based on [early identification docs](https://tenancy-v4.pages.dev/early-identification/):

**Early identification** ensures tenancy is initialized before controller instantiation, which is critical for certain scenarios.

### When Early Identification is Needed

- **Controllers using constructor dependency injection**
- **Integration with packages that inject dependencies in constructors**
- **Route model binding that needs tenant context**

### The Problem

Laravel executes controller constructors and route model binding before route-level middleware runs, causing services to use central context instead of tenant context.

### Solutions

#### 1. Avoid Constructor Injection (Recommended)
```php
class TenantController extends Controller
{
    // Instead of constructor injection
    public function index(TenantService $service)
    {
        // Method injection works correctly with tenant context
        return $service->getData();
    }
}
```

#### 2. Laravel's Native Solution
Use controllers that implement `HasMiddleware` interface:

```php
class TenantController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            InitializeTenancyByDomain::class,
        ];
    }
    
    public function __construct(TenantService $service)
    {
        // This now receives tenant-aware service
        $this->service = $service;
    }
}
```

#### 3. Kernel Identification
Add middleware to HTTP Kernel's global stack:

```php
// In app/Http/Kernel.php
protected $middleware = [
    \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
    // other middleware...
];
```

**Note**: You also need to flag the route with the `'tenant'` middleware if default route mode isn't set to TENANT.

### Benefits of Early Identification

- Constructor dependency injection receives tenant-aware services
- Seamless integration with existing Laravel applications
- Route model binding works correctly in tenant context

---

## 🔒 Session Scoping

Based on [session scoping docs](https://tenancy-v4.pages.dev/session-scoping/):

Ensures session data is properly isolated between tenants.

```php
'session' => [
    'driver' => env('SESSION_DRIVER', 'file'),
    'domain' => null, // Allow tenant-specific sessions
    'secure' => env('SESSION_SECURE_COOKIE'),
    'http_only' => true,
    'same_site' => 'lax',
],
```

---

## 🗄️ Multi-Database Tenancy

Each tenant gets their own PostgreSQL database for complete isolation.

### Database Structure
```
central_database          # Central app data
├── tenants              # Tenant registry
├── domains              # Domain mappings  
├── users                # Central users (if any)
└── ...

tenant_abc123_database   # Tenant-specific database
├── users                # Tenant users
├── teams                # Tenant teams  
├── products             # Tenant data
├── orders               # Tenant data
└── ...
```

### Running Migrations

```bash
# Run migrations for all tenants
php artisan tenants:migrate

# Run migrations for specific tenant
php artisan tenants:migrate --tenants=tenant_id

# Fresh migrations for all tenants
php artisan tenants:migrate:fresh --seed
```

### Queued Jobs

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Stancl\Tenancy\Contracts\Tenant;

class ProcessTenantData implements ShouldQueue
{
    use Queueable;

    protected Tenant $tenant;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function handle()
    {
        $this->tenant->run(function () {
            // Process data within tenant context
        });
    }
}
```

---

## 📡 Broadcasting

Based on [broadcasting docs](https://tenancy-v4.pages.dev/broadcasting/):

**Critical for our notification system!**

### Configuration

Add to `config/broadcasting.php`:

```php
'tenant' => [
    'enabled' => env('BROADCAST_TENANT_ISOLATION', true),
    'channel_prefix' => env('BROADCAST_TENANT_PREFIX', 'tenant'),
    'user_prefix' => env('BROADCAST_USER_PREFIX', 'user'),
    'team_prefix' => env('BROADCAST_TEAM_PREFIX', 'team'),
],
```

### Channel Authorization

Create `routes/channels.php`:

```php
// Tenant-aware broadcasting channels
Broadcast::channel('tenant.{tenantId}.user.{userId}', function (User $user, string $tenantId, int $userId) {
    return (int) $user->id === (int) $userId && tenant('id') === $tenantId;
});

Broadcast::channel('tenant.{tenantId}.team.{teamId}', function (User $user, string $tenantId, int $teamId) {
    return $user->belongsToTeam($teamId) && tenant('id') === $tenantId;
});
```

### Broadcasting Events

```php
class DesktopNotification implements ShouldBroadcast
{
    public function broadcastOn()
    {
        $channel = tenant() 
            ? "tenant.".tenant('id').".user.{$this->userId}"
            : "user.{$this->userId}";

        return new PrivateChannel($channel);
    }
}
```

---

## ⏳ Pending Tenants

Based on [pending tenants docs](https://tenancy-v4.pages.dev/pending-tenants/):

Handle tenants that aren't fully provisioned yet.

```php
class Tenant extends BaseTenant
{
    protected $casts = [
        'data' => 'array',
        'status' => 'string',
    ];
    
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
    
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
```

---

## ⚡ Feature Classes

### Tenant Config

Based on [tenant config docs](https://tenancy-v4.pages.dev/features/tenant-config/):

```php
// config/tenancy.php
'features' => [
    \Stancl\Tenancy\Features\TenantConfig::class,
],

// In tenant context, override config values
config(['app.name' => tenant('name')]);
```

### Cross-domain Redirect

Based on [cross-domain redirect docs](https://tenancy-v4.pages.dev/features/cross-domain-redirect/):

Redirect users between central and tenant domains.

### User Impersonation

Based on [user impersonation docs](https://tenancy-v4.pages.dev/features/user-impersonation/):

Impersonate users across tenant boundaries.

### Telescope Tags

Based on [telescope tags docs](https://tenancy-v4.pages.dev/features/telescope-tags/):

Tag Telescope entries with tenant information.

### Vite Bundler

Based on [vite bundler docs](https://tenancy-v4.pages.dev/features/vite-bundler/):

Tenant-aware asset compilation with Vite.

---

## 📁 File Management & Storage

**This is critical for our ElFinder + Tenant Buckets integration!**

### Tenant Storage Structure

Based on our ElFinder integration and `vidwanco/tenant-buckets`:

```
cloudflare-r2-bucket/
├── tenant-{tenant_id}/
│   ├── team-{team_id}/
│   │   ├── documents/
│   │   ├── images/
│   │   ├── uploads/
│   │   ├── shared/
│   │   └── trash/
│   └── team-{other_team_id}/
└── tenant-{other_tenant_id}/
```

### Storage Configuration

```php
// config/filesystems.php
'disks' => [
    'tenant' => [
        'driver' => 'local',
        'root' => storage_path('app/tenant'),
        'url' => env('APP_URL').'/storage/tenant',
        'visibility' => 'private',
    ],
    
    'tenant-r2' => [
        'driver' => 's3',
        'key' => env('CLOUDFLARE_R2_ACCESS_KEY_ID'),
        'secret' => env('CLOUDFLARE_R2_SECRET_ACCESS_KEY'),
        'region' => 'auto',
        'bucket' => env('CLOUDFLARE_R2_BUCKET'),
        'endpoint' => env('CLOUDFLARE_R2_ENDPOINT'),
        'url' => env('CLOUDFLARE_R2_URL'),
        'use_path_style_endpoint' => true,
        'root' => '', // Will be dynamically set by tenant-buckets
    ],
],
```

### ElFinder Integration

```php
// config/elfinder.php
'disks' => [
    'tenant-files' => [
        'driver' => 'TenantElfinderDisk',
        'path' => 'tenant-files',
        'URL' => '/tenant-files',
        'alias' => 'Files',
        'access' => ['read', 'write'],
        'roots' => [
            [
                'driver' => 'ElFinderVolumeDriver',
                'path' => storage_path('app/tenant-files'),
                'URL' => asset('storage/tenant-files'),
                'alias' => 'Tenant Files',
                'accessControl' => 'ElFinderTenantAccess',
            ],
        ],
    ],
],
```

### Tenant Bucket Service

```php
<?php

namespace App\Services;

use Vidwanco\TenantBuckets\TenantBucketService as BaseTenantBucketService;

class TenantBucketService extends BaseTenantBucketService
{
    public function initializeTenantStorage(): void
    {
        $tenant = tenant();
        $bucketName = "tenant-{$tenant->id}";
        
        // Initialize tenant storage structure
        $this->createTenantBucket($bucketName);
        
        // Create team-specific directories for each team
        foreach ($tenant->teams as $team) {
            $this->createTeamDirectories($bucketName, $team->id);
        }
    }
    
    protected function createTeamDirectories(string $bucketName, int $teamId): void
    {
        $directories = ['documents', 'images', 'uploads', 'shared', 'trash'];
        
        foreach ($directories as $directory) {
            $path = "team-{$teamId}/{$directory}";
            Storage::disk('tenant-r2')->makeDirectory($path);
        }
    }
}
```

### File Permissions

```php
<?php

namespace App\Services;

class FilePermissionService
{
    public function canAccess(User $user, string $filePath): bool
    {
        // Check tenant isolation
        if (!$this->isInUserTenant($filePath)) {
            return false;
        }
        
        // Check team access
        if (!$this->isInUserTeam($user, $filePath)) {
            return false;
        }
        
        // Check file-specific permissions
        return $this->hasFilePermission($user, $filePath);
    }
    
    protected function isInUserTenant(string $filePath): bool
    {
        $tenantId = tenant('id');
        return str_starts_with($filePath, "tenant-{$tenantId}/");
    }
    
    protected function isInUserTeam(User $user, string $filePath): bool
    {
        $teamId = $user->currentTeam->id;
        return str_contains($filePath, "team-{$teamId}/");
    }
}
```

---

## 🔗 Integrations

### Horizon Integration

Based on [Horizon integration docs](https://tenancy-v4.pages.dev/integrations/horizon/):

Configure Horizon for tenant-aware queue processing.

### Spatie Integrations

Based on [Spatie integrations docs](https://tenancy-v4.pages.dev/integrations/spatie/):

Integration with popular Spatie packages like laravel-permission, laravel-activitylog.

### Sanctum Integration

Based on [Sanctum integration docs](https://tenancy-v4.pages.dev/integrations/sanctum/):

API authentication for tenant applications.

```php
// Tenant-aware API authentication
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::get('/api/user', function (Request $request) {
        return $request->user();
    });
});
```

---

## 🧪 Testing

### Testing Environment

Tests use Docker with MySQL/PostgreSQL/Redis. The `./test` script runs Pest tests inside containers with proper database isolation.

`./t 'test name'` is equivalent to `./test --filter 'test name'`

### Key Test Patterns

- **Database preparation and cleanup** between tests
- **Multi-database scenarios** (central + tenant databases)
- **Middleware and identification testing**
- **Resource syncing validation**

### Tenant Testing

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\User;

class TenantDesktopTest extends TestCase
{
    /** @test */
    public function user_can_access_desktop_in_tenant_context()
    {
        $tenant = Tenant::create(['name' => 'Test Tenant', 'plan' => 'pro']);
        $tenant->domains()->create(['domain' => 'test.app.test', 'is_primary' => true]);

        tenancy()->initialize($tenant);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/desktop')
            ->assertOk();
    }

    /** @test */
    public function tenant_file_isolation_works()
    {
        $tenant1 = Tenant::create(['name' => 'Tenant 1']);
        $tenant2 = Tenant::create(['name' => 'Tenant 2']);

        // Create file in tenant 1
        tenancy()->initialize($tenant1);
        Storage::disk('tenant')->put('test.txt', 'Tenant 1 content');

        // Switch to tenant 2
        tenancy()->initialize($tenant2);
        $this->assertFalse(Storage::disk('tenant')->exists('test.txt'));
    }

    /** @test */
    public function tenant_context_isolation()
    {
        $tenant1 = Tenant::create(['name' => 'Tenant 1']);
        $tenant2 = Tenant::create(['name' => 'Tenant 2']);

        tenancy()->initialize($tenant1);
        $user1 = User::factory()->create(['email' => 'user1@tenant1.com']);

        tenancy()->initialize($tenant2);
        $user2 = User::factory()->create(['email' => 'user2@tenant2.com']);

        // Verify isolation
        tenancy()->initialize($tenant1);
        $this->assertCount(1, User::all());
        $this->assertEquals('user1@tenant1.com', User::first()->email);

        tenancy()->initialize($tenant2);
        $this->assertCount(1, User::all());
        $this->assertEquals('user2@tenant2.com', User::first()->email);
    }

    /** @test */
    public function tenant_identification_middleware_works()
    {
        $tenant = Tenant::create(['name' => 'Test Tenant']);
        $tenant->domains()->create(['domain' => 'test.app.test', 'is_primary' => true]);

        // Test middleware identifies tenant correctly
        $this->get('http://test.app.test/api/tenant-info')
            ->assertOk()
            ->assertJson(['tenant_id' => $tenant->id]);
    }
}
```

---

## 🚀 Production Deployment

### Environment Configuration

**.env for Production**:
```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=central
DB_HOST=your-postgres-host
DB_PORT=5432
DB_DATABASE=central_production
DB_USERNAME=your-username
DB_PASSWORD=your-secure-password

# Tenancy
TENANCY_CENTRAL_CONNECTION=central
TENANCY_DATABASE_AUTO_DELETE=false
TENANCY_MIGRATE_FRESH=false

# Cache & Sessions
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Broadcasting
BROADCAST_DRIVER=reverb
BROADCAST_TENANT_ISOLATION=true
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https

# File Storage
CLOUDFLARE_R2_ACCESS_KEY_ID=your_r2_key
CLOUDFLARE_R2_SECRET_ACCESS_KEY=your_r2_secret
CLOUDFLARE_R2_BUCKET=your_r2_bucket
CLOUDFLARE_R2_ENDPOINT=https://your_account_id.r2.cloudflarestorage.com
CLOUDFLARE_R2_URL=https://your_custom_domain.com

# ElFinder
ELFINDER_MAX_UPLOAD_SIZE=100M
ELFINDER_ALLOWED_EXTENSIONS="jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,json,xml,zip,rar,7z"
```

### Deployment Commands

```bash
# Run central migrations
php artisan migrate --force

# Run tenant migrations
php artisan tenants:migrate --force

# Clear and cache configs
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Install tenancy
php artisan tenancy:install

# Publish ElFinder assets
php artisan vendor:publish --provider="Barryvdh\Elfinder\ElfinderServiceProvider" --tag=public
```

---

## 📚 Best Practices

### 1. **Data Isolation**
- Always use multi-database tenancy for complete isolation
- Implement proper tenant-aware middleware
- Use tenant-specific storage buckets
- Validate tenant ownership of all resources

### 2. **File Management**
- Use vidwanco/tenant-buckets for tenant-specific file storage
- Implement proper file permissions and access controls
- Use ElFinder with tenant-aware configurations
- Store files in structured tenant/team directories

### 3. **Performance**
- Use connection pooling for database efficiency
- Implement tenant-specific caching strategies
- Optimize file operations with proper indexing
- Use CDN for static assets

### 4. **Security**
- Enforce tenant boundaries in all operations
- Implement proper file access controls
- Use secure file upload validation
- Monitor for cross-tenant access attempts

### 5. **Broadcasting**
- Use tenant-prefixed channels for notifications
- Implement proper channel authorization
- Ensure real-time features respect tenant boundaries

### 6. **Development**
- Comprehensive testing with tenant contexts
- Test file isolation between tenants
- Use proper error handling and logging
- Leverage the event system for customization

### 7. **Architecture Guidelines**
- Prefer method injection over constructor injection
- Use early identification when necessary
- Implement cached tenant resolution for performance
- Follow route middleware group conventions

---

## 🎯 Implementation Checklist

- [ ] Install and configure stancl/tenancy v4
- [ ] Set up PostgreSQL multi-database architecture
- [ ] Configure domain-based tenant identification
- [ ] Implement Tenant and Domain models
- [ ] Set up tenant-aware middleware
- [ ] Configure tenant routes and controllers
- [ ] Implement tenant database migrations
- [ ] Set up tenant file storage with vidwanco/tenant-buckets
- [ ] Configure ElFinder integration
- [ ] Set up Cloudflare R2 storage
- [ ] Configure broadcasting for real-time features
- [ ] Implement tenant-aware queue system
- [ ] Set up comprehensive testing
- [ ] Configure production deployment
- [ ] Implement monitoring and health checks

---

## 📚 Documentation Sources & Accuracy Statement

This reference guide has been compiled from the following **verified sources**:

### Primary Sources:
- **Official v4 Documentation**: `https://tenancy-v4.pages.dev/` (Current v4 documentation)
- **GitHub Repository**: `https://github.com/archtechx/tenancy` (v4.x branch)
- **ElFinder Integration Setup**: `ELFINDER_INTEGRATION_SETUP.md`
- **Tenant Buckets Package**: `https://github.com/vidwanco/tenant-buckets/tree/tenancy-v4`
- **CLAUDE.md**: Internal development and architecture documentation

### Coverage Verification:
✅ **Installation & Setup** - Based on v4 getting started guide  
✅ **Core Concepts** - From v4 basics documentation  
✅ **Tenants** - From v4 tenants documentation  
✅ **Initialization** - From v4 initializing tenancy docs  
✅ **Identification** - From v4 tenant identification docs  
✅ **Configuration** - From v4 configuration documentation  
✅ **Bootstrappers** - From v4 bootstrappers section  
✅ **Routing** - From v4 routing documentation  
✅ **Broadcasting** - From v4 broadcasting documentation  
✅ **File Management** - Based on ElFinder setup and tenant-buckets integration  
✅ **Integrations** - From v4 integrations documentation  
✅ **Testing** - From v4 testing guidelines
✅ **Architecture** - From CLAUDE.md development documentation

**For Latest Updates**: Always refer to the official documentation at `https://tenancy-v4.pages.dev/` for the most current information and detailed API references.

---

## 📖 Additional Resources

### Important Links
- **Official Documentation**: https://tenancy-v4.pages.dev/
- **GitHub Repository**: https://github.com/archtechx/tenancy
- **Tenant Buckets**: https://github.com/vidwanco/tenant-buckets/tree/tenancy-v4
- **ElFinder**: https://github.com/barryvdh/laravel-elfinder
- **Flysystem**: https://github.com/thephpleague/flysystem

### File Management Stack
- **ElFinder**: Provides the file manager interface with custom styling
- **Flysystem**: Handles the underlying file operations
- **Tenant Buckets**: Provides tenant-specific storage isolation
- **Cloudflare R2**: Object storage backend with CDN capabilities

This reference guide serves as the definitive resource for implementing Tenancy v4 in our OS-style desktop application with comprehensive file management capabilities. 