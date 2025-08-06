import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.ts',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
            '@core': resolve(__dirname, 'resources/js/Core'),
            '@apps': resolve(__dirname, 'resources/js/Apps'),
            '@tenant': resolve(__dirname, 'resources/js/Tenant'),
            '@utils': resolve(__dirname, 'resources/js/Utils'),
            '@desktop': resolve(__dirname, 'resources/js/Desktop'),
            '@components': resolve(__dirname, 'resources/js/Components'),
            '@layouts': resolve(__dirname, 'resources/js/Layouts'),
            '@pages': resolve(__dirname, 'resources/js/Pages'),
        },
    },

    build: {
        // Enable code splitting for better performance
        rollupOptions: {
            output: {
                // Split vendor dependencies
                manualChunks: {
                    // Vue & Inertia core
                    'vue-core': [
                        'vue',
                        '@inertiajs/vue3',
                    ],
                    
                    // Desktop system core
                    'desktop-core': [
                        './resources/js/Core/WindowManager.ts',
                        './resources/js/Core/EventSystem.ts',
                        './resources/js/Core/AppRegistry.ts',
                        './resources/js/Core/AppLoader.ts',
                    ],
                    
                    // Vue components
                    'vue-components': [
                        './resources/js/Layouts/DesktopLayout.vue',
                        './resources/js/Components/Desktop/WindowManager.vue',
                        './resources/js/Components/Desktop/WindowBase.vue',
                        './resources/js/Components/Desktop/Taskbar.vue',
                        './resources/js/Components/Desktop/DesktopIcons.vue',
                        './resources/js/Components/Desktop/DesktopBackground.vue',
                    ],
                    
                    // Tenant/API functionality
                    'tenant-system': [
                        './resources/js/Tenant/ApiService.ts',
                    ],
                    
                    // Base app framework
                    'app-framework': [
                        './resources/js/Apps/BaseApp.ts',
                    ],
                    
                    // UI Libraries
                    'ui-icons': ['@heroicons/vue/24/outline', '@heroicons/vue/24/solid'],
                    'ui-headless': ['@headlessui/vue'],
                    
                    // Individual apps (will be dynamically imported)
                    'app-calculator': ['./resources/js/Apps/CalculatorApp.ts'],
                    'app-file-explorer': ['./resources/js/Apps/FileExplorerApp.ts'],
                    'app-settings': ['./resources/js/Apps/SettingsApp.ts'],
                    'app-store': ['./resources/js/Apps/AppStoreApp.ts'],
                    'app-site-builder': ['./resources/js/Apps/SiteBuilderApp.ts'],
                    'app-email': ['./resources/js/Apps/EmailApp.ts'],
                    'app-photoshop': ['./resources/js/Apps/PhotoshopApp.ts'],
                    'app-mail': ['./resources/js/Apps/MailApp.ts'],
                    'app-iframe-base': ['./resources/js/Apps/IframeBaseApp.ts'],
                    'app-browser': ['./resources/js/Apps/BrowserApp.ts'],
                    'app-notes': ['./resources/js/Apps/NotesApp.ts'],
                    'app-calendar': ['./resources/js/Apps/CalendarApp.ts'],
                    'app-chat': ['./resources/js/Apps/ChatApp.ts'],
                    'app-photos': ['./resources/js/Apps/PhotosApp.ts'],
                    'app-music': ['./resources/js/Apps/MusicApp.ts'],
                    'app-video': ['./resources/js/Apps/VideoApp.ts'],
                    'app-code-editor': ['./resources/js/Apps/CodeEditorApp.ts'],
                    'app-terminal': ['./resources/js/Apps/TerminalApp.ts'],
                    'app-paint': ['./resources/js/Apps/PaintApp.ts'],
                    'app-pdf-viewer': ['./resources/js/Apps/PdfViewerApp.ts'],
                    'app-zip-manager': ['./resources/js/Apps/ZipManagerApp.ts'],
                    'app-system-monitor': ['./resources/js/Apps/SystemMonitorApp.ts'],
                    'app-backup': ['./resources/js/Apps/BackupApp.ts'],
                },
                
                // Optimize chunk names for better caching
                chunkFileNames: (chunkInfo) => {
                    const { name } = chunkInfo;
                    
                    // Core system chunks
                    if (name.includes('core-system')) {
                        return 'js/core/[name].[hash].js';
                    }
                    
                    // Tenant system chunks
                    if (name.includes('tenant-system')) {
                        return 'js/tenant/[name].[hash].js';
                    }
                    
                    // App chunks
                    if (name.startsWith('app-')) {
                        return 'js/apps/[name].[hash].js';
                    }
                    
                    // App framework
                    if (name.includes('app-framework')) {
                        return 'js/framework/[name].[hash].js';
                    }
                    
                    // Default chunks
                    return 'js/[name].[hash].js';
                },
            },
        },
        
        // Optimize for production
        minify: 'terser',
        terserOptions: {
            compress: {
                drop_console: false, // Keep console logs for debugging
                drop_debugger: true,
            },
        },
        
        // Generate source maps for debugging
        sourcemap: true,
        
        // Chunk size warning limit
        chunkSizeWarningLimit: 1000,
    },

    // Development server configuration
    server: {
        hmr: {
            host: 'localhost',
        },
        watch: {
            // Watch for changes in TypeScript files
            include: ['resources/js/**/*.ts', 'resources/js/**/*.vue'],
        },
    },

    // Optimize dependencies
    optimizeDeps: {
        include: [
            'vue',
            '@inertiajs/vue3',
            'axios',
            '@heroicons/vue/24/outline',
            '@heroicons/vue/24/solid',
            '@headlessui/vue',
        ],
        exclude: [
            // Exclude app modules from pre-bundling to enable dynamic imports
            '@apps/CalculatorApp',
            '@apps/FileExplorerApp',
            '@apps/SettingsApp',
            '@apps/AppStoreApp',
            '@apps/SiteBuilderApp',
            '@apps/EmailApp',
            '@apps/BrowserApp',
            '@apps/NotesApp',
            '@apps/CalendarApp',
            '@apps/ChatApp',
            '@apps/PhotosApp',
            '@apps/MusicApp',
            '@apps/VideoApp',
            '@apps/CodeEditorApp',
            '@apps/TerminalApp',
            '@apps/PaintApp',
            '@apps/PdfViewerApp',
            '@apps/ZipManagerApp',
            '@apps/SystemMonitorApp',
            '@apps/BackupApp',
        ],
    },

    // TypeScript configuration
    esbuild: {
        target: 'es2020',
        keepNames: true,
    },

    // CSS configuration
    css: {
        devSourcemap: true,
        preprocessorOptions: {
            scss: {
                additionalData: `@import "resources/sass/variables.scss";`,
            },
        },
    },

    // Environment variables for the app
    define: {
        __VUE_OPTIONS_API__: true,
        __VUE_PROD_DEVTOOLS__: false,
    },

    // Preview configuration for production builds
    preview: {
        port: 4173,
        strictPort: true,
    },

    // Base URL configuration
    base: './',
}); 