<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookProcessor;
use Spatie\WebhookClient\Exceptions\InvalidConfig;
use Spatie\WebhookClient\Exceptions\InvalidWebhookSignature;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Controller - Manages incoming webhook processing and validation
 *
 * This controller handles incoming webhooks from various external services
 * including payment providers, notification services, and third-party integrations.
 * It provides comprehensive webhook processing with signature validation,
 * tenant-specific handling, and error management.
 *
 * Key features:
 * - Multi-provider webhook processing
 * - Signature validation and security
 * - Tenant-specific webhook handling
 * - Comprehensive error handling
 * - Webhook health monitoring
 * - Configuration-based webhook routing
 * - Logging and monitoring
 * - Security and access control
 * - Webhook call processing
 * - Response management
 *
 * Supported webhook providers:
 * - Stripe payment webhooks
 * - PayPal payment notifications
 * - Notification service webhooks
 * - Third-party integration webhooks
 * - Custom service webhooks
 * - Tenant-specific webhooks
 *
 * Webhook processing includes:
 * - Signature validation and verification
 * - Configuration-based routing
 * - Tenant context identification
 * - Event type processing
 * - Error handling and logging
 * - Response generation
 * - Health monitoring
 *
 * The controller provides:
 * - RESTful webhook endpoints
 * - Comprehensive security validation
 * - Tenant-aware webhook processing
 * - Error handling and logging
 * - Health monitoring endpoints
 * - Configuration management
 * - Multi-provider support
 *
 * @package App\Http\Controllers
 * @since 1.0.0
 */
class WebhookController extends Controller
{
    /**
     * Handle incoming webhooks using Spatie webhook client
     *
     * This method processes incoming webhooks from various external services,
     * including signature validation, configuration-based routing, and
     * comprehensive error handling for secure webhook processing.
     *
     * The processing includes:
     * - Configuration key validation
     * - Webhook signature verification
     * - Event type identification
     * - Webhook call processing
     * - Error handling and logging
     * - Response generation
     *
     * @param string|null $configKey The webhook configuration key
     * @return JsonResponse Response indicating success or failure
     */
    public function handle(string $configKey = null): JsonResponse
    {
        $configKey = $configKey ?? request()->route('configKey');
        
        if (!$configKey) {
            Log::warning('Webhook received without config key', [
                'url' => request()->url(),
                'method' => request()->method(),
            ]);
            
            return response()->json(['error' => 'Config key required'], 400);
        }

        try {
            // Get webhook configuration
            $webhookConfig = $this->getWebhookConfig($configKey);
            
            if (!$webhookConfig) {
                Log::warning('Invalid webhook config key', [
                    'config_key' => $configKey,
                    'available_configs' => array_column(config('webhook-client.configs'), 'name'),
                ]);
                
                return response()->json(['error' => 'Invalid webhook configuration'], 404);
            }

            // Process the webhook
            $webhookCall = (new WebhookProcessor(request(), $webhookConfig))->process();

            Log::info('Webhook processed successfully', [
                'config_key' => $configKey,
                'webhook_call_id' => $webhookCall?->id,
                'event_type' => request()->input('type'),
            ]);

            return response()->json(['success' => true], 200);

        } catch (InvalidWebhookSignature $e) {
            Log::warning('Invalid webhook signature', [
                'config_key' => $configKey,
                'error' => $e->getMessage(),
                'headers' => request()->headers->all(),
            ]);
            
            return response()->json(['error' => 'Invalid signature'], 401);

        } catch (InvalidConfig $e) {
            Log::error('Invalid webhook configuration', [
                'config_key' => $configKey,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json(['error' => 'Configuration error'], 500);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'config_key' => $configKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Get webhook configuration by configuration key
     *
     * This method retrieves the webhook configuration for a specific
     * configuration key, enabling dynamic webhook routing and processing.
     *
     * @param string $configKey The webhook configuration key
     * @return WebhookConfig|null The webhook configuration or null if not found
     */
    protected function getWebhookConfig(string $configKey): ?WebhookConfig
    {
        $configs = config('webhook-client.configs', []);
        
        foreach ($configs as $config) {
            if ($config['name'] === $configKey) {
                return new WebhookConfig($config);
            }
        }
        
        return null;
    }

    /**
     * Handle tenant-specific webhook processing
     *
     * This method processes webhooks for specific tenants, providing
     * tenant-aware webhook handling with proper isolation and security.
     *
     * @param string $tenantId The tenant identifier
     * @param string $configKey The webhook configuration key (defaults to 'stripe-tenant')
     * @return JsonResponse Response indicating success or failure
     */
    public function handleTenant(string $tenantId, string $configKey = 'stripe-tenant'): JsonResponse
    {
        try {
            // Initialize tenant context
            $tenant = \App\Models\Tenant::find($tenantId);
            if (!$tenant) {
                Log::warning('Tenant not found for webhook', [
                    'tenant_id' => $tenantId,
                    'config_key' => $configKey,
                ]);
                
                return response()->json(['error' => 'Tenant not found'], 404);
            }

            // Initialize tenancy
            tenancy()->initialize($tenant);

            Log::info('Processing tenant webhook', [
                'tenant_id' => $tenantId,
                'config_key' => $configKey,
                'event_type' => request()->input('type'),
            ]);

            // Get tenant-specific webhook configuration
            $webhookConfig = $this->getTenantWebhookConfig($tenant, $configKey);
            
            if (!$webhookConfig) {
                // Fallback to default config
                $webhookConfig = $this->getWebhookConfig($configKey);
            }

            if (!$webhookConfig) {
                return response()->json(['error' => 'No webhook configuration available'], 404);
            }

            // Process the webhook with tenant context
            $webhookCall = (new WebhookProcessor(request(), $webhookConfig))->process();

            Log::info('Tenant webhook processed successfully', [
                'tenant_id' => $tenantId,
                'config_key' => $configKey,
                'webhook_call_id' => $webhookCall?->id,
            ]);

            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            Log::error('Tenant webhook processing failed', [
                'tenant_id' => $tenantId,
                'config_key' => $configKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    /**
     * Get tenant-specific webhook configuration
     */
    protected function getTenantWebhookConfig(\App\Models\Tenant $tenant, string $configKey): ?WebhookConfig
    {
        // Check if tenant has custom webhook settings
        $tenantConfig = \App\Models\Wallet\PaymentProviderConfig::where('tenant_id', $tenant->id)
            ->where('provider_name', 'stripe-cashier')
            ->where('is_enabled', true)
            ->first();

        if (!$tenantConfig) {
            return null;
        }

        // Get base configuration
        $baseConfig = $this->getWebhookConfig($configKey);
        if (!$baseConfig) {
            return null;
        }

        // Override with tenant-specific settings
        $config = $baseConfig->toArray();
        
        // Use tenant's webhook secret if available
        if ($tenantConfig->getDecryptedWebhookSecret()) {
            $config['signing_secret'] = $tenantConfig->getDecryptedWebhookSecret();
        }

        return new WebhookConfig($config);
    }

    /**
     * Health check endpoint for webhook monitoring
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'webhook_configs' => array_column(config('webhook-client.configs', []), 'name'),
        ]);
    }
} 