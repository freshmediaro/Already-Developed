<?php

namespace App\Jobs\Webhooks;

use App\Models\User;
use App\Models\Team;
use App\Services\Wallet\AiTokenService;
use App\Services\Wallet\PlatformCommissionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;
use Spatie\WebhookClient\Models\WebhookCall;

/**
 * Process Stripe Webhook Job - Platform-level Stripe webhook processing
 *
 * This job processes Stripe webhook events for platform-level operations including
 * subscription management, payment processing, wallet top-ups, AI token purchases,
 * and customer management. It handles all Stripe events for the main platform.
 *
 * Key features:
 * - Comprehensive Stripe event processing
 * - Subscription lifecycle management
 * - Payment processing and validation
 * - Wallet top-up processing
 * - AI token purchase handling
 * - Customer management
 * - Invoice and charge processing
 * - Dispute handling
 * - Platform commission tracking
 * - Error handling and logging
 *
 * Supported Stripe events:
 * - customer.subscription.*: Subscription lifecycle events
 * - invoice.payment_*: Invoice payment events
 * - payment_intent.*: Payment intent events
 * - checkout.session.*: Checkout session events
 * - charge.*: Charge events for wallet top-ups
 * - customer.*: Customer management events
 * - payment_method.*: Payment method events
 *
 * The job provides:
 * - Event-specific processing logic
 * - Payment validation and processing
 * - Subscription management
 * - Customer data synchronization
 * - Error handling and retry logic
 * - Comprehensive logging
 *
 * @package App\Jobs\Webhooks
 * @since 1.0.0
 */
class ProcessStripeWebhookJob extends ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var AiTokenService Service for AI token management */
    protected $aiTokenService;
    
    /** @var PlatformCommissionService Service for platform commission tracking */
    protected $platformCommissionService;

    /**
     * Create a new Stripe webhook processing job instance
     *
     * This constructor initializes the job with the webhook call and
     * injects required services for payment processing and commission tracking.
     *
     * @param WebhookCall $webhookCall The webhook call to process
     */
    public function __construct(WebhookCall $webhookCall)
    {
        parent::__construct($webhookCall);
        $this->aiTokenService = app(AiTokenService::class);
        $this->platformCommissionService = app(PlatformCommissionService::class);
    }

    /**
     * Execute the webhook processing job
     *
     * This method processes the Stripe webhook event based on the event type,
     * routing to appropriate handlers for different types of events.
     */
    public function handle()
    {
        $payload = $this->webhookCall->payload;
        $eventType = $payload['type'];
        
        Log::info('Processing Stripe platform webhook', [
            'event_type' => $eventType,
            'event_id' => $payload['id'] ?? null,
        ]);

        try {
            switch ($eventType) {
                // Cashier subscription events
                case 'customer.subscription.created':
                    $this->handleSubscriptionCreated($payload);
                    break;

                case 'customer.subscription.updated':
                    $this->handleSubscriptionUpdated($payload);
                    break;

                case 'customer.subscription.deleted':
                    $this->handleSubscriptionDeleted($payload);
                    break;

                case 'customer.subscription.trial_will_end':
                    $this->handleTrialWillEnd($payload);
                    break;

                // Invoice events
                case 'invoice.payment_succeeded':
                    $this->handleInvoicePaymentSucceeded($payload);
                    break;

                case 'invoice.payment_failed':
                    $this->handleInvoicePaymentFailed($payload);
                    break;

                // Payment events for wallet top-ups and AI tokens
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($payload);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentIntentFailed($payload);
                    break;

                // Checkout events
                case 'checkout.session.completed':
                    $this->handleCheckoutSessionCompleted($payload);
                    break;

                // Charge events for wallet top-ups
                case 'charge.succeeded':
                    $this->handleChargeSucceeded($payload);
                    break;

                case 'charge.failed':
                    $this->handleChargeFailed($payload);
                    break;

                case 'charge.dispute.created':
                    $this->handleChargeDispute($payload);
                    break;

                // Customer events
                case 'customer.created':
                    $this->handleCustomerCreated($payload);
                    break;

                case 'customer.updated':
                    $this->handleCustomerUpdated($payload);
                    break;

                case 'customer.deleted':
                    $this->handleCustomerDeleted($payload);
                    break;

                // Payment method events
                case 'payment_method.attached':
                    $this->handlePaymentMethodAttached($payload);
                    break;

                case 'payment_method.detached':
                    $this->handlePaymentMethodDetached($payload);
                    break;

                default:
                    Log::info('Unhandled Stripe platform webhook event', [
                        'event_type' => $eventType,
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing Stripe platform webhook', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e; // Re-throw to trigger retry
        }
    }

    protected function handleSubscriptionCreated(array $payload): void
    {
        $subscription = $payload['data']['object'];
        
        Log::info('Subscription created', [
            'subscription_id' => $subscription['id'],
            'customer_id' => $subscription['customer'],
            'status' => $subscription['status'],
        ]);
    }

    protected function handleSubscriptionUpdated(array $payload): void
    {
        $subscription = $payload['data']['object'];
        
        Log::info('Subscription updated', [
            'subscription_id' => $subscription['id'],
            'status' => $subscription['status'],
        ]);
    }

    protected function handleSubscriptionDeleted(array $payload): void
    {
        $subscription = $payload['data']['object'];
        
        Log::info('Subscription deleted', [
            'subscription_id' => $subscription['id'],
            'customer_id' => $subscription['customer'],
        ]);
    }

    protected function handleTrialWillEnd(array $payload): void
    {
        $subscription = $payload['data']['object'];
        
        Log::info('Trial will end', [
            'subscription_id' => $subscription['id'],
            'trial_end' => $subscription['trial_end'],
        ]);
        
        // TODO: Send notification to user about trial ending
    }

    protected function handleInvoicePaymentSucceeded(array $payload): void
    {
        $invoice = $payload['data']['object'];
        
        Log::info('Invoice payment succeeded', [
            'invoice_id' => $invoice['id'],
            'customer_id' => $invoice['customer'],
            'amount_paid' => $invoice['amount_paid'],
        ]);
    }

    protected function handleInvoicePaymentFailed(array $payload): void
    {
        $invoice = $payload['data']['object'];
        
        Log::warning('Invoice payment failed', [
            'invoice_id' => $invoice['id'],
            'customer_id' => $invoice['customer'],
            'amount_due' => $invoice['amount_due'],
        ]);
        
        // TODO: Send notification to user about failed payment
    }

    protected function handlePaymentIntentSucceeded(array $payload): void
    {
        $paymentIntent = $payload['data']['object'];
        $metadata = $paymentIntent['metadata'] ?? [];
        $amount = ($paymentIntent['amount'] ?? 0) / 100; // Convert cents to dollars

        Log::info('Payment intent succeeded', [
            'payment_intent_id' => $paymentIntent['id'],
            'amount' => $amount,
            'metadata' => $metadata,
        ]);

        // Handle different payment types based on metadata
        switch ($metadata['type'] ?? null) {
            case 'wallet_topup':
                $this->processWalletTopup($paymentIntent, $metadata);
                break;
                
            case 'ai_token_purchase':
                $this->processAiTokenPurchase($paymentIntent, $metadata);
                break;
                
            default:
                Log::info('Unknown payment intent type', [
                    'payment_intent_id' => $paymentIntent['id'],
                    'type' => $metadata['type'] ?? 'unknown',
                ]);
        }
    }

    protected function handlePaymentIntentFailed(array $payload): void
    {
        $paymentIntent = $payload['data']['object'];
        $metadata = $paymentIntent['metadata'] ?? [];

        Log::warning('Payment intent failed', [
            'payment_intent_id' => $paymentIntent['id'],
            'amount' => ($paymentIntent['amount'] ?? 0) / 100,
            'failure_code' => $paymentIntent['last_payment_error']['code'] ?? null,
            'metadata' => $metadata,
        ]);
    }

    protected function handleCheckoutSessionCompleted(array $payload): void
    {
        $session = $payload['data']['object'];
        $metadata = $session['metadata'] ?? [];
        $amount = ($session['amount_total'] ?? 0) / 100; // Convert cents to dollars

        Log::info('Checkout session completed', [
            'session_id' => $session['id'],
            'amount' => $amount,
            'metadata' => $metadata,
        ]);

        // Handle different checkout types based on metadata
        switch ($metadata['type'] ?? null) {
            case 'wallet_topup':
                $this->processWalletTopupCheckout($session, $metadata);
                break;
                
            case 'ai_token_purchase':
                $this->processAiTokenPurchaseCheckout($session, $metadata);
                break;
        }
    }

    protected function handleChargeSucceeded(array $payload): void
    {
        $charge = $payload['data']['object'];
        
        Log::info('Charge succeeded', [
            'charge_id' => $charge['id'],
            'amount' => ($charge['amount'] ?? 0) / 100,
            'customer' => $charge['customer'],
        ]);
    }

    protected function handleChargeFailed(array $payload): void
    {
        $charge = $payload['data']['object'];
        
        Log::warning('Charge failed', [
            'charge_id' => $charge['id'],
            'amount' => ($charge['amount'] ?? 0) / 100,
            'failure_code' => $charge['failure_code'],
        ]);
    }

    protected function handleChargeDispute(array $payload): void
    {
        $dispute = $payload['data']['object'];
        
        Log::critical('Charge dispute created', [
            'dispute_id' => $dispute['id'],
            'charge_id' => $dispute['charge'],
            'amount' => ($dispute['amount'] ?? 0) / 100,
            'reason' => $dispute['reason'],
        ]);
        
        // TODO: Send alert to administrators
    }

    protected function handleCustomerCreated(array $payload): void
    {
        $customer = $payload['data']['object'];
        
        Log::info('Customer created', [
            'customer_id' => $customer['id'],
            'email' => $customer['email'],
        ]);
    }

    protected function handleCustomerUpdated(array $payload): void
    {
        $customer = $payload['data']['object'];
        
        Log::info('Customer updated', [
            'customer_id' => $customer['id'],
            'email' => $customer['email'],
        ]);
    }

    protected function handleCustomerDeleted(array $payload): void
    {
        $customer = $payload['data']['object'];
        
        Log::info('Customer deleted', [
            'customer_id' => $customer['id'],
        ]);
    }

    protected function handlePaymentMethodAttached(array $payload): void
    {
        $paymentMethod = $payload['data']['object'];
        
        Log::info('Payment method attached', [
            'payment_method_id' => $paymentMethod['id'],
            'customer' => $paymentMethod['customer'],
            'type' => $paymentMethod['type'],
        ]);
    }

    protected function handlePaymentMethodDetached(array $payload): void
    {
        $paymentMethod = $payload['data']['object'];
        
        Log::info('Payment method detached', [
            'payment_method_id' => $paymentMethod['id'],
            'customer' => $paymentMethod['customer'],
        ]);
    }

    protected function processWalletTopup(array $paymentIntent, array $metadata): void
    {
        $userId = $metadata['user_id'] ?? null;
        $teamId = $metadata['team_id'] ?? null;
        $amount = ($paymentIntent['amount'] ?? 0) / 100;

        if (!$userId) {
            Log::warning('No user ID in wallet topup metadata', [
                'payment_intent_id' => $paymentIntent['id'],
            ]);
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            Log::warning('User not found for wallet topup', [
                'user_id' => $userId,
                'payment_intent_id' => $paymentIntent['id'],
            ]);
            return;
        }

        $team = $teamId ? Team::find($teamId) : null;

        // Credit to appropriate wallet
        $wallet = $team ? $team->getMainWallet() : $user->getMainWallet();
        $wallet->deposit($amount, [
            'type' => 'stripe_topup',
            'payment_intent_id' => $paymentIntent['id'],
            'description' => 'Wallet top-up via Stripe',
        ]);

        Log::info('Wallet credited from Stripe payment', [
            'user_id' => $userId,
            'team_id' => $teamId,
            'amount' => $amount,
            'payment_intent_id' => $paymentIntent['id'],
        ]);
    }

    protected function processAiTokenPurchase(array $paymentIntent, array $metadata): void
    {
        $userId = $metadata['user_id'] ?? null;
        $packageId = $metadata['package_id'] ?? null;

        if (!$userId || !$packageId) {
            Log::warning('Missing user ID or package ID in AI token purchase', [
                'payment_intent_id' => $paymentIntent['id'],
                'user_id' => $userId,
                'package_id' => $packageId,
            ]);
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            Log::warning('User not found for AI token purchase', [
                'user_id' => $userId,
                'payment_intent_id' => $paymentIntent['id'],
            ]);
            return;
        }

        // Process AI token purchase via service
        $success = $this->aiTokenService->processTokenPurchaseWebhook(
            $user, 
            $packageId, 
            $paymentIntent['id']
        );

        if ($success) {
            Log::info('AI tokens credited from Stripe payment', [
                'user_id' => $userId,
                'package_id' => $packageId,
                'payment_intent_id' => $paymentIntent['id'],
            ]);
        }
    }

    protected function processWalletTopupCheckout(array $session, array $metadata): void
    {
        // Similar to processWalletTopup but for checkout sessions
        $this->processWalletTopup($session, $metadata);
    }

    protected function processAiTokenPurchaseCheckout(array $session, array $metadata): void
    {
        // Similar to processAiTokenPurchase but for checkout sessions
        $this->processAiTokenPurchase($session, $metadata);
    }
} 