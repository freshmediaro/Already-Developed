<?php

declare(strict_types=1);

use Stancl\Tenancy\Bootstrappers;
use Stancl\Tenancy\Middleware;

return [
    'tenant_model' => \App\Models\Tenant::class,

    'id_generator' => \Stancl\Tenancy\UuidGenerator::class,

    'domain_model' => \Stancl\Tenancy\Database\Models\Domain::class,

    /*
    |--------------------------------------------------------------------------
    | Bootstrappers
    |--------------------------------------------------------------------------
    |
    | The bootstrappers to execute when a tenant is identified.
    |
    */
    'bootstrappers' => [
        Bootstrappers\DatabaseTenancyBootstrapper::class,
        Bootstrappers\CacheTenancyBootstrapper::class,
        Bootstrappers\FilesystemTenancyBootstrapper::class,
        Bootstrappers\QueueTenancyBootstrapper::class,
        Bootstrappers\BroadcastChannelPrefixBootstrapper::class,
        // Bootstrappers\RedisTenancyBootstrapper::class, // Uncomment if using Redis
    ],

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    |
    | Configuration for database tenancy.
    |
    */
    'database' => [
        'central_connection' => env('DB_CONNECTION', 'central'),

        'template_tenant_connection' => null,

        /*
        |--------------------------------------------------------------------------
        | Tenant Database Connection
        |--------------------------------------------------------------------------
        |
        | The database connection used for tenants. Leave as null to use
        | the same connection that's used for the central database.
        |
        */
        'tenant_connection' => null,

        'managers' => [
            'sqlite' => \Stancl\Tenancy\Database\DatabaseManagers\SQLiteDatabaseManager::class,
            'mysql' => \Stancl\Tenancy\Database\DatabaseManagers\MySQLDatabaseManager::class,
            'pgsql' => \Stancl\Tenancy\Database\DatabaseManagers\PostgreSQLDatabaseManager::class,

            /**
             * Use this database manager for MySQL to have a DB user created for each tenant database.
             * You can customize the grants given to these users by changing the $grants property.
             */
            // 'mysql' => \Stancl\Tenancy\Database\DatabaseManagers\PermissionControlledMySQLDatabaseManager::class,

            /**
             * Disable the pgsql manager above, and enable the one below if you
             * want to separate tenant DBs by schemas rather than databases.
             */
            // 'pgsql' => \Stancl\Tenancy\Database\DatabaseManagers\PostgreSQLSchemaManager::class, // Separate by schema instead of database
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Cache tenancy configuration.
    |
    */
    'cache' => [
        'tag_base' => env('CACHE_TAG_BASE', 'tenant'), // This tag_base, followed by the tenant_id, will form a tag that's applied on each cache call.
    ],

    /*
    |--------------------------------------------------------------------------
    | Filesystem
    |--------------------------------------------------------------------------
    |
    | Filesystem tenancy configuration.
    |
    */
    'filesystem' => [
        /*
        |--------------------------------------------------------------------------
        | Asset helper URL
        |--------------------------------------------------------------------------
        |
        | The URL returned by calls to asset() when the package is resolving
        | tenant files. This value is used to prefix tenant file paths.
        |
        */
        'asset_helper_tenancy' => true,

        /*
        |--------------------------------------------------------------------------
        | Filesystem disks
        |--------------------------------------------------------------------------
        |
        | Disks which should be suffixed by the tenant_id.
        |
        */
        'disks' => [
            'local',
            'public',
            'tenant-r2',
        ],

        /*
        |--------------------------------------------------------------------------
        | Use `storage_path()` macro
        |--------------------------------------------------------------------------
        |
        | Use `storage_path()` macro for making the local disks tenant-aware.
        | Set to false if you're using a custom way to make the paths tenant-aware.
        |
        */
        'override_storage_path' => env('OVERRIDE_STORAGE_PATH', true),

        /*
        |--------------------------------------------------------------------------
        | Suffix storage path
        |--------------------------------------------------------------------------
        |
        | Suffix the `storage_path()` with the tenant id. This is only meaningful
        | if override_storage_path is true.
        |
        */
        'suffix_storage_path' => env('SUFFIX_STORAGE_PATH', true),

        /*
        |--------------------------------------------------------------------------
        | Storage path suffix
        |--------------------------------------------------------------------------
        |
        | The suffix to append to the storage path. This is only meaningful
        | if suffix_storage_path is true. Use %tenant_id% as a placeholder.
        |
        */
        'storage_path_suffix' => env('STORAGE_PATH_SUFFIX', '/%tenant_id%'),

        /*
        |--------------------------------------------------------------------------
        | Storage path
        |--------------------------------------------------------------------------
        |
        | The storage path. This is only meaningful if override_storage_path
        | is true. Use %tenant_id% as a placeholder.
        |
        */
        'storage_path' => env('STORAGE_PATH', storage_path() . '/%tenant_id%'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Redis
    |--------------------------------------------------------------------------
    |
    | Redis tenancy configuration.
    |
    */
    'redis' => [
        'prefix_base' => env('REDIS_PREFIX_BASE', 'tenant'), // This prefix_base, followed by the tenant_id, will form a prefix that's applied on each Redis call.
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Features of the package that can be toggled.
    |
    */
    'features' => [
        // \Stancl\Tenancy\Features\UserImpersonation::class,
        // \Stancl\Tenancy\Features\TelescopeTags::class,
        // \Stancl\Tenancy\Features\UniversalRoutes::class,
        \Stancl\Tenancy\Features\TenantConfig::class, // https://tenancyforlaravel.com/docs/v3/features/tenant-config
        // \Stancl\Tenancy\Features\CrossDomainRedirect::class, // https://tenancyforlaravel.com/docs/v3/features/cross-domain-redirect
        // \Stancl\Tenancy\Features\ViteBundler::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Early identification
    |--------------------------------------------------------------------------
    |
    | Early identification of tenants.
    |
    */
    'early_identification' => false,

    /*
    |--------------------------------------------------------------------------
    | Exempt domains
    |--------------------------------------------------------------------------
    |
    | Domains that are exempt from the tenant identification.
    |
    */
    'exempt_domains' => [
        'www.' . env('CENTRAL_DOMAIN'),
        env('CENTRAL_DOMAIN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Identification middleware
    |--------------------------------------------------------------------------
    |
    | Middleware used for tenant identification.
    |
    */
    'identification_middleware' => Middleware\InitializeTenancyByDomain::class,

    /*
    |--------------------------------------------------------------------------
    | Tenant route namespace
    |--------------------------------------------------------------------------
    |
    | The namespace used for tenant routes when the universal router is used.
    |
    */
    'tenant_route_namespace' => 'App\Http\Controllers\Tenant',

    /*
    |--------------------------------------------------------------------------
    | Fallback URL
    |--------------------------------------------------------------------------
    |
    | The URL to redirect to when no tenant is identified and the request
    | is made to a domain that should have a tenant (tenant domain).
    |
    */
    'fallback_url' => env('CENTRAL_DOMAIN_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Queue database connections
    |--------------------------------------------------------------------------
    |
    | The database connections which can be used by the queue for tenant-aware jobs.
    | When null, only the 'tenant' connection can be used.
    |
    */
    'queue_database_connections' => [null],
]; 