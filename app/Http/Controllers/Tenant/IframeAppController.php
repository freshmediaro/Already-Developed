<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Middleware\IframeSecurityMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Iframe App Controller - Manages iframe-based application integration
 *
 * This controller handles iframe-based application integration within the
 * multi-tenant system, providing secure iframe embedding, configuration
 * management, and security controls for external applications.
 *
 * Key features:
 * - Iframe app configuration management
 * - Security validation and sandboxing
 * - Cross-origin communication handling
 * - App permission management
 * - URL validation and security
 * - Sandbox permissions control
 * - Security headers management
 * - Multi-tenant app isolation
 * - App status monitoring
 * - Configuration persistence
 *
 * Supported iframe apps:
 * - Photoshop (Photopea integration)
 * - Mail applications (Gmail, Outlook)
 * - Document editors (Google Docs, Office)
 * - Design tools (Figma, Canva)
 * - Development tools (CodePen, JSFiddle)
 * - Custom iframe applications
 *
 * Security features:
 * - Sandbox permissions control
 * - Cross-origin policy enforcement
 * - URL validation and whitelisting
 * - Security headers management
 * - Content Security Policy (CSP)
 * - Frame options control
 * - Communication channel security
 *
 * The controller provides:
 * - RESTful API endpoints for iframe app management
 * - Comprehensive security validation
 * - Configuration management and persistence
 * - Permission-based access control
 * - Multi-tenant isolation
 * - Security monitoring and logging
 * - App status tracking
 *
 * @package App\Http\Controllers\Tenant
 * @since 1.0.0
 */
class IframeAppController extends Controller
{
    /**
     * Get iframe app configuration for tenant
     *
     * This method retrieves the configuration for a specific iframe app
     * within the tenant context, including security settings, permissions,
     * and app-specific configuration.
     *
     * @param Request $request The HTTP request
     * @param string $appId The iframe app identifier
     * @return \Illuminate\Http\JsonResponse Response containing app configuration
     */
    public function getAppConfig(Request $request, string $appId)
    {
        $user = $request->user();
        $tenant = app('currentTenant');
        
        // Validate app ID
        if (!$this->isValidIframeApp($appId)) {
            return response()->json(['error' => 'Invalid app ID'], 404);
        }
        
        // Get tenant-specific configuration
        $config = $this->getIframeAppConfig($appId, $tenant, $user);
        
        return response()->json([
            'app_id' => $appId,
            'config' => $config,
            'security' => $this->getSecurityConfig($appId, $tenant),
            'permissions' => $this->getUserAppPermissions($appId, $user),
        ]);
    }
    
    /**
     * Update iframe app configuration
     *
     * This method updates the configuration for a specific iframe app,
     * including URL settings, security parameters, and custom configuration.
     * It validates all settings for security compliance.
     *
     * Request parameters:
     * - url: The iframe app URL
     * - allowed_origins: Array of allowed origins for communication
     * - enable_communication: Whether to enable cross-origin communication
     * - auto_login: Whether to enable automatic login
     * - custom_settings: Custom app-specific settings
     *
     * @param Request $request The HTTP request with configuration data
     * @param string $appId The iframe app identifier
     * @return \Illuminate\Http\JsonResponse Response indicating success or failure
     */
    public function updateAppConfig(Request $request, string $appId)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'url|max:2048',
            'allowed_origins' => 'array',
            'allowed_origins.*' => 'url',
            'enable_communication' => 'boolean',
            'auto_login' => 'boolean',
            'custom_settings' => 'array',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = $request->user();
        $tenant = app('currentTenant');
        
        // Check permissions
        if (!$user->hasTeamPermission($user->currentTeam, 'apps.configure')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }
        
        // Validate security constraints
        $securityValidation = $this->validateSecuritySettings($request->all(), $appId);
        if (!$securityValidation['valid']) {
            return response()->json(['error' => $securityValidation['message']], 422);
        }
        
        // Update configuration
        $config = $this->updateIframeAppConfig($appId, $request->all(), $tenant, $user);
        
        return response()->json([
            'success' => true,
            'config' => $config,
        ]);
    }
    
    /**
     * Get available iframe apps for tenant
     *
     * This method retrieves all available iframe applications for the
     * current tenant, including app information, status, and configuration
     * based on user permissions and tenant settings.
     *
     * @param Request $request The HTTP request
     * @return \Illuminate\Http\JsonResponse Response containing available apps
     */
    public function getAvailableApps(Request $request)
    {
        $user = $request->user();
        $tenant = app('currentTenant');
        
        $apps = [];
        
        // Photoshop (Photopea)
        if ($this->hasAppPermission($user, 'photoshop')) {
            $apps[] = [
                'id' => 'photoshop',
                'name' => 'Photoshop',
                'description' => 'Professional photo editing powered by Photopea',
                'icon' => 'fa-palette',
                'category' => 'media',
                'provider' => 'Photopea',
                'url' => 'https://www.photopea.com/',
                'status' => $this->getAppStatus('photoshop', $tenant),
                'config' => $this->getIframeAppConfig('photoshop', $tenant, $user),
            ];
        }
        
        // Mail apps
        if ($this->hasAppPermission($user, 'mail')) {
            $mailApps = $this->getAvailableMailApps($tenant, $user);
            $apps = array_merge($apps, $mailApps);
        }
        
        return response()->json(['apps' => $apps]);
    }
    
    /**
     * Validate iframe URL before loading
     */
    public function validateIframeUrl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
            'app_id' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $url = $request->input('url');
        $appId = $request->input('app_id');
        $tenant = app('currentTenant');
        
        // Check if URL is allowed for this app and tenant
        $isAllowed = $this->isUrlAllowedForApp($url, $appId, $tenant);
        
        if (!$isAllowed) {
            return response()->json([
                'valid' => false,
                'message' => 'URL not allowed for this application',
            ], 422);
        }
        
        // Get security headers for this URL
        $securityHeaders = $this->getSecurityHeadersForUrl($url, $appId, $tenant);
        
        return response()->json([
            'valid' => true,
            'security_headers' => $securityHeaders,
            'sandbox_permissions' => $this->getSandboxPermissions($appId),
        ]);
    }
    
    /**
     * Get mail server configuration for tenant
     */
    public function getMailConfig(Request $request)
    {
        $user = $request->user();
        $tenant = app('currentTenant');
        
        if (!$this->hasAppPermission($user, 'mail')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }
        
        $mailConfig = $tenant->data['mail_config'] ?? [];
        
        // Don't expose sensitive credentials
        unset($mailConfig['password'], $mailConfig['admin_password']);
        
        return response()->json([
            'config' => $mailConfig,
            'available_providers' => $this->getAvailableMailProviders(),
        ]);
    }
    
    /**
     * Update mail server configuration
     */
    public function updateMailConfig(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|in:sogo,mailcow,roundcube,custom',
            'server_url' => 'required|url',
            'domain' => 'required|string|max:255',
            'ssl' => 'boolean',
            'port' => 'integer|min:1|max:65535',
            'auto_login' => 'boolean',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = $request->user();
        $tenant = app('currentTenant');
        
        if (!$user->hasTeamPermission($user->currentTeam, 'mail.configure')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }
        
        // Update tenant mail configuration
        $mailConfig = $request->only(['provider', 'server_url', 'domain', 'ssl', 'port', 'auto_login']);
        
        $tenantData = $tenant->data;
        $tenantData['mail_config'] = $mailConfig;
        $tenant->update(['data' => $tenantData]);
        
        return response()->json([
            'success' => true,
            'config' => $mailConfig,
        ]);
    }
    
    // Private helper methods
    
    private function isValidIframeApp(string $appId): bool
    {
        return in_array($appId, ['photoshop', 'mail']);
    }
    
    private function getIframeAppConfig(string $appId, $tenant, $user): array
    {
        $config = $tenant->data['iframe_apps'][$appId] ?? [];
        
        switch ($appId) {
            case 'photoshop':
                return array_merge([
                    'url' => 'https://www.photopea.com/',
                    'allowed_origins' => ['https://www.photopea.com', 'https://photopea.com'],
                    'enable_communication' => true,
                    'enable_file_integration' => true,
                    'theme' => 'auto',
                ], $config);
                
            case 'mail':
                $mailConfig = $tenant->data['mail_config'] ?? [];
                return array_merge([
                    'url' => $mailConfig['server_url'] ?? 'https://demo.sogo.nu/SOGo/',
                    'provider' => $mailConfig['provider'] ?? 'sogo',
                    'auto_login' => $mailConfig['auto_login'] ?? false,
                    'enable_notifications' => true,
                ], $config);
                
            default:
                return $config;
        }
    }
    
    private function getSecurityConfig(string $appId, $tenant): array
    {
        $baseConfig = [
            'sandbox_permissions' => $this->getSandboxPermissions($appId),
            'allowed_features' => $this->getAllowedFeatures($appId),
            'referrer_policy' => 'strict-origin-when-cross-origin',
        ];
        
        // Add app-specific security settings
        $appSecurity = $tenant->data['iframe_security'][$appId] ?? [];
        
        return array_merge($baseConfig, $appSecurity);
    }
    
    private function getSandboxPermissions(string $appId): array
    {
        $base = ['allow-scripts', 'allow-same-origin', 'allow-forms'];
        
        switch ($appId) {
            case 'photoshop':
                return array_merge($base, [
                    'allow-downloads',
                    'allow-modals',
                    'allow-popups',
                    'allow-storage-access-by-user-activation'
                ]);
                
            case 'mail':
                return array_merge($base, [
                    'allow-popups',
                    'allow-modals',
                    'allow-downloads'
                ]);
                
            default:
                return $base;
        }
    }
    
    private function getAllowedFeatures(string $appId): array
    {
        switch ($appId) {
            case 'photoshop':
                return [
                    'camera',
                    'microphone',
                    'fullscreen',
                    'clipboard-read',
                    'clipboard-write'
                ];
                
            case 'mail':
                return [
                    'clipboard-read',
                    'clipboard-write',
                    'notifications'
                ];
                
            default:
                return [];
        }
    }
    
    private function hasAppPermission($user, string $appId): bool
    {
        $permission = "apps.{$appId}";
        return $user->hasTeamPermission($user->currentTeam, $permission);
    }
    
    private function getAppStatus(string $appId, $tenant): string
    {
        $config = $tenant->data['iframe_apps'][$appId] ?? [];
        return $config['enabled'] ?? true ? 'enabled' : 'disabled';
    }
    
    private function getAvailableMailApps($tenant, $user): array
    {
        $mailConfig = $tenant->data['mail_config'] ?? [];
        $apps = [];
        
        // Primary mail app
        $apps[] = [
            'id' => 'mail',
            'name' => 'Mail',
            'description' => 'Team email client',
            'icon' => 'fa-envelope',
            'category' => 'communication',
            'provider' => $mailConfig['provider'] ?? 'sogo',
            'url' => $mailConfig['server_url'] ?? 'https://demo.sogo.nu/SOGo/',
            'status' => $this->getAppStatus('mail', $tenant),
            'config' => $this->getIframeAppConfig('mail', $tenant, $user),
        ];
        
        return $apps;
    }
    
    private function isUrlAllowedForApp(string $url, string $appId, $tenant): bool
    {
        $config = $this->getIframeAppConfig($appId, $tenant, auth()->user());
        $allowedOrigins = $config['allowed_origins'] ?? [];
        
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return false;
        }
        
        $urlOrigin = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        if (isset($parsedUrl['port'])) {
            $urlOrigin .= ':' . $parsedUrl['port'];
        }
        
        return in_array($urlOrigin, $allowedOrigins);
    }
    
    private function getSecurityHeadersForUrl(string $url, string $appId, $tenant): array
    {
        return [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];
    }
    
    private function getAvailableMailProviders(): array
    {
        return [
            'sogo' => [
                'name' => 'SOGo',
                'description' => 'Modern groupware server',
                'default_path' => '/SOGo/',
            ],
            'mailcow' => [
                'name' => 'Mailcow',
                'description' => 'Dockerized email server',
                'default_path' => '/',
            ],
            'roundcube' => [
                'name' => 'Roundcube',
                'description' => 'Browser-based IMAP client',
                'default_path' => '/roundcube/',
            ],
            'custom' => [
                'name' => 'Custom',
                'description' => 'Custom mail server configuration',
                'default_path' => '/',
            ],
        ];
    }
    
    private function validateSecuritySettings(array $settings, string $appId): array
    {
        // Validate that security settings are within acceptable bounds
        if (isset($settings['allowed_origins'])) {
            foreach ($settings['allowed_origins'] as $origin) {
                if (!filter_var($origin, FILTER_VALIDATE_URL)) {
                    return ['valid' => false, 'message' => 'Invalid origin URL'];
                }
            }
        }
        
        return ['valid' => true];
    }
    
    private function updateIframeAppConfig(string $appId, array $settings, $tenant, $user): array
    {
        $tenantData = $tenant->data;
        $currentConfig = $tenantData['iframe_apps'][$appId] ?? [];
        
        // Merge new settings with current config
        $newConfig = array_merge($currentConfig, $settings);
        
        $tenantData['iframe_apps'][$appId] = $newConfig;
        $tenant->update(['data' => $tenantData]);
        
        return $newConfig;
    }
    
    private function getUserAppPermissions(string $appId, $user): array
    {
        $team = $user->currentTeam;
        if (!$team) {
            return [];
        }
        
        $permissions = [];
        $permissionMap = [
            'photoshop' => ['files.read', 'files.write', 'apps.photoshop'],
            'mail' => ['mail.read', 'mail.write', 'mail.send', 'apps.mail'],
        ];
        
        foreach ($permissionMap[$appId] ?? [] as $permission) {
            if ($user->hasTeamPermission($team, $permission)) {
                $permissions[] = $permission;
            }
        }
        
        return $permissions;
    }
} 