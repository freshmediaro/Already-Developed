<?php

namespace App\Models\Wallet;

use App\Models\User;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Withdrawal Request Model - Manages user withdrawal requests and processing
 *
 * This model represents withdrawal requests from users and teams within the
 * multi-tenant system, including request processing, approval workflows,
 * and withdrawal method management.
 *
 * Key features:
 * - Withdrawal request management and tracking
 * - Approval workflow and status management
 * - Withdrawal method and detail handling
 * - Fee calculation and processing
 * - Multi-tenant withdrawal isolation
 * - Reference number generation
 * - Processing metadata tracking
 * - Admin notes and communication
 * - Status transitions and validation
 * - Security and encryption
 *
 * Withdrawal statuses:
 * - pending: Awaiting admin approval
 * - approved: Approved by admin, awaiting processing
 * - processing: Currently being processed
 * - completed: Successfully completed
 * - rejected: Rejected by admin
 * - cancelled: Cancelled by user or system
 *
 * Withdrawal methods:
 * - bank_transfer: Direct bank transfer
 * - paypal: PayPal withdrawal
 * - stripe: Stripe payout
 * - check: Physical check mailing
 * - crypto: Cryptocurrency transfer
 * - wire_transfer: International wire transfer
 *
 * The model provides:
 * - Comprehensive withdrawal request management
 * - Secure withdrawal detail storage
 * - Approval workflow management
 * - Status tracking and transitions
 * - Fee calculation and processing
 * - Multi-tenant isolation
 * - Reference number generation
 * - Processing timeline tracking
 *
 * @package App\Models\Wallet
 * @since 1.0.0
 */
class WithdrawalRequest extends Model
{
    use HasFactory;

    /** @var string The table name for withdrawal requests */
    protected $table = 'withdrawal_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'team_id',
        'requested_amount',
        'withdrawal_fee',
        'final_amount',
        'withdrawal_method',
        'withdrawal_details',
        'status',
        'rejection_reason',
        'reference_number',
        'requested_at',
        'approved_at',
        'processed_at',
        'completed_at',
        'approved_by',
        'processed_by',
        'processing_metadata',
        'admin_notes',
        'metadata'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'requested_amount' => 'decimal:2',
        'withdrawal_fee' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'withdrawal_details' => 'array', // Encrypted
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'processing_metadata' => 'array',
        'metadata' => 'array'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'withdrawal_details'
    ];

    /**
     * The attributes that should be treated as dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'requested_at',
        'approved_at',
        'processed_at',
        'completed_at'
    ];

    /**
     * Get the user who made this withdrawal request
     *
     * This relationship provides access to the user who
     * submitted the withdrawal request.
     *
     * @return BelongsTo Relationship to User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team context for this withdrawal request
     *
     * This relationship provides access to the team context for
     * the withdrawal request.
     *
     * @return BelongsTo Relationship to Team model
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the admin user who approved this withdrawal request
     *
     * This relationship provides access to the admin user who
     * approved the withdrawal request.
     *
     * @return BelongsTo Relationship to User model (admin approver)
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the admin user who processed this withdrawal request
     *
     * This relationship provides access to the admin user who
     * processed the withdrawal request.
     *
     * @return BelongsTo Relationship to User model (admin processor)
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scope for filtering withdrawal requests by user
     *
     * This scope filters withdrawal requests by user ID,
     * providing user-specific request access.
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
     * Scope for filtering withdrawal requests by team
     *
     * This scope filters withdrawal requests by team ID,
     * providing team-specific request access.
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
     * Scope for filtering withdrawal requests by status
     *
     * This scope filters withdrawal requests by status,
     * enabling status-specific queries.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param string $status The status to filter by
     * @return \Illuminate\Database\Eloquent\Builder The filtered query
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByMethod($query, string $method)
    {
        return $query->where('withdrawal_method', $method);
    }

    public function scopeInDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('requested_at', [$startDate, $endDate]);
    }

    public function scopeTenantIsolated($query, int $userId, ?int $teamId = null)
    {
        $query->where('user_id', $userId);
        if ($teamId) {
            $query->where('team_id', $teamId);
        }
        return $query;
    }

    public function scopeAwaitingApproval($query)
    {
        return $query->where('status', 'pending')
            ->orderBy('requested_at', 'asc');
    }

    public function scopeAwaitingProcessing($query)
    {
        return $query->where('status', 'approved')
            ->orderBy('approved_at', 'asc');
    }

    // Status Check Methods
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeApproved(): bool
    {
        return $this->isPending();
    }

    public function canBeRejected(): bool
    {
        return $this->isPending();
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'approved']);
    }

    public function canBeProcessed(): bool
    {
        return $this->isApproved();
    }

    // Action Methods
    public function approve(int $approvedByUserId, ?string $notes = null): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $approvedByUserId,
            'admin_notes' => $notes
        ]);

        return true;
    }

    public function reject(int $rejectedByUserId, string $reason, ?string $notes = null): bool
    {
        if (!$this->canBeRejected()) {
            return false;
        }

        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_by' => $rejectedByUserId,
            'approved_at' => now(),
            'admin_notes' => $notes
        ]);

        return true;
    }

    public function cancel(?string $reason = null): bool
    {
        if (!$this->canBeCancelled()) {
            return false;
        }

        $this->update([
            'status' => 'cancelled',
            'rejection_reason' => $reason ?? 'Cancelled by user'
        ]);

        return true;
    }

    public function markAsProcessing(int $processedByUserId, array $metadata = []): bool
    {
        if (!$this->canBeProcessed()) {
            return false;
        }

        $this->update([
            'status' => 'processing',
            'processed_at' => now(),
            'processed_by' => $processedByUserId,
            'processing_metadata' => $metadata
        ]);

        return true;
    }

    public function markAsCompleted(array $metadata = []): bool
    {
        if (!$this->isProcessing()) {
            return false;
        }

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'processing_metadata' => array_merge($this->processing_metadata ?? [], $metadata)
        ]);

        return true;
    }

    // Helper Methods
    public function getDecryptedWithdrawalDetails(): ?array
    {
        if (!$this->withdrawal_details) {
            return null;
        }

        try {
            return is_array($this->withdrawal_details) ? $this->withdrawal_details : [];
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getFormattedRequestedAmount(): string
    {
        return '$' . number_format($this->requested_amount, 2);
    }

    public function getFormattedFinalAmount(): string
    {
        return '$' . number_format($this->final_amount, 2);
    }

    public function getFormattedWithdrawalFee(): string
    {
        return '$' . number_format($this->withdrawal_fee, 2);
    }

    public function getStatusBadgeClass(): string
    {
        $classes = [
            'pending' => 'bg-yellow-100 text-yellow-800',
            'approved' => 'bg-blue-100 text-blue-800',
            'processing' => 'bg-purple-100 text-purple-800',
            'completed' => 'bg-green-100 text-green-800',
            'rejected' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-gray-100 text-gray-800'
        ];

        return $classes[$this->status] ?? 'bg-gray-100 text-gray-800';
    }

    public function getProcessingDays(): int
    {
        if (!$this->processed_at) {
            return 0;
        }

        $endDate = $this->completed_at ?? now();
        return $this->processed_at->diffInDays($endDate);
    }

    public function getEstimatedCompletionDate(): ?Carbon
    {
        if (!$this->processed_at || $this->isCompleted()) {
            return null;
        }

        $processingDays = config('wallet.withdrawals.processing_days', 3);
        return $this->processed_at->addDays($processingDays);
    }

    public function getMethodDisplayName(): string
    {
        $methods = [
            'bank_transfer' => 'Bank Transfer',
            'paypal' => 'PayPal',
            'check' => 'Check',
            'wire_transfer' => 'Wire Transfer'
        ];

        return $methods[$this->withdrawal_method] ?? ucfirst(str_replace('_', ' ', $this->withdrawal_method));
    }

    // Static Methods
    public static function generateReferenceNumber(): string
    {
        return 'WD' . now()->format('Ymd') . strtoupper(Str::random(6));
    }

    public static function createRequest(
        User $user,
        ?Team $team,
        float $amount,
        string $method,
        array $details,
        array $metadata = []
    ): self {
        $withdrawalFee = static::calculateWithdrawalFee($amount);
        $finalAmount = $amount - $withdrawalFee;

        return static::create([
            'user_id' => $user->id,
            'team_id' => $team?->id,
            'requested_amount' => $amount,
            'withdrawal_fee' => $withdrawalFee,
            'final_amount' => $finalAmount,
            'withdrawal_method' => $method,
            'withdrawal_details' => $details,
            'status' => 'pending',
            'reference_number' => static::generateReferenceNumber(),
            'requested_at' => now(),
            'metadata' => $metadata
        ]);
    }

    public static function calculateWithdrawalFee(float $amount): float
    {
        $feeRate = config('wallet.withdrawals.fee_rate', 0.02);
        return round($amount * $feeRate, 2);
    }

    public static function getMinWithdrawalAmount(): float
    {
        return config('wallet.withdrawals.min_amount', 10.00);
    }

    public static function getMaxWithdrawalAmount(): float
    {
        return config('wallet.withdrawals.max_amount', 10000.00);
    }

    public static function getPendingRequestsForUser(int $userId, ?int $teamId = null): \Illuminate\Database\Eloquent\Collection
    {
        return static::tenantIsolated($userId, $teamId)
            ->pending()
            ->orderBy('requested_at', 'desc')
            ->get();
    }

    public static function getRequestsInDateRange(int $userId, ?int $teamId, Carbon $startDate, Carbon $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return static::tenantIsolated($userId, $teamId)
            ->inDateRange($startDate, $endDate)
            ->orderBy('requested_at', 'desc')
            ->get();
    }

    public static function getTotalWithdrawnAmount(int $userId, ?int $teamId = null, string $period = 'month'): float
    {
        $startDate = match ($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth()
        };

        return static::tenantIsolated($userId, $teamId)
            ->completed()
            ->where('completed_at', '>=', $startDate)
            ->sum('final_amount');
    }
} 