<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * Notification Settings Model - Manages user notification preferences and delivery settings
 *
 * This model represents notification settings for users within the multi-tenant
 * system. It manages notification channels, categories, quiet hours, and delivery
 * preferences for comprehensive notification management.
 *
 * Key features:
 * - Multi-channel notification delivery (desktop, email, SMS, push)
 * - Category-based notification filtering
 * - Quiet hours and do-not-disturb mode
 * - App-specific muting capabilities
 * - Email frequency and summary settings
 * - Security and marketing notification controls
 * - Tenant isolation and team-specific settings
 * - Stacking mode for notification display
 *
 * Supported channels:
 * - desktop: In-app desktop notifications
 * - email: Email notifications with frequency control
 * - sms: SMS notifications (requires phone number)
 * - push: Push notifications for mobile devices
 * - broadcast: Real-time broadcasting
 *
 * Notification categories:
 * - general: General system notifications
 * - orders: Order and transaction updates
 * - payments: Payment processing notifications
 * - security: Security alerts and warnings
 * - system: System maintenance and updates
 * - social: Social interactions and mentions
 * - marketing: Marketing communications
 * - ai: AI assistant interactions
 * - files: File operation notifications
 * - apps: Application-specific notifications
 *
 * The model provides:
 * - Channel-specific notification control
 * - Category-based filtering
 * - Time-based quiet hours
 * - App-specific muting
 * - Email frequency management
 * - Privacy and marketing controls
 *
 * @package App\Models
 * @since 1.0.0
 */
class NotificationSettings extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'team_id',
        'tenant_id',
        'desktop_enabled',
        'email_enabled',
        'sms_enabled',
        'push_enabled',
        'sound_enabled',
        'stacking_mode',
        'do_not_disturb',
        'quiet_hours_enabled',
        'quiet_hours_start',
        'quiet_hours_end',
        'notification_categories',
        'muted_apps',
        'email_frequency',
        'summary_email_enabled',
        'security_alerts_enabled',
        'marketing_enabled',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'desktop_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'sound_enabled' => 'boolean',
        'do_not_disturb' => 'boolean',
        'quiet_hours_enabled' => 'boolean',
        'quiet_hours_start' => 'datetime:H:i',
        'quiet_hours_end' => 'datetime:H:i',
        'notification_categories' => 'array',
        'muted_apps' => 'array',
        'summary_email_enabled' => 'boolean',
        'security_alerts_enabled' => 'boolean',
        'marketing_enabled' => 'boolean',
    ];

    /**
     * Boot the model and set up automatic behaviors
     *
     * This method initializes the model with automatic tenant context
     * injection and global tenant isolation scoping.
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set tenant context
        static::creating(function ($settings) {
            if (empty($settings->tenant_id) && tenant()) {
                $settings->tenant_id = tenant('id');
            }

            if (auth()->check() && empty($settings->user_id)) {
                $settings->user_id = auth()->id();
                
                if (auth()->user()->currentTeam && empty($settings->team_id)) {
                    $settings->team_id = auth()->user()->currentTeam->id;
                }
            }
        });

        // Global scope for tenant isolation
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (tenant()) {
                $builder->where('tenant_id', tenant('id'));
            }
        });
    }

    /**
     * Get the user who owns these notification settings
     *
     * This relationship provides access to the user who owns
     * the notification settings, enabling user-specific preference management.
     *
     * @return BelongsTo Relationship to User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team context for these notification settings
     *
     * This relationship provides access to the team context for
     * the notification settings, enabling team-specific preference management.
     *
     * @return BelongsTo Relationship to Team model
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope for filtering settings by user
     *
     * This scope filters notification settings by user ID, providing
     * user-specific configuration access and management.
     *
     * @param Builder $query The query builder
     * @param int $userId The user ID to filter by
     * @return Builder The filtered query
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering settings by team
     *
     * This scope filters notification settings by team ID, providing
     * team-specific configuration access and management.
     *
     * @param Builder $query The query builder
     * @param int $teamId The team ID to filter by
     * @return Builder The filtered query
     */
    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Get default notification settings for new users
     *
     * This method provides the default configuration values for new
     * notification settings, ensuring consistent initial preferences
     * across all users and teams.
     *
     * Default settings include:
     * - Enabled desktop, email, and push notifications
     * - Disabled SMS notifications
     * - Three-notification stacking mode
     * - Disabled do-not-disturb and quiet hours
     * - All categories enabled except marketing
     * - Immediate email frequency
     * - Enabled security alerts
     *
     * @return array Associative array of default notification settings
     */
    public static function getDefaults(): array
    {
        return [
            'desktop_enabled' => true,
            'email_enabled' => true,
            'sms_enabled' => false,
            'push_enabled' => true,
            'sound_enabled' => true,
            'stacking_mode' => 'three', // one, three, all
            'do_not_disturb' => false,
            'quiet_hours_enabled' => false,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '08:00',
            'notification_categories' => [
                'general' => true,
                'orders' => true,
                'payments' => true,
                'security' => true,
                'system' => true,
                'social' => true,
                'marketing' => false,
                'ai' => true,
                'files' => true,
                'apps' => true,
            ],
            'muted_apps' => [],
            'email_frequency' => 'immediate', // immediate, daily, weekly, never
            'summary_email_enabled' => true,
            'security_alerts_enabled' => true,
            'marketing_enabled' => false,
        ];
    }

    /**
     * Get or create notification settings for a user and team
     *
     * This method ensures that every user and team combination has
     * notification settings by creating default settings if they don't exist.
     * It provides a convenient way to access notification configuration.
     *
     * @param int $userId The user ID to get settings for
     * @param int|null $teamId Optional team ID for team-specific settings
     * @return self The notification settings instance (created or existing)
     */
    public static function getForUser(int $userId, ?int $teamId = null): self
    {
        return static::firstOrCreate(
            [
                'user_id' => $userId,
                'team_id' => $teamId,
            ],
            static::getDefaults()
        );
    }

    /**
     * Check if notifications are enabled for a specific channel
     *
     * This method validates whether notifications are enabled for the
     * specified delivery channel, including broadcast channel mapping.
     *
     * @param string $channel The notification channel to check
     * @return bool True if the channel is enabled, false otherwise
     */
    public function isChannelEnabled(string $channel): bool
    {
        return match ($channel) {
            'desktop' => $this->desktop_enabled,
            'email' => $this->email_enabled,
            'sms' => $this->sms_enabled,
            'push' => $this->push_enabled,
            'broadcast' => $this->desktop_enabled,
            default => false,
        };
    }

    /**
     * Check if notifications are enabled for a specific category
     *
     * This method validates whether notifications are enabled for the
     * specified category, defaulting to true if not explicitly configured.
     *
     * @param string $category The notification category to check
     * @return bool True if the category is enabled, false otherwise
     */
    public function isCategoryEnabled(string $category): bool
    {
        $categories = $this->notification_categories ?? [];
        return $categories[$category] ?? true;
    }

    /**
     * Check if an app is muted for notifications
     *
     * This method validates whether notifications from a specific app
     * are muted, preventing notification delivery from that app.
     *
     * @param string $appId The app ID to check for muting
     * @return bool True if the app is muted, false otherwise
     */
    public function isAppMuted(string $appId): bool
    {
        $mutedApps = $this->muted_apps ?? [];
        return in_array($appId, $mutedApps);
    }

    /**
     * Check if currently in quiet hours period
     *
     * This method determines whether the current time falls within
     * the configured quiet hours, including overnight periods.
     *
     * @return bool True if currently in quiet hours, false otherwise
     */
    public function isInQuietHours(): bool
    {
        if (!$this->quiet_hours_enabled) {
            return false;
        }

        $now = now()->format('H:i');
        $start = $this->quiet_hours_start?->format('H:i') ?? '22:00';
        $end = $this->quiet_hours_end?->format('H:i') ?? '08:00';

        // Handle overnight quiet hours (e.g., 22:00 to 08:00)
        if ($start > $end) {
            return $now >= $start || $now <= $end;
        }

        return $now >= $start && $now <= $end;
    }

    /**
     * Check if do not disturb mode is currently active
     *
     * This method determines whether do-not-disturb mode is active,
     * either through manual activation or quiet hours.
     *
     * @return bool True if do-not-disturb is active, false otherwise
     */
    public function isDoNotDisturbActive(): bool
    {
        return $this->do_not_disturb || $this->isInQuietHours();
    }

    /**
     * Mute notifications from a specific app
     *
     * This method adds an app to the muted apps list, preventing
     * notification delivery from that app.
     *
     * @param string $appId The app ID to mute
     */
    public function muteApp(string $appId): void
    {
        $mutedApps = $this->muted_apps ?? [];
        if (!in_array($appId, $mutedApps)) {
            $mutedApps[] = $appId;
            $this->update(['muted_apps' => $mutedApps]);
        }
    }

    /**
     * Unmute notifications from a specific app
     *
     * This method removes an app from the muted apps list, allowing
     * notification delivery from that app to resume.
     *
     * @param string $appId The app ID to unmute
     */
    public function unmuteApp(string $appId): void
    {
        $mutedApps = $this->muted_apps ?? [];
        $mutedApps = array_filter($mutedApps, fn($app) => $app !== $appId);
        $this->update(['muted_apps' => array_values($mutedApps)]);
    }

    /**
     * Enable or disable a notification category
     *
     * This method updates the enabled status for a specific notification
     * category, allowing fine-grained control over notification types.
     *
     * @param string $category The notification category to update
     * @param bool $enabled Whether the category should be enabled
     */
    public function setCategoryEnabled(string $category, bool $enabled): void
    {
        $categories = $this->notification_categories ?? [];
        $categories[$category] = $enabled;
        $this->update(['notification_categories' => $categories]);
    }

    /**
     * Toggle do not disturb mode
     *
     * This method toggles the do-not-disturb mode on or off,
     * returning the new state for immediate feedback.
     *
     * @return bool The new do-not-disturb state
     */
    public function toggleDoNotDisturb(): bool
    {
        $newState = !$this->do_not_disturb;
        $this->update(['do_not_disturb' => $newState]);
        return $newState;
    }

    /**
     * Get available stacking modes for notification display
     *
     * This method provides the available options for notification
     * stacking behavior in the desktop interface.
     *
     * @return array Associative array of stacking mode keys and display names
     */
    public static function getStackingModes(): array
    {
        return [
            'one' => 'Show 1 notification',
            'three' => 'Show 3 notifications',
            'all' => 'Show all notifications',
        ];
    }

    /**
     * Get available email frequency options
     *
     * This method provides the available options for email notification
     * frequency, allowing users to control email delivery timing.
     *
     * @return array Associative array of frequency keys and display names
     */
    public static function getEmailFrequencies(): array
    {
        return [
            'immediate' => 'Immediate',
            'daily' => 'Daily summary',
            'weekly' => 'Weekly summary',
            'never' => 'Never',
        ];
    }

    /**
     * Get available notification categories
     *
     * This method provides the list of available notification categories
     * with their display names for UI presentation and user selection.
     *
     * @return array Associative array of category keys and display names
     */
    public static function getAvailableCategories(): array
    {
        return [
            'general' => 'General notifications',
            'orders' => 'Order updates',
            'payments' => 'Payment notifications',
            'security' => 'Security alerts',
            'system' => 'System notifications',
            'social' => 'Social interactions',
            'marketing' => 'Marketing communications',
            'ai' => 'AI Assistant',
            'files' => 'File operations',
            'apps' => 'Application notifications',
        ];
    }
} 