# Wallet & Payment System Setup Guide

This guide covers the implementation of the wallet and payment system using `bavix/laravel-wallet` with tenant isolation, AI token payments, platform commissions, and multiple payment providers.

## Prerequisites

- Laravel 12+ with Tenancy for Laravel
- [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/)
- AI Chat system (see `AI_INTEGRATION_SETUP.md`)
- Stripe account for default payment processing

## Installation

### 1. Install Required Packages

```bash
composer require bavix/laravel-wallet
composer require omnipay/omnipay
composer require omnipay/stripe
composer require omnipay/paypal
```

### 2. Publish Wallet Configuration

```bash
php artisan vendor:publish --provider="Bavix\Wallet\WalletServiceProvider" --tag="config"
php artisan vendor:publish --provider="Bavix\Wallet\WalletServiceProvider" --tag="migrations"
```

### 3. Environment Variables

Add to your `.env` file:

```env
# Wallet Configuration
WALLET_CACHE_DRIVER=redis
WALLET_LOCK_DRIVER=redis
WALLET_DEFAULT_WALLET_NAME=default
WALLET_DEFAULT_WALLET_SLUG=default

# Platform Commission Settings
PLATFORM_COMMISSION_RATE=0.05
STRIPE_COMMISSION_RATE=0.029
PLATFORM_STRIPE_COMBINED_RATE=0.079

# AI Token Pricing
AI_TOKEN_FREE_MONTHLY_LIMIT=10000
AI_TOKEN_PRICE_PER_1000=0.02
AI_TOKEN_BULK_DISCOUNT_THRESHOLD=100000
AI_TOKEN_BULK_DISCOUNT_RATE=0.015

# Payment Provider Settings
STRIPE_KEY=your_stripe_publishable_key
STRIPE_SECRET=your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=your_stripe_webhook_secret

PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret
PAYPAL_SANDBOX=true

# Wallet App Settings
WALLET_WITHDRAWAL_MIN_AMOUNT=10.00
WALLET_WITHDRAWAL_MAX_AMOUNT=10000.00
WALLET_WITHDRAWAL_FEE_RATE=0.02
```

## Database Migrations

### 4. Run Wallet Migrations

First, run the base wallet migrations:

```bash
php artisan migrate
```

Then run tenant-specific migrations:

```bash
php artisan tenants:migrate --tenants=all
```

### 5. Custom Wallet Migrations

The following migrations will be created for tenant-specific wallet functionality:

- `create_tenant_wallet_settings_table.php` - Tenant wallet configurations
- `create_ai_token_packages_table.php` - AI token purchasing packages
- `create_platform_commissions_table.php` - Platform commission tracking
- `create_payment_provider_configs_table.php` - Additional payment provider settings
- `create_withdrawal_requests_table.php` - Tenant money withdrawal requests

## Configuration Files

### 6. Update config/wallet.php

```php
<?php

return [
    'package' => [
        'rateLimiter' => env('WALLET_RATE_LIMITER', 'cache'),
        'defaultWallet' => env('WALLET_DEFAULT_WALLET_NAME', 'default'),
        'walletSlug' => env('WALLET_DEFAULT_WALLET_SLUG', 'default'),
    ],
    
    'cache' => [
        'driver' => env('WALLET_CACHE_DRIVER', 'array'),
        'ttl' => 24 * 60 * 60, // 24 hours
    ],
    
    'lock' => [
        'driver' => env('WALLET_LOCK_DRIVER', 'array'),
        'seconds' => 1,
    ],
    
    // Tenant-specific settings
    'tenant_isolation' => [
        'enabled' => true,
        'wallet_prefix' => 'tenant-{tenant_id}',
        'team_wallet_format' => 'team-{team_id}',
    ],
    
    // AI Token settings
    'ai_tokens' => [
        'free_monthly_limit' => env('AI_TOKEN_FREE_MONTHLY_LIMIT', 10000),
        'price_per_1000' => env('AI_TOKEN_PRICE_PER_1000', 0.02),
        'bulk_discount_threshold' => env('AI_TOKEN_BULK_DISCOUNT_THRESHOLD', 100000),
        'bulk_discount_rate' => env('AI_TOKEN_BULK_DISCOUNT_RATE', 0.015),
        'wallet_slug' => 'ai-tokens',
    ],
    
    // Platform commission
    'platform' => [
        'commission_rate' => env('PLATFORM_COMMISSION_RATE', 0.05),
        'stripe_rate' => env('STRIPE_COMMISSION_RATE', 0.029),
        'combined_rate' => env('PLATFORM_STRIPE_COMBINED_RATE', 0.079),
        'wallet_slug' => 'platform-revenue',
    ],
    
    // Withdrawal settings
    'withdrawals' => [
        'min_amount' => env('WALLET_WITHDRAWAL_MIN_AMOUNT', 10.00),
        'max_amount' => env('WALLET_WITHDRAWAL_MAX_AMOUNT', 10000.00),
        'fee_rate' => env('WALLET_WITHDRAWAL_FEE_RATE', 0.02),
        'processing_days' => 3,
    ],
];
```

### 7. Create config/payment-providers.php

```php
<?php

return [
    'default' => 'stripe',
    
    'providers' => [
        'stripe' => [
            'driver' => 'Stripe',
            'key' => env('STRIPE_KEY'),
            'secret' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'currency' => 'USD',
            'enabled' => true,
            'monthly_fee' => 0, // Default provider - no monthly fee
        ],
        
        'paypal' => [
            'driver' => 'PayPal_Express',
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'client_secret' => env('PAYPAL_CLIENT_SECRET'),
            'sandbox' => env('PAYPAL_SANDBOX', true),
            'currency' => 'USD',
            'enabled' => false,
            'monthly_fee' => 29.99, // Additional provider monthly fee
        ],
        
        'square' => [
            'driver' => 'Square',
            'application_id' => env('SQUARE_APPLICATION_ID'),
            'access_token' => env('SQUARE_ACCESS_TOKEN'),
            'environment' => env('SQUARE_ENVIRONMENT', 'sandbox'),
            'currency' => 'USD',
            'enabled' => false,
            'monthly_fee' => 19.99,
        ],
        
        'authorize_net' => [
            'driver' => 'AuthorizeNet_AIM',
            'api_login_id' => env('AUTHNET_API_LOGIN_ID'),
            'transaction_key' => env('AUTHNET_TRANSACTION_KEY'),
            'test_mode' => env('AUTHNET_TEST_MODE', true),
            'currency' => 'USD',
            'enabled' => false,
            'monthly_fee' => 24.99,
        ],
    ],
    
    'features' => [
        'subscriptions' => true,
        'one_time_payments' => true,
        'refunds' => true,
        'partial_refunds' => true,
        'webhooks' => true,
        'recurring_billing' => true,
    ],
];
```

## Service Provider Registration

### 8. Register Services in AppServiceProvider

Add to `app/Providers/AppServiceProvider.php`:

```php
public function register(): void
{
    // ... existing code ...
    
    $this->app->singleton(WalletService::class);
    $this->app->singleton(PaymentProviderService::class);
    $this->app->singleton(AiTokenService::class);
    $this->app->singleton(PlatformCommissionService::class);
}

public function boot(): void
{
    // ... existing code ...
    
    // Wallet tenant isolation
    Wallet::observe(WalletTenantObserver::class);
    Transaction::observe(TransactionTenantObserver::class);
}
```

### 9. Middleware Registration

Add to `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        // ... existing middleware ...
        'wallet-tenant' => \App\Http\Middleware\WalletTenantMiddleware::class,
        'payment-provider' => \App\Http\Middleware\PaymentProviderMiddleware::class,
    ]);
})
```

## Model Integration

### 10. Update User Model

Add wallet functionality to `app/Models/User.php`:

```php
use Bavix\Wallet\Traits\HasWallet;
use Bavix\Wallet\Traits\HasWallets;
use Bavix\Wallet\Interfaces\Wallet;

class User extends Authenticatable implements Wallet
{
    use HasWallet, HasWallets;
    
    // ... existing code ...
    
    public function getAiTokenWallet()
    {
        return $this->getWallet('ai-tokens');
    }
    
    public function getMainWallet()
    {
        return $this->getWallet('default');
    }
    
    public function hasEnoughAiTokens(int $tokensNeeded): bool
    {
        return $this->getAiTokenWallet()->balance >= $tokensNeeded;
    }
}
```

### 11. Update Team Model

Add to `app/Models/Team.php`:

```php
use Bavix\Wallet\Traits\HasWallet;
use Bavix\Wallet\Traits\HasWallets;
use Bavix\Wallet\Interfaces\Wallet;

class Team extends Model implements Wallet
{
    use HasWallet, HasWallets;
    
    // ... existing code ...
    
    public function getRevenueWallet()
    {
        return $this->getWallet('revenue');
    }
    
    public function getExpenseWallet()
    {
        return $this->getWallet('expenses');
    }
}
```

## Directory Structure

Create the following directories:

```bash
mkdir -p app/Services/Wallet
mkdir -p app/Http/Controllers/Api/Wallet
mkdir -p app/Models/Wallet
mkdir -p app/Observers/Wallet
mkdir -p resources/js/Components/Apps/Wallet
mkdir -p resources/js/Components/SystemTray/Wallet
```

## AI Token Integration

### 12. Update AI Chat Service

Modify `app/Services/AiChatService.php` to check and consume AI tokens:

```php
public function processMessage(array $context): array
{
    $user = User::find($context['user_id']);
    $estimatedTokens = $this->estimateTokenUsage($context['message']);
    
    // Check if user has enough tokens
    if (!$user->hasEnoughAiTokens($estimatedTokens)) {
        return [
            'error' => 'insufficient_tokens',
            'message' => 'You have insufficient AI tokens. Please purchase more tokens to continue.',
            'tokens_needed' => $estimatedTokens,
            'current_balance' => $user->getAiTokenWallet()->balance,
        ];
    }
    
    // Process message...
    $response = $this->sendToPrism($context, $modelConfig);
    
    // Deduct tokens after successful processing
    $actualTokensUsed = $response['usage']['total_tokens'] ?? $estimatedTokens;
    $user->getAiTokenWallet()->withdraw($actualTokensUsed);
    
    // Track usage and cost
    $this->trackUsage($context, $actualTokensUsed, $this->calculateCost($actualTokensUsed, $modelConfig['model']));
    
    return $response;
}
```

## Frontend Integration

### 13. Wallet Applications

Two wallet applications will be created:

1. **Standalone Wallet App** (`resources/js/Components/Apps/Wallet/WalletApp.vue`)
   - Full window application with sidebar, toolbar
   - Complete wallet management features
   - Transaction history, payment methods, withdrawals
   - AI token purchasing and usage analytics

2. **System Tray Wallet Panel** (`resources/js/Components/SystemTray/WalletPanel.vue`)
   - Mobile banking style quick panel
   - Balance overview, quick actions
   - Recent transactions, quick AI token purchase
   - Withdrawal request shortcuts

## API Endpoints

### 14. Wallet API Routes

Add to `routes/tenant-desktop.php`:

```php
// Wallet Management
Route::prefix('wallet')->middleware(['auth:sanctum', 'wallet-tenant'])->group(function () {
    Route::get('/balance', [WalletController::class, 'getBalance']);
    Route::get('/transactions', [WalletController::class, 'getTransactions']);
    Route::post('/withdraw', [WalletController::class, 'requestWithdrawal']);
    Route::get('/withdrawal-requests', [WalletController::class, 'getWithdrawalRequests']);
});

// AI Tokens
Route::prefix('ai-tokens')->middleware(['auth:sanctum', 'wallet-tenant'])->group(function () {
    Route::get('/balance', [AiTokenController::class, 'getBalance']);
    Route::get('/packages', [AiTokenController::class, 'getPackages']);
    Route::post('/purchase', [AiTokenController::class, 'purchaseTokens']);
    Route::get('/usage-history', [AiTokenController::class, 'getUsageHistory']);
});

// Payment Providers
Route::prefix('payment-providers')->middleware(['auth:sanctum', 'payment-provider'])->group(function () {
    Route::get('/', [PaymentProviderController::class, 'getAvailable']);
    Route::post('/enable', [PaymentProviderController::class, 'enableProvider']);
    Route::post('/disable', [PaymentProviderController::class, 'disableProvider']);
    Route::put('/configure', [PaymentProviderController::class, 'updateConfig']);
});
```

## Security & Performance

### 15. Security Considerations

- All wallet operations are tenant-isolated
- API keys for payment providers are encrypted
- Transaction logs are immutable
- Withdrawal requests require verification
- Platform commission calculations are transparent

### 16. Performance Optimizations

- Redis caching for wallet balances
- Background job processing for transactions
- Batch token consumption for AI operations
- Optimized database indexes for wallet queries

## Testing

### 17. Seed Data

```bash
php artisan db:seed --class=WalletSeeder
php artisan tenants:seed --class=AiTokenPackageSeeder --tenants=all
```

### 18. Test Commands

```bash
# Test wallet functionality
php artisan wallet:test-transaction

# Test AI token consumption
php artisan ai:test-token-usage

# Test platform commission calculation
php artisan platform:test-commission
```

## Production Deployment

### 19. Production Checklist

- [ ] Configure Redis for wallet caching
- [ ] Set up webhook endpoints for payment providers
- [ ] Configure proper SSL certificates
- [ ] Set up monitoring for transaction failures
- [ ] Configure backup strategy for wallet data
- [ ] Set up alerts for low token balances
- [ ] Configure rate limiting for payment endpoints

## Troubleshooting

### Common Issues

1. **Wallet balance inconsistencies**
   - Check Redis cache synchronization
   - Verify transaction logs
   - Run wallet balance verification command

2. **AI token deduction failures**
   - Check user wallet permissions
   - Verify token estimation accuracy
   - Review transaction rollback logs

3. **Payment provider integration issues**
   - Verify API credentials
   - Check webhook configurations
   - Review provider-specific logs

4. **Platform commission calculation errors**
   - Verify commission rate configurations
   - Check transaction rounding logic
   - Review commission audit logs

## Support

For additional support with wallet and payment integration:
- Review Laravel Wallet documentation: https://laravel-wallet.readthedocs.io/
- Check Omnipay documentation: https://omnipay.thephpleague.com/
- Review tenant isolation implementation details 