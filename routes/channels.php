<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// User-specific notification channels (tenant-aware)
Broadcast::channel('tenant.{tenantId}.user.{userId}', function (User $user, string $tenantId, int $userId) {
    return (int) $user->id === (int) $userId && tenant('id') === $tenantId;
});

// Team-specific notification channels (tenant-aware)
Broadcast::channel('tenant.{tenantId}.team.{teamId}', function (User $user, string $tenantId, int $teamId) {
    return $user->belongsToTeam($teamId) && tenant('id') === $tenantId;
});

// Order-specific channels for AIMEOS (tenant-aware)
Broadcast::channel('tenant.{tenantId}.team.{teamId}.orders', function (User $user, string $tenantId, int $teamId) {
    return $user->belongsToTeam($teamId) && tenant('id') === $tenantId;
});

// AI Chat channels (tenant-aware)
Broadcast::channel('tenant.{tenantId}.user.{userId}.ai-chat', function (User $user, string $tenantId, int $userId) {
    return (int) $user->id === (int) $userId && tenant('id') === $tenantId;
});

// Wallet notifications (tenant-aware)
Broadcast::channel('tenant.{tenantId}.user.{userId}.wallet', function (User $user, string $tenantId, int $userId) {
    return (int) $user->id === (int) $userId && tenant('id') === $tenantId;
});

// Fallback channels for non-tenant environments (for testing/development)
Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('team.{teamId}', function (User $user, int $teamId) {
    return $user->belongsToTeam($teamId);
});

Broadcast::channel('team.{teamId}.orders', function (User $user, int $teamId) {
    return $user->belongsToTeam($teamId);
}); 