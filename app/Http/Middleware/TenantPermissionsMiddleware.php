<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantPermissionsMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        $tenant = app()->bound('currentTenant') ? app('currentTenant') : null;

        if (!$tenant) {
            return response()->json(['error' => 'Tenant context required'], 403);
        }

        // Ensure user belongs to current team
        if (!$this->userBelongsToCurrentTeam($user)) {
            return response()->json(['error' => 'Access denied: Invalid team context'], 403);
        }

        // Check permissions if specified
        if (!empty($permissions)) {
            if (!$this->userHasPermissions($user, $permissions)) {
                return response()->json([
                    'error' => 'Insufficient permissions',
                    'required_permissions' => $permissions
                ], 403);
            }
        }

        // Check team-specific quotas and limits
        if (!$this->checkTeamLimits($request)) {
            return response()->json(['error' => 'Team quota exceeded'], 429);
        }

        return $next($request);
    }

    /**
     * Check if user belongs to the current team
     */
    private function userBelongsToCurrentTeam($user): bool
    {
        if (!$user->currentTeam) {
            return false;
        }

        // Verify the user is actually a member of the current team
        return $user->belongsToTeam($user->currentTeam);
    }

    /**
     * Check if user has required permissions
     */
    private function userHasPermissions($user, array $permissions): bool
    {
        $currentTeam = $user->currentTeam;
        
        if (!$currentTeam) {
            return false;
        }

        // For personal teams, the owner has all permissions
        if ($currentTeam->personal_team && $currentTeam->user_id === $user->id) {
            return true;
        }

        // Check team membership role
        $teamMembership = $user->teamRole($currentTeam);
        
        if (!$teamMembership) {
            return false;
        }

        // Admin role has all permissions
        if ($teamMembership->role === 'admin') {
            return true;
        }

        // Check specific permissions based on role
        foreach ($permissions as $permission) {
            if (!$this->roleHasPermission($teamMembership->role, $permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if role has specific permission
     */
    private function roleHasPermission(string $role, string $permission): bool
    {
        $rolePermissions = $this->getRolePermissions();

        return isset($rolePermissions[$role]) && 
               in_array($permission, $rolePermissions[$role]);
    }

    /**
     * Get role-based permissions map
     */
    private function getRolePermissions(): array
    {
        return [
            'admin' => [
                // Full access
                'desktop.read', 'desktop.write', 'desktop.config',
                'files.read', 'files.write', 'files.delete', 'files.upload',
                'apps.install', 'apps.uninstall', 'apps.configure',
                'team.manage', 'team.invite', 'team.remove',
                'notifications.read', 'notifications.write',
                'settings.read', 'settings.write',
            ],
            'editor' => [
                // Edit content and files
                'desktop.read', 'desktop.write',
                'files.read', 'files.write', 'files.delete', 'files.upload',
                'apps.install', 'apps.configure',
                'notifications.read',
                'settings.read',
            ],
            'user' => [
                // Basic user permissions
                'desktop.read',
                'files.read', 'files.write', 'files.upload',
                'apps.configure',
                'notifications.read',
                'settings.read',
            ],
            'viewer' => [
                // Read-only access
                'desktop.read',
                'files.read',
                'notifications.read',
                'settings.read',
            ],
        ];
    }

    /**
     * Check team-specific limits and quotas
     */
    private function checkTeamLimits(Request $request): bool
    {
        $user = Auth::user();
        $team = $user->currentTeam;

        if (!$team) {
            return false;
        }

        // Check based on request type
        $routeName = $request->route()?->getName();

        // Check app installation limits
        if ($routeName === 'desktop.apps.install') {
            if (!$team->canInstallApps()) {
                return false;
            }
        }

        // Check file upload limits
        if ($request->hasFile('file') || str_contains($routeName ?? '', 'upload')) {
            $fileSize = 0;
            
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileSize = $file->getSize();
            }

            if (!$team->hasStorageAvailable($fileSize)) {
                return false;
            }
        }

        // Check API rate limits (basic implementation)
        if ($this->isApiRequest($request)) {
            return $this->checkApiRateLimit($team);
        }

        return true;
    }

    /**
     * Check if request is an API request
     */
    private function isApiRequest(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    /**
     * Check API rate limits for team
     */
    private function checkApiRateLimit($team): bool
    {
        // Basic rate limiting based on subscription plan
        $limits = [
            'free' => 100,      // 100 requests per minute
            'basic' => 500,     // 500 requests per minute
            'premium' => 2000,  // 2000 requests per minute
        ];

        $plan = $team->subscription_plan ?? 'free';
        $limit = $limits[$plan] ?? $limits['free'];

        // This is a basic implementation
        // In production, use Redis or Laravel's rate limiting
        $cacheKey = "api_rate_limit:{$team->id}:" . now()->format('Y-m-d-H-i');
        $currentCount = cache()->get($cacheKey, 0);

        if ($currentCount >= $limit) {
            return false;
        }

        cache()->put($cacheKey, $currentCount + 1, 60); // 1 minute TTL
        return true;
    }

    /**
     * Log permission check for audit
     */
    private function logPermissionCheck(Request $request, bool $granted, array $permissions = []): void
    {
        if (!config('app.log_permissions', false)) {
            return;
        }

        \Log::info('Permission Check', [
            'user_id' => Auth::id(),
            'team_id' => Auth::user()->currentTeam?->id,
            'tenant_id' => app()->bound('currentTenant') ? app('currentTenant')->id : null,
            'route' => $request->route()?->getName(),
            'permissions' => $permissions,
            'granted' => $granted,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
} 