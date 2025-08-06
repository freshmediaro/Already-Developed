<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Settings Repository
    |--------------------------------------------------------------------------
    |
    | Specify the repository that should be used to store settings.
    | The database repository will store settings in a database table.
    |
    */

    'repository' => \Rawilk\Settings\Repositories\DatabaseRepository::class,

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Settings will be cached to speed up retrieval. You can specify
    | how long the cache should last and the cache key prefix.
    |
    */

    'cache' => [
        /*
         * Enable or disable caching of settings.
         */
        'enable' => env('SETTINGS_CACHE_ENABLED', true),

        /*
         * The cache store to use for caching settings.
         */
        'store' => env('SETTINGS_CACHE_STORE', env('CACHE_DRIVER', 'redis')),

        /*
         * The time (in seconds) that settings should be cached for.
         */
        'ttl' => env('SETTINGS_CACHE_TTL', 300),

        /*
         * The cache prefix to use for caching settings.
         */
        'prefix' => env('SETTINGS_CACHE_PREFIX', 'laravel_settings'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the database repository.
    |
    */

    'database' => [
        /*
         * The database connection to use for storing settings.
         */
        'connection' => env('DB_CONNECTION', 'mysql'),

        /*
         * The table to store settings in.
         */
        'table' => env('SETTINGS_TABLE_NAME', 'settings'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-tenancy Settings
    |--------------------------------------------------------------------------
    |
    | Configure how settings should work in a multi-tenant environment.
    |
    */

    'tenancy' => [
        /*
         * Enable tenant-aware settings. When enabled, settings will be
         * automatically scoped to the current tenant context.
         */
        'enabled' => env('SETTINGS_TENANT_AWARE', true),

        /*
         * The tenant context resolver. This should return the current
         * tenant identifier or null if not in a tenant context.
         */
        'context_resolver' => function () {
            // Return current tenant ID for tenant-scoped settings
            if (function_exists('tenant') && tenant()) {
                return tenant('id');
            }
            
            return null;
        },

        /*
         * The team context resolver. This should return the current
         * team identifier or null if not in a team context.
         */
        'team_resolver' => function () {
            // Return current team ID if available
            if (auth()->check() && auth()->user()->currentTeam) {
                return auth()->user()->currentTeam->id;
            }
            
            return null;
        },

        /*
         * Cache key modifier for tenant-specific settings
         */
        'cache_key_modifier' => function ($key) {
            $tenantId = tenant('id');
            $teamId = auth()->check() && auth()->user()->currentTeam 
                ? auth()->user()->currentTeam->id 
                : null;
            
            if ($tenantId && $teamId) {
                return "tenant_{$tenantId}_team_{$teamId}_{$key}";
            } elseif ($tenantId) {
                return "tenant_{$tenantId}_{$key}";
            }
            
            return $key;
        },
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Define default values for settings that should be available
    | throughout the application.
    |
    */

    'defaults' => [
        // Application theme and appearance
        'app.theme' => 'dark',
        'app.accent_color' => 'blue',
        'app.wallpaper' => 'default',
        'app.language' => 'en',

        // Desktop settings
        'desktop.show_icons' => true,
        'desktop.auto_arrange' => false,
        'desktop.icon_size' => 80,
        'desktop.notifications_enabled' => true,
        'desktop.sound_effects' => true,

        // AI settings
        'ai.use_defaults' => true,
        'ai.model' => 'gpt-4',
        'ai.privacy_level' => 'public',
        'ai.max_tokens' => 4000,
        'ai.temperature' => 0.7,

        // Wallet settings
        'wallet.auto_topup_enabled' => false,
        'wallet.auto_topup_threshold' => 10.00,
        'wallet.auto_topup_amount' => 50.00,
        'wallet.email_notifications' => true,
        'wallet.sms_notifications' => false,

        // Payment provider settings
        'payments.default_provider' => 'stripe-cashier',
        'payments.currency' => 'usd',
        'payments.test_mode' => false,

        // Security settings
        'security.two_factor_enabled' => false,
        'security.session_timeout' => 120,
        'security.password_expiry_days' => 90,

        // File management settings
        'files.max_upload_size' => '100M',
        'files.allowed_extensions' => 'jpg,jpeg,png,gif,pdf,doc,docx,txt,csv',
        'files.storage_quota_gb' => 5,

        // Notification preferences
        'notifications.email_enabled' => true,
        'notifications.desktop_enabled' => true,
        'notifications.sound_enabled' => true,
        'notifications.marketing_enabled' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Encrypted Settings
    |--------------------------------------------------------------------------
    |
    | Define which settings should be encrypted when stored in the database.
    | These settings will be automatically encrypted/decrypted.
    |
    */

    'encrypted' => [
        'ai.api_key',
        'payments.stripe_secret_key',
        'payments.paypal_client_secret',
        'integrations.api_keys.*',
        'webhooks.secrets.*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cast Settings
    |--------------------------------------------------------------------------
    |
    | Define how settings should be cast when retrieved. This allows
    | automatic type conversion for settings values.
    |
    */

    'casts' => [
        'desktop.show_icons' => 'boolean',
        'desktop.auto_arrange' => 'boolean',
        'desktop.icon_size' => 'integer',
        'desktop.notifications_enabled' => 'boolean',
        'desktop.sound_effects' => 'boolean',
        
        'ai.use_defaults' => 'boolean',
        'ai.max_tokens' => 'integer',
        'ai.temperature' => 'float',
        
        'wallet.auto_topup_enabled' => 'boolean',
        'wallet.auto_topup_threshold' => 'float',
        'wallet.auto_topup_amount' => 'float',
        'wallet.email_notifications' => 'boolean',
        'wallet.sms_notifications' => 'boolean',
        
        'payments.test_mode' => 'boolean',
        
        'security.two_factor_enabled' => 'boolean',
        'security.session_timeout' => 'integer',
        'security.password_expiry_days' => 'integer',
        
        'files.storage_quota_gb' => 'integer',
        
        'notifications.email_enabled' => 'boolean',
        'notifications.desktop_enabled' => 'boolean',
        'notifications.sound_enabled' => 'boolean',
        'notifications.marketing_enabled' => 'boolean',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Rules
    |--------------------------------------------------------------------------
    |
    | Define validation rules for settings to ensure data integrity.
    |
    */

    'validation' => [
        'app.theme' => 'in:light,dark,auto',
        'app.accent_color' => 'in:blue,green,red,purple,orange,yellow',
        'app.language' => 'in:en,es,fr,de,it,pt',
        
        'desktop.icon_size' => 'integer|min:32|max:128',
        
        'ai.model' => 'in:gpt-3.5-turbo,gpt-4,gpt-4-turbo,claude-3-sonnet,claude-3-opus',
        'ai.privacy_level' => 'in:public,private,team',
        'ai.max_tokens' => 'integer|min:100|max:8000',
        'ai.temperature' => 'numeric|min:0|max:2',
        
        'wallet.auto_topup_threshold' => 'numeric|min:1|max:1000',
        'wallet.auto_topup_amount' => 'numeric|min:5|max:5000',
        
        'payments.currency' => 'in:usd,eur,gbp,cad,aud',
        
        'security.session_timeout' => 'integer|min:15|max:1440',
        'security.password_expiry_days' => 'integer|min:30|max:365',
        
        'files.storage_quota_gb' => 'integer|min:1|max:1000',
    ],

    /*
    |--------------------------------------------------------------------------
    | Settings Groups
    |--------------------------------------------------------------------------
    |
    | Define logical groups for settings to make management easier.
    |
    */

    'groups' => [
        'appearance' => [
            'app.theme',
            'app.accent_color',
            'app.wallpaper',
            'app.language',
        ],
        
        'desktop' => [
            'desktop.show_icons',
            'desktop.auto_arrange',
            'desktop.icon_size',
            'desktop.notifications_enabled',
            'desktop.sound_effects',
        ],
        
        'ai' => [
            'ai.use_defaults',
            'ai.model',
            'ai.api_key',
            'ai.privacy_level',
            'ai.max_tokens',
            'ai.temperature',
        ],
        
        'wallet' => [
            'wallet.auto_topup_enabled',
            'wallet.auto_topup_threshold',
            'wallet.auto_topup_amount',
            'wallet.email_notifications',
            'wallet.sms_notifications',
        ],
        
        'payments' => [
            'payments.default_provider',
            'payments.currency',
            'payments.test_mode',
        ],
        
        'security' => [
            'security.two_factor_enabled',
            'security.session_timeout',
            'security.password_expiry_days',
        ],
        
        'files' => [
            'files.max_upload_size',
            'files.allowed_extensions',
            'files.storage_quota_gb',
        ],
        
        'notifications' => [
            'notifications.email_enabled',
            'notifications.desktop_enabled',
            'notifications.sound_enabled',
            'notifications.marketing_enabled',
        ],
    ],

]; 