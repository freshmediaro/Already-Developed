<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tenant\FileManagerController;

/*
|--------------------------------------------------------------------------
| Tenant File Management Routes
|--------------------------------------------------------------------------
|
| These routes are for tenant-specific file management with ElFinder
| integration, Cloudflare R2 storage, and team-based access control.
|
*/

Route::middleware([
    'web',
    'tenant',
    'prevent-central-access',
    'auth:sanctum',
    'verified',
])->prefix('api/files')->group(function () {
    
    // ElFinder connector endpoint
    Route::any('/elfinder', [FileManagerController::class, 'elfinder'])
        ->name('files.elfinder');
    
    // Custom file management endpoints for the frontend
    Route::get('/folder', [FileManagerController::class, 'getFolderContent'])
        ->name('files.folder');
    
    Route::post('/folder', [FileManagerController::class, 'createFolder'])
        ->name('files.create-folder');
    
    Route::post('/upload', [FileManagerController::class, 'uploadFiles'])
        ->name('files.upload');
    
    Route::delete('/{fileIds}', [FileManagerController::class, 'deleteFiles'])
        ->name('files.delete')
        ->where('fileIds', '[0-9a-f,]+');
    
    // Tenant context endpoint
    Route::get('/context', [FileManagerController::class, 'getTenantContext'])
        ->name('files.context');
    
});

// Additional file management routes with specific permissions
Route::middleware([
    'web',
    'tenant',
    'prevent-central-access',
    'auth:sanctum',
    'verified',
])->prefix('api/files/admin')->group(function () {
    
    // Admin-only file management endpoints
    Route::post('/initialize-storage', function () {
        $tenant = tenant();
        $user = Auth::user();
        $teamId = $user->currentTeam?->id;
        
        if (!$tenant || !$teamId) {
            return response()->json(['error' => 'Invalid context'], 403);
        }
        
        $tenantBucketService = app(\App\Services\TenantBucketService::class);
        $success = $tenantBucketService->initializeTenantStorage($tenant->id, $teamId);
        
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Storage initialized successfully' : 'Failed to initialize storage'
        ]);
    })->name('files.admin.initialize-storage');
    
    Route::get('/storage-stats', function () {
        $tenant = tenant();
        $user = Auth::user();
        $teamId = $user->currentTeam?->id;
        
        if (!$tenant || !$teamId) {
            return response()->json(['error' => 'Invalid context'], 403);
        }
        
        $tenantBucketService = app(\App\Services\TenantBucketService::class);
        $stats = $tenantBucketService->getStorageStats($tenant->id, $teamId);
        
        return response()->json($stats);
    })->name('files.admin.storage-stats');
    
    Route::post('/empty-trash', function () {
        $tenant = tenant();
        $user = Auth::user();
        $teamId = $user->currentTeam?->id;
        
        if (!$tenant || !$teamId) {
            return response()->json(['error' => 'Invalid context'], 403);
        }
        
        $tenantBucketService = app(\App\Services\TenantBucketService::class);
        $success = $tenantBucketService->emptyTrash($tenant->id, $teamId);
        
        return response()->json([
            'success' => $success,
            'message' => $success ? 'Trash emptied successfully' : 'Failed to empty trash'
        ]);
    })->name('files.admin.empty-trash');
    
}); 