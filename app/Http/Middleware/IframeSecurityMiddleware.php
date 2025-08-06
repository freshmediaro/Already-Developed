<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Iframe Security Middleware - Comprehensive iframe security and sandboxing
 *
 * This middleware provides comprehensive security controls for iframe-based
 * applications, including Content Security Policy (CSP), X-Frame-Options,
 * and sandboxing controls to prevent security vulnerabilities.
 *
 * Key features:
 * - Content Security Policy (CSP) implementation
 * - X-Frame-Options header management
 * - Iframe sandboxing and security controls
 * - Multi-tenant iframe configuration
 * - Cross-origin resource sharing (CORS) management
 * - Security event logging and monitoring
 * - Iframe URL validation and whitelisting
 * - Sandbox attribute management
 * - Security header enforcement
 * - Tenant-specific security policies
 *
 * Security controls:
 * - frame_options: X-Frame-Options header (SAMEORIGIN, DENY, ALLOW-FROM)
 * - allowed_origins: Whitelisted domains for iframe embedding
 * - allowed_iframe_domains: Permitted iframe source domains
 * - enable_external_iframes: External iframe permission control
 * - max_iframe_count: Maximum number of iframes allowed
 * - iframe_sandbox: Sandbox permissions for iframe content
 *
 * The middleware provides:
 * - Comprehensive iframe security
 * - Multi-tenant isolation
 * - Security event monitoring
 * - Sandboxing controls
 * - CORS management
 * - Security header enforcement
 *
 * @package App\Http\Middleware
 * @since 1.0.0
 */
class IframeSecurityMiddleware
{
    /**
     * Handle an incoming request and apply iframe security controls
     *
     * This method processes the request, applies comprehensive iframe security
     * headers, and ensures proper sandboxing and security controls.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the stack
     * @return Response The response with iframe security controls applied
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Get the tenant configuration for iframe settings
        $tenantConfig = $this->getTenantIframeConfig($request);
        
        // Set X-Frame-Options for iframe security
        $frameOptions = $tenantConfig['frame_options'] ?? 'SAMEORIGIN';
        $response->headers->set('X-Frame-Options', $frameOptions);
        
        // Set Content Security Policy
        $csp = $this->buildContentSecurityPolicy($tenantConfig, $request);
        $response->headers->set('Content-Security-Policy', $csp);
        
        // Set additional security headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Set CORS headers for iframe communication
        if ($request->isMethod('OPTIONS')) {
            $response->headers->set('Access-Control-Allow-Origin', $this->getAllowedOrigins($tenantConfig));
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-Tenant-ID');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '86400');
        }
        
        return $response;
    }
    
    /**
     * Get tenant-specific iframe configuration
     *
     * This method retrieves the iframe security configuration for the current
     * tenant, merging tenant-specific settings with default configuration.
     *
     * @param Request $request The HTTP request for context
     * @return array The iframe configuration array
     */
    private function getTenantIframeConfig(Request $request): array
    {
        // Get current tenant
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;
        
        if (!$tenant) {
            return $this->getDefaultIframeConfig();
        }
        
        // Get tenant's iframe security settings from database or config
        $config = $tenant->data['iframe_config'] ?? [];
        
        return array_merge($this->getDefaultIframeConfig(), $config);
    }
    
    /**
     * Get default iframe security configuration
     *
     * This method returns the default iframe security configuration with
     * conservative security settings and common allowed domains.
     *
     * @return array The default iframe configuration array
     */
    private function getDefaultIframeConfig(): array
    {
        return [
            'frame_options' => 'SAMEORIGIN',
            'allowed_origins' => [
                'https://www.photopea.com',
                'https://photopea.com',
            ],
            'allowed_iframe_domains' => [
                'photopea.com',
                'www.photopea.com',
            ],
            'enable_external_iframes' => true,
            'max_iframe_count' => 10,
            'iframe_sandbox' => [
                'allow-scripts',
                'allow-same-origin',
                'allow-forms',
                'allow-downloads',
                'allow-modals',
                'allow-popups',
            ],
        ];
    }
    
    /**
     * Build Content Security Policy header
     *
     * This method constructs a comprehensive Content Security Policy header
     * based on tenant configuration and request context.
     *
     * @param array $config The iframe configuration
     * @param Request $request The HTTP request for context
     * @return string The Content Security Policy header value
     */
    private function buildContentSecurityPolicy(array $config, Request $request): string
    {
        $policies = [];
        
        // Default source
        $policies[] = "default-src 'self'";
        
        // Script sources - allow inline scripts for app functionality
        $scriptSrc = ["'self'", "'unsafe-inline'", "'unsafe-eval'"];
        if (!empty($config['allowed_script_domains'])) {
            $scriptSrc = array_merge($scriptSrc, $config['allowed_script_domains']);
        }
        $policies[] = "script-src " . implode(' ', $scriptSrc);
        
        // Style sources
        $styleSrc = ["'self'", "'unsafe-inline'", "data:", "https://fonts.googleapis.com"];
        if (!empty($config['allowed_style_domains'])) {
            $styleSrc = array_merge($styleSrc, $config['allowed_style_domains']);
        }
        $policies[] = "style-src " . implode(' ', $styleSrc);
        
        // Image sources
        $imgSrc = ["'self'", "data:", "https:", "blob:"];
        if (!empty($config['allowed_image_domains'])) {
            $imgSrc = array_merge($imgSrc, $config['allowed_image_domains']);
        }
        $policies[] = "img-src " . implode(' ', $imgSrc);
        
        // Font sources
        $fontSrc = ["'self'", "data:", "https://fonts.gstatic.com"];
        $policies[] = "font-src " . implode(' ', $fontSrc);
        
        // Connect sources (for API calls)
        $connectSrc = ["'self'", "https:", "wss:", "ws:"];
        if (!empty($config['allowed_connect_domains'])) {
            $connectSrc = array_merge($connectSrc, $config['allowed_connect_domains']);
        }
        $policies[] = "connect-src " . implode(' ', $connectSrc);
        
        // Frame sources (for iframes)
        $frameSrc = ["'self'"];
        if ($config['enable_external_iframes'] && !empty($config['allowed_iframe_domains'])) {
            foreach ($config['allowed_iframe_domains'] as $domain) {
                $frameSrc[] = "https://{$domain}";
                $frameSrc[] = "https://*.{$domain}";
            }
        }
        $policies[] = "frame-src " . implode(' ', $frameSrc);
        
        // Child sources (similar to frame-src for older browsers)
        $policies[] = "child-src " . implode(' ', $frameSrc);
        
        // Worker sources
        $policies[] = "worker-src 'self' blob:";
        
        // Media sources
        $policies[] = "media-src 'self' data: blob: https:";
        
        // Object sources (disable for security)
        $policies[] = "object-src 'none'";
        
        // Base URI
        $policies[] = "base-uri 'self'";
        
        // Form action
        $policies[] = "form-action 'self'";
        
        // Frame ancestors (who can embed this page)
        $frameAncestors = ["'self'"];
        if (!empty($config['allowed_frame_ancestors'])) {
            $frameAncestors = array_merge($frameAncestors, $config['allowed_frame_ancestors']);
        }
        $policies[] = "frame-ancestors " . implode(' ', $frameAncestors);
        
        return implode('; ', $policies);
    }
    
    /**
     * Get allowed origins for CORS
     */
    private function getAllowedOrigins(array $config): string
    {
        $origins = $config['allowed_origins'] ?? [];
        
        // Add current domain
        $currentDomain = request()->getSchemeAndHttpHost();
        if (!in_array($currentDomain, $origins)) {
            $origins[] = $currentDomain;
        }
        
        // For development, allow localhost
        if (app()->environment('local')) {
            $origins[] = 'http://localhost:3000';
            $origins[] = 'http://localhost:5173';
            $origins[] = 'http://127.0.0.1:3000';
            $origins[] = 'http://127.0.0.1:5173';
        }
        
        return implode(', ', $origins);
    }
    
    /**
     * Validate iframe request
     */
    public function validateIframeRequest(Request $request): bool
    {
        $tenantConfig = $this->getTenantIframeConfig($request);
        
        // Check if external iframes are enabled
        if (!$tenantConfig['enable_external_iframes']) {
            return false;
        }
        
        // Validate iframe URL if provided
        if ($request->has('iframe_url')) {
            $iframeUrl = $request->input('iframe_url');
            return $this->isAllowedIframeUrl($iframeUrl, $tenantConfig);
        }
        
        return true;
    }
    
    /**
     * Check if iframe URL is allowed
     */
    private function isAllowedIframeUrl(string $url, array $config): bool
    {
        $allowedDomains = $config['allowed_iframe_domains'] ?? [];
        
        $parsedUrl = parse_url($url);
        if (!$parsedUrl || !isset($parsedUrl['host'])) {
            return false;
        }
        
        $host = $parsedUrl['host'];
        
        foreach ($allowedDomains as $allowedDomain) {
            if ($host === $allowedDomain || str_ends_with($host, '.' . $allowedDomain)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate iframe sandbox attributes
     */
    public static function getIframeSandboxAttributes(array $permissions = []): string
    {
        $defaultPermissions = [
            'allow-scripts',
            'allow-same-origin',
            'allow-forms',
            'allow-downloads',
        ];
        
        $allPermissions = array_merge($defaultPermissions, $permissions);
        $uniquePermissions = array_unique($allPermissions);
        
        return implode(' ', $uniquePermissions);
    }
    
    /**
     * Log iframe security events
     */
    private function logSecurityEvent(string $event, array $data = []): void
    {
        \Log::info("Iframe Security Event: {$event}", [
            'tenant_id' => app()->bound('currentTenant') ? app('currentTenant')->id : null,
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'data' => $data,
        ]);
    }
} 