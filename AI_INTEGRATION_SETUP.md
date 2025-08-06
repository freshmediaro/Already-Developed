# AI Integration Setup Guide

This guide walks you through setting up the AI Chat system with Prism PHP (multi-LLM support), OpenAI Laravel integration, command execution, and full desktop application interaction with comprehensive tenant isolation and complete ecosystem integration.

## Prerequisites

- Laravel 11.x with Tenancy for Laravel (stancl/tenancy) installed
- PHP 8.2+
- Node.js 18+
- OpenAI API key
- Prism PHP compatible LLM providers (OpenAI, Anthropic, Groq, etc.)
- MySQL/PostgreSQL database
- Vue.js 3 with Inertia.js
- Laravel [Vue Starter Kit](https://github.com/laravel/vue-starter-kit/) 
- Redis for caching and real-time features

## Installation Steps

### 1. Install Backend Dependencies

```bash
# Core AI dependencies
composer require prism-php/prism:^0.1
composer require openai-php/laravel:^0.8.1

# Supporting packages for enhanced functionality
composer require spatie/laravel-activitylog:^4.8
composer require spatie/laravel-permission:^6.0
composer require league/flysystem:^3.0
composer require intervention/image:^3.0

# UUID support for chat sessions
composer require ramsey/uuid:^4.7

# E-commerce and Business Packages (Aimeos Integration)
composer require aimeos/aimeos-laravel:^2024.10
composer require aimeos/ai-payments:^2024.10
composer require aimeos/ai-filesystem:^2024.10

# File Management and Storage
composer require barryvdh/laravel-elfinder:^0.5
composer require vidwanco/tenant-buckets:^1.0

# Notifications and Real-time
composer require laravel/reverb:^1.0
composer require pusher/pusher-php-server:^7.2

# Settings and Configuration
composer require rawilk/laravel-settings:^3.0

# Analytics and SEO
composer require spatie/laravel-analytics:^4.2
composer require artesaos/seotools:^1.3
composer require spatie/schema-org:^3.0

# Communication
composer require prgayman/laravel-sms:^2.0
composer require worksome/verify-by-phone:^2.0

# Widgets and UI
composer require arrilot/laravel-widgets:^3.13

# Wallet and Payments
composer require bavix/laravel-wallet:^11.0

# Translations
composer require mcamara/laravel-localization:^2.0
composer require mohmmedashraf/laravel-translations:^1.0

# Form Builder
composer require vitormicillo/laravel-formbuilder:^1.20

# Module Management
composer require nwidart/laravel-modules:^11.0

# Search
composer require laravel/scout:^10.0
composer require meilisearch/meilisearch-php:^1.0

# Page Builder / Sitebuilder
composer require msa/laravel-grapes
```

### 2. Install Frontend Dependencies

```bash
# Speech recognition and AI chat enhancements
npm install @types/webkitSpeechRecognition
npm install marked highlight.js
npm install @heroicons/vue
npm install @fortawesome/vue-fontawesome

# Real-time communication
npm install laravel-echo pusher-js
npm install @laravel/reverb

# UI and Widgets
npm install gridstack
npm install swiper

# Command palette and search
npm install cmdk

# Form builder and page builder
npm install grapesjs
npm install @grapesjs/preset-webpage

# Charts and analytics
npm install chart.js vue-chartjs

# File management
npm install filepond
npm install filepond-plugin-image-preview
```

### 3. Publish Configuration Files

```bash
# Publish OpenAI configuration
php artisan vendor:publish --provider="OpenAI\Laravel\ServiceProvider"

# Publish Prism configuration (if available)
php artisan vendor:publish --tag=prism-config

# Publish permissions configuration
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Publish Aimeos configuration
php artisan vendor:publish --provider="Aimeos\Shop\ShopServiceProvider"

# Publish ElFinder configuration
php artisan vendor:publish --provider="Barryvdh\Elfinder\ElfinderServiceProvider"

# Publish settings configuration
php artisan vendor:publish --provider="Rawilk\Settings\SettingsServiceProvider"

# Publish activity log configuration
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider"

# Publish widgets configuration
php artisan vendor:publish --provider="Arrilot\Widgets\ServiceProvider"

# Publish reverb configuration
php artisan vendor:publish --provider="Laravel\Reverb\ReverbServiceProvider"

# Publish Laravel Grapes configuration
php artisan vendor:publish --provider="MSA\LaravelGrapes\LaravelGrapesServiceProvider" --tag="*"
```

### 4. Environment Configuration

Add these variables to your `.env` file:

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

# Aimeos E-commerce Integration
AIMEOS_ADMIN_JQU=true
AIMEOS_ADMIN_GRAPHQL=true
AIMEOS_ADMIN_JSONAPI=true

# Real-time and Broadcasting
BROADCAST_DRIVER=reverb
REVERB_APP_ID=local
REVERB_APP_KEY=local
REVERB_APP_SECRET=local
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

# File Management
ELFINDER_DRIVER=LocalFileSystem
ELFINDER_TENANT_AWARE=true

# Search and Analytics
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=

# SMS Configuration
SMS_DEFAULT_DRIVER=aws
AWS_SNS_ACCESS_KEY_ID=your-key
AWS_SNS_SECRET_ACCESS_KEY=your-secret
AWS_SNS_REGION=us-east-1

# Payment Gateways
STRIPE_KEY=pk_test_your-stripe-key

# Laravel Grapes (Sitebuilder)
LARAVEL_GRAPES_BUILDER_PREFIX=sitebuilder
LARAVEL_GRAPES_MIDDLEWARE=auth:tenant
LARAVEL_GRAPES_FRONTEND_PREFIX=
STRIPE_SECRET=sk_test_your-stripe-secret
PAYPAL_CLIENT_ID=your-paypal-client-id
PAYPAL_CLIENT_SECRET=your-paypal-secret

# Analytics
GOOGLE_ANALYTICS_TRACKING_ID=UA-your-tracking-id
GOOGLE_ANALYTICS_VIEW_ID=your-view-id
```

### 5. Database Migrations

The AI chat system requires four migration files that are already created with proper tenant isolation:

```bash
# Run the AI chat migrations (these are already created)
php artisan migrate

# For tenant databases specifically:
php artisan tenants:migrate --path=database/migrations/2024_01_06_000000_create_ai_chat_sessions_table.php
php artisan tenants:migrate --path=database/migrations/2024_01_07_000000_create_ai_chat_messages_table.php
php artisan tenants:migrate --path=database/migrations/2024_01_08_000000_create_ai_chat_feedback_table.php
php artisan tenants:migrate --path=database/migrations/2024_01_09_000000_create_ai_chat_usage_table.php

# Run Aimeos migrations
php artisan aimeos:setup

# Run other package migrations
php artisan scout:install
php artisan queue:table
php artisan notifications:table

# Run Laravel Grapes migrations for each tenant
php artisan tenants:migrate --option=laravel_grapes
```

**Migration Files Overview:**
- `create_ai_chat_sessions_table.php` - Chat sessions with user/team isolation
- `create_ai_chat_messages_table.php` - Individual messages linked to sessions
- `create_ai_chat_feedback_table.php` - User feedback on AI responses
- `create_ai_chat_usage_table.php` - Token usage and cost tracking

### 6. Configuration Files

Create `config/prism.php`:

```php
<?php

return [
    'default_provider' => env('PRISM_DEFAULT_PROVIDER', 'openai'),
    
    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'organization' => env('OPENAI_ORGANIZATION'),
            'request_timeout' => env('OPENAI_REQUEST_TIMEOUT', 30),
            'models' => [
                'chat' => 'gpt-4-turbo-preview',
                'vision' => 'gpt-4-vision-preview',
                'embedding' => 'text-embedding-3-small',
            ],
        ],
        
        'anthropic' => [
            'api_key' => env('PRISM_ANTHROPIC_API_KEY'),
            'models' => [
                'chat' => 'claude-3-sonnet-20240229',
            ],
        ],
        
        'groq' => [
            'api_key' => env('PRISM_GROQ_API_KEY'),
            'models' => [
                'chat' => 'llama3-70b-8192',
            ],
        ],
    ],
    
    'fallback_provider' => 'openai',
    'enable_logging' => env('APP_DEBUG', false),
];
```

Create `config/ai-chat.php`:

```php
<?php

return [
    'max_tokens' => env('AI_CHAT_MAX_TOKENS', 4000),
    'temperature' => env('AI_CHAT_TEMPERATURE', 0.7),
    'max_history' => env('AI_CHAT_MAX_HISTORY', 10),
    'session_timeout' => env('AI_CHAT_SESSION_TIMEOUT', 3600),
    
    'features' => [
        'tools' => env('AI_CHAT_ENABLE_TOOLS', true),
        'image_analysis' => env('AI_CHAT_ENABLE_IMAGE_ANALYSIS', true),
        'document_analysis' => env('AI_CHAT_ENABLE_DOCUMENT_ANALYSIS', true),
        'tenant_isolation' => env('AI_CHAT_TENANT_ISOLATION', true),
        'usage_tracking' => env('AI_CHAT_USAGE_TRACKING', true),
        'cost_tracking' => env('AI_CHAT_COST_TRACKING', true),
    ],
    
    'uploads' => [
        'max_file_size' => env('AI_CHAT_MAX_FILE_SIZE', '10M'),
        'allowed_image_types' => explode(',', env('AI_CHAT_ALLOWED_IMAGE_TYPES', 'jpg,jpeg,png,gif,webp')),
        'allowed_document_types' => explode(',', env('AI_CHAT_ALLOWED_DOCUMENT_TYPES', 'pdf,doc,docx,txt,csv,json')),
        'storage_path' => 'ai-chat/uploads',
    ],
    
    'commands' => [
        'enabled' => env('AI_COMMAND_EXECUTION_ENABLED', true),
        'audit_log' => env('AI_COMMAND_AUDIT_LOG', true),
        'rate_limit' => env('AI_COMMAND_RATE_LIMIT', 100),
    ],
    
    'tenant' => [
        'isolation_enabled' => env('AI_CHAT_TENANT_ISOLATION', true),
        'usage_limits' => [
            'monthly_tokens' => 100000,
            'daily_requests' => 1000,
            'max_file_size' => '10M',
        ],
        'storage_hierarchy' => 'tenant-{tenant_id}/team-{team_id}/user-{user_id}',
    ],
    
    // Ecosystem Integrations
    'integrations' => [
        'aimeos' => [
            'enabled' => true,
            'api_endpoint' => '/jsonapi',
            'graphql_endpoint' => '/admin/default/graphql',
        ],
        'elfinder' => [
            'enabled' => true,
            'root_path' => 'tenant-files',
        ],
        'analytics' => [
            'enabled' => true,
            'providers' => ['google', 'plausible'],
        ],
        'notifications' => [
            'enabled' => true,
            'channels' => ['broadcast', 'sms', 'email'],
        ],
        'search' => [
            'enabled' => true,
            'driver' => 'meilisearch',
        ],
        'payments' => [
            'enabled' => true,
            'gateways' => ['stripe', 'paypal'],
        ],
        'translations' => [
            'enabled' => true,
            'auto_translate' => false,
        ],
        'seo' => [
            'enabled' => true,
            'auto_optimize' => true,
        ],
        'sitebuilder' => [
            'enabled' => true,
            'builder_prefix' => env('LARAVEL_GRAPES_BUILDER_PREFIX', 'sitebuilder'),
            'middleware' => env('LARAVEL_GRAPES_MIDDLEWARE', 'auth:tenant'),
            'frontend_prefix' => env('LARAVEL_GRAPES_FRONTEND_PREFIX', ''),
            'languages' => ['en', 'ar', 'es', 'fr', 'de'],
            'grapesjs' => [
                'canvas_styles' => [
                    'height' => '100vh',
                    'background' => '#ffffff',
                ],
                'block_manager' => [
                    'appendTo' => '.blocks-container',
                ],
                'style_manager' => [
                    'appendTo' => '.styles-container',
                ],
                'layer_manager' => [
                    'appendTo' => '.layers-container',
                ],
                'trait_manager' => [
                    'appendTo' => '.traits-container',
                ],
                'panels' => [
                    'devices-c' => true,
                    'sw-visibility' => true,
                ],
            ],
        ],
    ],
];
```

### 7. Service Provider Registration

Add to `config/app.php`:

```php
'providers' => [
    // ... existing providers
    App\Providers\AiChatServiceProvider::class,
    
    // Aimeos
    Aimeos\Shop\ShopServiceProvider::class,
    
    // File management
    Barryvdh\Elfinder\ElfinderServiceProvider::class,
    
    // Settings
    Rawilk\Settings\SettingsServiceProvider::class,
    
    // Widgets
    Arrilot\Widgets\ServiceProvider::class,
    
    // Reverb
    Laravel\Reverb\ReverbServiceProvider::class,
    
    // Laravel Grapes
    MSA\LaravelGrapes\LaravelGrapesServiceProvider::class,
],
```

### 8. Middleware Registration

Add to `bootstrap/app.php`:

```php
// In the middleware alias section
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        // ... existing middleware
        'ai-chat-tenant' => \App\Http\Middleware\AiChatTenantMiddleware::class,
        'localize' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class,
        'localization-redirect' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
        'localized-routes' => \Mcamara\LaravelLocalization\Middleware\LocalizedRoutesFilter::class,
    ]);
})
```

### 9. Create Required Directories

```bash
mkdir -p storage/app/ai-chat
mkdir -p storage/logs/ai-chat
mkdir -p storage/app/ai-chat/tenant-uploads
mkdir -p storage/app/elfinder
mkdir -p storage/app/aimeos
mkdir -p storage/app/translations
mkdir -p storage/app/analytics
mkdir -p storage/app/seo-cache
mkdir -p storage/app/sitebuilder
mkdir -p storage/app/sitebuilder/templates
```

### 10. Seed Default Data

```bash
php artisan make:seeder AiChatSystemPromptsSeeder
php artisan db:seed --class=AiChatSystemPromptsSeeder

# Aimeos setup
php artisan aimeos:setup --option=setup/default/demo:1

# Create default permissions
php artisan permission:create-role admin
php artisan permission:create-permission "manage-ai-chat"
```

## Complete Ecosystem Integration

### 1. Aimeos E-commerce Integration

The AI can now interact with your complete e-commerce system:

```php
// In CommandExecutionService.php - E-commerce Commands
public function executeEcommerceCommand(string $command, array $parameters, User $user, int $teamId): array
{
    switch ($command) {
        case 'get_product_stats':
            return $this->getProductStatistics($parameters, $teamId);
        
        case 'create_product':
            return $this->createAimeosProduct($parameters, $user, $teamId);
        
        case 'update_inventory':
            return $this->updateProductInventory($parameters, $teamId);
        
        case 'get_order_summary':
            return $this->getOrderSummary($parameters, $teamId);
        
        case 'process_refund':
            return $this->processRefund($parameters, $user, $teamId);
        
        case 'get_sales_analytics':
            return $this->getSalesAnalytics($parameters, $teamId);
        
        case 'manage_customer':
            return $this->manageCustomer($parameters, $user, $teamId);
        
        case 'update_pricing':
            return $this->updateProductPricing($parameters, $teamId);
        
        case 'manage_discounts':
            return $this->manageDiscounts($parameters, $teamId);
        
        case 'get_inventory_alerts':
            return $this->getInventoryAlerts($teamId);
    }
}

protected function createAimeosProduct(array $data, User $user, int $teamId): array
{
    try {
        $context = \Aimeos\Shop\Facades\Shop::context();
        $manager = \Aimeos\MShop::create($context, 'product');
        
        $item = $manager->create();
        $item->setLabel($data['name']);
        $item->setCode($data['sku'] ?? strtolower(str_replace(' ', '-', $data['name'])));
        $item->setStatus(1);
        
        // Set pricing
        if (isset($data['price'])) {
            $priceManager = \Aimeos\MShop::create($context, 'price');
            $priceItem = $priceManager->create();
            $priceItem->setValue($data['price']);
            $priceItem->setCurrencyId($data['currency'] ?? 'USD');
            $item->getRefItems('price')->push($priceItem);
        }
        
        // Set categories
        if (isset($data['category'])) {
            $catalogManager = \Aimeos\MShop::create($context, 'catalog');
            $search = $catalogManager->filter();
            $search->setConditions($search->compare('==', 'catalog.label', $data['category']));
            $categories = $catalogManager->search($search);
            
            foreach ($categories as $category) {
                $item->getRefItems('catalog')->push($category);
            }
        }
        
        $savedItem = $manager->save($item);
        
        // Log activity
        activity()
            ->causedBy($user)
            ->performedOn($savedItem)
            ->withProperties(['team_id' => $teamId, 'ai_generated' => true])
            ->log('AI created product: ' . $data['name']);
        
        return [
            'success' => true,
            'data' => [
                'product_id' => $savedItem->getId(),
                'name' => $savedItem->getLabel(),
                'sku' => $savedItem->getCode(),
                'message' => 'Product created successfully via AI'
            ]
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to create product: ' . $e->getMessage()
        ];
    }
}

protected function getProductStatistics(array $parameters, int $teamId): array
{
    $context = \Aimeos\Shop\Facades\Shop::context();
    $manager = \Aimeos\MShop::create($context, 'product');
    
    $search = $manager->filter();
    $total = $manager->aggregate($search, 'product.id')->count();
    
    // Get products by status
    $search->setConditions($search->compare('==', 'product.status', 1));
    $active = $manager->aggregate($search, 'product.id')->count();
    
    // Get low stock items
    $stockManager = \Aimeos\MShop::create($context, 'stock');
    $stockSearch = $stockManager->filter();
    $stockSearch->setConditions($stockSearch->compare('<', 'stock.stocklevel', 10));
    $lowStock = $stockManager->search($stockSearch)->count();
    
    return [
        'success' => true,
        'data' => [
            'total_products' => $total,
            'active_products' => $active,
            'inactive_products' => $total - $active,
            'low_stock_items' => $lowStock,
            'team_id' => $teamId
        ]
    ];
}
```

### 2. File Management Integration

```php
// File management commands through ElFinder
public function executeFileCommand(string $command, array $parameters, User $user, int $teamId): array
{
    switch ($command) {
        case 'list_files':
            return $this->listTenantFiles($parameters, $teamId);
        
        case 'upload_file':
            return $this->uploadFile($parameters, $user, $teamId);
        
        case 'delete_file':
            return $this->deleteFile($parameters, $user, $teamId);
        
        case 'share_file':
            return $this->shareFile($parameters, $user, $teamId);
        
        case 'get_storage_stats':
            return $this->getStorageStatistics($teamId);
        
        case 'organize_files':
            return $this->organizeFiles($parameters, $teamId);
    }
}

protected function listTenantFiles(array $parameters, int $teamId): array
{
    $path = $parameters['path'] ?? '';
    $tenantPath = "tenant-{$teamId}/{$path}";
    
    $disk = Storage::disk('tenant');
    $files = $disk->allFiles($tenantPath);
    $directories = $disk->allDirectories($tenantPath);
    
    return [
        'success' => true,
        'data' => [
            'files' => array_map(function ($file) use ($disk) {
                return [
                    'name' => basename($file),
                    'size' => $disk->size($file),
                    'modified' => $disk->lastModified($file),
                    'url' => $disk->url($file)
                ];
            }, $files),
            'directories' => array_map('basename', $directories),
            'total_files' => count($files),
            'total_size' => array_sum(array_map(fn($f) => $disk->size($f), $files))
        ]
    ];
}
```

### 3. Analytics and SEO Integration

```php
// Analytics and SEO commands
public function executeAnalyticsCommand(string $command, array $parameters, User $user, int $teamId): array
{
    switch ($command) {
        case 'get_website_stats':
            return $this->getWebsiteAnalytics($parameters, $teamId);
        
        case 'seo_analysis':
            return $this->performSeoAnalysis($parameters, $teamId);
        
        case 'generate_sitemap':
            return $this->generateSitemap($teamId);
        
        case 'optimize_images':
            return $this->optimizeImages($parameters, $teamId);
        
        case 'get_keywords':
            return $this->getKeywordAnalysis($parameters, $teamId);
    }
}

protected function getWebsiteAnalytics(array $parameters, int $teamId): array
{
    $period = $parameters['period'] ?? '30daysAgo';
    
    try {
        $analytics = app(\Spatie\Analytics\Analytics::class);
        $data = $analytics->fetchMostVisitedPages($period);
        
        return [
            'success' => true,
            'data' => [
                'most_visited_pages' => $data->take(10),
                'period' => $period,
                'team_id' => $teamId,
                'generated_at' => now()->toISOString()
            ]
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to fetch analytics: ' . $e->getMessage()
        ];
    }
}
```

### 4. Notification System Integration

```php
// Notification commands
public function executeNotificationCommand(string $command, array $parameters, User $user, int $teamId): array
{
    switch ($command) {
        case 'send_notification':
            return $this->sendNotification($parameters, $user, $teamId);
        
        case 'send_sms':
            return $this->sendSMS($parameters, $user, $teamId);
        
        case 'send_email_campaign':
            return $this->sendEmailCampaign($parameters, $user, $teamId);
        
        case 'get_notification_stats':
            return $this->getNotificationStatistics($teamId);
    }
}

protected function sendNotification(array $parameters, User $user, int $teamId): array
{
    try {
        $notification = [
            'title' => $parameters['title'],
            'message' => $parameters['message'],
            'type' => $parameters['type'] ?? 'info',
            'team_id' => $teamId,
            'sent_by' => $user->id,
            'ai_generated' => true
        ];
        
        // Broadcast to team members
        broadcast(new \App\Events\TeamNotification($notification, $teamId));
        
        // Store in database
        \App\Models\Notification::create($notification);
        
        return [
            'success' => true,
            'data' => [
                'message' => 'Notification sent successfully',
                'recipients' => $this->getTeamMemberCount($teamId)
            ]
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to send notification: ' . $e->getMessage()
        ];
    }
}
```

### 5. Translation and Localization Commands

```php
// Translation commands
public function executeTranslationCommand(string $command, array $parameters, User $user, int $teamId): array
{
    switch ($command) {
        case 'translate_content':
            return $this->translateContent($parameters, $teamId);
        
        case 'get_supported_languages':
            return $this->getSupportedLanguages();
        
        case 'add_translation':
            return $this->addTranslation($parameters, $user, $teamId);
        
        case 'export_translations':
            return $this->exportTranslations($parameters, $teamId);
    }
}

protected function translateContent(array $parameters, int $teamId): array
{
    $content = $parameters['content'];
    $targetLanguage = $parameters['target_language'];
    $sourceLanguage = $parameters['source_language'] ?? 'en';
    
    // Use AI to translate content
    $prompt = "Translate the following content from {$sourceLanguage} to {$targetLanguage}. Maintain the tone and context:\n\n{$content}";
    
    $aiResponse = $this->callAiForTranslation($prompt);
    
    return [
        'success' => true,
        'data' => [
            'original_content' => $content,
            'translated_content' => $aiResponse,
            'source_language' => $sourceLanguage,
            'target_language' => $targetLanguage,
            'team_id' => $teamId
        ]
    ];
}
```

### 6. Widget and Dashboard Commands

```php
// Widget management commands
public function executeWidgetCommand(string $command, array $parameters, User $user, int $teamId): array
{
    switch ($command) {
        case 'create_widget':
            return $this->createWidget($parameters, $user, $teamId);
        
        case 'update_dashboard':
            return $this->updateDashboard($parameters, $user, $teamId);
        
        case 'get_widget_data':
            return $this->getWidgetData($parameters, $teamId);
        
        case 'arrange_widgets':
            return $this->arrangeWidgets($parameters, $user, $teamId);
    }
}

protected function createWidget(array $parameters, User $user, int $teamId): array
{
    $widgetData = [
        'name' => $parameters['name'],
        'type' => $parameters['type'],
        'config' => $parameters['config'] ?? [],
        'user_id' => $user->id,
        'team_id' => $teamId,
        'position' => $parameters['position'] ?? ['x' => 0, 'y' => 0, 'w' => 4, 'h' => 3]
    ];
    
    $widget = \App\Models\Widget::create($widgetData);
    
    return [
        'success' => true,
        'data' => [
            'widget_id' => $widget->id,
            'message' => 'Widget created successfully',
            'widget' => $widget
        ]
    ];
}
```

### 7. Search and Content Commands

```php
// Search and content management
public function executeSearchCommand(string $command, array $parameters, User $user, int $teamId): array
{
    switch ($command) {
        case 'search_content':
            return $this->searchContent($parameters, $teamId);
        
        case 'index_content':
            return $this->indexContent($parameters, $teamId);
        
        case 'get_search_stats':
            return $this->getSearchStatistics($teamId);
    }
}

protected function searchContent(array $parameters, int $teamId): array
{
    $query = $parameters['query'];
    $filters = $parameters['filters'] ?? [];
    
    // Search across multiple models
    $results = [
        'products' => \App\Models\Product::search($query)->where('team_id', $teamId)->get(),
        'orders' => \App\Models\Order::search($query)->where('team_id', $teamId)->get(),
        'customers' => \App\Models\Customer::search($query)->where('team_id', $teamId)->get(),
        'content' => \App\Models\Content::search($query)->where('team_id', $teamId)->get(),
    ];
    
    return [
        'success' => true,
        'data' => [
            'query' => $query,
            'results' => $results,
            'total_results' => collect($results)->sum(fn($r) => $r->count()),
            'team_id' => $teamId
        ]
    ];
}
```

## Enhanced AI Context Preparation

```php
// Enhanced context preparation including all ecosystem data
protected function prepareAiContext(User $user, ?int $teamId): array
{
    $context = [
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        ],
        'team' => $teamId ? [
            'id' => $teamId,
            'name' => Team::find($teamId)?->name,
            'settings' => $this->getTeamSettings($teamId),
        ] : null,
        'tenant' => [
            'id' => tenant('id'),
            'domain' => tenant('domain'),
        ],
        'ecosystem' => [
            'ecommerce' => $this->getEcommerceContext($teamId),
            'analytics' => $this->getAnalyticsContext($teamId),
            'files' => $this->getFileSystemContext($teamId),
            'notifications' => $this->getNotificationContext($teamId),
            'translations' => $this->getTranslationContext($teamId),
            'seo' => $this->getSeoContext($teamId),
            'widgets' => $this->getWidgetContext($user->id, $teamId),
        ],
        'installedApps' => App::where('installed', true)
            ->where(function ($query) use ($teamId) {
                $query->whereNull('team_id')
                      ->orWhere('team_id', $teamId);
            })
            ->pluck('app_id')
            ->toArray(),
        'preferences' => $user->preferences ?? [],
        'timestamp' => now()->toISOString(),
    ];
    
    return $context;
}

protected function getEcommerceContext(int $teamId): array
{
    $context = \Aimeos\Shop\Facades\Shop::context();
    $productManager = \Aimeos\MShop::create($context, 'product');
    $orderManager = \Aimeos\MShop::create($context, 'order');
    
    return [
        'total_products' => $productManager->aggregate($productManager->filter(), 'product.id')->count(),
        'total_orders' => $orderManager->aggregate($orderManager->filter(), 'order.id')->count(),
        'currencies' => ['USD', 'EUR', 'GBP'], // From config
        'payment_methods' => ['stripe', 'paypal'], // From config
        'shipping_methods' => $this->getShippingMethods(),
    ];
}

protected function getAnalyticsContext(int $teamId): array
{
    return [
        'enabled' => config('ai-chat.integrations.analytics.enabled'),
        'providers' => config('ai-chat.integrations.analytics.providers'),
        'last_updated' => $this->getLastAnalyticsUpdate($teamId),
    ];
}

protected function getFileSystemContext(int $teamId): array
{
    $disk = Storage::disk('tenant');
    $tenantPath = "tenant-{$teamId}";
    
    return [
        'total_files' => count($disk->allFiles($tenantPath)),
        'total_size' => $this->calculateDirectorySize($disk, $tenantPath),
        'file_types' => $this->getFileTypeDistribution($disk, $tenantPath),
    ];
}
```

## Available AI Commands by Category

### E-commerce Commands (Aimeos Integration)
- `get_product_stats` - Product statistics and inventory
- `create_product` - Create new products
- `update_inventory` - Manage stock levels
- `get_order_summary` - Order analytics
- `process_refund` - Handle refunds
- `get_sales_analytics` - Sales performance data
- `manage_customer` - Customer management
- `update_pricing` - Price management
- `manage_discounts` - Discount and coupon management
- `get_inventory_alerts` - Low stock alerts

### File Management Commands
- `list_files` - Browse tenant files
- `upload_file` - Upload and organize files
- `delete_file` - File deletion with permissions
- `share_file` - File sharing within team
- `get_storage_stats` - Storage usage analytics
- `organize_files` - Auto-organize files by type/date

### Analytics & SEO Commands
- `get_website_stats` - Website performance
- `seo_analysis` - SEO audit and recommendations
- `generate_sitemap` - XML sitemap generation
- `optimize_images` - Image optimization
- `get_keywords` - Keyword analysis

### Communication Commands
- `send_notification` - Real-time notifications
- `send_sms` - SMS marketing campaigns
- `send_email_campaign` - Email marketing
- `get_notification_stats` - Communication analytics

### Translation Commands
- `translate_content` - AI-powered translation
- `get_supported_languages` - Available languages
- `add_translation` - Manual translation management
- `export_translations` - Translation export

### Widget & Dashboard Commands
- `create_widget` - Custom widget creation
- `update_dashboard` - Dashboard management
- `get_widget_data` - Widget data refresh
- `arrange_widgets` - Auto-arrange dashboard

### Search Commands
- `search_content` - Global content search
- `index_content` - Search index management
- `get_search_stats` - Search analytics

### System Commands
- `launch_app` - Application launcher
- `show_notification` - System notifications
- `update_user_preferences` - User settings
- `generate_report` - Custom reporting
- `backup_data` - Data backup management
- `system_health` - System monitoring

### Sitebuilder Commands (Laravel Grapes Integration)
- `createPage` - Create new HTML page with GrapesJS
- `updatePageContent` - Modify page content, styles, and layout
- `changePageColors` - Update color scheme and styling
- `updatePageTexts` - Modify text content across the page
- `createPageTemplate` - Create reusable page templates
- `clonePage` - Duplicate existing pages
- `optimizePageSeo` - Enhance page SEO elements
- `addPageComponent` - Add HTML components to page
- `removePageComponent` - Remove components from page
- `updatePageSettings` - Modify page settings and meta data
- `exportPageHtml` - Export page as HTML file
- `importPageTemplate` - Import external page templates
- `previewPage` - Generate page preview
- `publishPage` - Publish page to live site
- `updatePageStyles` - Modify CSS styles and themes
- `addPageBlock` - Add predefined blocks and sections

## Tenant Isolation Features

### 1. Database Isolation

All AI chat data is strictly isolated per tenant and team with the following structure:

#### AI Chat Sessions (`ai_chat_sessions`)
- **Primary Key**: UUID for security
- **Isolation Fields**: `user_id`, `team_id`
- **Indexes**: Optimized for tenant-specific queries
- **Foreign Keys**: Cascade deletion with users/teams

#### AI Chat Messages (`ai_chat_messages`)
- **Linked to**: Sessions, users, teams
- **Content**: Long text support for large AI responses
- **Metadata**: JSON field for additional context
- **Type**: Distinguishes user vs AI messages

#### AI Chat Feedback (`ai_chat_feedback`)
- **Types**: like, dislike, report
- **Comments**: Optional detailed feedback
- **Unique Constraint**: One feedback per user per message per type

#### AI Chat Usage (`ai_chat_usage`)
- **Tracking**: Tokens, costs, operation types
- **Providers**: Multi-LLM usage tracking
- **Billing**: Cost calculation per tenant/team

### 2. Middleware Protection (`AiChatTenantMiddleware`)

The AI chat middleware provides comprehensive protection:

```php
class AiChatTenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Authentication check
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // 2. Team access validation
        $teamId = $this->getTeamIdFromRequest($request);
        if ($teamId && !$this->validateTeamAccess($user, $teamId)) {
            return response()->json(['error' => 'Access denied'], 403);
        }
        
        // 3. Usage limits enforcement
        if (!$this->checkUsageLimits($user, $teamId)) {
            return response()->json(['error' => 'Usage limit exceeded'], 429);
        }
        
        // 4. Context injection
        $request->merge([
            'validated_team_id' => $teamId,
            'ai_context' => [
                'user_id' => $user->id,
                'team_id' => $teamId,
                'tenant_id' => tenant('id'),
                'permissions' => $this->getUserAiPermissions($user, $teamId)
            ]
        ]);
        
        return $next($request);
    }
}
```

**Features:**
- **Team Access Validation**: Ensures users can only access their team's AI resources
- **Usage Limits**: Enforces per-tenant/team token quotas and rate limiting
- **Permission Checking**: Role-based access control for AI features
- **Context Injection**: Adds validated tenant context to all requests

### 3. Tenant-Aware File Storage

AI file uploads are organized in a strict hierarchy:

```
storage/app/
├── ai-chat/
│   ├── tenant-{tenant_id}/
│   │   ├── team-{team_id}/
│   │   │   ├── user-{user_id}/
│   │   │   │   ├── images/
│   │   │   │   ├── documents/
│   │   │   │   └── uploads/
│   │   │   └── shared/
│   │   └── user-{other_user_id}/
│   └── tenant-{other_tenant_id}/
```

**Security Benefits:**
- Physical file isolation per tenant
- Team-level shared directories
- User-specific private storage
- Automatic cleanup on tenant deletion

### 4. Model Scopes for Data Isolation

All AI chat models include tenant-aware scopes:

```php
// Example: AiChatSession model
public function scopeTenantIsolated($query, int $userId, ?int $teamId = null)
{
    $query->where('user_id', $userId);
    if ($teamId) {
        $query->where('team_id', $teamId);
    }
    return $query;
}

public function scopeForTeam($query, int $teamId)
{
    return $query->where('team_id', $teamId);
}

public function scopeForUser($query, int $userId)
{
    return $query->where('user_id', $userId);
}
```

**Usage in Controllers:**
```php
// Get sessions for current user and team
$sessions = AiChatSession::tenantIsolated($user->id, $teamId)->get();

// Get messages for a specific session (with tenant validation)
$messages = AiChatMessage::tenantIsolated($user->id, $teamId)
    ->where('session_id', $sessionId)
    ->orderBy('created_at')
    ->get();
```

## Configuration Details

### 1. Prism PHP Multi-LLM Setup

Configure multiple LLM providers for failover and cost optimization:

```php
// In your AiChatService
use Prism\Prism;

$prism = new Prism([
    'provider' => config('prism.default_provider'),
    'anthropic' => [
        'api_key' => config('prism.providers.anthropic.api_key'),
    ],
    'openai' => [
        'api_key' => config('prism.providers.openai.api_key'),
    ],
    'groq' => [
        'api_key' => config('prism.providers.groq.api_key'),
    ],
]);
```

### 2. Command Execution System

The AI can execute commands across various desktop applications with tenant isolation:

#### Blog Management (Tenant-Aware)
```php
// All commands validate team membership and app access
public function createBlogPost(array $data, User $user, int $teamId): array
{
    // Validate team membership
    if (!$this->validateTeamMembership($user, $teamId)) {
        return ['success' => false, 'message' => 'Access denied'];
    }
    
    // Validate app access
    if (!$this->validateAppAccess('blog', $teamId)) {
        return ['success' => false, 'message' => 'Blog app not available'];
    }
    
    // Create with tenant isolation
    $blogPost = BlogPost::create(array_merge($data, [
        'user_id' => $user->id,
        'team_id' => $teamId,
    ]));
    
    return ['success' => true, 'data' => $blogPost];
}
```

#### Available Commands:
- **Blog**: `create_blog_post`, `update_blog_post`, `publish_blog_post`, `delete_blog_post`
- **Products**: `create_product`, `update_product`, `delete_product`, `update_inventory`
- **Orders**: `view_orders`, `update_order_status`, `create_invoice`, `process_refund`
- **Email**: `send_email`, `create_email_template`, `schedule_email`
- **Contacts**: `create_contact`, `update_contact`, `search_contacts`, `export_contacts`
- **System**: `launch_app`, `show_notification`, `update_user_preferences`, `generate_report`

### 3. Context Awareness with Tenant Data

The AI system maintains tenant-specific context:

```php
protected function prepareAiContext(User $user, ?int $teamId): array
{
    return [
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ],
        'team' => $teamId ? [
            'id' => $teamId,
            'name' => Team::find($teamId)?->name,
        ] : null,
        'tenant' => [
            'id' => tenant('id'),
            'domain' => tenant('domain'),
        ],
        'installedApps' => App::where('installed', true)
            ->where(function ($query) use ($teamId) {
                $query->whereNull('team_id')
                      ->orWhere('team_id', $teamId);
            })
            ->pluck('app_id')
            ->toArray(),
        'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        'preferences' => $user->preferences ?? [],
        'timestamp' => now()->toISOString(),
    ];
}
```

## Frontend Integration

### 1. AI Chat Window Component

The `AiChatWindow.vue` component provides:
- **Real-time chat interface** with tenant-aware messaging
- **Voice input** via Web Speech API
- **File upload** for image/document analysis (tenant-specific storage)
- **Action buttons** for quick commands
- **Message history** and session management (tenant-isolated)
- **Feedback system** (like/dislike/report with tenant context)

### 2. Integration with Desktop System

```typescript
// Opening AI chat from taskbar
const showAIChat = ref(false);
const currentTeam = computed(() => usePage().props.auth.user.current_team_id);

function handleAIChatClick() {
    showAIChat.value = !showAIChat.value;
}

// AI executing commands with tenant context
function executeCommand(command: any) {
    const teamId = currentTeam.value;
    
    switch (command.action) {
        case 'launch_app':
            if (validateAppAccess(command.data.app_id, teamId)) {
                window.vueDesktop.launchApp(command.data.app_id);
            }
            break;
        case 'show_notification':
            window.vueDesktop.showNotification({
                ...command.data,
                tenant_id: window.tenant.id,
                team_id: teamId,
            });
            break;
        // ... other tenant-aware commands
    }
}
```

### 3. Voice Input with Tenant Context

```typescript
// Web Speech API integration with tenant-specific settings
function startVoiceInput() {
    if ('webkitSpeechRecognition' in window) {
        const recognition = new webkitSpeechRecognition();
        recognition.lang = user.value.locale || 'en-US';
        
        recognition.onresult = (event) => {
            currentMessage.value = event.results[0][0].transcript;
            sendMessage({
                message: currentMessage.value,
                team_id: currentTeam.value,
                context: getTenantContext(),
            });
        };
        
        recognition.start();
    }
}

function getTenantContext() {
    return {
        tenant_id: window.tenant.id,
        team_id: currentTeam.value,
        user_id: user.value.id,
        timestamp: new Date().toISOString(),
    };
}
```

## API Endpoints with Tenant Protection

All AI chat endpoints are protected by the `ai-chat-tenant` middleware:

### Core AI Chat (Tenant-Protected)

```php
// In routes/tenant-desktop.php
Route::prefix('ai-chat')->middleware(['ai-chat-tenant'])->group(function () {
    Route::post('/message', [AiChatController::class, 'sendMessage'])->name('ai-chat.message');
    Route::post('/execute', [AiChatController::class, 'executeCommand'])->name('ai-chat.execute');
    Route::post('/upload', [AiChatController::class, 'uploadFile'])->name('ai-chat.upload');
    Route::post('/feedback', [AiChatController::class, 'sendFeedback'])->name('ai-chat.feedback');
    Route::get('/history', [AiChatController::class, 'getHistory'])->name('ai-chat.history');
    Route::get('/commands', [AiChatController::class, 'getAvailableCommands'])->name('ai-chat.commands');
});
```

### Admin Endpoints (Tenant-Scoped)

```php
Route::prefix('ai-chat/admin')->middleware(['ai-chat-tenant', 'can:manage-ai-chat'])->group(function () {
    Route::get('/usage', [AiChatController::class, 'getUsageStats'])->name('ai-chat.admin.usage');
    Route::post('/reset-session', [AiChatController::class, 'resetUserSession'])->name('ai-chat.admin.reset');
    Route::get('/feedback', [AiChatController::class, 'getFeedbackData'])->name('ai-chat.admin.feedback');
    Route::post('/update-prompts', [AiChatController::class, 'updateSystemPrompts'])->name('ai-chat.admin.prompts');
});
```

## Security Features

### 1. Tenant-Aware Command Validation

```php
public function validateCommand(string $command, User $user, ?int $teamId): bool
{
    // Check team membership
    if ($teamId && !$user->teams()->where('teams.id', $teamId)->exists()) {
        return false;
    }
    
    // Check user permissions for the command
    if (!$user->hasPermissionTo("execute.{$command}")) {
        return false;
    }
    
    // Validate app access for the team
    $commandAppMap = [
        'create_blog_post' => 'blog',
        'create_product' => 'products',
        'create_order' => 'orders',
        // ... other mappings
    ];
    
    $requiredApp = $commandAppMap[$command] ?? null;
    if ($requiredApp) {
        return $this->validateAppAccess($requiredApp, $teamId);
    }
    
    return true;
}

protected function validateAppAccess(string $appId, ?int $teamId): bool
{
    $query = App::where('app_id', $appId)->where('installed', true);
    
    if ($teamId) {
        $query->where(function ($q) use ($teamId) {
            $q->whereNull('team_id')->orWhere('team_id', $teamId);
        });
    }
    
    return $query->exists();
}
```

### 2. Rate Limiting with Tenant Context

```php
// In RouteServiceProvider.php
RateLimiter::for('ai-chat', function (Request $request) {
    $teamId = $request->input('team_id') ?? $request->user()->current_team_id;
    $key = "ai-chat:{$request->user()->id}:{$teamId}";
    
    return Limit::perMinute(config('ai-chat.commands.rate_limit'))
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'error' => 'Rate limit exceeded',
                        'message' => 'Too many AI requests. Please try again later.',
                        'retry_after' => $headers['Retry-After'] ?? 60,
                    ], 429, $headers);
                });
});
```

### 3. Input Sanitization and Validation

```php
public function sanitizeInput(string $input, ?int $teamId = null): string
{
    // Remove potential injection attempts
    $input = strip_tags($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    
    // Limit input length based on team settings
    $maxLength = $this->getTeamMaxInputLength($teamId) ?: 2000;
    $input = substr($input, 0, $maxLength);
    
    // Log potentially suspicious input
    if ($this->containsSuspiciousPatterns($input)) {
        Log::warning('Suspicious AI input detected', [
            'input' => $input,
            'user_id' => auth()->id(),
            'team_id' => $teamId,
            'tenant_id' => tenant('id'),
        ]);
    }
    
    return $input;
}
```

## Performance Optimization

### 1. Tenant-Aware Caching

```php
// Cache AI responses with tenant context
public function getCachedResponse(string $message, int $userId, ?int $teamId): ?string
{
    $cacheKey = "ai_response:" . md5($message) . ":{$userId}";
    if ($teamId) {
        $cacheKey .= ":{$teamId}";
    }
    
    return Cache::remember($cacheKey, 300, function () use ($message, $userId, $teamId) {
        return $this->sendToAI($message, $this->getTenantContext($userId, $teamId));
    });
}

// Cache user context per tenant
public function getUserContext(int $userId, ?int $teamId): array
{
    $cacheKey = "user_context:{$userId}";
    if ($teamId) {
        $cacheKey .= ":{$teamId}";
    }
    
    return Cache::remember($cacheKey, 600, function () use ($userId, $teamId) {
        return $this->gatherUserContext($userId, $teamId);
    });
}
```

### 2. Background Processing with Tenant Context

```php
// Queue tenant-aware AI operations
dispatch(new ProcessAiCommand($command, $user, $teamId, tenant('id')));

// Process file analysis with tenant storage
dispatch(new AnalyzeUploadedFile($file, $sessionId, $userId, $teamId, tenant('id')));
```

### 3. Database Optimization

```php
// Optimized queries with proper indexing
class AiChatMessage extends Model
{
    protected static function boot()
    {
        parent::boot();
        
        // Always scope queries by tenant
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('user_id', auth()->id());
                
                if ($teamId = request()->input('team_id')) {
                    $builder->where('team_id', $teamId);
                }
            }
        });
    }
}
```

## Advanced Features

### 1. Tenant-Specific Custom Tools

```php
public function getTenantCustomTools(int $userId, ?int $teamId): array
{
    $tools = [];
    
    // Get apps available to this tenant/team
    $availableApps = App::where('installed', true)
        ->where(function ($query) use ($teamId) {
            $query->whereNull('team_id')->orWhere('team_id', $teamId);
        })
        ->get();
    
    foreach ($availableApps as $app) {
        $tools = array_merge($tools, $this->getAppSpecificTools($app));
    }
    
    return $tools;
}

protected function getAppSpecificTools(App $app): array
{
    switch ($app->app_id) {
        case 'blog':
            return [
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'create_blog_post',
                        'description' => 'Create a new blog post',
                        'parameters' => [
                            'type' => 'object',
                            'properties' => [
                                'title' => ['type' => 'string'],
                                'content' => ['type' => 'string'],
                                'category' => ['type' => 'string'],
                                'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
                            ],
                            'required' => ['title', 'content'],
                        ],
                    ],
                ],
            ];
        // ... other app tools
        default:
            return [];
    }
}
```

### 2. Tenant-Isolated Conversation Memory

```php
public function updateConversationMemory(User $user, ?int $teamId, string $context): void
{
    $memory = ConversationMemory::firstOrCreate([
        'user_id' => $user->id,
        'team_id' => $teamId,
        'tenant_id' => tenant('id'),
    ]);
    
    $memory->update([
        'long_term_context' => array_merge(
            $memory->long_term_context ?? [],
            [$context]
        ),
        'preferences' => $this->extractUserPreferences($context),
        'last_interaction' => now(),
    ]);
}
```

### 3. Usage Analytics and Billing

```php
public function getUsageAnalytics(?int $teamId = null): array
{
    $query = AiChatUsage::where('user_id', auth()->id());
    
    if ($teamId) {
        $query->where('team_id', $teamId);
    }
    
    $usage = $query->selectRaw('
        DATE(created_at) as date,
        SUM(tokens_used) as total_tokens,
        SUM(cost) as total_cost,
        COUNT(*) as total_requests,
        provider,
        operation_type
    ')
    ->groupBy('date', 'provider', 'operation_type')
    ->orderBy('date', 'desc')
    ->get();
    
    return [
        'daily_usage' => $usage->groupBy('date'),
        'total_cost' => $usage->sum('total_cost'),
        'total_tokens' => $usage->sum('total_tokens'),
        'total_requests' => $usage->sum('total_requests'),
        'by_provider' => $usage->groupBy('provider'),
        'by_operation' => $usage->groupBy('operation_type'),
    ];
}
```

## Usage Tracking and Cost Management

### 1. Token Usage Tracking

The system automatically tracks all AI usage:

```php
// In AiChatService
protected function trackUsage(array $response, array $context, string $operationType): void
{
    try {
        $usage = $response['usage'] ?? [];
        $userId = $context['user']['id'] ?? null;
        $teamId = $context['team']['id'] ?? null;
        $sessionId = $context['session_id'] ?? null;
        $messageId = $context['message_id'] ?? null;
        
        if (!$userId) {
            Log::warning('Cannot track AI usage: user_id not found in context');
            return;
        }
        
        $tokensUsed = $usage['total_tokens'] ?? 0;
        $model = $response['model'] ?? 'unknown';
        $provider = $response['provider'] ?? 'openai';
        $cost = $this->calculateCost($tokensUsed, $model, $provider);
        
        AiChatUsage::create([
            'session_id' => $sessionId,
            'message_id' => $messageId,
            'user_id' => $userId,
            'team_id' => $teamId,
            'provider' => $provider,
            'model' => $model,
            'tokens_used' => $tokensUsed,
            'cost' => $cost,
            'operation_type' => $operationType,
            'metadata' => [
                'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                'completion_tokens' => $usage['completion_tokens'] ?? 0,
                'timestamp' => now()->toISOString(),
            ],
        ]);
    } catch (Exception $e) {
        Log::error('Failed to track AI usage', [
            'error' => $e->getMessage(),
            'user_id' => $context['user']['id'] ?? null,
            'team_id' => $context['team']['id'] ?? null,
        ]);
    }
}
```

### 2. Cost Calculation

```php
protected function calculateCost(int $tokens, string $model, string $provider): float
{
    // Pricing per 1K tokens (update these rates as needed)
    $pricing = [
        'openai' => [
            'gpt-4-turbo-preview' => 0.01,
            'gpt-4-vision-preview' => 0.01,
            'gpt-3.5-turbo' => 0.0015,
            'text-embedding-3-small' => 0.00002,
        ],
        'anthropic' => [
            'claude-3-sonnet-20240229' => 0.003,
            'claude-3-haiku-20240307' => 0.00025,
        ],
        'groq' => [
            'llama3-70b-8192' => 0.00059,
            'mixtral-8x7b-32768' => 0.00024,
        ],
    ];
    
    $rate = $pricing[$provider][$model] ?? 0.001; // Default rate
    return ($tokens / 1000) * $rate;
}
```

## Troubleshooting

### Common Issues

1. **Tenant isolation not working**
   - Verify middleware is registered and applied to routes
   - Check tenant context is properly injected
   - Ensure models use tenant-aware scopes

2. **AI responses are slow**
   - Check API timeouts in configuration
   - Consider using faster models for simple queries
   - Implement response caching

3. **Commands not executing**
   - Verify user permissions with `php artisan permission:show`
   - Check team membership and app access
   - Review command validation logs

4. **File uploads failing**
   - Check tenant-specific directory permissions
   - Verify file size limits and allowed types
   - Ensure storage disk configuration is correct

5. **Usage tracking not recording**
   - Check database connections and migration status
   - Verify AI service is calling `trackUsage` method
   - Review error logs for tracking failures

### Debug Mode

Enable detailed logging by adding to `.env`:

```env
AI_CHAT_LOG_REQUESTS=true
AI_CHAT_LOG_RESPONSES=true
AI_CHAT_LOG_COMMANDS=true
AI_CHAT_LOG_TENANT_ACCESS=true
LOG_LEVEL=debug
```

### Monitoring Commands

```bash
# Check AI chat usage
php artisan tinker
>>> AiChatUsage::where('created_at', '>=', now()->subDay())->sum('cost')

# View recent sessions
>>> AiChatSession::with('messages')->latest()->take(5)->get()

# Check tenant isolation
>>> AiChatMessage::tenantIsolated(1, 1)->count()

# Monitor file storage
>>> du -sh storage/app/ai-chat/
```

## Production Deployment

### 1. Environment Setup

```bash
# Production optimizations
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart

# Set up Redis for caching and sessions
redis-server --daemonize yes
```

### 2. Security Hardening

```env
# Production security settings
AI_CHAT_RATE_LIMIT=50
AI_COMMAND_EXECUTION_ENABLED=true
AI_COMMAND_AUDIT_LOG=true
AI_CHAT_MAX_FILE_SIZE=5M
OPENAI_REQUEST_TIMEOUT=20
```

### 3. Queue Configuration

```bash
# Set up queues for AI processing
php artisan queue:table
php artisan migrate

# Start queue workers
php artisan queue:work --queue=ai-chat,default --tries=3
```

### 4. Monitoring Setup

```php
// Add to AppServiceProvider
public function boot()
{
    // Monitor AI usage
    AiChatUsage::created(function ($usage) {
        if ($usage->cost > 1.0) { // Alert for high-cost operations
            Log::warning('High-cost AI operation', [
                'user_id' => $usage->user_id,
                'team_id' => $usage->team_id,
                'cost' => $usage->cost,
                'tokens' => $usage->tokens_used,
            ]);
        }
    });
}
```

## Testing

### 1. Feature Tests

```php
// Test tenant isolation
public function test_ai_chat_respects_tenant_isolation()
{
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    tenancy()->initialize($tenant1);
    $user1 = User::factory()->create();
    $session1 = AiChatSession::factory()->create(['user_id' => $user1->id]);
    
    tenancy()->initialize($tenant2);
    $user2 = User::factory()->create();
    
    // User 2 should not see User 1's sessions
    $this->actingAs($user2)
         ->getJson('/api/ai-chat/history')
         ->assertJsonMissing(['session_id' => $session1->id]);
}

// Test team access
public function test_team_access_validation()
{
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $otherTeam = Team::factory()->create();
    
    $user->teams()->attach($team);
    
    $this->actingAs($user)
         ->postJson('/api/ai-chat/message', [
             'message' => 'Hello',
             'team_id' => $otherTeam->id, // User not member of this team
         ])
         ->assertStatus(403);
}
```

### 2. Unit Tests

```php
// Test usage tracking
public function test_usage_tracking_records_correctly()
{
    $user = User::factory()->create();
    $team = Team::factory()->create();
    
    $service = new AiChatService();
    $response = [
        'usage' => ['total_tokens' => 100],
        'model' => 'gpt-4',
        'provider' => 'openai',
    ];
    
    $context = [
        'user' => ['id' => $user->id],
        'team' => ['id' => $team->id],
    ];
    
    $service->trackUsage($response, $context, 'chat');
    
    $this->assertDatabaseHas('ai_chat_usage', [
        'user_id' => $user->id,
        'team_id' => $team->id,
        'tokens_used' => 100,
        'model' => 'gpt-4',
    ]);
}
```

This comprehensive guide now integrates the AI system with your complete tech stack, enabling it to provide intelligent answers and execute commands across:

- **Aimeos E-commerce** - Full product, order, and customer management
- **File Management** - ElFinder integration with tenant isolation
- **Analytics & SEO** - Website performance and optimization
- **Notifications** - Real-time communication via Reverb
- **Translations** - Multi-language content management
- **Widgets** - Dashboard and UI customization
- **Search** - Scout-powered global search
- **Payments** - Wallet and payment gateway integration
- **And much more** across your entire ecosystem

The AI can now understand context from all these systems and execute sophisticated commands that span multiple applications within your desktop environment. 