<?php

namespace App\Services;

use App\Models\AppStoreApp;
use App\Models\AppStorePurchase;
use App\Models\InstalledApp;
use App\Models\User;
use App\Models\Team;
use App\Services\Wallet\PaymentProviderService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * App Store Service - Manages app store operations and installations with tenant isolation
 *
 * This service handles all app store operations including purchases, installations,
 * WordPress plugin management, and payment processing. It provides comprehensive
 * tenant isolation and supports multiple app types and installation methods.
 *
 * Key features:
 * - App purchase and payment processing
 * - Multi-type app installation (iframe, external, WordPress, Laravel modules)
 * - WordPress plugin management
 * - Payment intent creation and processing
 * - Tenant-specific app URL generation
 * - App data initialization and configuration
 * - Installation validation and conflict resolution
 *
 * Supported app types:
 * - iframe: Embedded iframe applications
 * - external: External API integrations
 * - wordpress_plugin: WordPress plugin installations
 * - laravel_module: Laravel module installations
 * - api_integration: API-based integrations
 *
 * The service ensures:
 * - Proper tenant isolation for all operations
 * - Payment validation and processing
 * - Installation conflict resolution
 * - WordPress plugin management
 * - Comprehensive error handling and logging
 * - Multi-app type support
 * - Security token generation for iframe apps
 *
 * @package App\Services
 * @since 1.0.0
 */
class AppStoreService
{
    /** @var PaymentProviderService Service for payment processing and validation */
    protected PaymentProviderService $paymentService;

    /**
     * Initialize the App Store Service with payment provider
     *
     * @param PaymentProviderService $paymentService Service for payment operations
     */
    public function __construct(PaymentProviderService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Create a purchase record for an app with payment tracking
     *
     * This method creates a purchase record for an app, handling both one-time
     * purchases and subscriptions. It tracks payment status and metadata for
     * billing and installation purposes.
     *
     * The method supports:
     * - One-time purchases and subscriptions
     * - Payment method tracking
     * - Metadata storage for app details
     * - Subscription date management
     * - Trial period handling
     *
     * @param AppStoreApp $app The app to create purchase for
     * @param User $user The user making the purchase
     * @param Team $team The team for the purchase
     * @param array $options Additional purchase options
     * @return AppStorePurchase The created purchase record
     */
    public function createPurchase(AppStoreApp $app, User $user, Team $team, array $options = []): AppStorePurchase
    {
        $purchaseType = $app->isSubscription() ? 'subscription' : 'one_time';
        $amount = $app->isSubscription() ? $app->monthly_price : $app->price;

        $purchase = AppStorePurchase::create([
            'app_store_app_id' => $app->id,
            'user_id' => $user->id,
            'team_id' => $team->id,
            'purchase_type' => $purchaseType,
            'amount' => $amount,
            'currency' => $app->currency,
            'payment_method' => $options['payment_method'] ?? 'stripe',
            'payment_provider' => 'stripe',
            'status' => 'pending',
            'metadata' => [
                'app_name' => $app->name,
                'app_version' => $app->version,
                'subscription_type' => $options['subscription_type'] ?? null,
            ],
        ]);

        if ($app->isSubscription()) {
            $purchase->update([
                'subscription_start_date' => now(),
                'subscription_end_date' => now()->addMonth(),
                'is_trial' => false,
            ]);
        }

        return $purchase;
    }

    /**
     * Create payment intent for a purchase with Stripe integration
     *
     * This method creates a payment intent through the payment provider service,
     * enabling secure payment processing for app purchases. It handles currency
     * conversion and metadata tracking.
     *
     * @param AppStorePurchase $purchase The purchase to create payment intent for
     * @return array Payment intent data from payment provider
     * @throws \Exception When payment intent creation fails
     */
    public function createPaymentIntent(AppStorePurchase $purchase): array
    {
        try {
            $paymentIntent = $this->paymentService->createPaymentIntent([
                'amount' => $purchase->amount * 100, // Convert to cents
                'currency' => strtolower($purchase->currency),
                'description' => "Purchase of {$purchase->app->name}",
                'metadata' => [
                    'purchase_id' => $purchase->id,
                    'app_id' => $purchase->app_store_app_id,
                    'team_id' => $purchase->team_id,
                    'user_id' => $purchase->user_id,
                ],
            ]);

            $purchase->update([
                'payment_intent_id' => $paymentIntent['id'],
                'stripe_payment_intent_id' => $paymentIntent['id'],
            ]);

            return $paymentIntent;

        } catch (\Exception $e) {
            Log::error('Failed to create payment intent: ' . $e->getMessage());
            throw new \Exception('Failed to create payment intent');
        }
    }

    /**
     * Install an app for a team with comprehensive validation and setup
     *
     * This method handles the complete app installation process including
     * validation, conflict resolution, and app-specific initialization.
     * It supports multiple app types and ensures proper tenant isolation.
     *
     * The installation process includes:
     * - Existing installation checking and reactivation
     * - Purchase validation for paid apps
     * - Installation record creation
     * - App-specific data initialization
     * - URL generation for different app types
     * - Permission and configuration setup
     *
     * @param AppStoreApp $app The app to install
     * @param User $user The user performing the installation
     * @param Team $team The team to install the app for
     * @return InstalledApp The created installation record
     * @throws \Exception When installation fails or app is already installed
     */
    public function installApp(AppStoreApp $app, User $user, Team $team): InstalledApp
    {
        // Check if already installed
        $existingInstallation = InstalledApp::where('team_id', $team->id)
            ->where('app_id', $app->slug)
            ->first();

        if ($existingInstallation) {
            if ($existingInstallation->is_active) {
                throw new \Exception('App is already installed');
            } else {
                // Reactivate existing installation
                $existingInstallation->update([
                    'is_active' => true,
                    'version' => $app->version,
                    'last_used_at' => now(),
                ]);
                return $existingInstallation;
            }
        }

        // Get purchase if app is paid
        $purchase = null;
        if (!$app->isFree()) {
            $purchase = $app->purchases()
                ->where('user_id', $user->id)
                ->where('team_id', $team->id)
                ->where('status', 'completed')
                ->first();

            if (!$purchase) {
                throw new \Exception('App must be purchased before installation');
            }
        }

        // Create installation record
        $installation = InstalledApp::create([
            'team_id' => $team->id,
            'app_id' => $app->slug,
            'app_store_app_id' => $app->id,
            'app_name' => $app->name,
            'app_type' => $app->app_type,
            'module_name' => $app->module_name,
            'icon' => $app->icon,
            'url' => $this->generateAppUrl($app, $team),
            'iframe_config' => $app->iframe_config ?? InstalledApp::getDefaultIframeConfig(),
            'installed_by' => $user->id,
            'is_active' => true,
            'version' => $app->version,
            'permissions' => $app->permissions_required ?? [],
            'installation_data' => [
                'installed_at' => now()->toISOString(),
                'installation_method' => 'app_store',
                'app_store_app_id' => $app->id,
            ],
            'purchase_id' => $purchase?->id,
            'last_used_at' => now(),
        ]);

        // Initialize app-specific data or configuration
        $this->initializeAppData($app, $installation, $team);

        return $installation;
    }

    /**
     * Install a WordPress plugin for a team with file system management
     *
     * This method handles WordPress plugin installation including directory
     * creation, plugin download, and file system management. It ensures
     * proper plugin structure and activation tracking.
     *
     * @param AppStoreApp $app The WordPress plugin app to install
     * @param InstalledApp $installation The installation record
     * @throws \Exception When WordPress plugin installation fails
     */
    public function installWordPressPlugin(AppStoreApp $app, InstalledApp $installation): void
    {
        try {
            $team = $installation->team;
            $wordpressPath = storage_path("app/tenants/{$team->id}/wordpress");

            // Ensure WordPress directory exists
            if (!File::exists($wordpressPath)) {
                File::makeDirectory($wordpressPath, 0755, true);
            }

            $pluginsPath = $wordpressPath . '/wp-content/plugins';
            if (!File::exists($pluginsPath)) {
                File::makeDirectory($pluginsPath, 0755, true);
            }

            // Download and extract plugin
            $this->downloadAndExtractPlugin($app, $pluginsPath);

            // Update installation data
            $installation->update([
                'installation_data' => array_merge($installation->installation_data ?? [], [
                    'wordpress_plugin_path' => $pluginsPath . '/' . $app->module_name,
                    'plugin_activated' => false,
                    'wordpress_integration' => true,
                ]),
            ]);

            Log::info("WordPress plugin {$app->name} installed for team {$team->id}");

        } catch (\Exception $e) {
            Log::error("Failed to install WordPress plugin {$app->name}: " . $e->getMessage());
            throw new \Exception('Failed to install WordPress plugin');
        }
    }

    /**
     * Uninstall a WordPress plugin with file system cleanup
     *
     * This method handles WordPress plugin uninstallation including file
     * system cleanup and installation data updates.
     *
     * @param InstalledApp $installation The installation to uninstall
     * @throws \Exception When WordPress plugin uninstallation fails
     */
    public function uninstallWordPressPlugin(InstalledApp $installation): void
    {
        try {
            $pluginPath = $installation->installation_data['wordpress_plugin_path'] ?? null;
            
            if ($pluginPath && File::exists($pluginPath)) {
                File::deleteDirectory($pluginPath);
            }

            Log::info("WordPress plugin {$installation->app_name} uninstalled");

        } catch (\Exception $e) {
            Log::error("Failed to uninstall WordPress plugin {$installation->app_name}: " . $e->getMessage());
            throw new \Exception('Failed to uninstall WordPress plugin');
        }
    }

    /**
     * Generate app URL for different app types with tenant-specific configuration
     *
     * This method generates appropriate URLs for different app types,
     * including tenant-specific domain configuration and app-specific
     * URL patterns.
     *
     * @param AppStoreApp $app The app to generate URL for
     * @param Team $team The team for tenant-specific URL generation
     * @return string|null The generated app URL or null if not applicable
     */
    protected function generateAppUrl(AppStoreApp $app, Team $team): ?string
    {
        switch ($app->app_type) {
            case 'iframe':
                return $app->iframe_config['url'] ?? null;
            
            case 'external':
                return $app->external_api_config['url'] ?? null;
            
            case 'wordpress_plugin':
                $tenantDomain = $team->domains()->where('is_primary', true)->first()?->domain;
                return $tenantDomain ? "https://{$tenantDomain}/wp-admin/admin.php?page={$app->module_name}" : null;
            
            case 'laravel_module':
                $tenantDomain = $team->domains()->where('is_primary', true)->first()?->domain;
                return $tenantDomain ? "https://{$tenantDomain}/modules/{$app->module_name}" : null;
            
            default:
                return null;
        }
    }

    /**
     * Initialize app-specific data and configuration based on app type
     *
     * This method handles app-specific initialization including module
     * configuration, API integration setup, and iframe app configuration.
     *
     * @param AppStoreApp $app The app to initialize
     * @param InstalledApp $installation The installation record
     * @param Team $team The team for tenant-specific configuration
     */
    protected function initializeAppData(AppStoreApp $app, InstalledApp $installation, Team $team): void
    {
        switch ($app->app_type) {
            case 'laravel_module':
                $this->initializeModuleData($app, $installation, $team);
                break;
            
            case 'api_integration':
                $this->initializeApiIntegration($app, $installation, $team);
                break;
            
            case 'iframe':
                $this->initializeIframeApp($app, $installation, $team);
                break;
        }
    }

    /**
     * Initialize Laravel module data with service provider configuration
     *
     * This method sets up Laravel module configuration including service
     * providers, namespaces, and module-specific settings.
     *
     * @param AppStoreApp $app The Laravel module app
     * @param InstalledApp $installation The installation record
     * @param Team $team The team for module configuration
     */
    protected function initializeModuleData(AppStoreApp $app, InstalledApp $installation, Team $team): void
    {
        $moduleConfig = [
            'module_name' => $app->module_name,
            'module_namespace' => "Modules\\{$app->module_name}",
            'enabled' => true,
            'auto_discovery' => true,
            'providers' => [
                "Modules\\{$app->module_name}\\Providers\\{$app->module_name}ServiceProvider",
            ],
        ];

        $installation->update([
            'installation_data' => array_merge($installation->installation_data ?? [], [
                'module_config' => $moduleConfig,
                'module_enabled' => true,
            ]),
        ]);
    }

    /**
     * Initialize API integration with API keys and webhook endpoints
     *
     * This method sets up API integration configuration including API keys,
     * webhook endpoints, and external API configuration.
     *
     * @param AppStoreApp $app The API integration app
     * @param InstalledApp $installation The installation record
     * @param Team $team The team for API configuration
     */
    protected function initializeApiIntegration(AppStoreApp $app, InstalledApp $installation, Team $team): void
    {
        $apiConfig = $app->external_api_config ?? [];
        
        $installation->update([
            'installation_data' => array_merge($installation->installation_data ?? [], [
                'api_config' => $apiConfig,
                'api_key' => $app->generateApiKey(),
                'webhook_endpoints' => $apiConfig['webhook_endpoints'] ?? [],
            ]),
        ]);
    }

    /**
     * Initialize iframe app with security tokens and tenant-specific configuration
     *
     * This method sets up iframe app configuration including security tokens,
     * tenant-specific URL parameters, and iframe-specific settings.
     *
     * @param AppStoreApp $app The iframe app
     * @param InstalledApp $installation The installation record
     * @param Team $team The team for iframe configuration
     */
    protected function initializeIframeApp(AppStoreApp $app, InstalledApp $installation, Team $team): void
    {
        $iframeConfig = $app->iframe_config ?? InstalledApp::getDefaultIframeConfig();
        
        // Add team-specific iframe parameters
        if (isset($iframeConfig['url'])) {
            $iframeConfig['url'] = str_replace(
                ['{team_id}', '{tenant_id}'],
                [$team->id, tenant('id')],
                $iframeConfig['url']
            );
        }

        $installation->update([
            'iframe_config' => $iframeConfig,
            'installation_data' => array_merge($installation->installation_data ?? [], [
                'iframe_initialized' => true,
                'security_token' => Str::random(32),
            ]),
        ]);
    }

    /**
     * Download and extract a WordPress plugin with file system management
     *
     * This method handles WordPress plugin download and extraction,
     * creating the necessary file structure and plugin files.
     *
     * @param AppStoreApp $app The WordPress plugin app
     * @param string $extractPath The path to extract the plugin to
     * @throws \Exception When plugin download or extraction fails
     */
    protected function downloadAndExtractPlugin(AppStoreApp $app, string $extractPath): void
    {
        if (!$app->repository_url) {
            throw new \Exception('No repository URL provided for plugin download');
        }

        // This would typically download from a repository
        // For now, we'll create a placeholder structure
        $pluginPath = $extractPath . '/' . $app->module_name;
        
        if (!File::exists($pluginPath)) {
            File::makeDirectory($pluginPath, 0755, true);
        }

        // Create a basic plugin file
        $pluginContent = $this->generateWordPressPluginFile($app);
        File::put($pluginPath . '/' . $app->module_name . '.php', $pluginContent);
    }

    /**
     * Generate WordPress plugin file content with app metadata
     *
     * This method generates the main WordPress plugin file with proper
     * plugin headers and initialization code.
     *
     * @param AppStoreApp $app The WordPress plugin app
     * @return string The generated plugin file content
     */
    protected function generateWordPressPluginFile(AppStoreApp $app): string
    {
        return "<?php
/*
Plugin Name: {$app->name}
Description: {$app->description}
Version: {$app->version}
Author: {$app->developer_name}
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin initialization code would go here
add_action('init', function() {
    // Initialize {$app->name} plugin
});
";
    }

    /**
     * Process completed payment and handle app installation
     *
     * This method handles completed payment processing, updating purchase
     * status and automatically installing free apps or marking paid apps
     * as ready for installation.
     *
     * @param string $paymentIntentId The payment intent ID from payment provider
     */
    public function processCompletedPayment(string $paymentIntentId): void
    {
        $purchase = AppStorePurchase::where('payment_intent_id', $paymentIntentId)->first();
        
        if (!$purchase) {
            Log::warning("Purchase not found for payment intent: {$paymentIntentId}");
            return;
        }

        $purchase->markAsCompleted();

        // Auto-install free apps or mark paid apps as ready for installation
        if ($purchase->app->isFree()) {
            try {
                $this->installApp($purchase->app, $purchase->user, $purchase->team);
            } catch (\Exception $e) {
                Log::error("Auto-install failed for free app {$purchase->app->name}: " . $e->getMessage());
            }
        }

        Log::info("Payment completed for purchase {$purchase->id}");
    }

    /**
     * Handle failed payment and update purchase status
     *
     * This method handles failed payment processing, updating purchase
     * status and logging the failure for administrative review.
     *
     * @param string $paymentIntentId The payment intent ID from payment provider
     */
    public function processFailedPayment(string $paymentIntentId): void
    {
        $purchase = AppStorePurchase::where('payment_intent_id', $paymentIntentId)->first();
        
        if (!$purchase) {
            Log::warning("Purchase not found for failed payment intent: {$paymentIntentId}");
            return;
        }

        $purchase->markAsFailed();
        Log::info("Payment failed for purchase {$purchase->id}");
    }

    /**
     * Check if Laravel Modules package is available for module installations
     *
     * This method verifies that the Laravel Modules package is installed
     * and available for Laravel module installations.
     *
     * @return bool True if Laravel Modules is available, false otherwise
     */
    public function isLaravelModulesReady(): bool
    {
        return class_exists('Nwidart\Modules\ModulesServiceProvider');
    }

    /**
     * Get module configuration template for Laravel module installations
     *
     * This method provides a template for Laravel module configuration
     * including providers, aliases, and module metadata.
     *
     * @return array Module configuration template
     */
    public function getModuleConfigTemplate(): array
    {
        return [
            'name' => '',
            'alias' => '',
            'description' => '',
            'keywords' => [],
            'priority' => 0,
            'providers' => [],
            'aliases' => [],
            'files' => [],
            'requires' => [],
        ];
    }
} 