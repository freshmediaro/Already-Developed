<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            // Register tenant routes
            Route::middleware([
                'web',
                InitializeTenancyByDomain::class,
                PreventAccessFromCentralDomains::class,
            ])->group(base_path('routes/tenant.php'));

            // Register tenant API routes
            Route::prefix('api')
                ->middleware([
                    'api',
                    InitializeTenancyByDomain::class,
                    PreventAccessFromCentralDomains::class,
                ])
                ->group(base_path('routes/tenant-api.php'));

            // Register desktop API routes
            Route::prefix('api')
                ->middleware([
                    'api',
                    'auth:sanctum',
                    InitializeTenancyByDomain::class,
                    PreventAccessFromCentralDomains::class,
                ])
                ->group(base_path('routes/tenant-desktop.php'));

            // Register file management routes
            Route::prefix('api')
                ->middleware([
                    'api',
                    'auth:sanctum',
                    InitializeTenancyByDomain::class,
                    PreventAccessFromCentralDomains::class,
                ])
                ->group(base_path('routes/tenant-files.php'));

            // Register iframe app routes
            Route::prefix('api')
                ->middleware([
                    'api',
                    'auth:sanctum',
                    InitializeTenancyByDomain::class,
                    PreventAccessFromCentralDomains::class,
                ])
                ->group(base_path('routes/tenant-iframe-apps.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Define tenancy middleware groups
        $middleware->group('tenant', [
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
        ]);

        $middleware->group('tenant.web', [
            'web',
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
        ]);

        $middleware->group('tenant.api', [
            'api',
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
        ]);

        $middleware->group('tenant.auth', [
            'api',
            'auth:sanctum',
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
        ]);

        // Universal middleware for routes that work on both central and tenant domains
        $middleware->group('universal', []);

        // Security middleware (apply globally)
        $middleware->append([
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
        ]);

        // Middleware aliases for easier reference
        $middleware->alias([
            'tenant.identify' => InitializeTenancyByDomain::class,
            'tenant.prevent-central' => PreventAccessFromCentralDomains::class,
            'tenant.permissions' => \App\Http\Middleware\TenantPermissionsMiddleware::class,
            'iframe.security' => \App\Http\Middleware\IframeSecurityMiddleware::class,
            'ai-chat-tenant' => \App\Http\Middleware\AiChatTenantMiddleware::class,
            'wallet-tenant' => \App\Http\Middleware\WalletTenantMiddleware::class,
            'rate-limit' => \App\Http\Middleware\RateLimitingMiddleware::class,
            'request-validation' => \App\Http\Middleware\RequestValidationMiddleware::class,
            'api-logging' => \App\Http\Middleware\ApiLoggingMiddleware::class,
            'cache-headers' => \App\Http\Middleware\CacheHeadersMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->withProviders([
        App\Providers\AppServiceProvider::class,
        App\Providers\TenancyServiceProvider::class,
    ]); 