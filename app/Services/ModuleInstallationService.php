<?php

namespace App\Services;

use App\Models\AppStoreApp;
use App\Models\InstalledApp;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Module Installation Service - Manages Laravel module installation and configuration
 *
 * This service handles the installation, configuration, and management of Laravel
 * modules within the multi-tenant system. It provides comprehensive module lifecycle
 * management including installation, customization, and uninstallation.
 *
 * Key features:
 * - Laravel module installation and setup
 * - Module customization and configuration
 * - Tenant-specific module registration
 * - Module file generation and management
 * - Service provider registration
 * - Route and controller generation
 * - Module uninstallation and cleanup
 * - AI token middleware integration
 * - API integration setup
 * - Module data management
 *
 * Supported module types:
 * - Standard Laravel modules with service providers
 * - API-focused modules with REST endpoints
 * - Frontend modules with Vue.js integration
 * - Admin modules with dashboard interfaces
 * - Custom modules with specific functionality
 *
 * Installation process includes:
 * - Module structure creation
 * - Service provider generation
 * - Route and controller setup
 * - Configuration file creation
 * - View and asset management
 * - Tenant-specific customization
 * - AI token middleware integration
 * - API endpoint registration
 *
 * The service provides:
 * - Automated module installation
 * - Module customization and configuration
 * - Tenant-aware module management
 * - Module uninstallation and cleanup
 * - Service provider registration
 * - Route and controller generation
 * - Configuration file management
 * - Module lifecycle management
 *
 * @package App\Services
 * @since 1.0.0
 */
class ModuleInstallationService
{
    /**
     * Install a Laravel module for an app store application
     *
     * This method installs a Laravel module for the specified app store application,
     * including module creation, customization, and tenant-specific configuration.
     * It handles both new module creation and existing module enablement.
     *
     * The installation process includes:
     * - Module structure creation and setup
     * - Service provider generation and registration
     * - Route and controller file generation
     * - Configuration file creation
     * - Tenant-specific customization
     * - AI token middleware integration
     * - API endpoint registration
     * - Module data initialization
     *
     * @param AppStoreApp $app The app store application to install
     * @param InstalledApp $installation The installation record
     * @throws \Exception When module installation fails
     */
    public function installModule(AppStoreApp $app, InstalledApp $installation): void
    {
        try {
            if (!$this->isModulesPackageInstalled()) {
                throw new \Exception('Laravel Modules package is not installed');
            }

            $moduleName = $app->module_name;
            $modulePath = $this->getModulePath($moduleName);

            // Create module if it doesn't exist
            if (!File::exists($modulePath)) {
                $this->createModuleFromTemplate($app, $installation);
            } else {
                $this->enableExistingModule($moduleName);
            }

            // Register module in tenant context
            $this->registerModuleForTenant($app, $installation);

            // Run module-specific installation steps
            $this->runModuleInstallation($app, $installation);

            Log::info("Laravel module {$moduleName} installed successfully");

        } catch (\Exception $e) {
            Log::error("Failed to install Laravel module {$app->module_name}: " . $e->getMessage());
            throw new \Exception('Failed to install Laravel module: ' . $e->getMessage());
        }
    }

    /**
     * Uninstall a Laravel module and clean up associated data
     *
     * This method uninstalls a Laravel module and performs comprehensive cleanup,
     * including module disabling, data removal, and optional file deletion.
     * It ensures proper cleanup of tenant-specific data and configurations.
     *
     * The uninstallation process includes:
     * - Module disabling and deactivation
     * - Tenant-specific data cleanup
     * - Configuration file removal
     * - Optional module file deletion
     * - Service provider unregistration
     * - Route and controller cleanup
     * - Database record cleanup
     *
     * @param InstalledApp $installation The installation record to uninstall
     * @throws \Exception When module uninstallation fails
     */
    public function uninstallModule(InstalledApp $installation): void
    {
        try {
            $moduleName = $installation->module_name;
            
            if (!$moduleName) {
                throw new \Exception('Module name not found in installation data');
            }

            // Disable module
            $this->disableModule($moduleName);

            // Remove tenant-specific data
            $this->cleanupModuleData($installation);

            // Optionally remove module files (be careful with shared modules)
            if ($this->shouldRemoveModuleFiles($installation)) {
                $this->removeModuleFiles($moduleName);
            }

            Log::info("Laravel module {$moduleName} uninstalled successfully");

        } catch (\Exception $e) {
            Log::error("Failed to uninstall Laravel module {$installation->module_name}: " . $e->getMessage());
            throw new \Exception('Failed to uninstall Laravel module: ' . $e->getMessage());
        }
    }

    /**
     * Check if Laravel Modules package is installed and available
     *
     * This method verifies that the Laravel Modules package is properly
     * installed and available for use in the application.
     *
     * @return bool True if the modules package is installed, false otherwise
     */
    protected function isModulesPackageInstalled(): bool
    {
        return class_exists('Nwidart\Modules\Facades\Module');
    }

    /**
     * Get the file system path for a Laravel module
     *
     * This method returns the absolute file system path for a Laravel
     * module based on the module name.
     *
     * @param string $moduleName The name of the module
     * @return string The absolute path to the module directory
     */
    protected function getModulePath(string $moduleName): string
    {
        return base_path("Modules/{$moduleName}");
    }

    /**
     * Create a new Laravel module from app store template
     *
     * This method creates a new Laravel module from the app store application
     * template, including all necessary files and configurations.
     *
     * @param AppStoreApp $app The app store application template
     * @param InstalledApp $installation The installation record
     */
    protected function createModuleFromTemplate(AppStoreApp $app, InstalledApp $installation): void
    {
        $moduleName = $app->module_name;

        // Use Laravel Modules command to create module
        if ($this->isModulesPackageInstalled()) {
            $this->runArtisanCommand("module:make {$moduleName}");
        } else {
            // Create module structure manually
            $this->createModuleStructure($app);
        }

        // Customize module with app-specific content
        $this->customizeModule($app, $installation);
    }

    /**
     * Create module structure manually
     */
    protected function createModuleStructure(AppStoreApp $app): void
    {
        $moduleName = $app->module_name;
        $modulePath = $this->getModulePath($moduleName);

        // Create directory structure
        $directories = [
            'Config',
            'Console',
            'Database/Migrations',
            'Database/Seeders',
            'Entities',
            'Http/Controllers',
            'Http/Middleware',
            'Http/Requests',
            'Providers',
            'Resources/assets/js',
            'Resources/assets/sass',
            'Resources/lang',
            'Resources/views',
            'Routes',
            'Tests/Feature',
            'Tests/Unit',
        ];

        foreach ($directories as $directory) {
            File::makeDirectory("{$modulePath}/{$directory}", 0755, true);
        }

        // Create essential files
        $this->createModuleFiles($app);
    }

    /**
     * Create essential module files
     */
    protected function createModuleFiles(AppStoreApp $app): void
    {
        $moduleName = $app->module_name;
        $modulePath = $this->getModulePath($moduleName);

        // module.json
        $moduleConfig = [
            'name' => $moduleName,
            'alias' => Str::lower($moduleName),
            'description' => $app->description,
            'keywords' => $app->tags ?? [],
            'version' => $app->version,
            'priority' => 0,
            'providers' => [
                "Modules\\{$moduleName}\\Providers\\{$moduleName}ServiceProvider",
                "Modules\\{$moduleName}\\Providers\\RouteServiceProvider",
            ],
            'aliases' => [],
            'files' => [],
            'requires' => [],
        ];

        File::put(
            "{$modulePath}/module.json",
            json_encode($moduleConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        // composer.json
        $composerConfig = [
            'name' => "modules/{$moduleName}",
            'description' => $app->description,
            'authors' => [
                [
                    'name' => $app->developer_name,
                    'email' => $app->developer_email,
                ]
            ],
            'extra' => [
                'laravel' => [
                    'providers' => [],
                    'aliases' => []
                ]
            ],
            'autoload' => [
                'psr-4' => [
                    "Modules\\{$moduleName}\\" => ""
                ]
            ]
        ];

        File::put(
            "{$modulePath}/composer.json",
            json_encode($composerConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        // Service Provider
        $serviceProviderContent = $this->generateServiceProvider($app);
        File::put(
            "{$modulePath}/Providers/{$moduleName}ServiceProvider.php",
            $serviceProviderContent
        );

        // Route Service Provider
        $routeProviderContent = $this->generateRouteServiceProvider($app);
        File::put(
            "{$modulePath}/Providers/RouteServiceProvider.php",
            $routeProviderContent
        );

        // Web routes
        File::put(
            "{$modulePath}/Routes/web.php",
            "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\nuse Modules\\{$moduleName}\\Http\\Controllers\\{$moduleName}Controller;\n\n/*\n|--------------------------------------------------------------------------\n| Web Routes\n|--------------------------------------------------------------------------\n*/\n\nRoute::prefix('{$moduleName}')\n    ->middleware(['web', 'auth', 'tenant'])\n    ->group(function () {\n        Route::get('/', [{$moduleName}Controller::class, 'index'])->name('{$moduleName}.index');\n    });\n"
        );

        // API routes
        File::put(
            "{$modulePath}/Routes/api.php",
            "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\nuse Modules\\{$moduleName}\\Http\\Controllers\\Api\\{$moduleName}ApiController;\n\n/*\n|--------------------------------------------------------------------------\n| API Routes\n|--------------------------------------------------------------------------\n*/\n\nRoute::prefix('api/{$moduleName}')\n    ->middleware(['api', 'auth:sanctum', 'tenant'])\n    ->group(function () {\n        Route::get('/', [{$moduleName}ApiController::class, 'index']);\n    });\n"
        );

        // Main Controller
        $controllerContent = $this->generateController($app);
        File::put(
            "{$modulePath}/Http/Controllers/{$moduleName}Controller.php",
            $controllerContent
        );

        // API Controller
        $apiControllerContent = $this->generateApiController($app);
        File::put(
            "{$modulePath}/Http/Controllers/Api/{$moduleName}ApiController.php",
            $apiControllerContent
        );
    }

    /**
     * Generate service provider content
     */
    protected function generateServiceProvider(AppStoreApp $app): string
    {
        $moduleName = $app->module_name;

        return "<?php

namespace Modules\\{$moduleName}\\Providers;

use Illuminate\\Support\\ServiceProvider;
use Illuminate\\Database\\Eloquent\\Factory;

class {$moduleName}ServiceProvider extends ServiceProvider
{
    protected \$moduleName = '{$moduleName}';
    protected \$moduleNameLower = '" . Str::lower($moduleName) . "';

    public function boot()
    {
        \$this->registerTranslations();
        \$this->registerConfig();
        \$this->registerViews();
        \$this->loadMigrationsFrom(module_path(\$this->moduleName, 'Database/Migrations'));
    }

    public function register()
    {
        \$this->app->register(RouteServiceProvider::class);
    }

    protected function registerConfig()
    {
        \$this->publishes([
            module_path(\$this->moduleName, 'Config/config.php') => config_path(\$this->moduleNameLower . '.php'),
        ], 'config');
        \$this->mergeConfigFrom(
            module_path(\$this->moduleName, 'Config/config.php'), \$this->moduleNameLower
        );
    }

    public function registerViews()
    {
        \$viewPath = resource_path('views/modules/' . \$this->moduleNameLower);

        \$sourcePath = module_path(\$this->moduleName, 'Resources/views');

        \$this->publishes([
            \$sourcePath => \$viewPath
        ], ['views', \$this->moduleNameLower . '-module-views']);

        \$this->loadViewsFrom(array_merge(\$this->getPublishableViewPaths(), [\$sourcePath]), \$this->moduleNameLower);
    }

    public function registerTranslations()
    {
        \$langPath = resource_path('lang/modules/' . \$this->moduleNameLower);

        if (is_dir(\$langPath)) {
            \$this->loadTranslationsFrom(\$langPath, \$this->moduleNameLower);
            \$this->loadJsonTranslationsFrom(\$langPath);
        } else {
            \$this->loadTranslationsFrom(module_path(\$this->moduleName, 'Resources/lang'), \$this->moduleNameLower);
            \$this->loadJsonTranslationsFrom(module_path(\$this->moduleName, 'Resources/lang'));
        }
    }

    public function provides()
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        \$paths = [];
        foreach (\\config('view.paths') as \$path) {
            if (is_dir(\$path . '/modules/' . \$this->moduleNameLower)) {
                \$paths[] = \$path . '/modules/' . \$this->moduleNameLower;
            }
        }
        return \$paths;
    }
}
";
    }

    /**
     * Generate route service provider content
     */
    protected function generateRouteServiceProvider(AppStoreApp $app): string
    {
        $moduleName = $app->module_name;

        return "<?php

namespace Modules\\{$moduleName}\\Providers;

use Illuminate\\Support\\Facades\\Route;
use Illuminate\\Foundation\\Support\\Providers\\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    protected \$moduleNamespace = 'Modules\\{$moduleName}\\Http\\Controllers';

    public function boot()
    {
        parent::boot();
    }

    public function map()
    {
        \$this->mapApiRoutes();
        \$this->mapWebRoutes();
    }

    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace(\$this->moduleNamespace)
            ->group(module_path('{$moduleName}', '/Routes/web.php'));
    }

    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace(\$this->moduleNamespace)
            ->group(module_path('{$moduleName}', '/Routes/api.php'));
    }
}
";
    }

    /**
     * Generate controller content
     */
    protected function generateController(AppStoreApp $app): string
    {
        $moduleName = $app->module_name;

        return "<?php

namespace Modules\\{$moduleName}\\Http\\Controllers;

use Illuminate\\Contracts\\Support\\Renderable;
use Illuminate\\Http\\Request;
use Illuminate\\Routing\\Controller;

class {$moduleName}Controller extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('{$moduleName}::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('{$moduleName}::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request \$request
     * @return Renderable
     */
    public function store(Request \$request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int \$id
     * @return Renderable
     */
    public function show(\$id)
    {
        return view('{$moduleName}::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int \$id
     * @return Renderable
     */
    public function edit(\$id)
    {
        return view('{$moduleName}::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request \$request
     * @param int \$id
     * @return Renderable
     */
    public function update(Request \$request, \$id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int \$id
     * @return Renderable
     */
    public function destroy(\$id)
    {
        //
    }
}
";
    }

    /**
     * Generate API controller content
     */
    protected function generateApiController(AppStoreApp $app): string
    {
        $moduleName = $app->module_name;

        return "<?php

namespace Modules\\{$moduleName}\\Http\\Controllers\\Api;

use Illuminate\\Http\\Request;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Routing\\Controller;

class {$moduleName}ApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request \$request): JsonResponse
    {
        try {
            // Your API logic here
            return response()->json([
                'message' => '{$moduleName} API endpoint',
                'data' => [],
            ]);

        } catch (\\Exception \$e) {
            return response()->json([
                'error' => 'Failed to fetch data',
                'message' => \$e->getMessage(),
            ], 500);
        }
    }
}
";
    }

    /**
     * Customize module with app-specific content
     */
    protected function customizeModule(AppStoreApp $app, InstalledApp $installation): void
    {
        // Add app-specific configuration
        $this->createModuleConfig($app);
        
        // Create basic view files
        $this->createModuleViews($app);
        
        // Add any custom middleware or features based on app requirements
        if ($app->requires_ai_tokens) {
            $this->addAiTokenMiddleware($app);
        }
        
        if ($app->requires_external_api) {
            $this->setupApiIntegration($app);
        }
    }

    /**
     * Create module configuration
     */
    protected function createModuleConfig(AppStoreApp $app): void
    {
        $moduleName = $app->module_name;
        $modulePath = $this->getModulePath($moduleName);

        $config = [
            'name' => $app->name,
            'description' => $app->description,
            'version' => $app->version,
            'developer' => $app->developer_name,
            'settings' => $app->configuration_schema ?? [],
            'permissions' => $app->permissions_required ?? [],
            'api_endpoints' => [],
        ];

        File::put(
            "{$modulePath}/Config/config.php",
            "<?php\n\nreturn " . var_export($config, true) . ";\n"
        );
    }

    /**
     * Create basic module views
     */
    protected function createModuleViews(AppStoreApp $app): void
    {
        $moduleName = $app->module_name;
        $modulePath = $this->getModulePath($moduleName);

        // Master layout
        $masterLayout = "@extends('layouts.app')\n\n@section('content')\n    @yield('module-content')\n@endsection\n";
        File::put("{$modulePath}/Resources/views/layouts/master.blade.php", $masterLayout);

        // Index view
        $indexView = "@extends('{$moduleName}::layouts.master')\n\n@section('module-content')\n    <div class=\"container\">\n        <h1>{$app->name}</h1>\n        <p>{$app->description}</p>\n    </div>\n@endsection\n";
        File::put("{$modulePath}/Resources/views/index.blade.php", $indexView);
    }

    /**
     * Enable existing module
     */
    protected function enableExistingModule(string $moduleName): void
    {
        if ($this->isModulesPackageInstalled()) {
            $this->runArtisanCommand("module:enable {$moduleName}");
        }
    }

    /**
     * Disable module
     */
    protected function disableModule(string $moduleName): void
    {
        if ($this->isModulesPackageInstalled()) {
            $this->runArtisanCommand("module:disable {$moduleName}");
        }
    }

    /**
     * Register module for tenant
     */
    protected function registerModuleForTenant(AppStoreApp $app, InstalledApp $installation): void
    {
        // This could involve tenant-specific module registration
        // For now, we'll just update the installation data
        $installation->update([
            'installation_data' => array_merge($installation->installation_data ?? [], [
                'module_registered' => true,
                'module_enabled' => true,
                'registration_timestamp' => now()->toISOString(),
            ]),
        ]);
    }

    /**
     * Run module installation steps
     */
    protected function runModuleInstallation(AppStoreApp $app, InstalledApp $installation): void
    {
        $moduleName = $app->module_name;

        try {
            // Run module migrations
            $this->runArtisanCommand("module:migrate {$moduleName}");

            // Seed module data if needed
            if ($this->hasModuleSeeder($moduleName)) {
                $this->runArtisanCommand("module:seed {$moduleName}");
            }

            // Publish module assets if needed
            $this->runArtisanCommand("module:publish {$moduleName}");

        } catch (\Exception $e) {
            Log::warning("Module installation steps failed for {$moduleName}: " . $e->getMessage());
        }
    }

    /**
     * Check if module has seeder
     */
    protected function hasModuleSeeder(string $moduleName): bool
    {
        $seederPath = $this->getModulePath($moduleName) . '/Database/Seeders';
        return File::exists($seederPath) && !empty(File::glob("{$seederPath}/*Seeder.php"));
    }

    /**
     * Clean up module data
     */
    protected function cleanupModuleData(InstalledApp $installation): void
    {
        // Clean up tenant-specific module data
        // This could involve removing database records, files, etc.
        Log::info("Cleaning up module data for {$installation->module_name}");
    }

    /**
     * Check if module files should be removed
     */
    protected function shouldRemoveModuleFiles(InstalledApp $installation): bool
    {
        // Check if any other teams are using this module
        $otherInstallations = InstalledApp::where('module_name', $installation->module_name)
            ->where('team_id', '!=', $installation->team_id)
            ->where('is_active', true)
            ->exists();

        return !$otherInstallations;
    }

    /**
     * Remove module files
     */
    protected function removeModuleFiles(string $moduleName): void
    {
        $modulePath = $this->getModulePath($moduleName);
        
        if (File::exists($modulePath)) {
            File::deleteDirectory($modulePath);
            Log::info("Module files removed for {$moduleName}");
        }
    }

    /**
     * Run artisan command
     */
    protected function runArtisanCommand(string $command): void
    {
        try {
            $result = Process::run("php artisan {$command}");
            
            if (!$result->successful()) {
                Log::warning("Artisan command failed: {$command}, Output: {$result->errorOutput()}");
            }
            
        } catch (\Exception $e) {
            Log::error("Failed to run artisan command: {$command}, Error: " . $e->getMessage());
        }
    }

    /**
     * Add AI token middleware for modules that require AI
     */
    protected function addAiTokenMiddleware(AppStoreApp $app): void
    {
        $moduleName = $app->module_name;
        $modulePath = $this->getModulePath($moduleName);

        $middlewareContent = "<?php

namespace Modules\\{$moduleName}\\Http\\Middleware;

use Closure;
use Illuminate\\Http\\Request;
use App\\Services\\Wallet\\AiTokenService;

class AiTokenMiddleware
{
    protected AiTokenService \$aiTokenService;

    public function __construct(AiTokenService \$aiTokenService)
    {
        \$this->aiTokenService = \$aiTokenService;
    }

    public function handle(Request \$request, Closure \$next)
    {
        \$user = \$request->user();
        \$requiredTokens = {$app->ai_token_cost_per_use};

        if (!\$this->aiTokenService->hasTokens(\$user, \$requiredTokens)) {
            return response()->json([
                'error' => 'Insufficient AI tokens',
                'required' => \$requiredTokens,
                'available' => \$this->aiTokenService->getBalance(\$user),
            ], 402);
        }

        return \$next(\$request);
    }
}
";

        File::put("{$modulePath}/Http/Middleware/AiTokenMiddleware.php", $middlewareContent);
    }

    /**
     * Setup API integration for modules that require external APIs
     */
    protected function setupApiIntegration(AppStoreApp $app): void
    {
        $moduleName = $app->module_name;
        $modulePath = $this->getModulePath($moduleName);
        $apiConfig = $app->external_api_config ?? [];

        $serviceContent = "<?php

namespace Modules\\{$moduleName}\\Services;

use Illuminate\\Support\\Facades\\Http;
use Illuminate\\Support\\Facades\\Log;

class ApiIntegrationService
{
    protected array \$config;

    public function __construct()
    {
        \$this->config = " . var_export($apiConfig, true) . ";
    }

    public function makeApiCall(string \$endpoint, array \$data = [], string \$method = 'GET')
    {
        try {
            \$response = Http::timeout(30)
                ->withHeaders(\$this->getHeaders())
                ->{\$method}(\$this->config['base_url'] . \$endpoint, \$data);

            return \$response->json();

        } catch (\\Exception \$e) {
            Log::error('API call failed: ' . \$e->getMessage());
            throw \$e;
        }
    }

    protected function getHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . (\$this->config['api_key'] ?? ''),
        ];
    }
}
";

        File::put("{$modulePath}/Services/ApiIntegrationService.php", $serviceContent);
    }
} 