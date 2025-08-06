<?php

use Laravel\Cashier\Cashier;

return [

    /*
    |--------------------------------------------------------------------------
    | Stripe Keys
    |--------------------------------------------------------------------------
    |
    | The Stripe publishable and secret keys give you access to Stripe's
    | API. The "publishable" key is typically used when interacting with
    | Stripe.js while the "secret" key accesses private API endpoints.
    |
    */

    'key' => env('STRIPE_KEY'),

    'secret' => env('STRIPE_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Cashier Webhook Secret
    |--------------------------------------------------------------------------
    |
    | This is the webhook endpoint secret that is used to verify that the
    | incoming webhook is actually from Stripe. You should set this to
    | the endpoint secret from your Stripe webhook configuration.
    |
    */

    'webhook' => [
        'secret' => env('STRIPE_WEBHOOK_SECRET'),
        'tolerance' => env('STRIPE_WEBHOOK_TOLERANCE', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cashier Currency
    |--------------------------------------------------------------------------
    |
    | This is the default currency that will be used when generating charges
    | from your application. Of course, you are welcome to use any of the
    | various world currencies that your payment provider supports.
    |
    */

    'currency' => env('CASHIER_CURRENCY', 'usd'),

    /*
    |--------------------------------------------------------------------------
    | Currency Locale
    |--------------------------------------------------------------------------
    |
    | This is the default locale in which your money values are formatted in
    | for display. To utilize other locales besides the default en locale
    | verify you have the "intl" PHP extension installed on the system.
    |
    */

    'currency_locale' => env('CASHIER_CURRENCY_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Cashier Logger
    |--------------------------------------------------------------------------
    |
    | This setting defines which logging channel will be used by the Cashier
    | library to write log messages. You are free to specify any of your
    | logging channels listed inside the "logging" configuration file.
    |
    */

    'logger' => env('CASHIER_LOGGER'),

    /*
    |--------------------------------------------------------------------------
    | Stripe SDK Version
    |--------------------------------------------------------------------------
    |
    | This option allows you to override the default Stripe SDK version that
    | will be used by Cashier. You should ensure that the specified version
    | is compatible with the Cashier version you are currently running.
    |
    */

    'stripe_version' => env('STRIPE_API_VERSION'),

    /*
    |--------------------------------------------------------------------------
    | Cashier Model
    |--------------------------------------------------------------------------
    |
    | This is the model in your application that includes the Billable trait
    | provided by Cashier. It will serve as the primary model you use while
    | interacting with Cashier related methods, subscriptions, and so on.
    |
    */

    'model' => env('CASHIER_MODEL', App\Models\User::class),

    /*
    |--------------------------------------------------------------------------
    | Invoice Paper
    |--------------------------------------------------------------------------
    |
    | This option is the unit of measurement that will be used when generating
    | invoices for your application. This will be used when creating invoice
    | paper sizes as well as configuring the invoice generation settings.
    |
    */

    'paper' => env('CASHIER_PAPER', 'letter'),

    /*
    |--------------------------------------------------------------------------
    | Stripe Connect
    |--------------------------------------------------------------------------
    |
    | This option determines whether Stripe Connect is enabled for your
    | application. If enabled, Cashier will attempt to use Connect when
    | processing payments from connected accounts.
    |
    */

    'connect' => [
        'enabled' => env('STRIPE_CONNECT_ENABLED', false),
        'client_id' => env('STRIPE_CONNECT_CLIENT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Isolation
    |--------------------------------------------------------------------------
    |
    | These settings control how Cashier handles multi-tenant scenarios.
    | When enabled, Cashier will use tenant-scoped Stripe customer IDs
    | and allow different Stripe configurations per tenant.
    |
    */

    'tenant_isolation' => [
        'enabled' => env('CASHIER_TENANT_ISOLATION', true),
        'customer_id_format' => 'stripe_id_{tenant_id}',
        'allow_tenant_stripe_keys' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Platform Integration
    |--------------------------------------------------------------------------
    |
    | Settings for integrating Cashier with the platform's wallet system
    | and commission structure.
    |
    */

    'platform' => [
        'wallet_integration' => true,
        'auto_credit_wallet' => true,
        'commission_rate' => env('PLATFORM_COMMISSION_RATE', 0.05),
        'webhook_processor' => \App\Jobs\Webhooks\ProcessStripeWebhookJob::class,
    ],

]; 