import { ref, computed, reactive } from 'vue'
import type { User, Team } from './Types'

// Define notification types
export interface NotificationData {
  id: string
  title: string
  message: string
  icon: string
  type: 'info' | 'success' | 'warning' | 'error'
  priority: 'low' | 'normal' | 'high' | 'urgent'
  category: string
  source_app?: string
  actions?: NotificationAction[]
  action_url?: string
  isRead: boolean
  timestamp: string
  formatted_time: string
  expires_at?: string
  metadata?: Record<string, any>
}

export interface NotificationAction {
  label: string
  action: string
  data?: Record<string, any>
  style?: 'primary' | 'secondary' | 'danger'
}

export interface NotificationSettings {
  desktop_enabled: boolean
  email_enabled: boolean
  sms_enabled: boolean
  push_enabled: boolean
  sound_enabled: boolean
  stacking_mode: 'one' | 'three' | 'all'
  do_not_disturb: boolean
  quiet_hours_enabled: boolean
  quiet_hours_start: string
  quiet_hours_end: string
  notification_categories: Record<string, boolean>
  muted_apps: string[]
  email_frequency: 'immediate' | 'daily' | 'weekly' | 'never'
  summary_email_enabled: boolean
  security_alerts_enabled: boolean
  marketing_enabled: boolean
}

class NotificationManager {
  private echo: any = null
  private isInitialized = false
  private currentUser: User | null = null
  private currentTeam: Team | null = null
  private tenantId: string | null = null

  // Reactive state
  public notifications = ref<NotificationData[]>([])
  public settings = ref<NotificationSettings | null>(null)
  public unreadCount = ref(0)
  public isLoading = ref(false)
  public error = ref<string | null>(null)

  // Sound configuration
  private notificationSounds = {
    info: '/sounds/notification-info.mp3',
    success: '/sounds/notification-success.mp3',
    warning: '/sounds/notification-warning.mp3',
    error: '/sounds/notification-error.mp3',
  }

  /**
   * Initialize the notification manager
   */
  async initialize(user: User, team: Team | null = null, tenantId: string | null = null): Promise<void> {
    try {
      this.currentUser = user
      this.currentTeam = team
      this.tenantId = tenantId

      // Initialize Laravel Echo
      await this.initializeEcho()

      // Load initial notifications and settings
      await Promise.all([
        this.loadNotifications(),
        this.loadSettings()
      ])

      // Set up real-time listeners
      this.setupRealtimeListeners()

      this.isInitialized = true
      console.log('NotificationManager initialized successfully')

    } catch (error) {
      console.error('Failed to initialize NotificationManager:', error)
      this.error.value = 'Failed to initialize notifications'
      throw error
    }
  }

  /**
   * Initialize Laravel Echo for real-time notifications
   */
  private async initializeEcho(): Promise<void> {
    try {
      // Import Laravel Echo dynamically
      const Echo = await import('laravel-echo')
      const Pusher = await import('pusher-js')

      // Configure Echo based on your broadcasting driver
      if (import.meta.env.VITE_BROADCAST_DRIVER === 'reverb') {
        this.echo = new Echo.default({
          broadcaster: 'reverb',
          key: import.meta.env.VITE_REVERB_APP_KEY,
          wsHost: import.meta.env.VITE_REVERB_HOST,
          wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
          wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
          forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
          enabledTransports: ['ws', 'wss'],
          auth: {
            headers: {
              Authorization: `Bearer ${this.getAuthToken()}`,
            },
          },
        })
      } else {
        // Fallback to Pusher
        this.echo = new Echo.default({
          broadcaster: 'pusher',
          key: import.meta.env.VITE_PUSHER_APP_KEY,
          cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
          wsHost: import.meta.env.VITE_PUSHER_HOST ? import.meta.env.VITE_PUSHER_HOST : `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher-channels.com`,
          wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
          wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
          forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
          enabledTransports: ['ws', 'wss'],
          auth: {
            headers: {
              Authorization: `Bearer ${this.getAuthToken()}`,
            },
          },
        })
      }

      console.log('Laravel Echo initialized for notifications')
    } catch (error) {
      console.error('Failed to initialize Laravel Echo:', error)
      throw error
    }
  }

  /**
   * Set up real-time notification listeners
   */
  private setupRealtimeListeners(): void {
    if (!this.echo || !this.currentUser) return

    // User-specific notifications
    const userChannelName = this.tenantId 
      ? `tenant.${this.tenantId}.user.${this.currentUser.id}`
      : `user.${this.currentUser.id}`

    this.echo.private(userChannelName)
      .listen('.notification.received', (event: any) => {
        this.handleNotificationReceived(event.notification)
      })

    // Team-specific notifications if applicable
    if (this.currentTeam) {
      const teamChannelName = this.tenantId 
        ? `tenant.${this.tenantId}.team.${this.currentTeam.id}`
        : `team.${this.currentTeam.id}`

      this.echo.private(teamChannelName)
        .listen('.notification.received', (event: any) => {
          this.handleNotificationReceived(event.notification)
        })

      // Order notifications for AIMEOS
      const orderChannelName = this.tenantId 
        ? `tenant.${this.tenantId}.team.${this.currentTeam.id}.orders`
        : `team.${this.currentTeam.id}.orders`

      this.echo.private(orderChannelName)
        .listen('.order.notification', (event: any) => {
          this.handleOrderNotificationReceived(event)
        })
    }

    console.log('Real-time notification listeners set up')
  }

  /**
   * Handle received notifications
   */
  private handleNotificationReceived(notification: NotificationData): void {
    try {
      // Check if notification should be displayed based on settings
      if (!this.settings.value) return // Added check for settings
      if (!this.shouldDisplayNotification(notification)) {
        return
      }

      // Add to notifications list
      this.notifications.value.unshift(notification)
      this.unreadCount.value++

      // Play notification sound if enabled
      if (this.settings.value?.sound_enabled && !this.settings.value?.do_not_disturb) {
        this.playNotificationSound(notification.type)
      }

      // Show desktop notification if enabled
      if (this.settings.value?.desktop_enabled && 'Notification' in window) {
        this.showDesktopNotification(notification)
      }

      // Emit event for UI components
      this.emitNotificationEvent('notification:received', notification)

      console.log('Notification received:', notification.title)
    } catch (error) {
      console.error('Error handling notification:', error)
    }
  }

  /**
   * Handle AIMEOS order notifications
   */
  private handleOrderNotificationReceived(event: any): void {
    try {
      const notification = event.notification
      
      // Add special handling for order notifications
      this.handleNotificationReceived({
        ...notification,
        category: 'orders',
        source_app: 'aimeos',
      })

      // Emit specific order event
      this.emitNotificationEvent('order:notification', event)

      console.log('Order notification received:', event.event_type, event.order)
    } catch (error) {
      console.error('Error handling order notification:', error)
    }
  }

  /**
   * Check if notification should be displayed
   */
  private shouldDisplayNotification(notification: NotificationData): boolean {
    if (!this.settings.value) return true

    // Check do not disturb mode
    if (this.settings.value.do_not_disturb) {
      // Only show urgent or security notifications during DND
      return notification.priority === 'urgent' || notification.category === 'security'
    }

    // Check category preferences
    const categoryEnabled = this.settings.value.notification_categories[notification.category] ?? true
    if (!categoryEnabled) return false

    // Check if app is muted
    if (notification.source_app && this.settings.value.muted_apps.includes(notification.source_app)) {
      return false
    }

    return true
  }

  /**
   * Play notification sound
   */
  private playNotificationSound(type: string): void {
    try {
      const soundUrl = this.notificationSounds[type as keyof typeof this.notificationSounds] || this.notificationSounds.info
      const audio = new Audio(soundUrl)
      audio.volume = 0.5 // Adjust volume as needed
      audio.play().catch(error => {
        console.warn('Failed to play notification sound:', error)
      })
    } catch (error) {
      console.warn('Error playing notification sound:', error)
    }
  }

  /**
   * Show browser desktop notification
   */
  private showDesktopNotification(notification: NotificationData): void {
    try {
      if (Notification.permission === 'granted') {
        const options = {
          body: notification.message,
          icon: '/img/square-logo.png', // Use your app icon
          tag: notification.id,
          data: notification,
          requireInteraction: notification.priority === 'urgent',
        }

        const desktopNotification = new Notification(notification.title, options)
        
        desktopNotification.onclick = () => {
          // Handle notification click
          this.handleNotificationClick(notification)
          desktopNotification.close()
        }

        // Auto close after 5 seconds unless urgent
        if (notification.priority !== 'urgent') {
          setTimeout(() => desktopNotification.close(), 5000)
        }
      }
    } catch (error) {
      console.warn('Error showing desktop notification:', error)
    }
  }

  /**
   * Load notifications from API
   */
  async loadNotifications(page = 1, filters: Record<string, any> = {}): Promise<void> {
    try {
      this.isLoading.value = true
      this.error.value = null

      const params = new URLSearchParams({
        page: page.toString(),
        per_page: '20',
        ...filters,
      })

      if (this.currentTeam) {
        params.set('team_id', this.currentTeam.id.toString())
      }

      const response = await fetch(`/api/notifications?${params}`, {
        headers: {
          'Authorization': `Bearer ${this.getAuthToken()}`,
          'Accept': 'application/json',
        },
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()
      
      if (page === 1) {
        this.notifications.value = data.notifications || []
      } else {
        this.notifications.value.push(...(data.notifications || []))
      }

      this.unreadCount.value = data.unread_count || 0

    } catch (error) {
      console.error('Failed to load notifications:', error)
      this.error.value = 'Failed to load notifications'
      throw error
    } finally {
      this.isLoading.value = false
    }
  }

  /**
   * Load notification settings
   */
  async loadSettings(): Promise<void> {
    try {
      const params = new URLSearchParams()
      if (this.currentTeam) {
        params.set('team_id', this.currentTeam.id.toString())
      }

      const response = await fetch(`/api/notifications/settings?${params}`, {
        headers: {
          'Authorization': `Bearer ${this.getAuthToken()}`,
          'Accept': 'application/json',
        },
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()
      this.settings.value = data.settings
    } catch (error) {
      console.error('Failed to load notification settings:', error)
      throw error
    }
  }

  /**
   * Update notification settings
   */
  async updateSettings(newSettings: Partial<NotificationSettings>): Promise<void> {
    try {
      const payload = {
        ...newSettings,
        team_id: this.currentTeam?.id,
      }

      const response = await fetch('/api/notifications/settings', {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${this.getAuthToken()}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(payload),
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()
      this.settings.value = { ...this.settings.value, ...data.settings }
    } catch (error) {
      console.error('Failed to update notification settings:', error)
      throw error
    }
  }

  /**
   * Mark notification as read
   */
  async markAsRead(notificationId: string): Promise<void> {
    try {
      const response = await fetch(`/api/notifications/${notificationId}/read`, {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${this.getAuthToken()}`,
          'Accept': 'application/json',
        },
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      // Update local state
      const notification = this.notifications.value.find(n => n.id === notificationId)
      if (notification && !notification.isRead) {
        notification.isRead = true
        this.unreadCount.value = Math.max(0, this.unreadCount.value - 1)
      }

      const data = await response.json()
      this.unreadCount.value = data.unread_count || 0
    } catch (error) {
      console.error('Failed to mark notification as read:', error)
      throw error
    }
  }

  /**
   * Clear all notifications
   */
  async clearAll(): Promise<void> {
    try {
      const params = new URLSearchParams()
      if (this.currentTeam) {
        params.set('team_id', this.currentTeam.id.toString())
      }

      const response = await fetch(`/api/notifications/clear-all?${params}`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${this.getAuthToken()}`,
          'Accept': 'application/json',
        },
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      // Clear local state
      this.notifications.value = []
      this.unreadCount.value = 0
    } catch (error) {
      console.error('Failed to clear notifications:', error)
      throw error
    }
  }

  /**
   * Toggle do not disturb mode
   */
  async toggleDoNotDisturb(): Promise<boolean> {
    try {
      const params = new URLSearchParams()
      if (this.currentTeam) {
        params.set('team_id', this.currentTeam.id.toString())
      }

      const response = await fetch(`/api/notifications/toggle-dnd?${params}`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${this.getAuthToken()}`,
          'Accept': 'application/json',
        },
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()
      if (this.settings.value) {
        this.settings.value.do_not_disturb = data.do_not_disturb
      }

      return data.do_not_disturb
    } catch (error) {
      console.error('Failed to toggle do not disturb:', error)
      throw error
    }
  }

  /**
   * Toggle app mute status
   */
  async toggleAppMute(appId: string): Promise<boolean> {
    try {
      const payload = {
        app_id: appId,
        team_id: this.currentTeam?.id,
      }

      const response = await fetch('/api/notifications/toggle-app-mute', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${this.getAuthToken()}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(payload),
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const data = await response.json()
      if (this.settings.value) {
        this.settings.value.muted_apps = data.muted_apps
      }

      return data.is_muted
    } catch (error) {
      console.error('Failed to toggle app mute:', error)
      throw error
    }
  }

  /**
   * Send test notification
   */
  async sendTestNotification(
    title: string, 
    message: string, 
    type: string = 'info'
  ): Promise<void> {
    try {
      const payload = {
        title,
        message,
        type,
        priority: 'normal',
        category: 'system',
        channels: ['database', 'broadcast'],
        team_id: this.currentTeam?.id,
      }

      const response = await fetch('/api/notifications/test', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${this.getAuthToken()}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json',
        },
        body: JSON.stringify(payload),
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      console.log('Test notification sent successfully')
    } catch (error) {
      console.error('Failed to send test notification:', error)
      throw error
    }
  }

  /**
   * Handle notification click
   */
  private handleNotificationClick(notification: NotificationData): void {
    // Mark as read if not already
    if (!notification.isRead) {
      this.markAsRead(notification.id)
    }

    // Handle action URL
    if (notification.action_url) {
      window.location.href = notification.action_url
      return
    }

    // Emit click event for custom handling
    this.emitNotificationEvent('notification:click', notification)
  }

  /**
   * Request desktop notification permission
   */
  async requestNotificationPermission(): Promise<boolean> {
    try {
      if (!('Notification' in window)) {
        console.warn('Desktop notifications not supported')
        return false
      }

      if (Notification.permission === 'granted') {
        return true
      }

      if (Notification.permission === 'denied') {
        return false
      }

      const permission = await Notification.requestPermission()
      return permission === 'granted'
    } catch (error) {
      console.error('Failed to request notification permission:', error)
      return false
    }
  }

  /**
   * Get authentication token
   */
  private getAuthToken(): string {
    // Get token from meta tag or local storage
    const token = document.querySelector('meta[name="api-token"]')?.getAttribute('content') ||
                  localStorage.getItem('auth_token') ||
                  sessionStorage.getItem('auth_token')
    
    if (!token) {
      throw new Error('Authentication token not found')
    }
    
    return token
  }

  /**
   * Emit notification event
   */
  private emitNotificationEvent(event: string, data: any): void {
    try {
      const customEvent = new CustomEvent(event, { detail: data })
      window.dispatchEvent(customEvent)
    } catch (error) {
      console.warn('Failed to emit notification event:', error)
    }
  }

  /**
   * Computed properties
   */
  get filteredNotifications() {
    return computed(() => {
      if (!this.settings.value) return this.notifications.value

      const { stacking_mode } = this.settings.value
      
      if (stacking_mode === 'one') {
        return this.notifications.value.slice(0, 1)
      } else if (stacking_mode === 'three') {
        return this.notifications.value.slice(0, 3)
      }
      
      return this.notifications.value
    })
  }

  get hasMoreNotifications() {
    return computed(() => {
      if (!this.settings.value) return false

      const { stacking_mode } = this.settings.value
      
      if (stacking_mode === 'one') {
        return this.notifications.value.length > 1
      } else if (stacking_mode === 'three') {
        return this.notifications.value.length > 3
      }
      
      return false
    })
  }

  /**
   * Cleanup
   */
  destroy(): void {
    try {
      if (this.echo) {
        this.echo.disconnect()
        this.echo = null
      }
      
      this.notifications.value = []
      this.settings.value = null
      this.unreadCount.value = 0
      this.isInitialized = false
      
      console.log('NotificationManager destroyed')
    } catch (error) {
      console.error('Error destroying NotificationManager:', error)
    }
  }
}

// Create singleton instance
export const notificationManager = new NotificationManager()

// Export types
export type { NotificationData, NotificationAction, NotificationSettings } 