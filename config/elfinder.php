<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Upload dir
    |--------------------------------------------------------------------------
    |
    | The dir where to store the images (relative from public)
    |
    */
    'dir' => ['files'],

    /*
    |--------------------------------------------------------------------------
    | Filesystem disks (Flysystem)
    |--------------------------------------------------------------------------
    |
    | Define an array of Filesystem disks, which use Flysystem.
    | You can set extra options, example:
    | 'my-disk' => [
    |        'URL' => url('to/disk'),
    |        'alias' => 'Local storage',
    |    ]
    */
    'disks' => [
        'tenant-r2' => [
            'URL' => env('CLOUDFLARE_R2_URL'),
            'alias' => 'Tenant Files',
            'driver' => 's3',
            'visibility' => 'private',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes group config
    |--------------------------------------------------------------------------
    |
    | The default group settings for the elFinder routes.
    |
    */

    'route' => [
        'prefix' => 'api/files',
        'middleware' => ['web', 'auth:sanctum', 'tenant', 'prevent-central-access'], //Set to null to disable middleware filter
    ],

    /*
    |--------------------------------------------------------------------------
    | Access filter
    |--------------------------------------------------------------------------
    |
    | Filter callback to check the files
    |
    */

    'access' => 'Barryvdh\Elfinder\Elfinder::checkAccess',

    /*
    |--------------------------------------------------------------------------
    | Roots
    |--------------------------------------------------------------------------
    |
    | By default, the roots file is LocalFileSystem, with the above public dir.
    | If you want custom options, you can set your own roots below.
    |
    */

    'roots' => null,

    /*
    |--------------------------------------------------------------------------
    | Options
    |--------------------------------------------------------------------------
    |
    | These options are merged, and will be passed to the Connector constructor.
    | See https://github.com/Studio-42/elFinder/wiki/Connector-configuration-options-2.1
    |
    */

    'options' => [
        'locale' => 'en_US.UTF-8',
        'encoding' => 'UTF-8',
        'debug' => env('APP_DEBUG', false),
        'uploadDeny' => ['all'],
        'uploadAllow' => [
            'image/png', 'image/jpeg', 'image/gif', 'image/webp',
            'application/pdf', 'text/plain', 'text/csv',
            'application/vnd.ms-excel', 
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip', 'application/x-zip-compressed',
        ],
        'uploadOrder' => ['deny', 'allow'],
        'uploadMaxSize' => '50M',
        'defaults' => [
            'read' => true,
            'write' => true,
        ],
        'URL' => env('APP_URL') . '/storage',
        'uploadTempPath' => sys_get_temp_dir(),
        'tmpPath' => sys_get_temp_dir(),
        'tmbDir' => '.tmb',
        'tmbCleanProb' => 1,
        'plugin' => [
            'Normalizer' => [
                'enable' => true,
                'nfc' => true,
                'nfkc' => true,
            ],
            'Sanitizer' => [
                'enable' => true,
                'targets' => ['\\','/',':','*','?','"','<','>','|'],
                'replace' => '_'
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Root Options
    |--------------------------------------------------------------------------
    |
    | These options are merged for all roots, and can be overridden per root.
    |
    */

    'root_options' => [
        'uploadDeny' => ['all'],
        'uploadAllow' => [
            'image/png', 'image/jpeg', 'image/gif', 'image/webp',
            'application/pdf', 'text/plain', 'text/csv',
            'application/vnd.ms-excel', 
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip', 'application/x-zip-compressed',
        ],
        'uploadOrder' => ['deny', 'allow'],
        'uploadMaxSize' => '50M',
        'attributes' => [
            [
                'pattern' => '/\./', // Hide dot files
                'read' => false,
                'write' => false,
                'hidden' => true,
                'locked' => false,
            ],
            [
                'pattern' => '/\.tmb/', // Hide thumbnails folder
                'read' => false,
                'write' => false,
                'hidden' => true,
                'locked' => false,
            ]
        ]
    ],

]; 