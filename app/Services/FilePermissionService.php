<?php

namespace App\Services;

use App\Models\User;
use Stancl\Tenancy\Database\Models\Tenant;
use Illuminate\Support\Facades\Cache;

/**
 * File Permission Service - Manages file and directory access permissions
 *
 * This service handles comprehensive file and directory permission management
 * within the multi-tenant system, ensuring proper access control, security,
 * and tenant isolation for all file operations.
 *
 * Key features:
 * - Multi-tenant file access control
 * - Team-based permission management
 * - File ownership and sharing controls
 * - Storage quota enforcement
 * - File locking and editing protection
 * - System file protection
 * - Extension and size restrictions
 * - Comprehensive permission validation
 * - Cache-based permission optimization
 * - Security and isolation enforcement
 *
 * Permission types:
 * - read: File and directory reading permissions
 * - write: File and directory writing permissions
 * - delete: File and directory deletion permissions
 * - hidden: File and directory visibility control
 * - locked: File locking and editing protection
 *
 * Access control levels:
 * - Tenant isolation: Users can only access their tenant's files
 * - Team isolation: Users can only access their team's files
 * - Ownership control: File owners have full access
 * - Sharing permissions: Shared file access management
 * - Admin privileges: Team admins have extended permissions
 *
 * The service provides:
 * - Comprehensive permission checking
 * - File locking and unlocking
 * - Storage quota validation
 * - Extension and size restrictions
 * - System file protection
 * - Cache-based performance optimization
 * - Security and isolation enforcement
 * - Multi-level access control
 *
 * @package App\Services
 * @since 1.0.0
 */
class FilePermissionService
{
    /**
     * Check if user has access to a file or directory with specific attribute
     *
     * This method performs comprehensive permission checking for file and directory
     * access, including tenant isolation, team permissions, ownership validation,
     * and specific attribute-based access control.
     *
     * The permission checking includes:
     * - Tenant isolation validation
     * - Team membership verification
     * - File ownership checking
     * - Shared access validation
     * - Storage quota enforcement
     * - System file protection
     * - Attribute-specific permission validation
     *
     * @param User $user The user requesting access
     * @param Tenant $tenant The tenant context
     * @param string $path The file or directory path
     * @param string $attribute The permission attribute to check (read, write, delete, hidden, locked)
     * @param bool $isDir Whether the path is a directory
     * @return bool True if user has access, false otherwise
     */
    public function checkAccess(User $user, Tenant $tenant, string $path, string $attribute, bool $isDir): bool
    {
        // Basic tenant isolation - users can only access their tenant's files
        if (!$this->isValidTenantPath($tenant->id, $path)) {
            return false;
        }

        // Team isolation - users can only access their team's files
        $teamId = $user->currentTeam?->id;
        if (!$teamId || !$this->isValidTeamPath($teamId, $path)) {
            return false;
        }

        // Check specific permissions based on attribute
        switch ($attribute) {
            case 'read':
                return $this->canRead($user, $tenant, $path, $isDir);
            case 'write':
                return $this->canWrite($user, $tenant, $path, $isDir);
            case 'delete':
                return $this->canDelete($user, $tenant, $path, $isDir);
            case 'hidden':
                return $this->isHidden($user, $tenant, $path, $isDir);
            case 'locked':
                return $this->isLocked($user, $tenant, $path, $isDir);
            default:
                return false;
        }
    }

    /**
     * Check if user can read a file or directory
     *
     * This method validates read permissions for files and directories,
     * considering system file protection, team membership, ownership,
     * and shared access permissions.
     *
     * @param User $user The user requesting read access
     * @param Tenant $tenant The tenant context
     * @param string $path The file or directory path
     * @param bool $isDir Whether the path is a directory
     * @return bool True if user can read, false otherwise
     */
    public function canRead(User $user, Tenant $tenant, string $path, bool $isDir): bool
    {
        // System files are hidden
        if ($this->isSystemFile($path)) {
            return false;
        }

        // Team members can read all team files
        if ($this->isTeamMember($user, $tenant)) {
            return true;
        }

        // Owner can always read
        if ($this->isFileOwner($user, $path)) {
            return true;
        }

        // Check shared file permissions
        return $this->hasSharedAccess($user, $path, 'read');
    }

    /**
     * Check if user can write to a file or directory
     *
     * This method validates write permissions for files and directories,
     * considering system path protection, storage quotas, team admin
     * privileges, ownership, and shared access permissions.
     *
     * @param User $user The user requesting write access
     * @param Tenant $tenant The tenant context
     * @param string $path The file or directory path
     * @param bool $isDir Whether the path is a directory
     * @return bool True if user can write, false otherwise
     */
    public function canWrite(User $user, Tenant $tenant, string $path, bool $isDir): bool
    {
        // System directories are read-only
        if ($this->isSystemPath($path)) {
            return false;
        }

        // Check storage quota
        if (!$this->hasStorageQuota($tenant, $user->currentTeam?->id)) {
            return false;
        }

        // Team admins can write anywhere in team space
        if ($this->isTeamAdmin($user)) {
            return true;
        }

        // Owner can write to their files
        if ($this->isFileOwner($user, $path)) {
            return true;
        }

        // Check shared write permissions
        return $this->hasSharedAccess($user, $path, 'write');
    }

    /**
     * Check if user can delete a file or directory
     *
     * This method validates delete permissions for files and directories,
     * considering system protection, team admin privileges, ownership,
     * and shared access permissions.
     *
     * @param User $user The user requesting delete access
     * @param Tenant $tenant The tenant context
     * @param string $path The file or directory path
     * @param bool $isDir Whether the path is a directory
     * @return bool True if user can delete, false otherwise
     */
    public function canDelete(User $user, Tenant $tenant, string $path, bool $isDir): bool
    {
        // System files cannot be deleted
        if ($this->isSystemFile($path) || $this->isSystemPath($path)) {
            return false;
        }

        // Team admins can delete team files
        if ($this->isTeamAdmin($user)) {
            return true;
        }

        // Owner can delete their files
        if ($this->isFileOwner($user, $path)) {
            return true;
        }

        // Check shared delete permissions
        return $this->hasSharedAccess($user, $path, 'delete');
    }

    /**
     * Check if file/directory should be hidden
     */
    public function isHidden(User $user, Tenant $tenant, string $path, bool $isDir): bool
    {
        // Hide system files and directories
        if ($this->isSystemFile($path) || $this->isSystemPath($path)) {
            return true;
        }

        // Hide files starting with dot
        $basename = basename($path);
        if (str_starts_with($basename, '.')) {
            return true;
        }

        // Hide other team's files
        $teamId = $user->currentTeam?->id;
        if (!$this->isValidTeamPath($teamId, $path)) {
            return true;
        }

        return false;
    }

    /**
     * Check if file/directory is locked
     */
    public function isLocked(User $user, Tenant $tenant, string $path, bool $isDir): bool
    {
        // System files are locked
        if ($this->isSystemFile($path) || $this->isSystemPath($path)) {
            return true;
        }

        // Check if file is being edited by another user
        return $this->isFileBeingEdited($path);
    }

    /**
     * Validate that path belongs to the tenant
     */
    protected function isValidTenantPath(string $tenantId, string $path): bool
    {
        return str_starts_with($path, "tenant-{$tenantId}/");
    }

    /**
     * Validate that path belongs to the team
     */
    protected function isValidTeamPath(?int $teamId, string $path): bool
    {
        if (!$teamId) {
            return false;
        }
        
        return str_contains($path, "/team-{$teamId}/");
    }

    /**
     * Check if path is a system file
     */
    protected function isSystemFile(string $path): bool
    {
        $systemFiles = ['.htaccess', '.gitignore', '.env', 'web.config'];
        $basename = basename($path);
        
        return in_array($basename, $systemFiles) || str_starts_with($basename, '.');
    }

    /**
     * Check if path is a system directory
     */
    protected function isSystemPath(string $path): bool
    {
        $systemPaths = ['system', 'config', 'logs', '.git', '.svn'];
        
        foreach ($systemPaths as $systemPath) {
            if (str_contains($path, "/{$systemPath}/") || str_ends_with($path, "/{$systemPath}")) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if user is a team member
     */
    protected function isTeamMember(User $user, Tenant $tenant): bool
    {
        $team = $user->currentTeam;
        return $team && $team->users->contains($user);
    }

    /**
     * Check if user is a team admin
     */
    protected function isTeamAdmin(User $user): bool
    {
        $team = $user->currentTeam;
        if (!$team) {
            return false;
        }
        
        return $team->owner_id === $user->id || 
               $user->hasTeamRole($team, 'admin');
    }

    /**
     * Check if user owns the file (simplified - in real app, track file ownership)
     */
    protected function isFileOwner(User $user, string $path): bool
    {
        // In a real implementation, you'd track file ownership in database
        // For now, assume users own files in their team space
        $teamId = $user->currentTeam?->id;
        return $teamId && str_contains($path, "/team-{$teamId}/");
    }

    /**
     * Check if user has shared access to file
     */
    protected function hasSharedAccess(User $user, string $path, string $permission): bool
    {
        // Cache key for performance
        $cacheKey = "file_shared_access_{$user->id}_{$path}_{$permission}";
        
        return Cache::remember($cacheKey, 300, function () use ($user, $path, $permission) {
            // In a real implementation, check file sharing permissions from database
            // For now, return false (no shared access)
            return false;
        });
    }

    /**
     * Check if tenant/team has storage quota available
     */
    protected function hasStorageQuota(Tenant $tenant, ?int $teamId): bool
    {
        if (!$teamId) {
            return false;
        }
        
        $cacheKey = "storage_quota_{$tenant->id}_{$teamId}";
        
        return Cache::remember($cacheKey, 300, function () use ($tenant, $teamId) {
            // In a real implementation, check storage limits from database
            // For now, assume quota is available
            return true;
        });
    }

    /**
     * Check if file is being edited by another user
     */
    protected function isFileBeingEdited(string $path): bool
    {
        $cacheKey = "file_lock_{$path}";
        return Cache::has($cacheKey);
    }

    /**
     * Lock a file for editing
     */
    public function lockFile(User $user, string $path, int $durationMinutes = 30): bool
    {
        $cacheKey = "file_lock_{$path}";
        $lockData = [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'locked_at' => now()->toISOString(),
        ];
        
        return Cache::put($cacheKey, $lockData, now()->addMinutes($durationMinutes));
    }

    /**
     * Unlock a file
     */
    public function unlockFile(User $user, string $path): bool
    {
        $cacheKey = "file_lock_{$path}";
        $lockData = Cache::get($cacheKey);
        
        // Only the user who locked the file can unlock it
        if ($lockData && $lockData['user_id'] === $user->id) {
            Cache::forget($cacheKey);
            return true;
        }
        
        return false;
    }

    /**
     * Get file lock information
     */
    public function getFileLock(string $path): ?array
    {
        $cacheKey = "file_lock_{$path}";
        return Cache::get($cacheKey);
    }

    /**
     * Get allowed file extensions for upload
     */
    public function getAllowedExtensions(): array
    {
        return config('filesystems.tenant_buckets.allowed_extensions', []);
    }

    /**
     * Check if file extension is allowed
     */
    public function isExtensionAllowed(string $filename): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $allowedExtensions = $this->getAllowedExtensions();
        
        return in_array($extension, $allowedExtensions);
    }

    /**
     * Get maximum file size for upload
     */
    public function getMaxFileSize(): string
    {
        return config('filesystems.tenant_buckets.max_file_size', '100MB');
    }
} 