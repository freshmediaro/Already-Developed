<?php

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\Team;
use App\Models\Wallet\PlatformCommission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Platform Commission Service - Manages platform revenue and commission tracking
 *
 * This service handles platform commission calculation, tracking, and management
 * for all tenant transactions. It provides comprehensive revenue analytics,
 * commission rate management, and webhook processing for payment providers.
 *
 * Key features:
 * - Commission calculation and recording for all transaction types
 * - Dynamic commission rates based on transaction type and volume
 * - Payment provider fee calculation (Stripe, PayPal, etc.)
 * - Revenue wallet management for tenant teams
 * - Commission analytics and reporting
 * - Webhook processing for payment confirmations
 * - Refund processing and commission reversal
 * - Platform-wide revenue statistics
 *
 * Supported transaction types:
 * - payment: Customer payments from e-commerce
 * - ai_tokens: AI token purchases
 * - app_purchase: App store purchases
 * - subscription: Recurring subscription payments
 * - withdrawal: User withdrawals
 *
 * The service provides:
 * - Automatic commission calculation and recording
 * - Revenue distribution to tenant wallets
 * - Comprehensive analytics and reporting
 * - Webhook processing for payment providers
 * - Refund handling and commission reversal
 * - Volume-based commission rate discounts
 *
 * @package App\Services\Wallet
 * @since 1.0.0
 */
class PlatformCommissionService
{
    /**
     * Record a commission for a tenant transaction
     *
     * This method calculates and records platform commissions for tenant
     * transactions, including platform fees, payment provider fees, and
     * revenue distribution to tenant wallets.
     *
     * The process includes:
     * - Commission rate calculation based on transaction type and amount
     * - Payment provider fee calculation
     * - Revenue distribution to tenant wallets
     * - Comprehensive logging and tracking
     * - Metadata storage for audit trails
     *
     * @param User $user The user making the transaction
     * @param Team|null $team The team context for the transaction
     * @param string $transactionType The type of transaction (payment, ai_tokens, etc.)
     * @param string $transactionId Unique identifier for the transaction
     * @param float $originalAmount The original transaction amount
     * @param string $paymentProvider The payment provider used (stripe, paypal, etc.)
     * @param array $metadata Additional metadata for the transaction
     * @return PlatformCommission|null The recorded commission or null if failed
     * @throws \Exception When commission recording fails
     */
    public function recordCommission(
        User $user,
        ?Team $team,
        string $transactionType,
        string $transactionId,
        float $originalAmount,
        string $paymentProvider = 'stripe',
        array $metadata = []
    ): ?PlatformCommission {
        try {
            // Calculate commission rates
            $platformRate = $this->getPlatformCommissionRate($transactionType, $originalAmount);
            $stripeRate = $this->getStripeCommissionRate($paymentProvider);
            
            // Calculate fees
            $platformCommission = $originalAmount * $platformRate;
            $stripeFee = $paymentProvider === 'stripe' ? ($originalAmount * $stripeRate) : 0;
            $totalFees = $platformCommission + $stripeFee;
            $tenantAmount = $originalAmount - $totalFees;

            // Create commission record
            $commission = PlatformCommission::create([
                'user_id' => $user->id,
                'team_id' => $team?->id,
                'transaction_type' => $transactionType,
                'transaction_id' => $transactionId,
                'original_amount' => $originalAmount,
                'platform_commission' => $platformCommission,
                'stripe_fee' => $stripeFee,
                'total_fees' => $totalFees,
                'tenant_amount' => $tenantAmount,
                'commission_rate' => $platformRate,
                'payment_provider' => $paymentProvider,
                'currency' => 'USD',
                'status' => 'processed',
                'processed_at' => now(),
                'metadata' => $metadata
            ]);

            // Add to tenant's revenue wallet if applicable
            if ($tenantAmount > 0 && $team && $this->shouldCreditToRevenueWallet($transactionType)) {
                $this->creditToRevenueWallet($team, $tenantAmount, $transactionType, $metadata);
            }

            Log::info('Platform commission recorded', [
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'team_id' => $team?->id,
                'transaction_type' => $transactionType,
                'original_amount' => $originalAmount,
                'platform_commission' => $platformCommission,
                'tenant_amount' => $tenantAmount
            ]);

            return $commission;

        } catch (\Exception $e) {
            Log::error('Failed to record platform commission', [
                'user_id' => $user->id,
                'team_id' => $team?->id,
                'transaction_type' => $transactionType,
                'original_amount' => $originalAmount,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get platform commission rate based on transaction type and amount
     *
     * This method calculates the appropriate commission rate based on the
     * transaction type and amount, including volume-based discounts for
     * large transactions.
     *
     * Rate structure:
     * - Base rate: 5% (configurable)
     * - AI tokens: 2.5% (50% of base rate)
     * - App purchases: 7.5% (150% of base rate)
     * - Withdrawals: 2% (fixed rate)
     * - Volume discounts: 10% for >$1000, 15% for >$10000
     *
     * @param string $transactionType The type of transaction
     * @param float $amount The transaction amount
     * @return float The calculated commission rate (0.0 to 1.0)
     */
    public function getPlatformCommissionRate(string $transactionType, float $amount): float
    {
        $baseRate = config('wallet.platform.commission_rate', 0.05); // 5% default

        // Different rates for different transaction types
        $rates = [
            'payment' => $baseRate,
            'ai_tokens' => $baseRate * 0.5, // Lower rate for AI tokens
            'app_purchase' => $baseRate * 1.5, // Higher rate for app purchases
            'subscription' => $baseRate,
            'withdrawal' => config('wallet.withdrawals.fee_rate', 0.02), // 2% withdrawal fee
        ];

        $rate = $rates[$transactionType] ?? $baseRate;

        // Volume-based discounts for large transactions
        if ($amount > 1000) {
            $rate *= 0.9; // 10% discount for transactions over $1000
        }
        if ($amount > 10000) {
            $rate *= 0.85; // Additional 15% discount for transactions over $10000
        }

        return $rate;
    }

    /**
     * Get payment provider commission rate
     *
     * This method returns the commission rate for different payment providers,
     * including Stripe, PayPal, Square, and other payment processors.
     *
     * @param string $provider The payment provider name
     * @return float The provider's commission rate (0.0 to 1.0)
     */
    public function getStripeCommissionRate(string $provider): float
    {
        $rates = [
            'stripe' => config('wallet.platform.stripe_rate', 0.029), // 2.9%
            'paypal' => 0.029, // Similar to Stripe
            'square' => 0.026, // Slightly lower
            'authorize_net' => 0.025,
            'internal' => 0, // No external fees for internal transactions
        ];

        return $rates[$provider] ?? 0;
    }

    /**
     * Check if transaction should credit to team's revenue wallet
     *
     * This method determines whether a transaction should result in
     * revenue being credited to the team's revenue wallet, based on
     * the transaction type.
     *
     * @param string $transactionType The type of transaction
     * @return bool True if revenue should be credited, false otherwise
     */
    private function shouldCreditToRevenueWallet(string $transactionType): bool
    {
        return in_array($transactionType, [
            'payment', // Customer payments from e-commerce
            'subscription', // Recurring subscription payments
            // Note: ai_tokens and app_purchase are internal, not external revenue
        ]);
    }

    /**
     * Credit amount to team's revenue wallet
     *
     * This method credits the tenant's share of revenue to their
     * revenue wallet, enabling teams to track and manage their earnings.
     *
     * @param Team $team The team to credit revenue to
     * @param float $amount The amount to credit
     * @param string $transactionType The type of transaction
     * @param array $metadata Additional metadata for the transaction
     */
    private function creditToRevenueWallet(Team $team, float $amount, string $transactionType, array $metadata = []): void
    {
        try {
            $revenueWallet = $team->getRevenueWallet();
            
            $revenueWallet->deposit($amount, [
                'type' => 'customer_payment',
                'description' => "Revenue from {$transactionType}",
                'metadata' => array_merge($metadata, [
                    'credited_at' => now()->toISOString(),
                    'transaction_type' => $transactionType,
                    'team_id' => $team->id,
                ])
            ]);

            Log::info('Revenue credited to team wallet', [
                'team_id' => $team->id,
                'amount' => $amount,
                'transaction_type' => $transactionType
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to credit revenue to team wallet', [
                'team_id' => $team->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get commission analytics for a team
     *
     * This method provides comprehensive commission analytics for a specific
     * team, including transaction volume, commission amounts, and breakdowns
     * by transaction type and payment provider.
     *
     * @param Team $team The team to get analytics for
     * @param string $period The time period for analytics (day, week, month, year)
     * @return array Comprehensive analytics data including summaries and breakdowns
     */
    public function getTeamCommissionAnalytics(Team $team, string $period = 'month'): array
    {
        $startDate = match($period) {
            'day' => Carbon::now()->startOfDay(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };
        $endDate = Carbon::now();

        $commissions = PlatformCommission::forTeam($team->id)
            ->inDateRange($startDate, $endDate)
            ->processed()
            ->get();

        $totalVolume = $commissions->sum('original_amount');
        $totalCommissions = $commissions->sum('platform_commission');
        $totalStripeFees = $commissions->sum('stripe_fee');
        $totalNetRevenue = $commissions->sum('tenant_amount');
        $transactionCount = $commissions->count();

        // Group by transaction type
        $byType = $commissions->groupBy('transaction_type')->map(function ($group, $type) {
            return [
                'type' => $type,
                'count' => $group->count(),
                'volume' => $group->sum('original_amount'),
                'commission' => $group->sum('platform_commission'),
                'net_revenue' => $group->sum('tenant_amount'),
            ];
        })->values();

        // Group by provider
        $byProvider = $commissions->groupBy('payment_provider')->map(function ($group, $provider) {
            return [
                'provider' => $provider,
                'count' => $group->count(),
                'volume' => $group->sum('original_amount'),
                'commission' => $group->sum('platform_commission'),
                'stripe_fees' => $group->sum('stripe_fee'),
            ];
        })->values();

        return [
            'period' => $period,
            'start_date' => $startDate->toISOString(),
            'end_date' => $endDate->toISOString(),
            'summary' => [
                'total_volume' => $totalVolume,
                'total_commissions' => $totalCommissions,
                'total_stripe_fees' => $totalStripeFees,
                'total_net_revenue' => $totalNetRevenue,
                'transaction_count' => $transactionCount,
                'average_transaction' => $transactionCount > 0 ? $totalVolume / $transactionCount : 0,
                'effective_commission_rate' => $totalVolume > 0 ? ($totalCommissions / $totalVolume) * 100 : 0,
            ],
            'by_transaction_type' => $byType,
            'by_provider' => $byProvider,
            'daily_breakdown' => $this->getDailyBreakdown($commissions, $startDate, $endDate),
        ];
    }

    /**
     * Get daily breakdown of commissions
     *
     * This method provides a daily breakdown of commission data for
     * charting and trend analysis purposes.
     *
     * @param \Illuminate\Database\Eloquent\Collection $commissions The commission collection
     * @param Carbon $startDate The start date for the breakdown
     * @param Carbon $endDate The end date for the breakdown
     * @return array Array of daily commission data
     */
    private function getDailyBreakdown($commissions, Carbon $startDate, Carbon $endDate): array
    {
        $days = [];
        $current = $startDate->copy();
        
        while ($current <= $endDate) {
            $dayCommissions = $commissions->filter(function ($commission) use ($current) {
                return $commission->created_at->isSameDay($current);
            });

            $days[] = [
                'date' => $current->toDateString(),
                'volume' => $dayCommissions->sum('original_amount'),
                'commission' => $dayCommissions->sum('platform_commission'),
                'net_revenue' => $dayCommissions->sum('tenant_amount'),
                'transaction_count' => $dayCommissions->count(),
            ];

            $current->addDay();
        }

        return $days;
    }

    /**
     * Process Stripe webhook for commission calculation
     *
     * This method processes Stripe webhook events to automatically
     * record commissions when payments are confirmed by Stripe.
     *
     * @param array $webhookData The Stripe webhook payload
     * @return PlatformCommission|null The recorded commission or null if not applicable
     */
    public function processStripeWebhook(array $webhookData): ?PlatformCommission
    {
        try {
            $eventType = $webhookData['type'] ?? null;
            $eventData = $webhookData['data']['object'] ?? [];

            if (!in_array($eventType, ['payment_intent.succeeded', 'charge.succeeded'])) {
                return null; // Not a payment event we care about
            }

            // Extract relevant data from Stripe webhook
            $amount = ($eventData['amount'] ?? 0) / 100; // Convert from cents
            $stripeChargeId = $eventData['id'] ?? null;
            $metadata = $eventData['metadata'] ?? [];

            // Find user and team from metadata
            $userId = $metadata['user_id'] ?? null;
            $teamId = $metadata['team_id'] ?? null;
            $transactionType = $metadata['transaction_type'] ?? 'payment';

            if (!$userId) {
                Log::warning('Stripe webhook missing user_id in metadata', ['webhook_data' => $webhookData]);
                return null;
            }

            $user = User::find($userId);
            $team = $teamId ? Team::find($teamId) : null;

            if (!$user) {
                Log::warning('User not found for Stripe webhook', ['user_id' => $userId]);
                return null;
            }

            // Record the commission
            return $this->recordCommission(
                $user,
                $team,
                $transactionType,
                $stripeChargeId,
                $amount,
                'stripe',
                [
                    'stripe_event_id' => $webhookData['id'] ?? null,
                    'stripe_charge_id' => $stripeChargeId,
                    'webhook_processed_at' => now()->toISOString(),
                ]
            );

        } catch (\Exception $e) {
            Log::error('Failed to process Stripe webhook for commission', [
                'webhook_data' => $webhookData,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get platform-wide commission statistics
     *
     * This method provides comprehensive platform-wide commission statistics,
     * including total revenue, commission amounts, and top-performing teams.
     *
     * @param string $period The time period for statistics (day, week, month, year)
     * @return array Platform-wide commission statistics and analytics
     */
    public function getPlatformCommissionStats(string $period = 'month'): array
    {
        $startDate = match($period) {
            'day' => Carbon::now()->startOfDay(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };
        $endDate = Carbon::now();

        $totalCommission = PlatformCommission::getTotalCommissionForPeriod($startDate, $endDate);
        $totalVolume = PlatformCommission::getTotalVolumeForPeriod($startDate, $endDate);
        $commissionByProvider = PlatformCommission::getCommissionByProvider($startDate, $endDate);
        $commissionByType = PlatformCommission::getCommissionByTransactionType($startDate, $endDate);
        $topTeams = PlatformCommission::getTopEarningTeams($startDate, $endDate, 10);

        return [
            'period' => $period,
            'start_date' => $startDate->toISOString(),
            'end_date' => $endDate->toISOString(),
            'total_commission' => $totalCommission,
            'total_volume' => $totalVolume,
            'average_commission_rate' => $totalVolume > 0 ? ($totalCommission / $totalVolume) * 100 : 0,
            'commission_by_provider' => $commissionByProvider,
            'commission_by_transaction_type' => $commissionByType,
            'top_earning_teams' => $topTeams,
            'formatted_total_commission' => '$' . number_format($totalCommission, 2),
            'formatted_total_volume' => '$' . number_format($totalVolume, 2),
        ];
    }

    /**
     * Refund commission when original transaction is refunded
     *
     * This method handles commission refunds when the original transaction
     * is refunded, including status updates and revenue reversal.
     *
     * @param string $originalTransactionId The original transaction ID to refund
     * @return bool True if refund was successful, false otherwise
     */
    public function refundCommission(string $originalTransactionId): bool
    {
        try {
            $commission = PlatformCommission::where('transaction_id', $originalTransactionId)->first();
            
            if (!$commission) {
                Log::warning('Commission not found for refund', ['transaction_id' => $originalTransactionId]);
                return false;
            }

            if ($commission->status === 'refunded') {
                return true; // Already refunded
            }

            // Update status to refunded
            $commission->update([
                'status' => 'refunded',
                'processed_at' => now(),
                'metadata' => array_merge($commission->metadata ?? [], [
                    'refunded_at' => now()->toISOString(),
                    'original_status' => $commission->status
                ])
            ]);

            // Reverse the revenue credit if applicable
            if ($commission->team && $commission->tenant_amount > 0) {
                $this->reverseRevenueCredit($commission);
            }

            Log::info('Commission refunded', [
                'commission_id' => $commission->id,
                'original_amount' => $commission->original_amount,
                'refund_amount' => $commission->tenant_amount
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to refund commission', [
                'transaction_id' => $originalTransactionId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Reverse revenue credit when commission is refunded
     *
     * This method reverses the revenue credit that was given to a team
     * when their commission is refunded, ensuring accurate revenue tracking.
     *
     * @param PlatformCommission $commission The commission being refunded
     */
    private function reverseRevenueCredit(PlatformCommission $commission): void
    {
        try {
            $team = $commission->team;
            $revenueWallet = $team->getRevenueWallet();
            
            if ($revenueWallet->balance >= $commission->tenant_amount) {
                $revenueWallet->withdraw($commission->tenant_amount, [
                    'type' => 'refund_reversal',
                    'description' => 'Revenue reversal due to refund',
                    'metadata' => [
                        'commission_id' => $commission->id,
                        'original_transaction_id' => $commission->transaction_id,
                        'reversed_at' => now()->toISOString(),
                    ]
                ]);

                Log::info('Revenue credit reversed', [
                    'team_id' => $team->id,
                    'amount' => $commission->tenant_amount,
                    'commission_id' => $commission->id
                ]);
            } else {
                Log::warning('Insufficient balance to reverse revenue credit', [
                    'team_id' => $team->id,
                    'required_amount' => $commission->tenant_amount,
                    'current_balance' => $revenueWallet->balance
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to reverse revenue credit', [
                'commission_id' => $commission->id,
                'error' => $e->getMessage()
            ]);
        }
    }
} 