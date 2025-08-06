<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Installed App Model - Manages installed applications within tenant teams
 *
 * This model represents applications that have been installed and configured
 * within specific tenant teams. It tracks installation details, user preferences,
 * desktop positioning, and access permissions for each installed application.
 *
 * Key features:
 * - Team-specific app installations with tenant isolation
 * - Desktop positioning and visibility management
 * - Taskbar pinning and user preferences
 * - Permission management and access control
 * - Purchase tracking for paid applications
 * - Usage tracking and last accessed timestamps
 * - Iframe configuration for embedded applications
 * - Version management and update tracking
 *
 * Supported app types:
 * - vue: Vue.js components with desktop integration
 * - iframe: Embedded iframe applications with security policies
 * - laravel_module: Laravel modules with service providers
 * - wordpress_plugin: WordPress plugin installations
 * - external: External link applications
 * - system: System-level applications
 * - api_integration: API-based integrations
 *
 * The model provides:
 * - Installation tracking and management
 * - Desktop UI positioning and customization
 * - Permission-based access control
 * - Purchase validation for paid apps
 * - Usage analytics and tracking
 * - Security configuration for iframe apps
 *
 * @package App\Models
 * @since 1.0.0
 */
class InstalledApp extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'app_id',
        'app_store_app_id',
        'app_name',
        'app_type',
        'module_name',
        'icon',
        'url',
        'iframe_config',
        'installed_by',
        'is_active',
        'position_x',
        'position_y',
        'desktop_visible',
        'pinned_to_taskbar',
        'permissions',
        'version',
        'last_used_at',
        'installation_data',
        'purchase_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'iframe_config' => 'array',
        'is_active' => 'boolean',
        'desktop_visible' => 'boolean',
        'pinned_to_taskbar' => 'boolean',
        'permissions' => 'array',
        'last_used_at' => 'datetime',
        'installation_data' => 'array',
    ];

    /**
     * Get the team that owns this installed app
     *
     * This relationship provides access to the team context for the
     * installed app, enabling team-based access control and management.
     *
     * @return BelongsTo Relationship to Team model
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the user who installed this app
     *
     * This relationship provides access to the user who performed
     * the installation, useful for audit trails and support.
     *
     * @return BelongsTo Relationship to User model (installer)
     */
    public function installedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'installed_by');
    }

    /**
     * Get the app store app that this installation is based on
     *
     * This relationship provides access to the original app store
     * application, including metadata, updates, and version information.
     *
     * @return BelongsTo Relationship to AppStoreApp model
     */
    public function appStoreApp(): BelongsTo
    {
        return $this->belongsTo(AppStoreApp::class, 'app_store_app_id');
    }

    /**
     * Get the purchase record for this app installation
     *
     * This relationship provides access to the purchase record
     * for paid applications, enabling license validation and billing.
     *
     * @return BelongsTo Relationship to AppStorePurchase model
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(AppStorePurchase::class, 'purchase_id');
    }

    /**
     * Get available app types for installation
     *
     * This method provides a list of supported app types that can
     * be installed, including their display names for UI presentation.
     *
     * @return array Associative array of app type keys and display names
     */
    public static function getAppTypes(): array
    {
        return [
            'vue' => 'Vue Component',
            'iframe' => 'Iframe Application',
            'laravel_module' => 'Laravel Module',
            'wordpress_plugin' => 'WordPress Plugin',
            'external' => 'External Link',
            'system' => 'System Application',
            'api_integration' => 'API Integration',
        ];
    }

    /**
     * Check if the app is iframe-based
     *
     * This method determines whether the installed app is an iframe
     * application, which requires special security configuration and
     * sandboxing for safe execution.
     *
     * @return bool True if the app is an iframe app, false otherwise
     */
    public function isIframeApp(): bool
    {
        return $this->app_type === 'iframe';
    }

    /**
     * Check if the app is a Laravel module
     *
     * This method determines whether the installed app is a Laravel
     * module, which requires service provider registration and
     * module-specific configuration.
     *
     * @return bool True if the app is a Laravel module, false otherwise
     */
    public function isLaravelModule(): bool
    {
        return $this->app_type === 'laravel_module';
    }

    /**
     * Check if the app is a WordPress plugin
     *
     * This method determines whether the installed app is a WordPress
     * plugin, which requires WordPress-specific installation and
     * activation procedures.
     *
     * @return bool True if the app is a WordPress plugin, false otherwise
     */
    public function isWordPressPlugin(): bool
    {
        return $this->app_type === 'wordpress_plugin';
    }

    /**
     * Get the module path for Laravel modules
     *
     * This method returns the file system path for Laravel modules,
     * used for module installation, service provider registration,
     * and autoloading configuration.
     *
     * @return string The module path (e.g., "Modules/MyApp") or empty string if not a Laravel module
     */
    public function getModulePath(): string
    {
        if (!$this->isLaravelModule()) {
            return '';
        }

        return "Modules/{$this->module_name}";
    }

    /**
     * Check if the app requires a purchase
     *
     * This method determines whether the installed app is a paid
     * application by checking if it has an associated purchase record.
     *
     * @return bool True if the app requires a purchase, false if it's free
     */
    public function isPaid(): bool
    {
        return $this->purchase_id !== null;
    }

    /**
     * Check if the app purchase is active and valid
     *
     * This method validates whether the user has active access to
     * the installed app based on their purchase status. Free apps
     * always have access, while paid apps require an active purchase.
     *
     * @return bool True if the user has access to the app, false otherwise
     */
    public function hasPaidAccess(): bool
    {
        if (!$this->isPaid()) {
            return true; // Free apps have access
        }

        return $this->purchase && $this->purchase->isActive();
    }

    /**
     * Get default iframe configuration for security
     *
     * This method provides the default security configuration for
     * iframe applications, including sandbox policies, loading behavior,
     * and security restrictions to ensure safe execution.
     *
     * @return array Default iframe configuration with security policies
     */
    public static function getDefaultIframeConfig(): array
    {
        return [
            'sandbox' => 'allow-scripts allow-same-origin allow-forms allow-popups allow-modals',
            'allow_fullscreen' => true,
            'loading' => 'lazy',
            'security_policy' => 'strict',
        ];
    }
} 