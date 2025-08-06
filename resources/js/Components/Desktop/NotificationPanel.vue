<template>
  <div 
    v-show="show"
    class="notifications-panel"
    :class="{ 'notifications-visible': show }"
  >
    <!-- Panel Header -->
    <div class="notifications-panel-header">
      <button class="notifications-menu-toggle" aria-label="Menu">
        <i class="fas fa-bars"></i>
      </button>
      <div class="notifications-header-center">
        <i class="fas fa-bell"></i>
        <span>Notifications</span>
      </div>
      <button class="panel-close-btn" @click="$emit('close')" aria-label="Close">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <!-- Notifications Content -->
    <div class="notifications-panel-content">
      <!-- Notification Filters -->
      <div class="notification-filters">
        <button 
          v-for="filter in notificationFilters"
          :key="filter.key"
          class="filter-button"
          :class="{ 'filter-active': activeFilter === filter.key }"
          @click="setActiveFilter(filter.key)"
        >
          {{ filter.label }}
          <span v-if="filter.count > 0" class="filter-count">{{ filter.count }}</span>
        </button>
      </div>

      <!-- Notifications Content -->
      <div class="notification-content">
        <div v-if="filteredNotifications.length === 0" class="notification-empty">
          <BellSlashIcon class="w-12 h-12 text-gray-500 mb-4" />
          <p class="text-gray-500">No notifications</p>
          <p class="text-xs text-gray-400 mt-2">
            {{ activeFilter === 'all' ? 'You\'re all caught up!' : 'No notifications in this category.' }}
          </p>
        </div>

        <div v-else class="notifications-list">
          <div
            v-for="notification in displayedNotifications"
            :key="notification.id"
            :data-notif-id="notification.id"
            class="notification-item notif-card"
            :class="[
              `notification-${notification.type}`,
              { 'notification-read': notification.isRead, 'read': notification.isRead },
              { 'notification-priority': notification.priority === 'high' },
              { 'unread': !notification.isRead }
            ]"
            @click="handleNotificationClick(notification)"
          >
            <!-- Notification Icon -->
            <div class="notification-icon">
              <component :is="getNotificationIcon(notification.type)" class="notification-icon-image" />
            </div>

            <!-- Notification Content -->
            <div class="notification-body">
              <div class="notification-header">
                <span class="notification-title">{{ notification.title }}</span>
                <span class="notification-time">{{ formatTime(notification.timestamp) }}</span>
              </div>
              <p class="notification-message">{{ notification.message }}</p>
              <div v-if="notification.actions && notification.actions.length > 0" class="notification-actions">
                <button
                  v-for="action in notification.actions"
                  :key="action.id"
                  class="notification-action"
                  :class="[`action-${action.type}`]"
                  @click.stop="handleActionClick(notification, action)"
                >
                  {{ action.label }}
                </button>
              </div>
            </div>

            <!-- Notification Controls -->
            <div class="notification-controls">
              <button 
                class="notification-control notif-delete-btn"
                @click.stop="handleDeleteNotification(notification)"
                title="Delete notification"
              >
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Load More Button -->
        <div v-if="hasMoreNotifications" class="notification-load-more">
          <button @click="loadMoreNotifications" class="load-more-btn">
            Load More
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import {
  BellIcon,
  EyeSlashIcon,
  XMarkIcon,
  CheckIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  ChatBubbleLeftRightIcon,
  EllipsisVerticalIcon,
  ShieldCheckIcon
} from '@heroicons/vue/24/outline'

interface NotificationAction {
  id: string
  label: string
  type: 'primary' | 'secondary' | 'danger'
  action: string
}

interface Notification {
  id: string
  type: 'info' | 'success' | 'warning' | 'error' | 'message' | 'security'
  title: string
  message: string
  timestamp: Date
  isRead: boolean
  priority?: 'low' | 'normal' | 'high'
  actions?: NotificationAction[]
  category?: string
  source?: string
}

interface Props {
  show?: boolean
  notifications: Notification[]
  doNotDisturb?: boolean
  stackingMode?: 'one' | 'three' | 'all'
}

const props = withDefaults(defineProps<Props>(), {
  show: false,
  doNotDisturb: false,
  stackingMode: 'all'
})

const emit = defineEmits<{
  close: []
  notificationClick: [notification: Notification]
  notificationRead: [notification: Notification]
  notificationDelete: [notification: Notification]
  actionClick: [notification: Notification, action: NotificationAction]
  clearAll: []
  settingsClick: []
  viewAllClick: []
  doNotDisturbToggle: []
  showAllClick: []
}>()

// State
const activeFilter = ref('all')
const searchQuery = ref('')

// Computed
// Filter tabs computation
const filterTabs = computed(() => [
  { key: 'all', label: 'All', count: props.notifications.length },
  { key: 'unread', label: 'Unread', count: props.notifications.filter((n: any) => !n.isRead).length },
  { key: 'important', label: 'Important', count: props.notifications.filter((n: any) => n.priority === 'high').length }
])

// Filtered notifications
const filteredNotifications = computed(() => {
  let notifications = [...props.notifications]
  
  if (activeFilter.value === 'unread') {
    notifications = notifications.filter((n: any) => !n.isRead)
  } else if (activeFilter.value === 'important') {
    notifications = notifications.filter((n: any) => n.priority === 'high')
  }
  
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    notifications = notifications.filter((n: any) => 
      n.title.toLowerCase().includes(query) || 
      n.message.toLowerCase().includes(query)
    )
  }
  
  return notifications.sort((a: any, b: any) =>
    new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime()
  )
})

const displayedNotifications = computed(() => {
  let filtered = filteredNotifications.value
  
  if (props.stackingMode === 'one') {
    return filtered.slice(0, 1)
  } else if (props.stackingMode === 'three') {
    return filtered.slice(0, 3)
  }
  
  return filtered // 'all' mode
})

const hasMoreNotifications = computed(() => {
  const filtered = filteredNotifications.value
  if (props.stackingMode === 'one') {
    return filtered.length > 1
  } else if (props.stackingMode === 'three') {
    return filtered.length > 3
  }
  return false
})

const remainingCount = computed(() => {
  const filtered = filteredNotifications.value
  if (props.stackingMode === 'one') {
    return filtered.length - 1
  } else if (props.stackingMode === 'three') {
    return filtered.length - 3
  }
  return 0
})

// Icon mapping
const notificationIcons = {
  'info': InformationCircleIcon,
  'success': CheckIcon,
  'warning': ExclamationTriangleIcon,
  'error': ExclamationTriangleIcon,
  'message': ChatBubbleLeftRightIcon,
  'security': ShieldCheckIcon
}

// Methods
const getNotificationIcon = (type: string) => {
  return notificationIcons[type as keyof typeof notificationIcons] || InformationCircleIcon
}

const formatTime = (timestamp: Date) => {
  const now = new Date()
  const time = new Date(timestamp)
  const diffMs = now.getTime() - time.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMins / 60)
  const diffDays = Math.floor(diffHours / 24)
  
  if (diffMins < 1) {
    return 'Just now'
  } else if (diffMins < 60) {
    return `${diffMins}m ago`
  } else if (diffHours < 24) {
    return `${diffHours}h ago`
  } else if (diffDays === 1) {
    return 'Yesterday'
  } else if (diffDays < 7) {
    return `${diffDays}d ago`
  } else {
    return time.toLocaleDateString()
  }
}

const setActiveFilter = (filter: string) => {
  activeFilter.value = filter
}

const handleNotificationClick = (notification: Notification) => {
  emit('notificationClick', notification)
}

const toggleNotificationRead = (notification: Notification) => {
  emit('notificationRead', notification)
}

const handleDeleteNotification = (notification: Notification) => {
  emit('notificationDelete', notification)
}

const handleActionClick = (notification: Notification, action: NotificationAction) => {
  emit('actionClick', notification, action)
}

const handleClearAll = () => {
  if (props.notifications.length > 0) {
    emit('clearAll')
  }
}

const handleSettingsClick = () => {
  emit('settingsClick')
}

const handleViewAllClick = () => {
  emit('viewAllClick')
}

const handleDoNotDisturbClick = () => {
  emit('doNotDisturbToggle')
}

const handleShowAllClick = () => {
  // Emit event to show all notifications
  emit('show-all')
}

// Initialize notification swipe functionality
onMounted(async () => {
  await nextTick()
  
  // Import and initialize notification swipe manager
  const { notificationSwipeManager } = await import('@/Core/NotificationSwipeManager')
  notificationSwipeManager.setupSwipeForCards()
  
  // Emit event that notifications have been rendered
  import('@/Core/EventSystem').then(({ eventSystem }) => {
    eventSystem.emit('notifications:rendered', {})
  })
})
</script>

<style scoped>
.notifications-panel {
  position: fixed;
  top: 0;
  right: 0;
  width: 400px;
  max-width: 90vw;
  height: 100vh;
  background: rgba(31, 41, 55, 0.95);
  backdrop-filter: blur(20px);
  border-left: 1px solid rgba(255, 255, 255, 0.1);
  z-index: 1000;
  transform: translateX(100%);
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.notifications-visible {
  transform: translateX(0);
}

.notifications-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(17, 24, 39, 0.8);
}

.notifications-menu-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 6px;
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  transition: all 0.2s ease;
}

.notifications-menu-toggle:hover {
  background: rgba(255, 255, 255, 0.2);
  color: #ffffff;
}

.notifications-header-center {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #ffffff;
  font-size: 18px;
  font-weight: 600;
}

.panel-close-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 6px;
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  transition: all 0.2s ease;
}

.panel-close-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  color: #ffffff;
}

.notification-filters {
  display: flex;
  gap: 4px;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(17, 24, 39, 0.5);
}

.filter-button {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: rgba(55, 65, 81, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 20px;
  color: rgba(255, 255, 255, 0.7);
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.filter-button:hover {
  background: rgba(75, 85, 99, 0.7);
  color: #ffffff;
}

.filter-button.filter-active {
  background: rgba(59, 130, 246, 0.3);
  border-color: rgba(59, 130, 246, 0.5);
  color: #60a5fa;
}

.filter-count {
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 16px;
  height: 16px;
  background: rgba(59, 130, 246, 0.8);
  border-radius: 8px;
  color: #ffffff;
  font-size: 10px;
  font-weight: 600;
  padding: 0 4px;
}

.notification-content {
  flex: 1;
  overflow-y: auto;
  padding: 8px 0;
}

.notification-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  text-align: center;
}

.notifications-list {
  display: flex;
  flex-direction: column;
}

.notification-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  cursor: pointer;
  transition: background-color 0.2s ease;
  position: relative;
}

.notification-item:hover {
  background: rgba(55, 65, 81, 0.3);
}

.notification-item.notification-read {
  opacity: 0.7;
}

.notification-item.notification-priority::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  background: #ef4444;
}

.notification-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  flex-shrink: 0;
  margin-top: 2px;
}

.notification-info .notification-icon {
  background: rgba(59, 130, 246, 0.2);
  color: #60a5fa;
}

.notification-success .notification-icon {
  background: rgba(34, 197, 94, 0.2);
  color: #4ade80;
}

.notification-warning .notification-icon {
  background: rgba(245, 158, 11, 0.2);
  color: #fbbf24;
}

.notification-error .notification-icon {
  background: rgba(239, 68, 68, 0.2);
  color: #f87171;
}

.notification-message .notification-icon {
  background: rgba(168, 85, 247, 0.2);
  color: #a78bfa;
}

.notification-security .notification-icon {
  background: rgba(239, 68, 68, 0.2);
  color: #f87171;
}

.notification-icon-image {
  width: 18px;
  height: 18px;
}

.notification-body {
  flex: 1;
  min-width: 0;
}

.notification-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 4px;
}

.notification-title {
  color: #ffffff;
  font-size: 14px;
  font-weight: 600;
  line-height: 1.2;
  flex: 1;
  min-width: 0;
}

.notification-time {
  color: rgba(255, 255, 255, 0.5);
  font-size: 11px;
  flex-shrink: 0;
}

.notification-message {
  color: rgba(255, 255, 255, 0.8);
  font-size: 13px;
  line-height: 1.4;
  margin: 0 0 8px 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.notification-actions {
  display: flex;
  gap: 8px;
  margin-top: 8px;
}

.notification-action {
  padding: 4px 12px;
  border: 1px solid transparent;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.notification-action.action-primary {
  background: rgba(59, 130, 246, 0.2);
  border-color: rgba(59, 130, 246, 0.3);
  color: #60a5fa;
}

.notification-action.action-primary:hover {
  background: rgba(59, 130, 246, 0.3);
}

.notification-action.action-secondary {
  background: rgba(107, 114, 128, 0.2);
  border-color: rgba(107, 114, 128, 0.3);
  color: #9ca3af;
}

.notification-action.action-secondary:hover {
  background: rgba(107, 114, 128, 0.3);
}

.notification-action.action-danger {
  background: rgba(239, 68, 68, 0.2);
  border-color: rgba(239, 68, 68, 0.3);
  color: #f87171;
}

.notification-action.action-danger:hover {
  background: rgba(239, 68, 68, 0.3);
}

.notification-controls {
  display: flex;
  flex-direction: column;
  gap: 4px;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.notification-item:hover .notification-controls {
  opacity: 1;
}

.notification-control {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  background: rgba(55, 65, 81, 0.8);
  border: none;
  border-radius: 4px;
  color: rgba(255, 255, 255, 0.6);
  cursor: pointer;
  transition: all 0.2s ease;
}

.notification-control:hover {
  background: rgba(75, 85, 99, 0.8);
  color: #ffffff;
}

.notification-control.delete:hover {
  background: rgba(239, 68, 68, 0.8);
  color: #ffffff;
}

.notification-panel-footer {
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding: 16px 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(17, 24, 39, 0.8);
}

.footer-action {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 8px 16px;
  background: rgba(55, 65, 81, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  color: rgba(255, 255, 255, 0.7);
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.footer-action:hover {
  background: rgba(75, 85, 99, 0.7);
  color: #ffffff;
}

/* Light theme support */
.light-theme .notifications-panel {
  background: rgba(249, 250, 251, 0.95);
  border-left-color: rgba(0, 0, 0, 0.1);
}

.light-theme .notifications-panel-header,
.light-theme .notification-panel-footer {
  background: rgba(243, 244, 246, 0.8);
  border-color: rgba(0, 0, 0, 0.1);
}

.light-theme .panel-title {
  color: #1f2937;
}

.light-theme .notification-item {
  border-bottom-color: rgba(0, 0, 0, 0.05);
}

.light-theme .notification-title {
  color: #1f2937;
}

.light-theme .notification-message {
  color: rgba(31, 41, 55, 0.8);
}

/* Stacking notification styles */
.notifications-more {
  padding: 12px 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.show-more-btn {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 12px 16px;
  background: rgba(59, 130, 246, 0.1);
  border: 1px solid rgba(59, 130, 246, 0.2);
  border-radius: 8px;
  color: #60a5fa;
  cursor: pointer;
  transition: all 0.2s ease;
}

.show-more-btn:hover {
  background: rgba(59, 130, 246, 0.2);
  border-color: rgba(59, 130, 246, 0.3);
}

.more-text {
  font-size: 14px;
  font-weight: 500;
}

.more-icon {
  width: 16px;
  height: 16px;
  transition: transform 0.2s ease;
}

.show-more-btn:hover .more-icon {
  transform: translateX(4px);
}
</style> 