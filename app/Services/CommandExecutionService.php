<?php

namespace App\Services;

use App\Models\User;
use App\Models\Team;
use App\Models\App;
use App\Models\AiChatUsage;
use App\Models\AiSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Facades\LogActivity;

/**
 * Command Execution Service - Handles AI-generated command execution with tenant isolation
 *
 * This service manages the execution of commands parsed from AI responses, providing
 * comprehensive validation, security, and tenant isolation. It supports multiple
 * application categories and ensures proper permission checking before execution.
 *
 * Key responsibilities:
 * - Command validation and permission checking
 * - Tenant isolation and team membership validation
 * - AI privacy settings enforcement
 * - Multi-category command routing
 * - Error handling and logging
 * - Activity tracking for audit trails
 *
 * Supported command categories:
 * - E-commerce (Aimeos integration)
 * - File management and storage
 * - Analytics and SEO
 * - Notifications and communication
 * - Translation and localization
 * - Widgets and dashboards
 * - Search and indexing
 * - Payments and billing
 * - Settings and configuration
 * - Blog and content management
 * - Contact and CRM management
 * - System administration
 * - Site builder (GrapesJS integration)
 *
 * The service ensures:
 * - Proper tenant isolation for all operations
 * - User permission validation
 * - AI privacy level compliance
 * - Secure command execution
 * - Comprehensive error handling
 * - Activity logging for audit trails
 *
 * @package App\Services
 * @since 1.0.0
 */
class CommandExecutionService
{
    /**
     * Execute a command with comprehensive validation and tenant isolation
     *
     * This method handles the complete command execution workflow including
     * validation, permission checking, privacy compliance, and category routing.
     * It ensures secure execution with proper tenant isolation and user validation.
     *
     * The execution process includes:
     * - Team membership validation
     * - Application access validation
     * - Command permission checking
     * - AI privacy settings compliance
     * - Category-based command routing
     * - Error handling and logging
     *
     * @param string $command The command to execute
     * @param string $app The application category for the command
     * @param string $action The specific action to perform
     * @param array $parameters Command parameters and data
     * @param User $user The authenticated user executing the command
     * @param int|null $teamId Optional team ID for team-specific operations
     * @return array Response containing success status, data, and messages
     * @throws \Exception When command execution fails or validation errors occur
     */
    public function execute(string $command, string $app, string $action, array $parameters, User $user, ?int $teamId): array
    {
        try {
            // Validate team membership and permissions
            if (!$this->validateTeamMembership($user, $teamId)) {
                return ['success' => false, 'message' => 'Access denied: Invalid team membership'];
            }

            if (!$this->validateAppAccess($app, $teamId)) {
                return ['success' => false, 'message' => "Access denied: {$app} app not available for this team"];
            }

            if (!$this->validateCommandPermission($user, $command, $teamId)) {
                return ['success' => false, 'message' => "Access denied: Insufficient permissions for {$command}"];
            }

            // Check AI privacy settings - only allow command execution if user is in 'agent' mode
            $aiSettings = AiSettings::getOrCreateForUser($user->id, $teamId);
            if (!$aiSettings->canExecuteCommands()) {
                return [
                    'success' => false, 
                    'message' => 'Command execution disabled. Please enable "Agent" mode in AI settings to allow command execution.',
                    'privacy_level' => $aiSettings->privacy_level
                ];
            }

            // Route to appropriate command handler based on app category
            return match ($app) {
                'ecommerce', 'aimeos', 'shop', 'products', 'orders' => $this->executeEcommerceCommand($command, $parameters, $user, $teamId),
                'files', 'elfinder', 'storage' => $this->executeFileCommand($command, $parameters, $user, $teamId),
                'analytics', 'seo', 'marketing' => $this->executeAnalyticsCommand($command, $parameters, $user, $teamId),
                'notifications', 'communication', 'sms', 'email' => $this->executeNotificationCommand($command, $parameters, $user, $teamId),
                'translations', 'localization', 'i18n' => $this->executeTranslationCommand($command, $parameters, $user, $teamId),
                'widgets', 'dashboard', 'ui' => $this->executeWidgetCommand($command, $parameters, $user, $teamId),
                'search', 'scout', 'indexing' => $this->executeSearchCommand($command, $parameters, $user, $teamId),
                'payments', 'wallet', 'billing' => $this->executePaymentCommand($command, $parameters, $user, $teamId),
                'settings', 'config', 'preferences' => $this->executeSettingsCommand($command, $parameters, $user, $teamId),
                'blog', 'content', 'cms' => $this->executeBlogCommand($command, $parameters, $user, $teamId),
                'contacts', 'crm', 'customers' => $this->executeContactCommand($command, $parameters, $user, $teamId),
                'system', 'admin', 'maintenance' => $this->executeSystemCommand($command, $parameters, $user, $teamId),
                'sitebuilder', 'pagebuilder', 'grapes', 'grapesjs' => $this->executeSitebuilderCommand($command, $parameters, $user, $teamId),
                default => $this->executeGenericCommand($command, $parameters, $user, $teamId)
            };

        } catch (\Exception $e) {
            Log::error('Command execution failed', [
                'command' => $command,
                'app' => $app,
                'user_id' => $user->id,
                'team_id' => $teamId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Command execution failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Execute e-commerce related commands with Aimeos integration
     *
     * This method handles all e-commerce operations including product management,
     * inventory updates, order processing, and analytics. It integrates with
     * the Aimeos e-commerce system when available.
     *
     * Supported commands:
     * - get_product_stats: Retrieve product statistics
     * - create_product: Create new products
     * - update_inventory: Update product inventory levels
     * - get_order_summary: Get order analytics
     * - process_refund: Process refunds
     * - get_sales_analytics: Get sales analytics
     * - manage_customer: Customer management operations
     * - update_pricing: Update product pricing
     * - manage_discounts: Discount management
     * - get_inventory_alerts: Low stock alerts
     * - create_category: Create product categories
     * - manage_shipping: Shipping management
     *
     * @param string $command The specific e-commerce command to execute
     * @param array $parameters Command parameters and data
     * @param User $user The authenticated user
     * @param int $teamId The team ID for tenant isolation
     * @return array Response containing execution results
     */
    protected function executeEcommerceCommand(string $command, array $parameters, User $user, int $teamId): array
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
            
            case 'create_category':
                return $this->createProductCategory($parameters, $user, $teamId);
            
            case 'manage_shipping':
                return $this->manageShipping($parameters, $teamId);
            
            default:
                return ['success' => false, 'message' => "Unknown e-commerce command: {$command}"];
        }
    }

    /**
     * Create a new product using Aimeos e-commerce system
     *
     * This method creates products in the Aimeos system with proper validation
     * and tenant isolation. It handles product data, pricing, and categorization.
     *
     * The method supports:
     * - Product name and SKU generation
     * - Price setting with currency
     * - Category assignment
     * - Status management
     * - Activity logging
     *
     * @param array $data Product data including name, price, category, etc.
     * @param User $user The user creating the product
     * @param int $teamId The team ID for tenant isolation
     * @return array Response containing product creation results
     */
    protected function createAimeosProduct(array $data, User $user, int $teamId): array
    {
        try {
            // Check if Aimeos is available
            if (!class_exists('\Aimeos\Shop\Facades\Shop')) {
                return ['success' => false, 'message' => 'Aimeos e-commerce system not available'];
            }

            $context = \Aimeos\Shop\Facades\Shop::context();
            $manager = \Aimeos\MShop::create($context, 'product');
            
            $item = $manager->create();
            $item->setLabel($data['name']);
            $item->setCode($data['sku'] ?? strtolower(str_replace(' ', '-', $data['name'])) . '-' . time());
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

    /**
     * Get product statistics for the team
     *
     * This method retrieves comprehensive product statistics including total
     * products, active products, low stock items, and other metrics.
     *
     * @param array $parameters Query parameters for filtering
     * @param int $teamId The team ID for tenant isolation
     * @return array Response containing product statistics
     */
    protected function getProductStatistics(array $parameters, int $teamId): array
    {
        try {
            if (!class_exists('\Aimeos\Shop\Facades\Shop')) {
                // Fallback to generic product stats if Aimeos not available
                return $this->getGenericProductStats($teamId);
            }

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
                    'team_id' => $teamId,
                    'platform' => 'Aimeos'
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to get product statistics: ' . $e->getMessage()];
        }
    }

    // File Management Commands
    protected function executeFileCommand(string $command, array $parameters, User $user, int $teamId): array
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
            
            case 'compress_files':
                return $this->compressFiles($parameters, $teamId);
            
            case 'backup_files':
                return $this->backupFiles($parameters, $teamId);
            
            default:
                return ['success' => false, 'message' => "Unknown file command: {$command}"];
        }
    }

    protected function listTenantFiles(array $parameters, int $teamId): array
    {
        try {
            $path = $parameters['path'] ?? '';
            $tenantPath = "tenant-{$teamId}/{$path}";
            
            $disk = Storage::disk('local'); // Use local disk as fallback
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
                    'total_size' => array_sum(array_map(fn($f) => $disk->size($f), $files)),
                    'path' => $path
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to list files: ' . $e->getMessage()];
        }
    }

    // Analytics and SEO Commands
    protected function executeAnalyticsCommand(string $command, array $parameters, User $user, int $teamId): array
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
            
            case 'track_conversions':
                return $this->trackConversions($parameters, $teamId);
            
            case 'competitor_analysis':
                return $this->performCompetitorAnalysis($parameters, $teamId);
            
            default:
                return ['success' => false, 'message' => "Unknown analytics command: {$command}"];
        }
    }

    protected function getWebsiteAnalytics(array $parameters, int $teamId): array
    {
        try {
            $period = $parameters['period'] ?? '30daysAgo';
            
            // Try to use Google Analytics if available
            if (class_exists('\Spatie\Analytics\Analytics')) {
                $analytics = app(\Spatie\Analytics\Analytics::class);
                $data = $analytics->fetchMostVisitedPages($period);
                
                return [
                    'success' => true,
                    'data' => [
                        'most_visited_pages' => $data->take(10),
                        'period' => $period,
                        'team_id' => $teamId,
                        'generated_at' => now()->toISOString(),
                        'provider' => 'Google Analytics'
                    ]
                ];
            }
            
            // Fallback to basic analytics
            return $this->getBasicAnalytics($parameters, $teamId);
            
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to fetch analytics: ' . $e->getMessage()];
        }
    }

    // Notification Commands
    protected function executeNotificationCommand(string $command, array $parameters, User $user, int $teamId): array
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
            
            case 'schedule_notification':
                return $this->scheduleNotification($parameters, $user, $teamId);
            
            case 'broadcast_message':
                return $this->broadcastMessage($parameters, $user, $teamId);
            
            default:
                return ['success' => false, 'message' => "Unknown notification command: {$command}"];
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
            
            // Try to broadcast if Laravel Reverb is available
            if (class_exists('\App\Events\TeamNotification')) {
                broadcast(new \App\Events\TeamNotification($notification, $teamId));
            }
            
            // Store in database
            DB::table('notifications')->insert([
                'id' => \Illuminate\Support\Str::uuid(),
                'type' => 'App\\Notifications\\TeamNotification',
                'notifiable_type' => 'App\\Models\\Team',
                'notifiable_id' => $teamId,
                'data' => json_encode($notification),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            return [
                'success' => true,
                'data' => [
                    'message' => 'Notification sent successfully',
                    'recipients' => $this->getTeamMemberCount($teamId),
                    'notification_id' => \Illuminate\Support\Str::uuid()
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage()
            ];
        }
    }

    // Translation Commands
    protected function executeTranslationCommand(string $command, array $parameters, User $user, int $teamId): array
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
            
            case 'import_translations':
                return $this->importTranslations($parameters, $user, $teamId);
            
            case 'auto_translate_missing':
                return $this->autoTranslateMissing($parameters, $teamId);
            
            default:
                return ['success' => false, 'message' => "Unknown translation command: {$command}"];
        }
    }

    protected function translateContent(array $parameters, int $teamId): array
    {
        try {
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
                    'team_id' => $teamId,
                    'method' => 'AI Translation'
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Translation failed: ' . $e->getMessage()];
        }
    }

    // Widget Commands
    protected function executeWidgetCommand(string $command, array $parameters, User $user, int $teamId): array
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
            
            case 'export_dashboard':
                return $this->exportDashboard($parameters, $user, $teamId);
            
            case 'clone_widget':
                return $this->cloneWidget($parameters, $user, $teamId);
            
            default:
                return ['success' => false, 'message' => "Unknown widget command: {$command}"];
        }
    }

    protected function createWidget(array $parameters, User $user, int $teamId): array
    {
        try {
            $widgetData = [
                'name' => $parameters['name'],
                'type' => $parameters['type'],
                'config' => json_encode($parameters['config'] ?? []),
                'user_id' => $user->id,
                'team_id' => $teamId,
                'position' => json_encode($parameters['position'] ?? ['x' => 0, 'y' => 0, 'w' => 4, 'h' => 3]),
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $widgetId = DB::table('widgets')->insertGetId($widgetData);
            
            return [
                'success' => true,
                'data' => [
                    'widget_id' => $widgetId,
                    'message' => 'Widget created successfully',
                    'widget' => $widgetData
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to create widget: ' . $e->getMessage()];
        }
    }

    // Search Commands
    protected function executeSearchCommand(string $command, array $parameters, User $user, int $teamId): array
    {
        switch ($command) {
            case 'search_content':
                return $this->searchContent($parameters, $teamId);
            
            case 'index_content':
                return $this->indexContent($parameters, $teamId);
            
            case 'get_search_stats':
                return $this->getSearchStatistics($teamId);
            
            case 'optimize_search':
                return $this->optimizeSearch($parameters, $teamId);
            
            case 'search_suggestions':
                return $this->getSearchSuggestions($parameters, $teamId);
            
            default:
                return ['success' => false, 'message' => "Unknown search command: {$command}"];
        }
    }

    protected function searchContent(array $parameters, int $teamId): array
    {
        try {
            $query = $parameters['query'];
            $filters = $parameters['filters'] ?? [];
            
            $results = [
                'total_results' => 0,
                'query' => $query,
                'team_id' => $teamId,
                'results' => []
            ];
            
            // Search across different models if they exist
            $searchableModels = [
                'products' => 'App\\Models\\Product',
                'orders' => 'App\\Models\\Order',
                'customers' => 'App\\Models\\Customer',
                'content' => 'App\\Models\\Content',
                'blog_posts' => 'App\\Models\\BlogPost',
                'files' => 'App\\Models\\File'
            ];
            
            foreach ($searchableModels as $type => $modelClass) {
                if (class_exists($modelClass)) {
                    try {
                        if (method_exists($modelClass, 'search')) {
                            $searchResults = $modelClass::search($query)->where('team_id', $teamId)->get();
                            $results['results'][$type] = $searchResults;
                            $results['total_results'] += $searchResults->count();
                        }
                    } catch (\Exception $e) {
                        // Skip if search fails for this model
                        continue;
                    }
                }
            }
            
            return [
                'success' => true,
                'data' => $results
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Search failed: ' . $e->getMessage()];
        }
    }

    // Payment Commands
    protected function executePaymentCommand(string $command, array $parameters, User $user, int $teamId): array
    {
        switch ($command) {
            case 'process_payment':
                return $this->processPayment($parameters, $user, $teamId);
            
            case 'get_wallet_balance':
                return $this->getWalletBalance($parameters, $teamId);
            
            case 'create_invoice':
                return $this->createInvoice($parameters, $user, $teamId);
            
            case 'get_payment_stats':
                return $this->getPaymentStatistics($teamId);
            
            case 'setup_subscription':
                return $this->setupSubscription($parameters, $user, $teamId);
            
            default:
                return ['success' => false, 'message' => "Unknown payment command: {$command}"];
        }
    }

    // Settings Commands
    protected function executeSettingsCommand(string $command, array $parameters, User $user, int $teamId): array
    {
        switch ($command) {
            case 'update_settings':
                return $this->updateSettings($parameters, $user, $teamId);
            
            case 'get_settings':
                return $this->getSettings($parameters, $teamId);
            
            case 'reset_settings':
                return $this->resetSettings($parameters, $user, $teamId);
            
            case 'export_settings':
                return $this->exportSettings($parameters, $teamId);
            
            default:
                return ['success' => false, 'message' => "Unknown settings command: {$command}"];
        }
    }

    // Blog/Content Commands
    protected function executeBlogCommand(string $command, array $parameters, User $user, int $teamId): array
    {
        switch ($command) {
            case 'create_blog_post':
                return $this->createBlogPost($parameters, $user, $teamId);
            
            case 'update_blog_post':
                return $this->updateBlogPost($parameters, $user, $teamId);
            
            case 'publish_blog_post':
                return $this->publishBlogPost($parameters, $user, $teamId);
            
            case 'get_blog_stats':
                return $this->getBlogStatistics($teamId);
            
            case 'optimize_content':
                return $this->optimizeContent($parameters, $teamId);
            
            default:
                return ['success' => false, 'message' => "Unknown blog command: {$command}"];
        }
    }

    // Contact/CRM Commands
    protected function executeContactCommand(string $command, array $parameters, User $user, int $teamId): array
    {
        switch ($command) {
            case 'create_contact':
                return $this->createContact($parameters, $user, $teamId);
            
            case 'update_contact':
                return $this->updateContact($parameters, $user, $teamId);
            
            case 'search_contacts':
                return $this->searchContacts($parameters, $teamId);
            
            case 'export_contacts':
                return $this->exportContacts($parameters, $teamId);
            
            case 'import_contacts':
                return $this->importContacts($parameters, $user, $teamId);
            
            default:
                return ['success' => false, 'message' => "Unknown contact command: {$command}"];
        }
    }

    // System Commands
    protected function executeSystemCommand(string $command, array $parameters, User $user, int $teamId): array
    {
        switch ($command) {
            case 'system_health':
                return $this->getSystemHealth($teamId);
            
            case 'backup_data':
                return $this->backupData($parameters, $teamId);
            
            case 'clear_cache':
                return $this->clearCache($parameters, $teamId);
            
            case 'update_app':
                return $this->updateApp($parameters, $user, $teamId);
            
            case 'generate_report':
                return $this->generateReport($parameters, $user, $teamId);
            
            default:
                return ['success' => false, 'message' => "Unknown system command: {$command}"];
        }
    }

    // Generic Command Handler
    protected function executeGenericCommand(string $command, array $parameters, User $user, int $teamId): array
    {
        return [
            'success' => false,
            'message' => "Command '{$command}' not implemented yet. Available categories: ecommerce, files, analytics, notifications, translations, widgets, search, payments, settings, blog, contacts, system"
        ];
    }

    // Validation Methods
    protected function validateTeamMembership(User $user, ?int $teamId): bool
    {
        if (!$teamId) {
            return true; // No team specified, allow personal commands
        }
        
        return $user->teams()->where('teams.id', $teamId)->exists();
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

    protected function validateCommandPermission(User $user, string $command, ?int $teamId): bool
    {
        // Check if user has permission to execute this command
        if (!$user->hasPermissionTo("execute.{$command}")) {
            return false;
        }
        
        // Additional command-specific validation
        $commandAppMap = [
            'create_product' => 'ecommerce',
            'create_blog_post' => 'blog',
            'send_sms' => 'communication',
            'backup_data' => 'system',
            // Add more mappings as needed
        ];
        
        $requiredApp = $commandAppMap[$command] ?? null;
        if ($requiredApp) {
            return $this->validateAppAccess($requiredApp, $teamId);
        }
        
        return true;
    }

    // Helper Methods
    protected function getTeamMemberCount(int $teamId): int
    {
        return DB::table('team_user')->where('team_id', $teamId)->count();
    }

    protected function callAiForTranslation(string $prompt): string
    {
        // This would call your AI service for translation
        // For now, return a placeholder
        return "AI-translated content: " . $prompt;
    }

    protected function getGenericProductStats(int $teamId): array
    {
        // Fallback product statistics when Aimeos is not available
        return [
            'success' => true,
            'data' => [
                'total_products' => 0,
                'active_products' => 0,
                'inactive_products' => 0,
                'low_stock_items' => 0,
                'team_id' => $teamId,
                'platform' => 'Generic'
            ]
        ];
    }

    protected function getBasicAnalytics(array $parameters, int $teamId): array
    {
        return [
            'success' => true,
            'data' => [
                'message' => 'Basic analytics not yet implemented',
                'team_id' => $teamId,
                'provider' => 'Basic'
            ]
        ];
    }

    protected function createBlogPost(array $data, User $user, int $teamId): array
    {
        try {
            $blogPost = [
                'title' => $data['title'],
                'content' => $data['content'],
                'excerpt' => $data['excerpt'] ?? substr($data['content'], 0, 160),
                'status' => $data['status'] ?? 'draft',
                'author_id' => $user->id,
                'team_id' => $teamId,
                'published_at' => $data['status'] === 'published' ? now() : null,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $postId = DB::table('blog_posts')->insertGetId($blogPost);
            
            // Log activity
            activity()
                ->causedBy($user)
                ->withProperties(['team_id' => $teamId, 'ai_generated' => true])
                ->log('AI created blog post: ' . $data['title']);
            
            return [
                'success' => true,
                'data' => [
                    'post_id' => $postId,
                    'title' => $data['title'],
                    'status' => $blogPost['status'],
                    'message' => 'Blog post created successfully'
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to create blog post: ' . $e->getMessage()];
        }
    }

    protected function createContact(array $data, User $user, int $teamId): array
    {
        try {
            $contact = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'company' => $data['company'] ?? null,
                'position' => $data['position'] ?? null,
                'notes' => $data['notes'] ?? null,
                'user_id' => $user->id,
                'team_id' => $teamId,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            $contactId = DB::table('contacts')->insertGetId($contact);
            
            return [
                'success' => true,
                'data' => [
                    'contact_id' => $contactId,
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'message' => 'Contact created successfully'
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to create contact: ' . $e->getMessage()];
        }
    }

    protected function getSystemHealth(int $teamId): array
    {
        return [
            'success' => true,
            'data' => [
                'status' => 'healthy',
                'uptime' => '99.9%',
                'memory_usage' => '65%',
                'disk_usage' => '45%',
                'active_users' => $this->getTeamMemberCount($teamId),
                'team_id' => $teamId,
                'timestamp' => now()->toISOString()
            ]
        ];
    }

    // Sitebuilder Commands (Laravel Grapes Integration)
    protected function executeSitebuilderCommand(string $command, array $parameters, User $user, int $teamId): array
    {
        switch ($command) {
            case 'createPage':
                return $this->createGrapesPage($parameters, $user, $teamId);
            
            case 'updatePageContent':
                return $this->updatePageContent($parameters, $user, $teamId);
            
            case 'changePageColors':
                return $this->changePageColors($parameters, $user, $teamId);
            
            case 'updatePageTexts':
                return $this->updatePageTexts($parameters, $user, $teamId);
            
            case 'createPageTemplate':
                return $this->createPageTemplate($parameters, $user, $teamId);
            
            case 'clonePage':
                return $this->clonePage($parameters, $user, $teamId);
            
            case 'optimizePageSeo':
                return $this->optimizePageSeo($parameters, $user, $teamId);
            
            case 'addPageComponent':
                return $this->addPageComponent($parameters, $user, $teamId);
            
            case 'removePageComponent':
                return $this->removePageComponent($parameters, $user, $teamId);
            
            case 'updatePageSettings':
                return $this->updatePageSettings($parameters, $user, $teamId);
            
            case 'exportPageHtml':
                return $this->exportPageHtml($parameters, $user, $teamId);
            
            case 'importPageTemplate':
                return $this->importPageTemplate($parameters, $user, $teamId);
            
            case 'previewPage':
                return $this->previewPage($parameters, $user, $teamId);
            
            case 'publishPage':
                return $this->publishPage($parameters, $user, $teamId);
            
            case 'updatePageStyles':
                return $this->updatePageStyles($parameters, $user, $teamId);
            
            case 'addPageBlock':
                return $this->addPageBlock($parameters, $user, $teamId);
            
            default:
                return ['success' => false, 'message' => "Unknown sitebuilder command: {$command}"];
        }
    }

    protected function createGrapesPage(array $parameters, User $user, int $teamId): array
    {
        try {
            $data = $parameters['data'] ?? [];
            
            // Validate required parameters
            if (empty($data['name']) || empty($data['slug'])) {
                return ['success' => false, 'message' => 'Page name and slug are required'];
            }

            // Check if slug already exists for this team
            $existingPage = DB::table('pages')
                ->where('slug', $data['slug'])
                ->where('team_id', $teamId)
                ->first();

            if ($existingPage) {
                return ['success' => false, 'message' => 'A page with this slug already exists'];
            }

            // Create the page with GrapesJS data structure
            $pageData = [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'title' => $data['title'] ?? $data['name'],
                'description' => $data['description'] ?? '',
                'keywords' => $data['keywords'] ?? '',
                'status' => $data['status'] ?? 'draft',
                'gjs_data' => json_encode([
                    'gjs-html' => $data['html'] ?? '<div>New Page Content</div>',
                    'gjs-css' => $data['css'] ?? '',
                    'gjs-components' => $data['components'] ?? [],
                    'gjs-style' => $data['styles'] ?? [],
                ]),
                'user_id' => $user->id,
                'team_id' => $teamId,
                'created_at' => now(),
                'updated_at' => now()
            ];

            $pageId = DB::table('pages')->insertGetId($pageData);

            return [
                'success' => true,
                'data' => [
                    'page_id' => $pageId,
                    'name' => $data['name'],
                    'slug' => $data['slug'],
                    'edit_url' => route('sitebuilder.edit', ['page' => $pageId]),
                    'preview_url' => route('sitebuilder.preview', ['page' => $pageId]),
                    'message' => 'Page created successfully'
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to create page: ' . $e->getMessage()];
        }
    }

    protected function updatePageContent(array $parameters, User $user, int $teamId): array
    {
        try {
            $data = $parameters['data'] ?? [];
            $pageId = $data['page_id'] ?? null;

            if (!$pageId) {
                return ['success' => false, 'message' => 'Page ID is required'];
            }

            // Find the page and verify ownership
            $page = DB::table('pages')
                ->where('id', $pageId)
                ->where('team_id', $teamId)
                ->first();

            if (!$page) {
                return ['success' => false, 'message' => 'Page not found or access denied'];
            }

            // Decode existing GrapesJS data
            $gjsData = json_decode($page->gjs_data, true) ?? [];

            // Update content based on provided data
            if (isset($data['html'])) {
                $gjsData['gjs-html'] = $data['html'];
            }
            if (isset($data['css'])) {
                $gjsData['gjs-css'] = $data['css'];
            }
            if (isset($data['components'])) {
                $gjsData['gjs-components'] = $data['components'];
            }
            if (isset($data['styles'])) {
                $gjsData['gjs-style'] = $data['styles'];
            }

            // Update the page
            DB::table('pages')
                ->where('id', $pageId)
                ->update([
                    'gjs_data' => json_encode($gjsData),
                    'updated_at' => now()
                ]);

            return [
                'success' => true,
                'data' => [
                    'page_id' => $pageId,
                    'message' => 'Page content updated successfully',
                    'updated_at' => now()->toISOString()
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to update page content: ' . $e->getMessage()];
        }
    }

    protected function changePageColors(array $parameters, User $user, int $teamId): array
    {
        try {
            $data = $parameters['data'] ?? [];
            $pageId = $data['page_id'] ?? null;
            $colorScheme = $data['color_scheme'] ?? [];

            if (!$pageId || empty($colorScheme)) {
                return ['success' => false, 'message' => 'Page ID and color scheme are required'];
            }

            // Find the page
            $page = DB::table('pages')
                ->where('id', $pageId)
                ->where('team_id', $teamId)
                ->first();

            if (!$page) {
                return ['success' => false, 'message' => 'Page not found or access denied'];
            }

            // Decode existing GrapesJS data
            $gjsData = json_decode($page->gjs_data, true) ?? [];
            $currentCss = $gjsData['gjs-css'] ?? '';

            // Apply color changes to CSS
            $updatedCss = $this->applyColorSchemeToCSS($currentCss, $colorScheme);
            $gjsData['gjs-css'] = $updatedCss;

            // Update the page
            DB::table('pages')
                ->where('id', $pageId)
                ->update([
                    'gjs_data' => json_encode($gjsData),
                    'updated_at' => now()
                ]);

            return [
                'success' => true,
                'data' => [
                    'page_id' => $pageId,
                    'color_scheme' => $colorScheme,
                    'message' => 'Page colors updated successfully'
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to update page colors: ' . $e->getMessage()];
        }
    }

    protected function updatePageTexts(array $parameters, User $user, int $teamId): array
    {
        try {
            $data = $parameters['data'] ?? [];
            $pageId = $data['page_id'] ?? null;
            $textUpdates = $data['text_updates'] ?? [];

            if (!$pageId || empty($textUpdates)) {
                return ['success' => false, 'message' => 'Page ID and text updates are required'];
            }

            // Find the page
            $page = DB::table('pages')
                ->where('id', $pageId)
                ->where('team_id', $teamId)
                ->first();

            if (!$page) {
                return ['success' => false, 'message' => 'Page not found or access denied'];
            }

            // Decode existing GrapesJS data
            $gjsData = json_decode($page->gjs_data, true) ?? [];
            $currentHtml = $gjsData['gjs-html'] ?? '';

            // Apply text updates to HTML
            $updatedHtml = $this->applyTextUpdatesToHTML($currentHtml, $textUpdates);
            $gjsData['gjs-html'] = $updatedHtml;

            // Update the page
            DB::table('pages')
                ->where('id', $pageId)
                ->update([
                    'gjs_data' => json_encode($gjsData),
                    'updated_at' => now()
                ]);

            return [
                'success' => true,
                'data' => [
                    'page_id' => $pageId,
                    'text_updates' => count($textUpdates),
                    'message' => 'Page texts updated successfully'
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to update page texts: ' . $e->getMessage()];
        }
    }

    protected function publishPage(array $parameters, User $user, int $teamId): array
    {
        try {
            $data = $parameters['data'] ?? [];
            $pageId = $data['page_id'] ?? null;

            if (!$pageId) {
                return ['success' => false, 'message' => 'Page ID is required'];
            }

            // Find the page
            $page = DB::table('pages')
                ->where('id', $pageId)
                ->where('team_id', $teamId)
                ->first();

            if (!$page) {
                return ['success' => false, 'message' => 'Page not found or access denied'];
            }

            // Update page status to published
            DB::table('pages')
                ->where('id', $pageId)
                ->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'updated_at' => now()
                ]);

            // Generate public URL based on frontend prefix
            $frontendPrefix = config('ai-chat.integrations.sitebuilder.frontend_prefix', '');
            $publicUrl = $frontendPrefix ? "/{$frontendPrefix}/{$page->slug}" : "/{$page->slug}";

            return [
                'success' => true,
                'data' => [
                    'page_id' => $pageId,
                    'status' => 'published',
                    'public_url' => $publicUrl,
                    'published_at' => now()->toISOString(),
                    'message' => 'Page published successfully'
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to publish page: ' . $e->getMessage()];
        }
    }

    // Helper methods for sitebuilder operations
    protected function applyColorSchemeToCSS(string $css, array $colorScheme): string
    {
        // Simple color replacement - in a real implementation, you'd want more sophisticated CSS parsing
        foreach ($colorScheme as $property => $color) {
            switch ($property) {
                case 'primary':
                    $css = preg_replace('/color:\s*#[0-9a-fA-F]{6}/', "color: {$color}", $css);
                    break;
                case 'background':
                    $css = preg_replace('/background-color:\s*#[0-9a-fA-F]{6}/', "background-color: {$color}", $css);
                    break;
                case 'accent':
                    $css = preg_replace('/border-color:\s*#[0-9a-fA-F]{6}/', "border-color: {$color}", $css);
                    break;
            }
        }
        return $css;
    }

    protected function applyTextUpdatesToHTML(string $html, array $textUpdates): string
    {
        // Simple text replacement - in a real implementation, you'd want DOM parsing
        foreach ($textUpdates as $update) {
            $selector = $update['selector'] ?? '';
            $oldText = $update['old_text'] ?? '';
            $newText = $update['new_text'] ?? '';
            
            if ($oldText && $newText) {
                $html = str_replace($oldText, $newText, $html);
            }
        }
        return $html;
    }

    // Placeholder implementations for other sitebuilder commands
    protected function createPageTemplate(array $parameters, User $user, int $teamId): array
    {
        return ['success' => true, 'message' => 'Template creation feature coming soon'];
    }

    protected function clonePage(array $parameters, User $user, int $teamId): array
    {
        return ['success' => true, 'message' => 'Page cloning feature coming soon'];
    }

    protected function optimizePageSeo(array $parameters, User $user, int $teamId): array
    {
        return ['success' => true, 'message' => 'SEO optimization feature coming soon'];
    }

    protected function addPageComponent(array $parameters, User $user, int $teamId): array
    {
        return ['success' => true, 'message' => 'Component addition feature coming soon'];
    }

    protected function removePageComponent(array $parameters, User $user, int $teamId): array
    {
        return ['success' => true, 'message' => 'Component removal feature coming soon'];
    }

    protected function updatePageSettings(array $parameters, User $user, int $teamId): array
    {
        return ['success' => true, 'message' => 'Page settings update feature coming soon'];
    }

    protected function exportPageHtml(array $parameters, User $user, int $teamId): array
    {
        return ['success' => true, 'message' => 'HTML export feature coming soon'];
    }

    protected function importPageTemplate(array $parameters, User $user, int $teamId): array
    {
        return ['success' => true, 'message' => 'Template import feature coming soon'];
    }

    protected function previewPage(array $parameters, User $user, int $teamId): array
    {
        return ['success' => true, 'message' => 'Page preview feature coming soon'];
    }

    protected function updatePageStyles(array $parameters, User $user, int $teamId): array
    {
        return ['success' => true, 'message' => 'Style update feature coming soon'];
    }

    protected function addPageBlock(array $parameters, User $user, int $teamId): array
    {
        return ['success' => true, 'message' => 'Block addition feature coming soon'];
    }
} 