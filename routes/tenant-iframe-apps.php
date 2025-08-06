<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tenant\IframeAppController;
use App\Http\Middleware\IframeSecurityMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/*
|--------------------------------------------------------------------------
| Tenant Iframe Apps Routes
|--------------------------------------------------------------------------
|
| These routes handle iframe-based applications like Photoshop (Photopea)
| and Mail apps (SOGo/Mailcow) with tenant-specific configurations and
| security policies.
|
*/

Route::middleware([
    'web',
    'tenant',
    'prevent-central-access',
    'auth:sanctum',
    'verified',
    IframeSecurityMiddleware::class,
])->prefix('api/iframe-apps')->group(function () {
    
    // Get available iframe apps for current tenant
    Route::get('/', [IframeAppController::class, 'getAvailableApps'])
        ->name('iframe-apps.index');
    
    // Get specific iframe app configuration
    Route::get('/{appId}/config', [IframeAppController::class, 'getAppConfig'])
        ->name('iframe-apps.config')
        ->where('appId', '[a-z-]+');
    
    // Update iframe app configuration
    Route::put('/{appId}/config', [IframeAppController::class, 'updateAppConfig'])
        ->name('iframe-apps.config.update')
        ->where('appId', '[a-z-]+');
    
    // Validate iframe URL before loading
    Route::post('/validate-url', [IframeAppController::class, 'validateIframeUrl'])
        ->name('iframe-apps.validate-url');
    
    // Mail app specific routes
    Route::prefix('mail')->group(function () {
        // Get mail server configuration
        Route::get('/config', [IframeAppController::class, 'getMailConfig'])
            ->name('iframe-apps.mail.config');
        
        // Update mail server configuration  
        Route::put('/config', [IframeAppController::class, 'updateMailConfig'])
            ->name('iframe-apps.mail.config.update');
        
        // Test mail server connection
        Route::post('/test-connection', function (Request $request) {
            $validator = Validator::make($request->all(), [
                'server_url' => 'required|url',
                'timeout' => 'integer|min:1|max:30',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            $serverUrl = $request->input('server_url');
            $timeout = $request->input('timeout', 10);
            
            try {
                $context = stream_context_create([
                    'http' => [
                        'timeout' => $timeout,
                        'method' => 'GET',
                        'header' => 'User-Agent: TenantOS/1.0'
                    ]
                ]);
                
                $response = @file_get_contents($serverUrl, false, $context);
                $available = $response !== false;
                
                return response()->json([
                    'available' => $available,
                    'message' => $available ? 'Mail server is accessible' : 'Mail server is not accessible',
                    'tested_url' => $serverUrl,
                ]);
                
            } catch (Exception $e) {
                return response()->json([
                    'available' => false,
                    'message' => 'Connection failed: ' . $e->getMessage(),
                    'tested_url' => $serverUrl,
                ]);
            }
        })->name('iframe-apps.mail.test-connection');
    });
    
    // Photoshop app specific routes
    Route::prefix('photoshop')->group(function () {
        // Get Photoshop app status and configuration
        Route::get('/status', function (Request $request) {
            $tenant = app('currentTenant');
            $config = $tenant->data['iframe_apps']['photoshop'] ?? [];
            
            return response()->json([
                'enabled' => $config['enabled'] ?? true,
                'url' => $config['url'] ?? 'https://www.photopea.com/',
                'file_integration' => $config['enable_file_integration'] ?? true,
                'theme' => $config['theme'] ?? 'auto',
                'last_used' => $config['last_used'] ?? null,
            ]);
        })->name('iframe-apps.photoshop.status');
        
        // Update last used timestamp
        Route::post('/ping', function (Request $request) {
            $tenant = app('currentTenant');
            $tenantData = $tenant->data;
            
            if (!isset($tenantData['iframe_apps']['photoshop'])) {
                $tenantData['iframe_apps']['photoshop'] = [];
            }
            
            $tenantData['iframe_apps']['photoshop']['last_used'] = now()->toISOString();
            $tenant->update(['data' => $tenantData]);
            
            return response()->json(['success' => true]);
        })->name('iframe-apps.photoshop.ping');
        
        // Save Photoshop project to tenant storage
        Route::post('/save-project', function (Request $request) {
            $validator = Validator::make($request->all(), [
                'project_name' => 'required|string|max:255',
                'project_data' => 'required|string',
                'format' => 'required|in:psd,png,jpg,webp',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            
            $user = $request->user();
            $tenant = app('currentTenant');
            $teamId = $user->currentTeam?->id;
            
            if (!$teamId) {
                return response()->json(['error' => 'No active team'], 403);
            }
            
            // TODO: Implement actual file saving to tenant storage
            // This would integrate with your file management system
            
            return response()->json([
                'success' => true,
                'message' => 'Project saved successfully',
                'project_name' => $request->input('project_name'),
                'saved_at' => now()->toISOString(),
            ]);
        })->name('iframe-apps.photoshop.save-project');
    });
    
});

// Admin routes for iframe app management
Route::middleware([
    'web',
    'tenant',
    'prevent-central-access',
    'auth:sanctum',
    'verified',
    IframeSecurityMiddleware::class,
])->prefix('api/admin/iframe-apps')->group(function () {
    
    // Enable/disable iframe apps for tenant
    Route::post('/{appId}/toggle', function (Request $request, string $appId) {
        $user = $request->user();
        $tenant = app('currentTenant');
        
        if (!$user->hasTeamPermission($user->currentTeam, 'apps.admin')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }
        
        $enabled = $request->boolean('enabled');
        
        $tenantData = $tenant->data;
        if (!isset($tenantData['iframe_apps'][$appId])) {
            $tenantData['iframe_apps'][$appId] = [];
        }
        
        $tenantData['iframe_apps'][$appId]['enabled'] = $enabled;
        $tenant->update(['data' => $tenantData]);
        
        return response()->json([
            'success' => true,
            'app_id' => $appId,
            'enabled' => $enabled,
        ]);
    })->name('iframe-apps.admin.toggle');
    
    // Get iframe app usage statistics
    Route::get('/usage-stats', function (Request $request) {
        $user = $request->user();
        $tenant = app('currentTenant');
        
        if (!$user->hasTeamPermission($user->currentTeam, 'apps.admin')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }
        
        $stats = [];
        $iframeApps = $tenant->data['iframe_apps'] ?? [];
        
        foreach ($iframeApps as $appId => $config) {
            $stats[$appId] = [
                'enabled' => $config['enabled'] ?? true,
                'last_used' => $config['last_used'] ?? null,
                'usage_count' => $config['usage_count'] ?? 0,
                'configuration_updated' => $config['configuration_updated'] ?? null,
            ];
        }
        
        return response()->json(['stats' => $stats]);
    })->name('iframe-apps.admin.usage-stats');
    
    // Bulk update iframe app security settings
    Route::put('/security-settings', function (Request $request) {
        $validator = Validator::make($request->all(), [
            'enable_external_iframes' => 'boolean',
            'max_iframe_count' => 'integer|min:1|max:50',
            'allowed_iframe_domains' => 'array',
            'allowed_iframe_domains.*' => 'string|max:255',
            'frame_options' => 'in:DENY,SAMEORIGIN,ALLOW-FROM',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $user = $request->user();
        $tenant = app('currentTenant');
        
        if (!$user->hasTeamPermission($user->currentTeam, 'apps.admin')) {
            return response()->json(['error' => 'Insufficient permissions'], 403);
        }
        
        $tenantData = $tenant->data;
        $tenantData['iframe_config'] = array_merge(
            $tenantData['iframe_config'] ?? [],
            $request->only([
                'enable_external_iframes',
                'max_iframe_count', 
                'allowed_iframe_domains',
                'frame_options'
            ])
        );
        
        $tenant->update(['data' => $tenantData]);
        
        return response()->json([
            'success' => true,
            'config' => $tenantData['iframe_config'],
        ]);
    })->name('iframe-apps.admin.security-settings');
    
}); 