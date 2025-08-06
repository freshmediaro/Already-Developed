<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Notification Event - Real-time notification broadcasting
 *
 * This event handles real-time broadcasting of notifications to users and teams
 * within the multi-tenant system. It provides immediate delivery of system
 * notifications, alerts, and updates through WebSocket channels.
 *
 * Key features:
 * - Real-time notification broadcasting
 * - Multi-tenant event isolation
 * - User and team-specific channels
 * - Comprehensive notification data transmission
 * - Timestamp tracking
 * - Priority-based delivery
 * - Channel-based routing
 * - WebSocket delivery
 *
 * Broadcasting channels:
 * - User-specific private channels
 * - Team-specific channels
 * - Tenant-isolated channel namespaces
 * - Real-time WebSocket delivery
 *
 * The event provides:
 * - Real-time notification delivery
 * - Multi-tenant isolation
 * - User and team notifications
 * - Comprehensive notification data
 * - Event classification
 * - Timestamp tracking
 *
 * @package App\Events
 * @since 1.0.0
 */
class NotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var array Notification data including title, message, type, priority, etc. */
    public array $notification;
    
    /** @var int User ID receiving the notification */
    public int $userId;
    
    /** @var int|null Team ID for team-specific notifications */
    public ?int $teamId;
    
    /** @var string|null Tenant ID for multi-tenant isolation */
    public ?string $tenantId;

    /**
     * Create a new notification event instance
     *
     * This constructor initializes the event with notification data and
     * user/team context for proper notification delivery.
     *
     * @param array $notification Notification data including title, message, type, etc.
     * @param int $userId ID of the user to notify
     * @param int|null $teamId Optional team ID for team-specific notifications
     * @param string|null $tenantId Optional tenant ID for multi-tenant isolation
     */
    public function __construct(array $notification, int $userId, ?int $teamId = null, ?string $tenantId = null)
    {
        $this->notification = $notification;
        $this->userId = $userId;
        $this->teamId = $teamId;
        $this->tenantId = $tenantId ?? tenant('id');
    }

    /**
     * Get the channels the event should broadcast on
     *
     * This method determines the appropriate broadcasting channels based on
     * user, team, and tenant context, ensuring proper notification delivery.
     *
     * @return array Array of broadcasting channels
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // User-specific channel
        $userChannel = $this->tenantId 
            ? "tenant.{$this->tenantId}.user.{$this->userId}"
            : "user.{$this->userId}";
        
        $channels[] = new PrivateChannel($userChannel);

        // Team-specific channel if team is set
        if ($this->teamId) {
            $teamChannel = $this->tenantId 
                ? "tenant.{$this->tenantId}.team.{$this->teamId}"
                : "team.{$this->teamId}";
            
            $channels[] = new PrivateChannel($teamChannel);
        }

        return $channels;
    }

    /**
     * Get the data to broadcast with the event
     *
     * This method provides the complete data payload for broadcasting,
     * including notification information, user context, and timestamp.
     *
     * @return array Complete event data for broadcasting
     */
    public function broadcastWith(): array
    {
        return [
            'notification' => $this->notification,
            'user_id' => $this->userId,
            'team_id' => $this->teamId,
            'tenant_id' => $this->tenantId,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Get the event's broadcast name
     *
     * This method returns the unique broadcast name for this event type,
     * used for event identification and routing.
     *
     * @return string The broadcast event name
     */
    public function broadcastAs(): string
    {
        return 'notification.received';
    }
} 