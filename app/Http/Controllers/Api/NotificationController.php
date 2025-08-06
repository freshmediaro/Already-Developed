<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationSettings;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Notification Controller - Manages notification operations and user preferences
 *
 * This controller handles all notification-related operations including fetching,
 * managing, and configuring notifications for users and teams within the
 * multi-tenant system. It provides comprehensive notification management
 * with filtering, preferences, and delivery controls.
 *
 * Key features:
 * - Notification retrieval and filtering
 * - Read/unread status management
 * - Notification settings configuration
 * - Do-not-disturb mode control
 * - App-specific muting capabilities
 * - Notification statistics and analytics
 * - Test notification delivery
 * - Bulk notification operations
 *
 * Supported operations:
 * - Fetch notifications with filtering and pagination
 * - Mark notifications as read/unread
 * - Delete individual notifications
 * - Clear all notifications
 * - Configure notification preferences
 * - Toggle do-not-disturb mode
 * - Mute/unmute specific apps
 * - Send test notifications
 * - Get notification statistics
 *
 * The controller provides:
 * - RESTful API endpoints for notification management
 * - Comprehensive filtering and search capabilities
 * - User preference management
 * - Team-specific notification handling
 * - Security and access control
 * - Error handling and logging
 *
 * @package App\Http\Controllers\Api
 * @since 1.0.0
 */
class NotificationController extends Controller
{
    /** @var NotificationService Service for notification operations */
    protected NotificationService $notificationService;

    /**
     * Initialize the Notification Controller with dependencies
     *
     * @param NotificationService $notificationService Service for notification operations
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get notifications for the authenticated user with filtering and pagination
     *
     * This method retrieves notifications for the authenticated user with
     * comprehensive filtering options including category, priority, source app,
     * and read status. It supports pagination and team-specific filtering.
     *
     * Query parameters:
     * - team_id: Filter by specific team
     * - category: Filter by notification category
     * - priority: Filter by notification priority
     * - source_app: Filter by source application
     * - unread_only: Show only unread notifications
     * - per_page: Number of notifications per page
     *
     * @param Request $request The HTTP request with filtering parameters
     * @return JsonResponse Response containing notifications and pagination data
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $request->input('team_id', $user->currentTeam?->id);

            $notifications = Notification::where('user_id', $user->id)
                ->when($teamId, fn($query) => $query->where('team_id', $teamId))
                ->when($request->filled('category'), fn($query) => $query->where('category', $request->category))
                ->when($request->filled('priority'), fn($query) => $query->where('priority', $request->priority))
                ->when($request->filled('source_app'), fn($query) => $query->where('source_app', $request->source_app))
                ->when($request->boolean('unread_only'), fn($query) => $query->unread())
                ->notExpired()
                ->orderBy('created_at', 'desc')
                ->paginate($request->input('per_page', 20));

            $formattedNotifications = $notifications->getCollection()->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->getTitle(),
                    'message' => $notification->getMessage(),
                    'icon' => $notification->getIcon(),
                    'type' => $notification->type,
                    'priority' => $notification->priority,
                    'category' => $notification->category,
                    'source_app' => $notification->source_app,
                    'actions' => $notification->getActions(),
                    'action_url' => $notification->action_url,
                    'isRead' => $notification->read(),
                    'timestamp' => $notification->created_at->toISOString(),
                    'formatted_time' => $notification->getFormattedTime(),
                    'expires_at' => $notification->expires_at?->toISOString(),
                    'metadata' => $notification->metadata,
                ];
            });

            return response()->json([
                'notifications' => $formattedNotifications,
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'last_page' => $notifications->lastPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                ],
                'unread_count' => $this->notificationService->getUnreadCount($user->id, $teamId),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch notifications', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to fetch notifications',
                'message' => 'An error occurred while retrieving notifications.'
            ], 500);
        }
    }

    /**
     * Get notification settings for the authenticated user
     *
     * This method retrieves the current notification settings for the user,
     * including channel preferences, quiet hours, and category-specific
     * settings for both user and team contexts.
     *
     * @param Request $request The HTTP request (may include team_id)
     * @return JsonResponse Response containing notification settings
     */
    public function getSettings(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $request->input('team_id', $user->currentTeam?->id);

            $settings = NotificationSettings::getForUser($user->id, $teamId);

            return response()->json([
                'settings' => [
                    'desktop_enabled' => $settings->desktop_enabled,
                    'email_enabled' => $settings->email_enabled,
                    'sms_enabled' => $settings->sms_enabled,
                    'push_enabled' => $settings->push_enabled,
                    'sound_enabled' => $settings->sound_enabled,
                    'stacking_mode' => $settings->stacking_mode,
                    'do_not_disturb' => $settings->do_not_disturb,
                    'quiet_hours_enabled' => $settings->quiet_hours_enabled,
                    'quiet_hours_start' => $settings->quiet_hours_start?->format('H:i'),
                    'quiet_hours_end' => $settings->quiet_hours_end?->format('H:i'),
                    'notification_categories' => $settings->notification_categories,
                    'muted_apps' => $settings->muted_apps,
                    'email_frequency' => $settings->email_frequency,
                    'summary_email_enabled' => $settings->summary_email_enabled,
                    'security_alerts_enabled' => $settings->security_alerts_enabled,
                    'marketing_enabled' => $settings->marketing_enabled,
                ],
                'available_options' => [
                    'stacking_modes' => NotificationSettings::getStackingModes(),
                    'email_frequencies' => NotificationSettings::getEmailFrequencies(),
                    'categories' => NotificationSettings::getAvailableCategories(),
                    'channels' => Notification::getChannels(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch notification settings', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to fetch notification settings',
                'message' => 'An error occurred while retrieving notification settings.'
            ], 500);
        }
    }

    /**
     * Update notification settings for the authenticated user.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'team_id' => 'nullable|integer|exists:teams,id',
                'desktop_enabled' => 'boolean',
                'email_enabled' => 'boolean',
                'sms_enabled' => 'boolean',
                'push_enabled' => 'boolean',
                'sound_enabled' => 'boolean',
                'stacking_mode' => 'string|in:one,three,all',
                'do_not_disturb' => 'boolean',
                'quiet_hours_enabled' => 'boolean',
                'quiet_hours_start' => 'nullable|date_format:H:i',
                'quiet_hours_end' => 'nullable|date_format:H:i',
                'notification_categories' => 'array',
                'muted_apps' => 'array',
                'muted_apps.*' => 'string',
                'email_frequency' => 'string|in:immediate,daily,weekly,never',
                'summary_email_enabled' => 'boolean',
                'security_alerts_enabled' => 'boolean',
                'marketing_enabled' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $teamId = $request->input('team_id', $user->currentTeam?->id);

            $settings = NotificationSettings::getForUser($user->id, $teamId);
            
            $settings->update($validator->validated());

            return response()->json([
                'message' => 'Notification settings updated successfully',
                'settings' => $settings->refresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update notification settings', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'error' => 'Failed to update notification settings',
                'message' => 'An error occurred while updating notification settings.'
            ], 500);
        }
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $success = $this->notificationService->markAsRead($notificationId, $user->id);

            if (!$success) {
                return response()->json([
                    'error' => 'Notification not found',
                    'message' => 'The specified notification was not found or you do not have permission to access it.'
                ], 404);
            }

            return response()->json([
                'message' => 'Notification marked as read',
                'unread_count' => $this->notificationService->getUnreadCount($user->id, $user->currentTeam?->id),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to mark notification as read', [
                'user_id' => Auth::id(),
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to mark notification as read',
                'message' => 'An error occurred while updating the notification.'
            ], 500);
        }
    }

    /**
     * Mark a notification as unread.
     */
    public function markAsUnread(Request $request, string $notificationId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $notification = Notification::where('id', $notificationId)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                return response()->json([
                    'error' => 'Notification not found',
                    'message' => 'The specified notification was not found or you do not have permission to access it.'
                ], 404);
            }

            $notification->markAsUnread();

            return response()->json([
                'message' => 'Notification marked as unread',
                'unread_count' => $this->notificationService->getUnreadCount($user->id, $user->currentTeam?->id),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to mark notification as unread', [
                'user_id' => Auth::id(),
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to mark notification as unread',
                'message' => 'An error occurred while updating the notification.'
            ], 500);
        }
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $notificationId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $notification = Notification::where('id', $notificationId)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                return response()->json([
                    'error' => 'Notification not found',
                    'message' => 'The specified notification was not found or you do not have permission to access it.'
                ], 404);
            }

            $notification->delete();

            return response()->json([
                'message' => 'Notification deleted successfully',
                'unread_count' => $this->notificationService->getUnreadCount($user->id, $user->currentTeam?->id),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete notification', [
                'user_id' => Auth::id(),
                'notification_id' => $notificationId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to delete notification',
                'message' => 'An error occurred while deleting the notification.'
            ], 500);
        }
    }

    /**
     * Clear all notifications for the authenticated user.
     */
    public function clearAll(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $request->input('team_id', $user->currentTeam?->id);

            $deletedCount = $this->notificationService->clearAllNotifications($user->id, $teamId);

            return response()->json([
                'message' => "Successfully cleared {$deletedCount} notifications",
                'deleted_count' => $deletedCount,
                'unread_count' => 0,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to clear all notifications', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to clear notifications',
                'message' => 'An error occurred while clearing notifications.'
            ], 500);
        }
    }

    /**
     * Toggle do not disturb mode.
     */
    public function toggleDoNotDisturb(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $request->input('team_id', $user->currentTeam?->id);

            $settings = NotificationSettings::getForUser($user->id, $teamId);
            $newState = $settings->toggleDoNotDisturb();

            return response()->json([
                'message' => $newState ? 'Do not disturb enabled' : 'Do not disturb disabled',
                'do_not_disturb' => $newState,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle do not disturb', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to toggle do not disturb',
                'message' => 'An error occurred while updating the setting.'
            ], 500);
        }
    }

    /**
     * Mute/unmute an app.
     */
    public function toggleAppMute(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'app_id' => 'required|string',
                'team_id' => 'nullable|integer|exists:teams,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $teamId = $request->input('team_id', $user->currentTeam?->id);
            $appId = $request->input('app_id');

            $settings = NotificationSettings::getForUser($user->id, $teamId);
            
            if ($settings->isAppMuted($appId)) {
                $settings->unmuteApp($appId);
                $isMuted = false;
                $message = "App '{$appId}' unmuted";
            } else {
                $settings->muteApp($appId);
                $isMuted = true;
                $message = "App '{$appId}' muted";
            }

            return response()->json([
                'message' => $message,
                'app_id' => $appId,
                'is_muted' => $isMuted,
                'muted_apps' => $settings->fresh()->muted_apps,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle app mute', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'error' => 'Failed to toggle app mute',
                'message' => 'An error occurred while updating the app mute setting.'
            ], 500);
        }
    }

    /**
     * Send a test notification.
     */
    public function sendTest(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'message' => 'required|string|max:1000',
                'type' => 'string|in:info,success,warning,error',
                'priority' => 'string|in:low,normal,high,urgent',
                'category' => 'string',
                'channels' => 'array',
                'channels.*' => 'string|in:database,broadcast,email,sms,push',
                'team_id' => 'nullable|integer|exists:teams,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $teamId = $request->input('team_id', $user->currentTeam?->id);

            $notificationData = [
                'title' => $request->input('title'),
                'message' => $request->input('message'),
                'type' => $request->input('type', 'info'),
                'priority' => $request->input('priority', 'normal'),
                'category' => $request->input('category', 'system'),
                'source_app' => 'system',
                'icon' => 'fa-test-tube',
            ];

            $channels = $request->input('channels', ['database', 'broadcast']);

            $notification = $this->notificationService->sendNotification(
                $user->id,
                $notificationData,
                $teamId,
                $channels
            );

            if ($notification) {
                return response()->json([
                    'message' => 'Test notification sent successfully',
                    'notification_id' => $notification->id,
                    'channels' => $channels,
                ]);
            } else {
                return response()->json([
                    'message' => 'Test notification was not sent (blocked by user preferences)',
                    'channels' => $channels,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send test notification', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'error' => 'Failed to send test notification',
                'message' => 'An error occurred while sending the test notification.'
            ], 500);
        }
    }

    /**
     * Get notification statistics.
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $teamId = $request->input('team_id', $user->currentTeam?->id);

            $query = Notification::where('user_id', $user->id)
                ->when($teamId, fn($q) => $q->where('team_id', $teamId));

            $stats = [
                'total' => $query->count(),
                'unread' => $query->clone()->unread()->count(),
                'today' => $query->clone()->whereDate('created_at', today())->count(),
                'this_week' => $query->clone()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'by_category' => $query->clone()->selectRaw('category, COUNT(*) as count')->groupBy('category')->pluck('count', 'category'),
                'by_priority' => $query->clone()->selectRaw('priority, COUNT(*) as count')->groupBy('priority')->pluck('count', 'priority'),
                'by_source_app' => $query->clone()->selectRaw('source_app, COUNT(*) as count')->groupBy('source_app')->limit(10)->pluck('count', 'source_app'),
            ];

            return response()->json([
                'stats' => $stats,
                'last_updated' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch notification statistics', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to fetch notification statistics',
                'message' => 'An error occurred while retrieving notification statistics.'
            ], 500);
        }
    }
} 