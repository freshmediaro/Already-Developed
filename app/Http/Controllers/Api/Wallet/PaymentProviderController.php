<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Team;
use App\Models\Wallet\PaymentProviderConfig;
use App\Services\Wallet\OmnipayProviderService;
use App\Services\Wallet\CashierWalletService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Payment Provider Controller - Manages payment provider configuration and operations
 *
 * This controller handles all payment provider operations including provider
 * management, configuration, testing, and payment processing within the
 * multi-tenant system. It supports both Omnipay and Cashier payment providers.
 *
 * Key features:
 * - Payment provider discovery and management
 * - Provider enablement and configuration
 * - Payment processing and transaction handling
 * - Provider testing and validation
 * - Default provider management
 * - Provider statistics and analytics
 * - Cashier integration for Stripe
 * - Wallet top-up and AI token purchases
 * - Team-specific provider configurations
 *
 * Supported operations:
 * - Get available payment providers
 * - Enable and configure providers
 * - Test provider connections
 * - Process payments through providers
 * - Set default payment providers
 * - Get provider statistics
 * - Enable Cashier integration
 * - Create wallet top-up intents
 * - Create AI token purchase intents
 *
 * Supported providers:
 * - Stripe (via Cashier)
 * - PayPal Express
 * - Square
 * - Authorize.net
 * - Internal payment processing
 *
 * The controller provides:
 * - RESTful API endpoints for payment provider management
 * - Comprehensive validation and error handling
 * - Team-specific provider configurations
 * - Security and access control
 * - Payment processing integration
 * - Provider testing and validation
 *
 * @package App\Http\Controllers\Api\Wallet
 * @since 1.0.0
 */
class PaymentProviderController extends Controller
{
    /** @var OmnipayProviderService Service for Omnipay payment providers */
    protected $omnipayService;

    /** @var CashierWalletService Service for Cashier payment processing */
    protected $cashierService;

    /**
     * Initialize the Payment Provider Controller with dependencies
     *
     * @param OmnipayProviderService $omnipayService Service for Omnipay providers
     * @param CashierWalletService $cashierService Service for Cashier integration
     */
    public function __construct(
        OmnipayProviderService $omnipayService,
        CashierWalletService $cashierService
    ) {
        $this->omnipayService = $omnipayService;
        $this->cashierService = $cashierService;
    }

    /**
     * Get available payment providers for the platform
     *
     * This method retrieves all available payment providers that can be
     * configured and used for payment processing within the system.
     *
     * @param Request $request The HTTP request
     * @return JsonResponse Response containing available payment providers
     */
    public function getAvailableProviders(Request $request): JsonResponse
    {
        try {
            $providers = $this->omnipayService->getAvailableProviders();
            
            return response()->json([
                'success' => true,
                'providers' => $providers,
                'total_providers' => count($providers)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get available providers', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment providers'
            ], 500);
        }
    }

    /**
     * Get enabled payment providers for user and team
     *
     * This method retrieves all enabled payment providers for the authenticated
     * user and team, including their configuration status and capabilities.
     *
     * @param Request $request The HTTP request (may include team_id)
     * @return JsonResponse Response containing enabled payment providers
     */
    public function getEnabledProviders(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);
            
            // Validate team access if team_id is provided
            if ($teamId && !$this->validateTeamAccess($user, $teamId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to team resources'
                ], 403);
            }

            $team = $teamId ? Team::find($teamId) : null;
            $enabledProviders = $this->omnipayService->getEnabledProviders($user, $team);

            return response()->json([
                'success' => true,
                'enabled_providers' => $enabledProviders,
                'total_enabled' => count($enabledProviders)
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get enabled providers', [
                'user_id' => Auth::id(),
                'team_id' => $request->input('team_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve enabled providers'
            ], 500);
        }
    }

    /**
     * Enable a payment provider for user and team
     *
     * This method enables and configures a payment provider for the authenticated
     * user and team, including API key validation and connection testing.
     *
     * Request parameters:
     * - provider_name: Name of the payment provider to enable
     * - config: Provider configuration including API keys
     * - team_id: Team context for the provider
     *
     * @param Request $request The HTTP request with provider configuration
     * @return JsonResponse Response indicating success or failure
     */
    public function enableProvider(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'provider_name' => 'required|string|in:stripe,paypal,square,authorize_net',
                'api_key' => 'required|string',
                'api_secret' => 'nullable|string',
                'webhook_secret' => 'nullable|string',
                'test_mode' => 'boolean',
                'currency' => 'string|size:3',
                'additional_config' => 'array',
                'team_id' => 'nullable|integer|exists:teams,id'
            ]);

            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);
            
            // Validate team access if team_id is provided
            if ($teamId && !$this->validateTeamAccess($user, $teamId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to team resources'
                ], 403);
            }

            $team = $teamId ? Team::find($teamId) : null;
            
            $config = [
                'api_key' => $request->input('api_key'),
                'api_secret' => $request->input('api_secret'),
                'webhook_secret' => $request->input('webhook_secret'),
                'test_mode' => $request->boolean('test_mode', true),
                'currency' => $request->input('currency', 'USD'),
                'additional_config' => $request->input('additional_config', [])
            ];

            $result = $this->omnipayService->enableProvider(
                $user,
                $team,
                $request->input('provider_name'),
                $config
            );

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment provider enabled successfully',
                'provider_config' => [
                    'provider_name' => $result['provider_config']->provider_name,
                    'display_name' => $result['provider_config']->getProviderDisplayName(),
                    'currency' => $result['provider_config']->currency,
                    'test_mode' => $result['provider_config']->test_mode,
                    'monthly_fee' => $result['provider_config']->monthly_fee,
                    'subscription_expires_at' => $result['provider_config']->subscription_expires_at,
                    'supported_features' => $result['provider_config']->supported_features
                ],
                'test_result' => $result['test_result'],
                'monthly_fee_charged' => $result['monthly_fee_charged']
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to enable payment provider', [
                'user_id' => Auth::id(),
                'provider_name' => $request->input('provider_name'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to enable payment provider'
            ], 500);
        }
    }

    /**
     * Disable a payment provider
     */
    public function disableProvider(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'provider_name' => 'required|string',
                'team_id' => 'nullable|integer|exists:teams,id'
            ]);

            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);
            
            // Validate team access if team_id is provided
            if ($teamId && !$this->validateTeamAccess($user, $teamId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to team resources'
                ], 403);
            }

            $team = $teamId ? Team::find($teamId) : null;

            $result = $this->omnipayService->disableProvider(
                $user,
                $team,
                $request->input('provider_name')
            );

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment provider disabled successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to disable payment provider', [
                'user_id' => Auth::id(),
                'provider_name' => $request->input('provider_name'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to disable payment provider'
            ], 500);
        }
    }

    /**
     * Test provider connection
     */
    public function testProvider(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'provider_name' => 'required|string',
                'team_id' => 'nullable|integer|exists:teams,id'
            ]);

            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);
            
            // Validate team access if team_id is provided
            if ($teamId && !$this->validateTeamAccess($user, $teamId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to team resources'
                ], 403);
            }

            $team = $teamId ? Team::find($teamId) : null;
            $config = $this->omnipayService->getProviderConfig($user, $team, $request->input('provider_name'));

            if (!$config) {
                return response()->json([
                    'success' => false,
                    'message' => 'Provider not configured or disabled'
                ], 404);
            }

            $testResult = $this->omnipayService->testProviderConnection($config);

            return response()->json([
                'success' => true,
                'test_result' => $testResult
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to test payment provider', [
                'user_id' => Auth::id(),
                'provider_name' => $request->input('provider_name'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to test payment provider'
            ], 500);
        }
    }

    /**
     * Process payment
     */
    public function processPayment(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'provider_name' => 'required|string',
                'amount' => 'required|numeric|min:0.01',
                'description' => 'nullable|string',
                'payment_method' => 'nullable|string',
                'return_url' => 'nullable|url',
                'cancel_url' => 'nullable|url',
                'team_id' => 'nullable|integer|exists:teams,id'
            ]);

            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);
            
            // Validate team access if team_id is provided
            if ($teamId && !$this->validateTeamAccess($user, $teamId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to team resources'
                ], 403);
            }

            $team = $teamId ? Team::find($teamId) : null;
            
            $paymentData = [
                'amount' => $request->input('amount'),
                'description' => $request->input('description', 'Payment'),
                'payment_method' => $request->input('payment_method'),
                'return_url' => $request->input('return_url'),
                'cancel_url' => $request->input('cancel_url'),
                'transaction_id' => uniqid('pay_')
            ];

            $result = $this->omnipayService->processPayment(
                $user,
                $team,
                $request->input('provider_name'),
                $paymentData
            );

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to process payment', [
                'user_id' => Auth::id(),
                'provider_name' => $request->input('provider_name'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process payment'
            ], 500);
        }
    }

    /**
     * Set default provider
     */
    public function setDefaultProvider(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'provider_name' => 'required|string',
                'team_id' => 'nullable|integer|exists:teams,id'
            ]);

            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);
            
            // Validate team access if team_id is provided
            if ($teamId && !$this->validateTeamAccess($user, $teamId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to team resources'
                ], 403);
            }

            $success = PaymentProviderConfig::setDefault(
                $user->id,
                $teamId,
                $request->input('provider_name')
            );

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to set default provider. Provider may not be enabled or active.'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => 'Default payment provider updated successfully'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to set default provider', [
                'user_id' => Auth::id(),
                'provider_name' => $request->input('provider_name'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to set default provider'
            ], 500);
        }
    }

    /**
     * Get provider statistics
     */
    public function getProviderStats(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);
            
            // Validate team access if team_id is provided
            if ($teamId && !$this->validateTeamAccess($user, $teamId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to team resources'
                ], 403);
            }

            $configs = PaymentProviderConfig::getActiveForUser($user->id, $teamId);
            
            $stats = [
                'total_providers' => $configs->count(),
                'monthly_fees_total' => $configs->sum('monthly_fee'),
                'providers_expiring_soon' => $configs->filter(function ($config) {
                    return $config->isExpiringSoon();
                })->count(),
                'test_mode_providers' => $configs->where('test_mode', true)->count(),
                'live_mode_providers' => $configs->where('test_mode', false)->count(),
                'providers_by_status' => $configs->groupBy('subscription_status')
                    ->map(function ($group) {
                        return $group->count();
                    })
                    ->toArray()
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get provider stats', [
                'user_id' => Auth::id(),
                'team_id' => $request->input('team_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve provider statistics'
            ], 500);
        }
    }

    /**
     * Enable Cashier as default payment provider
     */
    public function enableCashier(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'publishable_key' => 'required|string',
                'secret_key' => 'required|string',
                'webhook_secret' => 'nullable|string',
                'test_mode' => 'boolean',
                'currency' => 'nullable|string|size:3',
                'team_id' => 'nullable|integer|exists:teams,id'
            ]);

            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);
            
            // Validate team access if team_id is provided
            if ($teamId && !$this->validateTeamAccess($user, $teamId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to team resources'
                ], 403);
            }

            $team = $teamId ? Team::find($teamId) : null;
            
            $stripeConfig = [
                'publishable_key' => $request->input('publishable_key'),
                'secret_key' => $request->input('secret_key'),
                'webhook_secret' => $request->input('webhook_secret'),
                'test_mode' => $request->input('test_mode', true),
                'currency' => $request->input('currency', 'USD'),
            ];

            $result = $this->cashierService->enableCashierForTenant($user, $team, $stripeConfig);

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to enable Cashier', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to enable Cashier payment provider'
            ], 500);
        }
    }

    /**
     * Create wallet top-up payment intent
     */
    public function createWalletTopupIntent(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'amount' => 'required|numeric|min:1|max:10000',
                'currency' => 'nullable|string|size:3',
                'team_id' => 'nullable|integer|exists:teams,id'
            ]);

            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);
            
            // Validate team access if team_id is provided
            if ($teamId && !$this->validateTeamAccess($user, $teamId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to team resources'
                ], 403);
            }

            $amount = $request->input('amount');
            $currency = $request->input('currency', 'usd');
            $metadata = [
                'team_id' => $teamId,
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            $result = $this->cashierService->createWalletTopupPaymentIntent(
                $user,
                $amount,
                $currency,
                $metadata
            );

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create wallet top-up intent', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create wallet top-up payment intent'
            ], 500);
        }
    }

    /**
     * Create AI token purchase payment intent
     */
    public function createAiTokenPurchaseIntent(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'package_id' => 'required|string|exists:ai_token_packages,id',
                'team_id' => 'nullable|integer|exists:teams,id'
            ]);

            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);
            
            // Validate team access if team_id is provided
            if ($teamId && !$this->validateTeamAccess($user, $teamId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to team resources'
                ], 403);
            }

            $packageId = $request->input('package_id');
            $metadata = [
                'team_id' => $teamId,
                'request_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ];

            $result = $this->cashierService->createAiTokenPurchasePaymentIntent(
                $user,
                $packageId,
                $metadata
            );

            if (!$result['success']) {
                return response()->json($result, 400);
            }

            return response()->json($result);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Failed to create AI token purchase intent', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create AI token purchase payment intent'
            ], 500);
        }
    }

    /**
     * Get team ID from request with validation
     */
    private function getTeamIdFromRequest(Request $request): ?int
    {
        $teamId = $request->input('team_id');
        return $teamId ? (int) $teamId : null;
    }

    /**
     * Validate team access for user
     */
    private function validateTeamAccess(User $user, int $teamId): bool
    {
        return $user->teams()->where('teams.id', $teamId)->exists();
    }
} 