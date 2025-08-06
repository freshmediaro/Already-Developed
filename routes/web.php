<?php

use Illuminate\Support\Facades\Route;
use Spatie\WebhookClient\WebhookController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| These routes handle incoming webhooks from external services like Stripe
| They are kept separate from API routes for security and isolation
|
*/

// Platform-level Stripe webhooks (Cashier and platform payments)
Route::post('/webhooks/stripe/platform', [WebhookController::class, 'handle'])
    ->name('webhooks.stripe.platform')
    ->middleware(['api'])
    ->where('configKey', 'stripe-platform');

// Tenant-specific Stripe webhooks (tenant payment providers)
Route::post('/webhooks/stripe/tenant/{tenant_id}', function($tenantId) {
    // Set tenant context for webhook processing
    $tenant = \App\Models\Tenant::find($tenantId);
    if ($tenant) {
        tenancy()->initialize($tenant);
    }
    
    return app(WebhookController::class)->handle('stripe-tenant');
})
    ->name('webhooks.stripe.tenant')
    ->middleware(['api'])
    ->where('tenant_id', '[a-f0-9\-]{36}'); // UUID format

// Laravel Cashier webhooks (handled automatically by Cashier)
Route::stripeWebhooks('/webhooks/stripe/cashier'); 