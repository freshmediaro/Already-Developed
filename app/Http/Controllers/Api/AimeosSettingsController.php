<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;
use Rawilk\Settings\Support\Context;
use Rawilk\Settings\Facades\Settings;
use Exception;

/**
 * Aimeos Settings Controller - Manages Aimeos e-commerce configuration
 *
 * This controller handles all Aimeos e-commerce settings including website
 * configuration, product management, payment processing, shipping options,
 * customer management, and email templates within the multi-tenant system.
 *
 * Key features:
 * - E-commerce website configuration
 * - Product catalog management
 * - Payment gateway integration
 * - Shipping method configuration
 * - Customer management settings
 * - Email template customization
 * - Billing and invoicing settings
 * - Logo and branding management
 * - Settings validation and security
 * - Tenant-aware configuration isolation
 *
 * Supported operations:
 * - Get all Aimeos settings
 * - Update e-commerce configuration
 * - Upload and manage logos
 * - Reset settings to defaults
 * - Validate e-commerce settings
 * - Manage payment gateways
 * - Configure shipping methods
 * - Customize email templates
 *
 * Settings categories:
 * - website: Website configuration and branding
 * - products: Product catalog and inventory
 * - payments: Payment gateway settings
 * - shipping: Shipping method configuration
 * - customers: Customer management settings
 * - emails: Email template customization
 * - billing: Billing and invoicing settings
 *
 * The controller provides:
 * - RESTful API endpoints for e-commerce settings
 * - Comprehensive validation and error handling
 * - File upload and management
 * - Tenant isolation and context awareness
 * - Settings backup and restoration
 * - Security and access control
 *
 * @package App\Http\Controllers\Api
 * @since 1.0.0
 */
class AimeosSettingsController extends Controller
{
    /**
     * Get all Aimeos e-commerce settings
     *
     * This method retrieves all Aimeos settings for the authenticated user
     * and team, including website configuration, product settings, payment
     * gateways, shipping methods, and customer management settings.
     *
     * @param Request $request The HTTP request (may include team_id)
     * @return JsonResponse Response containing all Aimeos settings
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);

            // Set context for tenant-aware settings
            $this->setSettingsContext($user->id, $teamId);

            $settings = $this->getAllAimeosSettings();

            return response()->json([
                'success' => true,
                'data' => $settings,
                'context' => [
                    'user_id' => $user->id,
                    'team_id' => $teamId,
                    'tenant_id' => tenant('id'),
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get Aimeos settings', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'team_id' => $request->get('team_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve Aimeos settings'
            ], 500);
        }
    }

    /**
     * Update Aimeos e-commerce settings
     *
     * This method updates Aimeos settings including website configuration,
     * product management, payment gateways, shipping methods, and customer
     * settings. It provides comprehensive validation for all e-commerce
     * configuration data.
     *
     * Request parameters:
     * - settings: Array of Aimeos settings to update
     * - team_id: Team context for settings
     *
     * Settings structure:
     * - website: Website configuration and branding
     * - products: Product catalog settings
     * - payments: Payment gateway configuration
     * - shipping: Shipping method settings
     * - customers: Customer management configuration
     * - emails: Email template settings
     * - billing: Billing and invoicing configuration
     *
     * @param Request $request The HTTP request with Aimeos settings data
     * @return JsonResponse Response indicating success or failure
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);
            $settings = $request->get('settings', []);

            // Validate the settings input
            $validator = Validator::make($request->all(), [
                'settings' => 'required|array',
                'settings.website' => 'sometimes|array',
                'settings.products' => 'sometimes|array',
                'settings.payments' => 'sometimes|array',
                'settings.shipping' => 'sometimes|array',
                'settings.customers' => 'sometimes|array',
                'settings.emails' => 'sometimes|array',
                'settings.billing' => 'sometimes|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid settings data',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Set context for tenant-aware settings
            $this->setSettingsContext($user->id, $teamId);

            // Validate individual settings
            $validationErrors = $this->validateAimeosSettings($settings);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Settings validation failed',
                    'errors' => $validationErrors
                ], 422);
            }

            // Update settings with proper prefixing
            foreach ($settings as $category => $categorySettings) {
                foreach ($categorySettings as $key => $value) {
                    $settingKey = "aimeos.{$category}.{$key}";
                    Settings::set($settingKey, $value);
                }
            }

            Log::info('Aimeos settings updated', [
                'user_id' => $user->id,
                'team_id' => $teamId,
                'categories_updated' => array_keys($settings),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Aimeos settings updated successfully',
                'data' => $this->getAllAimeosSettings()
            ]);
        } catch (Exception $e) {
            Log::error('Failed to update Aimeos settings', [
                'user_id' => Auth::id(),
                'team_id' => $request->get('team_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update Aimeos settings'
            ], 500);
        }
    }

    /**
     * Upload store logo
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);

            $validator = Validator::make($request->all(), [
                'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // 2MB max
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid logo file',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('logo');
            if (!$file) {
                return response()->json([
                    'success' => false,
                    'error' => 'No logo file provided'
                ], 400);
            }

            // Generate filename
            $tenantId = tenant('id');
            $filename = 'store-logo-' . $tenantId . '.' . $file->getClientOriginalExtension();
            
            // Store file in tenant-specific directory
            $path = $file->storeAs("tenant/{$tenantId}/assets/logos", $filename, 'public');
            
            if (!$path) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to store logo file'
                ], 500);
            }

            // Generate public URL
            $url = Storage::url($path);

            // Set context and update logo URL setting
            $this->setSettingsContext($user->id, $teamId);
            Settings::set('aimeos.website.logoUrl', $url);

            Log::info('Store logo uploaded', [
                'user_id' => $user->id,
                'team_id' => $teamId,
                'tenant_id' => $tenantId,
                'filename' => $filename,
                'path' => $path,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logo uploaded successfully',
                'url' => $url,
                'path' => $path
            ]);
        } catch (Exception $e) {
            Log::error('Failed to upload store logo', [
                'user_id' => Auth::id(),
                'team_id' => $request->get('team_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to upload logo'
            ], 500);
        }
    }

    /**
     * Reset Aimeos settings to defaults
     */
    public function reset(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);

            // Set context for tenant-aware settings
            $this->setSettingsContext($user->id, $teamId);

            // Get default settings
            $defaults = $this->getDefaultAimeosSettings();

            // Update all settings with defaults
            foreach ($defaults as $category => $categorySettings) {
                foreach ($categorySettings as $key => $value) {
                    $settingKey = "aimeos.{$category}.{$key}";
                    Settings::set($settingKey, $value);
                }
            }

            Log::info('Aimeos settings reset to defaults', [
                'user_id' => $user->id,
                'team_id' => $teamId,
                'tenant_id' => tenant('id'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Settings reset to defaults successfully',
                'data' => $defaults
            ]);
        } catch (Exception $e) {
            Log::error('Failed to reset Aimeos settings', [
                'user_id' => Auth::id(),
                'team_id' => $request->get('team_id'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to reset settings'
            ], 500);
        }
    }

    /**
     * Get team ID from request
     */
    protected function getTeamIdFromRequest(Request $request): ?int
    {
        $teamId = $request->get('team_id');
        return $teamId ? (int) $teamId : null;
    }

    /**
     * Set settings context for tenant isolation
     */
    protected function setSettingsContext(int $userId, ?int $teamId): void
    {
        $context = [
            'user_id' => $userId,
            'tenant_id' => tenant('id'),
        ];

        if ($teamId) {
            $context['team_id'] = $teamId;
        }

        Settings::context($context);
    }

    /**
     * Get all Aimeos settings with defaults
     */
    protected function getAllAimeosSettings(): array
    {
        $defaults = $this->getDefaultAimeosSettings();
        $settings = [];

        foreach ($defaults as $category => $categoryDefaults) {
            $settings[$category] = [];
            foreach ($categoryDefaults as $key => $defaultValue) {
                $settingKey = "aimeos.{$category}.{$key}";
                $settings[$category][$key] = Settings::get($settingKey, $defaultValue);
            }
        }

        return $settings;
    }

    /**
     * Get default Aimeos settings
     */
    protected function getDefaultAimeosSettings(): array
    {
        return [
            'website' => [
                'storeName' => config('app.name', 'My Store'),
                'logoUrl' => '',
                'description' => '',
                'contactEmail' => config('mail.from.address', ''),
                'phone' => '',
                'address' => ''
            ],
            'products' => [
                'perPage' => 24,
                'defaultSort' => 'relevance',
                'enableReviews' => true,
                'enableWishlist' => true,
                'trackInventory' => true,
                'lowStockThreshold' => 10
            ],
            'payments' => [
                'enableCards' => true,
                'enablePaypal' => false,
                'enableBankTransfer' => false,
                'defaultCurrency' => config('cashier.currency', 'USD'),
                'taxCalculation' => 'none'
            ],
            'shipping' => [
                'freeShippingThreshold' => 100.00,
                'standardCost' => 9.99,
                'expressCost' => 19.99,
                'processingTime' => '2',
                'enablePickup' => false
            ],
            'customers' => [
                'requireAccount' => false,
                'enableGuestCheckout' => true,
                'requireEmailVerification' => true,
                'showCookieConsent' => true,
                'dataRetentionPeriod' => '365'
            ],
            'emails' => [
                'sendOrderConfirmation' => true,
                'sendShippingNotification' => true,
                'sendDeliveryConfirmation' => false,
                'enableNewsletter' => true,
                'enablePromotional' => false,
                'sendAbandonedCartReminders' => true
            ],
            'billing' => [
                'invoicePrefix' => 'INV-',
                'autoGenerateInvoices' => true,
                'includeLogoOnInvoices' => true,
                'termsOfServiceUrl' => '',
                'privacyPolicyUrl' => '',
                'returnPolicyUrl' => ''
            ]
        ];
    }

    /**
     * Validate Aimeos settings
     */
    protected function validateAimeosSettings(array $settings): array
    {
        $errors = [];

        // Website validation
        if (isset($settings['website'])) {
            $website = $settings['website'];
            
            if (isset($website['contactEmail']) && !filter_var($website['contactEmail'], FILTER_VALIDATE_EMAIL)) {
                $errors['website.contactEmail'] = 'Invalid email format';
            }
            
            if (isset($website['storeName']) && strlen($website['storeName']) > 255) {
                $errors['website.storeName'] = 'Store name must be less than 255 characters';
            }
        }

        // Products validation
        if (isset($settings['products'])) {
            $products = $settings['products'];
            
            if (isset($products['perPage']) && (!is_numeric($products['perPage']) || $products['perPage'] < 1 || $products['perPage'] > 100)) {
                $errors['products.perPage'] = 'Products per page must be between 1 and 100';
            }
            
            if (isset($products['lowStockThreshold']) && (!is_numeric($products['lowStockThreshold']) || $products['lowStockThreshold'] < 0)) {
                $errors['products.lowStockThreshold'] = 'Low stock threshold must be a positive number';
            }
        }

        // Shipping validation
        if (isset($settings['shipping'])) {
            $shipping = $settings['shipping'];
            
            foreach (['freeShippingThreshold', 'standardCost', 'expressCost'] as $field) {
                if (isset($shipping[$field]) && (!is_numeric($shipping[$field]) || $shipping[$field] < 0)) {
                    $errors["shipping.{$field}"] = ucfirst(str_replace('_', ' ', $field)) . ' must be a positive number';
                }
            }
        }

        // Billing validation
        if (isset($settings['billing'])) {
            $billing = $settings['billing'];
            
            foreach (['termsOfServiceUrl', 'privacyPolicyUrl', 'returnPolicyUrl'] as $field) {
                if (isset($billing[$field]) && !empty($billing[$field]) && !filter_var($billing[$field], FILTER_VALIDATE_URL)) {
                    $errors["billing.{$field}"] = ucfirst(str_replace('_', ' ', $field)) . ' must be a valid URL';
                }
            }
        }

        return $errors;
    }
} 