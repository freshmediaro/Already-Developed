<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Logging Middleware - Comprehensive API request and response logging
 *
 * This middleware provides comprehensive logging for API requests and responses,
 * including performance metrics, security events, and audit trails. It ensures
 * proper data sanitization and privacy protection while maintaining detailed
 * logging for debugging and monitoring.
 *
 * Key features:
 * - Request and response logging with performance metrics
 * - Data sanitization and privacy protection
 * - Multi-tenant logging isolation
 * - Security event logging
 * - Performance monitoring and metrics
 * - Sensitive endpoint filtering
 * - Context-aware logging
 * - Audit trail maintenance
 * - Error tracking and debugging
 * - Team and user context tracking
 *
 * Log levels:
 * - info: Standard API request/response logging
 * - debug: Detailed debugging information
 * - warning: Warning-level events and issues
 * - error: Error conditions and failures
 * - security: Security-related events and alerts
 *
 * Logged information:
 * - Request method, URL, and path
 * - User and tenant context
 * - Request duration and performance
 * - Response status and headers
 * - Sanitized request/response data
 * - IP address and user agent
 * - Team and user identification
 *
 * The middleware provides:
 * - Comprehensive API logging
 * - Performance monitoring
 * - Security event tracking
 * - Data privacy protection
 * - Multi-tenant isolation
 * - Debugging support
 *
 * @package App\Http\Middleware
 * @since 1.0.0
 */
class ApiLoggingMiddleware
{
    /**
     * Handle an incoming request and log API activity
     *
     * This method processes the request, logs the incoming request details,
     * processes the response, and logs the response with performance metrics.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the stack
     * @param string $logLevel The log level to use for logging (info, debug, warning, error)
     * @return Response The response with logging applied
     */
    public function handle(Request $request, Closure $next, string $logLevel = 'info'): Response
    {
        $startTime = microtime(true);
        
        // Log request
        $this->logRequest($request, $logLevel);
        
        $response = $next($request);
        
        // Log response
        $this->logResponse($request, $response, $startTime, $logLevel);
        
        return $response;
    }

    /**
     * Log incoming API request details
     *
     * This method logs comprehensive information about the incoming request,
     * including method, URL, user context, and sanitized request data.
     *
     * @param Request $request The HTTP request to log
     * @param string $logLevel The log level to use
     */
    protected function logRequest(Request $request, string $logLevel): void
    {
        $context = $this->buildRequestContext($request);
        
        // Don't log sensitive endpoints by default
        if ($this->shouldSkipLogging($request)) {
            return;
        }

        Log::log($logLevel, 'API Request', $context);
    }

    /**
     * Log outgoing API response details
     *
     * This method logs comprehensive information about the response,
     * including status, duration, and sanitized response data.
     *
     * @param Request $request The original HTTP request
     * @param Response $response The HTTP response to log
     * @param float $startTime The request start time for duration calculation
     * @param string $logLevel The log level to use
     */
    protected function logResponse(Request $request, Response $response, float $startTime, string $logLevel): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2); // ms
        
        // Don't log sensitive endpoints by default
        if ($this->shouldSkipLogging($request)) {
            return;
        }

        $context = $this->buildResponseContext($request, $response, $duration);
        
        // Use different log levels based on response status
        $responseLogLevel = $this->getResponseLogLevel($response->getStatusCode(), $logLevel);
        
        Log::log($responseLogLevel, 'API Response', $context);
    }

    /**
     * Build comprehensive request context for logging
     *
     * This method builds a detailed context array containing all relevant
     * information about the request for logging purposes.
     *
     * @param Request $request The HTTP request to build context for
     * @return array The request context array for logging
     */
    protected function buildRequestContext(Request $request): array
    {
        $user = $request->user();
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

        $context = [
            'method' => $request->getMethod(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $user?->id,
            'tenant_id' => $tenant?->id,
            'headers' => $this->sanitizeHeaders($request->headers->all()),
        ];

        // Add request data for non-GET requests
        if (!$request->isMethod('GET')) {
            $context['payload'] = $this->sanitizeRequestData($request->all());
        }

        // Add query parameters for GET requests
        if ($request->isMethod('GET') && $request->query->count() > 0) {
            $context['query'] = $request->query->all();
        }

        // Add team context if available
        if ($teamId = $this->getTeamIdFromRequest($request)) {
            $context['team_id'] = $teamId;
        }

        return $context;
    }

    /**
     * Build response context for logging
     */
    protected function buildResponseContext(Request $request, Response $response, float $duration): array
    {
        $user = $request->user();
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

        $context = [
            'method' => $request->getMethod(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_id' => $user?->id,
            'tenant_id' => $tenant?->id,
            'response_size' => strlen($response->getContent()),
        ];

        // Add response data for errors
        if ($response->getStatusCode() >= 400) {
            $content = $response->getContent();
            if (is_string($content)) {
                $decoded = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $context['error_response'] = $this->sanitizeResponseData($decoded);
                }
            }
        }

        // Add performance metrics
        if ($duration > 1000) { // Log slow requests (>1s)
            $context['performance_warning'] = 'Slow request detected';
        }

        return $context;
    }

    /**
     * Sanitize headers for logging
     */
    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = [
            'authorization',
            'cookie',
            'x-api-key',
            'x-auth-token',
        ];

        $sanitized = [];
        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $sensitiveHeaders)) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = is_array($value) ? implode(', ', $value) : $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize request data for logging
     */
    protected function sanitizeRequestData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'api_key',
            'secret',
            'private_key',
            'credit_card_number',
            'cvv',
            'ssn',
        ];

        return $this->recursiveSanitize($data, $sensitiveFields);
    }

    /**
     * Sanitize response data for logging
     */
    protected function sanitizeResponseData(array $data): array
    {
        $sensitiveFields = [
            'token',
            'api_key',
            'secret',
            'private_key',
            'password',
        ];

        return $this->recursiveSanitize($data, $sensitiveFields);
    }

    /**
     * Recursively sanitize array data
     */
    protected function recursiveSanitize(array $data, array $sensitiveFields): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->recursiveSanitize($value, $sensitiveFields);
            } else {
                // Truncate very long values
                if (is_string($value) && strlen($value) > 1000) {
                    $sanitized[$key] = substr($value, 0, 1000) . '... [TRUNCATED]';
                } else {
                    $sanitized[$key] = $value;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Get team ID from request
     */
    protected function getTeamIdFromRequest(Request $request): ?int
    {
        return $request->input('team_id') 
            ?? $request->header('X-Team-ID') 
            ?? $request->user()?->current_team_id;
    }

    /**
     * Determine if request should skip logging
     */
    protected function shouldSkipLogging(Request $request): bool
    {
        $skipPaths = [
            'up', // Health check
            'sanctum/csrf-cookie',
            'webhooks/stripe', // Webhook endpoints (too noisy)
        ];

        $path = $request->path();
        
        foreach ($skipPaths as $skipPath) {
            if (str_contains($path, $skipPath)) {
                return true;
            }
        }

        // Skip logging for specific methods on certain endpoints
        if ($request->isMethod('OPTIONS')) {
            return true; // Skip CORS preflight requests
        }

        return false;
    }

    /**
     * Get appropriate log level based on response status
     */
    protected function getResponseLogLevel(int $statusCode, string $defaultLevel): string
    {
        return match(true) {
            $statusCode >= 500 => 'error',
            $statusCode >= 400 => 'warning',
            $statusCode >= 300 => 'info',
            default => $defaultLevel,
        };
    }

    /**
     * Log security events
     */
    public static function logSecurityEvent(string $event, array $context = []): void
    {
        Log::warning("Security Event: {$event}", array_merge([
            'timestamp' => now()->toISOString(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'tenant_id' => app()->bound('currentTenant') ? app('currentTenant')->id : null,
        ], $context));
    }

    /**
     * Log performance metrics
     */
    public static function logPerformanceMetrics(string $operation, float $duration, array $context = []): void
    {
        Log::info("Performance Metric: {$operation}", array_merge([
            'duration_ms' => round($duration * 1000, 2),
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id(),
            'tenant_id' => app()->bound('currentTenant') ? app('currentTenant')->id : null,
        ], $context));
    }
} 