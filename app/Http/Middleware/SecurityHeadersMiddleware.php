<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security Headers Middleware - Comprehensive security header management
 *
 * This middleware applies comprehensive security headers to all HTTP responses,
 * providing protection against various web vulnerabilities and attacks. It
 * implements modern security standards and best practices for web applications.
 *
 * Key security features:
 * - Content Security Policy (CSP) for XSS protection
 * - Frame options for clickjacking prevention
 * - Content type options for MIME sniffing prevention
 * - XSS protection headers
 * - Referrer policy control
 * - HTTPS enforcement (HSTS)
 * - Permissions policy for browser feature control
 * - Cross-origin policies for resource isolation
 *
 * Security headers applied:
 * - X-Content-Type-Options: nosniff
 * - X-Frame-Options: DENY (except for iframe apps)
 * - X-XSS-Protection: 1; mode=block
 * - Referrer-Policy: strict-origin-when-cross-origin
 * - Strict-Transport-Security: HTTPS enforcement
 * - Permissions-Policy: Browser feature restrictions
 * - Content-Security-Policy: Comprehensive XSS protection
 * - Cross-Origin-Embedder-Policy: credentialless
 * - Cross-Origin-Opener-Policy: same-origin
 * - Cross-Origin-Resource-Policy: cross-origin
 *
 * The middleware provides:
 * - Route-specific security policies
 * - Iframe app compatibility
 * - Development vs production configurations
 * - Comprehensive attack prevention
 * - Modern browser security features
 *
 * @package App\Http\Middleware
 * @since 1.0.0
 */
class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request and apply security headers
     *
     * This method processes the request and applies comprehensive security
     * headers to the response, ensuring protection against common web
     * vulnerabilities and attacks.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the stack
     * @return Response The response with security headers applied
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip for non-HTTP responses
        if (!$response instanceof \Illuminate\Http\Response) {
            return $response;
        }

        // Apply security headers
        $this->applySecurityHeaders($response, $request);

        return $response;
    }

    /**
     * Apply comprehensive security headers to the response
     *
     * This method applies all security headers to the HTTP response,
     * providing protection against XSS, clickjacking, MIME sniffing,
     * and other common web vulnerabilities.
     *
     * @param Response $response The HTTP response to modify
     * @param Request $request The original HTTP request
     */
    private function applySecurityHeaders(Response $response, Request $request): void
    {
        // X-Content-Type-Options: Prevents MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // X-Frame-Options: Prevents clickjacking (except for iframe apps)
        if (!$this->isIframeRoute($request)) {
            $response->headers->set('X-Frame-Options', 'DENY');
        }

        // X-XSS-Protection: Enables XSS filtering
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer-Policy: Controls referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Strict-Transport-Security: Enforces HTTPS (only in production)
        if ($request->isSecure() && !app()->environment('local')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Permissions-Policy: Controls browser features
        $response->headers->set('Permissions-Policy', $this->buildPermissionsPolicy());

        // Content-Security-Policy: Comprehensive CSP
        $response->headers->set('Content-Security-Policy', $this->buildContentSecurityPolicy($request));

        // Cross-Origin-Embedder-Policy: Controls embedding of cross-origin resources
        $response->headers->set('Cross-Origin-Embedder-Policy', 'credentialless');

        // Cross-Origin-Opener-Policy: Controls cross-origin window interactions
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        // Cross-Origin-Resource-Policy: Controls cross-origin resource sharing
        $response->headers->set('Cross-Origin-Resource-Policy', 'cross-origin');

        // Remove server information
        $response->headers->remove('Server');
        $response->headers->remove('X-Powered-By');
    }

    /**
     * Build Permissions Policy header for browser feature control
     *
     * This method constructs a comprehensive permissions policy that
     * restricts access to sensitive browser features and APIs,
     * improving security and privacy.
     *
     * @return string The permissions policy header value
     */
    private function buildPermissionsPolicy(): string
    {
        $policies = [
            'accelerometer=()',
            'ambient-light-sensor=()',
            'autoplay=(self)',
            'battery=()',
            'camera=(self)',
            'display-capture=(self)',
            'document-domain=()',
            'encrypted-media=(self)',
            'fullscreen=(self)',
            'geolocation=(self)',
            'gyroscope=()',
            'layout-animations=(self)',
            'legacy-image-formats=(self)',
            'magnetometer=()',
            'microphone=(self)',
            'midi=()',
            'navigation-override=()',
            'oversized-images=(self)',
            'payment=(self)',
            'picture-in-picture=(self)',
            'publickey-credentials-get=(self)',
            'speaker-selection=()',
            'sync-xhr=()',
            'unoptimized-images=(self)',
            'unsized-media=(self)',
            'usb=()',
            'screen-wake-lock=(self)',
            'web-share=(self)',
            'xr-spatial-tracking=()',
        ];

        return implode(', ', $policies);
    }

    /**
     * Build Content Security Policy header
     */
    private function buildContentSecurityPolicy(Request $request): string
    {
        $policies = [];
        $isLocal = app()->environment('local');
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

        // Default source
        $policies[] = "default-src 'self'";

        // Script sources
        $scriptSrc = ["'self'"];
        if ($isLocal) {
            $scriptSrc[] = "'unsafe-eval'"; // For Vite HMR
            $scriptSrc[] = "http://localhost:*";
            $scriptSrc[] = "ws://localhost:*";
        }
        // Allow inline scripts for specific functionality (use nonce in production)
        if ($this->needsInlineScripts($request)) {
            $scriptSrc[] = "'unsafe-inline'";
        }
        $policies[] = "script-src " . implode(' ', $scriptSrc);

        // Style sources
        $styleSrc = ["'self'", "'unsafe-inline'", "https://fonts.googleapis.com"];
        if ($isLocal) {
            $styleSrc[] = "http://localhost:*";
        }
        $policies[] = "style-src " . implode(' ', $styleSrc);

        // Image sources
        $imgSrc = ["'self'", "data:", "blob:", "https:"];
        if ($tenant) {
            // Add tenant-specific image sources
            $imgSrc[] = "https://*.r2.cloudflarestorage.com";
        }
        $policies[] = "img-src " . implode(' ', $imgSrc);

        // Font sources
        $fontSrc = ["'self'", "data:", "https://fonts.gstatic.com"];
        $policies[] = "font-src " . implode(' ', $fontSrc);

        // Connect sources (API calls, WebSocket)
        $connectSrc = ["'self'", "https:", "wss:"];
        if ($isLocal) {
            $connectSrc[] = "http://localhost:*";
            $connectSrc[] = "ws://localhost:*";
        }
        $policies[] = "connect-src " . implode(' ', $connectSrc);

        // Frame sources (for iframe applications)
        $frameSrc = ["'self'"];
        if ($this->isIframeRoute($request) || $this->needsExternalFrames($request)) {
            $frameSrc[] = "https://www.photopea.com";
            $frameSrc[] = "https://photopea.com";
            // Add other allowed iframe sources based on tenant config
            if ($tenant && isset($tenant->data['allowed_iframe_domains'])) {
                foreach ($tenant->data['allowed_iframe_domains'] as $domain) {
                    $frameSrc[] = "https://{$domain}";
                    $frameSrc[] = "https://*.{$domain}";
                }
            }
        }
        $policies[] = "frame-src " . implode(' ', $frameSrc);

        // Child sources (same as frame-src for compatibility)
        $policies[] = "child-src " . implode(' ', $frameSrc);

        // Worker sources
        $policies[] = "worker-src 'self' blob:";

        // Media sources
        $policies[] = "media-src 'self' data: blob: https:";

        // Object sources (disabled for security)
        $policies[] = "object-src 'none'";

        // Base URI
        $policies[] = "base-uri 'self'";

        // Form action
        $policies[] = "form-action 'self'";

        // Frame ancestors (prevent embedding except for allowed cases)
        if ($this->allowsEmbedding($request)) {
            $policies[] = "frame-ancestors 'self' https:";
        } else {
            $policies[] = "frame-ancestors 'none'";
        }

        // Manifest source
        $policies[] = "manifest-src 'self'";

        // Upgrade insecure requests (only in production)
        if (!$isLocal) {
            $policies[] = "upgrade-insecure-requests";
        }

        return implode('; ', $policies);
    }

    /**
     * Check if current route is an iframe-related route
     */
    private function isIframeRoute(Request $request): bool
    {
        $path = $request->path();
        return str_contains($path, 'iframe') || 
               str_contains($path, 'elfinder') ||
               str_contains(strtolower($request->route()?->getName() ?? ''), 'iframe');
    }

    /**
     * Check if route needs inline scripts
     */
    private function needsInlineScripts(Request $request): bool
    {
        // Allow inline scripts for specific routes that need them
        $allowedRoutes = [
            'desktop',
            'dashboard',
        ];

        $routeName = $request->route()?->getName();
        return $routeName && in_array($routeName, $allowedRoutes);
    }

    /**
     * Check if route needs external frames
     */
    private function needsExternalFrames(Request $request): bool
    {
        return $this->isIframeRoute($request) || 
               str_contains($request->path(), 'app');
    }

    /**
     * Check if page allows embedding
     */
    private function allowsEmbedding(Request $request): bool
    {
        // Only iframe-related routes should allow embedding
        return $this->isIframeRoute($request);
    }
} 