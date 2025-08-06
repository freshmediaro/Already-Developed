<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * AI Chat Tenant Middleware - Handles AI-specific tenant validation and access control
 * 
 * This middleware provides comprehensive validation for AI chat operations within
 * the multi-tenant system. It ensures proper team access, usage limits, and
 * permission checking for AI-related functionality.
 * 
 * Key responsibilities:
 * - Team context validation for AI operations
 * - Usage limit enforcement and quota checking
 * - AI permission management based on team roles
 * - Tenant context injection for controllers
 * - Security validation for AI resource access
 * 
 * The middleware validates:
 * - User authentication and team membership
 * - AI usage quotas and limits
 * - Team-based permissions and roles
 * - Tenant isolation and data access
 * 
 * @package App\Http\Middleware
 * @since 1.0.0
 */
class AiChatTenantMiddleware
{
    /**
     * Handle an incoming request with AI-specific tenant validation
     * 
     * This method processes incoming AI chat requests and performs comprehensive
     * validation including team access, usage limits, and permission checking.
     * It injects validated tenant context into the request for use by controllers.
     * 
     * The validation process includes:
     * - User authentication verification
     * - Team access validation
     * - Usage limit checking
     * - Permission assignment based on team role
     * - Tenant context injection
     * 
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the stack
     * @return Response The HTTP response (either error or continuation)
     * 
     * @throws \Exception When validation fails or tenant context is invalid
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Validate team context for AI operations
        $teamId = $this->getTeamIdFromRequest($request);
        
        if ($teamId && !$this->validateTeamAccess($user, $teamId)) {
            return response()->json([
                'error' => 'Access denied', 
                'message' => 'You do not have access to this team\'s AI resources'
            ], 403);
        }

        // Check AI usage limits for the team/user
        if (!$this->checkUsageLimits($user, $teamId)) {
            return response()->json([
                'error' => 'Usage limit exceeded',
                'message' => 'AI usage limit has been reached for this period'
            ], 429);
        }

        // Add tenant context to request for controllers
        $request->merge([
            'validated_team_id' => $teamId,
            'ai_context' => [
                'user_id' => $user->id,
                'team_id' => $teamId,
                'tenant_id' => tenant('id'),
                'permissions' => $this->getUserAiPermissions($user, $teamId)
            ]
        ]);

        return $next($request);
    }

    /**
     * Extract team ID from request context
     * 
     * This method attempts to extract the team ID from various sources in the
     * request, providing fallback mechanisms to ensure proper team context
     * for AI operations.
     * 
     * @param Request $request The incoming HTTP request
     * @return int|null The team ID if found, null otherwise
     */
    protected function getTeamIdFromRequest(Request $request): ?int
    {
        // Try to get team ID from various sources
        $teamId = $request->input('context.team.id') ?? 
                  $request->input('team_id') ?? 
                  $request->user()?->current_team_id;

        return $teamId ? (int) $teamId : null;
    }

    /**
     * Validate user access to the specified team
     * 
     * This method ensures that the authenticated user is actually a member
     * of the specified team, providing team-based access control for AI
     * operations.
     * 
     * @param mixed $user The authenticated user model
     * @param int $teamId The team ID to validate access for
     * @return bool True if user has access to the team, false otherwise
     */
    protected function validateTeamAccess($user, int $teamId): bool
    {
        return $user->teams()->where('teams.id', $teamId)->exists();
    }

    /**
     * Check if user/team has remaining AI usage quota
     * 
     * This method validates that the user or team has not exceeded their
     * AI usage limits for the current period, preventing abuse and ensuring
     * fair resource distribution.
     * 
     * @param mixed $user The authenticated user model
     * @param int|null $teamId Optional team ID for team-based quota checking
     * @return bool True if usage is within limits, false if exceeded
     */
    protected function checkUsageLimits($user, ?int $teamId): bool
    {
        // In a real implementation, you'd check against usage quotas
        // For now, we'll do basic validation
        
        $monthlyUsage = \App\Models\AiChatUsage::where('user_id', $user->id)
            ->when($teamId, function ($query) use ($teamId) {
                return $query->where('team_id', $teamId);
            })
            ->whereMonth('created_at', now()->month)
            ->sum('tokens_used');

        // Default limit: 100K tokens per month per user/team
        $limit = config('ai-chat.usage_limits.monthly_tokens', 100000);
        
        return $monthlyUsage < $limit;
    }

    /**
     * Get AI permissions for user in the context of a team
     * 
     * This method determines the AI-related permissions available to the user
     * based on their role within the specified team. It provides role-based
     * access control for AI functionality.
     * 
     * @param mixed $user The authenticated user model
     * @param int|null $teamId Optional team ID for team-based permissions
     * @return array Array of permission flags for AI operations
     */
    protected function getUserAiPermissions($user, ?int $teamId): array
    {
        $permissions = [
            'can_chat' => true,
            'can_upload_images' => true,
            'can_upload_documents' => true,
            'can_execute_commands' => false,
            'can_access_analytics' => false,
        ];

        if ($teamId) {
            $team = $user->teams()->where('teams.id', $teamId)->first();
            $role = $team?->pivot?->role;

            // Adjust permissions based on team role
            switch ($role) {
                case 'owner':
                case 'admin':
                    $permissions['can_execute_commands'] = true;
                    $permissions['can_access_analytics'] = true;
                    break;
                    
                case 'editor':
                    $permissions['can_execute_commands'] = true;
                    break;
                    
                case 'viewer':
                    $permissions['can_execute_commands'] = false;
                    break;
            }
        }

        return $permissions;
    }
} 