<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\DesktopConfiguration;
use App\Models\InstalledApp;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Desktop Controller - Manages desktop application interface and configuration
 *
 * This controller handles all desktop-related operations including configuration
 * management, app installation, notification handling, and desktop interface
 * customization within the multi-tenant system.
 *
 * Key features:
 * - Desktop configuration management
 * - App installation and management
 * - Notification handling and status
 * - Desktop customization and theming
 * - App usage tracking and analytics
 * - Multi-tenant desktop isolation
 * - User preference management
 * - Desktop layout management
 * - Notification management
 * - App lifecycle management
 *
 * Desktop configuration includes:
 * - Theme and appearance settings
 * - Wallpaper and desktop customization
 * - Icon size and arrangement
 * - Notification and sound preferences
 * - Language and localization
 * - Analytics and crash reporting
 * - Window animations and effects
 * - Desktop layout preferences
 *
 * Supported operations:
 * - Get and update desktop configuration
 * - Install and uninstall applications
 * - Manage notification status
 * - Track app usage and analytics
 * - Customize desktop appearance
 * - Manage app positioning
 * - Handle notification interactions
 * - Update app preferences
 *
 * The controller provides:
 * - RESTful API endpoints for desktop management
 * - Comprehensive configuration handling
 * - App lifecycle management
 * - Notification system integration
 * - Multi-tenant isolation
 * - User preference persistence
 * - Desktop customization tools
 *
 * @package App\Http\Controllers\Tenant
 * @since 1.0.0
 */
class DesktopController extends Controller
{
    /**
     * Get desktop configuration for current user and team
     *
     * This method retrieves the desktop configuration for the authenticated
     * user and current team, creating default configuration if none exists.
     * It ensures proper tenant isolation and user-specific preferences.
     *
     * @param Request $request The HTTP request
     * @return JsonResponse Response containing desktop configuration
     */
    public function getConfiguration(Request $request): JsonResponse
    {
        $user = Auth::user();
        $teamId = $user->currentTeam->id;

        $config = DesktopConfiguration::where('user_id', $user->id)
            ->where('team_id', $teamId)
            ->first();

        if (!$config) {
            // Create default configuration
            $config = DesktopConfiguration::create(array_merge(
                DesktopConfiguration::getDefaults(),
                [
                    'user_id' => $user->id,
                    'team_id' => $teamId,
                ]
            ));
        }

        return response()->json($config);
    }

    /**
     * Update desktop configuration for current user and team
     *
     * This method updates the desktop configuration with user preferences,
     * including theme settings, appearance customization, and desktop
     * behavior preferences. It validates all configuration parameters.
     *
     * Request parameters:
     * - theme: Desktop theme (auto, light, dark)
     * - accent_color: Accent color for UI elements
     * - wallpaper: Desktop wallpaper path
     * - desktop_icons_enabled: Whether desktop icons are enabled
     * - desktop_auto_arrange: Whether icons auto-arrange
     * - desktop_icon_size: Icon size (small, medium, large)
     * - notifications_enabled: Whether notifications are enabled
     * - sound_enabled: Whether sound effects are enabled
     * - language: Interface language
     * - analytics_enabled: Whether analytics are enabled
     * - crash_reports_enabled: Whether crash reports are enabled
     * - window_animations: Whether window animations are enabled
     * - transparency_effects: Whether transparency effects are enabled
     * - desktop_layout: Desktop layout configuration array
     *
     * @param Request $request The HTTP request with configuration data
     * @return JsonResponse Response containing updated configuration
     */
    public function updateConfiguration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme' => ['string', Rule::in(['auto', 'light', 'dark'])],
            'accent_color' => 'string|max:50',
            'wallpaper' => 'string|max:255',
            'desktop_icons_enabled' => 'boolean',
            'desktop_auto_arrange' => 'boolean',
            'desktop_icon_size' => ['string', Rule::in(['small', 'medium', 'large'])],
            'notifications_enabled' => 'boolean',
            'sound_enabled' => 'boolean',
            'language' => 'string|max:10',
            'analytics_enabled' => 'boolean',
            'crash_reports_enabled' => 'boolean',
            'window_animations' => 'boolean',
            'transparency_effects' => 'boolean',
            'desktop_layout' => 'array',
        ]);

        $user = Auth::user();
        $teamId = $user->currentTeam->id;

        $config = DesktopConfiguration::updateOrCreate(
            [
                'user_id' => $user->id,
                'team_id' => $teamId,
            ],
            $validated
        );

        return response()->json($config);
    }

    /**
     * Get installed apps for current team
     *
     * This method retrieves all active installed applications for the
     * current team, providing app information for desktop display
     * and management.
     *
     * @param Request $request The HTTP request
     * @return JsonResponse Response containing installed apps
     */
    public function getInstalledApps(Request $request): JsonResponse
    {
        $user = Auth::user();
        $teamId = $user->currentTeam->id;

        $apps = InstalledApp::where('team_id', $teamId)
            ->where('is_active', true)
            ->orderBy('app_name')
            ->get();

        return response()->json($apps);
    }

    /**
     * Install a new app for the current team
     *
     * This method installs a new application for the current team,
     * including app configuration, desktop positioning, and
     * permission validation.
     *
     * Request parameters:
     * - app_id: The app store application ID to install
     * - desktop_position: Desktop positioning coordinates
     * - taskbar_pinned: Whether to pin to taskbar
     * - desktop_visible: Whether to show on desktop
     *
     * @param Request $request The HTTP request with installation data
     * @return JsonResponse Response indicating installation success or failure
     */
    public function installApp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'app_id' => 'required|string|max:100',
            'app_name' => 'required|string|max:255',
            'app_type' => ['required', Rule::in(['vue', 'iframe', 'external', 'system'])],
            'icon' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:500',
            'iframe_config' => 'nullable|array',
            'position_x' => 'nullable|integer',
            'position_y' => 'nullable|integer',
            'desktop_visible' => 'boolean',
            'pinned_to_taskbar' => 'boolean',
            'permissions' => 'nullable|array',
        ]);

        $user = Auth::user();
        $team = $user->currentTeam;

        // Check if team can install more apps
        if (!$team->canInstallApps()) {
            return response()->json([
                'error' => 'App quota exceeded. Cannot install more apps.'
            ], 403);
        }

        // Check if app is already installed
        $existingApp = InstalledApp::where('team_id', $team->id)
            ->where('app_id', $validated['app_id'])
            ->first();

        if ($existingApp) {
            return response()->json([
                'error' => 'App is already installed.'
            ], 409);
        }

        // Set defaults for iframe apps
        if ($validated['app_type'] === 'iframe' && empty($validated['iframe_config'])) {
            $validated['iframe_config'] = InstalledApp::getDefaultIframeConfig();
        }

        $app = InstalledApp::create(array_merge($validated, [
            'team_id' => $team->id,
            'installed_by' => $user->id,
        ]));

        return response()->json($app, 201);
    }

    /**
     * Update app configuration
     */
    public function updateApp(Request $request, InstalledApp $app): JsonResponse
    {
        $user = Auth::user();
        
        // Ensure app belongs to current team
        if ($app->team_id !== $user->currentTeam->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'app_name' => 'string|max:255',
            'icon' => 'nullable|string|max:255',
            'url' => 'nullable|url|max:500',
            'iframe_config' => 'nullable|array',
            'is_active' => 'boolean',
            'position_x' => 'nullable|integer',
            'position_y' => 'nullable|integer',
            'desktop_visible' => 'boolean',
            'pinned_to_taskbar' => 'boolean',
            'permissions' => 'nullable|array',
        ]);

        $app->update($validated);

        return response()->json($app);
    }

    /**
     * Uninstall an app
     */
    public function uninstallApp(Request $request, InstalledApp $app): JsonResponse
    {
        $user = Auth::user();
        
        // Ensure app belongs to current team
        if ($app->team_id !== $user->currentTeam->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $app->delete();

        return response()->json(['message' => 'App uninstalled successfully']);
    }

    /**
     * Get notifications for current user
     */
    public function getNotifications(Request $request): JsonResponse
    {
        $user = Auth::user();
        $teamId = $user->currentTeam->id;

        $notifications = Notification::where('user_id', $user->id)
            ->where(function ($query) use ($teamId) {
                $query->where('team_id', $teamId)
                      ->orWhereNull('team_id');
            })
            ->active()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json($notifications);
    }

    /**
     * Mark notification as read
     */
    public function markNotificationRead(Request $request, Notification $notification): JsonResponse
    {
        $user = Auth::user();
        
        // Ensure notification belongs to current user
        if ($notification->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();

        return response()->json($notification);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $user = Auth::user();
        $teamId = $user->currentTeam->id;

        Notification::where('user_id', $user->id)
            ->where(function ($query) use ($teamId) {
                $query->where('team_id', $teamId)
                      ->orWhereNull('team_id');
            })
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    /**
     * Update app last used timestamp
     */
    public function updateAppUsage(Request $request, InstalledApp $app): JsonResponse
    {
        $user = Auth::user();
        
        // Ensure app belongs to current team
        if ($app->team_id !== $user->currentTeam->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $app->update(['last_used_at' => now()]);

        return response()->json(['message' => 'App usage updated']);
    }
} 