<?php

namespace App\Models\Wallet;

use App\Models\User;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

/**
 * Payment Provider Config Model - Manages payment provider configurations and subscriptions
 *
 * This model represents payment provider configurations for users and teams within
 * the multi-tenant system, including API credentials, subscription management,
 * and provider-specific settings for payment processing.
 *
 * Key features:
 * - Payment provider configuration management
 * - API credential encryption and storage
 * - Subscription and billing management
 * - Multi-tenant provider isolation
 * - Provider feature support tracking
 * - Test mode configuration
 * - Webhook secret management
 * - Usage tracking and analytics
 * - Default provider selection
 * - Security and access control
 *
 * Supported providers:
 * - Stripe: Credit card and digital wallet processing
 * - PayPal: Digital wallet and bank transfer processing
 * - Square: Point-of-sale and online payment processing
 * - Authorize.net: AIM payment gateway processing
 * - Custom providers: Third-party payment integrations
 *
 * Subscription statuses:
 * - active: Subscription is active and valid
 * - expired: Subscription has expired
 * - cancelled: Subscription was cancelled
 * - pending: Subscription is pending activation
 * - suspended: Subscription is temporarily suspended
 *
 * The model provides:
 * - Secure credential storage with encryption
 * - Subscription lifecycle management
 * - Provider feature validation
 * - Multi-tenant isolation
 * - Usage tracking and analytics
 * - Default provider management
 * - Security and access control
 *
 * @package App\Models\Wallet
 * @since 1.0.0
 */
class PaymentProviderConfig extends Model
{
    use HasFactory;

    /** @var string The table name for payment provider configurations */
    protected $table = 'payment_provider_configs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'team_id',
        'provider_name',
        'is_enabled',
        'is_default',
        'api_key',
        'api_secret',
        'webhook_secret',
        'additional_config',
        'test_mode',
        'currency',
        'monthly_fee',
        'subscription_started_at',
        'subscription_expires_at',
        'subscription_status',
        'last_used_at',
        'supported_features',
        'metadata'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
        'is_default' => 'boolean',
        'additional_config' => 'array',
        'test_mode' => 'boolean',
        'monthly_fee' => 'decimal:2',
        'subscription_started_at' => 'date',
        'subscription_expires_at' => 'date',
        'last_used_at' => 'datetime',
        'supported_features' => 'array',
        'metadata' => 'array'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'api_key',
        'api_secret',
        'webhook_secret'
    ];

    /**
     * The attributes that should be treated as dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'subscription_started_at',
        'subscription_expires_at',
        'last_used_at'
    ];

    /**
     * Get the user who owns this payment provider configuration
     *
     * This relationship provides access to the user who owns
     * the payment provider configuration.
     *
     * @return BelongsTo Relationship to User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team context for this payment provider configuration
     *
     * This relationship provides access to the team context for
     * the payment provider configuration.
     *
     * @return BelongsTo Relationship to Team model
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope for filtering configurations by user
     *
     * This scope filters payment provider configurations by user ID,
     * providing user-specific configuration access.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param int $userId The user ID to filter by
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering configurations by team
     *
     * This scope filters payment provider configurations by team ID,
     * providing team-specific configuration access.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param int|null $teamId The team ID to filter by
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeForTeam($query, ?int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope for enabled payment provider configurations
     *
     * This scope filters payment provider configurations to only
     * include those that are currently enabled.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Scope for active subscription configurations
     *
     * This scope filters payment provider configurations to only
     * include those with active subscriptions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeActive($query)
    {
        return $query->where('subscription_status', 'active');
    }

    /**
     * Scope for filtering configurations by provider name
     *
     * This scope filters payment provider configurations by provider name,
     * enabling provider-specific queries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param string $providerName The provider name to filter by
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeByProvider($query, string $providerName)
    {
        return $query->where('provider_name', $providerName);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeTenantIsolated($query, int $userId, ?int $teamId = null)
    {
        $query->where('user_id', $userId);
        if ($teamId) {
            $query->where('team_id', $teamId);
        }
        return $query;
    }

    // Helper Methods
    public function getDecryptedApiKey(): ?string
    {
        if (!$this->api_key) {
            return null;
        }

        try {
            return Crypt::decryptString($this->api_key);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getDecryptedApiSecret(): ?string
    {
        if (!$this->api_secret) {
            return null;
        }

        try {
            return Crypt::decryptString($this->api_secret);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getDecryptedWebhookSecret(): ?string
    {
        if (!$this->webhook_secret) {
            return null;
        }

        try {
            return Crypt::decryptString($this->webhook_secret);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function isActive(): bool
    {
        return $this->is_enabled && $this->subscription_status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->subscription_expires_at && $this->subscription_expires_at->isPast();
    }

    public function isExpiringSoon(int $days = 7): bool
    {
        return $this->subscription_expires_at && 
               $this->subscription_expires_at->diffInDays(now()) <= $days;
    }

    public function getRemainingDays(): int
    {
        if (!$this->subscription_expires_at) {
            return 0;
        }

        return max(0, $this->subscription_expires_at->diffInDays(now()));
    }

    public function canProcessPayments(): bool
    {
        return $this->isActive() && 
               !$this->isExpired() && 
               $this->hasRequiredCredentials();
    }

    public function hasRequiredCredentials(): bool
    {
        switch ($this->provider_name) {
            case 'stripe':
            case 'stripe-cashier':
                return !empty($this->api_key);
            case 'paypal':
                return !empty($this->api_key) && !empty($this->api_secret);
            case 'square':
                return !empty($this->api_key) && !empty($this->api_secret);
            case 'authorize_net':
                return !empty($this->api_key) && !empty($this->api_secret);
            default:
                return !empty($this->api_key);
        }
    }

    public function getProviderDisplayName(): string
    {
        $names = [
            'stripe' => 'Stripe (Direct)',
            'stripe-cashier' => 'Stripe (Platform Default)',
            'paypal' => 'PayPal',
            'square' => 'Square',
            'authorize_net' => 'Authorize.Net'
        ];

        return $names[$this->provider_name] ?? ucfirst($this->provider_name);
    }

    public function supportsFeature(string $feature): bool
    {
        return isset($this->supported_features[$feature]) && 
               $this->supported_features[$feature] === true;
    }

    public function getMonthlyFeeFormatted(): string
    {
        return '$' . number_format($this->monthly_fee, 2);
    }

    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    // Static Helper Methods
    public static function getForUser(int $userId, ?int $teamId = null): \Illuminate\Database\Eloquent\Collection
    {
        return static::tenantIsolated($userId, $teamId)->get();
    }

    public static function getActiveForUser(int $userId, ?int $teamId = null): \Illuminate\Database\Eloquent\Collection
    {
        return static::tenantIsolated($userId, $teamId)
            ->enabled()
            ->active()
            ->get();
    }

    public static function getDefaultForUser(int $userId, ?int $teamId = null): ?self
    {
        return static::tenantIsolated($userId, $teamId)
            ->enabled()
            ->active()
            ->default()
            ->first();
    }

    public static function findByProvider(int $userId, ?int $teamId, string $providerName): ?self
    {
        return static::tenantIsolated($userId, $teamId)
            ->byProvider($providerName)
            ->first();
    }

    public static function getEnabledProviders(int $userId, ?int $teamId = null): array
    {
        return static::tenantIsolated($userId, $teamId)
            ->enabled()
            ->active()
            ->get()
            ->map(function ($config) {
                return [
                    'provider_name' => $config->provider_name,
                    'display_name' => $config->getProviderDisplayName(),
                    'currency' => $config->currency,
                    'test_mode' => $config->test_mode,
                    'monthly_fee' => $config->monthly_fee,
                    'supported_features' => $config->supported_features,
                    'expires_at' => $config->subscription_expires_at,
                    'is_default' => $config->is_default,
                    'can_process_payments' => $config->canProcessPayments()
                ];
            })
            ->toArray();
    }

    public static function setDefault(int $userId, ?int $teamId, string $providerName): bool
    {
        try {
            // Remove default from all providers for this user/team
            static::tenantIsolated($userId, $teamId)
                ->update(['is_default' => false]);

            // Set the specified provider as default
            $config = static::tenantIsolated($userId, $teamId)
                ->byProvider($providerName)
                ->enabled()
                ->active()
                ->first();

            if ($config) {
                $config->update(['is_default' => true]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
} 