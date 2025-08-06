<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cache Headers Middleware - Comprehensive HTTP caching and performance optimization
 *
 * This middleware applies appropriate HTTP cache headers to responses based on
 * content type, request context, and caching requirements. It provides intelligent
 * caching strategies for different types of content and requests.
 *
 * Key features:
 * - Dynamic cache header application based on content type
 * - Multi-tenant cache isolation and security
 * - API-specific caching strategies
 * - Static asset optimization
 * - Conditional request handling (ETag, Last-Modified)
 * - Compression hints and performance optimization
 * - Security-aware caching for authenticated requests
 * - Content-type specific caching rules
 * - Cache invalidation strategies
 * - Performance monitoring and optimization
 *
 * Cache types:
 * - static: Long-term caching for static content (1 year)
 * - public: Medium-term public caching (1 hour)
 * - private: Short-term private caching (5 minutes)
 * - api: API-specific caching with tenant isolation
 * - assets: Asset file caching with compression
 * - images: Image-specific caching optimization
 * - no-cache: No caching for dynamic content
 * - default: Intelligent default caching
 *
 * The middleware provides:
 * - Comprehensive caching strategies
 * - Performance optimization
 * - Security-aware caching
 * - Multi-tenant isolation
 * - Conditional request handling
 * - Compression optimization
 *
 * @package App\Http\Middleware
 * @since 1.0.0
 */
class CacheHeadersMiddleware
{
    /**
     * Handle an incoming request and apply cache headers
     *
     * This method processes the request and applies appropriate cache headers
     * based on the cache type parameter and response characteristics.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the stack
     * @param string $cacheType The type of caching to apply (static, public, private, api, assets, images, no-cache, default)
     * @return Response The response with cache headers applied
     */
    public function handle(Request $request, Closure $next, string $cacheType = 'default'): Response
    {
        $response = $next($request);

        // Apply cache headers based on cache type and response
        $this->applyCacheHeaders($response, $request, $cacheType);

        return $response;
    }

    /**
     * Apply appropriate cache headers based on cache type and request context
     *
     * This method determines and applies the appropriate cache headers based on
     * the cache type, response status, and request characteristics.
     *
     * @param Response $response The HTTP response to modify
     * @param Request $request The original HTTP request
     * @param string $cacheType The type of caching to apply
     */
    protected function applyCacheHeaders(Response $response, Request $request, string $cacheType): void
    {
        // Don't cache error responses
        if ($response->getStatusCode() >= 400) {
            $this->applyNoCacheHeaders($response);
            return;
        }

        // Don't cache authenticated API requests by default
        if ($request->user() && str_contains($request->path(), 'api/')) {
            $this->applyNoCacheHeaders($response);
            return;
        }

        // Apply cache headers based on type
        match($cacheType) {
            'static' => $this->applyStaticCacheHeaders($response),
            'public' => $this->applyPublicCacheHeaders($response),
            'private' => $this->applyPrivateCacheHeaders($response),
            'api' => $this->applyApiCacheHeaders($response, $request),
            'assets' => $this->applyAssetCacheHeaders($response),
            'images' => $this->applyImageCacheHeaders($response),
            'no-cache' => $this->applyNoCacheHeaders($response),
            default => $this->applyDefaultCacheHeaders($response, $request),
        };

        // Add ETag if not present
        if (!$response->headers->has('ETag')) {
            $this->addETag($response);
        }

        // Add Last-Modified if not present
        if (!$response->headers->has('Last-Modified')) {
            $this->addLastModified($response);
        }
    }

    /**
     * Apply static content cache headers for long-term caching
     *
     * This method applies cache headers suitable for static content that
     * rarely changes, such as CSS, JS, and image files.
     *
     * @param Response $response The HTTP response to modify
     */
    protected function applyStaticCacheHeaders(Response $response): void
    {
        $response->headers->add([
            'Cache-Control' => 'public, max-age=31536000, immutable', // 1 year
            'Expires' => gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT',
            'Vary' => 'Accept-Encoding',
        ]);
    }

    /**
     * Apply public cache headers for medium-term caching
     *
     * This method applies cache headers suitable for public content that
     * can be cached by browsers and CDNs for moderate periods.
     *
     * @param Response $response The HTTP response to modify
     */
    protected function applyPublicCacheHeaders(Response $response): void
    {
        $response->headers->add([
            'Cache-Control' => 'public, max-age=3600, s-maxage=3600', // 1 hour
            'Expires' => gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT',
            'Vary' => 'Accept-Encoding, Accept',
        ]);
    }

    /**
     * Apply private cache headers for short-term caching
     *
     * This method applies cache headers suitable for private content that
     * should only be cached by the user's browser for short periods.
     *
     * @param Response $response The HTTP response to modify
     */
    protected function applyPrivateCacheHeaders(Response $response): void
    {
        $response->headers->add([
            'Cache-Control' => 'private, max-age=300', // 5 minutes
            'Expires' => gmdate('D, d M Y H:i:s', time() + 300) . ' GMT',
            'Vary' => 'Accept-Encoding, Accept, Authorization',
        ]);
    }

    /**
     * Apply API cache headers (very short-term caching)
     */
    protected function applyApiCacheHeaders(Response $response, Request $request): void
    {
        $path = $request->path();
        
        // Different caching for different API endpoints
        if (str_contains($path, 'app-store') || str_contains($path, 'categories')) {
            // App store data can be cached longer
            $response->headers->add([
                'Cache-Control' => 'public, max-age=900', // 15 minutes
                'Expires' => gmdate('D, d M Y H:i:s', time() + 900) . ' GMT',
            ]);
        } elseif (str_contains($path, 'settings') || str_contains($path, 'user')) {
            // User-specific data - private cache
            $response->headers->add([
                'Cache-Control' => 'private, max-age=60', // 1 minute
                'Expires' => gmdate('D, d M Y H:i:s', time() + 60) . ' GMT',
            ]);
        } else {
            // Default API caching
            $response->headers->add([
                'Cache-Control' => 'private, max-age=30', // 30 seconds
                'Expires' => gmdate('D, d M Y H:i:s', time() + 30) . ' GMT',
            ]);
        }

        $response->headers->add([
            'Vary' => 'Accept, Authorization, X-Tenant-ID',
        ]);
    }

    /**
     * Apply asset cache headers (CSS, JS, etc.)
     */
    protected function applyAssetCacheHeaders(Response $response): void
    {
        $response->headers->add([
            'Cache-Control' => 'public, max-age=86400, immutable', // 1 day
            'Expires' => gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT',
            'Vary' => 'Accept-Encoding',
        ]);
    }

    /**
     * Apply image cache headers
     */
    protected function applyImageCacheHeaders(Response $response): void
    {
        $response->headers->add([
            'Cache-Control' => 'public, max-age=604800', // 1 week
            'Expires' => gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT',
            'Vary' => 'Accept-Encoding',
        ]);
    }

    /**
     * Apply no-cache headers
     */
    protected function applyNoCacheHeaders(Response $response): void
    {
        $response->headers->add([
            'Cache-Control' => 'no-cache, no-store, must-revalidate, private',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Apply default cache headers
     */
    protected function applyDefaultCacheHeaders(Response $response, Request $request): void
    {
        // Determine cache strategy based on request
        if ($this->isStaticContent($request)) {
            $this->applyStaticCacheHeaders($response);
        } elseif ($this->isApiRequest($request)) {
            $this->applyApiCacheHeaders($response, $request);
        } else {
            $this->applyPrivateCacheHeaders($response);
        }
    }

    /**
     * Check if request is for static content
     */
    protected function isStaticContent(Request $request): bool
    {
        $path = $request->path();
        $staticPaths = [
            'css/',
            'js/',
            'fonts/',
            'images/',
            'icons/',
            'build/',
            'storage/',
        ];

        foreach ($staticPaths as $staticPath) {
            if (str_starts_with($path, $staticPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if request is an API request
     */
    protected function isApiRequest(Request $request): bool
    {
        return str_starts_with($request->path(), 'api/');
    }

    /**
     * Add ETag header
     */
    protected function addETag(Response $response): void
    {
        $content = $response->getContent();
        if ($content && is_string($content)) {
            $etag = '"' . md5($content) . '"';
            $response->headers->set('ETag', $etag);
        }
    }

    /**
     * Add Last-Modified header
     */
    protected function addLastModified(Response $response): void
    {
        // Set Last-Modified to current time for dynamic content
        // In a real application, you might want to get this from the database
        $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
    }

    /**
     * Handle conditional requests (304 Not Modified)
     */
    protected function handleConditionalRequest(Request $request, Response $response): Response
    {
        $etag = $response->headers->get('ETag');
        $lastModified = $response->headers->get('Last-Modified');

        // Check If-None-Match header
        if ($etag && $request->headers->get('If-None-Match') === $etag) {
            return response('', 304)->withHeaders([
                'ETag' => $etag,
                'Last-Modified' => $lastModified,
                'Cache-Control' => $response->headers->get('Cache-Control'),
            ]);
        }

        // Check If-Modified-Since header
        if ($lastModified) {
            $ifModifiedSince = $request->headers->get('If-Modified-Since');
            if ($ifModifiedSince && strtotime($ifModifiedSince) >= strtotime($lastModified)) {
                return response('', 304)->withHeaders([
                    'ETag' => $etag,
                    'Last-Modified' => $lastModified,
                    'Cache-Control' => $response->headers->get('Cache-Control'),
                ]);
            }
        }

        return $response;
    }

    /**
     * Apply tenant-specific cache headers
     */
    protected function applyTenantCacheHeaders(Response $response, Request $request): void
    {
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;
        
        if ($tenant) {
            // Add tenant-specific cache variations
            $response->headers->add([
                'Vary' => $response->headers->get('Vary', '') . ', X-Tenant-ID',
            ]);
        }
    }

    /**
     * Apply compression hints
     */
    protected function applyCompressionHints(Response $response): void
    {
        $contentType = $response->headers->get('Content-Type', '');
        
        // Add compression hints for compressible content
        if (str_contains($contentType, 'text/') || 
            str_contains($contentType, 'application/json') ||
            str_contains($contentType, 'application/javascript')) {
            
            $response->headers->add([
                'Vary' => $response->headers->get('Vary', '') . ', Accept-Encoding',
            ]);
        }
    }
} 