<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Notification Model - Multi-channel notification system with tenant isolation
 *
 * This model represents notifications in the multi-tenant system, providing
 * comprehensive notification management with support for multiple channels,
 * priorities, categories, and tenant isolation. It extends Laravel's base
 * notification system with additional features for the desktop application.
 *
 * Key features:
 * - Multi-channel notification delivery (database, broadcast, email, SMS, push)
 * - Priority-based notification management (low, normal, high, urgent)
 * - Category-based organization (general, orders, payments, security, etc.)
 * - Tenant isolation and team-based filtering
 * - Expiration and read status tracking
 * - UUID-based notification identification
 * - Soft deletes for notification history
 * - Automatic tenant and user context injection
 *
 * The model supports:
 * - Real-time notifications via broadcasting
 * - Notification actions and clickable URLs
 * - Metadata storage for additional context
 * - Source app tracking for app-specific notifications
 * - Formatted timestamp display
 * - Global tenant isolation scoping
 *
 * @package App\Models
 * @since 1.0.0
 */
class Notification extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'user_id',
        'team_id',
        'tenant_id',
        'data',
        'read_at',
        'created_at',
        'updated_at',
        'priority',
        'category',
        'channel',
        'source_app',
        'action_url',
        'expires_at',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'metadata' => 'array',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /** @var string Primary key type for UUID support */
    protected $keyType = 'string';

    /** @var bool Disable auto-incrementing for UUID primary keys */
    public $incrementing = false;

    /**
     * Boot the model and set up automatic behaviors
     *
     * This method initializes the model with automatic UUID generation,
     * tenant context injection, and global tenant isolation scoping.
     * It ensures proper tenant isolation and automatic context setting.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate UUID for notifications
        static::creating(function ($notification) {
            if (empty($notification->id)) {
                $notification->id = (string) \Illuminate\Support\Str::uuid();
            }

            // Automatically set tenant context if not provided
            if (empty($notification->tenant_id) && tenant()) {
                $notification->tenant_id = tenant('id');
            }

            // Set team and user context from current session if not provided
            if (auth()->check() && empty($notification->user_id)) {
                $notification->user_id = auth()->id();
                
                if (auth()->user()->currentTeam && empty($notification->team_id)) {
                    $notification->team_id = auth()->user()->currentTeam->id;
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
     * Get the notifiable entity that the notification belongs to
     *
     * This relationship provides polymorphic access to the entity that
     * should receive the notification (User, Team, etc.).
     *
     * @return MorphTo Polymorphic relationship to notifiable entity
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user that owns the notification
     *
     * This relationship provides access to the user who should receive
     * or owns the notification.
     *
     * @return BelongsTo Relationship to User model
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team that owns the notification
     *
     * This relationship provides access to the team context for the
     * notification, enabling team-based notification filtering.
     *
     * @return BelongsTo Relationship to Team model
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Scope for unread notifications
     *
     * This scope filters notifications that have not been read by the user,
     * useful for displaying unread notification counts and indicators.
     *
     * @param Builder $query The query builder
     * @return Builder The filtered query for unread notifications
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope for read notifications
     *
     * This scope filters notifications that have been read by the user,
     * useful for displaying notification history and read status.
     *
     * @param Builder $query The query builder
     * @return Builder The filtered query for read notifications
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope for notifications by priority level
     *
     * This scope filters notifications by their priority level (low, normal,
     * high, urgent) for priority-based notification management.
     *
     * @param Builder $query The query builder
     * @param string $priority The priority level to filter by
     * @return Builder The filtered query for priority-based notifications
     */
    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for notifications by category
     *
     * This scope filters notifications by their category (general, orders,
     * payments, security, etc.) for category-based organization.
     *
     * @param Builder $query The query builder
     * @param string $category The category to filter by
     * @return Builder The filtered query for category-based notifications
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for notifications by source application
     *
     * This scope filters notifications by the application that generated
     * them, useful for app-specific notification management.
     *
     * @param Builder $query The query builder
     * @param string $app The source app to filter by
     * @return Builder The filtered query for app-specific notifications
     */
    public function scopeByApp(Builder $query, string $app): Builder
    {
        return $query->where('source_app', $app);
    }

    /**
     * Scope for notifications by team
     *
     * This scope filters notifications by team context, providing
     * team-based notification isolation and filtering.
     *
     * @param Builder $query The query builder
     * @param int $teamId The team ID to filter by
     * @return Builder The filtered query for team-specific notifications
     */
    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    /**
     * Scope for notifications by user
     *
     * This scope filters notifications by user, providing user-specific
     * notification filtering and personalization.
     *
     * @param Builder $query The query builder
     * @param int $userId The user ID to filter by
     * @return Builder The filtered query for user-specific notifications
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for non-expired notifications
     *
     * This scope filters notifications that have not expired, excluding
     * notifications with past expiration dates.
     *
     * @param Builder $query The query builder
     * @return Builder The filtered query for non-expired notifications
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Mark the notification as read
     *
     * This method updates the notification's read_at timestamp to mark
     * it as read by the user, enabling read status tracking.
     */
    public function markAsRead(): void
    {
        if (is_null($this->read_at)) {
            $this->forceFill(['read_at' => $this->freshTimestamp()])->save();
        }
    }

    /**
     * Mark the notification as unread
     *
     * This method clears the notification's read_at timestamp to mark
     * it as unread, useful for notification management and testing.
     */
    public function markAsUnread(): void
    {
        if (!is_null($this->read_at)) {
            $this->forceFill(['read_at' => null])->save();
        }
    }

    /**
     * Determine if a notification has been read
     *
     * This method checks whether the notification has been read by
     * examining the read_at timestamp.
     *
     * @return bool True if notification has been read, false otherwise
     */
    public function read(): bool
    {
        return $this->read_at !== null;
    }

    /**
     * Determine if a notification has not been read
     *
     * This method checks whether the notification has not been read by
     * examining the read_at timestamp.
     *
     * @return bool True if notification has not been read, false otherwise
     */
    public function unread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Get the notification's title from data array
     *
     * This method extracts the title from the notification's data array,
     * providing a fallback to 'Notification' if no title is specified.
     *
     * @return string The notification title
     */
    public function getTitle(): string
    {
        return $this->data['title'] ?? 'Notification';
    }

    /**
     * Get the notification's message from data array
     *
     * This method extracts the message content from the notification's
     * data array, providing an empty string fallback if no message is specified.
     *
     * @return string The notification message
     */
    public function getMessage(): string
    {
        return $this->data['message'] ?? '';
    }

    /**
     * Get the notification's icon from data array
     *
     * This method extracts the icon identifier from the notification's
     * data array, providing a default bell icon if no icon is specified.
     *
     * @return string The notification icon identifier
     */
    public function getIcon(): string
    {
        return $this->data['icon'] ?? 'fa-bell';
    }

    /**
     * Get notification actions from data array
     *
     * This method extracts the actions array from the notification's
     * data array, providing an empty array fallback if no actions are specified.
     *
     * @return array The notification actions array
     */
    public function getActions(): array
    {
        return $this->data['actions'] ?? [];
    }

    /**
     * Check if notification has expired
     *
     * This method checks whether the notification has passed its
     * expiration date, enabling automatic cleanup of expired notifications.
     *
     * @return bool True if notification has expired, false otherwise
     */
    public function hasExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Get formatted timestamp for display
     *
     * This method provides a human-readable timestamp for notification
     * display, showing relative time (e.g., "2h ago") for recent notifications
     * and absolute dates for older ones.
     *
     * @return string Formatted timestamp string
     */
    public function getFormattedTime(): string
    {
        $now = now();
        $diffInMinutes = $this->created_at->diffInMinutes($now);
        
        if ($diffInMinutes < 1) {
            return 'Just now';
        } elseif ($diffInMinutes < 60) {
            return $diffInMinutes . 'm ago';
        } elseif ($diffInMinutes < 1440) { // 24 hours
            return $this->created_at->diffInHours($now) . 'h ago';
        } elseif ($diffInMinutes < 10080) { // 7 days
            return $this->created_at->diffInDays($now) . 'd ago';
        } else {
            return $this->created_at->format('M j');
        }
    }

    /**
     * Create a new notification instance with automatic context
     *
     * This static method creates a new notification with automatic
     * context injection, including user, team, and tenant information.
     * It provides a convenient way to create notifications with proper
     * defaults and context.
     *
     * @param array $data Notification data including title, message, priority, etc.
     * @return self The created notification instance
     */
    public static function createNotification(array $data): self
    {
        return static::create([
            'type' => $data['type'] ?? 'info',
            'notifiable_type' => $data['notifiable_type'] ?? User::class,
            'notifiable_id' => $data['notifiable_id'] ?? auth()->id(),
            'user_id' => $data['user_id'] ?? auth()->id(),
            'team_id' => $data['team_id'] ?? auth()->user()?->currentTeam?->id,
            'data' => [
                'title' => $data['title'] ?? 'Notification',
                'message' => $data['message'] ?? '',
                'icon' => $data['icon'] ?? 'fa-bell',
                'actions' => $data['actions'] ?? [],
            ],
            'priority' => $data['priority'] ?? 'normal',
            'category' => $data['category'] ?? 'general',
            'channel' => $data['channel'] ?? 'database',
            'source_app' => $data['source_app'] ?? 'system',
            'action_url' => $data['action_url'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]);
    }

    /**
     * Get available notification categories
     *
     * This method provides a list of available notification categories
     * for organizing and filtering notifications by type.
     *
     * @return array Associative array of category keys and display names
     */
    public static function getCategories(): array
    {
        return [
            'general' => 'General',
            'orders' => 'Orders',
            'payments' => 'Payments',
            'security' => 'Security',
            'system' => 'System',
            'social' => 'Social',
            'marketing' => 'Marketing',
            'ai' => 'AI Assistant',
            'files' => 'Files',
            'apps' => 'Applications',
        ];
    }

    /**
     * Get available notification priorities
     *
     * This method provides a list of available notification priorities
     * for priority-based notification management and filtering.
     *
     * @return array Associative array of priority keys and display names
     */
    public static function getPriorities(): array
    {
        return [
            'low' => 'Low',
            'normal' => 'Normal',
            'high' => 'High',
            'urgent' => 'Urgent',
        ];
    }

    /**
     * Get available notification channels
     *
     * This method provides a list of available notification channels
     * for multi-channel notification delivery.
     *
     * @return array Associative array of channel keys and display names
     */
    public static function getChannels(): array
    {
        return [
            'database' => 'Database',
            'broadcast' => 'Real-time',
            'mail' => 'Email',
            'sms' => 'SMS',
            'push' => 'Push Notification',
            'slack' => 'Slack',
        ];
    }
} 