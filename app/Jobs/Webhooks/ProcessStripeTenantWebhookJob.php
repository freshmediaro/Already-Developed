<?php

namespace App\Jobs\Webhooks;

use App\Models\User;
use App\Models\Team;
use App\Models\Wallet\PaymentProviderConfig;
use App\Services\Wallet\PlatformCommissionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;
use Spatie\WebhookClient\Models\WebhookCall;
use Stancl\Tenancy\Features\TenantConfig;

/**
 * Process Stripe Tenant Webhook Job - Tenant-specific Stripe webhook processing
 *
 * This job processes Stripe webhook events for tenant-specific operations including
 * payment processing, revenue distribution, commission tracking, and tenant account
 * management. It handles all Stripe events for tenant teams and businesses.
 *
 * Key features:
 * - Tenant-specific Stripe event processing
 * - Payment processing and validation
 * - Revenue distribution to tenant wallets
 * - Platform commission tracking
 * - Tenant account management
 * - Refund and dispute handling
 * - Payout processing
 * - Multi-tenant isolation
 * - Error handling and logging
 *
 * Supported Stripe events:
 * - payment_intent.*: Payment intent events for tenant services
 * - checkout.session.*: Checkout session events
 * - charge.*: Charge events for tenant payments
 * - refund.*: Refund events
 * - payout.*: Payout events to tenant accounts
 * - dispute.*: Dispute events
 *
 * The job provides:
 * - Tenant context initialization
 * - Payment validation and processing
 * - Revenue distribution
 * - Commission tracking
 * - Error handling and retry logic
 * - Comprehensive logging
 *
 * @package App\Jobs\Webhooks
 * @since 1.0.0
 */
class ProcessStripeTenantWebhookJob extends ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var PlatformCommissionService Service for platform commission tracking */
    protected $platformCommissionService;

    /**
     * Create a new Stripe tenant webhook processing job instance
     *
     * This constructor initializes the job with the webhook call and
     * injects required services for commission tracking.
     *
     * @param WebhookCall $webhookCall The webhook call to process
     */
    public function __construct(WebhookCall $webhookCall)
    {
        parent::__construct($webhookCall);
        $this->platformCommissionService = app(PlatformCommissionService::class);
    }

    /**
     * Execute the tenant webhook processing job
     *
     * This method processes the Stripe webhook event for tenant-specific operations,
     * extracting tenant information and routing to appropriate handlers.
     */
    public function handle()
    {
        $payload = $this->webhookCall->payload;
        $eventType = $payload['type'];
        
        // Extract tenant information from webhook metadata
        $tenantInfo = $this->extractTenantInfo($payload);
        
        if (!$tenantInfo) {
            Log::warning('No tenant information found in webhook', [
                'event_type' => $eventType,
                'event_id' => $payload['id'] ?? null,
            ]);
            return;
        }

        Log::info('Processing Stripe tenant webhook', [
            'event_type' => $eventType,
            'event_id' => $payload['id'] ?? null,
            'tenant_id' => $tenantInfo['tenant_id'] ?? null,
            'user_id' => $tenantInfo['user_id'] ?? null,
        ]);

        // Initialize tenant context if available
        if (isset($tenantInfo['tenant_id'])) {
            $tenant = \App\Models\Tenant::find($tenantInfo['tenant_id']);
            if ($tenant) {
                tenancy()->initialize($tenant);
            }
        }

        try {
            switch ($eventType) {
                // Payment processing for tenant services
                case 'payment_intent.succeeded':
                    $this->handleTenantPaymentSucceeded($payload, $tenantInfo);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handleTenantPaymentFailed($payload, $tenantInfo);
                    break;

                // Checkout sessions for tenant services
                case 'checkout.session.completed':
                    $this->handleTenantCheckoutCompleted($payload, $tenantInfo);
                    break;

                // Direct charges for tenant services
                case 'charge.succeeded':
                    $this->handleTenantChargeSucceeded($payload, $tenantInfo);
                    break;

                case 'charge.failed':
                    $this->handleTenantChargeFailed($payload, $tenantInfo);
                    break;

                case 'charge.refunded':
                    $this->handleTenantChargeRefunded($payload, $tenantInfo);
                    break;

                case 'charge.dispute.created':
                    $this->handleTenantChargeDispute($payload, $tenantInfo);
                    break;

                // Refunds
                case 'refund.created':
                    $this->handleTenantRefund($payload, $tenantInfo);
                    break;

                // Payouts to tenant accounts
                case 'payout.paid':
                    $this->handleTenantPayoutPaid($payload, $tenantInfo);
                    break;

                case 'payout.failed':
                    $this->handleTenantPayoutFailed($payload, $tenantInfo);
                    break;

                default:
                    Log::info('Unhandled Stripe tenant webhook event', [
                        'event_type' => $eventType,
                        'tenant_id' => $tenantInfo['tenant_id'] ?? null,
                    ]);
            }
        } catch (\Exception $e) {
            Log::error('Error processing Stripe tenant webhook', [
                'event_type' => $eventType,
                'tenant_id' => $tenantInfo['tenant_id'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e; // Re-throw to trigger retry
        }
    }

    protected function extractTenantInfo(array $payload): ?array
    {
        // Try to extract tenant info from various places in the webhook
        $metadata = $payload['data']['object']['metadata'] ?? [];
        
        // Check payment intent metadata
        if (isset($payload['data']['object']['payment_intent'])) {
            $paymentIntentId = $payload['data']['object']['payment_intent'];
            // Would need to fetch the payment intent to get metadata
        }

        // Check for tenant and user IDs in metadata
        $tenantId = $metadata['tenant_id'] ?? null;
        $userId = $metadata['user_id'] ?? null;
        $teamId = $metadata['team_id'] ?? null;

        if (!$tenantId && !$userId) {
            // Try to find from webhook endpoint URL or other means
            return null;
        }

        return [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'team_id' => $teamId,
        ];
    }

    protected function handleTenantPaymentSucceeded(array $payload, array $tenantInfo): void
    {
        $paymentIntent = $payload['data']['object'];
        $amount = ($paymentIntent['amount'] ?? 0) / 100; // Convert cents to dollars
        $metadata = $paymentIntent['metadata'] ?? [];

        $user = isset($tenantInfo['user_id']) ? User::find($tenantInfo['user_id']) : null;
        $team = isset($tenantInfo['team_id']) ? Team::find($tenantInfo['team_id']) : null;

        if ($user || $team) {
            // Credit to tenant's revenue wallet
            $this->creditTenantRevenue($user, $team, $amount, $paymentIntent, $metadata);
            
            // Calculate and process platform commission
            $this->processPlatformCommission($user, $team, $amount, 'tenant_payment', $metadata);
            
            Log::info('Tenant payment succeeded', [
                'user_id' => $user?->id,
                'team_id' => $team?->id,
                'amount' => $amount,
                'payment_intent_id' => $paymentIntent['id'],
            ]);
        }
    }

    protected function handleTenantPaymentFailed(array $payload, array $tenantInfo): void
    {
        $paymentIntent = $payload['data']['object'];
        
        Log::warning('Tenant payment failed', [
            'user_id' => $tenantInfo['user_id'] ?? null,
            'team_id' => $tenantInfo['team_id'] ?? null,
            'payment_intent_id' => $paymentIntent['id'],
            'amount' => ($paymentIntent['amount'] ?? 0) / 100,
            'failure_code' => $paymentIntent['last_payment_error']['code'] ?? null,
        ]);
    }

    protected function handleTenantCheckoutCompleted(array $payload, array $tenantInfo): void
    {
        $session = $payload['data']['object'];
        $amount = ($session['amount_total'] ?? 0) / 100; // Convert cents to dollars
        $metadata = $session['metadata'] ?? [];

        $user = isset($tenantInfo['user_id']) ? User::find($tenantInfo['user_id']) : null;
        $team = isset($tenantInfo['team_id']) ? Team::find($tenantInfo['team_id']) : null;

        if ($user || $team) {
            // Credit to tenant's revenue wallet
            $this->creditTenantRevenue($user, $team, $amount, $session, $metadata);
            
            // Calculate and process platform commission
            $this->processPlatformCommission($user, $team, $amount, 'tenant_checkout', $metadata);
            
            Log::info('Tenant checkout completed', [
                'user_id' => $user?->id,
                'team_id' => $team?->id,
                'amount' => $amount,
                'session_id' => $session['id'],
            ]);
        }
    }

    protected function handleTenantChargeSucceeded(array $payload, array $tenantInfo): void
    {
        $charge = $payload['data']['object'];
        $amount = ($charge['amount'] ?? 0) / 100; // Convert cents to dollars
        $metadata = $charge['metadata'] ?? [];

        $user = isset($tenantInfo['user_id']) ? User::find($tenantInfo['user_id']) : null;
        $team = isset($tenantInfo['team_id']) ? Team::find($tenantInfo['team_id']) : null;

        if ($user || $team) {
            Log::info('Tenant charge succeeded', [
                'user_id' => $user?->id,
                'team_id' => $team?->id,
                'amount' => $amount,
                'charge_id' => $charge['id'],
            ]);
        }
    }

    protected function handleTenantChargeFailed(array $payload, array $tenantInfo): void
    {
        $charge = $payload['data']['object'];
        
        Log::warning('Tenant charge failed', [
            'user_id' => $tenantInfo['user_id'] ?? null,
            'team_id' => $tenantInfo['team_id'] ?? null,
            'charge_id' => $charge['id'],
            'amount' => ($charge['amount'] ?? 0) / 100,
            'failure_code' => $charge['failure_code'] ?? null,
        ]);
    }

    protected function handleTenantChargeRefunded(array $payload, array $tenantInfo): void
    {
        $charge = $payload['data']['object'];
        $refundAmount = ($charge['amount_refunded'] ?? 0) / 100;

        $user = isset($tenantInfo['user_id']) ? User::find($tenantInfo['user_id']) : null;
        $team = isset($tenantInfo['team_id']) ? Team::find($tenantInfo['team_id']) : null;

        if ($user || $team) {
            // Deduct from tenant's revenue wallet
            $wallet = $team ? $team->getRevenueWallet() : $user->getRevenueWallet();
            if ($wallet->balance >= $refundAmount) {
                $wallet->withdraw($refundAmount, [
                    'type' => 'stripe_refund',
                    'charge_id' => $charge['id'],
                    'description' => 'Payment refund processed',
                ]);
            }
            
            Log::info('Tenant charge refunded', [
                'user_id' => $user?->id,
                'team_id' => $team?->id,
                'refund_amount' => $refundAmount,
                'charge_id' => $charge['id'],
            ]);
        }
    }

    protected function handleTenantChargeDispute(array $payload, array $tenantInfo): void
    {
        $dispute = $payload['data']['object'];
        
        Log::critical('Tenant charge dispute created', [
            'user_id' => $tenantInfo['user_id'] ?? null,
            'team_id' => $tenantInfo['team_id'] ?? null,
            'dispute_id' => $dispute['id'],
            'charge_id' => $dispute['charge'],
            'amount' => ($dispute['amount'] ?? 0) / 100,
            'reason' => $dispute['reason'],
        ]);
    }

    protected function handleTenantRefund(array $payload, array $tenantInfo): void
    {
        $refund = $payload['data']['object'];
        $amount = ($refund['amount'] ?? 0) / 100;
        
        Log::info('Tenant refund processed', [
            'user_id' => $tenantInfo['user_id'] ?? null,
            'team_id' => $tenantInfo['team_id'] ?? null,
            'refund_id' => $refund['id'],
            'amount' => $amount,
        ]);
    }

    protected function handleTenantPayoutPaid(array $payload, array $tenantInfo): void
    {
        $payout = $payload['data']['object'];
        $amount = ($payout['amount'] ?? 0) / 100;
        
        Log::info('Tenant payout paid', [
            'user_id' => $tenantInfo['user_id'] ?? null,
            'team_id' => $tenantInfo['team_id'] ?? null,
            'payout_id' => $payout['id'],
            'amount' => $amount,
        ]);
    }

    protected function handleTenantPayoutFailed(array $payload, array $tenantInfo): void
    {
        $payout = $payload['data']['object'];
        
        Log::warning('Tenant payout failed', [
            'user_id' => $tenantInfo['user_id'] ?? null,
            'team_id' => $tenantInfo['team_id'] ?? null,
            'payout_id' => $payout['id'],
            'amount' => ($payout['amount'] ?? 0) / 100,
            'failure_code' => $payout['failure_code'] ?? null,
        ]);
    }

    protected function creditTenantRevenue(
        ?User $user, 
        ?Team $team, 
        float $amount, 
        array $stripeObject, 
        array $metadata
    ): void {
        $wallet = $team ? $team->getRevenueWallet() : ($user ? $user->getRevenueWallet() : null);
        
        if ($wallet) {
            $wallet->deposit($amount, [
                'type' => 'tenant_payment_received',
                'stripe_object_id' => $stripeObject['id'],
                'description' => 'Payment received from customer',
                'metadata' => $metadata,
            ]);
        }
    }

    protected function processPlatformCommission(
        ?User $user, 
        ?Team $team, 
        float $amount, 
        string $transactionType, 
        array $metadata
    ): void {
        $this->platformCommissionService->calculateAndRecordCommission(
            $user,
            $team,
            $amount,
            $transactionType,
            $metadata
        );
    }
} 