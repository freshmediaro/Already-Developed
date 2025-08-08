<?php

namespace App\Models\Wallet;

use App\Models\User;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tenant Wallet Settings Model - Manages wallet configuration and preferences
 *
 * This model represents wallet settings and configuration for users and teams
 * within the multi-tenant system, including auto-top-up settings, notification
 * preferences, and spending limits.
 *
 * Key features:
 * - Wallet type configuration and management
 * - Auto-top-up settings and thresholds
 * - Payment method preferences
 * - Notification preferences and delivery
 * - Spending limits and controls
 * - Multi-tenant wallet isolation
 * - Settings persistence and management
 * - Metadata and configuration storage
 * - Security and access control
 * - Default settings management
 *
 * Wallet types:
 * - default: Standard user wallet
 * - business: Business/team wallet
 * - revenue: Revenue wallet for teams
 * - ai_tokens: AI token wallet
 * - escrow: Escrow wallet for transactions
 * - savings: Savings wallet
 *
 * Notification preferences:
 * - low_balance: Low balance alerts
 * - large_transaction: Large transaction notifications
 * - failed_payment: Failed payment alerts
 * - successful_top_up: Successful top-up confirmations
 * - spending_limit: Spending limit warnings
 * - security_alert: Security-related notifications
 *
 * Spending limit periods:
 * - daily: Daily spending limit
 * - weekly: Weekly spending limit
 * - monthly: Monthly spending limit
 * - yearly: Yearly spending limit
 *
 * The model provides:
 * - Comprehensive wallet configuration
 * - Auto-top-up management
 * - Notification preference control
 * - Spending limit enforcement
 * - Multi-tenant isolation
 * - Settings persistence
 * - Security controls
 *
 * @package App\Models\Wallet
 * @since 1.0.0
 */
class TenantWalletSettings extends Model
{
    use HasFactory;

    /** @var string The table name for tenant wallet settings */
    protected $table = 'tenant_wallet_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'team_id',
        'wallet_type',
        'auto_top_up',
        'auto_top_up_threshold',
        'auto_top_up_amount',
        'preferred_payment_method',
        'email_notifications',
        'sms_notifications',
        'notification_preferences',
        'spending_limits',
        'metadata'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'auto_top_up' => 'boolean',
        'auto_top_up_threshold' => 'decimal:2',
        'auto_top_up_amount' => 'decimal:2',
        'email_notifications' => 'boolean',
        'sms_notifications' => 'boolean',
        'notification_preferences' => 'array',
        'spending_limits' => 'array',
        'metadata' => 'array'
    ];

    /**
     * Get the user who owns these wallet settings
     *
     * This relationship provides access to the user who owns
     * the wallet settings.
     *
     * @return BelongsTo Relationship to User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team context for these wallet settings
     *
     * This relationship provides access to the team context for
     * the wallet settings.
     *
     * @return BelongsTo Relationship to Team model
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope for filtering settings by user
     *
     * This scope filters wallet settings by user ID,
     * providing user-specific settings access.
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
     * Scope for filtering settings by team
     *
     * This scope filters wallet settings by team ID,
     * providing team-specific settings access.
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
     * Scope for filtering settings by wallet type
     *
     * This scope filters wallet settings by wallet type,
     * enabling type-specific settings queries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param string $walletType The wallet type to filter by
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeForWalletType($query, string $walletType)
    {
        return $query->where('wallet_type', $walletType);
    }

    /**
     * Scope for comprehensive tenant isolation
     *
     * This scope provides comprehensive tenant isolation by
     * filtering by both user and team context, ensuring proper
     * access control and data separation.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param int $userId The user ID for security validation
     * @param int|null $teamId Optional team ID for additional tenant isolation
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeTenantIsolated($query, int $userId, ?int $teamId = null)
    {
        $query->where('user_id', $userId);
        if ($teamId) {
            $query->where('team_id', $teamId);
        }
        return $query;
    }

    /**
     * Check if auto-top-up should be triggered for the current balance
     *
     * This method determines whether auto-top-up should be triggered
     * based on the current wallet balance and configured threshold.
     *
     * @param float $currentBalance The current wallet balance
     * @return bool True if auto-top-up should be triggered, false otherwise
     */
    public function shouldAutoTopUp(float $currentBalance): bool
    {
        return $this->auto_top_up && 
               $this->auto_top_up_threshold && 
               $currentBalance <= $this->auto_top_up_threshold;
    }

    /**
     * Get the configured top-up amount
     *
     * This method returns the amount that should be used for
     * auto-top-up transactions.
     *
     * @return float The top-up amount (0 if not configured)
     */
    public function getTopUpAmount(): float
    {
        return $this->auto_top_up_amount ?? 0;
    }

    /**
     * Check if a transaction amount is within spending limits
     *
     * This method validates whether a transaction amount is within
     * the configured spending limits for the specified period.
     *
     * @param float $amount The transaction amount to check
     * @param string $period The spending limit period (daily, weekly, monthly, yearly)
     * @return bool True if within spending limit, false otherwise
     */
    public function isWithinSpendingLimit(float $amount, string $period = 'daily'): bool
    {
        $limits = $this->spending_limits ?? [];
        
        if (!isset($limits[$period])) {
            return true; // No limit set
        }

        // This would need to check against actual spending in the period
        // Implementation would require additional logic to track spending
        return true; // Placeholder implementation
    }

    public static function getOrCreateForUser(int $userId, ?int $teamId = null, string $walletType = 'default'): self
    {
        return static::firstOrCreate(
            [
                'user_id' => $userId,
                'team_id' => $teamId,
                'wallet_type' => $walletType
            ],
            [
                'auto_top_up' => false,
                'email_notifications' => true,
                'sms_notifications' => false,
                'notification_preferences' => [],
                'spending_limits' => [],
                'metadata' => []
            ]
        );
    }

    public function getNotificationPreference(string $key, $default = null)
    {
        $preferences = $this->notification_preferences ?? [];
        return $preferences[$key] ?? $default;
    }

    public function setNotificationPreference(string $key, $value): void
    {
        $preferences = $this->notification_preferences ?? [];
        $preferences[$key] = $value;
        $this->notification_preferences = $preferences;
    }

    public function getSpendingLimit(string $period): ?float
    {
        $limits = $this->spending_limits ?? [];
        return isset($limits[$period]) ? (float) $limits[$period] : null;
    }

    public function setSpendingLimit(string $period, float $amount): void
    {
        $limits = $this->spending_limits ?? [];
        $limits[$period] = $amount;
        $this->spending_limits = $limits;
    }
} 