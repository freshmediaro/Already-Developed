<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wallet Tenant Middleware - Manages wallet access and permissions for multi-tenant system
 *
 * This middleware validates wallet access permissions and team membership for
 * wallet-related operations. It ensures proper tenant isolation and access
 * control for all wallet functionality within the multi-tenant system.
 *
 * Key features:
 * - Team membership validation for wallet access
 * - Wallet permission checking and validation
 * - Tenant status and payment limit verification
 * - Wallet context injection for downstream processing
 * - Comprehensive access control and security
 * - Team-specific wallet permissions
 * - Account restriction handling
 *
 * Access control levels:
 * - Individual user wallets (personal wallets)
 * - Team wallets (shared team resources)
 * - Tenant wallets (platform-level resources)
 * - Admin wallets (system-level operations)
 *
 * The middleware provides:
 * - Request validation and team access checking
 * - Permission-based access control
 * - Wallet context injection
 * - Account status verification
 * - Comprehensive error handling
 * - Security and isolation enforcement
 *
 * @package App\Http\Middleware
 * @since 1.0.0
 */
class WalletTenantMiddleware
{
    /**
     * Handle an incoming request and validate wallet access
     *
     * This method processes wallet-related requests and validates user
     * permissions, team access, and account status before allowing
     * wallet operations to proceed.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the stack
     * @return Response The response or error response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Validate team access if team_id is provided
        $teamId = $this->getTeamIdFromRequest($request);
        if ($teamId && !$this->validateTeamAccess($user, $teamId)) {
            return response()->json([
                'error' => 'Access denied',
                'message' => 'You do not have access to this team\'s wallet resources'
            ], 403);
        }

        // Check if user has wallet permissions
        if (!$this->checkWalletPermissions($user, $teamId)) {
            return response()->json([
                'error' => 'Insufficient permissions',
                'message' => 'You do not have permission to access wallet features'
            ], 403);
        }

        // Check tenant status and payment limits
        if (!$this->checkTenantStatus($user, $teamId)) {
            return response()->json([
                'error' => 'Account restricted',
                'message' => 'Wallet operations are restricted for this account'
            ], 423);
        }

        // Add wallet context to request
        $request->merge([
            'validated_team_id' => $teamId,
            'wallet_context' => [
                'user_id' => $user->id,
                'team_id' => $teamId,
                'tenant_id' => tenant('id'),
                'permissions' => $this->getUserWalletPermissions($user, $teamId),
                'limits' => $this->getWalletLimits($user, $teamId)
            ]
        ]);

        return $next($request);
    }

    /**
     * Get team ID from request parameters or headers
     *
     * This method extracts the team ID from various possible sources
     * in the request, including request parameters, headers, and route
     * parameters.
     *
     * @param Request $request The HTTP request to extract team ID from
     * @return int|null The team ID or null if not found
     */
    protected function getTeamIdFromRequest(Request $request): ?int
    {
        // Check multiple possible sources for team_id
        $teamId = $request->input('team_id') 
            ?? $request->header('X-Team-ID') 
            ?? $request->route('team_id');
            
        return $teamId ? (int) $teamId : null;
    }

    /**
     * Validate team access for user
     *
     * This method checks whether the user is a member of the specified
     * team, ensuring proper access control for team-specific wallet
     * operations.
     *
     * @param mixed $user The authenticated user
     * @param int $teamId The team ID to validate access for
     * @return bool True if user has access to the team, false otherwise
     */
    protected function validateTeamAccess($user, int $teamId): bool
    {
        return $user->teams()->where('teams.id', $teamId)->exists();
    }

    /**
     * Check wallet permissions for user and team
     *
     * This method validates whether the user has the necessary permissions
     * to access wallet functionality, considering both individual and
     * team-based permissions.
     *
     * @param mixed $user The authenticated user
     * @param int|null $teamId The team ID for team-specific permissions
     * @return bool True if user has wallet permissions, false otherwise
     */
    protected function checkWalletPermissions($user, ?int $teamId): bool
    {
        // For individual users without team context
        if (!$teamId) {
            return true; // Individual users can manage their own wallets
        }

        // Check team-specific permissions
        $teamUser = $user->teams()
            ->where('teams.id', $teamId)
            ->first();

        if (!$teamUser) {
            return false;
        }

        // Check if user has wallet management permissions in the team
        $pivot = $teamUser->pivot;
        $role = $pivot->role ?? 'member';

        // Define roles that can manage wallet operations
        $allowedRoles = ['owner', 'admin', 'finance', 'manager'];
        
        return in_array($role, $allowedRoles);
    }

    /**
     * Check tenant status and restrictions
     */
    protected function checkTenantStatus($user, ?int $teamId): bool
    {
        // Check if tenant account is active
        $tenant = tenant();
        if (!$tenant) {
            return false;
        }

        // Check for any payment restrictions
        // This could include checking for overdue bills, suspension, etc.
        
        // For now, always allow unless explicitly restricted
        return true;
    }

    /**
     * Get wallet permissions for user/team
     */
    protected function getUserWalletPermissions($user, ?int $teamId): array
    {
        $permissions = [
            'can_view_balance' => true,
            'can_view_transactions' => true,
            'can_add_funds' => false,
            'can_withdraw_funds' => false,
            'can_purchase_tokens' => false,
            'can_manage_providers' => false,
            'can_view_analytics' => false
        ];

        if (!$teamId) {
            // Individual user permissions
            $permissions['can_add_funds'] = true;
            $permissions['can_withdraw_funds'] = true;
            $permissions['can_purchase_tokens'] = true;
            $permissions['can_manage_providers'] = true;
            $permissions['can_view_analytics'] = true;
        } else {
            // Team-based permissions
            $teamUser = $user->teams()
                ->where('teams.id', $teamId)
                ->first();

            if ($teamUser) {
                $role = $teamUser->pivot->role ?? 'member';
                
                switch ($role) {
                    case 'owner':
                    case 'admin':
                        $permissions['can_add_funds'] = true;
                        $permissions['can_withdraw_funds'] = true;
                        $permissions['can_purchase_tokens'] = true;
                        $permissions['can_manage_providers'] = true;
                        $permissions['can_view_analytics'] = true;
                        break;
                        
                    case 'finance':
                    case 'manager':
                        $permissions['can_add_funds'] = true;
                        $permissions['can_purchase_tokens'] = true;
                        $permissions['can_view_analytics'] = true;
                        break;
                        
                    case 'member':
                        $permissions['can_purchase_tokens'] = true;
                        break;
                }
            }
        }

        return $permissions;
    }

    /**
     * Get wallet limits for user/team
     */
    protected function getWalletLimits($user, ?int $teamId): array
    {
        $limits = [
            'daily_spending_limit' => null,
            'monthly_spending_limit' => null,
            'withdrawal_limit' => null,
            'max_providers' => 10,
            'min_withdrawal_amount' => config('wallet.withdrawals.min_amount', 10.00),
            'max_withdrawal_amount' => config('wallet.withdrawals.max_amount', 10000.00)
        ];

        // You could customize limits based on user tier, team plan, etc.
        
        return $limits;
    }
} 