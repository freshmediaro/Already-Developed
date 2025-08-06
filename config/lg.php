<?php

return [
    // Laravel Grapes Builder Configuration
    
    // Routes configuration
    'builder_prefix' => env('LARAVEL_GRAPES_BUILDER_PREFIX', 'sitebuilder'), // prefix for builder
    'middleware' => env('LARAVEL_GRAPES_MIDDLEWARE', 'auth:tenant'), // middleware for builder
    'frontend_prefix' => env('LARAVEL_GRAPES_FRONTEND_PREFIX', ''), // prefix for frontend

    // Multi-language support
    'languages' => [
        'en', // English
        'ar', // Arabic (RTL supported)
        'es', // Spanish
        'fr', // French
        'de', // German
    ],

    // Tenant isolation settings
    'tenant_isolation' => [
        'enabled' => true,
        'storage_path' => 'sitebuilder/tenant-{tenant_id}/team-{team_id}',
        'templates_path' => 'sitebuilder/templates',
        'assets_path' => 'sitebuilder/assets',
    ],

    // GrapesJS Editor Configuration
    'grapesjs' => [
        'canvas' => [
            'styles' => [
                'height' => '100vh',
                'background' => '#ffffff',
                'font-family' => 'Arial, sans-serif',
            ],
        ],
        'panels' => [
            'defaults' => [
                [
                    'id' => 'layers',
                    'el' => '.panel__right',
                    'resizable' => [
                        'tc' => 0,
                        'cl' => 1,
                        'cr' => 0,
                        'bc' => 0,
                        'minDim' => 200,
                    ],
                ],
                [
                    'id' => 'styles',
                    'el' => '.panel__right',
                    'resizable' => [
                        'tc' => 0,
                        'cl' => 1,
                        'cr' => 0,
                        'bc' => 0,
                        'minDim' => 200,
                    ],
                ],
            ],
        ],
        'storage' => [
            'type' => 'remote',
            'autosave' => true,
            'autoload' => true,
            'stepsBeforeSave' => 5,
        ],
        'style_manager' => [
            'sectors' => [
                [
                    'name' => 'General',
                    'open' => false,
                    'buildProps' => [
                        'float',
                        'display',
                        'position',
                        'top',
                        'right',
                        'left',
                        'bottom',
                    ],
                ],
                [
                    'name' => 'Dimension',
                    'open' => false,
                    'buildProps' => [
                        'width',
                        'height',
                        'max-width',
                        'min-height',
                        'margin',
                        'padding',
                    ],
                ],
                [
                    'name' => 'Typography',
                    'open' => false,
                    'buildProps' => [
                        'font-family',
                        'font-size',
                        'font-weight',
                        'letter-spacing',
                        'color',
                        'line-height',
                        'text-align',
                        'text-decoration',
                        'text-shadow',
                    ],
                ],
                [
                    'name' => 'Background',
                    'open' => false,
                    'buildProps' => [
                        'background-color',
                        'background-image',
                        'background-repeat',
                        'background-position',
                        'background-attachment',
                        'background-size',
                    ],
                ],
                [
                    'name' => 'Border',
                    'open' => false,
                    'buildProps' => [
                        'border',
                        'border-radius',
                        'box-shadow',
                    ],
                ],
                [
                    'name' => 'Extra',
                    'open' => false,
                    'buildProps' => [
                        'opacity',
                        'transition',
                        'transform',
                        'cursor',
                        'overflow',
                    ],
                ],
            ],
        ],
        'block_manager' => [
            'blocks' => [
                'text' => [
                    'label' => 'Text',
                    'category' => 'Basic',
                    'content' => '<div>Insert your text here</div>',
                ],
                'image' => [
                    'label' => 'Image',
                    'category' => 'Basic',
                    'content' => [
                        'type' => 'image',
                        'style' => [
                            'color' => 'black',
                        ],
                        'activeOnRender' => 1,
                    ],
                ],
                'video' => [
                    'label' => 'Video',
                    'category' => 'Basic',
                    'content' => [
                        'type' => 'video',
                        'src' => 'img/video2.webm',
                        'style' => [
                            'height' => '350px',
                            'width' => '615px',
                        ],
                    ],
                ],
                'map' => [
                    'label' => 'Map',
                    'category' => 'Basic',
                    'content' => [
                        'type' => 'map',
                        'style' => [
                            'height' => '350px',
                        ],
                    ],
                ],
            ],
        ],
    ],

    // AI Integration Settings
    'ai_integration' => [
        'enabled' => true,
        'command_prefix' => 'sitebuilder',
        'allowed_commands' => [
            'createPage',
            'updatePageContent',
            'changePageColors',
            'updatePageTexts',
            'createPageTemplate',
            'clonePage',
            'optimizePageSeo',
            'addPageComponent',
            'removePageComponent',
            'updatePageSettings',
            'exportPageHtml',
            'importPageTemplate',
            'previewPage',
            'publishPage',
            'updatePageStyles',
            'addPageBlock',
        ],
        'context_awareness' => [
            'include_page_stats' => true,
            'include_recent_pages' => true,
            'include_templates' => true,
            'include_components' => true,
        ],
    ],

    // Security and permissions
    'security' => [
        'allowed_file_types' => ['html', 'css', 'js', 'json'],
        'max_file_size' => '5MB',
        'sanitize_html' => true,
        'escape_javascript' => true,
        'allowed_domains' => ['localhost', request()->getHost()],
    ],

    // Performance settings
    'performance' => [
        'cache_templates' => true,
        'cache_components' => true,
        'cache_pages' => true,
        'cache_duration' => 3600, // seconds
        'enable_compression' => true,
    ],

    // SEO Configuration
    'seo' => [
        'meta_tags' => [
            'generator' => 'Laravel Grapes Builder',
            'viewport' => 'width=device-width, initial-scale=1',
            'charset' => 'utf-8',
        ],
        'auto_sitemap' => true,
        'auto_robots' => true,
        'structured_data' => true,
    ],

    // Custom CSS/JS assets
    'assets' => [
        'css' => [
            // Add custom CSS files here
        ],
        'js' => [
            // Add custom JS files here
        ],
        'fonts' => [
            'google_fonts' => [
                'Roboto:300,400,500,700',
                'Open Sans:300,400,600,700',
                'Lato:300,400,700',
            ],
        ],
    ],
]; 