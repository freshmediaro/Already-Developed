<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Cashier\Billable;
use Bavix\Wallet\Traits\HasWallet;
use Bavix\Wallet\Traits\HasWallets;
use Bavix\Wallet\Interfaces\Wallet;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * User Model - Core user entity with multi-tenant support and wallet integration
 *
 * This model represents the core user entity in the multi-tenant system,
 * providing authentication, authorization, wallet management, and team
 * membership functionality. It integrates with multiple Laravel packages
 * for comprehensive user management capabilities.
 *
 * Key features:
 * - Multi-tenant authentication and authorization
 * - Wallet management for payments and credits
 * - Team membership and collaboration
 * - Two-factor authentication support
 * - API token management
 * - Desktop configuration management
 * - Notification system integration
 * - Stripe billing integration with tenant isolation
 *
 * The model integrates with:
 * - Laravel Sanctum for API authentication
 * - Laravel Fortify for two-factor authentication
 * - Laravel Cashier for Stripe billing
 * - Bavix Wallet for multi-wallet support
 * - Spatie Laravel Permission for role management
 *
 * @package App\Models
 * @since 1.0.0
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use Billable;
    use HasWallet;
    use HasWallets;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'language',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the user's desktop configuration for UI customization
     *
     * This relationship provides access to the user's desktop configuration
     * including theme preferences, layout settings, and UI customizations.
     *
     * @return HasOne Relationship to DesktopConfiguration model
     */
    public function desktopConfiguration(): HasOne
    {
        return $this->hasOne(DesktopConfiguration::class);
    }

    /**
     * Get all notifications for the user
     *
     * This relationship provides access to all notifications associated
     * with the user, including system notifications, app notifications,
     * and team-specific notifications.
     *
     * @return HasMany Relationship to Notification model
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get unread notifications for the user
     *
     * This relationship provides access to only unread notifications,
     * useful for displaying notification counts and unread indicators.
     *
     * @return HasMany Relationship to unread Notification models
     */
    public function unreadNotifications(): HasMany
    {
        return $this->notifications()->unread();
    }

    /**
     * Get or create desktop configuration with default settings
     *
     * This method ensures that every user has a desktop configuration
     * by creating one with default settings if it doesn't exist.
     * It provides a convenient way to access user preferences.
     *
     * @return DesktopConfiguration The user's desktop configuration
     */
    public function getDesktopConfig(): DesktopConfiguration
    {
        return $this->desktopConfiguration()->firstOrCreate(
            ['user_id' => $this->id],
            DesktopConfiguration::getDefaults()
        );
    }

    /**
     * Update the user's last login timestamp
     *
     * This method updates the last_login_at field to track user activity
     * and can be used for analytics, session management, and security
     * monitoring purposes.
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Get the user's main wallet for general payments and credits
     *
     * This method provides access to the user's primary wallet used for
     * general transactions, payments, and credit management. It ensures
     * the wallet exists by creating it if necessary.
     *
     * @return Wallet The user's main wallet instance
     */
    public function getMainWallet(): Wallet
    {
        return $this->getWallet('default');
    }

    /**
     * Get the user's AI tokens wallet for AI chat operations
     *
     * This method provides access to the user's AI tokens wallet used
     * specifically for AI chat operations, token consumption tracking,
     * and AI-related billing.
     *
     * @return Wallet The user's AI tokens wallet instance
     */
    public function getAiTokenWallet(): Wallet
    {
        return $this->getWallet('ai-tokens');
    }

    /**
     * Get the user's revenue wallet for team-based revenue tracking
     *
     * This method provides access to the user's revenue wallet used
     * for tracking team-based revenue, commissions, and business
     * income within the multi-tenant system.
     *
     * @return Wallet The user's revenue wallet instance
     */
    public function getRevenueWallet(): Wallet
    {
        return $this->getWallet('revenue');
    }

    /**
     * Override Cashier's stripe method to use tenant-scoped Stripe keys
     *
     * This method extends Laravel Cashier's stripe functionality to support
     * tenant-specific Stripe configurations. It allows each tenant to have
     * their own Stripe API keys while falling back to platform defaults.
     *
     * The method:
     * - Checks for tenant-specific Stripe configuration
     * - Uses tenant API keys if available
     * - Falls back to platform default keys
     * - Maintains compatibility with Cashier's billing features
     *
     * @param array $options Additional options for Stripe configuration
     * @return \Stripe\StripeClient The configured Stripe client instance
     */
    public function stripe(array $options = [])
    {
        // Check if we're in a tenant context and if tenant has custom Stripe config
        if (tenant() && $this->hasTenantStripeConfig()) {
            $config = $this->getTenantStripeConfig();
            $options = array_merge([
                'api_key' => $config['secret_key'],
            ], $options);
        }

        return parent::stripe($options);
    }

    /**
     * Check if current tenant has custom Stripe configuration
     *
     * This method validates whether the current tenant has configured
     * their own Stripe API keys for tenant-specific billing operations.
     *
     * @return bool True if tenant has custom Stripe config, false otherwise
     */
    protected function hasTenantStripeConfig(): bool
    {
        if (!tenant()) {
            return false;
        }

        return \App\Models\Wallet\PaymentProviderConfig::tenantIsolated($this->id)
            ->byProvider('stripe-cashier')
            ->enabled()
            ->active()
            ->exists();
    }

    /**
     * Get tenant-specific Stripe configuration with fallback
     *
     * This method retrieves the tenant's Stripe configuration including
     * API keys and settings. It provides a fallback to platform defaults
     * if no tenant-specific configuration exists.
     *
     * @return array Stripe configuration with secret_key and public_key
     */
    protected function getTenantStripeConfig(): array
    {
        $config = \App\Models\Wallet\PaymentProviderConfig::tenantIsolated($this->id)
            ->byProvider('stripe-cashier')
            ->enabled()
            ->active()
            ->first();

        if (!$config) {
            // Fallback to platform default Stripe config
            return [
                'secret_key' => config('cashier.secret'),
                'public_key' => config('cashier.key'),
            ];
        }

        return [
            'secret_key' => $config->getDecryptedApiKey(),
            'public_key' => $config->getDecryptedApiSecret(),
        ];
    }

    /**
     * Get tenant-scoped Stripe customer ID for multi-tenancy
     *
     * This method retrieves the Stripe customer ID specific to the current
     * tenant context, allowing for tenant-isolated billing and customer
     * management in Stripe.
     *
     * @return string|null The tenant-specific Stripe customer ID or null if not set
     */
    public function stripeId(): ?string
    {
        if (tenant()) {
            // Use tenant-scoped stripe customer ID
            $tenantId = tenant('id');
            return $this->getAttributeValue("stripe_id_{$tenantId}") ?: $this->getAttributeValue('stripe_id');
        }

        return $this->getAttributeValue('stripe_id');
    }

    /**
     * Set tenant-scoped Stripe customer ID for multi-tenancy
     *
     * This method sets the Stripe customer ID specific to the current
     * tenant context, enabling tenant-isolated customer management
     * in Stripe billing operations.
     *
     * @param string $stripeId The Stripe customer ID to set
     */
    public function updateStripeId(string $stripeId): void
    {
        if (tenant()) {
            $tenantId = tenant('id');
            $this->update(["stripe_id_{$tenantId}" => $stripeId]);
        } else {
            $this->update(['stripe_id' => $stripeId]);
        }
    }

    /**
     * Check if user belongs to a specific team for access control
     *
     * This method validates whether the user is a member of the specified
     * team, providing a convenient way to check team membership for
     * access control and permission validation.
     *
     * @param int $teamId The team ID to check membership for
     * @return bool True if user belongs to the team, false otherwise
     */
    public function belongsToTeam(int $teamId): bool
    {
        return $this->teams()->where('teams.id', $teamId)->exists();
    }
} 