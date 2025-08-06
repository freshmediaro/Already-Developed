<?php

use App\Http\Controllers\Admin\SecurityReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Routes for central admin functionality including security reviews
|
*/

Route::middleware(['auth', 'role:admin', 'rate-limit:api', 'api-logging'])->prefix('admin')->group(function () {
    
    // Security Review Routes
    Route::prefix('security-review')->group(function () {
        Route::get('/', [SecurityReviewController::class, 'index'])->name('admin.security-review.index');
        Route::get('/{app}', [SecurityReviewController::class, 'show'])->name('admin.security-review.show');
        Route::post('/{app}/approve', [SecurityReviewController::class, 'approve'])->name('admin.security-review.approve');
        Route::post('/{app}/reject', [SecurityReviewController::class, 'reject'])->name('admin.security-review.reject');
        Route::post('/{app}/rescan', [SecurityReviewController::class, 'rescan'])->name('admin.security-review.rescan');
    });
    
    // Additional admin routes can be added here
    // Route::prefix('app-store')->group(function () {
    //     // App store management routes
    // });
    
    // Route::prefix('tenants')->group(function () {
    //     // Tenant management routes
    // });
    
});