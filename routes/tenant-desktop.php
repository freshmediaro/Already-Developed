<?php

use App\Http\Controllers\Tenant\DesktopController;
use App\Http\Controllers\Tenant\FileManagerController;
use App\Http\Controllers\Tenant\IframeAppController;
use App\Http\Controllers\Api\Wallet\AiTokenController;
use App\Http\Controllers\Api\Wallet\PaymentProviderController;
use App\Http\Controllers\Api\AiChatController;
use App\Http\Controllers\Api\AimeosSettingsController;
use App\Http\Controllers\Api\AiSettingsController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AppStoreController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register tenant routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "tenant" middleware group. These routes are automatically
| prefixed with the tenant subdomain.
|
*/

// Desktop interface route
Route::get('/', [DesktopController::class, 'index'])->name('desktop');

// API Routes
Route::prefix('api')->middleware(['auth:sanctum', 'api-logging', 'cache-headers:api'])->group(function () {

    // AI Token Management
    Route::prefix('ai-tokens')->middleware(['wallet-tenant', 'rate-limit:wallet-operations', 'request-validation:wallet'])->group(function () {
        Route::get('/balance', [AiTokenController::class, 'getBalance']);
        Route::get('/packages', [AiTokenController::class, 'getPackages']);
        Route::post('/purchase', [AiTokenController::class, 'purchaseTokens']);
        Route::get('/usage-history', [AiTokenController::class, 'getUsageHistory']);
    });

    // Payment Providers
    Route::prefix('payment-providers')->middleware(['wallet-tenant', 'rate-limit:wallet-operations', 'request-validation:wallet'])->group(function () {
        Route::get('/', [PaymentProviderController::class, 'getAvailable']);
        Route::post('/enable', [PaymentProviderController::class, 'enableProvider']);
        Route::post('/disable', [PaymentProviderController::class, 'disableProvider']);
        Route::put('/configure', [PaymentProviderController::class, 'updateConfig']);
        Route::post('/enable-cashier', [PaymentProviderController::class, 'enableCashier']);
        Route::post('/wallet-topup-intent', [PaymentProviderController::class, 'createWalletTopupIntent']);
        Route::post('/ai-token-purchase-intent', [PaymentProviderController::class, 'createAiTokenPurchaseIntent']);
    });

    // AI Chat
    Route::prefix('ai-chat')->middleware(['ai-chat-tenant', 'rate-limit:ai-chat', 'request-validation:ai-chat'])->group(function () {
        Route::post('/message', [AiChatController::class, 'sendMessage']);
        Route::post('/execute', [AiChatController::class, 'executeCommand']);
        Route::post('/upload', [AiChatController::class, 'uploadFile']);
        Route::post('/feedback', [AiChatController::class, 'sendFeedback']);
        Route::get('/history', [AiChatController::class, 'getHistory']);
        Route::get('/commands', [AiChatController::class, 'getAvailableCommands']);
    });

    // AI Settings
    Route::prefix('ai-settings')->group(function () {
        Route::get('/', [AiSettingsController::class, 'index']);
        Route::put('/', [AiSettingsController::class, 'update']);
        Route::post('/reset', [AiSettingsController::class, 'reset']);
    });

    // Aimeos Settings
    Route::prefix('aimeos-settings')->group(function () {
        Route::get('/', [AimeosSettingsController::class, 'index']);
        Route::put('/', [AimeosSettingsController::class, 'update']);
        Route::post('/upload-logo', [AimeosSettingsController::class, 'uploadLogo']);
        Route::post('/reset', [AimeosSettingsController::class, 'reset']);
    });

    // General Settings
    Route::prefix('settings')->middleware(['rate-limit:settings', 'request-validation:settings'])->group(function () {
        Route::get('/', [SettingsController::class, 'index']);
        Route::put('/', [SettingsController::class, 'update']);
        Route::get('/groups', [SettingsController::class, 'groups']);
        Route::post('/reset', [SettingsController::class, 'reset']);
        Route::get('/{key}', [SettingsController::class, 'show']);
        Route::put('/{key}', [SettingsController::class, 'updateSingle']);
    });

    // Notification Management
    Route::prefix('notifications')->group(function () {
        // Get notifications
        Route::get('/', [NotificationController::class, 'index']);
        
        // Notification settings
        Route::get('/settings', [NotificationController::class, 'getSettings']);
        Route::put('/settings', [NotificationController::class, 'updateSettings']);
        
        // Notification actions
        Route::post('/clear-all', [NotificationController::class, 'clearAll']);
        Route::post('/toggle-dnd', [NotificationController::class, 'toggleDoNotDisturb']);
        Route::post('/toggle-app-mute', [NotificationController::class, 'toggleAppMute']);
        Route::post('/test', [NotificationController::class, 'sendTest']);
        Route::get('/stats', [NotificationController::class, 'getStats']);
        
        // Individual notification actions
        Route::put('/{notificationId}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/{notificationId}/unread', [NotificationController::class, 'markAsUnread']);
        Route::delete('/{notificationId}', [NotificationController::class, 'destroy']);
    });

});

// File Manager Routes
Route::prefix('files')->middleware(['auth:sanctum', 'rate-limit:file-upload', 'request-validation:file-upload'])->group(function () {
    Route::get('/context', [FileManagerController::class, 'getContext']);
    Route::get('/folder', [FileManagerController::class, 'getFolderContents']);
    Route::post('/folder', [FileManagerController::class, 'createFolder']);
    Route::post('/upload', [FileManagerController::class, 'uploadFiles']);
    Route::delete('/{fileIds}', [FileManagerController::class, 'deleteFiles']);
    Route::any('/elfinder', [FileManagerController::class, 'elfinder'])->name('elfinder.connector');
    
    // Admin endpoints for file management
    Route::prefix('admin')->middleware(['can:manage-files'])->group(function () {
        Route::post('/initialize-storage', [FileManagerController::class, 'initializeStorage']);
        Route::get('/storage-stats', [FileManagerController::class, 'getStorageStats']);
        Route::post('/empty-trash', [FileManagerController::class, 'emptyTrash']);
    });
});

    // Iframe App Routes
Route::prefix('iframe-apps')->middleware(['auth:sanctum', 'iframe.security', 'rate-limit:iframe-apps', 'request-validation:iframe-apps'])->group(function () {
    Route::get('/', [IframeAppController::class, 'index']);
    Route::post('/', [IframeAppController::class, 'store']);
    Route::get('/{app}', [IframeAppController::class, 'show']);
    Route::put('/{app}', [IframeAppController::class, 'update']);
    Route::delete('/{app}', [IframeAppController::class, 'destroy']);
    Route::post('/{app}/install', [IframeAppController::class, 'install']);
    Route::post('/{app}/uninstall', [IframeAppController::class, 'uninstall']);
});

    // App Store Routes
    Route::prefix('app-store')->middleware(['auth:sanctum'])->group(function () {
        Route::get('/', [AppStoreController::class, 'index']);
        Route::get('/browse', [AppStoreController::class, 'browse']);
        Route::get('/installed', [AppStoreController::class, 'installed']);
        Route::get('/purchases', [AppStoreController::class, 'purchases']);
        Route::get('/my-apps', [AppStoreController::class, 'myApps']);
        Route::post('/submit-app', [AppStoreController::class, 'submitApp']);
        Route::get('/{slug}', [AppStoreController::class, 'show']);
        Route::post('/{slug}/purchase', [AppStoreController::class, 'purchase']);
        Route::post('/{slug}/install', [AppStoreController::class, 'install']);
        Route::delete('/{slug}/uninstall', [AppStoreController::class, 'uninstall']);
        Route::post('/{slug}/review', [AppStoreController::class, 'submitReview']);
    }); 