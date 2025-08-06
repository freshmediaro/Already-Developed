<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Team;
use App\Models\Wallet\AiTokenPackage;
use App\Services\Wallet\AiTokenService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * AI Token Controller - Manages AI token operations and purchases
 *
 * This controller handles all AI token-related operations including balance
 * checking, package purchases, usage tracking, and token estimation within
 * the multi-tenant system. It provides comprehensive AI token management.
 *
 * Key features:
 * - AI token balance management
 * - Token package purchases
 * - Usage history tracking
 * - Token consumption estimation
 * - Free monthly token allocation
 * - Package recommendations
 * - Best value package selection
 * - Token pricing and discounts
 * - Team-specific token management
 *
 * Supported operations:
 * - Get current token balance
 * - Purchase token packages
 * - View available packages
 * - Track usage history
 * - Estimate token consumption
 * - Get package recommendations
 * - Manage free monthly tokens
 * - Process token purchases
 *
 * Token packages include:
 * - Starter packages for new users
 * - Standard packages for regular usage
 * - Premium packages for heavy usage
 * - Enterprise packages for teams
 * - Recurring subscription packages
 *
 * The controller provides:
 * - RESTful API endpoints for AI token management
 * - Comprehensive balance tracking
 * - Package purchase processing
 * - Usage analytics and reporting
 * - Team-specific token isolation
 * - Security and access control
 *
 * @package App\Http\Controllers\Api\Wallet
 * @since 1.0.0
 */
class AiTokenController extends Controller
{
    /** @var AiTokenService Service for AI token operations */
    protected $aiTokenService;

    /**
     * Initialize the AI Token Controller with dependencies
     *
     * @param AiTokenService $aiTokenService Service for AI token operations
     */
    public function __construct(AiTokenService $aiTokenService)
    {
        $this->aiTokenService = $aiTokenService;
    }

    /**
     * Get user's AI token balance and free token allocation
     *
     * This method retrieves the current AI token balance for the authenticated
     * user, including automatic free monthly token allocation and balance
     * formatting for display.
     *
     * @param Request $request The HTTP request (may include team_id)
     * @return JsonResponse Response containing token balance and allocation info
     */
    public function getBalance(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);
            
            $balance = $this->aiTokenService->getTokenBalance($user);
            
            // Check for free monthly tokens
            $freeTokensGranted = $this->aiTokenService->addFreeMonthlyTokens($user);
            if ($freeTokensGranted > 0) {
                $balance = $this->aiTokenService->getTokenBalance($user); // Refresh balance
            }

            return response()->json([
                'success' => true,
                'balance' => $balance,
                'free_tokens_granted' => $freeTokensGranted,
                'monthly_limit' => config('wallet.ai_tokens.free_monthly_limit', 10000),
                'formatted_balance' => number_format($balance) . ' tokens'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get AI token balance', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve token balance',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available AI token packages for purchase
     *
     * This method retrieves all available AI token packages including featured
     * packages, recommendations, and best value options for the user to purchase.
     *
     * @param Request $request The HTTP request
     * @return JsonResponse Response containing available token packages
     */
    public function getPackages(Request $request): JsonResponse
    {
        try {
            $packages = AiTokenPackage::getActivePackages();
            $featuredPackages = AiTokenPackage::getFeaturedPackages();
            $recommendedPackage = AiTokenPackage::getRecommendedPackage();
            $bestValuePackage = AiTokenPackage::getBestValuePackage();

            $formattedPackages = $packages->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'description' => $package->description,
                    'token_amount' => $package->token_amount,
                    'formatted_token_amount' => $package->getFormattedTokenAmount(),
                    'price' => $package->price,
                    'discounted_price' => $package->getDiscountedPrice(),
                    'formatted_price' => $package->getFormattedPrice(),
                    'discount_percentage' => $package->discount_percentage,
                    'savings' => $package->getSavings(),
                    'price_per_token' => $package->getPricePerToken(),
                    'formatted_price_per_token' => $package->getFormattedPricePerToken(),
                    'is_featured' => $package->is_featured,
                    'is_recurring' => $package->is_recurring,
                    'package_type' => $package->package_type,
                    'validity_days' => $package->validity_days,
                    'features' => $package->getFeaturesList(),
                    'badge_text' => $package->getBadgeText(),
                    'sort_order' => $package->sort_order,
                ];
            });

            return response()->json([
                'success' => true,
                'packages' => $formattedPackages,
                'featured_packages' => $featuredPackages->pluck('id'),
                'recommended_package_id' => $recommendedPackage?->id,
                'best_value_package_id' => $bestValuePackage?->id,
                'pricing_info' => [
                    'base_price_per_1000' => config('wallet.ai_tokens.price_per_1000', 0.02),
                    'bulk_discount_threshold' => config('wallet.ai_tokens.bulk_discount_threshold', 100000),
                    'bulk_discount_rate' => config('wallet.ai_tokens.bulk_discount_rate', 0.015),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get AI token packages', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve token packages',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Purchase AI token package
     */
    public function purchaseTokens(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'package_id' => 'required|integer|exists:ai_token_packages,id',
                'team_id' => 'nullable|integer|exists:teams,id'
            ]);

            $user = Auth::user();
            $packageId = $request->input('package_id');
            $teamId = $request->input('team_id');
            
            // Validate team access if team_id provided
            if ($teamId) {
                $team = Team::find($teamId);
                if (!$team || !$user->belongsToTeam($team)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'access_denied',
                        'message' => 'You do not have access to this team'
                    ], 403);
                }
            }

            // Get the package
            $package = AiTokenPackage::active()->find($packageId);
            if (!$package) {
                return response()->json([
                    'success' => false,
                    'error' => 'package_not_found',
                    'message' => 'The selected token package is not available'
                ], 404);
            }

            // Attempt purchase
            $result = $this->aiTokenService->purchaseTokenPackage(
                $user, 
                $package, 
                $teamId ? Team::find($teamId) : null
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                    'message' => $result['message'],
                    'required_amount' => $result['required_amount'] ?? null,
                    'current_balance' => $result['current_balance'] ?? null
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Token package purchased successfully',
                'tokens_purchased' => $result['tokens_purchased'],
                'amount_paid' => $result['amount_paid'],
                'new_balance' => $result['new_balance'],
                'transaction_id' => $result['transaction_id'],
                'expiry_date' => $result['expiry_date']
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'validation_failed',
                'message' => 'Invalid input data',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to purchase AI token package', [
                'user_id' => Auth::id(),
                'package_id' => $request->input('package_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'purchase_failed',
                'message' => 'Failed to process token purchase: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's AI token usage history
     */
    public function getUsageHistory(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'team_id' => 'nullable|integer|exists:teams,id',
                'limit' => 'nullable|integer|min:1|max:100',
                'period' => 'nullable|string|in:day,week,month,year'
            ]);

            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);
            $limit = $request->input('limit', 50);
            $period = $request->input('period', 'month');

            // Validate team access if team_id provided
            if ($teamId) {
                $team = Team::find($teamId);
                if (!$team || !$user->belongsToTeam($team)) {
                    return response()->json([
                        'success' => false,
                        'error' => 'access_denied',
                        'message' => 'You do not have access to this team\'s usage data'
                    ], 403);
                }
            }

            // Get usage history
            $history = $this->aiTokenService->getUsageHistory(
                $user, 
                $teamId ? Team::find($teamId) : null, 
                $limit
            );

            // Get usage statistics for the specified period
            $stats = $this->aiTokenService->getUsageStats(
                $user, 
                $teamId ? Team::find($teamId) : null, 
                $period
            );

            return response()->json([
                'success' => true,
                'current_balance' => $this->aiTokenService->getTokenBalance($user),
                'usage_history' => $history['usage_history'],
                'history_summary' => [
                    'total_tokens_used' => $history['total_tokens_used'],
                    'total_cost' => $history['total_cost'],
                    'records_count' => count($history['usage_history'])
                ],
                'period_stats' => $stats,
                'pagination' => [
                    'limit' => $limit,
                    'has_more' => count($history['usage_history']) >= $limit
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'validation_failed',
                'message' => 'Invalid input data',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to get AI token usage history', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve usage history',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get estimated token cost for a message
     */
    public function estimateTokens(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'message' => 'required|string|max:10000',
                'model' => 'nullable|string|max:100'
            ]);

            $message = $request->input('message');
            $model = $request->input('model', 'gpt-4');

            $estimatedTokens = $this->aiTokenService->estimateTokenUsage($message, $model);
            $user = Auth::user();
            $currentBalance = $this->aiTokenService->getTokenBalance($user);
            $hasEnoughTokens = $currentBalance >= $estimatedTokens;

            return response()->json([
                'success' => true,
                'estimated_tokens' => $estimatedTokens,
                'current_balance' => $currentBalance,
                'has_enough_tokens' => $hasEnoughTokens,
                'tokens_needed' => $hasEnoughTokens ? 0 : ($estimatedTokens - $currentBalance),
                'model' => $model
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'validation_failed',
                'message' => 'Invalid input data',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'estimation_failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get team ID from request, validating user access
     */
    private function getTeamIdFromRequest(Request $request): ?int
    {
        $teamId = $request->header('X-Team-ID') ?: $request->input('team_id');
        
        if (!$teamId) {
            return null;
        }

        $user = Auth::user();
        $team = Team::find($teamId);
        
        if (!$team || !$user->belongsToTeam($team)) {
            return null;
        }

        return (int) $teamId;
    }
} 