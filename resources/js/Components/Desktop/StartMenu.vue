<template>
  <div 
    v-show="show"
    class="start-menu"
    :class="{ 'start-menu-visible': show }"
    id="start-menu"
  >
    <!-- Main menu content: App Grid + Right Sidebar -->
    <div class="start-menu-body">
      <!-- Left Panel (App Grid) -->
      <div class="start-menu-left-panel">
        <div class="installed-apps-header">
          <h3>My Apps</h3>
          <button type="button" class="start-menu-category-link">
            <span class="sort-icon"></span> Alphabet
          </button>
        </div>
        
        <!-- Apps Grid Sections -->
        <div class="apps-grid-sections">
          <!-- Pinned Apps Section -->
          <div v-if="pinnedApps.length > 0" class="app-grid-section">
            <h4 class="section-title">Pinned</h4>
            <div class="app-grid">
              <div
                v-for="app in pinnedApps"
                :key="app.id"
                class="app-item pinned"
                @click="handleAppLaunch(app)"
                @contextmenu.prevent="handleAppContextMenu(app, $event)"
              >
                <div class="app-icon-bg" :class="getAppIconBackground(app)" :style="getAppIconStyle(app)">
                  <img 
                    v-if="app.iconType === 'image' && app.iconImage" 
                    :src="app.iconImage" 
                    :alt="app.name"
                    class="app-icon-image"
                  />
                  <i 
                    v-else-if="app.iconType === 'fontawesome' || !app.iconType" 
                    :class="app.icon || 'fas fa-puzzle-piece'"
                    class="app-icon"
                  ></i>
                  <span 
                    v-else-if="app.iconType === 'css'"
                    :class="app.icon"
                    class="app-icon app-icon-css"
                  ></span>
                </div>
                <span class="app-name">{{ app.name }}</span>
              </div>
            </div>
          </div>

          <!-- System Apps Section -->
          <div class="app-grid-section">
            <h4 class="section-title">System</h4>
            <div class="app-grid">
              <div
                v-for="app in systemApps"
                :key="app.id"
                class="app-item"
                @click="handleAppLaunch(app)"
                @contextmenu.prevent="handleAppContextMenu(app, $event)"
              >
                <div class="app-icon-bg" :class="getAppIconBackground(app)" :style="getAppIconStyle(app)">
                  <img 
                    v-if="app.iconType === 'image' && app.iconImage" 
                    :src="app.iconImage" 
                    :alt="app.name"
                    class="app-icon-image"
                  />
                  <i 
                    v-else-if="app.iconType === 'fontawesome' || !app.iconType" 
                    :class="app.icon || 'fas fa-puzzle-piece'"
                    class="app-icon"
                  ></i>
                  <span 
                    v-else-if="app.iconType === 'css'"
                    :class="app.icon"
                    class="app-icon app-icon-css"
                  ></span>
                </div>
                <span class="app-name">{{ app.name }}</span>
              </div>
            </div>
          </div>

          <!-- Business Apps Section -->
          <div v-if="businessApps.length > 0" class="app-grid-section">
            <h4 class="section-title">Business</h4>
            <div class="app-grid">
              <div
                v-for="app in businessApps"
                :key="app.id"
                class="app-item"
                @click="handleAppLaunch(app)"
                @contextmenu.prevent="handleAppContextMenu(app, $event)"
              >
                <div class="app-icon-bg" :class="getAppIconBackground(app)" :style="getAppIconStyle(app)">
                  <img 
                    v-if="app.iconType === 'image' && app.iconImage" 
                    :src="app.iconImage" 
                    :alt="app.name"
                    class="app-icon-image"
                  />
                  <i 
                    v-else-if="app.iconType === 'fontawesome' || !app.iconType" 
                    :class="app.icon || 'fas fa-puzzle-piece'"
                    class="app-icon"
                  ></i>
                  <span 
                    v-else-if="app.iconType === 'css'"
                    :class="app.icon"
                    class="app-icon app-icon-css"
                  ></span>
                </div>
                <span class="app-name">{{ app.name }}</span>
              </div>
            </div>
          </div>

          <!-- Productivity Apps Section -->
          <div v-if="productivityApps.length > 0" class="app-grid-section">
            <h4 class="section-title">Productivity</h4>
            <div class="app-grid">
              <div
                v-for="app in productivityApps"
                :key="app.id"
                class="app-item"
                @click="handleAppLaunch(app)"
                @contextmenu.prevent="handleAppContextMenu(app, $event)"
              >
                <div class="app-icon-bg" :class="getAppIconBackground(app)" :style="getAppIconStyle(app)">
                  <img 
                    v-if="app.iconType === 'image' && app.iconImage" 
                    :src="app.iconImage" 
                    :alt="app.name"
                    class="app-icon-image"
                  />
                  <i 
                    v-else-if="app.iconType === 'fontawesome' || !app.iconType" 
                    :class="app.icon || 'fas fa-puzzle-piece'"
                    class="app-icon"
                  ></i>
                  <span 
                    v-else-if="app.iconType === 'css'"
                    :class="app.icon"
                    class="app-icon app-icon-css"
                  ></span>
                </div>
                <span class="app-name">{{ app.name }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Sidebar -->
      <div class="start-menu-right-sidebar">
        <div class="start-menu-right-sidebar-header">
          <div class="start-menu-right-sidebar-header-left">
            <img 
              :src="userAvatar || '/img/avatar.png'" 
              alt="User Avatar" 
              class="site-logo-round start-menu-right-sidebar-avatar"
            >
            <span class="start-menu-right-sidebar-header-left-name">{{ userName }}</span>
            <span class="start-menu-right-sidebar-header-website-url">{{ websiteUrl }}</span>
          </div>
          <div class="start-menu-right-sidebar-header-right">
          </div>
        </div>

        <div class="start-menu-right-sidebar-body">
          <!-- Website Section -->
          <div class="menu-section">
            <h3>WEBSITE</h3>
            <ul class="menu-links-list">
              <li>
                <a href="#" class="menu-item" @click.prevent="handleMenuClick('apps')">
                  <span class="menu-icon menu-icon-rocket"></span>
                  <span>Installed Apps</span>
                  <span class="menu-chevron"></span>
                </a>
              </li>
              <li>
                <a href="#" class="menu-item" @click.prevent="handleMenuClick('featured')">
                  <span class="menu-icon menu-icon-box"></span>
                  <span>Featured Apps</span>
                </a>
              </li>
              <li>
                <a href="#" class="menu-item" @click.prevent="handleMenuClick('marketplace')">
                  <span class="menu-icon menu-icon-dollar"></span>
                  <span>Marketplace</span>
                  <span class="menu-chevron"></span>
                </a>
              </li>
              <li>
                <a href="#" class="menu-item" @click.prevent="handleMenuClick('analytics')">
                  <span class="menu-icon menu-icon-chart"></span>
                  <span>Analytics</span>
                  <span class="menu-chevron"></span>
                </a>
              </li>
              <li>
                <a href="#" class="menu-item" @click.prevent="handleMenuClick('campaigns')">
                  <span class="menu-icon menu-icon-bullhorn"></span>
                  <span>Campaigns</span>
                  <span class="menu-chevron"></span>
                </a>
              </li>
              <li>
                <a href="#" class="menu-item" @click.prevent="handleMenuClick('wallet')">
                  <span class="menu-icon menu-icon-wallet"></span>
                  <span>Wallet</span>
                </a>
              </li>
            </ul>
          </div>
          
          <!-- Settings Section -->
          <div class="menu-section">
            <h3>SETTINGS</h3>
            <ul class="menu-links-list">
              <li>
                <a href="#" class="menu-item" @click="handleQuickLaunch('settings')">
                  <i class="fas fa-cog"></i>
                  <span>Settings</span>
                  <i class="fas fa-chevron-right"></i>
                </a>
              </li>
              <li>
                <a href="#" class="menu-item" @click="handleQuickAction('apps-integrations')">
                  <i class="fas fa-cogs"></i>
                  <span>Apps Integrations</span>
                </a>
              </li>
              <li>
                <a href="#" class="menu-item" @click="handleQuickAction('notifications')">
                  <i class="fas fa-bell"></i>
                  <span>Notifications</span>
                </a>
              </li>
              <li>
                <a href="#" class="menu-item" @click="handleQuickAction('languages')">
                  <i class="fas fa-language"></i>
                  <span>Languages</span>
                </a>
              </li>
              <li>
                <a href="#" class="menu-item" @click="handleQuickAction('administrators')">
                  <i class="fas fa-users-cog"></i>
                  <span>Administrators</span>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Footer with Search and Logout -->
    <div class="start-menu-footer">
      <div class="start-menu-search-bar-bottom">
        <i class="fas fa-search search-icon-bottom"></i>
        <input 
          type="text" 
          id="start-menu-search-input-bottom" 
          class="start-menu-search-input-bottom" 
          placeholder="Search apps (windows key)"
          v-model="searchQuery"
          @input="handleSearch"
          @keydown.enter="handleSearchEnter"
        >
      </div>
      <button class="start-menu-logout-button" @click="handleLogout">
        <i class="fas fa-sign-out-alt"></i>
        <span>Log Out</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { appDataService, type UIAppData } from '@/Services/AppDataService'

interface Props {
  show: boolean
  currentTeam?: any
  allTeams?: any[]
  apps?: UIAppData[] // Optional, will use AppDataService if not provided
}

interface App extends UIAppData {
  isPinned?: boolean
  lastUsed?: Date
  iconType?: 'image' | 'fontawesome' | 'css'
  iconImage?: string
  iconBackground?: string // Added for dynamic background
}

const props = withDefaults(defineProps<Props>(), {
  show: false,
  currentTeam: undefined,
  allTeams: undefined,
  apps: undefined
})

const emit = defineEmits<{
  close: []
  appLaunch: [app: App]
  appPin: [app: App]
  appUnpin: [app: App]
  appContextMenu: [app: App, event: MouseEvent]
  userProfileClick: []
  teamSwitch: [team: any]
  settingsClick: []
  allAppsClick: []
  menuClick: [action: string]
  quickAction: [action: string]
}>()

// Reactive state
const searchQuery = ref('')
const page = usePage()

// Computed properties for user info
const userName = computed(() => {
  return page.props.auth?.user?.name || 'User'
})

const userAvatar = computed(() => {
  return page.props.auth?.user?.profile_photo_url || null
})

const websiteUrl = computed(() => {
  return page.props.tenant?.domains?.[0] || 'mywebsite.com'
})

// Get app data from service or props
const appData = computed(() => {
  if (props.apps) {
    // Use provided apps if available
    return {
      pinnedApps: props.apps.filter((app: UIAppData) => app.isPinned),
      systemApps: props.apps.filter((app: UIAppData) => 
        ['system', 'utilities'].includes(app.category || '') ||
        ['file-explorer', 'settings', 'calculator'].includes(app.id)
      ),
      businessApps: props.apps.filter((app: UIAppData) => 
        ['business', 'ecommerce'].includes(app.category || '') ||
        ['contacts', 'orders-manager', 'products-manager', 'point-of-sale'].includes(app.id)
      ),
      productivityApps: props.apps.filter((app: UIAppData) => 
        ['productivity', 'communication'].includes(app.category || '') ||
        ['email', 'calendar', 'messages', 'site-builder'].includes(app.id)
      ),
      allApps: props.apps
    }
  }
  // Use AppDataService for dynamic data
  return appDataService.getStartMenuData()
})

// App categorization
const pinnedApps = computed(() => {
  return appData.value.pinnedApps
})

const systemApps = computed(() => {
  return appData.value.systemApps
})

const businessApps = computed(() => {
  return appData.value.businessApps
})

const productivityApps = computed(() => {
  return appData.value.productivityApps
})

const filteredApps = computed(() => {
  let apps = [...appData.value.allApps]

  if (searchQuery.value?.trim()) {
    const query = searchQuery.value.toLowerCase()
    apps = apps.filter((app: any) => 
      app.name.toLowerCase().includes(query) ||
      app.description?.toLowerCase().includes(query)
    )
  }

  return apps.sort((a: any, b: any) => a.name.localeCompare(b.name))
})

// Dynamic app icon helpers (same as Taskbar)
const getAppIconBackground = (app: any) => {
  if (app.iconBackground) {
    // If it starts with #, it's a hex color
    if (app.iconBackground.startsWith('#')) {
      return ''
    }
    // Otherwise, it's a CSS class
    return app.iconBackground
  }
  // Default background color based on app category or fallback
  const defaultBackgrounds: Record<string, string> = {
    'system': 'blue-icon',
    'productivity': 'green-icon',
    'communication': 'cyan-icon',
    'business': 'orange-icon',
    'entertainment': 'purple-icon',
    'utilities': 'gray-icon'
  }
  return defaultBackgrounds[app.category || 'utilities'] || 'gray-icon'
}

const getAppIconStyle = (app: any) => {
  if (app.iconBackground?.startsWith('#')) {
    return {
      backgroundColor: app.iconBackground
    }
  }
  return {}
}

const formatLastUsed = (lastUsed?: Date) => {
  if (!lastUsed) return ''
  
  const now = new Date()
  const used = new Date(lastUsed)
  const diffMs = now.getTime() - used.getTime()
  const diffMins = Math.floor(diffMs / 60000)
  const diffHours = Math.floor(diffMins / 60)
  const diffDays = Math.floor(diffHours / 24)
  
  if (diffMins < 60) {
    return `${diffMins}m ago`
  } else if (diffHours < 24) {
    return `${diffHours}h ago`
  } else if (diffDays === 1) {
    return 'Yesterday'
  } else if (diffDays < 7) {
    return `${diffDays}d ago`
  } else {
    return used.toLocaleDateString()
  }
}

const handleSearch = () => {
  // Search is handled reactively through computed property
}

const handleSearchEnter = () => {
  if (filteredApps.value.length > 0) {
    handleAppLaunch(filteredApps.value[0])
  }
}

const handleAppLaunch = (app: App) => {
  emit('appLaunch', app)
  emit('close')
}

const handleAppContextMenu = (app: App, event: MouseEvent) => {
  // Could emit context menu event
  console.log('App context menu:', app, event)
}

const handlePinApp = (app: App) => {
  emit('appPin', app)
}

const handleUnpinApp = (app: App) => {
  emit('appUnpin', app)
}

const handleMenuClick = (action: string) => {
  emit('menuClick', action)
  emit('close')
}

const handleQuickAction = (action: string) => {
  emit('quickAction', action)
  emit('close')
}

const handleSettingsClick = () => {
  emit('settingsClick')
  emit('close')
}

const handleQuickLaunch = (appId: string) => {
  // Find the app and launch it
  const app = appData.value.allApps.find((a: UIAppData) => a.id === appId)
  if (app) {
    handleAppLaunch(app)
  }
}

const handleLogout = () => {
  // Handle logout
  window.location.href = '/logout'
}

const handleAllAppsClick = () => {
  emit('allAppsClick')
  emit('close')
}
</script>

<style scoped>
.start-menu {
  position: fixed;
  bottom: 48px;
  left: 16px;
  width: 360px;
  max-height: 640px;
  background: rgba(31, 41, 55, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
  z-index: 1000;
  transform: translateY(100%) scale(0.95);
  opacity: 0;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.start-menu-visible {
  transform: translateY(0) scale(1);
  opacity: 1;
}

.start-menu-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(17, 24, 39, 0.8);
}

.start-menu-user {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  overflow: hidden;
  background: rgba(59, 130, 246, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.user-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.user-avatar-placeholder {
  color: #60a5fa;
  font-weight: 600;
  font-size: 14px;
}

.user-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.user-name {
  color: #ffffff;
  font-weight: 600;
  font-size: 14px;
}

.user-role {
  color: rgba(255, 255, 255, 0.7);
  font-size: 12px;
}

.start-menu-close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 6px;
  color: #ffffff;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.start-menu-close:hover {
  background: rgba(255, 255, 255, 0.2);
}

.start-menu-search {
  position: relative;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.search-icon {
  position: absolute;
  left: 32px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: rgba(255, 255, 255, 0.5);
}

.search-input {
  width: 100%;
  padding: 8px 12px 8px 36px;
  background: rgba(55, 65, 81, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  color: #ffffff;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s ease;
}

.search-input:focus {
  border-color: rgba(59, 130, 246, 0.5);
}

.search-input::placeholder {
  color: rgba(255, 255, 255, 0.5);
}

.start-menu-quick-actions {
  display: flex;
  gap: 8px;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.quick-action {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: rgba(55, 65, 81, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  transition: all 0.2s ease;
}

.quick-action:hover {
  background: rgba(75, 85, 99, 0.8);
  color: #ffffff;
  transform: translateY(-1px);
}

.quick-action-icon {
  width: 20px;
  height: 20px;
}

.start-menu-content {
  flex: 1;
  overflow-y: auto;
  padding: 16px 20px;
}

.apps-section {
  margin-bottom: 24px;
}

.apps-section:last-child {
  margin-bottom: 0;
}

.section-title {
  color: #ffffff;
  font-size: 14px;
  font-weight: 600;
  margin: 0 0 12px 0;
}

.apps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.app-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 12px;
  background: rgba(55, 65, 81, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

.app-item:hover {
  background: rgba(75, 85, 99, 0.7);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.app-icon-bg {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 6px;
  overflow: hidden;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

.app-icon {
  width: 20px;
  height: 20px;
  color: #60a5fa; /* Default color for FontAwesome/CSS icons */
}

.app-icon-css {
  font-size: 18px; /* Adjust size for CSS icons */
}

.app-name {
  color: #ffffff;
  font-size: 11px;
  text-align: center;
  line-height: 1.2;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.app-unpin {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 16px;
  height: 16px;
  background: rgba(239, 68, 68, 0.8);
  border: none;
  border-radius: 50%;
  color: #ffffff;
  cursor: pointer;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.app-item:hover .app-unpin {
  opacity: 1;
}

.apps-list {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.app-item-list {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  background: rgba(55, 65, 81, 0.3);
  border-radius: 6px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.app-item-list:hover {
  background: rgba(75, 85, 99, 0.5);
}

.app-item-list.recent {
  background: rgba(34, 197, 94, 0.1);
  border: 1px solid rgba(34, 197, 94, 0.2);
}

.app-icon-small {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(59, 130, 246, 0.2);
  border-radius: 4px;
  flex-shrink: 0;
}

.app-icon-small .app-icon-image {
  width: 14px;
  height: 14px;
  color: #60a5fa;
}

.app-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.app-details .app-name {
  color: #ffffff;
  font-size: 13px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.app-description,
.app-last-used {
  color: rgba(255, 255, 255, 0.6);
  font-size: 11px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.app-pin {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  background: rgba(59, 130, 246, 0.2);
  border: none;
  border-radius: 4px;
  color: #60a5fa;
  cursor: pointer;
  opacity: 0;
  transition: all 0.2s ease;
  flex-shrink: 0;
}

.app-item-list:hover .app-pin {
  opacity: 1;
}

.app-pin:hover {
  background: rgba(59, 130, 246, 0.3);
}

.start-menu-footer {
  display: flex;
  gap: 8px;
  padding: 16px 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(17, 24, 39, 0.8);
}

.footer-action {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 8px 12px;
  background: rgba(55, 65, 81, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 6px;
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 12px;
}

.footer-action:hover {
  background: rgba(75, 85, 99, 0.7);
  color: #ffffff;
}

/* Light theme support */
.light-theme .start-menu {
  background: rgba(249, 250, 251, 0.95);
  border-color: rgba(0, 0, 0, 0.1);
}

.light-theme .start-menu-header {
  background: rgba(243, 244, 246, 0.8);
  border-bottom-color: rgba(0, 0, 0, 0.1);
}

.light-theme .user-name,
.light-theme .section-title {
  color: #1f2937;
}

.light-theme .search-input {
  background: rgba(255, 255, 255, 0.8);
  border-color: rgba(0, 0, 0, 0.1);
  color: #1f2937;
}

.light-theme .app-item,
.light-theme .app-item-list {
  background: rgba(255, 255, 255, 0.8);
  border-color: rgba(0, 0, 0, 0.1);
}

.light-theme .app-details .app-name {
  color: #1f2937;
}
</style> 