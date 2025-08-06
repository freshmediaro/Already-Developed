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
 * Order Notification Event - Real-time order event broadcasting
 *
 * This event handles real-time broadcasting of order-related notifications
 * to users and teams within the multi-tenant system. It provides immediate
 * updates for order status changes, shipping updates, and delivery confirmations.
 *
 * Key features:
 * - Real-time order status broadcasting
 * - Multi-tenant event isolation
 * - User and team-specific channels
 * - Comprehensive order data transmission
 * - Notification data generation
 * - Event type classification
 * - Timestamp tracking
 * - Priority-based notifications
 *
 * Supported event types:
 * - order_received: New order received notification
 * - order_shipped: Order shipped notification
 * - order_delivered: Order delivered notification
 * - order_cancelled: Order cancelled notification
 * - order_updated: Order updated notification
 * - order_refunded: Order refunded notification
 *
 * Broadcasting channels:
 * - User-specific private channels
 * - Team-specific order management channels
 * - Tenant-isolated channel namespaces
 * - Real-time WebSocket delivery
 *
 * The event provides:
 * - Real-time order updates
 * - Multi-tenant isolation
 * - User and team notifications
 * - Comprehensive order data
 * - Notification formatting
 * - Event classification
 *
 * @package App\Events
 * @since 1.0.0
 */
class OrderNotificationEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @var array Order data including ID, customer info, totals, etc. */
    public array $orderData;
    
    /** @var string Event type (order_received, order_shipped, order_delivered, order_cancelled) */
    public string $eventType;
    
    /** @var int User ID receiving the notification */
    public int $userId;
    
    /** @var int|null Team ID for team-specific notifications */
    public ?int $teamId;
    
    /** @var string|null Tenant ID for multi-tenant isolation */
    public ?string $tenantId;

    /**
     * Create a new order notification event instance
     *
     * This constructor initializes the event with order data, event type,
     * and user/team context for proper notification delivery.
     *
     * @param array $orderData Order information including ID, customer, totals, etc.
     * @param string $eventType Type of order event (order_received, order_shipped, etc.)
     * @param int $userId ID of the user to notify
     * @param int|null $teamId Optional team ID for team-specific notifications
     * @param string|null $tenantId Optional tenant ID for multi-tenant isolation
     */
    public function __construct(array $orderData, string $eventType, int $userId, ?int $teamId = null, ?string $tenantId = null)
    {
        $this->orderData = $orderData;
        $this->eventType = $eventType;
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

        // Team-specific channel for order management
        if ($this->teamId) {
            $teamChannel = $this->tenantId 
                ? "tenant.{$this->tenantId}.team.{$this->teamId}.orders"
                : "team.{$this->teamId}.orders";
            
            $channels[] = new PrivateChannel($teamChannel);
        }

        return $channels;
    }

    /**
     * Get the data to broadcast with the event
     *
     * This method provides the complete data payload for broadcasting,
     * including order information, event details, and notification data.
     *
     * @return array Complete event data for broadcasting
     */
    public function broadcastWith(): array
    {
        return [
            'order' => $this->orderData,
            'event_type' => $this->eventType,
            'user_id' => $this->userId,
            'team_id' => $this->teamId,
            'tenant_id' => $this->tenantId,
            'timestamp' => now()->toISOString(),
            'notification' => $this->getNotificationData(),
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
        return 'order.notification';
    }

    /**
     * Get notification data for this order event
     *
     * This method generates appropriate notification data based on the
     * event type, including titles, messages, icons, and priorities.
     *
     * @return array Notification data with title, message, icon, and priority
     */
    private function getNotificationData(): array
    {
        $orderId = $this->orderData['order_id'] ?? $this->orderData['id'] ?? 'Unknown';
        $customerName = $this->orderData['customer_name'] ?? 'Customer';
        $orderTotal = $this->orderData['total'] ?? $this->orderData['order_total'] ?? '0.00';

        return match ($this->eventType) {
            'order_received' => [
                'title' => 'New Order Received',
                'message' => "Order #{$orderId} from {$customerName} - {$orderTotal}",
                'icon' => 'fa-shopping-cart',
                'type' => 'success',
                'priority' => 'high',
                'category' => 'orders',
                'actions' => [
                    [
                        'label' => 'View Order',
                        'action' => 'view_order',
                        'data' => ['order_id' => $orderId],
                    ],
                    [
                        'label' => 'Process Order',
                        'action' => 'process_order',
                        'data' => ['order_id' => $orderId],
                    ],
                ],
            ],
            'order_shipped' => [
                'title' => 'Order Shipped',
                'message' => "Order #{$orderId} has been shipped",
                'icon' => 'fa-truck',
                'type' => 'info',
                'priority' => 'normal',
                'category' => 'orders',
                'actions' => [
                    [
                        'label' => 'Track Package',
                        'action' => 'track_order',
                        'data' => ['order_id' => $orderId],
                    ],
                ],
            ],
            'order_delivered' => [
                'title' => 'Order Delivered',
                'message' => "Order #{$orderId} has been delivered",
                'icon' => 'fa-check-circle',
                'type' => 'success',
                'priority' => 'normal',
                'category' => 'orders',
                'actions' => [
                    [
                        'label' => 'Request Review',
                        'action' => 'request_review',
                        'data' => ['order_id' => $orderId],
                    ],
                ],
            ],
            'order_cancelled' => [
                'title' => 'Order Cancelled',
                'message' => "Order #{$orderId} has been cancelled",
                'icon' => 'fa-times-circle',
                'type' => 'warning',
                'priority' => 'normal',
                'category' => 'orders',
                'actions' => [
                    [
                        'label' => 'View Details',
                        'action' => 'view_order',
                        'data' => ['order_id' => $orderId],
                    ],
                ],
            ],
            default => [
                'title' => 'Order Update',
                'message' => "Order #{$orderId} has been updated",
                'icon' => 'fa-info-circle',
                'type' => 'info',
                'priority' => 'normal',
                'category' => 'orders',
            ],
        };
    }
} 