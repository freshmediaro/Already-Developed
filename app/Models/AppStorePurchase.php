<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App Store Purchase Model - Manages app store purchases and transactions
 *
 * This model represents purchases made in the app store marketplace within
 * the multi-tenant system, including payment processing, subscription management,
 * and purchase lifecycle tracking.
 *
 * Key features:
 * - Purchase transaction management
 * - Payment processing and tracking
 * - Subscription lifecycle management
 * - Trial period handling
 * - Refund processing and tracking
 * - Multi-tenant purchase isolation
 * - Payment provider integration
 * - Purchase status tracking
 * - Metadata and audit trails
 * - Currency and amount handling
 *
 * Purchase types:
 * - one_time: Single purchase transactions
 * - subscription: Recurring subscription payments
 * - in_app_purchase: In-app purchase transactions
 * - upgrade: Upgrade to premium features
 *
 * Purchase statuses:
 * - pending: Awaiting payment processing
 * - processing: Payment being processed
 * - completed: Successfully completed
 * - failed: Payment processing failed
 * - cancelled: Purchase was cancelled
 * - refunded: Fully refunded
 * - partially_refunded: Partially refunded
 *
 * The model provides:
 * - Comprehensive purchase tracking
 * - Payment processing management
 * - Subscription lifecycle handling
 * - Refund processing
 * - Multi-tenant isolation
 * - Audit trail maintenance
 *
 * @package App\Models
 * @since 1.0.0
 */
class AppStorePurchase extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'app_store_app_id',
        'user_id',
        'team_id',
        'purchase_type',
        'amount',
        'currency',
        'payment_method',
        'payment_provider',
        'payment_intent_id',
        'stripe_payment_intent_id',
        'status',
        'metadata',
        'refunded_at',
        'refund_amount',
        'refund_reason',
        'subscription_id',
        'subscription_start_date',
        'subscription_end_date',
        'is_trial',
        'trial_ends_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'metadata' => 'array',
        'refunded_at' => 'datetime',
        'subscription_start_date' => 'datetime',
        'subscription_end_date' => 'datetime',
        'is_trial' => 'boolean',
        'trial_ends_at' => 'datetime',
    ];

    /**
     * Get available purchase types
     *
     * This method returns the list of available purchase types
     * with their display names for UI presentation.
     *
     * @return array Associative array of purchase type keys and display names
     */
    public static function getPurchaseTypes(): array
    {
        return [
            'one_time' => 'One-time Purchase',
            'subscription' => 'Subscription',
            'in_app_purchase' => 'In-app Purchase',
            'upgrade' => 'Upgrade',
        ];
    }

    /**
     * Get available purchase statuses
     *
     * This method returns the list of available purchase statuses
     * with their display names for UI presentation.
     *
     * @return array Associative array of status keys and display names
     */
    public static function getStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            'partially_refunded' => 'Partially Refunded',
        ];
    }

    /**
     * Get the app that was purchased
     *
     * This relationship provides access to the app store app
     * that was purchased in this transaction.
     *
     * @return BelongsTo Relationship to AppStoreApp model
     */
    public function app(): BelongsTo
    {
        return $this->belongsTo(AppStoreApp::class, 'app_store_app_id');
    }

    /**
     * Get the user who made this purchase
     *
     * This relationship provides access to the user who
     * made the purchase transaction.
     *
     * @return BelongsTo Relationship to User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team context for this purchase
     *
     * This relationship provides access to the team context for
     * the purchase, enabling team-based purchase management.
     *
     * @return BelongsTo Relationship to Team model
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope for completed purchases
     *
     * This scope filters purchases to only include those
     * that have been successfully completed.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for subscription purchases
     *
     * This scope filters purchases to only include those
     * that are subscription-based transactions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeSubscriptions($query)
    {
        return $query->where('purchase_type', 'subscription');
    }

    /**
     * Scope for one-time purchases
     *
     * This scope filters purchases to only include those
     * that are one-time transactions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeOneTime($query)
    {
        return $query->where('purchase_type', 'one_time');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'completed')
                    ->where(function ($q) {
                        $q->where('purchase_type', 'one_time')
                          ->orWhere(function ($sq) {
                              $sq->where('purchase_type', 'subscription')
                                 ->where('subscription_end_date', '>', now());
                          });
                    });
    }

    // Helper Methods
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return in_array($this->status, ['refunded', 'partially_refunded']);
    }

    public function isSubscription(): bool
    {
        return $this->purchase_type === 'subscription';
    }

    public function isOneTime(): bool
    {
        return $this->purchase_type === 'one_time';
    }

    public function isActive(): bool
    {
        if (!$this->isCompleted()) {
            return false;
        }

        if ($this->isOneTime()) {
            return true;
        }

        if ($this->isSubscription()) {
            return $this->subscription_end_date && $this->subscription_end_date->isFuture();
        }

        return false;
    }

    public function isTrialActive(): bool
    {
        return $this->is_trial && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function getFormattedAmount(): string
    {
        $symbol = $this->currency === 'USD' ? '$' : $this->currency;
        return "{$symbol}{$this->amount}";
    }

    public function canBeRefunded(): bool
    {
        if (!$this->isCompleted()) {
            return false;
        }

        if ($this->isRefunded()) {
            return false;
        }

        // Allow refunds within 30 days for one-time purchases
        if ($this->isOneTime()) {
            return $this->created_at->diffInDays(now()) <= 30;
        }

        // Allow refunds for current billing period for subscriptions
        if ($this->isSubscription()) {
            return $this->subscription_end_date && $this->subscription_end_date->isFuture();
        }

        return false;
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => 'failed']);
    }

    public function processRefund(float $amount, string $reason = null): void
    {
        $this->update([
            'status' => $amount >= $this->amount ? 'refunded' : 'partially_refunded',
            'refunded_at' => now(),
            'refund_amount' => $amount,
            'refund_reason' => $reason,
        ]);
    }
} 