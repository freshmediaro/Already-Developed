# OS-Style Desktop Application - Laravel Integration Implementation Plan

## Current Status Overview

### ✅ **COMPLETED - Frontend Implementation (100%)**
- **Vanilla JS to Vue 3 + TypeScript**: Complete modular architecture migration from 30,368-line monolithic app.js
- **Mobile-Responsive Design**: Full touch gesture support, orientation handling, responsive breakpoints
- **Complete Application Suite**: All 10+ applications fully implemented with state management
- **File Management Integration**: ElFinder with custom UI, Cloudflare R2, multi-tenant storage
- **Build System**: Vite + TypeScript + Tailwind CSS configuration optimized
- **Component Architecture**: Modular, reusable Vue components with proper separation of concerns

### ✅ **COMPLETED - File Management Backend (100%)**
- **Multi-Tenant File System**: Complete Laravel backend with tenant/team isolation
- **ElFinder Integration**: Custom connector with R2 storage and tenant buckets
- **API Endpoints**: Full file management API with security and permissions
- **Storage Services**: TenantBucketService, FilePermissionService implemented

### 🔄 **PENDING - Laravel Backend Integration**
- **Tenancy Configuration**: stancl/tenancy v4 setup with [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/)
- **Database Schema**: Tenant/central database migrations
- **Desktop API Controllers**: App management and business logic endpoints
- **Authentication Integration**: Sanctum tokens with team switching

## Implementation Roadmap

### Phase 1: Laravel Foundation Setup (Week 1)

#### 1.1 Project Initialization

**Step 1: Create Laravel Project**
```bash
# Create new Laravel project
composer create-project laravel/laravel os-desktop-backend
cd os-desktop-backend

# Install required packages
composer require stancl/tenancy
# Note: This project should be based on [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/)
# If starting fresh, use: composer create-project laravel/vue-starter-kit your-project-name
composer require laravel/sanctum
composer require spatie/laravel-permission
```

**Step 2: Note: [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/) is pre-configured**
```bash
# Note: [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/) components are pre-configured
# No additional installation commands needed for the starter kit

# Install frontend dependencies
npm install @inertiajs/vue3 vue@next @vitejs/plugin-vue
npm install -D typescript @types/node
npm install pinia @headlessui/vue @heroicons/vue
npm install @vueuse/core
```

**Step 3: Install and Configure Tenancy**
```bash
# Install tenancy
php artisan vendor:publish --provider="Stancl\Tenancy\TenancyServiceProvider" --tag=migrations
php artisan vendor:publish --provider="Stancl\Tenancy\TenancyServiceProvider" --tag=config

# Run initial migrations (central database)
php artisan migrate
```

#### 1.2 Database Configuration

**Central Database Schema (Landlord)**
```php
// database/migrations/landlord/create_tenants_table.php
Schema::create('tenants', function (Blueprint $table) {
    $table->string('id')->primary();
    $table->json('data')->nullable();
    $table->timestamps();
});

// database/migrations/landlord/create_domains_table.php
Schema::create('domains', function (Blueprint $table) {
    $table->id();
    $table->string('domain')->unique();
    $table->string('tenant_id');
    $table->timestamps();
    $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
});
```

**Tenant Database Schema**
```php
// database/migrations/tenant/2024_01_01_create_desktop_configuration_table.php
Schema::create('desktop_configurations', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('team_id')->nullable();
    $table->string('theme')->default('dark');
    $table->string('wallpaper')->default('/wallpapers/default.jpg');
    $table->json('taskbar_settings')->nullable();
    $table->json('desktop_layout')->nullable();
    $table->timestamps();
    
    $table->unique(['user_id', 'team_id']);
    $table->index(['user_id', 'team_id']);
});

// database/migrations/tenant/2024_01_02_create_installed_apps_table.php
Schema::create('installed_apps', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('team_id')->nullable();
    $table->string('app_id');
    $table->string('app_name');
    $table->string('version')->default('1.0.0');
    $table->json('configuration')->nullable();
    $table->boolean('is_enabled')->default(true);
    $table->timestamps();
    
    $table->index(['team_id', 'app_id']);
});

// database/migrations/tenant/2024_01_03_create_notifications_table.php
Schema::create('notifications', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id');
    $table->unsignedBigInteger('team_id')->nullable();
    $table->string('title');
    $table->text('message');
    $table->string('type')->default('info');
    $table->string('icon')->nullable();
    $table->boolean('is_read')->default(false);
    $table->timestamp('read_at')->nullable();
    $table->timestamps();
    
    $table->index(['user_id', 'team_id', 'is_read']);
});
```

#### 1.3 Configuration Files

**config/tenancy.php**
```php
<?php

return [
    'tenant_model' => \App\Models\Tenant::class,
    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,
    
    'domain_model' => \Stancl\Tenancy\Database\Models\Domain::class,
    
    'database' => [
        'central_connection' => env('DB_CONNECTION', 'mysql'),
        'template_tenant_connection' => null,
        'prefix' => 'tenant',
        'suffix' => '',
    ],
    
    'features' => [
        Stancl\Tenancy\Features\TelescopeTags::class,
        Stancl\Tenancy\Features\TenantConfig::class,
        Stancl\Tenancy\Features\CrossDomainRedirect::class,
    ],
    
    'migration_parameters' => [
        '--force' => true,
        '--path' => [database_path('migrations/tenant')],
    ],
];
```

**config/desktop.php**
```php
<?php

return [
    'default_apps' => [
        'file-explorer' => [
            'name' => 'File Explorer',
            'icon' => 'fa-folder',
            'category' => 'system',
            'component' => 'FileExplorerApp',
            'version' => '1.0.0',
            'permissions' => ['files.read', 'files.write'],
        ],
        'settings' => [
            'name' => 'Settings',
            'icon' => 'fa-cog', 
            'category' => 'system',
            'component' => 'SettingsApp',
            'version' => '1.0.0',
            'permissions' => ['settings.read', 'settings.write'],
        ],
        'app-store' => [
            'name' => 'App Store',
            'icon' => 'fa-store',
            'category' => 'productivity',
            'component' => 'AppStoreApp',
            'version' => '1.0.0',
            'permissions' => ['apps.browse'],
        ],
        'calculator' => [
            'name' => 'Calculator',
            'icon' => 'fa-calculator',
            'category' => 'utilities',
            'component' => 'CalculatorApp',
            'version' => '1.0.0',
            'permissions' => [],
        ],
        'site-builder' => [
            'name' => 'Site Builder',
            'icon' => 'fa-code',
            'category' => 'development',
            'component' => 'SiteBuilderApp',
            'version' => '1.0.0',
            'permissions' => ['sites.read', 'sites.write'],
        ],
        'email-app' => [
            'name' => 'Email',
            'icon' => 'fa-envelope',
            'category' => 'communication',
            'component' => 'EmailApp',
            'version' => '1.0.0',
            'permissions' => ['email.read', 'email.write'],
        ],
        'calendar-app' => [
            'name' => 'Calendar',
            'icon' => 'fa-calendar',
            'category' => 'productivity',
            'component' => 'CalendarApp',
            'version' => '1.0.0',
            'permissions' => ['calendar.read', 'calendar.write'],
        ],
        'point-of-sale-app' => [
            'name' => 'Point of Sale',
            'icon' => 'fa-cash-register',
            'category' => 'business',
            'component' => 'PointOfSaleApp',
            'version' => '1.0.0',
            'permissions' => ['pos.access'],
        ],
        'contact-app' => [
            'name' => 'Contacts',
            'icon' => 'fa-address-book',
            'category' => 'communication',
            'component' => 'ContactApp',
            'version' => '1.0.0',
            'permissions' => ['contacts.read', 'contacts.write'],
        ],
        'orders-manager' => [
            'name' => 'Orders Manager',
            'icon' => 'fa-shopping-cart',
            'category' => 'business',
            'component' => 'OrdersManagerApp',
            'version' => '1.0.0',
            'permissions' => ['orders.read', 'orders.write'],
        ],
        'products-manager' => [
            'name' => 'Products Manager',
            'icon' => 'fa-box',
            'category' => 'business',
            'component' => 'ProductsManagerApp',
            'version' => '1.0.0',
            'permissions' => ['products.read', 'products.write'],
        ],
    ],
    
    'theme' => [
        'default' => 'dark',
        'available' => ['light', 'dark', 'auto'],
    ],
    
    'desktop' => [
        'grid_size' => 80,
        'icon_spacing' => 3,
    ],
    
    'windows' => [
        'default_width' => 800,
        'default_height' => 600,
        'min_width' => 350,
        'min_height' => 400,
    ],
];
```

### Phase 2: Model and Controller Implementation (Week 2)

#### 2.1 Core Models

**app/Models/Tenant.php**
```php
<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;

    protected $casts = [
        'data' => 'array',
    ];

    public static function getCustomColumns(): array
    {
        return ['id', 'data'];
    }

    public function getName(): string
    {
        return $this->data['name'] ?? $this->id;
    }

    public function getLogoUrl(): ?string
    {
        return $this->data['logo_url'] ?? null;
    }
}
```

**app/Models/DesktopConfiguration.php**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesktopConfiguration extends Model
{
    protected $fillable = [
        'user_id',
        'team_id',
        'theme',
        'wallpaper',
        'taskbar_settings',
        'desktop_layout',
    ];

    protected $casts = [
        'taskbar_settings' => 'array',
        'desktop_layout' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
```

**app/Models/InstalledApp.php**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstalledApp extends Model
{
    protected $fillable = [
        'team_id',
        'app_id',
        'app_name',
        'version',
        'configuration',
        'is_enabled',
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_enabled' => 'boolean',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
```

#### 2.2 Core Controllers

**app/Http/Controllers/Tenant/DesktopController.php**
```php
<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\DesktopConfiguration;
use App\Models\InstalledApp;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DesktopController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $currentTeam = $user->currentTeam;

        // Get or create desktop configuration
        $desktopConfig = DesktopConfiguration::firstOrCreate([
            'user_id' => $user->id,
            'team_id' => $currentTeam?->id,
        ], [
            'theme' => config('desktop.theme.default'),
            'wallpaper' => '/wallpapers/default.jpg',
            'taskbar_settings' => [],
            'desktop_layout' => [],
        ]);

        // Get installed apps for current team
        $installedApps = InstalledApp::where('team_id', $currentTeam?->id)
            ->where('is_enabled', true)
            ->get()
            ->keyBy('app_id');

        // Merge with default apps
        $availableApps = collect(config('desktop.default_apps'))
            ->map(function ($appConfig, $appId) use ($installedApps) {
                $installedApp = $installedApps->get($appId);
                
                return array_merge($appConfig, [
                    'id' => $appId,
                    'is_installed' => $installedApp !== null,
                    'version' => $installedApp?->version ?? $appConfig['version'],
                    'configuration' => $installedApp?->configuration ?? [],
                ]);
            });

        // Get recent notifications
        $notifications = $user->notifications()
            ->where('team_id', $currentTeam?->id)
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Desktop', [
            'user' => $user->load('teams'),
            'currentTeam' => $currentTeam,
            'desktopConfig' => $desktopConfig,
            'availableApps' => $availableApps,
            'notifications' => $notifications,
        ]);
    }

    public function updateConfiguration(Request $request)
    {
        $validated = $request->validate([
            'theme' => 'in:light,dark,auto',
            'wallpaper' => 'string',
            'taskbar_settings' => 'array',
            'desktop_layout' => 'array',
        ]);

        $config = DesktopConfiguration::updateOrCreate([
            'user_id' => $request->user()->id,
            'team_id' => $request->user()->currentTeam?->id,
        ], $validated);

        return response()->json(['success' => true, 'config' => $config]);
    }
}
```

**app/Http/Controllers/Tenant/AppController.php**
```php
<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\InstalledApp;
use Illuminate\Http\Request;

class AppController extends Controller
{
    public function index(Request $request)
    {
        $teamId = $request->user()->currentTeam?->id;
        
        $installedApps = InstalledApp::where('team_id', $teamId)
            ->where('is_enabled', true)
            ->get()
            ->keyBy('app_id');

        $availableApps = collect(config('desktop.default_apps'))
            ->map(function ($appConfig, $appId) use ($installedApps) {
                $installedApp = $installedApps->get($appId);
                
                return array_merge($appConfig, [
                    'id' => $appId,
                    'is_installed' => $installedApp !== null,
                    'version' => $installedApp?->version ?? $appConfig['version'],
                    'configuration' => $installedApp?->configuration ?? [],
                ]);
            });

        return response()->json(['apps' => $availableApps]);
    }

    public function install(Request $request, string $appId)
    {
        $appConfig = config("desktop.default_apps.{$appId}");
        
        if (!$appConfig) {
            return response()->json(['error' => 'App not found'], 404);
        }

        $teamId = $request->user()->currentTeam?->id;

        $installedApp = InstalledApp::updateOrCreate([
            'team_id' => $teamId,
            'app_id' => $appId,
        ], [
            'app_name' => $appConfig['name'],
            'version' => $appConfig['version'],
            'configuration' => [],
            'is_enabled' => true,
        ]);

        return response()->json(['success' => true, 'app' => $installedApp]);
    }

    public function uninstall(Request $request, string $appId)
    {
        $teamId = $request->user()->currentTeam?->id;

        InstalledApp::where('team_id', $teamId)
            ->where('app_id', $appId)
            ->delete();

        return response()->json(['success' => true]);
    }

    public function configure(Request $request, string $appId)
    {
        $validated = $request->validate([
            'configuration' => 'array',
        ]);

        $teamId = $request->user()->currentTeam?->id;

        $installedApp = InstalledApp::where('team_id', $teamId)
            ->where('app_id', $appId)
            ->firstOrFail();

        $installedApp->update(['configuration' => $validated['configuration']]);

        return response()->json(['success' => true, 'app' => $installedApp]);
    }
}
```

#### 2.3 Route Configuration

**routes/tenant.php**
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tenant\DesktopController;
use App\Http\Controllers\Tenant\AppController;
use App\Http\Controllers\Tenant\NotificationController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    
    // Authentication routes ([Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/))
    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
        
        // Main desktop route
        Route::get('/', [DesktopController::class, 'index'])->name('desktop');
        
        // API routes for desktop functionality
        Route::prefix('api')->group(function () {
            
            // Desktop configuration
            Route::put('/desktop/configuration', [DesktopController::class, 'updateConfiguration']);
            
            // App management
            Route::get('/apps', [AppController::class, 'index']);
            Route::post('/apps/{appId}/install', [AppController::class, 'install']);
            Route::delete('/apps/{appId}', [AppController::class, 'uninstall']);
            Route::put('/apps/{appId}/configure', [AppController::class, 'configure']);
            
            // Notifications
            Route::get('/notifications', [NotificationController::class, 'index']);
            Route::put('/notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead']);
            Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        });
    });
});
```

**routes/web.php** (Central routes)
```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Central\TenantController;
use App\Http\Controllers\Central\LandingController;

// Landing page and marketing routes
Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/pricing', [LandingController::class, 'pricing'])->name('pricing');
Route::get('/features', [LandingController::class, 'features'])->name('features');

// Central authentication and tenant management
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return inertia('Dashboard');
    })->name('dashboard');
    
    // Tenant management
    Route::resource('tenants', TenantController::class);
});
```

### Phase 3: Frontend Integration (Week 3)

#### 3.1 Inertia Configuration

**app/Http/Middleware/HandleInertiaRequests.php**
```php
<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? $request->user()->load('teams') : null,
                'currentTeam' => $request->user()?->currentTeam,
            ],
            'tenant' => function () {
                if (app()->bound('currentTenant')) {
                    return [
                        'id' => app('currentTenant')->id,
                        'name' => app('currentTenant')->getName(),
                        'logo' => app('currentTenant')->getLogoUrl(),
                    ];
                }
                return null;
            },
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}
```

#### 3.2 Vue Component Integration

**resources/js/app.ts**
```typescript
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m'

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Desktop OS'
const pinia = createPinia()

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    return createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(pinia)
      .use(ZiggyVue, Ziggy)
      .mount(el)
  },
  progress: {
    color: '#4F46E5',
  },
})
```

**resources/js/Pages/Desktop.vue**
```vue
<template>
  <div class="desktop-container">
    <DesktopLayout
      :apps="availableApps"
      :config="desktopConfig"
      :notifications="notifications"
      @app-launch="handleAppLaunch"
      @config-update="handleConfigUpdate"
    />
  </div>
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import DesktopLayout from '@/Layouts/DesktopLayout.vue'

interface Props {
  user: any
  currentTeam: any
  desktopConfig: any
  availableApps: any[]
  notifications: any[]
}

const props = defineProps<Props>()

const handleAppLaunch = (appId: string) => {
  // App launching is handled by the frontend WindowManager
  console.log('Launching app:', appId)
}

const handleConfigUpdate = (config: any) => {
  router.put('/api/desktop/configuration', config, {
    preserveScroll: true,
  })
}
</script>
```

### Phase 4: Business Application Controllers (Week 4)

#### 4.1 Email System

**app/Http/Controllers/Tenant/EmailController.php**
```php
<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\EmailThread;
use App\Models\EmailMessage;
use Illuminate\Http\Request;

class EmailController extends Controller
{
    public function index(Request $request)
    {
        $teamId = $request->user()->currentTeam?->id;
        
        $threads = EmailThread::where('team_id', $teamId)
            ->with(['messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->orderBy('updated_at', 'desc')
            ->paginate(50);

        return response()->json(['threads' => $threads]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'thread_id' => 'nullable|exists:email_threads,id',
        ]);

        $teamId = $request->user()->currentTeam?->id;

        if ($validated['thread_id']) {
            $thread = EmailThread::findOrFail($validated['thread_id']);
        } else {
            $thread = EmailThread::create([
                'team_id' => $teamId,
                'subject' => $validated['subject'],
                'participants' => [$request->user()->email, $validated['to']],
            ]);
        }

        $message = EmailMessage::create([
            'thread_id' => $thread->id,
            'user_id' => $request->user()->id,
            'to' => $validated['to'],
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'sent_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => $message->load('thread')]);
    }
}
```

#### 4.2 Business Data Controllers

**app/Http/Controllers/Tenant/ContactController.php**
```php
<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $teamId = $request->user()->currentTeam?->id;
        
        $contacts = Contact::where('team_id', $teamId)
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(50);

        return response()->json(['contacts' => $contacts]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $contact = Contact::create([
            ...$validated,
            'team_id' => $request->user()->currentTeam?->id,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'contact' => $contact]);
    }

    public function update(Request $request, Contact $contact)
    {
        $this->authorize('update', $contact);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $contact->update($validated);

        return response()->json(['success' => true, 'contact' => $contact]);
    }

    public function destroy(Contact $contact)
    {
        $this->authorize('delete', $contact);
        
        $contact->delete();

        return response()->json(['success' => true]);
    }
}
```

### Phase 5: Authentication & Security (Week 5)

#### 5.1 API Authentication Setup

**app/Http/Kernel.php**
```php
protected $middlewareGroups = [
    'web' => [
        // ... existing middleware
        \App\Http\Middleware\HandleInertiaRequests::class,
    ],
    
    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];

protected $routeMiddleware = [
    // ... existing middleware
    'tenant' => \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class,
    'prevent.central' => \Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains::class,
];
```

#### 5.2 Team Permission System

**app/Policies/ContactPolicy.php**
```php
<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->currentTeam && $user->hasTeamPermission($user->currentTeam, 'contacts:read');
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->currentTeam 
            && $contact->team_id === $user->currentTeam->id
            && $user->hasTeamPermission($user->currentTeam, 'contacts:read');
    }

    public function create(User $user): bool
    {
        return $user->currentTeam && $user->hasTeamPermission($user->currentTeam, 'contacts:create');
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->currentTeam 
            && $contact->team_id === $user->currentTeam->id
            && $user->hasTeamPermission($user->currentTeam, 'contacts:update');
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->currentTeam 
            && $contact->team_id === $user->currentTeam->id
            && $user->hasTeamPermission($user->currentTeam, 'contacts:delete');
    }
}
```

### Phase 6: Testing & Deployment (Week 6)

#### 6.1 Feature Tests

**tests/Feature/Tenant/DesktopTest.php**
```php
<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Stancl\Tenancy\Features\TenantsCanBeCreated;

class DesktopTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a tenant for testing
        $this->tenant = Tenant::create(['id' => 'test-tenant']);
        $this->tenant->domains()->create(['domain' => 'test.localhost']);
        
        // Initialize tenancy
        tenancy()->initialize($this->tenant);
    }

    public function test_desktop_loads_with_default_configuration()
    {
        $user = User::factory()->withPersonalTeam()->create();
        
        $response = $this->actingAs($user)->get('/');
        
        $response->assertOk()
                ->assertInertia(fn ($page) => 
                    $page->component('Desktop')
                         ->has('desktopConfig')
                         ->has('availableApps')
                );
    }

    public function test_user_can_update_desktop_configuration()
    {
        $user = User::factory()->withPersonalTeam()->create();
        
        $response = $this->actingAs($user)->put('/api/desktop/configuration', [
            'theme' => 'light',
            'wallpaper' => '/wallpapers/custom.jpg',
        ]);
        
        $response->assertOk()
                ->assertJson(['success' => true]);
                
        $this->assertDatabaseHas('desktop_configurations', [
            'user_id' => $user->id,
            'theme' => 'light',
            'wallpaper' => '/wallpapers/custom.jpg',
        ]);
    }
}
```

#### 6.2 Deployment Configuration

**docker-compose.yml**
```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "8000:8000"
    environment:
      - APP_ENV=production
      - DB_CONNECTION=mysql
      - DB_HOST=db
      - DB_DATABASE=os_desktop
      - DB_USERNAME=root
      - DB_PASSWORD=secret
    volumes:
      - .:/var/www/html
    depends_on:
      - db
      - redis

  db:
    image: mysql:8.0
    environment:
      - MYSQL_ROOT_PASSWORD=secret
      - MYSQL_DATABASE=os_desktop
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:alpine
    command: redis-server --appendonly yes
    volumes:
      - redis_data:/data

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/conf.d/default.conf
      - ./ssl:/etc/nginx/ssl
    depends_on:
      - app

volumes:
  mysql_data:
  redis_data:
```

## Implementation Checklist

### ✅ **Completed (Ready for Integration)**
- [x] **Frontend Architecture**: Complete Vue 3 + TypeScript implementation
- [x] **File Management System**: ElFinder + R2 + Multi-tenant storage 
- [x] **Component Library**: All 10+ applications implemented
- [x] **Build System**: Vite + Tailwind CSS optimized
- [x] **Mobile Support**: Touch gestures, responsive design
- [x] **State Management**: Pinia stores and composables

### 🔄 **In Progress (Backend Implementation)**
- [ ] **Laravel Project Setup**: Base installation and dependencies
- [ ] **Tenancy Configuration**: stancl/tenancy v4 setup
- [ ] **[Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/) Integration**: Authentication
- [ ] **Database Migrations**: Central and tenant schemas
- [ ] **API Controllers**: Desktop and business logic endpoints
- [ ] **Route Configuration**: Tenant and central routing
- [ ] **Authentication**: Sanctum tokens and team permissions
- [ ] **Testing Suite**: Feature and unit tests
- [ ] **Deployment**: Docker and production configuration

## Expected Timeline

| Phase | Duration | Status | Dependencies |
|-------|----------|--------|--------------|
| **Phase 1**: Laravel Foundation | 1 week | 🔄 Pending | None |
| **Phase 2**: Models & Controllers | 1 week | 🔄 Pending | Phase 1 |
| **Phase 3**: Frontend Integration | 1 week | 🔄 Pending | Phase 2 |
| **Phase 4**: Business Controllers | 1 week | 🔄 Pending | Phase 3 |
| **Phase 5**: Auth & Security | 1 week | 🔄 Pending | Phase 4 |
| **Phase 6**: Testing & Deployment | 1 week | 🔄 Pending | Phase 5 |

**Total Estimated Time**: 6 weeks for complete backend integration

## Critical Success Factors

### 1. **Tenant Isolation**
- Separate databases per tenant
- Domain-based tenant identification
- Secure file storage per tenant
- Team-scoped data access

### 2. **Performance**
- Efficient database queries with proper indexing
- Lazy loading of app components
- Optimized asset delivery
- Redis caching for tenant data

### 3. **Security**
- CSRF protection on all API endpoints
- Team-based permission system
- Secure file upload and storage
- Input validation and sanitization

### 4. **Scalability**
- Horizontal scaling with multiple tenants
- Efficient resource usage per tenant
- Database optimization for growth
- CDN integration for assets

## Post-Implementation Enhancements

### **Phase 7+: Advanced Features**
- **Real-time Features**: WebSocket integration for live collaboration
- **Plugin System**: Tenant-installable custom applications
- **Advanced Analytics**: Usage tracking and reporting per tenant
- **Custom Branding**: Full tenant customization capabilities
- **API Marketplace**: Third-party app integration ecosystem
- **Backup & Recovery**: Automated tenant data backup systems
- **Advanced Security**: 2FA, SSO, audit trails
- **Mobile Apps**: Native iOS/Android companion apps

## Conclusion

This implementation plan provides a comprehensive roadmap for integrating the completed frontend implementation with a robust Laravel backend featuring multi-tenancy and team collaboration. The modular approach ensures maintainability while the tenant-first design enables scalable SaaS deployment.

The critical next step is **Phase 1: Laravel Foundation Setup** which will establish the core backend infrastructure needed to support the existing frontend implementation.