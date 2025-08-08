<?php

namespace App\Webhooks\Stripe;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

/**
 * Stripe Tenant Webhook Profile - Tenant-specific webhook event filtering
 *
 * This profile determines which Stripe webhook events should be processed
 * for tenant-specific operations including payment processing, revenue
 * distribution, commission tracking, and tenant account management.
 *
 * Key features:
 * - Tenant-specific event filtering and validation
 * - Payment processing events
 * - Revenue distribution events
 * - Commission tracking events
 * - Tenant account management
 * - Connect account events
 * - Payout processing events
 * - Security and validation
 * - Multi-tenant isolation
 *
 * Supported event categories:
 * - Payment events: Payment intent processing for tenant services
 * - Checkout events: Checkout session completion for tenant services
 * - Charge events: Direct charge processing for tenant accounts
 * - Refund events: Refund processing for tenant transactions
 * - Connect events: Multi-tenant payment processing
 * - Payout events: Payout processing to tenant accounts
 * - Customer events: Customer management for tenant services
 * - Balance events: Balance updates for tenant accounts
 *
 * The profile provides:
 * - Tenant-specific event filtering
 * - Payment processing validation
 * - Revenue distribution tracking
 * - Commission calculation support
 * - Multi-tenant isolation
 * - Security and performance optimization
 *
 * @package App\Webhooks\Stripe
 * @since 1.0.0
 */
class StripeTenantWebhookProfile implements WebhookProfile
{
    /**
     * Determine if the webhook should be stored and processed
     *
     * This method validates the webhook request and determines whether
     * the event type should be processed for tenant-specific operations.
     *
     * @param Request $request The incoming webhook request
     * @return bool True if the webhook should be processed, false otherwise
     */
    public function shouldProcess(Request $request): bool
    {
        // Only process tenant-specific Stripe events
        $payload = $request->json()->all();
        
        if (!isset($payload['type'])) {
            return false;
        }

        $eventType = $payload['type'];

        // Tenant-specific events (payments received by tenant apps/services)
        $handledEvents = [
            // Tenant payment processing
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
            'payment_intent.canceled',
            'payment_intent.requires_action',
            
            // Checkout sessions for tenant services
            'checkout.session.completed',
            'checkout.session.expired',
            
            // Direct charges to tenant accounts
            'charge.succeeded',
            'charge.failed',
            'charge.captured',
            'charge.refunded',
            'charge.dispute.created',
            
            // Refunds
            'refund.created',
            'refund.updated',
            'refund.failed',
            
            // Connect account events (if tenant uses Connect)
            'account.updated',
            'capability.updated',
            'person.created',
            'person.updated',
            
            // Payouts to tenant accounts
            'payout.created',
            'payout.updated',
            'payout.paid',
            'payout.failed',
            
            // Balance transactions
            'balance.available',
            
            // Customer events for tenant's customers
            'customer.created',
            'customer.updated',
            'customer.source.created',
            'customer.source.updated',
            'customer.source.deleted',
        ];

        return in_array($eventType, $handledEvents);
    }
} 