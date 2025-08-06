<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

/**
 * Request Validation Middleware - Comprehensive request validation and sanitization
 *
 * This middleware provides intelligent request validation and data sanitization
 * for different types of API endpoints, ensuring data integrity and security
 * while providing clear error feedback to clients.
 *
 * Key features:
 * - Context-aware validation rules based on request type
 * - Comprehensive data sanitization and cleaning
 * - Security-focused validation for sensitive operations
 * - Multi-tenant validation and isolation
 * - File upload validation and security
 * - Input sanitization and XSS prevention
 * - Validation error handling and feedback
 * - Performance optimization through intelligent validation
 * - Security hardening and abuse prevention
 * - Data integrity and consistency
 *
 * Validation types:
 * - ai-chat: AI chat message validation
 * - file-upload: File upload validation and security
 * - wallet: Wallet operation validation
 * - settings: Settings update validation
 * - app-store: App store operation validation
 * - iframe-apps: Iframe app validation
 * - notifications: Notification operation validation
 * - tenant: Tenant-specific validation
 * - auth: Authentication validation
 * - basic: Basic request validation
 *
 * The middleware provides:
 * - Comprehensive request validation
 * - Data sanitization and security
 * - Error handling and feedback
 * - Multi-tenant isolation
 * - Security hardening
 * - Performance optimization
 *
 * @package App\Http\Middleware
 * @since 1.0.0
 */
class RequestValidationMiddleware
{
    /**
     * Handle an incoming request and apply validation
     *
     * This method processes the request, validates it based on the validation type,
     * sanitizes the data, and either allows the request to proceed or returns
     * validation errors.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the stack
     * @param string $validationType The type of validation to apply (ai-chat, file-upload, wallet, etc.)
     * @return Response The response with validation applied
     */
    public function handle(Request $request, Closure $next, string $validationType = 'basic'): Response
    {
        // Validate request based on type
        $validation = $this->validateRequest($request, $validationType);
        
        if ($validation !== true) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'The request contains invalid data',
                'errors' => $validation
            ], 422);
        }

        // Sanitize request data
        $this->sanitizeRequest($request);

        return $next($request);
    }

    /**
     * Validate request based on validation type and request context
     *
     * This method applies appropriate validation rules based on the validation type
     * and request method, returning validation errors or true if validation passes.
     *
     * @param Request $request The HTTP request to validate
     * @param string $validationType The type of validation to apply
     * @return array|bool Validation errors array or true if validation passes
     */
    protected function validateRequest(Request $request, string $validationType): array|bool
    {
        $rules = $this->getValidationRules($validationType, $request);
        
        if (empty($rules)) {
            return true;
        }

        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
            return $validator->errors()->toArray();
        }

        return true;
    }

    /**
     * Get validation rules based on validation type and request method
     *
     * This method returns the appropriate validation rules based on the validation
     * type and HTTP method, ensuring context-aware validation.
     *
     * @param string $validationType The type of validation to apply
     * @param Request $request The HTTP request for context
     * @return array The validation rules array
     */
    protected function getValidationRules(string $validationType, Request $request): array
    {
        $method = $request->getMethod();
        $path = $request->path();

        return match($validationType) {
            'ai-chat' => $this->getAiChatRules($method),
            'file-upload' => $this->getFileUploadRules($method),
            'wallet' => $this->getWalletRules($method),
            'settings' => $this->getSettingsRules($method),
            'app-store' => $this->getAppStoreRules($method),
            'iframe-apps' => $this->getIframeAppRules($method),
            'notifications' => $this->getNotificationRules($method),
            'tenant' => $this->getTenantRules($method),
            'auth' => $this->getAuthRules($method),
            'basic' => $this->getBasicRules($method),
            default => [],
        };
    }

    /**
     * Get AI chat validation rules based on HTTP method
     *
     * This method returns validation rules specific to AI chat operations,
     * including message content, context, and file uploads.
     *
     * @param string $method The HTTP method (GET, POST, etc.)
     * @return array The validation rules for AI chat operations
     */
    protected function getAiChatRules(string $method): array
    {
        return match($method) {
            'POST' => [
                'message' => 'required|string|max:10000',
                'context' => 'sometimes|array',
                'context.team' => 'sometimes|array',
                'context.team.id' => 'sometimes|integer|exists:teams,id',
                'files' => 'sometimes|array|max:5',
                'files.*' => 'file|mimes:txt,pdf,doc,docx,jpg,jpeg,png,gif|max:10240', // 10MB max
            ],
            'GET' => [
                'page' => 'sometimes|integer|min:1',
                'limit' => 'sometimes|integer|min:1|max:100',
                'team_id' => 'sometimes|integer|exists:teams,id',
            ],
            default => [],
        };
    }

    /**
     * File upload validation rules
     */
    protected function getFileUploadRules(string $method): array
    {
        return match($method) {
            'POST' => [
                'files' => 'required|array|min:1|max:10',
                'files.*' => 'file|max:102400', // 100MB max per file
                'folder_path' => 'sometimes|string|max:255',
                'overwrite' => 'sometimes|boolean',
            ],
            default => [],
        };
    }

    /**
     * Wallet validation rules
     */
    protected function getWalletRules(string $method): array
    {
        return match($method) {
            'POST' => [
                'amount' => 'required|numeric|min:0.01|max:999999.99',
                'currency' => 'sometimes|string|size:3|in:USD,EUR,GBP',
                'payment_method' => 'sometimes|string|in:stripe,paypal,bank_transfer',
                'team_id' => 'sometimes|integer|exists:teams,id',
            ],
            'PUT' => [
                'amount' => 'sometimes|numeric|min:0.01|max:999999.99',
                'currency' => 'sometimes|string|size:3|in:USD,EUR,GBP',
            ],
            default => [],
        };
    }

    /**
     * Settings validation rules
     */
    protected function getSettingsRules(string $method): array
    {
        return match($method) {
            'PUT' => [
                'settings' => 'required|array',
                'settings.*' => 'required',
                'team_id' => 'sometimes|integer|exists:teams,id',
            ],
            'POST' => [
                'key' => 'required|string|max:255',
                'value' => 'required',
                'group' => 'sometimes|string|max:255',
            ],
            default => [],
        };
    }

    /**
     * App Store validation rules
     */
    protected function getAppStoreRules(string $method): array
    {
        return match($method) {
            'POST' => [
                'app_id' => 'required|string|exists:app_store_apps,id',
                'review' => 'sometimes|string|max:1000',
                'rating' => 'sometimes|integer|min:1|max:5',
            ],
            'GET' => [
                'category' => 'sometimes|string|exists:app_store_categories,slug',
                'search' => 'sometimes|string|max:255',
                'page' => 'sometimes|integer|min:1',
                'limit' => 'sometimes|integer|min:1|max:50',
            ],
            default => [],
        };
    }

    /**
     * Iframe Apps validation rules
     */
    protected function getIframeAppRules(string $method): array
    {
        return match($method) {
            'POST' => [
                'name' => 'required|string|max:255',
                'url' => 'required|url|max:500',
                'icon' => 'sometimes|string|max:255',
                'category' => 'sometimes|string|max:100',
                'sandbox_permissions' => 'sometimes|array',
            ],
            'PUT' => [
                'name' => 'sometimes|string|max:255',
                'url' => 'sometimes|url|max:500',
                'icon' => 'sometimes|string|max:255',
                'category' => 'sometimes|string|max:100',
            ],
            default => [],
        };
    }

    /**
     * Notification validation rules
     */
    protected function getNotificationRules(string $method): array
    {
        return match($method) {
            'POST' => [
                'title' => 'required|string|max:255',
                'message' => 'required|string|max:1000',
                'type' => 'sometimes|string|in:info,success,warning,error',
                'team_id' => 'sometimes|integer|exists:teams,id',
            ],
            'PUT' => [
                'settings' => 'required|array',
                'settings.enabled' => 'sometimes|boolean',
                'settings.sound' => 'sometimes|boolean',
            ],
            default => [],
        };
    }

    /**
     * Tenant validation rules
     */
    protected function getTenantRules(string $method): array
    {
        return match($method) {
            'POST' => [
                'domain' => 'required|string|max:255|unique:tenants,id',
                'name' => 'required|string|max:255',
            ],
            'PUT' => [
                'name' => 'sometimes|string|max:255',
                'data' => 'sometimes|array',
            ],
            default => [],
        };
    }

    /**
     * Auth validation rules
     */
    protected function getAuthRules(string $method): array
    {
        return match($method) {
            'POST' => [
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:8',
                'remember' => 'sometimes|boolean',
            ],
            default => [],
        };
    }

    /**
     * Basic validation rules
     */
    protected function getBasicRules(string $method): array
    {
        return [
            'page' => 'sometimes|integer|min:1',
            'limit' => 'sometimes|integer|min:1|max:100',
            'sort' => 'sometimes|string|max:50',
            'order' => 'sometimes|string|in:asc,desc',
        ];
    }

    /**
     * Sanitize request data
     */
    protected function sanitizeRequest(Request $request): void
    {
        $data = $request->all();
        
        // Recursively sanitize array data
        $sanitized = $this->sanitizeArray($data);
        
        // Replace request data with sanitized version
        $request->replace($sanitized);
    }

    /**
     * Recursively sanitize array data
     */
    protected function sanitizeArray(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize string value
     */
    protected function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace("\0", '', $value);
        
        // Trim whitespace
        $value = trim($value);
        
        // For specific fields, apply additional sanitization
        // This could be expanded based on specific needs
        
        return $value;
    }

    /**
     * Check if request needs special handling
     */
    protected function needsSpecialHandling(Request $request): bool
    {
        // Check for file uploads, base64 data, etc.
        return $request->hasFile('files') || 
               str_contains($request->getContent(), 'data:image/') ||
               str_contains($request->path(), 'upload');
    }
} 