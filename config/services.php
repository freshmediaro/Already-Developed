<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AWS SNS Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for AWS SNS notifications including SMS confirmations
    | and other notification delivery channels.
    |
    */

    'aws-sns' => [
        'key' => env('AWS_SNS_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
        'secret' => env('AWS_SNS_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
        'region' => env('AWS_SNS_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
        'sender_id' => env('AWS_SNS_SENDER_ID'),
        'sms_type' => env('AWS_SNS_SMS_TYPE', 'Transactional'),
        'max_price' => env('AWS_SNS_MAX_PRICE', 0.10),
        
        // SMS Templates
        'templates' => [
            'tenant_registration' => env('AWS_SNS_TENANT_REGISTRATION_TEMPLATE', 'Welcome to {app_name}! Your account has been created. Verification code: {code}'),
            'phone_verification' => env('AWS_SNS_PHONE_VERIFICATION_TEMPLATE', 'Your {app_name} verification code is: {code}'),
            'order_confirmation' => env('AWS_SNS_ORDER_CONFIRMATION_TEMPLATE', 'Order #{order_id} confirmed! Thank you for shopping with {tenant_name}.'),
            'security_alert' => env('AWS_SNS_SECURITY_ALERT_TEMPLATE', 'Security alert for your {app_name} account. If this wasn\'t you, please contact support.'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Laravel AWS PubSub Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for backend event-driven queueing and microservice scaling
    | using Laravel AWS PubSub package.
    |
    */

    'aws-pubsub' => [
        'key' => env('AWS_PUBSUB_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
        'secret' => env('AWS_PUBSUB_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
        'region' => env('AWS_PUBSUB_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
        'topic_arn' => env('AWS_PUBSUB_TOPIC_ARN'),
        'sqs_queue' => env('AWS_PUBSUB_SQS_QUEUE'),
        
        // Event routing
        'event_routing' => [
            'tenant_notifications' => env('AWS_PUBSUB_TENANT_TOPIC'),
            'platform_events' => env('AWS_PUBSUB_PLATFORM_TOPIC'),
            'ai_processing' => env('AWS_PUBSUB_AI_TOPIC'),
            'payment_events' => env('AWS_PUBSUB_PAYMENT_TOPIC'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Service Configuration
    |--------------------------------------------------------------------------
    |
    | Global notification service settings for the application including
    | rate limiting, delivery preferences, and fallback configurations.
    |
    */

    'notifications' => [
        'rate_limiting' => [
            'sms_per_minute' => env('NOTIFICATION_SMS_RATE_LIMIT', 5),
            'email_per_minute' => env('NOTIFICATION_EMAIL_RATE_LIMIT', 10),
            'push_per_minute' => env('NOTIFICATION_PUSH_RATE_LIMIT', 20),
        ],
        
        'delivery_preferences' => [
            'default_channels' => ['database', 'broadcast'],
            'fallback_channels' => ['database'],
            'retry_attempts' => env('NOTIFICATION_RETRY_ATTEMPTS', 3),
            'retry_delay' => env('NOTIFICATION_RETRY_DELAY', 300), // seconds
        ],
        
        'tenant_customization' => [
            'allow_custom_templates' => env('NOTIFICATION_ALLOW_CUSTOM_TEMPLATES', true),
            'allow_custom_channels' => env('NOTIFICATION_ALLOW_CUSTOM_CHANNELS', true),
            'allow_disable_notifications' => env('NOTIFICATION_ALLOW_DISABLE', true),
        ],
    ],

]; 