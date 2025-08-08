<?php

namespace App\Services\Wallet;

use App\Models\User;
use App\Models\Team;
use App\Models\Wallet\PaymentProviderConfig;
use App\Models\Wallet\TenantWalletSettings;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Payment;
use Laravel\Cashier\PaymentMethod;
use Stripe\StripeClient;
use Exception;

/**
 * Cashier Wallet Service - Laravel Cashier integration for Stripe payments with tenant isolation
 *
 * This service provides comprehensive Stripe payment integration using Laravel Cashier,
 * supporting wallet top-ups, AI token purchases, and tenant-specific payment processing.
 * It ensures proper tenant isolation and multi-tenant payment management.
 *
 * Key features:
 * - Stripe customer management with tenant isolation
 * - Wallet top-up payment processing
 * - AI token package purchases
 * - Checkout session creation
 * - Tenant-specific payment processing
 * - Payment method management
 * - Auto-topup functionality
 * - Multi-currency support
 *
 * The service supports:
 * - Platform-level payments (wallet top-ups, AI tokens)
 * - Tenant-specific payments (e-commerce, subscriptions)
 * - Automatic customer creation and management
 * - Payment intent and checkout session creation
 * - Webhook processing and payment confirmation
 * - Default payment method management
 *
 * @package App\Services\Wallet
 * @since 1.0.0
 */
class CashierWalletService
{
    /** @var AiTokenService Service for AI token management and consumption */
    protected $aiTokenService;

    /** @var PlatformCommissionService Service for platform commission tracking */
    protected $platformCommissionService;

    /**
     * Initialize the Cashier Wallet Service with dependencies
     *
     * @param AiTokenService $aiTokenService Service for AI token operations
     * @param PlatformCommissionService $platformCommissionService Service for commission tracking
     */
    public function __construct(
        AiTokenService $aiTokenService,
        PlatformCommissionService $platformCommissionService
    ) {
        $this->aiTokenService = $aiTokenService;
        $this->platformCommissionService = $platformCommissionService;
    }

    /**
     * Create or get Stripe customer for user with tenant isolation
     *
     * This method ensures that every user has a Stripe customer account,
     * creating one if it doesn't exist. It supports tenant-specific customer
     * management and includes proper metadata for tracking.
     *
     * The method:
     * - Checks if user already has a Stripe customer ID
     * - Creates new customer with tenant context if needed
     * - Includes user and tenant metadata for tracking
     * - Logs customer creation for audit purposes
     *
     * @param User $user The user to create or get Stripe customer for
     * @return \Laravel\Cashier\Customer The Stripe customer instance
     */
    public function getOrCreateStripeCustomer(User $user): \Laravel\Cashier\Customer
    {
        // Use tenant-scoped customer ID if in tenant context
        if (!$user->hasStripeId()) {
            $customer = $user->createAsStripeCustomer([
                'name' => $user->name,
                'email' => $user->email,
                'metadata' => [
                    'user_id' => $user->id,
                    'tenant_id' => tenant('id'),
                    'platform' => config('app.name'),
                ],
            ]);

            Log::info('Created Stripe customer for user', [
                'user_id' => $user->id,
                'stripe_customer_id' => $user->stripeId(),
                'tenant_id' => tenant('id'),
            ]);

            return $customer;
        }

        return $user->asStripeCustomer();
    }

    /**
     * Create a wallet top-up payment intent for platform payments
     *
     * This method creates a Stripe payment intent for wallet top-ups,
     * allowing users to add funds to their platform wallet. It handles
     * currency conversion and includes comprehensive metadata.
     *
     * @param User $user The user making the top-up
     * @param float $amount The amount to top up (in dollars)
     * @param string $currency The currency for the payment (default: usd)
     * @param array $metadata Additional metadata for the payment
     * @return array Response containing payment intent and client secret
     */
    public function createWalletTopupPaymentIntent(
        User $user,
        float $amount,
        string $currency = 'usd',
        array $metadata = []
    ): array {
        try {
            $customer = $this->getOrCreateStripeCustomer($user);
            
            $paymentIntent = $user->pay(
                $amount * 100, // Convert to cents
                [
                    'currency' => $currency,
                    'metadata' => array_merge([
                        'type' => 'wallet_topup',
                        'user_id' => $user->id,
                        'tenant_id' => tenant('id'),
                        'team_id' => $metadata['team_id'] ?? null,
                    ], $metadata),
                    'setup_future_usage' => 'off_session', // Allow future payments
                ]
            );

            Log::info('Created wallet top-up payment intent', [
                'user_id' => $user->id,
                'amount' => $amount,
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
                'client_secret' => $paymentIntent->client_secret,
            ];
        } catch (Exception $e) {
            Log::error('Failed to create wallet top-up payment intent', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create an AI token purchase payment intent
     *
     * This method creates a Stripe payment intent for AI token package
     * purchases, including package validation and token amount tracking.
     *
     * @param User $user The user purchasing AI tokens
     * @param string $packageId The ID of the AI token package
     * @param array $metadata Additional metadata for the purchase
     * @return array Response containing payment intent, client secret, and package info
     */
    public function createAiTokenPurchasePaymentIntent(
        User $user,
        string $packageId,
        array $metadata = []
    ): array {
        try {
            $package = \App\Models\Wallet\AiTokenPackage::find($packageId);
            if (!$package) {
                return [
                    'success' => false,
                    'error' => 'AI token package not found',
                ];
            }

            $customer = $this->getOrCreateStripeCustomer($user);
            
            $paymentIntent = $user->pay(
                $package->price_cents,
                [
                    'currency' => $package->currency,
                    'metadata' => array_merge([
                        'type' => 'ai_token_purchase',
                        'user_id' => $user->id,
                        'tenant_id' => tenant('id'),
                        'package_id' => $packageId,
                        'token_amount' => $package->token_amount,
                        'team_id' => $metadata['team_id'] ?? null,
                    ], $metadata),
                ]
            );

            Log::info('Created AI token purchase payment intent', [
                'user_id' => $user->id,
                'package_id' => $packageId,
                'amount' => $package->price,
                'tokens' => $package->token_amount,
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
                'client_secret' => $paymentIntent->client_secret,
                'package' => $package,
            ];
        } catch (Exception $e) {
            Log::error('Failed to create AI token purchase payment intent', [
                'user_id' => $user->id,
                'package_id' => $packageId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a checkout session for wallet top-up
     *
     * This method creates a Stripe checkout session for wallet top-ups,
     * providing a hosted payment page for users to complete their payment.
     *
     * @param User $user The user making the top-up
     * @param float $amount The amount to top up (in dollars)
     * @param string $successUrl URL to redirect to on successful payment
     * @param string $cancelUrl URL to redirect to on cancelled payment
     * @param array $metadata Additional metadata for the payment
     * @return array Response containing checkout session and URL
     */
    public function createWalletTopupCheckoutSession(
        User $user,
        float $amount,
        string $successUrl,
        string $cancelUrl,
        array $metadata = []
    ): array {
        try {
            $customer = $this->getOrCreateStripeCustomer($user);

            $checkoutSession = $user->checkoutCharge(
                $amount * 100, // Convert to cents
                'Wallet Top-up',
                1,
                [
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    'metadata' => array_merge([
                        'type' => 'wallet_topup',
                        'user_id' => $user->id,
                        'tenant_id' => tenant('id'),
                        'team_id' => $metadata['team_id'] ?? null,
                    ], $metadata),
                ]
            );

            Log::info('Created wallet top-up checkout session', [
                'user_id' => $user->id,
                'amount' => $amount,
                'session_id' => $checkoutSession->id,
            ]);

            return [
                'success' => true,
                'checkout_session' => $checkoutSession,
                'session_url' => $checkoutSession->url,
            ];
        } catch (Exception $e) {
            Log::error('Failed to create wallet top-up checkout session', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create tenant payment processing for received payments
     *
     * This method creates payment intents using the tenant's own Stripe
     * configuration, allowing tenants to receive payments directly to
     * their Stripe accounts with platform commission tracking.
     *
     * @param User $user The user processing the payment
     * @param Team|null $team The team context for the payment
     * @param float $amount The payment amount (in dollars)
     * @param string $description Description of the payment
     * @param array $customerData Customer information for the payment
     * @param array $metadata Additional metadata for the payment
     * @return array Response containing payment intent and client secret
     */
    public function createTenantPaymentIntent(
        User $user,
        ?Team $team,
        float $amount,
        string $description,
        array $customerData,
        array $metadata = []
    ): array {
        try {
            // Get tenant's Stripe configuration
            $stripeConfig = $this->getTenantStripeConfig($user, $team);
            if (!$stripeConfig) {
                return [
                    'success' => false,
                    'error' => 'Tenant Stripe configuration not found',
                ];
            }

            // Create payment intent using tenant's Stripe account
            $stripe = new StripeClient($stripeConfig['secret_key']);
            
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => $amount * 100, // Convert to cents
                'currency' => 'usd',
                'description' => $description,
                'metadata' => array_merge([
                    'type' => 'tenant_payment',
                    'user_id' => $user->id,
                    'team_id' => $team?->id,
                    'tenant_id' => tenant('id'),
                ], $metadata),
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            Log::info('Created tenant payment intent', [
                'user_id' => $user->id,
                'team_id' => $team?->id,
                'amount' => $amount,
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
                'client_secret' => $paymentIntent->client_secret,
            ];
        } catch (Exception $e) {
            Log::error('Failed to create tenant payment intent', [
                'user_id' => $user->id,
                'team_id' => $team?->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Enable Cashier as default payment provider for tenant
     *
     * This method configures Laravel Cashier as the default payment
     * provider for a tenant, including Stripe key validation and
     * provider configuration setup.
     *
     * @param User $user The user enabling Cashier
     * @param Team|null $team The team context for the configuration
     * @param array $stripeConfig Stripe configuration including API keys
     * @return array Response containing configuration status and details
     */
    public function enableCashierForTenant(User $user, ?Team $team, array $stripeConfig): array
    {
        try {
            $teamId = $team?->id;

            // Validate Stripe keys
            if (empty($stripeConfig['publishable_key']) || empty($stripeConfig['secret_key'])) {
                return [
                    'success' => false,
                    'error' => 'Stripe publishable and secret keys are required',
                ];
            }

            // Test the keys
            $testResult = $this->testStripeKeys($stripeConfig);
            if (!$testResult['success']) {
                return $testResult;
            }

            // Create or update provider config
            $providerConfig = PaymentProviderConfig::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'team_id' => $teamId,
                    'provider_name' => 'stripe-cashier'
                ],
                [
                    'is_enabled' => true,
                    'is_default' => true, // Set as default
                    'api_key' => \Illuminate\Support\Facades\Crypt::encryptString($stripeConfig['secret_key']),
                    'api_secret' => \Illuminate\Support\Facades\Crypt::encryptString($stripeConfig['publishable_key']),
                    'webhook_secret' => isset($stripeConfig['webhook_secret']) 
                        ? \Illuminate\Support\Facades\Crypt::encryptString($stripeConfig['webhook_secret']) 
                        : null,
                    'test_mode' => $stripeConfig['test_mode'] ?? true,
                    'currency' => $stripeConfig['currency'] ?? 'USD',
                    'monthly_fee' => 0, // Platform default is free
                    'subscription_started_at' => now(),
                    'subscription_status' => 'active',
                    'supported_features' => [
                        'payments' => true,
                        'subscriptions' => true,
                        'refunds' => true,
                        'webhooks' => true,
                        'connect' => false,
                    ],
                    'metadata' => [
                        'integration_type' => 'cashier',
                        'enabled_at' => now()->toISOString(),
                    ]
                ]
            );

            // Disable other default providers
            PaymentProviderConfig::tenantIsolated($user->id, $teamId)
                ->where('provider_name', '!=', 'stripe-cashier')
                ->update(['is_default' => false]);

            Log::info('Enabled Cashier for tenant', [
                'user_id' => $user->id,
                'team_id' => $teamId,
                'tenant_id' => tenant('id'),
            ]);

            return [
                'success' => true,
                'message' => 'Stripe Cashier enabled as default payment provider',
                'config' => $providerConfig,
            ];
        } catch (Exception $e) {
            Log::error('Failed to enable Cashier for tenant', [
                'user_id' => $user->id,
                'team_id' => $team?->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get tenant's Stripe configuration with decrypted keys
     *
     * This method retrieves the tenant's Stripe configuration from the
     * database and decrypts the API keys for use in payment processing.
     *
     * @param User $user The user to get configuration for
     * @param Team|null $team The team context for the configuration
     * @return array|null Stripe configuration with decrypted keys or null if not found
     */
    protected function getTenantStripeConfig(User $user, ?Team $team): ?array
    {
        $config = PaymentProviderConfig::tenantIsolated($user->id, $team?->id)
            ->byProvider('stripe-cashier')
            ->enabled()
            ->active()
            ->first();

        if (!$config) {
            return null;
        }

        return [
            'secret_key' => $config->getDecryptedApiKey(),
            'publishable_key' => $config->getDecryptedApiSecret(),
            'webhook_secret' => $config->getDecryptedWebhookSecret(),
        ];
    }

    /**
     * Test Stripe API keys for validity
     *
     * This method validates Stripe API keys by attempting to retrieve
     * account information from Stripe, ensuring the keys are valid
     * before enabling them for payment processing.
     *
     * @param array $config Stripe configuration with secret key
     * @return array Response containing validation result and account information
     */
    protected function testStripeKeys(array $config): array
    {
        try {
            $stripe = new StripeClient($config['secret_key']);
            
            // Test by retrieving account information
            $account = $stripe->accounts->retrieve();
            
            return [
                'success' => true,
                'account_id' => $account->id,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Invalid Stripe keys: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check if user/team uses Cashier as default provider
     *
     * This method checks whether the user or team has configured
     * Laravel Cashier as their default payment provider.
     *
     * @param User $user The user to check
     * @param Team|null $team Optional team context for the check
     * @return bool True if Cashier is the default provider, false otherwise
     */
    public function usesCashierAsDefault(User $user, ?Team $team = null): bool
    {
        return PaymentProviderConfig::tenantIsolated($user->id, $team?->id)
            ->byProvider('stripe-cashier')
            ->enabled()
            ->active()
            ->default()
            ->exists();
    }

    /**
     * Get wallet settings with auto-topup via Cashier
     *
     * This method retrieves wallet settings and checks if auto-topup
     * is available through Cashier integration.
     *
     * @param User $user The user to get settings for
     * @param Team|null $team Optional team context for the settings
     * @return array Wallet settings with Cashier integration status
     */
    public function getWalletSettingsWithCashier(User $user, ?Team $team = null): array
    {
        $settings = TenantWalletSettings::getOrCreateForUser($user->id, $team?->id);
        $usesCashier = $this->usesCashierAsDefault($user, $team);

        return [
            'settings' => $settings,
            'uses_cashier' => $usesCashier,
            'can_auto_topup' => $usesCashier && $user->hasDefaultPaymentMethod(),
            'default_payment_method' => $usesCashier ? $user->defaultPaymentMethod() : null,
        ];
    }
} 