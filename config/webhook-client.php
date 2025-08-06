<?php

return [
    'configs' => [
        [
            /*
             * This key is used to identify this config. It should be unique.
             */
            'name' => 'stripe-platform',

            /*
             * Use this option to activate this config.
             */
            'signing_secret' => env('STRIPE_WEBHOOK_SECRET'),

            /*
             * The name of the header containing the signature.
             */
            'signature_header_name' => 'Stripe-Signature',

            /*
             * This class will verify that the content of the signature header is valid.
             *
             * It should implement \Spatie\WebhookClient\SignatureValidator\SignatureValidator
             */
            'signature_validator' => \Spatie\WebhookClient\SignatureValidator\DefaultSignatureValidator::class,

            /*
             * This class determines if the webhook call should be stored and processed.
             */
            'webhook_profile' => \App\Webhooks\Stripe\StripeWebhookProfile::class,

            /*
             * This class determines the response that will be returned to the webhook call.
             */
            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,

            /*
             * The classname of the model to be created when a webhook call comes in.
             * The class should be or extend Spatie\WebhookClient\Models\WebhookCall.
             */
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,

            /*
             * The class responsible for processing the webhook request.
             *
             * This should be or extend \Spatie\WebhookClient\ProcessWebhookJob.
             */
            'process_webhook_job' => \App\Jobs\Webhooks\ProcessStripeWebhookJob::class,
        ],
        
        [
            /*
             * Tenant-specific Stripe webhooks for custom payment providers
             */
            'name' => 'stripe-tenant',

            /*
             * This will be dynamically set based on tenant configuration
             */
            'signing_secret' => env('STRIPE_TENANT_WEBHOOK_SECRET'),

            'signature_header_name' => 'Stripe-Signature',

            'signature_validator' => \Spatie\WebhookClient\SignatureValidator\DefaultSignatureValidator::class,

            'webhook_profile' => \App\Webhooks\Stripe\StripeTenantWebhookProfile::class,

            'webhook_response' => \Spatie\WebhookClient\WebhookResponse\DefaultRespondsTo::class,

            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,

            'process_webhook_job' => \App\Jobs\Webhooks\ProcessStripeTenantWebhookJob::class,
        ],
    ],

    /*
     * The integer amount of days after which models should be deleted.
     *
     * 7 deletes all records after 1 week. Set to null if no models should be deleted.
     */
    'delete_after_days' => 30,
]; 