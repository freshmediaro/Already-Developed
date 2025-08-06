# 🖥️ Desktop OS Implementation Guide for Laravel

A complete step-by-step guide to implement the desktop OS interface in your existing Laravel + Tenancy + Teams + Inertia.js + Vue.js setup.

## 📋 Prerequisites

Before starting, ensure you have:
- ✅ Laravel (9.x or higher)
- ✅ `stancl/tenancy` package installed and configured
- ✅ Laravel Vue Starter Kit (https://github.com/laravel/vue-starter-kit/) 
- ✅ Inertia.js with Vue.js setup
- ✅ Authentication system working
- ✅ Node.js and npm installed

---

## 🎯 Phase 1: Frontend Dependencies & Configuration

### Step 1.1: Install Required NPM Packages

```bash
# Navigate to your Laravel project root
cd your-laravel-project

# Install core UI dependencies
npm install @heroicons/vue@^2.0.0
npm install @headlessui/vue@^1.7.0

# Install development dependencies
npm install --save-dev @types/node
npm install --save-dev typescript
npm install --save-dev vue-tsc
```

### Step 1.2: Update Tailwind Configuration

Update your `tailwind.config.js`:

```javascript
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/vue-starter-kit/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // Desktop OS specific variables
            colors: {
                'desktop-blue': '#0078D4',
                'desktop-bg': '#000428',
                'desktop-surface': 'rgba(255, 255, 255, 0.1)',
            },
            spacing: {
                '18': '4.5rem',
                '88': '22rem',
            },
            zIndex: {
                '60': '60',
                '70': '70',
                '80': '80',
                '90': '90',
                '100': '100',
                '1000': '1000',
                '1100': '1100',
            },
            // CSS Grid variables for desktop icons
            gridTemplateColumns: {
                'desktop': 'repeat(auto-fill, minmax(80px, 1fr))',
            },
            // Animation for desktop effects
            animation: {
                'fadeIn': 'fadeIn 0.3s ease-in-out',
                'slideIn': 'slideIn 0.3s ease-out',
                'pulse-slow': 'pulse 3s infinite',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                slideIn: {
                    '0%': { transform: 'translateY(10px)', opacity: '0' },
                    '100%': { transform: 'translateY(0)', opacity: '1' },
                },
            }
        },
    },
    plugins: [forms],
};
```

### Step 1.3: Update Vite Configuration

Update your `vite.config.js`:

```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.ts', // Note: changed to .ts
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
        },
    },
    define: {
        __VUE_OPTIONS_API__: false,
    },
});
```

### Step 1.4: TypeScript Configuration

Create `tsconfig.json` in your project root:

```json
{
  "compilerOptions": {
    "target": "ES2020",
    "lib": ["ES2020", "DOM", "DOM.Iterable"],
    "allowJs": true,
    "skipLibCheck": true,
    "esModuleInterop": true,
    "allowSyntheticDefaultImports": true,
    "strict": true,
    "forceConsistentCasingInFileNames": true,
    "module": "ESNext",
    "moduleResolution": "Node",
    "resolveJsonModule": true,
    "isolatedModules": true,
    "noEmit": true,
    "jsx": "preserve",
    "baseUrl": ".",
    "paths": {
      "@/*": ["resources/js/*"]
    },
    "types": ["vite/client", "node"]
  },
  "include": [
    "resources/js/**/*.ts",
    "resources/js/**/*.d.ts",
    "resources/js/**/*.tsx",
    "resources/js/**/*.vue"
  ],
  "exclude": ["node_modules"]
}
```

---

## 🏗️ Phase 2: Backend Infrastructure

### Step 2.1: Database Migrations

Create the required database tables:

```bash
# User preferences
php artisan make:migration create_user_preferences_table

# Desktop icons
php artisan make:migration create_desktop_icons_table

# Apps registry
php artisan make:migration create_apps_table

# Notifications
php artisan make:migration create_desktop_notifications_table
```

#### User Preferences Migration:

```php
<?php
// database/migrations/xxxx_create_user_preferences_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('desktop_wallpaper')->default('/img/wallpapers/default.jpg');
            $table->enum('theme', ['light', 'dark', 'system'])->default('system');
            $table->json('notification_settings')->nullable();
            $table->json('volume_settings')->nullable();
            $table->json('privacy_settings')->nullable();
            $table->json('taskbar_settings')->nullable();
            $table->json('interface_settings')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'team_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_preferences');
    }
};
```

#### Desktop Icons Migration:

```php
<?php
// database/migrations/xxxx_create_desktop_icons_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('desktop_icons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('app_id');
            $table->string('name');
            $table->string('icon')->nullable();
            $table->integer('position_x')->nullable();
            $table->integer('position_y')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('visible')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'team_id', 'visible']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('desktop_icons');
    }
};
```

#### Apps Migration:

```php
<?php
// database/migrations/xxxx_create_apps_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('apps', function (Blueprint $table) {
            $table->id();
            $table->string('app_id')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon');
            $table->string('component');
            $table->string('category')->default('utilities');
            $table->json('permissions')->nullable();
            $table->boolean('installed')->default(true);
            $table->boolean('system')->default(false);
            $table->boolean('team_scoped')->default(true);
            $table->string('version')->default('1.0.0');
            $table->string('author')->nullable();
            $table->decimal('price', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('apps');
    }
};
```

#### Desktop Notifications Migration:

```php
<?php
// database/migrations/xxxx_create_desktop_notifications_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('desktop_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('info'); // info, success, warning, error
            $table->string('icon')->nullable();
            $table->json('data')->nullable();
            $table->boolean('read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'team_id', 'read', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('desktop_notifications');
    }
};
```

### Step 2.2: Eloquent Models

Create the required models:

```bash
php artisan make:model UserPreference
php artisan make:model DesktopIcon
php artisan make:model App
php artisan make:model DesktopNotification
```

#### UserPreference Model:

```php
<?php
// app/Models/UserPreference.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'team_id',
        'desktop_wallpaper',
        'theme',
        'notification_settings',
        'volume_settings',
        'privacy_settings',
        'taskbar_settings',
        'interface_settings',
    ];

    protected $casts = [
        'notification_settings' => 'array',
        'volume_settings' => 'array',
        'privacy_settings' => 'array',
        'taskbar_settings' => 'array',
        'interface_settings' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public static function getDefaults(): array
    {
        return [
            'desktop_wallpaper' => '/img/wallpapers/default.jpg',
            'theme' => 'system',
            'notification_settings' => [
                'desktop_enabled' => true,
                'email_enabled' => true,
                'push_enabled' => true,
                'sound_enabled' => true,
                'stacking_mode' => 'three',
            ],
            'volume_settings' => [
                'master_volume' => 50,
                'muted' => false,
                'app_volumes' => [],
            ],
            'privacy_settings' => [
                'show_online_status' => true,
                'allow_team_notifications' => true,
                'share_usage_data' => false,
            ],
            'taskbar_settings' => [
                'style' => 'default',
                'showSearchBar' => true,
                'showAppLauncher' => true,
                'showGlobalSearch' => true,
                'showWallet' => true,
                'showFullscreen' => true,
                'showVolume' => true,
            ],
            'interface_settings' => [
                'mode' => 'desktop',
                'isFullscreen' => false,
            ],
        ];
    }
}
```

#### DesktopIcon Model:

```php
<?php
// app/Models/DesktopIcon.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesktopIcon extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'team_id',
        'app_id',
        'name',
        'icon',
        'position_x',
        'position_y',
        'order',
        'visible',
    ];

    protected $casts = [
        'position_x' => 'integer',
        'position_y' => 'integer',
        'order' => 'integer',
        'visible' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id', 'app_id');
    }
}
```

#### App Model:

```php
<?php
// app/Models/App.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class App extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_id',
        'name',
        'description',
        'icon',
        'component',
        'category',
        'permissions',
        'installed',
        'system',
        'team_scoped',
        'version',
        'author',
        'price',
    ];

    protected $casts = [
        'permissions' => 'array',
        'installed' => 'boolean',
        'system' => 'boolean',
        'team_scoped' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function getRouteKeyName(): string
    {
        return 'app_id';
    }
}
```

### Step 2.3: Seeders

Create seeders for initial data:

```bash
php artisan make:seeder AppsSeeder
```

#### Apps Seeder:

```php
<?php
// database/seeders/AppsSeeder.php

namespace Database\Seeders;

use App\Models\App;
use Illuminate\Database\Seeder;

class AppsSeeder extends Seeder
{
    public function run(): void
    {
        $apps = [
            [
                'app_id' => 'file-explorer',
                'name' => 'File Explorer',
                'description' => 'Browse and manage files',
                'icon' => 'fa-folder',
                'component' => 'FileExplorer',
                'category' => 'system',
                'system' => true,
                'permissions' => ['files.read', 'files.write'],
            ],
            [
                'app_id' => 'settings',
                'name' => 'Settings',
                'description' => 'System and user preferences',
                'icon' => 'fa-cog',
                'component' => 'Settings',
                'category' => 'system',
                'system' => true,
                'permissions' => ['settings.read', 'settings.write'],
            ],
            [
                'app_id' => 'calculator',
                'name' => 'Calculator',
                'description' => 'Simple calculator app',
                'icon' => 'fa-calculator',
                'component' => 'Calculator',
                'category' => 'utilities',
                'system' => true,
                'permissions' => [],
            ],
            [
                'app_id' => 'email',
                'name' => 'Email',
                'description' => 'Email client application',
                'icon' => 'fa-envelope',
                'component' => 'Email',
                'category' => 'communication',
                'system' => false,
                'permissions' => ['email.read', 'email.write'],
            ],
            [
                'app_id' => 'contacts',
                'name' => 'Contacts',
                'description' => 'Contact management',
                'icon' => 'fa-address-book',
                'component' => 'Contacts',
                'category' => 'business',
                'system' => false,
                'permissions' => ['contacts.read', 'contacts.write'],
            ],
            [
                'app_id' => 'app-store',
                'name' => 'App Store',
                'description' => 'Browse and install applications',
                'icon' => 'fa-store',
                'component' => 'AppStore',
                'category' => 'system',
                'system' => true,
                'permissions' => ['apps.install'],
            ],
        ];

        foreach ($apps as $app) {
            App::updateOrCreate(
                ['app_id' => $app['app_id']],
                $app
            );
        }
    }
}
```

### Step 2.4: Controllers

Create the required controllers:

```bash
php artisan make:controller DesktopController
php artisan make:controller Api/DesktopApiController
```

#### Desktop Controller:

```php
<?php
// app/Http/Controllers/DesktopController.php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\DesktopIcon;
use App\Models\DesktopNotification;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DesktopController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $currentTeam = $user->currentTeam;
        $allTeams = $user->allTeams();

        // Get user preferences
        $userPreferences = UserPreference::where('user_id', $user->id)
            ->where('team_id', $currentTeam?->id)
            ->first();

        if (!$userPreferences) {
            $userPreferences = UserPreference::create([
                'user_id' => $user->id,
                'team_id' => $currentTeam?->id,
                ...UserPreference::getDefaults()
            ]);
        }

        // Get desktop icons
        $desktopIcons = DesktopIcon::with('app')
            ->where('user_id', $user->id)
            ->where('team_id', $currentTeam?->id)
            ->where('visible', true)
            ->orderBy('order')
            ->get()
            ->map(function ($icon) {
                return [
                    'id' => $icon->id,
                    'appId' => $icon->app_id,
                    'name' => $icon->name,
                    'icon' => $icon->icon ?? $icon->app->icon,
                    'type' => 'app',
                    'x' => $icon->position_x,
                    'y' => $icon->position_y,
                ];
            });

        // Get available apps
        $availableApps = App::where('installed', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        // Get taskbar apps (pinned apps)
        $taskbarApps = App::whereIn('app_id', ['file-explorer', 'settings', 'calculator', 'email'])
            ->get()
            ->map(function ($app) {
                return [
                    'id' => $app->app_id,
                    'name' => $app->name,
                    'icon' => $app->icon,
                    'windowId' => null, // Will be set when app is launched
                ];
            });

        // Get notifications
        $notifications = DesktopNotification::where('user_id', $user->id)
            ->where('team_id', $currentTeam?->id)
            ->latest()
            ->limit(20)
            ->get();

        return Inertia::render('Desktop', [
            'isDesktopMode' => true,
            'user' => $user,
            'currentTeam' => $currentTeam,
            'allTeams' => $allTeams,
            'userPreferences' => $userPreferences,
            'desktopIcons' => $desktopIcons,
            'availableApps' => $availableApps,
            'taskbarApps' => $taskbarApps,
            'notifications' => $notifications,
            'widgets' => [], // Can be expanded later
        ]);
    }
}
```

#### Desktop API Controller:

```php
<?php
// app/Http/Controllers/Api/DesktopApiController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\DesktopIcon;
use App\Models\DesktopNotification;
use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DesktopApiController extends Controller
{
    public function data(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentTeam = $user->currentTeam;

        // Get user preferences
        $userPreferences = UserPreference::where('user_id', $user->id)
            ->where('team_id', $currentTeam?->id)
            ->first() ?? UserPreference::getDefaults();

        // Get desktop icons
        $desktopIcons = DesktopIcon::with('app')
            ->where('user_id', $user->id)
            ->where('team_id', $currentTeam?->id)
            ->where('visible', true)
            ->orderBy('order')
            ->get()
            ->map(function ($icon) {
                return [
                    'id' => $icon->id,
                    'appId' => $icon->app_id,
                    'name' => $icon->name,
                    'icon' => $icon->icon ?? $icon->app->icon,
                    'type' => 'app',
                    'x' => $icon->position_x,
                    'y' => $icon->position_y,
                ];
            });

        // Get available apps
        $availableApps = App::where('installed', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        // Get taskbar apps
        $taskbarApps = App::whereIn('app_id', ['file-explorer', 'settings', 'calculator', 'email'])
            ->get()
            ->map(function ($app) {
                return [
                    'id' => $app->app_id,
                    'name' => $app->name,
                    'icon' => $app->icon,
                    'windowId' => null,
                ];
            });

        // Get notifications
        $notifications = DesktopNotification::where('user_id', $user->id)
            ->where('team_id', $currentTeam?->id)
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'desktopIcons' => $desktopIcons,
            'availableApps' => $availableApps,
            'taskbarApps' => $taskbarApps,
            'notifications' => $notifications,
            'widgets' => [],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q');
        $user = $request->user();
        $currentTeam = $user->currentTeam;

        if (empty($query)) {
            return response()->json([]);
        }

        // Search in apps
        $apps = App::where('installed', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get()
            ->map(function ($app) {
                return [
                    'type' => 'app',
                    'title' => $app->name,
                    'description' => $app->description,
                    'icon' => $app->icon,
                    'appId' => $app->app_id,
                ];
            });

        return response()->json($apps);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentTeam = $user->currentTeam;

        $preferences = UserPreference::updateOrCreate(
            [
                'user_id' => $user->id,
                'team_id' => $currentTeam?->id,
            ],
            $request->validated()
        );

        return response()->json($preferences);
    }
}
```

---

## 🎨 Phase 3: Frontend Implementation

### Step 3.1: Copy Core TypeScript Files

Copy all the TypeScript files from the project to your Laravel project:

```bash
# Create the Core directory structure
mkdir -p resources/js/Core
mkdir -p resources/js/Components/Desktop
mkdir -p resources/js/Components/Apps
mkdir -p resources/js/Layouts
mkdir -p resources/js/Apps
mkdir -p resources/css/components
mkdir -p resources/css/components/apps

# Copy all the core TypeScript files
# (You'll need to copy these manually from the project files)
```

### Step 3.2: Main Application Entry Point

Rename `resources/js/app.js` to `resources/js/app.ts` and update it:

```typescript
// resources/js/app.ts

import './bootstrap';
import '../css/app.css';

import { createApp, h, DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';

// Import desktop application
import DesktopApplication from './app-desktop';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        // Initialize desktop application globally
        if (typeof window !== 'undefined') {
            window.desktopApp = new DesktopApplication();
        }

        return app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
```

### Step 3.3: Desktop Application File

Create `resources/js/app-desktop.ts`:

```typescript
// resources/js/app-desktop.ts

// Core system imports
import { windowManager } from './Core/WindowManager';
import { appRegistry } from './Core/AppRegistry';
import { appLoader } from './Core/AppLoader';
import { eventSystem } from './Core/EventSystem';
import { orientationManager } from './Core/OrientationManager';
import { volumeManager } from './Core/VolumeManager';
import { globalStateManager } from './Core/GlobalStateManager';
import { mobileSwipeManager } from './Core/MobileSwipeManager';

class DesktopApplication {
  private initialized = false;
  private currentTeamId?: string;
  private currentUserId?: number;

  /**
   * Initialize the desktop application
   */
  async initialize(): Promise<void> {
    if (this.initialized) {
      console.warn('Desktop application already initialized');
      return;
    }

    try {
      console.log('Initializing Desktop Application...');

      // Initialize tenant context
      await this.initializeTenantContext();

      // Initialize core systems
      await this.initializeCoreApps();

      // Setup app loader
      this.setupAppLoader();

      // Initialize desktop UI
      await this.initializeDesktop();

      // Setup global event listeners
      this.setupGlobalEventListeners();

      this.initialized = true;
      console.log('Desktop Application initialized successfully');

      // Emit initialization event
      eventSystem.emit('desktop:initialized', {});

    } catch (error) {
      console.error('Failed to initialize Desktop Application:', error);
      throw error;
    }
  }

  private async initializeTenantContext(): Promise<void> {
    // Get current user and team from Laravel
    const userElement = document.querySelector('meta[name="user"]');
    const teamElement = document.querySelector('meta[name="current-team"]');

    if (userElement) {
      this.currentUserId = parseInt(userElement.getAttribute('content') || '0');
    }

    if (teamElement) {
      this.currentTeamId = teamElement.getAttribute('content') || undefined;
    }
  }

  private async initializeCoreApps(): Promise<void> {
    // Register core system apps
    const coreApps = [
      { id: 'file-explorer', name: 'File Explorer', component: 'FileExplorer' },
      { id: 'settings', name: 'Settings', component: 'Settings' },
      { id: 'calculator', name: 'Calculator', component: 'Calculator' },
      { id: 'email', name: 'Email', component: 'Email' },
      { id: 'contacts', name: 'Contacts', component: 'Contacts' },
      { id: 'app-store', name: 'App Store', component: 'AppStore' },
    ];

    for (const app of coreApps) {
      await appRegistry.register(app.id, {
        name: app.name,
        component: app.component,
        version: '1.0.0',
        permissions: [],
      });
    }
  }

  private setupAppLoader(): void {
    // Configure app loader with dynamic imports
    appLoader.configure({
      'FileExplorer': () => import('./Components/Apps/FileExplorer.vue'),
      'Settings': () => import('./Components/Apps/Settings.vue'),
      'Calculator': () => import('./Components/Apps/Calculator.vue'),
      'Email': () => import('./Components/Apps/Email.vue'),
      'Contacts': () => import('./Components/Apps/Contacts.vue'),
      'AppStore': () => import('./Components/Apps/AppStore.vue'),
    });
  }

  private async initializeDesktop(): Promise<void> {
    // Initialize core managers
    orientationManager.initialize();
    volumeManager.initialize();
    globalStateManager.initialize();
    mobileSwipeManager.initialize();
  }

  private setupGlobalEventListeners(): void {
    // Global keyboard shortcuts
    document.addEventListener('keydown', (e) => {
      if (e.ctrlKey || e.metaKey) {
        switch (e.key) {
          case ' ':
            e.preventDefault();
            eventSystem.emit('global-search:show', {});
            break;
          case 'Escape':
            eventSystem.emit('global:escape', {});
            break;
        }
      }
    });

    // Window focus events
    window.addEventListener('focus', () => {
      eventSystem.emit('window:focused', {});
    });

    window.addEventListener('blur', () => {
      eventSystem.emit('window:blurred', {});
    });
  }

  /**
   * Launch an application
   */
  async launchApp(appId: string): Promise<string> {
    try {
      const windowId = await windowManager.createWindow({
        appId,
        title: appId,
        width: 800,
        height: 600,
        x: Math.random() * 200 + 100,
        y: Math.random() * 200 + 100,
      });

      eventSystem.emit('app:launched', { appId, windowId });
      return windowId;
    } catch (error) {
      console.error(`Failed to launch app ${appId}:`, error);
      throw error;
    }
  }

  /**
   * Activate a window
   */
  activateWindow(windowId: string): void {
    windowManager.activateWindow(windowId);
  }

  /**
   * Switch team context
   */
  async switchTeam(teamId: string): Promise<void> {
    this.currentTeamId = teamId;
    eventSystem.emit('team:switched', { teamId });
    
    // Reload desktop data for new team context
    window.location.reload();
  }

  /**
   * Event system access
   */
  on(event: string, callback: (data: any) => void): () => void {
    return eventSystem.on(event as any, callback);
  }

  /**
   * Get current state
   */
  getCurrentTeamId(): string | undefined {
    return this.currentTeamId;
  }

  getCurrentUserId(): number | undefined {
    return this.currentUserId;
  }

  isInitialized(): boolean {
    return this.initialized;
  }
}

export default DesktopApplication;
```

### Step 3.4: Desktop Page Component

Create `resources/js/Pages/Desktop.vue`:

```vue
<template>
  <DesktopLayout
    :title="title"
    :is-desktop-mode="isDesktopMode"
  >
    <!-- Desktop content is handled by the layout -->
  </DesktopLayout>
</template>

<script setup lang="ts">
import DesktopLayout from '@/Layouts/DesktopLayout.vue';

interface Props {
  title?: string;
  isDesktopMode?: boolean;
}

withDefaults(defineProps<Props>(), {
  title: 'Desktop',
  isDesktopMode: true
});
</script>
```

### Step 3.5: Environment Types

Create `resources/js/env.d.ts`:

```typescript
/// <reference types="vite/client" />

declare module '*.vue' {
  import type { DefineComponent } from 'vue';
  const component: DefineComponent<Record<string, unknown>, Record<string, unknown>, unknown>;
  export default component;
}

interface ImportMetaEnv {
  readonly VITE_APP_NAME: string;
  readonly VITE_APP_ENV: string;
  readonly VITE_APP_URL: string;
}

interface ImportMeta {
  readonly env: ImportMetaEnv;
}

// Global desktop application interface
interface Window {
  desktopApp: {
    initialize(): Promise<void>;
    launchApp(appId: string): Promise<string>;
    activateWindow(windowId: string): void;
    switchTeam(teamId: string): Promise<void>;
    on(event: string, callback: (data: any) => void): () => void;
  };
  vueDesktop: {
    launchApp(appId: string): Promise<string | null>;
    showNotifications(): void;
    showWidgets(): void;
    toggleStartMenu(): void;
    toggleGlobalSearch(): void;
  };
  elFinder: unknown;
  axios: unknown;
}
```

---

## 🎨 Phase 4: Styling & Assets

### Step 4.1: Main CSS File

Update `resources/css/app.css`:

```css
@tailwind base;
@tailwind components;
@tailwind utilities;

/* Import desktop-specific styles */
@import 'components/base-styles.css';
@import 'components/desktop.css';
@import 'components/windows.css';
@import 'components/taskbar.css';
@import 'components/context-menu.css';
@import 'components/start-menu.css';
@import 'components/notifications.css';
@import 'components/volume-panel.css';
@import 'mobile-responsive.css';

/* Import app-specific styles */
@import 'components/apps/file-explorer.css';
@import 'components/apps/settings.css';
@import 'components/apps/calculator.css';
@import 'components/apps/email.css';
@import 'components/apps/contacts.css';
@import 'components/apps/app-store.css';

/* Global CSS Variables */
:root {
  /* Desktop layout variables */
  --desktop-bg: linear-gradient(135deg, #000428 0%, #004e92 100%);
  --desktop-surface: rgba(255, 255, 255, 0.1);
  --desktop-border: rgba(255, 255, 255, 0.2);
  
  /* Grid system for icons */
  --grid-cell-width: 80px;
  --grid-cell-height: 80px;
  --grid-gap: 20px;
  
  /* Z-index layers */
  --z-desktop-background: 1;
  --z-desktop-icons: 10;
  --z-windows: 100;
  --z-taskbar: 1000;
  --z-context-menu: 1100;
  --z-modals: 1200;
  
  /* Window colors */
  --window-bg: rgba(30, 30, 30, 0.95);
  --window-border: rgba(255, 255, 255, 0.1);
  --window-header-bg: rgba(20, 20, 20, 0.9);
  
  /* Taskbar */
  --taskbar-bg: rgba(0, 0, 0, 0.8);
  --taskbar-height: 50px;
  
  /* Dark mode colors */
  --dark-mode-bg: #1a1a1a;
  --dark-mode-surface: #2d2d2d;
  --dark-mode-border: #404040;
  --dark-mode-text: #ffffff;
  --dark-mode-text-secondary: #cccccc;
  --dark-mode-hover: rgba(255, 255, 255, 0.1);
}

/* Base styles */
* {
  box-sizing: border-box;
}

html, body {
  margin: 0;
  padding: 0;
  height: 100%;
  overflow: hidden;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

#app {
  height: 100vh;
  width: 100vw;
  overflow: hidden;
}

/* Scrollbar styling */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.5);
}

/* Utility classes */
.blur-backdrop {
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
}

.glass-effect {
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
}

.text-shadow {
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

.no-select {
  -webkit-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideIn {
  from { 
    opacity: 0;
    transform: translateY(10px);
  }
  to { 
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes bounceIn {
  0% {
    opacity: 0;
    transform: scale(0.8);
  }
  50% {
    transform: scale(1.05);
  }
  100% {
    opacity: 1;
    transform: scale(1);
  }
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

.animate-slide-in {
  animation: slideIn 0.3s ease-out;
}

.animate-bounce-in {
  animation: bounceIn 0.4s ease-out;
}
```

### Step 4.2: Copy All CSS Component Files

You'll need to copy all the CSS files from the project:

- `resources/css/components/base-styles.css`
- `resources/css/components/desktop.css`
- `resources/css/components/windows.css`
- `resources/css/components/taskbar.css`
- `resources/css/components/context-menu.css`
- `resources/css/components/start-menu.css`
- `resources/css/components/notifications.css`
- `resources/css/components/volume-panel.css`
- `resources/css/mobile-responsive.css`
- All app-specific CSS files in `resources/css/components/apps/`

---

## 🔧 Phase 5: Routes & Integration

### Step 5.1: Web Routes

Add to your `routes/web.php`:

```php
<?php
// routes/web.php

use App\Http\Controllers\DesktopController;
use Illuminate\Support\Facades\Route;

// Desktop routes
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/desktop', [DesktopController::class, 'index'])->name('desktop');
    
    // Make desktop the default authenticated route
    Route::get('/dashboard', function () {
        return redirect()->route('desktop');
    })->name('dashboard');
});
```

### Step 5.2: API Routes

Add to your `routes/api.php`:

```php
<?php
// routes/api.php

use App\Http\Controllers\Api\DesktopApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('desktop')->group(function () {
    Route::get('/data', [DesktopApiController::class, 'data']);
    Route::get('/search', [DesktopApiController::class, 'search']);
    Route::post('/preferences', [DesktopApiController::class, 'updatePreferences']);
});
```

### Step 5.3: Update User Model

Add relationships to your `User` model:

```php
<?php
// app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
 // or your [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/) teams implementation

class User extends Authenticatable
{
    // or your [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/) teams trait

    // ... existing code ...

    /**
     * Get the user's desktop icons.
     */
    public function desktopIcons(): HasMany
    {
        return $this->hasMany(DesktopIcon::class);
    }

    /**
     * Get the user's preferences.
     */
    public function preferences(): HasMany
    {
        return $this->hasMany(UserPreference::class);
    }

    /**
     * Get the user's desktop notifications.
     */
    public function desktopNotifications(): HasMany
    {
        return $this->hasMany(DesktopNotification::class);
    }

    /**
     * Get user preferences for a specific team.
     */
    public function getPreferencesForTeam($teamId = null)
    {
        return $this->preferences()
            ->where('team_id', $teamId)
            ->first() ?? UserPreference::getDefaults();
    }
}
```

---

## 🚀 Phase 6: Finalization & Testing

### Step 6.1: Run Migrations and Seeders

```bash
# Run the migrations
php artisan migrate

# Run the seeders
php artisan db:seed --class=AppsSeeder

# Or run all seeders
php artisan db:seed
```

### Step 6.2: Build Frontend Assets

```bash
# Install dependencies
npm install

# Build for development
npm run dev

# Or build for production
npm run build
```

### Step 6.3: Create Default Desktop Icons

Create a command to set up default desktop icons for users:

```bash
php artisan make:command SetupDefaultDesktopIcons
```

```php
<?php
// app/Console/Commands/SetupDefaultDesktopIcons.php

namespace App\Console\Commands;

use App\Models\App;
use App\Models\DesktopIcon;
use App\Models\User;
use Illuminate\Console\Command;

class SetupDefaultDesktopIcons extends Command
{
    protected $signature = 'desktop:setup-icons {--user=}';
    protected $description = 'Setup default desktop icons for users';

    public function handle()
    {
        $userId = $this->option('user');
        
        if ($userId) {
            $users = User::where('id', $userId)->get();
        } else {
            $users = User::all();
        }

        $defaultApps = ['file-explorer', 'settings', 'calculator', 'email'];
        
        foreach ($users as $user) {
            foreach ($user->allTeams() as $team) {
                $this->setupIconsForUserTeam($user, $team, $defaultApps);
            }
        }

        $this->info('Default desktop icons setup completed.');
    }

    private function setupIconsForUserTeam($user, $team, $defaultApps)
    {
        foreach ($defaultApps as $index => $appId) {
            $app = App::where('app_id', $appId)->first();
            
            if ($app) {
                DesktopIcon::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'team_id' => $team->id,
                        'app_id' => $appId,
                    ],
                    [
                        'name' => $app->name,
                        'icon' => $app->icon,
                        'order' => $index,
                        'visible' => true,
                    ]
                );
            }
        }
    }
}
```

### Step 6.4: Add Meta Tags to Layout

Update your main layout blade file to include meta tags:

```blade
{{-- resources/views/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        
        {{-- Desktop OS Meta Tags --}}
        @auth
            <meta name="user" content="{{ auth()->id() }}">
            <meta name="current-team" content="{{ auth()->user()->current_team_id }}">
        @endauth

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        
        <!-- Font Awesome for icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
```

### Step 6.5: Test the Implementation

1. **Access the desktop**: Navigate to `/desktop` in your browser
2. **Test basic functionality**:
   - Desktop icons should be visible
   - Drag selector should work (click and drag on empty space)
   - Taskbar should be functional
   - Start menu should open
   - Context menus should work
3. **Test mobile responsive**:
   - Resize browser to mobile size
   - Swipe between screens should work
   - Mobile profile bar should be visible

### Step 6.6: Optional Enhancements

Create additional commands for maintenance:

```bash
# Setup desktop icons for all users
php artisan desktop:setup-icons

# Setup for specific user
php artisan desktop:setup-icons --user=1
```

---

## 🎯 Summary

You've now successfully implemented the desktop OS interface in your Laravel application! The implementation includes:

✅ **Complete TypeScript/Vue.js frontend** with drag selector functionality  
✅ **Laravel backend** with proper models, controllers, and API endpoints  
✅ **Database structure** for users, preferences, icons, and notifications  
✅ **Multi-tenancy support** with team-scoped data  
✅ **Mobile responsive design** with 3-screen swipe navigation  
✅ **App ecosystem** with registry, loader, and window management  
✅ **Global state management** and event system  

## 🔄 Next Steps

1. **Customize apps**: Add your specific business applications
2. **Enhance notifications**: Implement real-time notifications
3. **Add file management**: Integrate with Laravel filesystem
4. **Extend widgets**: Create custom dashboard widgets
5. **Theme customization**: Add more wallpapers and themes
6. **Performance optimization**: Implement caching and lazy loading

The desktop OS is now fully functional and ready for your users! 🚀 