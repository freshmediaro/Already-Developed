<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * App Store App Model - Application marketplace entity with comprehensive app management
 *
 * This model represents applications in the app store marketplace, providing
 * comprehensive app management including publishing, approval workflows, pricing,
 * security scanning, and installation tracking. It supports multiple app types
 * and integration methods.
 *
 * Key features:
 * - Multi-type app support (Vue, iframe, Laravel modules, WordPress plugins)
 * - Publishing and approval workflow management
 * - Pricing model support (free, one-time, subscription, freemium)
 * - Security scanning and vulnerability tracking
 * - Installation and download statistics
 * - Rating and review system
 * - Dependency management
 * - AI token integration
 * - External API configuration
 *
 * Supported app types:
 * - vue: Vue.js components
 * - iframe: Embedded iframe applications
 * - laravel_module: Laravel modules with service providers
 * - wordpress_plugin: WordPress plugin installations
 * - external: External link applications
 * - system: System-level applications
 * - api_integration: API-based integrations
 *
 * The model provides:
 * - Comprehensive app metadata management
 * - Security scanning and approval workflows
 * - Installation tracking and statistics
 * - Pricing and subscription management
 * - Developer and team relationships
 * - Category and tag organization
 *
 * @package App\Models
 * @since 1.0.0
 */
class AppStoreApp extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'detailed_description',
        'icon',
        'screenshots',
        'app_type',
        'module_name',
        'version',
        'developer_id',
        'developer_name',
        'developer_website',
        'developer_email',
        'pricing_type',
        'price',
        'monthly_price',
        'currency',
        'is_published',
        'is_featured',
        'is_approved',
        'approval_status',
        'approval_notes',
        'approved_by',
        'approved_at',
        'published_by',
        'published_at',
        'download_count',
        'active_installs',
        'rating_average',
        'rating_count',
        'minimum_php_version',
        'minimum_laravel_version',
        'repository_url',
        'documentation_url',
        'support_url',
        'license',
        'tags',
        'iframe_config',
        'requires_ai_tokens',
        'ai_token_cost_per_use',
        'requires_external_api',
        'external_api_config',
        'security_notes',
        'changelog',
        'installation_notes',
        'configuration_schema',
        'permissions_required',
        'requires_admin_approval',
        'auto_update_enabled',
        'last_updated_at',
        'package_path',
        'submitted_by_team_id',
        'security_scan_status',
        'security_scan_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'screenshots' => 'array',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'published_at' => 'datetime',
        'price' => 'decimal:2',
        'monthly_price' => 'decimal:2',
        'download_count' => 'integer',
        'active_installs' => 'integer',
        'rating_average' => 'decimal:2',
        'rating_count' => 'integer',
        'tags' => 'array',
        'iframe_config' => 'array',
        'requires_ai_tokens' => 'boolean',
        'ai_token_cost_per_use' => 'integer',
        'requires_external_api' => 'boolean',
        'external_api_config' => 'array',
        'changelog' => 'array',
        'configuration_schema' => 'array',
        'permissions_required' => 'array',
        'requires_admin_approval' => 'boolean',
        'auto_update_enabled' => 'boolean',
        'last_updated_at' => 'datetime',
    ];

    /**
     * Boot the model and set up automatic behaviors
     *
     * This method initializes the model with automatic slug generation
     * when creating new app store applications.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($app) {
            if (!$app->slug) {
                $app->slug = Str::slug($app->name);
            }
        });
    }

    /**
     * Get the developer who created this app
     *
     * @return BelongsTo Relationship to User model (developer)
     */
    public function developer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'developer_id');
    }

    /**
     * Get the user who approved this app
     *
     * @return BelongsTo Relationship to User model (approver)
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the user who published this app
     *
     * @return BelongsTo Relationship to User model (publisher)
     */
    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    /**
     * Get the categories this app belongs to
     *
     * @return BelongsToMany Many-to-many relationship with AppStoreCategory
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(AppStoreCategory::class, 'app_store_app_categories');
    }

    /**
     * Get the apps that this app depends on
     *
     * @return BelongsToMany Many-to-many relationship with dependent apps
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(AppStoreApp::class, 'app_store_dependencies', 'app_id', 'dependency_id');
    }

    /**
     * Get the apps that depend on this app
     *
     * @return BelongsToMany Many-to-many relationship with dependent apps
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(AppStoreApp::class, 'app_store_dependencies', 'dependency_id', 'app_id');
    }

    /**
     * Get the reviews for this app
     *
     * @return HasMany One-to-many relationship with AppStoreReview
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(AppStoreReview::class);
    }

    /**
     * Get the installations of this app
     *
     * @return HasMany One-to-many relationship with InstalledApp
     */
    public function installations(): HasMany
    {
        return $this->hasMany(InstalledApp::class, 'app_id', 'slug');
    }

    /**
     * Get the purchases of this app
     *
     * @return HasMany One-to-many relationship with AppStorePurchase
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(AppStorePurchase::class);
    }

    /**
     * Get the security scan results for this app
     *
     * This relationship provides access to all security scan results
     * for the app, enabling vulnerability tracking and security monitoring.
     *
     * @return HasMany One-to-many relationship with SecurityScanResult
     */
    public function securityScans(): HasMany
    {
        return $this->hasMany(SecurityScanResult::class);
    }

    /**
     * Get the latest security scan result for this app
     *
     * This relationship provides access to the most recent security
     * scan result, useful for displaying current security status.
     *
     * @return HasOne One-to-one relationship with latest SecurityScanResult
     */
    public function latestSecurityScan(): HasOne
    {
        return $this->hasOne(SecurityScanResult::class)->latestOfMany();
    }

    /**
     * Scope for published apps
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope for approved apps
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope for featured apps
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for apps by category
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param string $categorySlug The category slug to filter by
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeByCategory($query, $categorySlug)
    {
        return $query->whereHas('categories', function ($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        });
    }

    /**
     * Scope for apps by developer
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param int $developerId The developer ID to filter by
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeByDeveloper($query, $developerId)
    {
        return $query->where('developer_id', $developerId);
    }

    /**
     * Scope for searching apps by name, description, developer, or tags
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param string $term The search term
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'ILIKE', "%{$term}%")
              ->orWhere('description', 'ILIKE', "%{$term}%")
              ->orWhere('developer_name', 'ILIKE', "%{$term}%")
              ->orWhereJsonContains('tags', $term);
        });
    }

    /**
     * Get available app types for the marketplace
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
     * Get available pricing types for apps
     *
     * @return array Associative array of pricing type keys and display names
     */
    public static function getPricingTypes(): array
    {
        return [
            'free' => 'Free',
            'one_time' => 'One-time Payment',
            'monthly' => 'Monthly Subscription',
            'freemium' => 'Freemium (with in-app purchases)',
        ];
    }

    /**
     * Get available approval statuses for apps
     *
     * @return array Associative array of approval status keys and display names
     */
    public static function getApprovalStatuses(): array
    {
        return [
            'pending' => 'Pending Review',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'needs_changes' => 'Needs Changes',
            'under_review' => 'Under Review',
        ];
    }

    /**
     * Check if the app is free
     *
     * @return bool True if the app is free, false otherwise
     */
    public function isFree(): bool
    {
        return $this->pricing_type === 'free';
    }

    /**
     * Check if the app requires a one-time payment
     *
     * @return bool True if the app requires one-time payment, false otherwise
     */
    public function isOneTime(): bool
    {
        return $this->pricing_type === 'one_time';
    }

    /**
     * Check if the app requires a monthly subscription
     *
     * @return bool True if the app requires monthly subscription, false otherwise
     */
    public function isSubscription(): bool
    {
        return $this->pricing_type === 'monthly';
    }

    /**
     * Check if the app has freemium pricing model
     *
     * @return bool True if the app has freemium pricing, false otherwise
     */
    public function hasFreemium(): bool
    {
        return $this->pricing_type === 'freemium';
    }

    /**
     * Check if the app is an iframe application
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
     * @return bool True if the app is a Laravel module, false otherwise
     */
    public function isLaravelModule(): bool
    {
        return $this->app_type === 'laravel_module';
    }

    /**
     * Check if the app is a WordPress plugin
     *
     * @return bool True if the app is a WordPress plugin, false otherwise
     */
    public function isWordPressPlugin(): bool
    {
        return $this->app_type === 'wordpress_plugin';
    }

    /**
     * Check if the app requires admin approval
     *
     * @return bool True if the app requires approval, false otherwise
     */
    public function requiresApproval(): bool
    {
        return $this->requires_admin_approval || !$this->is_approved;
    }

    /**
     * Check if a user can install this app
     *
     * This method validates whether a user can install the app based on
     * approval status, publication status, team context, existing installations,
     * and dependency requirements.
     *
     * @param User $user The user attempting to install the app
     * @return bool True if the user can install the app, false otherwise
     */
    public function canBeInstalledBy(User $user): bool
    {
        // Check if app is approved and published
        if (!$this->is_approved || !$this->is_published) {
            return false;
        }

        // Check if user is in a team (tenant context)
        if (!$user->currentTeam) {
            return false;
        }

        // Check if app is already installed for this team
        $alreadyInstalled = $this->installations()
            ->where('team_id', $user->currentTeam->id)
            ->where('is_active', true)
            ->exists();

        if ($alreadyInstalled) {
            return false;
        }

        // Check dependencies
        foreach ($this->dependencies as $dependency) {
            $dependencyInstalled = $dependency->installations()
                ->where('team_id', $user->currentTeam->id)
                ->where('is_active', true)
                ->exists();

            if (!$dependencyInstalled) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the installation price for a specific user
     *
     * This method calculates the price for installing the app, considering
     * whether the user has already purchased it and the pricing model.
     *
     * @param User $user The user to calculate price for
     * @return float The installation price (0 for free apps or already purchased)
     */
    public function getInstallationPrice(User $user): float
    {
        if ($this->isFree()) {
            return 0;
        }

        // Check if user already purchased
        $hasPurchased = $this->purchases()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->exists();

        if ($hasPurchased && !$this->isSubscription()) {
            return 0;
        }

        return $this->isSubscription() ? $this->monthly_price : $this->price;
    }

    /**
     * Get formatted price string for display
     *
     * This method returns a human-readable price string including
     * currency symbols and subscription indicators.
     *
     * @return string Formatted price string (e.g., "Free", "$9.99", "$19.99/month")
     */
    public function getFormattedPrice(): string
    {
        if ($this->isFree()) {
            return 'Free';
        }

        $price = $this->isSubscription() ? $this->monthly_price : $this->price;
        $symbol = $this->currency === 'USD' ? '$' : $this->currency;

        if ($this->isSubscription()) {
            return "{$symbol}{$price}/month";
        }

        return "{$symbol}{$price}";
    }

    /**
     * Update the app's rating with a new review
     *
     * This method recalculates the average rating when a new review
     * is added, maintaining the weighted average calculation.
     *
     * @param float $newRating The new rating to add to the average
     */
    public function updateRating(float $newRating): void
    {
        $totalRating = ($this->rating_average * $this->rating_count) + $newRating;
        $this->rating_count += 1;
        $this->rating_average = $totalRating / $this->rating_count;
        $this->save();
    }

    /**
     * Increment the download count for this app
     *
     * This method increases the download count by 1, typically
     * called when a user downloads the app.
     */
    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    /**
     * Increment the active installations count for this app
     *
     * This method increases the active installations count by 1,
     * typically called when the app is installed.
     */
    public function incrementActiveInstalls(): void
    {
        $this->increment('active_installs');
    }

    /**
     * Decrement the active installations count for this app
     *
     * This method decreases the active installations count by 1,
     * typically called when the app is uninstalled.
     */
    public function decrementActiveInstalls(): void
    {
        $this->decrement('active_installs');
    }

    /**
     * Get the module path for Laravel modules
     *
     * This method returns the file system path for Laravel modules,
     * used for module installation and management.
     *
     * @return string The module path (e.g., "Modules/MyApp")
     */
    public function getModulePath(): string
    {
        return "Modules/{$this->module_name}";
    }

    /**
     * Get the module namespace for Laravel modules
     *
     * This method returns the PHP namespace for Laravel modules,
     * used for service provider registration and autoloading.
     *
     * @return string The module namespace (e.g., "Modules\MyApp")
     */
    public function getModuleNamespace(): string
    {
        return "Modules\\{$this->module_name}";
    }

    /**
     * Generate a new API key for the app
     *
     * This method generates a secure random API key for apps that
     * require external API integration.
     *
     * @return string A 32-character random API key
     */
    public function generateApiKey(): string
    {
        return Str::random(32);
    }

    /**
     * Get screenshot URLs for the app
     *
     * This method processes screenshot paths and returns full URLs,
     * handling both local storage and external URLs.
     *
     * @return array Array of screenshot URLs
     */
    public function getScreenshotUrls(): array
    {
        if (!$this->screenshots) {
            return [];
        }

        return array_map(function ($screenshot) {
            if (Str::startsWith($screenshot, ['http://', 'https://'])) {
                return $screenshot;
            }
            return asset("storage/app-store/screenshots/{$screenshot}");
        }, $this->screenshots);
    }

    /**
     * Get the icon URL for the app
     *
     * This method returns the full URL for the app's icon, providing
     * a default icon if none is specified.
     *
     * @return string The icon URL
     */
    public function getIconUrl(): string
    {
        if (!$this->icon) {
            return asset('img/apps/default-app-icon.png');
        }

        if (Str::startsWith($this->icon, ['http://', 'https://'])) {
            return $this->icon;
        }

        return asset("storage/app-store/icons/{$this->icon}");
    }
} 