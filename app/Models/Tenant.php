<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

/**
 * Tenant Model - Multi-tenant system entity with database and domain isolation
 *
 * This model represents a tenant in the multi-tenant system, providing
 * complete isolation between different organizations or customers. It
 * extends the Stancl Tenancy base tenant with additional functionality
 * for database and domain management.
 *
 * Key features:
 * - Database isolation with separate databases per tenant
 * - Domain management with custom domains per tenant
 * - Plan-based tenant management
 * - UUID-based tenant identification
 * - Tenant-specific configuration and settings
 *
 * The model provides:
 * - Complete data isolation between tenants
 * - Custom domain support for each tenant
 * - Plan-based feature access control
 * - Tenant-specific database connections
 * - Domain routing and management
 *
 * @package App\Models
 * @since 1.0.0
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'name',
        'email',
        'plan',
        'created_at',
        'updated_at',
    ];

    /**
     * Get custom columns for tenant configuration
     *
     * This method defines the custom columns that are specific to this
     * tenant implementation, excluding the standard Stancl Tenancy
     * columns and focusing on business-specific fields.
     *
     * @return array<int, string> Array of custom column names
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'email',
            'plan',
        ];
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => 'string',
    ];

    /**
     * Disable auto-incrementing for UUID primary keys
     *
     * This method ensures that the tenant ID is not auto-incremented,
     * allowing for custom UUID generation and management of tenant
     * identifiers.
     *
     * @return bool Always returns false to disable auto-incrementing
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Set the primary key type to string for UUID support
     *
     * This method configures the primary key type to support string-based
     * UUID identifiers instead of integer-based auto-incrementing keys.
     *
     * @return string Always returns 'string' for UUID primary keys
     */
    public function getKeyType()
    {
        return 'string';
    }
} 