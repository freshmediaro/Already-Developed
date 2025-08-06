<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'webhooks/*',
        'elfinder',
        'files/*',
        'app-store/*',
        'iframe-apps/*',
        'ai-chat/*',
        'ai-tokens/*',
        'payment-providers/*',
        'notifications/*',
        'settings/*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Development origins
        'http://localhost:3000',
        'http://localhost:5173',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
        'http://localhost:8000',
        'http://127.0.0.1:8000',
        
        // Production will be dynamically set based on tenant domains
    ],

    'allowed_origins_patterns' => [
        // Allow tenant subdomains in production
        '/^https:\/\/[a-zA-Z0-9\-]+\.' . preg_quote(parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST), '/') . '$/',
        
        // Allow iframe app domains
        '/^https:\/\/(www\.)?photopea\.com$/',
        '/^https:\/\/photopea\.com$/',
    ],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-Tenant-ID',
        'X-Team-ID',
        'X-Socket-ID',
        'Origin',
        'Cache-Control',
        'Pragma',
        'If-Modified-Since',
    ],

    'exposed_headers' => [
        'X-RateLimit-Remaining',
        'X-RateLimit-Limit',
        'X-Tenant-ID',
        'X-Team-ID',
    ],

    'max_age' => 0,

    'supports_credentials' => true,

]; 