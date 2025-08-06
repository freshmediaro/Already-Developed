<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Rawilk\Settings\Support\Context;
use Rawilk\Settings\Facades\Settings;
use Exception;

/**
 * Settings Controller - Manages application settings and configuration
 *
 * This controller handles all application settings including user preferences,
 * team configurations, and system-wide settings within the multi-tenant system.
 * It provides comprehensive settings management with validation and context awareness.
 *
 * Key features:
 * - Settings retrieval and management
 * - Group-based settings organization
 * - Tenant-aware settings context
 * - Settings validation and security
 * - Settings reset and restoration
 * - Individual and bulk settings operations
 * - Settings groups and categories
 * - Context-aware settings isolation
 *
 * Supported operations:
 * - Get all settings or specific groups
 * - Update individual or multiple settings
 * - Get specific setting by key
 * - Set specific setting by key
 * - Reset settings to defaults
 * - Get available settings groups
 * - Validate settings data
 * - Context-aware settings management
 *
 * Settings groups:
 * - general: General application settings
 * - security: Security and privacy settings
 * - notifications: Notification preferences
 * - appearance: UI and appearance settings
 * - integrations: Third-party integrations
 * - performance: Performance and optimization
 *
 * The controller provides:
 * - RESTful API endpoints for settings management
 * - Comprehensive validation and error handling
 * - Tenant isolation and context awareness
 * - Group-based settings organization
 * - Security and access control
 * - Settings backup and restoration
 *
 * @package App\Http\Controllers\Api
 * @since 1.0.0
 */
class SettingsController extends Controller
{
    /**
     * Get all settings or specific group of settings
     *
     * This method retrieves settings for the authenticated user and team,
     * supporting both all settings and group-specific retrieval. It ensures
     * proper tenant context and isolation for settings access.
     *
     * Query parameters:
     * - team_id: Team context for settings
     * - group: Specific settings group to retrieve
     *
     * @param Request $request The HTTP request with optional group parameter
     * @return JsonResponse Response containing settings and context information
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);
            $group = $request->get('group');

            // Set context for tenant-aware settings
            $this->setSettingsContext($user->id, $teamId);

            if ($group) {
                // Get settings for specific group
                $settings = $this->getSettingsGroup($group);
            } else {
                // Get all settings
                $settings = $this->getAllSettings();
            }

            return response()->json([
                'success' => true,
                'data' => $settings,
                'context' => [
                    'user_id' => $user->id,
                    'team_id' => $teamId,
                    'tenant_id' => tenant('id'),
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get settings', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'team_id' => $request->get('team_id'),
                'group' => $request->get('group'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve settings'
            ], 500);
        }
    }

    /**
     * Update multiple settings at once
     *
     * This method updates multiple settings in a single operation, providing
     * comprehensive validation and ensuring proper tenant context for all
     * settings modifications.
     *
     * Request parameters:
     * - settings: Array of settings to update
     * - team_id: Team context for settings
     *
     * @param Request $request The HTTP request with settings data
     * @return JsonResponse Response indicating success or failure
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);
            $settings = $request->get('settings', []);

            // Validate the settings input
            $validator = Validator::make($request->all(), [
                'settings' => 'required|array',
                'settings.*' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid settings data',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Set context for tenant-aware settings
            $this->setSettingsContext($user->id, $teamId);

            // Validate individual settings
            $validationErrors = $this->validateSettings($settings);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Settings validation failed',
                    'errors' => $validationErrors
                ], 422);
            }

            // Update settings
            foreach ($settings as $key => $value) {
                Settings::set($key, $value);
            }

            Log::info('Settings updated', [
                'user_id' => $user->id,
                'team_id' => $teamId,
                'settings_updated' => array_keys($settings),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => $this->getAllSettings()
            ]);
        } catch (Exception $e) {
            Log::error('Failed to update settings', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'team_id' => $request->get('team_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update settings'
            ], 500);
        }
    }

    /**
     * Get a specific setting value
     */
    public function get(Request $request, string $key): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);

            // Set context for tenant-aware settings
            $this->setSettingsContext($user->id, $teamId);

            $value = Settings::get($key);

            return response()->json([
                'success' => true,
                'data' => [
                    'key' => $key,
                    'value' => $value,
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get setting', [
                'error' => $e->getMessage(),
                'key' => $key,
                'user_id' => Auth::id(),
                'team_id' => $request->get('team_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve setting'
            ], 500);
        }
    }

    /**
     * Set a specific setting value
     */
    public function set(Request $request, string $key): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);

            $validator = Validator::make($request->all(), [
                'value' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Value is required',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Set context for tenant-aware settings
            $this->setSettingsContext($user->id, $teamId);

            // Validate the specific setting
            $validationErrors = $this->validateSettings([$key => $request->get('value')]);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Setting validation failed',
                    'errors' => $validationErrors
                ], 422);
            }

            Settings::set($key, $request->get('value'));

            Log::info('Setting updated', [
                'user_id' => $user->id,
                'team_id' => $teamId,
                'key' => $key,
                'value' => $request->get('value'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully',
                'data' => [
                    'key' => $key,
                    'value' => Settings::get($key),
                ]
            ]);
        } catch (Exception $e) {
            Log::error('Failed to set setting', [
                'error' => $e->getMessage(),
                'key' => $key,
                'user_id' => Auth::id(),
                'team_id' => $request->get('team_id'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to update setting'
            ], 500);
        }
    }

    /**
     * Reset settings to defaults
     */
    public function reset(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $this->getTeamIdFromRequest($request);
            $group = $request->get('group');

            // Set context for tenant-aware settings
            $this->setSettingsContext($user->id, $teamId);

            if ($group) {
                // Reset specific group
                $this->resetSettingsGroup($group);
            } else {
                // Reset all settings
                $this->resetAllSettings();
            }

            Log::info('Settings reset', [
                'user_id' => $user->id,
                'team_id' => $teamId,
                'group' => $group,
            ]);

            return response()->json([
                'success' => true,
                'message' => $group ? "Settings group '{$group}' reset to defaults" : 'All settings reset to defaults',
                'data' => $group ? $this->getSettingsGroup($group) : $this->getAllSettings()
            ]);
        } catch (Exception $e) {
            Log::error('Failed to reset settings', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'team_id' => $request->get('team_id'),
                'group' => $request->get('group'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to reset settings'
            ], 500);
        }
    }

    /**
     * Get available settings groups
     */
    public function groups(): JsonResponse
    {
        try {
            $groups = config('settings.groups', []);

            return response()->json([
                'success' => true,
                'data' => array_keys($groups)
            ]);
        } catch (Exception $e) {
            Log::error('Failed to get settings groups', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve settings groups'
            ], 500);
        }
    }

    /**
     * Set settings context for tenant awareness
     */
    protected function setSettingsContext(int $userId, ?int $teamId): void
    {
        $context = new Context([
            'user_id' => $userId,
            'team_id' => $teamId,
            'tenant_id' => tenant('id'),
        ]);

        Settings::context($context);
    }

    /**
     * Get team ID from request
     */
    protected function getTeamIdFromRequest(Request $request): ?int
    {
        $teamId = $request->get('team_id');
        
        if ($teamId && Auth::user()->belongsToTeam(Team::find($teamId))) {
            return (int) $teamId;
        }

        // Return current team ID if available
        return Auth::user()->currentTeam?->id;
    }

    /**
     * Get all settings organized by groups
     */
    protected function getAllSettings(): array
    {
        $groups = config('settings.groups', []);
        $result = [];

        foreach ($groups as $groupName => $settingKeys) {
            $result[$groupName] = [];
            foreach ($settingKeys as $key) {
                $result[$groupName][$key] = Settings::get($key);
            }
        }

        return $result;
    }

    /**
     * Get settings for a specific group
     */
    protected function getSettingsGroup(string $group): array
    {
        $groups = config('settings.groups', []);
        
        if (!isset($groups[$group])) {
            throw new Exception("Settings group '{$group}' not found");
        }

        $result = [];
        foreach ($groups[$group] as $key) {
            $result[$key] = Settings::get($key);
        }

        return $result;
    }

    /**
     * Validate settings against defined rules
     */
    protected function validateSettings(array $settings): array
    {
        $validationRules = config('settings.validation', []);
        $errors = [];

        foreach ($settings as $key => $value) {
            if (isset($validationRules[$key])) {
                $validator = Validator::make(
                    [$key => $value],
                    [$key => $validationRules[$key]]
                );

                if ($validator->fails()) {
                    $errors[$key] = $validator->errors()->first($key);
                }
            }
        }

        return $errors;
    }

    /**
     * Reset all settings to defaults
     */
    protected function resetAllSettings(): void
    {
        $defaults = config('settings.defaults', []);
        
        foreach ($defaults as $key => $value) {
            Settings::set($key, $value);
        }
    }

    /**
     * Reset settings group to defaults
     */
    protected function resetSettingsGroup(string $group): void
    {
        $groups = config('settings.groups', []);
        $defaults = config('settings.defaults', []);
        
        if (!isset($groups[$group])) {
            throw new Exception("Settings group '{$group}' not found");
        }

        foreach ($groups[$group] as $key) {
            if (isset($defaults[$key])) {
                Settings::set($key, $defaults[$key]);
            }
        }
    }
} 