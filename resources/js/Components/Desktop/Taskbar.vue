<template>
  <div 
    class="taskbar"
    :class="[
      `taskbar-${taskbarStyle}`,
      {
        'taskbar-windows11-style': taskbarStyle === 'windows11',
        'taskbar-icons-left': taskbarStyle === 'left' || taskbarStyle === 'text',
        'taskbar-text-mode': taskbarStyle === 'text'
      }
    ]"
    @contextmenu.prevent="handleTaskbarContextMenu"
  >
    <!-- Start Button -->
    <button
      class="start-button"
      :class="{ active: showStartMenu }"
      @click="$emit('startClick')"
      @contextmenu.prevent="handleStartButtonContextMenu"
      title="Start"
    >
      <img src="/img/square-logo.png" alt="Start" class="start-icon" />
    </button>

    <!-- Search Bar -->
    <div 
      v-if="showSearchBar"
      class="search-container"
    >
      <div class="search-wrapper">
        <MagnifyingGlassIcon class="search-icon" />
        <input
          ref="searchInput"
          v-model="searchQuery"
          type="text"
          placeholder="Search"
          class="search-input"
          @keyup.enter="handleSearch"
        />
      </div>
    </div>

    <!-- App Icons -->
    <div class="taskbar-app-icons" :class="{ 'with-text': taskbarStyle === 'text' }">
      <button
        v-for="app in taskbarApps"
        :key="app.id"
        class="taskbar-app-icon"
        :class="{ 
          active: app.isActive,
          'has-notification': app.hasNotification,
          'opened-app': app.isActive || app.hasNotification
        }"
        @click="$emit('appClick', app)"
        :title="app.title"
      >
        <div class="icon-container" :class="getAppIconBackground(app)" :style="getAppIconStyle(app)">
          <img 
            v-if="app.iconType === 'image' && app.iconImage" 
            :src="app.iconImage" 
            :alt="app.title"
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
        <span v-if="taskbarStyle === 'text'" class="taskbar-app-text">{{ app.title }}</span>
        <div v-if="app.hasNotification" class="notification-dot"></div>
      </button>
    </div>

    <!-- Team Switcher -->
    <div v-if="hasMultipleTeams" class="team-switcher">
      <button
        class="team-switcher-trigger"
        @click="showTeamDropdown = !showTeamDropdown"
        :title="currentTeam?.name"
      >
        <div class="team-avatar">
          {{ getTeamInitials(currentTeam?.name || '') }}
        </div>
        <ChevronUpIcon class="w-4 h-4" />
      </button>
      
      <div v-if="showTeamDropdown" class="team-dropdown">
        <div class="team-dropdown-header">
          <span class="text-sm font-medium">Switch Team</span>
        </div>
        <div
          v-for="team in allTeams"
          :key="team.id"
          class="team-dropdown-item"
          :class="{ active: team.id === currentTeam?.id }"
          @click="handleTeamSwitch(team)"
        >
          <div class="team-switcher-avatar">
            {{ getTeamInitials(team.name) }}
          </div>
          <div>
            <div class="font-medium">{{ team.name }}</div>
            <div class="text-xs text-gray-500">{{ team.role }}</div>
          </div>
        </div>
      </div>
      
            <!-- System Tray Icons -->
      <div class="system-tray">
        <!-- Interface Mode Toggle -->
        <button
          class="system-tray-item interface-mode-toggle"
          @click="handleInterfaceModeToggle"
          @contextmenu.prevent="handleInterfaceModeContextMenu"
          :title="`Switch to ${getNextModeLabel()}`"
        >
          <component :is="getInterfaceModeIcon()" class="system-tray-icon" />
          <span class="mode-indicator">{{ getCurrentModeLabel() }}</span>
        </button>
        
        <!-- App Launcher -->
        <button
          v-if="interfaceMode !== 'easy' && showAppLauncher"
          class="system-tray-item app-launcher-btn"
          @click="handleAppLauncherClick"
          title="App Launcher"
        >
          <Squares2X2Icon class="system-tray-icon" />
        </button>
        
        <!-- Widgets Toggle -->
        <button
          v-if="interfaceMode === 'desktop'"
          class="system-tray-item widgets-toggle"
          @click="toggleWidgets"
          @contextmenu.prevent="handleWidgetsContextMenu"
          title="Toggle Widgets"
        >
          <span class="widgets-toggle-arrow" :class="{ 'widgets-open': showWidgetsPanel }">
            <ChevronRightIcon class="system-tray-icon" />
          </span>
        </button>
        
        <!-- Global Search -->
        <button
          v-if="showGlobalSearch"
          class="system-tray-item"
          @click="handleGlobalSearchClick"
          @contextmenu.prevent="handleSearchContextMenu"
          title="Search"
        >
          <MagnifyingGlassIcon class="system-tray-icon" />
        </button>
        
        <!-- Wallet -->
        <button
          v-if="showWallet"
          class="system-tray-item wallet-btn"
          @click="handleWalletClick"
          @contextmenu.prevent="handleWalletContextMenu"
          title="Wallet"
        >
          <component :is="getWalletIcon()" class="system-tray-icon" />
          <span v-if="walletBalance" class="wallet-balance">{{ formatWalletBalance(walletBalance) }}</span>
        </button>
        
        <!-- Fullscreen Toggle -->
        <button
          v-if="showFullscreen"
          class="system-tray-item"
          @click="handleFullscreenToggle"
          @contextmenu.prevent="handleFullscreenContextMenu"
          title="Fullscreen"
        >
          <component :is="getFullscreenIcon()" class="system-tray-icon" />
        </button>
        
        <!-- AI Chat -->
        <button
          class="system-tray-item ai-chat-btn"
          @click="handleAIChatClick"
          @contextmenu.prevent="handleAIChatContextMenu"
          title="AI Assistant"
        >
          <img src="/img/alien.png" alt="AI" class="ai-avatar" />
        </button>
        
        <!-- Volume Control -->
        <button
          v-if="showVolume"
          class="system-tray-item"
          @click="toggleVolumePanel"
          @contextmenu.prevent="handleVolumeContextMenu"
          title="Volume"
        >
          <component :is="getVolumeIcon()" class="system-tray-icon" />
        </button>
        
        <!-- Network Status -->
        <button
          class="system-tray-item"
          @click="handleNetworkClick"
          @contextmenu.prevent="handleNetworkContextMenu"
          title="Network"
        >
          <WifiIcon class="system-tray-icon" />
        </button>
        
        <!-- Notifications -->
        <button
          class="system-tray-item"
          @click="handleNotificationsClick"
          @contextmenu.prevent="handleNotificationsContextMenu"
          title="Notifications"
        >
          <BellIcon class="system-tray-icon" />
          <div v-if="unreadNotifications > 0" class="system-tray-badge"></div>
        </button>
      </div>
      
      <!-- Clock -->
      <button class="taskbar-clock" @click="handleClockClick" title="Calendar">
        <div class="text-sm font-medium">{{ currentTime }}</div>
        <div class="text-xs">{{ currentDate }}</div>
      </button>
    </div>
    
    <!-- Volume Panel -->
    <div v-show="showVolumePanel" class="volume-panel visible">
      <button class="close-volume-panel-btn" @click="showVolumePanel = false" title="Close">
        <span class="close-icon"></span>
      </button>
      <div class="music-panel-box">
        <div class="music-panel-content">
          <div class="music-info-block">
            <div class="music-title">Currently Playing...</div>
            <div class="music-meta">System Audio</div>
          </div>
          <div class="music-album-art-block">
            <img src="/img/avatar.png" alt="Album Art" />
          </div>
        </div>
        <div class="music-progress-row">
          <input type="range" class="music-progress-slider" min="0" max="100" value="47">
          <div class="music-time-row">
            <span class="music-current-time">0:47</span>
            <span class="music-total-time">3:15</span>
          </div>
        </div>
        <div class="music-controls-row">
          <button class="music-btn" title="Playlist"><span class="music-icon music-icon-list"></span></button>
          <button class="music-btn" title="Devices"><span class="music-icon music-icon-laptop"></span></button>
          <button class="music-btn" title="Previous"><span class="music-icon music-icon-backward"></span></button>
          <button class="music-btn play-btn" title="Play/Pause"><span class="music-icon music-icon-pause"></span></button>
          <button class="music-btn" title="Next"><span class="music-icon music-icon-forward"></span></button>
          <button class="music-btn" title="Repeat"><span class="music-icon music-icon-repeat"></span></button>
          <button class="music-btn" title="Shuffle"><span class="music-icon music-icon-shuffle"></span></button>
        </div>
      </div>
      <div class="volume-panel-box">
        <div class="volume-slider-panel">
          <span class="volume-icon"></span>
          <input type="range" id="browser-volume-slider" min="0" max="100" :value="volume" @input="handleVolumeChange">
          <span id="volume-percentage">{{ volume }}%</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import {
  HomeIcon,
  Squares2X2Icon,
  MagnifyingGlassIcon,
  BellIcon,
  PhotoIcon,
  UserIcon,
  CogIcon,
  AdjustmentsHorizontalIcon,
  DevicePhoneMobileIcon,
  EllipsisVerticalIcon,
  PlusIcon,
  DocumentIcon,
  WalletIcon,
  ArrowsPointingInIcon,
  ArrowsPointingOutIcon,
  ComputerDesktopIcon,
  ChevronUpIcon,
  ChevronRightIcon,
  XMarkIcon,
  CheckIcon
} from '@heroicons/vue/24/outline'

import { appDataService, type UIAppData } from '@/Services/AppDataService'

interface Team {
  id: string
  name: string
  role: string
}

interface Props {
  apps: UIAppData[]
  currentTeam?: Team
  allTeams?: Team[]
  showStartMenu: boolean
  unreadNotifications?: number
  showWidgetsPanel?: boolean
  walletBalance?: number
  isFullscreen?: boolean
  interfaceMode?: 'desktop' | 'app-launcher' | 'easy'
  taskbarStyle?: 'default' | 'windows11' | 'left' | 'text'
  showSearchBar?: boolean
  showAppLauncher?: boolean
  showGlobalSearch?: boolean
  showWallet?: boolean
  showFullscreen?: boolean
  showVolume?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  unreadNotifications: 0,
  allTeams: () => [],
  showWidgetsPanel: false,
  walletBalance: 0,
  isFullscreen: false,
  interfaceMode: 'desktop',
  taskbarStyle: 'default',
  showSearchBar: true,
  showAppLauncher: true,
  showGlobalSearch: true,
  showWallet: true,
  showFullscreen: true,
  showVolume: true
})

const emit = defineEmits<{
  startClick: []
  appClick: [app: UIAppData]
  search: [query: string]
  teamSwitch: [teamId: string]
  notificationsClick: []
  volumeChange: [volume: number]
  widgetsToggle: []
  globalSearchClick: []
  walletClick: []
  fullscreenToggle: []
  aiChatClick: []
  interfaceModeToggle: []
  appLauncherClick: []
  systemTrayContextMenu: [type: string, event: MouseEvent]
  taskbarContextMenu: [event: MouseEvent]
  startButtonContextMenu: [event: MouseEvent]
}>()

// State
const searchQuery = ref('')
const searchInput = ref<HTMLInputElement>()
const showTeamDropdown = ref(false)
const showVolumePanel = ref(false)
const volume = ref(75)
const currentTime = ref('')
const currentDate = ref('')

// Computed
const hasMultipleTeams = computed(() => 
  props.allTeams && props.allTeams.length > 1
)

// Get dynamic app data
const taskbarApps = computed(() => {
  if (props.apps && props.apps.length > 0) {
    // Use provided apps if available
    return props.apps
  }
  // Fallback to AppDataService for dynamic apps
  return appDataService.getTaskbarApps()
})

const quickApps = computed(() => {
  // Get system apps for quick access
  const systemApps = appDataService.getSystemAppsForUI()
  return systemApps.slice(0, 6) // Limit to 6 quick apps
})

const systemTrayIcons = [
  { name: 'Apps', icon: Squares2X2Icon, action: 'apps' },
  { name: 'Volume', icon: volume.value === 0 ? XMarkIcon : CheckIcon, action: 'volume' }
]

// Methods
const getVolumeIcon = () => {
  return volume.value === 0 ? AdjustmentsHorizontalIcon : MagnifyingGlassIcon
}

const getWalletIcon = () => {
  return props.walletBalance > 0 ? WalletIcon : WalletIcon
}

const getFullscreenIcon = () => {
  return props.isFullscreen ? ArrowsPointingInIcon : ArrowsPointingOutIcon
}

const formatWalletBalance = (balance: number) => {
  if (balance >= 1000000) {
    return (balance / 1000000).toFixed(1) + 'M'
  } else if (balance >= 1000) {
    return (balance / 1000).toFixed(1) + 'K'
  }
  return balance.toString()
}

const getTeamInitials = (teamName: string = '') => {
  return teamName
    .split(' ')
    .map(word => word.charAt(0))
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const updateTime = () => {
  const now = new Date()
  currentTime.value = now.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
  })
  currentDate.value = now.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric'
  })
}

// Dynamic app icon helpers
const getAppIconBackground = (app: UIAppData) => {
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

const getAppIconStyle = (app: UIAppData) => {
  if (app.iconBackground?.startsWith('#')) {
    return {
      backgroundColor: app.iconBackground
    }
  }
  return {}
}

// Event handlers
const handleSearch = () => {
  if ((searchQuery.value || '').trim()) {
    emit('search', (searchQuery.value || '').trim())
    searchQuery.value = ''
  }
}

const handleSearchFocus = () => {
  // Could emit event to show search suggestions
}

const handleSearchBlur = () => {
  // Could emit event to hide search suggestions
  setTimeout(() => {
    searchQuery.value = ''
  }, 200)
}

const handleSearchEnter = () => {
  if ((searchQuery.value || '').trim()) {
    emit('search', (searchQuery.value || '').trim())
    searchInput.value?.blur()
  }
}

const handleAppContextMenu = (app: UIAppData, event: MouseEvent) => {
  // Could show app context menu (pin/unpin, close, etc.)
  console.log('App context menu for:', app, event)
}

const toggleTeamDropdown = () => {
  showTeamDropdown.value = !showTeamDropdown.value
  showVolumePanel.value = false
}

const handleTeamSwitch = (team: Team) => {
  emit('teamSwitch', team.id)
  showTeamDropdown.value = false
}

const toggleVolumePanel = () => {
  showVolumePanel.value = !showVolumePanel.value
  showTeamDropdown.value = false
}

const handleVolumeChange = () => {
  emit('volumeChange', volume.value)
}

const handleNetworkClick = () => {
  // Could show network panel
  console.log('Network clicked')
}

const handleNotificationsClick = () => {
  emit('notificationsClick')
}

const handleClockClick = () => {
  // Could show calendar/date picker
  console.log('Clock clicked')
}

const toggleWidgets = () => {
  emit('widgetsToggle')
}

const handleGlobalSearchClick = () => {
  emit('globalSearchClick')
}

const handleWalletClick = () => {
  emit('walletClick')
}

const handleFullscreenToggle = () => {
  emit('fullscreenToggle')
}

const handleAIChatClick = () => {
  emit('aiChatClick')
}

const handleInterfaceModeToggle = () => {
  emit('interfaceModeToggle')
}

const handleAppLauncherClick = () => {
  emit('appLauncherClick')
}

const getInterfaceModeIcon = () => {
  switch (props.interfaceMode) {
    case 'desktop':
      return HomeIcon
    case 'app-launcher':
      return Squares2X2Icon
    case 'easy':
      return UserIcon
    default:
      return HomeIcon
  }
}

const getCurrentModeLabel = () => {
  switch (props.interfaceMode) {
    case 'desktop':
      return 'Desktop'
    case 'app-launcher':
      return 'Tablet'
    case 'easy':
      return 'Easy'
    default:
      return 'Desktop'
  }
}

const getNextModeLabel = () => {
  switch (props.interfaceMode) {
    case 'desktop':
      return 'Tablet Mode'
    case 'app-launcher':
      return 'Easy Mode'
    case 'easy':
      return 'Desktop Mode'
    default:
      return 'Tablet Mode'
  }
}

// Context menu handlers
const handleInterfaceModeContextMenu = (event: MouseEvent) => {
  emit('systemTrayContextMenu', 'interface-mode', event)
}

const handleWidgetsContextMenu = (event: MouseEvent) => {
  emit('systemTrayContextMenu', 'widgets', event)
}

const handleSearchContextMenu = (event: MouseEvent) => {
  emit('systemTrayContextMenu', 'search', event)
}

const handleWalletContextMenu = (event: MouseEvent) => {
  emit('systemTrayContextMenu', 'wallet', event)
}

const handleFullscreenContextMenu = (event: MouseEvent) => {
  emit('systemTrayContextMenu', 'fullscreen', event)
}

const handleAIChatContextMenu = (event: MouseEvent) => {
  emit('systemTrayContextMenu', 'ai-chat', event)
}

const handleVolumeContextMenu = (event: MouseEvent) => {
  emit('systemTrayContextMenu', 'volume', event)
}

const handleNetworkContextMenu = (event: MouseEvent) => {
  emit('systemTrayContextMenu', 'network', event)
}

const handleNotificationsContextMenu = (event: MouseEvent) => {
  emit('systemTrayContextMenu', 'notifications', event)
}

const handleTaskbarContextMenu = (event: MouseEvent) => {
  emit('taskbarContextMenu', event)
}

const handleStartButtonContextMenu = (event: MouseEvent) => {
  emit('startButtonContextMenu', event)
}

// Global click handler to close dropdowns
const handleGlobalClick = (event: MouseEvent) => {
  const target = event.target as HTMLElement
  
  if (!target.closest('.team-switcher')) {
    showTeamDropdown.value = false
  }
  
  if (!target.closest('.volume-panel') && !target.closest('[title="Volume"]')) {
    showVolumePanel.value = false
  }
}

// Lifecycle
onMounted(() => {
  updateTime()
  const timeInterval = setInterval(updateTime, 1000)
  
  document.addEventListener('click', handleGlobalClick)
  
  onUnmounted(() => {
    clearInterval(timeInterval)
    document.removeEventListener('click', handleGlobalClick)
  })
})

// Keyboard shortcuts
const handleKeydown = (event: KeyboardEvent) => {
  // Ctrl/Cmd + Space to focus search
  if ((event.ctrlKey || event.metaKey) && event.code === 'Space') {
    event.preventDefault()
    searchInput.value?.focus()
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
.taskbar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  height: 48px;
  background: rgba(0, 0, 0, 0.8);
  backdrop-filter: blur(20px);
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 12px;
  z-index: 1000;
  transition: all 0.3s ease;
}

/* Taskbar Style Variations */
.taskbar-default {
  justify-content: space-between;
}

.taskbar-windows11 {
  justify-content: center;
}

.taskbar-left {
  justify-content: flex-start;
}

.taskbar-text {
  justify-content: flex-start;
}

.taskbar.taskbar-windows11-style {
  justify-content: center;
}

.taskbar.taskbar-windows11-style .start-button {
  position: absolute;
  left: 12px;
}

.taskbar.taskbar-icons-left .taskbar-app-icons {
  justify-content: flex-start;
  margin-left: 0;
}

.taskbar.taskbar-text-mode .app-label {
  display: inline-block;
  margin-left: 8px;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.9);
}

/* Start Button */
.start-button {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-right: 12px;
}

.start-button:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.05);
}

.start-button.active {
  background: rgba(59, 130, 246, 0.3);
  border: 1px solid rgba(59, 130, 246, 0.5);
}

.start-icon {
  width: 24px;
  height: 24px;
  object-fit: contain;
}

/* Search Container */
.search-container {
  display: flex;
  align-items: center;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 20px;
  padding: 8px 16px;
  margin: 0 16px;
  min-width: 200px;
  transition: all 0.2s ease;
}

.search-container:focus-within {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(59, 130, 246, 0.5);
}

.search-wrapper {
  display: flex;
  align-items: center;
  width: 100%;
}

.search-icon {
  width: 16px;
  height: 16px;
  color: rgba(255, 255, 255, 0.6);
  margin-right: 8px;
}

.search-input {
  background: transparent;
  border: none;
  outline: none;
  color: white;
  font-size: 14px;
  width: 100%;
}

.search-input::placeholder {
  color: rgba(255, 255, 255, 0.5);
}

/* App Icons */
.taskbar-app-icons {
  display: flex;
  align-items: center;
  gap: 4px;
  flex: 1;
  justify-content: center;
  max-width: 600px;
}

.taskbar-app-icons.with-text {
  gap: 8px;
}

.taskbar-app-icon {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 40px;
  height: 40px;
  padding: 8px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.taskbar-app-icon:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
}

.taskbar-app-icon.active {
  background: rgba(59, 130, 246, 0.3);
  border: 1px solid rgba(59, 130, 246, 0.5);
}

.taskbar-app-icon.has-notification::after {
  content: '';
  position: absolute;
  top: 2px;
  right: 2px;
  width: 8px;
  height: 8px;
  background: #ef4444;
  border-radius: 50%;
  border: 2px solid rgba(0, 0, 0, 0.8);
}

.app-icon {
  width: 24px;
  height: 24px;
  color: white;
}

.app-label {
  display: none;
  margin-left: 8px;
  font-size: 12px;
  color: rgba(255, 255, 255, 0.9);
  white-space: nowrap;
}

.taskbar-text-mode .app-label {
  display: inline-block;
}

.notification-dot {
  position: absolute;
  top: 2px;
  right: 2px;
  width: 8px;
  height: 8px;
  background: #ef4444;
  border-radius: 50%;
  border: 2px solid rgba(0, 0, 0, 0.8);
}

/* Team Switcher */
.team-switcher {
  position: relative;
  margin-right: 16px;
}

.team-switcher-trigger {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.team-switcher-trigger:hover {
  background: rgba(255, 255, 255, 0.2);
}

.team-avatar {
  width: 24px;
  height: 24px;
  background: rgba(59, 130, 246, 0.8);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  font-weight: 600;
  color: white;
}

.team-dropdown {
  position: absolute;
  bottom: 60px;
  right: 0;
  background: rgba(0, 0, 0, 0.9);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  padding: 8px;
  min-width: 200px;
  z-index: 1001;
}

.team-dropdown-header {
  padding: 8px 12px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  margin-bottom: 4px;
}

.team-dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.team-dropdown-item:hover {
  background: rgba(255, 255, 255, 0.1);
}

.team-dropdown-item.active {
  background: rgba(59, 130, 246, 0.2);
}

.team-switcher-avatar {
  width: 32px;
  height: 32px;
  background: rgba(59, 130, 246, 0.8);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 600;
  color: white;
}

/* System Tray */
.system-tray {
  display: flex;
  align-items: center;
  gap: 4px;
}

.system-tray-item {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.system-tray-item:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: scale(1.05);
}

.system-tray-icon {
  width: 16px;
  height: 16px;
  color: rgba(255, 255, 255, 0.9);
}

.interface-mode-toggle {
  position: relative;
  min-width: 60px;
}

.mode-indicator {
  font-size: 10px;
  font-weight: 600;
  margin-left: 4px;
  background: rgba(59, 130, 246, 0.2);
  padding: 2px 4px;
  border-radius: 6px;
  color: #60a5fa;
}

.app-launcher-btn {
  transition: all 0.2s ease;
}

.app-launcher-btn:hover {
  background: rgba(255, 255, 255, 0.15);
  transform: scale(1.05);
}

.widgets-toggle-arrow {
  transition: transform 0.3s ease;
}

.widgets-toggle-arrow.widgets-open {
  transform: rotate(90deg);
}

.wallet-balance {
  font-size: 10px;
  font-weight: 600;
  margin-left: 4px;
  background: rgba(34, 197, 94, 0.2);
  padding: 2px 4px;
  border-radius: 4px;
  color: #4ade80;
}

.ai-avatar {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  object-fit: cover;
}

.system-tray-badge {
  position: absolute;
  top: 2px;
  right: 2px;
  width: 8px;
  height: 8px;
  background: #ef4444;
  border-radius: 50%;
  border: 2px solid rgba(0, 0, 0, 0.8);
}

/* Clock */
.taskbar-clock {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  color: white;
  text-align: center;
  min-width: 80px;
}

.taskbar-clock:hover {
  background: rgba(255, 255, 255, 0.2);
}

/* Volume Panel */
.volume-panel {
  position: absolute;
  bottom: 60px;
  right: 80px;
  background: rgba(0, 0, 0, 0.9);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  padding: 16px;
  width: 200px;
  z-index: 1001;
}

.volume-slider {
  width: 100%;
  height: 4px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 2px;
  outline: none;
  appearance: none;
}

.volume-slider::-webkit-slider-thumb {
  appearance: none;
  width: 16px;
  height: 16px;
  background: #60a5fa;
  border-radius: 50%;
  cursor: pointer;
}

.volume-slider::-moz-range-thumb {
  width: 16px;
  height: 16px;
  background: #60a5fa;
  border-radius: 50%;
  cursor: pointer;
  border: none;
}
</style> 