<?php

namespace App\Webhooks\Stripe;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

/**
 * Stripe Webhook Profile - Platform-level webhook event filtering
 *
 * This profile determines which Stripe webhook events should be processed
 * for platform-level operations including subscription management, payment
 * processing, wallet top-ups, and customer management.
 *
 * Key features:
 * - Event type filtering and validation
 * - Platform-specific event handling
 * - Cashier integration support
 * - Payment processing events
 * - Customer management events
 * - Connect account events
 * - Security and validation
 * - Performance optimization
 *
 * Supported event categories:
 * - Subscription events: Cashier subscription lifecycle
 * - Payment events: Payment intent and method management
 * - Checkout events: Checkout session completion
 * - Charge events: Direct charge processing
 * - Customer events: Customer account management
 * - Connect events: Multi-tenant payment processing
 *
 * The profile provides:
 * - Event filtering and validation
 * - Platform-specific event handling
 * - Security and performance optimization
 * - Comprehensive event coverage
 *
 * @package App\Webhooks\Stripe
 * @since 1.0.0
 */
class StripeWebhookProfile implements WebhookProfile
{
    /**
     * Determine if the webhook should be stored and processed
     *
     * This method validates the webhook request and determines whether
     * the event type should be processed based on platform requirements.
     *
     * @param Request $request The incoming webhook request
     * @return bool True if the webhook should be processed, false otherwise
     */
    public function shouldProcess(Request $request): bool
    {
        // Only process specific Stripe events that we care about
        $payload = $request->json()->all();
        
        if (!isset($payload['type'])) {
            return false;
        }

        $eventType = $payload['type'];

        // Platform-level events we handle (Cashier-related and platform management)
        $handledEvents = [
            // Cashier subscription events
            'customer.subscription.created',
            'customer.subscription.updated',
            'customer.subscription.deleted',
            'customer.subscription.trial_will_end',
            'invoice.payment_succeeded',
            'invoice.payment_failed',
            'invoice.created',
            'invoice.updated',
            
            // Payment events
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
            'payment_intent.canceled',
            'payment_method.attached',
            'payment_method.detached',
            
            // Checkout events
            'checkout.session.completed',
            'checkout.session.expired',
            
            // Platform wallet top-ups
            'charge.succeeded',
            'charge.failed',
            'charge.dispute.created',
            
            // Customer events
            'customer.created',
            'customer.updated',
            'customer.deleted',
            
            // Connect events for tenant payments
            'account.updated',
            'account.application.deauthorized',
            'transfer.created',
            'transfer.failed',
        ];

        return in_array($eventType, $handledEvents);
    }
} 