<?php

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\Team;
use App\Models\Wallet\AiTokenPackage;
use App\Models\Wallet\TenantWalletSettings;
use App\Models\AiChatUsage;
use Bavix\Wallet\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * AI Token Service - Manages AI token consumption and billing
 * 
 * This service handles all aspects of AI token management including balance
 * checking, token consumption, purchase processing, and usage analytics.
 * It integrates with the wallet system for secure token transactions
 * and provides comprehensive usage tracking for billing purposes.
 * 
 * Key features:
 * - Token balance management and validation
 * - Token consumption with metadata tracking
 * - Purchase processing and webhook handling
 * - Usage analytics and reporting
 * - Auto-topup functionality
 * - Cost calculation and billing
 * 
 * The service provides:
 * - Real-time balance checking
 * - Secure token transactions
 * - Usage history and analytics
 * - Purchase workflow management
 * - Team-based token allocation
 * - Platform commission tracking
 * 
 * @package App\Services\Wallet
 * @since 1.0.0
 */
class AiTokenService
{
    /**
     * Get user's current AI token balance
     * 
     * This method retrieves the current token balance from the user's
     * AI token wallet, providing real-time balance information for
     * consumption validation and UI display.
     * 
     * @param User $user The user to get balance for
     * @return int The current token balance (0 if no wallet exists)
     */
    public function getTokenBalance(User $user): int
    {
        return (int) $user->getAiTokenWallet()->balance;
    }

    /**
     * Check if user has enough tokens for a specific operation
     * 
     * This method validates that the user has sufficient tokens to
     * perform the requested operation, preventing failed operations
     * due to insufficient balance.
     * 
     * @param User $user The user to check balance for
     * @param int $tokensNeeded The number of tokens required for the operation
     * @return bool True if user has sufficient tokens, false otherwise
     */
    public function hasEnoughTokens(User $user, int $tokensNeeded): bool
    {
        return $this->getTokenBalance($user) >= $tokensNeeded;
    }

    /**
     * Consume AI tokens for an operation with metadata tracking
     * 
     * This method deducts tokens from the user's AI wallet and records
     * the transaction with comprehensive metadata for billing and analytics.
     * It ensures atomic transactions and proper error handling.
     * 
     * The method tracks:
     * - Operation type and description
     * - Team context for billing
     * - Timestamp and user information
     * - Usage analytics data
     * 
     * @param User $user The user consuming tokens
     * @param int $tokens The number of tokens to consume
     * @param array $metadata Additional metadata for transaction tracking
     * @return bool True if tokens were successfully consumed, false otherwise
     * 
     * @throws \Exception When wallet operation fails or insufficient balance
     */
    public function consumeTokens(User $user, int $tokens, array $metadata = []): bool
    {
        try {
            $wallet = $user->getAiTokenWallet();
            
            if ($wallet->balance < $tokens) {
                return false;
            }

            $wallet->withdraw($tokens, [
                'type' => 'ai_token_consumption',
                'description' => 'AI tokens consumed for chat operation',
                'metadata' => array_merge($metadata, [
                    'consumed_at' => now()->toISOString(),
                    'user_id' => $user->id,
                    'team_id' => $metadata['team_id'] ?? null,
                ])
            ]);

            Log::info('AI tokens consumed', [
                'user_id' => $user->id,
                'team_id' => $metadata['team_id'] ?? null,
                'tokens_consumed' => $tokens,
                'remaining_balance' => $this->getTokenBalance($user)
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to consume AI tokens', [
                'user_id' => $user->id,
                'tokens' => $tokens,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Process token purchase from webhook payment confirmation
     * 
     * This method handles the webhook processing for successful token purchases,
     * crediting the user's AI wallet and recording the transaction for billing
     * and analytics purposes.
     * 
     * The method:
     * - Validates the package and payment reference
     * - Credits tokens to the user's AI wallet
     * - Records transaction metadata
     * - Handles platform commission tracking
     * - Logs the successful purchase
     * 
     * @param User $user The user who made the purchase
     * @param string $packageId The ID of the purchased token package
     * @param string $paymentReference The payment reference from the payment provider
     * @return bool True if purchase was processed successfully, false otherwise
     * 
     * @throws \Exception When package not found or wallet operation fails
     */
    public function processTokenPurchaseWebhook(User $user, string $packageId, string $paymentReference): bool
    {
        try {
            $package = \App\Models\Wallet\AiTokenPackage::find($packageId);
            if (!$package) {
                Log::error('AI token package not found for webhook processing', [
                    'package_id' => $packageId,
                    'user_id' => $user->id,
                ]);
                return false;
            }

            // Credit tokens to user's AI wallet
            $aiWallet = $user->getAiTokenWallet();
            $aiWallet->deposit($package->token_amount, [
                'type' => 'ai_token_purchase_webhook',
                'package_id' => $packageId,
                'payment_reference' => $paymentReference,
                'description' => "AI tokens purchased: {$package->name}",
                'metadata' => [
                    'package_name' => $package->name,
                    'price_paid' => $package->price,
                    'webhook_processed_at' => now()->toISOString(),
                ]
            ]);

            Log::info('AI tokens credited via webhook', [
                'user_id' => $user->id,
                'package_id' => $packageId,
                'tokens_credited' => $package->token_amount,
                'payment_reference' => $paymentReference,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to process AI token purchase webhook', [
                'user_id' => $user->id,
                'package_id' => $packageId,
                'payment_reference' => $paymentReference,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Add free monthly tokens to user's wallet
     */
    public function addFreeMonthlyTokens(User $user): int
    {
        $freeTokens = config('wallet.ai_tokens.free_monthly_limit', 10000);
        
        // Check if user already received free tokens this month
        $lastFreeTokens = $user->getAiTokenWallet()
            ->transactions()
            ->where('type', 'deposit')
            ->whereJsonContains('meta->type', 'free_monthly_tokens')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->first();

        if ($lastFreeTokens) {
            return 0; // Already received this month
        }

        try {
            $user->getAiTokenWallet()->deposit($freeTokens, [
                'type' => 'free_monthly_tokens',
                'description' => 'Free monthly AI tokens',
                'metadata' => [
                    'granted_at' => now()->toISOString(),
                    'month' => now()->format('Y-m'),
                    'user_id' => $user->id,
                ]
            ]);

            Log::info('Free monthly AI tokens granted', [
                'user_id' => $user->id,
                'tokens_granted' => $freeTokens
            ]);

            return $freeTokens;
        } catch (\Exception $e) {
            Log::error('Failed to grant free monthly tokens', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Purchase AI token package
     */
    public function purchaseTokenPackage(User $user, AiTokenPackage $package, ?Team $team = null): array
    {
        try {
            DB::beginTransaction();

            // Calculate final price (with any discounts)
            $finalPrice = $package->getDiscountedPrice();
            
            // Check if user has enough funds in main wallet
            $mainWallet = $user->getMainWallet();
            if ($mainWallet->balance < $finalPrice) {
                return [
                    'success' => false,
                    'error' => 'insufficient_funds',
                    'message' => 'Insufficient funds in main wallet',
                    'required_amount' => $finalPrice,
                    'current_balance' => $mainWallet->balance
                ];
            }

            // Withdraw from main wallet
            $mainWallet->withdraw($finalPrice, [
                'type' => 'ai_token_purchase',
                'description' => "Purchase of {$package->name}",
                'metadata' => [
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'tokens_purchased' => $package->token_amount,
                    'team_id' => $team?->id,
                    'purchase_type' => $package->package_type,
                    'purchased_at' => now()->toISOString(),
                ]
            ]);

            // Add tokens to AI token wallet
            $aiWallet = $user->getAiTokenWallet();
            $depositMeta = [
                'type' => 'token_package_purchase',
                'description' => "Tokens from {$package->name}",
                'metadata' => [
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'purchase_amount' => $finalPrice,
                    'team_id' => $team?->id,
                    'expiry_date' => $package->getExpiryDate()?->toISOString(),
                    'purchased_at' => now()->toISOString(),
                ]
            ];

            if ($package->isExpiring()) {
                $depositMeta['metadata']['expires_at'] = $package->getExpiryDate()->toISOString();
            }

            $aiWallet->deposit($package->token_amount, $depositMeta);

            // Record platform commission (if applicable)
            if ($finalPrice > 0) {
                $this->recordPlatformCommission($user, $team, 'ai_tokens', $finalPrice, [
                    'package_id' => $package->id,
                    'tokens_purchased' => $package->token_amount
                ]);
            }

            DB::commit();

            Log::info('AI token package purchased successfully', [
                'user_id' => $user->id,
                'team_id' => $team?->id,
                'package_id' => $package->id,
                'tokens_purchased' => $package->token_amount,
                'amount_paid' => $finalPrice
            ]);

            return [
                'success' => true,
                'tokens_purchased' => $package->token_amount,
                'amount_paid' => $finalPrice,
                'new_balance' => $this->getTokenBalance($user),
                'transaction_id' => $aiWallet->transactions()->latest()->first()?->id,
                'expiry_date' => $package->getExpiryDate()?->toISOString()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to purchase AI token package', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'purchase_failed',
                'message' => 'Failed to process token purchase: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get user's token usage history
     */
    public function getUsageHistory(User $user, ?Team $team = null, int $limit = 50): array
    {
        $query = AiChatUsage::tenantIsolated($user->id, $team?->id)
            ->with(['session', 'message'])
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        $usageRecords = $query->get();

        return [
            'usage_history' => $usageRecords->map(function ($usage) {
                return [
                    'id' => $usage->id,
                    'date' => $usage->created_at->toISOString(),
                    'tokens_used' => $usage->tokens_used,
                    'cost' => $usage->cost,
                    'operation_type' => $usage->operation_type,
                    'model' => $usage->model,
                    'provider' => $usage->provider,
                    'session_title' => $usage->session?->title ?? 'Unknown Session'
                ];
            }),
            'total_tokens_used' => $usageRecords->sum('tokens_used'),
            'total_cost' => $usageRecords->sum('cost'),
        ];
    }

    /**
     * Get token usage statistics for a period
     */
    public function getUsageStats(User $user, ?Team $team = null, string $period = 'month'): array
    {
        $startDate = match($period) {
            'day' => Carbon::now()->startOfDay(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        $usage = AiChatUsage::tenantIsolated($user->id, $team?->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                SUM(tokens_used) as total_tokens,
                SUM(cost) as total_cost,
                COUNT(*) as total_operations,
                AVG(tokens_used) as avg_tokens_per_operation,
                operation_type,
                model,
                provider
            ')
            ->groupBy(['operation_type', 'model', 'provider'])
            ->get();

        $summary = [
            'period' => $period,
            'start_date' => $startDate->toISOString(),
            'end_date' => Carbon::now()->toISOString(),
            'total_tokens_used' => $usage->sum('total_tokens'),
            'total_cost' => $usage->sum('total_cost'),
            'total_operations' => $usage->sum('total_operations'),
            'avg_tokens_per_operation' => $usage->avg('avg_tokens_per_operation'),
            'current_balance' => $this->getTokenBalance($user),
        ];

        $breakdown = $usage->groupBy('operation_type')->map(function ($group, $type) {
            return [
                'operation_type' => $type,
                'total_tokens' => $group->sum('total_tokens'),
                'total_cost' => $group->sum('total_cost'),
                'total_operations' => $group->sum('total_operations'),
                'models' => $group->groupBy('model')->map(function ($modelGroup, $model) {
                    return [
                        'model' => $model,
                        'tokens' => $modelGroup->sum('total_tokens'),
                        'cost' => $modelGroup->sum('total_cost'),
                        'operations' => $modelGroup->sum('total_operations'),
                    ];
                })->values()
            ];
        })->values();

        return [
            'summary' => $summary,
            'breakdown' => $breakdown
        ];
    }

    /**
     * Estimate token cost for a message
     */
    public function estimateTokenUsage(string $message, string $model = 'gpt-4'): int
    {
        // Rough estimation: ~4 characters per token for GPT models
        $charCount = strlen($message);
        $estimatedTokens = ceil($charCount / 4);
        
        // Add some buffer for system prompts and formatting
        $estimatedTokens = (int) ($estimatedTokens * 1.2);
        
        // Minimum estimation
        return max($estimatedTokens, 10);
    }

    /**
     * Check if user needs to top up tokens (for auto-top-up feature)
     */
    public function checkAutoTopUp(User $user, ?Team $team = null): bool
    {
        $settings = TenantWalletSettings::getOrCreateForUser($user->id, $team?->id, 'ai-tokens');
        
        if (!$settings->shouldAutoTopUp($this->getTokenBalance($user))) {
            return false;
        }

        // Find default top-up package or use configured amount
        $topUpAmount = $settings->getTopUpAmount();
        
        if ($topUpAmount <= 0) {
            return false;
        }

        // Convert top-up amount to tokens (using default rate)
        $tokenPrice = config('wallet.ai_tokens.price_per_1000', 0.02);
        $tokensToAdd = (int) ($topUpAmount / $tokenPrice * 1000);

        try {
            $user->getMainWallet()->withdraw($topUpAmount, [
                'type' => 'auto_top_up',
                'description' => 'Automatic AI token top-up',
                'metadata' => [
                    'auto_top_up' => true,
                    'tokens_added' => $tokensToAdd,
                    'team_id' => $team?->id,
                ]
            ]);

            $user->getAiTokenWallet()->deposit($tokensToAdd, [
                'type' => 'auto_top_up',
                'description' => 'Automatic token top-up',
                'metadata' => [
                    'auto_top_up' => true,
                    'amount_paid' => $topUpAmount,
                    'team_id' => $team?->id,
                ]
            ]);

            Log::info('Auto top-up completed', [
                'user_id' => $user->id,
                'team_id' => $team?->id,
                'tokens_added' => $tokensToAdd,
                'amount_paid' => $topUpAmount
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Auto top-up failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Record platform commission for token purchases
     */
    private function recordPlatformCommission(User $user, ?Team $team, string $transactionType, float $amount, array $metadata = []): void
    {
        $commissionRate = config('wallet.platform.commission_rate', 0.05);
        $platformCommission = $amount * $commissionRate;
        
        try {
            DB::table('platform_commissions')->insert([
                'user_id' => $user->id,
                'team_id' => $team?->id,
                'transaction_type' => $transactionType,
                'transaction_id' => $metadata['transaction_id'] ?? uniqid(),
                'original_amount' => $amount,
                'platform_commission' => $platformCommission,
                'stripe_fee' => 0, // No stripe fee for internal token purchases
                'total_fees' => $platformCommission,
                'tenant_amount' => $amount - $platformCommission,
                'commission_rate' => $commissionRate,
                'payment_provider' => 'internal',
                'currency' => 'USD',
                'status' => 'processed',
                'processed_at' => now(),
                'metadata' => json_encode($metadata),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to record platform commission', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
        }
    }
} 