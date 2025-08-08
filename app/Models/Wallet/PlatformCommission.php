<?php

namespace App\Models\Wallet;

use App\Models\User;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Platform Commission Model - Manages platform revenue and commission tracking
 *
 * This model represents platform commissions and revenue tracking for all
 * transactions within the multi-tenant system, including fee calculations,
 * provider fees, and revenue distribution.
 *
 * Key features:
 * - Platform commission tracking and management
 * - Payment provider fee calculation
 * - Revenue distribution to tenants
 * - Transaction type classification
 * - Multi-currency support
 * - Commission rate management
 * - Processing status tracking
 * - Analytics and reporting
 * - Multi-tenant revenue isolation
 * - Metadata and audit trails
 *
 * Transaction types:
 * - payment: Customer payments from e-commerce
 * - ai_tokens: AI token purchases
 * - app_purchase: App store purchases
 * - subscription: Recurring subscription payments
 * - withdrawal: User withdrawal fees
 * - refund: Payment refunds and reversals
 *
 * Commission statuses:
 * - pending: Awaiting processing
 * - processed: Successfully processed
 * - failed: Processing failed
 * - refunded: Commission refunded
 * - cancelled: Commission cancelled
 *
 * The model provides:
 * - Comprehensive commission tracking
 * - Fee calculation and distribution
 * - Revenue analytics and reporting
 * - Multi-tenant isolation
 * - Provider-specific fee handling
 * - Currency conversion support
 * - Audit trail maintenance
 *
 * @package App\Models\Wallet
 * @since 1.0.0
 */
class PlatformCommission extends Model
{
    use HasFactory;

    /** @var string The table name for platform commissions */
    protected $table = 'platform_commissions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'team_id',
        'transaction_type',
        'transaction_id',
        'original_amount',
        'platform_commission',
        'stripe_fee',
        'total_fees',
        'tenant_amount',
        'commission_rate',
        'payment_provider',
        'currency',
        'status',
        'processed_at',
        'metadata'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'original_amount' => 'decimal:2',
        'platform_commission' => 'decimal:2',
        'stripe_fee' => 'decimal:2',
        'total_fees' => 'decimal:2',
        'tenant_amount' => 'decimal:2',
        'commission_rate' => 'decimal:4',
        'processed_at' => 'datetime',
        'metadata' => 'array'
    ];

    /**
     * Get the user associated with this commission
     *
     * This relationship provides access to the user who
     * generated the commission transaction.
     *
     * @return BelongsTo Relationship to User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team associated with this commission
     *
     * This relationship provides access to the team context for
     * the commission transaction.
     *
     * @return BelongsTo Relationship to Team model
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope for filtering commissions by user
     *
     * This scope filters platform commissions by user ID,
     * providing user-specific commission access.
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
     * Scope for filtering commissions by team
     *
     * This scope filters platform commissions by team ID,
     * providing team-specific commission access.
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
     * Scope for filtering commissions by transaction type
     *
     * This scope filters platform commissions by transaction type,
     * enabling type-specific commission analysis.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param string $type The transaction type to filter by
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeByTransactionType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope for filtering commissions by payment provider
     *
     * This scope filters platform commissions by payment provider,
     * enabling provider-specific commission analysis.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param string $provider The payment provider to filter by
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('payment_provider', $provider);
    }

    /**
     * Scope for filtering commissions by status
     *
     * This scope filters platform commissions by processing status,
     * enabling status-specific commission queries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param string $status The status to filter by
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering commissions by date range
     *
     * This scope filters platform commissions by date range,
     * enabling time-based commission analysis.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param Carbon $startDate The start date for the range
     * @param Carbon $endDate The end date for the range
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeInDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
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
     * Scope for processed commissions
     *
     * This scope filters platform commissions to only include
     * those that have been successfully processed.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Scope for pending commissions
     *
     * This scope filters platform commissions to only include
     * those that are awaiting processing.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Helper methods
    /**
     * Check if the commission has been processed
     *
     * This method determines if the commission's status is 'processed'.
     *
     * @return bool True if processed, false otherwise
     */
    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    /**
     * Check if the commission is pending
     *
     * This method determines if the commission's status is 'pending'.
     *
     * @return bool True if pending, false otherwise
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the commission failed processing
     *
     * This method determines if the commission's status is 'failed'.
     *
     * @return bool True if failed, false otherwise
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if the commission was refunded
     *
     * This method determines if the commission's status is 'refunded'.
     *
     * @return bool True if refunded, false otherwise
     */
    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    /**
     * Get the effective commission rate
     *
     * This method calculates the effective commission rate based on
     * the commission_rate attribute.
     *
     * @return float The effective commission rate
     */
    public function getEffectiveCommissionRate(): float
    {
        return (float) $this->commission_rate;
    }

    /**
     * Get the total fees percentage
     *
     * This method calculates the percentage of the original amount
     * that represents total fees (platform commission + stripe fee).
     *
     * @return float The total fees percentage
     */
    public function getTotalFeesPercentage(): float
    {
        return $this->original_amount > 0 ? ($this->total_fees / $this->original_amount) * 100 : 0;
    }

    /**
     * Get the net amount available for the tenant
     *
     * This method calculates the net amount that is available for
     * the tenant after deducting total fees.
     *
     * @return float The net amount for the tenant
     */
    public function getNetAmountForTenant(): float
    {
        return (float) $this->tenant_amount;
    }

    /**
     * Get the formatted original amount
     *
     * This method formats the original_amount attribute as a string
     * with a dollar sign and two decimal places.
     *
     * @return string The formatted original amount
     */
    public function getFormattedOriginalAmount(): string
    {
        return '$' . number_format($this->original_amount, 2);
    }

    /**
     * Get the formatted platform commission
     *
     * This method formats the platform_commission attribute as a string
     * with a dollar sign and two decimal places.
     *
     * @return string The formatted platform commission
     */
    public function getFormattedPlatformCommission(): string
    {
        return '$' . number_format($this->platform_commission, 2);
    }

    /**
     * Get the formatted stripe fee
     *
     * This method formats the stripe_fee attribute as a string
     * with a dollar sign and two decimal places.
     *
     * @return string The formatted stripe fee
     */
    public function getFormattedStripeFee(): string
    {
        return '$' . number_format($this->stripe_fee ?? 0, 2);
    }

    /**
     * Get the formatted total fees
     *
     * This method formats the total_fees attribute as a string
     * with a dollar sign and two decimal places.
     *
     * @return string The formatted total fees
     */
    public function getFormattedTotalFees(): string
    {
        return '$' . number_format($this->total_fees, 2);
    }

    /**
     * Get the formatted tenant amount
     *
     * This method formats the tenant_amount attribute as a string
     * with a dollar sign and two decimal places.
     *
     * @return string The formatted tenant amount
     */
    public function getFormattedTenantAmount(): string
    {
        return '$' . number_format($this->tenant_amount, 2);
    }

    /**
     * Get the label for the transaction type
     *
     * This method returns a human-readable label for the transaction_type.
     *
     * @return string The transaction type label
     */
    public function getTransactionTypeLabel(): string
    {
        return match($this->transaction_type) {
            'payment' => 'Customer Payment',
            'withdrawal' => 'Withdrawal Request',
            'ai_tokens' => 'AI Token Purchase',
            'app_purchase' => 'App Store Purchase',
            'subscription' => 'Subscription Payment',
            'refund' => 'Refund Processing',
            default => ucfirst(str_replace('_', ' ', $this->transaction_type))
        };
    }

    /**
     * Get the label for the payment provider
     *
     * This method returns a human-readable label for the payment_provider.
     *
     * @return string The payment provider label
     */
    public function getProviderLabel(): string
    {
        return match($this->payment_provider) {
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'square' => 'Square',
            'authorize_net' => 'Authorize.Net',
            'internal' => 'Internal',
            default => ucfirst($this->payment_provider)
        };
    }

    /**
     * Get the label for the commission status
     *
     * This method returns a human-readable label for the status.
     *
     * @return string The status label
     */
    public function getStatusLabel(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'processed' => 'Processed',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
            default => ucfirst($this->status)
        };
    }

    /**
     * Get the color for the commission status
     *
     * This method returns a color class for the status,
     * typically for UI display.
     *
     * @return string The status color class
     */
    public function getStatusColor(): string
    {
        return match($this->status) {
            'pending' => 'yellow',
            'processed' => 'green',
            'failed' => 'red',
            'refunded' => 'blue',
            default => 'gray'
        };
    }

    // Static methods for analytics
    /**
     * Get total platform commission for a given period
     *
     * This method calculates the sum of platform_commission for
     * processed commissions within a specified date range.
     *
     * @param Carbon $startDate The start date of the period
     * @param Carbon $endDate The end date of the period
     * @param int|null $teamId Optional team ID to filter by
     * @return float The total commission for the period
     */
    public static function getTotalCommissionForPeriod(Carbon $startDate, Carbon $endDate, ?int $teamId = null): float
    {
        $query = static::processed()->inDateRange($startDate, $endDate);
        
        if ($teamId) {
            $query->forTeam($teamId);
        }
        
        return (float) $query->sum('platform_commission');
    }

    /**
     * Get total transaction volume for a given period
     *
     * This method calculates the sum of original_amount for
     * processed commissions within a specified date range.
     *
     * @param Carbon $startDate The start date of the period
     * @param Carbon $endDate The end date of the period
     * @param int|null $teamId Optional team ID to filter by
     * @return float The total transaction volume for the period
     */
    public static function getTotalVolumeForPeriod(Carbon $startDate, Carbon $endDate, ?int $teamId = null): float
    {
        $query = static::processed()->inDateRange($startDate, $endDate);
        
        if ($teamId) {
            $query->forTeam($teamId);
        }
        
        return (float) $query->sum('original_amount');
    }

    /**
     * Get commission breakdown by payment provider
     *
     * This method aggregates platform_commission and transaction_count
     * by payment provider for processed commissions within a date range.
     *
     * @param Carbon $startDate The start date of the period
     * @param Carbon $endDate The end date of the period
     * @return array Associative array of payment providers to their totals
     */
    public static function getCommissionByProvider(Carbon $startDate, Carbon $endDate): array
    {
        return static::processed()
            ->inDateRange($startDate, $endDate)
            ->selectRaw('payment_provider, SUM(platform_commission) as total_commission, COUNT(*) as transaction_count')
            ->groupBy('payment_provider')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->payment_provider => [
                    'total_commission' => (float) $item->total_commission,
                    'transaction_count' => (int) $item->transaction_count,
                    'formatted_commission' => '$' . number_format($item->total_commission, 2)
                ]];
            })
            ->toArray();
    }

    /**
     * Get commission breakdown by transaction type
     *
     * This method aggregates platform_commission and transaction_count
     * by transaction type for processed commissions within a date range.
     *
     * @param Carbon $startDate The start date of the period
     * @param Carbon $endDate The end date of the period
     * @return array Associative array of transaction types to their totals
     */
    public static function getCommissionByTransactionType(Carbon $startDate, Carbon $endDate): array
    {
        return static::processed()
            ->inDateRange($startDate, $endDate)
            ->selectRaw('transaction_type, SUM(platform_commission) as total_commission, COUNT(*) as transaction_count')
            ->groupBy('transaction_type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->transaction_type => [
                    'total_commission' => (float) $item->total_commission,
                    'transaction_count' => (int) $item->transaction_count,
                    'formatted_commission' => '$' . number_format($item->total_commission, 2)
                ]];
            })
            ->toArray();
    }

    /**
     * Get top earning teams for a given period
     *
     * This method retrieves the top teams based on total_commission
     * for processed commissions within a date range.
     *
     * @param Carbon $startDate The start date of the period
     * @param Carbon $endDate The end date of the period
     * @param int $limit The number of top teams to return
     * @return array Associative array of team IDs to their earnings
     */
    public static function getTopEarningTeams(Carbon $startDate, Carbon $endDate, int $limit = 10): array
    {
        return static::processed()
            ->inDateRange($startDate, $endDate)
            ->whereNotNull('team_id')
            ->with('team')
            ->selectRaw('team_id, SUM(platform_commission) as total_commission, COUNT(*) as transaction_count')
            ->groupBy('team_id')
            ->orderByDesc('total_commission')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'team_id' => $item->team_id,
                    'team_name' => $item->team?->name ?? 'Unknown Team',
                    'total_commission' => (float) $item->total_commission,
                    'transaction_count' => (int) $item->transaction_count,
                    'formatted_commission' => '$' . number_format($item->total_commission, 2)
                ];
            })
            ->toArray();
    }
} 