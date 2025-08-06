<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate Limiting Middleware - Comprehensive request throttling and rate limiting
 *
 * This middleware provides intelligent rate limiting for different types of requests,
 * protecting against abuse while ensuring fair access to system resources. It supports
 * context-aware rate limiting with user, tenant, and route-specific limits.
 *
 * Key features:
 * - Context-aware rate limiting (user, tenant, IP, route)
 * - Configurable limits for different request types
 * - Intelligent decay periods for different operations
 * - Rate limit headers for client awareness
 * - Graceful failure responses with retry information
 * - Multi-tenant rate limit isolation
 * - Security-focused rate limiting for sensitive operations
 * - Performance optimization through intelligent caching
 * - Abuse prevention and protection
 * - Fair resource allocation
 *
 * Rate limiters:
 * - api: General API requests (60/min)
 * - auth: Authentication attempts (5/min)
 * - ai-chat: AI chat requests (30/min)
 * - file-upload: File upload operations (10/min)
 * - wallet-operations: Wallet transactions (20/min)
 * - webhook: Webhook processing (100/min)
 * - search: Search operations (120/min)
 * - notifications: Notification operations (50/min)
 * - app-store: App store requests (100/min)
 * - iframe-apps: Iframe app requests (200/min)
 * - settings: Settings updates (30/min)
 * - default: Default rate limiting (60/min)
 *
 * The middleware provides:
 * - Comprehensive rate limiting
 * - Security protection
 * - Performance optimization
 * - Multi-tenant isolation
 * - Client feedback
 * - Abuse prevention
 *
 * @package App\Http\Middleware
 * @since 1.0.0
 */
class RateLimitingMiddleware
{
    /**
     * Handle an incoming request and apply rate limiting
     *
     * This method processes the request, checks rate limits based on the limiter type,
     * and either allows the request to proceed or returns a failure response.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the stack
     * @param string $limiter The type of rate limiter to apply (api, auth, ai-chat, etc.)
     * @return Response The response with rate limiting applied
     */
    public function handle(Request $request, Closure $next, string $limiter = 'default'): Response
    {
        $key = $this->resolveRequestSignature($request, $limiter);
        
        if (!RateLimiter::attempt($key, $this->getMaxAttempts($limiter), function () {
            // Empty callback - just checking rate limit
        }, $this->getDecayMinutes($limiter) * 60)) {
            return $this->buildFailureResponse($request, $key, $limiter);
        }

        $response = $next($request);

        // Add rate limit headers
        return $this->addHeaders(
            $response,
            $this->getMaxAttempts($limiter),
            $this->calculateRemainingAttempts($key, $this->getMaxAttempts($limiter))
        );
    }

    /**
     * Resolve request signature for rate limiting based on context
     *
     * This method creates a unique rate limiting key based on the request context,
     * including user, tenant, IP address, and route information.
     *
     * @param Request $request The HTTP request to create signature for
     * @param string $limiter The type of rate limiter being applied
     * @return string The unique rate limiting key
     */
    protected function resolveRequestSignature(Request $request, string $limiter): string
    {
        $user = $request->user();
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;
        
        // Build rate limit key based on context
        $keyParts = [
            $limiter,
            $request->ip(),
        ];

        // Add user context if authenticated
        if ($user) {
            $keyParts[] = "user:{$user->id}";
        }

        // Add tenant context if available
        if ($tenant) {
            $keyParts[] = "tenant:{$tenant->id}";
        }

        // Add route-specific context for certain limiters
        if (in_array($limiter, ['ai-chat', 'file-upload', 'wallet-operations'])) {
            $keyParts[] = "route:{$request->route()?->getName()}";
        }

        return implode(':', $keyParts);
    }

    /**
     * Get maximum attempts for the given rate limiter
     *
     * This method returns the maximum number of attempts allowed for different
     * types of operations based on their sensitivity and resource requirements.
     *
     * @param string $limiter The type of rate limiter
     * @return int The maximum number of attempts allowed
     */
    protected function getMaxAttempts(string $limiter): int
    {
        return match($limiter) {
            'api' => config('app.rate_limits.api', 60), // 60 requests per minute
            'auth' => config('app.rate_limits.auth', 5), // 5 login attempts per minute
            'ai-chat' => config('app.rate_limits.ai_chat', 30), // 30 AI requests per minute
            'file-upload' => config('app.rate_limits.file_upload', 10), // 10 uploads per minute
            'wallet-operations' => config('app.rate_limits.wallet', 20), // 20 wallet ops per minute
            'webhook' => config('app.rate_limits.webhook', 100), // 100 webhook calls per minute
            'search' => config('app.rate_limits.search', 120), // 120 searches per minute
            'notifications' => config('app.rate_limits.notifications', 50), // 50 notification ops per minute
            'app-store' => config('app.rate_limits.app_store', 100), // 100 app store requests per minute
            'iframe-apps' => config('app.rate_limits.iframe_apps', 200), // 200 iframe app requests per minute
            'settings' => config('app.rate_limits.settings', 30), // 30 settings updates per minute
            default => config('app.rate_limits.default', 60),
        };
    }

    /**
     * Get decay minutes for the given rate limiter
     *
     * This method returns the decay period in minutes for different types of
     * rate limiters, determining how long the rate limit remains active.
     *
     * @param string $limiter The type of rate limiter
     * @return int The decay period in minutes
     */
    protected function getDecayMinutes(string $limiter): int
    {
        return match($limiter) {
            'auth' => config('app.rate_limits.auth_decay', 15), // 15 minute lockout for failed auth
            'ai-chat' => config('app.rate_limits.ai_chat_decay', 5), // 5 minute decay for AI
            'wallet-operations' => config('app.rate_limits.wallet_decay', 10), // 10 minute decay for wallet
            default => 1, // 1 minute decay for most operations
        };
    }

    /**
     * Calculate remaining attempts
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return RateLimiter::remaining($key, $maxAttempts);
    }

    /**
     * Add the limit header information to the given response
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => max(0, $remainingAttempts),
        ]);

        return $response;
    }

    /**
     * Build the rate limit failure response
     */
    protected function buildFailureResponse(Request $request, string $key, string $limiter): Response
    {
        $retryAfter = RateLimiter::availableIn($key);
        
        $response = response()->json([
            'error' => 'Too Many Requests',
            'message' => $this->getThrottleMessage($limiter),
            'retry_after' => $retryAfter,
        ], 429);

        $response->headers->add([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Limit' => $this->getMaxAttempts($limiter),
            'X-RateLimit-Remaining' => 0,
        ]);

        return $response;
    }

    /**
     * Get throttle message based on limiter type
     */
    protected function getThrottleMessage(string $limiter): string
    {
        return match($limiter) {
            'auth' => 'Too many login attempts. Please try again later.',
            'ai-chat' => 'AI chat rate limit exceeded. Please wait before sending more messages.',
            'file-upload' => 'File upload rate limit exceeded. Please wait before uploading more files.',
            'wallet-operations' => 'Wallet operation rate limit exceeded. Please wait before making more transactions.',
            'webhook' => 'Webhook rate limit exceeded.',
            'search' => 'Search rate limit exceeded. Please wait before searching again.',
            'notifications' => 'Notification rate limit exceeded.',
            'app-store' => 'App store rate limit exceeded.',
            'iframe-apps' => 'Iframe app rate limit exceeded.',
            'settings' => 'Settings update rate limit exceeded.',
            default => 'Rate limit exceeded. Please try again later.',
        };
    }
} 