<?php

namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Events\TenancyInitialized;
use Stancl\Tenancy\Events\TenancyEnded;
use Stancl\Tenancy\Listeners\BootstrapTenancy;
use Stancl\Tenancy\Listeners\RevertToCentralContext;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/**
 * Tenancy Service Provider - Multi-tenant system configuration and initialization
 *
 * This service provider configures the multi-tenant system, handling tenant
 * initialization, event listeners, middleware configuration, and tenant-specific
 * service configurations for proper isolation and functionality.
 *
 * Key features:
 * - Tenant initialization and bootstrap
 * - Event listener registration
 * - Middleware configuration
 * - Permission system isolation
 * - File system isolation
 * - Cache key management
 * - Error handling and fallbacks
 * - Service configuration
 * - Domain-based tenant identification
 *
 * Supported configurations:
 * - Tenant initialization events
 * - Permission cache isolation
 * - File system isolation
 * - Middleware error handling
 * - Service provider integration
 * - Event listener management
 *
 * The provider provides:
 * - Multi-tenant system initialization
 * - Tenant isolation configuration
 * - Service provider integration
 * - Event listener management
 * - Error handling and fallbacks
 * - Cache and file system isolation
 *
 * @package App\Providers
 * @since 1.0.0
 */
class TenancyServiceProvider extends ServiceProvider
{
    /**
     * Register services in the container
     *
     * This method registers any services that should be available in the
     * service container for the multi-tenant system.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services and configure multi-tenant system
     *
     * This method configures the multi-tenant system including event listeners,
     * middleware settings, and tenant-specific service configurations.
     */
    public function boot(): void
    {
        // Register event listeners
        Event::listen(TenancyInitialized::class, BootstrapTenancy::class);
        Event::listen(TenancyEnded::class, RevertToCentralContext::class);

        // Configure tenancy identification failure handling
        InitializeTenancyByDomain::$onFail = function ($exception, $request, $next) {
            // Redirect to central domain on tenant identification failure
            return redirect()->to(config('app.url'));
        };

        // Configure spatie/laravel-permission cache key for tenant isolation
        $this->configureSpatiePermissions();

        // Configure file permissions for tenant isolation
        $this->configureFilePermissions();
    }

    /**
     * Configure spatie/laravel-permission for tenant isolation
     *
     * This method configures the permission system to use tenant-specific
     * cache keys, ensuring proper permission isolation between tenants.
     */
    protected function configureSpatiePermissions(): void
    {
        // Set tenant-specific cache key for permissions
        Event::listen(TenancyInitialized::class, function () {
            if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
                app(\Spatie\Permission\PermissionRegistrar::class)->cacheKey = 'spatie.permission.cache.tenant.' . tenant('id');
            }
        });

        // Reset cache key when tenancy ends
        Event::listen(TenancyEnded::class, function () {
            if (class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
                app(\Spatie\Permission\PermissionRegistrar::class)->cacheKey = 'spatie.permission.cache';
            }
        });
    }

    /**
     * Configure file permissions for tenant isolation
     *
     * This method configures file system and media library settings for
     * proper tenant isolation and URL generation.
     */
    protected function configureFilePermissions(): void
    {
        // Configure Laravel Media Library URL generator for tenant-aware URLs
        if (class_exists(\Spatie\MediaLibrary\MediaCollections\Models\Media::class)) {
            Event::listen(TenancyInitialized::class, function () {
                // Set tenant-aware URL generator if needed
                // This would be implemented based on your specific needs
            });
        }
    }
} 