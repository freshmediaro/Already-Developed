# Environment Configuration Guide

This document outlines all the environment variables required for deploying the Laravel Cashier + Spatie Webhook Client + Laravel Settings integrated platform.

## Core Application Configuration

```env
# Application Configuration
APP_NAME="OS Desktop Platform"
APP_ENV=production
APP_KEY=base64:your_32_character_app_key
APP_DEBUG=false
APP_URL=https://your-domain.com

# Logging
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=info

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=os_desktop_platform
DB_USERNAME=root
DB_PASSWORD=your_secure_password
```

## Cache and Session Configuration

```env
# Cache and Session Configuration
CACHE_DRIVER=redis
CACHE_PREFIX=os_desktop
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

# Queue Configuration
QUEUE_CONNECTION=redis
QUEUE_FAILED_DRIVER=database

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
```

## Tenancy Configuration

```env
# Tenancy Configuration
TENANCY_TENANT_CONNECTION=tenant
TENANCY_TENANT_DB_PREFIX=tenant_
TENANCY_CENTRAL_DOMAINS=your-domain.com
```

## Storage Configuration

```env
# Cloudflare R2 Storage Configuration
CLOUDFLARE_R2_ACCESS_KEY_ID=your_r2_access_key_id
CLOUDFLARE_R2_SECRET_ACCESS_KEY=your_r2_secret_access_key
CLOUDFLARE_R2_BUCKET=your_r2_bucket_name
CLOUDFLARE_R2_ENDPOINT=https://your_account_id.r2.cloudflarestorage.com
CLOUDFLARE_R2_URL=https://your_custom_domain.com
CLOUDFLARE_R2_USE_PATH_STYLE_ENDPOINT=false

# File Management Configuration
ELFINDER_DRIVER=LocalFileSystem
ELFINDER_TENANT_AWARE=true
ELFINDER_MAX_UPLOAD_SIZE=100M
ELFINDER_ROOT_PATH=storage/app/public
ELFINDER_URL_PREFIX=/storage
```

## Payment and Wallet Configuration

```env
# Platform Stripe Configuration (Default for all tenants)
STRIPE_KEY=pk_live_your_stripe_publishable_key
STRIPE_SECRET=sk_live_your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=whsec_your_stripe_webhook_secret

# Cashier Configuration
CASHIER_CURRENCY=usd
CASHIER_CURRENCY_LOCALE=en
CASHIER_MODEL=App\Models\User
CASHIER_TENANT_ISOLATION=true
CASHIER_PAPER=letter
CASHIER_LOGGER=

# Stripe Connect (for tenant payment processing)
STRIPE_CONNECT_ENABLED=false
STRIPE_CONNECT_CLIENT_ID=ca_your_stripe_connect_client_id

# Platform Commission Settings
PLATFORM_COMMISSION_RATE=0.05
STRIPE_COMMISSION_RATE=0.029
PLATFORM_STRIPE_COMBINED_RATE=0.079

# Webhook Configuration
STRIPE_WEBHOOK_TOLERANCE=300
STRIPE_TENANT_WEBHOOK_SECRET=whsec_tenant_webhook_secret

# Wallet Configuration
WALLET_CACHE_DRIVER=redis
WALLET_LOCK_DRIVER=redis
WALLET_DEFAULT_WALLET_NAME=default
WALLET_DEFAULT_WALLET_SLUG=default

# AI Token Configuration
AI_TOKEN_FREE_MONTHLY_LIMIT=10000
AI_TOKEN_PRICE_PER_1000=0.02
AI_TOKEN_BULK_DISCOUNT_THRESHOLD=100000
AI_TOKEN_BULK_DISCOUNT_RATE=0.015

# Wallet App Settings
WALLET_WITHDRAWAL_MIN_AMOUNT=10.00
WALLET_WITHDRAWAL_MAX_AMOUNT=10000.00
WALLET_WITHDRAWAL_FEE_RATE=0.02

# PayPal Configuration (Alternative Payment Provider)
PAYPAL_CLIENT_ID=your_paypal_client_id
PAYPAL_CLIENT_SECRET=your_paypal_client_secret
PAYPAL_SANDBOX=false
```

## AI and Chat Configuration

```env
# OpenAI Configuration
OPENAI_API_KEY=sk-your-openai-api-key
OPENAI_ORGANIZATION=your-organization-id
OPENAI_REQUEST_TIMEOUT=30

# Prism Configuration (Multi-LLM Support)
PRISM_DEFAULT_PROVIDER=openai
PRISM_ANTHROPIC_API_KEY=your-anthropic-api-key
PRISM_GROQ_API_KEY=your-groq-api-key
PRISM_COHERE_API_KEY=your-cohere-api-key

# AI Chat Settings
AI_CHAT_MAX_TOKENS=4000
AI_CHAT_TEMPERATURE=0.7
AI_CHAT_MAX_HISTORY=10
AI_CHAT_SESSION_TIMEOUT=3600
AI_CHAT_ENABLE_TOOLS=true
AI_CHAT_ENABLE_IMAGE_ANALYSIS=true
AI_CHAT_ENABLE_DOCUMENT_ANALYSIS=true

# File Upload Settings for AI
AI_CHAT_MAX_FILE_SIZE=10M
AI_CHAT_ALLOWED_IMAGE_TYPES="jpg,jpeg,png,gif,webp"
AI_CHAT_ALLOWED_DOCUMENT_TYPES="pdf,doc,docx,txt,csv,json"

# Command Execution Settings
AI_COMMAND_EXECUTION_ENABLED=true
AI_COMMAND_AUDIT_LOG=true
AI_COMMAND_RATE_LIMIT=100

# Tenant Isolation Settings
AI_CHAT_TENANT_ISOLATION=true
AI_CHAT_USAGE_TRACKING=true
AI_CHAT_COST_TRACKING=true
```

## Settings Management Configuration

```env
# Laravel Settings Configuration
SETTINGS_CACHE_DRIVER=redis
SETTINGS_CACHE_PREFIX=settings
SETTINGS_TABLE_NAME=settings
SETTINGS_TENANT_AWARE=true
```

## Aimeos E-commerce Configuration

```env
# Aimeos Core Configuration
AIMEOS_DRIVER=Laravel
AIMEOS_SHOP_DOMAINS="your-tenant-domains.com,*.yourdomain.com"
AIMEOS_ADMIN_TEMPLATE=admin/jqadm
AIMEOS_SHOP_TEMPLATE=catalog/filter/tree

# Aimeos Database Configuration
AIMEOS_DB_CONNECTION=mysql
AIMEOS_DB_HOST=127.0.0.1
AIMEOS_DB_PORT=3306
AIMEOS_DB_DATABASE=aimeos_tenant_data
AIMEOS_DB_USERNAME=aimeos_user
AIMEOS_DB_PASSWORD=aimeos_secure_password

# Aimeos File Storage
AIMEOS_UPLOAD_DIR=storage/app/public/aimeos
AIMEOS_MAX_UPLOAD_SIZE=10M
AIMEOS_ALLOWED_EXTENSIONS="jpg,jpeg,png,gif,svg,pdf,doc,docx"

# Aimeos Cache Configuration
AIMEOS_CACHE_DRIVER=redis
AIMEOS_CACHE_PREFIX=aimeos_
AIMEOS_CACHE_TTL=3600

# Aimeos Tenant Settings Defaults
AIMEOS_DEFAULT_CURRENCY=USD
AIMEOS_DEFAULT_LANGUAGE=en
AIMEOS_DEFAULT_COUNTRY=US
AIMEOS_DEFAULT_TIMEZONE="America/New_York"

# Aimeos Payment Integration
AIMEOS_PAYMENT_STRIPE_ENABLED=true
AIMEOS_PAYMENT_PAYPAL_ENABLED=false
AIMEOS_PAYMENT_BANK_TRANSFER_ENABLED=false

# Aimeos Shipping Configuration
AIMEOS_SHIPPING_FREE_THRESHOLD=100.00
AIMEOS_SHIPPING_STANDARD_COST=9.99
AIMEOS_SHIPPING_EXPRESS_COST=19.99
AIMEOS_SHIPPING_PROCESSING_DAYS=2

# Aimeos Email Templates
AIMEOS_EMAIL_FROM_ADDRESS="${MAIL_FROM_ADDRESS}"
AIMEOS_EMAIL_FROM_NAME="${MAIL_FROM_NAME}"
AIMEOS_EMAIL_TEMPLATE_PATH=resources/views/aimeos/emails
AIMEOS_EMAIL_LOGO_URL=""

# Aimeos Product Settings
AIMEOS_PRODUCTS_PER_PAGE=24
AIMEOS_PRODUCT_IMAGE_SIZES="150x150,300x300,600x600"
AIMEOS_ENABLE_PRODUCT_REVIEWS=true
AIMEOS_ENABLE_WISHLIST=true
AIMEOS_TRACK_INVENTORY=true
AIMEOS_LOW_STOCK_THRESHOLD=10

# Aimeos Customer Settings
AIMEOS_REQUIRE_ACCOUNT=false
AIMEOS_ENABLE_GUEST_CHECKOUT=true
AIMEOS_REQUIRE_EMAIL_VERIFICATION=true
AIMEOS_SHOW_COOKIE_CONSENT=true
AIMEOS_DATA_RETENTION_DAYS=365

# Aimeos Security
AIMEOS_ADMIN_SECRET=your_aimeos_admin_secret_key
AIMEOS_API_SECRET=your_aimeos_api_secret_key
AIMEOS_WEBHOOK_SECRET=your_aimeos_webhook_secret

# Aimeos Performance
AIMEOS_ENABLE_CACHING=true
AIMEOS_ENABLE_COMPRESSION=true
AIMEOS_ENABLE_CDN=false
AIMEOS_CDN_URL=""

# Aimeos Logging
AIMEOS_LOG_LEVEL=info
AIMEOS_LOG_CHANNEL=aimeos
AIMEOS_DEBUG_MODE=false
```

## Broadcasting and Real-time Configuration

```env
# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_smtp_username
MAIL_PASSWORD=your_smtp_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@your-domain.com"
MAIL_FROM_NAME="${APP_NAME}"

# Pusher/Broadcasting Configuration
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_app_key
PUSHER_APP_SECRET=your_pusher_app_secret
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

# Laravel Reverb (Alternative to Pusher)
REVERB_APP_ID=local
REVERB_APP_KEY=local
REVERB_APP_SECRET=local
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
```

## Security Configuration

```env
# Security Configuration
SECURE_HEADERS_FORCE_HTTPS=true
SECURE_HEADERS_HSTS_MAX_AGE=31536000
SECURE_HEADERS_CONTENT_SECURITY_POLICY="default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'"

# Iframe Security Configuration
IFRAME_ALLOWED_DOMAINS="photopea.com,*.photopea.com"
IFRAME_SECURITY_STRICT=true

# Laravel Sanctum
SANCTUM_STATEFUL_DOMAINS=your-domain.com,localhost:3000,127.0.0.1:3000

# Team and User Management
VUE_STARTER_KIT_STACK=inertia
VUE_STARTER_KIT_FEATURES=api,profile-photos
```

## Optional Services Configuration

```env
# Search Configuration
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=your_meilisearch_key

# Analytics and SEO
GOOGLE_ANALYTICS_ID=GA-XXXXXXXXX-X
GOOGLE_SEARCH_CONSOLE_VERIFICATION=your_verification_code

# Communication
SMS_DRIVER=twilio
SMS_FROM=+1234567890

# Performance Configuration
OCTANE_SERVER=swoole
OCTANE_HTTPS=true

# Monitoring and Debugging (Development only)
TELESCOPE_ENABLED=false
DEBUGBAR_ENABLED=false

# Additional API Keys
MAPS_API_KEY=your_google_maps_api_key
WEATHER_API_KEY=your_weather_api_key
```

## Vite Configuration for Frontend

```env
# Vite Asset Configuration
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

## Deployment Checklist

### Required for Basic Functionality
- [ ] `APP_KEY` - Generate with `php artisan key:generate`
- [ ] Database credentials (`DB_*`)
- [ ] Redis configuration (`REDIS_*`)
- [ ] Stripe credentials (`STRIPE_*`)
- [ ] Storage configuration (`CLOUDFLARE_R2_*`)

### Required for AI Features
- [ ] OpenAI API key (`OPENAI_API_KEY`)
- [ ] AI configuration (`AI_CHAT_*`)

### Required for Real-time Features
- [ ] Broadcasting configuration (`PUSHER_*` or `REVERB_*`)
- [ ] Mail configuration (`MAIL_*`)

### Required for Security
- [ ] `APP_DEBUG=false` in production
- [ ] Strong database passwords
- [ ] HTTPS configuration
- [ ] Proper CSP headers

## Post-Deployment Commands

After setting up environment variables, run these commands:

```bash
# Install dependencies
composer install --optimize-autoloader --no-dev
npm ci

# Build assets
npm run build

# Run migrations
php artisan migrate --force

# Publish configurations
php artisan vendor:publish --provider="Spatie\WebhookClient\WebhookClientServiceProvider"
php artisan vendor:publish --provider="Laravel\Cashier\CashierServiceProvider"
php artisan vendor:publish --provider="Rawilk\Settings\SettingsServiceProvider"

# Set up storage
php artisan storage:link

# Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Clear any existing caches
php artisan cache:clear
php artisan queue:restart
```

## Webhook Endpoints for Stripe Configuration

Configure these webhook endpoints in your Stripe dashboard:

### Platform Webhooks
- **URL**: `https://your-domain.com/webhooks/stripe/platform`
- **Events**: All Cashier and platform events

### Tenant Webhooks  
- **URL**: `https://your-domain.com/webhooks/stripe/tenant/{tenant-uuid}`
- **Events**: Tenant-specific payment events

### Cashier Webhooks
- **URL**: `https://your-domain.com/webhooks/stripe/cashier`
- **Events**: Handled automatically by Laravel Cashier 