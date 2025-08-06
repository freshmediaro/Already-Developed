<template>
  <div class="desktop-container" :class="{ 'desktop-initialized': initialized }">
    <!-- Team Banner (if user has multiple teams) -->
    <TeamBanner v-if="hasMultipleTeams && currentTeam" :team="currentTeam" />
    
    <div class="os-container">
      <!-- Main Content Area for 3-Screen Mobile Layout -->
      <div class="main-content-area">
        <!-- Notifications Screen (Left) -->
        <div id="notifications-screen">
          <NotificationPanel 
            :show="true"
            :notifications="notifications"
            @close="showNotifications = false"
            @notification-click="handleNotificationClick"
          />
        </div>

        <!-- Desktop Area (Center/Home) -->
        <div class="desktop-area" id="desktop-area">
          <!-- Mobile Profile Top Bar -->
          <div class="mobile-profile-top-bar">
            <div class="mobile-profile-top-bar-header">
              <div class="mobile-profile-top-bar-header-left">
                <img 
                  :src="user?.profile_photo_url || '/img/avatar.png'" 
                  :alt="user?.name || 'User Avatar'" 
                  class="site-logo-round"
                >
                <span>{{ user?.name || 'User' }}</span>
              </div>
              <div class="mobile-profile-header-right">
                <button 
                  class="toolbar-search-btn" 
                  title="Search"
                  @click="showGlobalSearch = true"
                >
                  <i class="fas fa-search"></i>
                </button>
              </div>
            </div>
          </div>
          
          <!-- Desktop Background -->
          <DesktopBackground :wallpaper="userPreferences.desktop_wallpaper" />
          
          <!-- Desktop Icons -->
          <DesktopIcons 
            :icons="desktopIcons" 
            @icon-click="handleIconClick"
            @icon-double-click="handleIconDoubleClick"
            @icon-context-menu="handleDesktopIconContextMenu"
          />
          
          <!-- Windows Container -->
          <WindowManager 
            :windows="windows" 
            @window-created="handleWindowCreated"
            @window-closed="handleWindowClosed"
            @window-activated="handleWindowActivated"
          />

          <!-- Taskbar -->
          <Taskbar 
            :apps="taskbarApps"
            :current-team="currentTeam"
            :all-teams="allTeams"
            :show-start-menu="showStartMenu"
            :unread-notifications="unreadNotifications"
            :show-widgets-panel="showWidgets"
            :wallet-balance="walletBalance"
            :is-fullscreen="isFullscreen"
            :interface-mode="interfaceMode"
            :taskbar-style="taskbarSettings.style"
            :show-search-bar="taskbarSettings.showSearchBar"
            :show-app-launcher="taskbarSettings.showAppLauncher"
            :show-global-search="taskbarSettings.showGlobalSearch"
            :show-wallet="taskbarSettings.showWallet"
            :show-fullscreen="taskbarSettings.showFullscreen"
            :show-volume="taskbarSettings.showVolume"
            @start-click="toggleStartMenu"
            @app-click="handleTaskbarAppClick"
            @search="handleGlobalSearch"
            @team-switch="handleTeamSwitch"
            @notifications-click="showNotifications = true"
            @widgets-toggle="showWidgets = !showWidgets"
            @global-search-click="showGlobalSearch = true"
            @wallet-click="handleWalletClick"
            @fullscreen-toggle="handleFullscreenToggle"
            @ai-chat-click="handleAIChatClick"
            @interface-mode-toggle="handleInterfaceModeToggle"
            @app-launcher-click="handleAppLauncherClick"
            @system-tray-context-menu="handleSystemTrayContextMenu"
            @taskbar-context-menu="handleTaskbarContextMenu"
            @start-button-context-menu="handleStartButtonContextMenuFromTaskbar"
          />
          
          <!-- Drag Selector -->
          <div id="drag-selector" class="hidden"></div>
        </div>

        <!-- Widgets Screen (Right) -->
        <div id="widgets-screen">
          <WidgetPanel 
            :show="true"
            :widgets="widgets"
            @close="showWidgets = false"
          />
        </div>
      </div>
    </div>
    
    <!-- Start Menu -->
    <StartMenu 
      v-show="showStartMenu"
      :apps="availableApps"
      :search-query="searchQuery"
      @app-launch="handleAppLaunch"
      @close="showStartMenu = false"
    />
    
    <!-- Context Menu -->
    <ContextMenu 
      v-show="contextMenu.show"
      :x="contextMenu.x"
      :y="contextMenu.y"
      :items="contextMenu.items"
      @item-click="handleContextMenuClick"
      @close="closeContextMenu"
    />
    
    <!-- Global Search -->
    <GlobalSearch 
      v-show="showGlobalSearch"
      :query="searchQuery"
      :results="searchResults"
      @close="showGlobalSearch = false"
      @search="handleSearch"
      @result-click="handleSearchResultClick"
    />
    
    <!-- Team Management Modal -->
    <TeamManagementModal 
      v-show="showTeamModal"
      :teams="allTeams"
      :current-team="currentTeam"
      @close="showTeamModal = false"
      @team-create="handleTeamCreate"
      @team-switch="handleTeamSwitch"
    />
    
    <!-- AI Chat Window -->
    <AiChatWindow 
      :show="showAIChat"
      @close="showAIChat = false"
    />
    
    <!-- Page Content (for non-desktop pages) -->
    <main v-if="!isDesktopMode">
      <slot />
    </main>
    
    <!-- Orientation Warning for Mobile -->
    <OrientationWarning />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import DesktopBackground from '@/Components/Desktop/DesktopBackground.vue'
import DesktopIcons from '@/Components/Desktop/DesktopIcons.vue'
import WindowManager from '@/Components/Desktop/WindowManager.vue'
import Taskbar from '@/Components/Desktop/Taskbar.vue'
import StartMenu from '@/Components/Desktop/StartMenu.vue'
import NotificationPanel from '@/Components/Desktop/NotificationPanel.vue'
import WidgetPanel from '@/Components/Desktop/WidgetPanel.vue'
import ContextMenu from '@/Components/Desktop/ContextMenu.vue'
import GlobalSearch from '@/Components/Desktop/GlobalSearch.vue'
import TeamBanner from '@/Components/Teams/TeamBanner.vue'
import TeamManagementModal from '@/Components/Teams/TeamManagementModal.vue'
import AppLauncher from '@/Components/Desktop/AppLauncher.vue'
import OrientationWarning from '@/Components/Desktop/OrientationWarning.vue'
import AiChatWindow from '@/Components/Desktop/AiChatWindow.vue'

// Import our TypeScript desktop app
import desktopApp from '@/app'
import { DesktopDragSelector } from '@/Core/DesktopDragSelector'
import { globalStateManager } from '@/Core/GlobalStateManager'
import { clipboardService } from '@/Core/ClipboardService'
import { eventSystem } from '@/Core/EventSystem'
import { mobileSwipeManager } from '@/Core/MobileSwipeManager'
import type { ClipboardService, MobileSwipeManager, ContextMenuItem } from '@/Core/Types'

interface Props {
  title?: string
  isDesktopMode?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  title: 'Desktop',
  isDesktopMode: true
})

// Page props from Inertia
const page = usePage()

// Reactive state
const initialized = ref(false)
const showStartMenu = ref(false)
const showNotifications = ref(false)
const showWidgets = ref(false)
const showGlobalSearch = ref(false)
const showTeamModal = ref(false)
const showAIChat = ref(false)
const searchQuery = ref('')

// Desktop state
const windows = ref(new Map())
const desktopIcons = ref([])
const taskbarApps = ref([])
const availableApps = ref([])
const notifications = ref<any[]>([])
const widgets = ref([])
const searchResults = ref([])

// Context menu state
const contextMenu = ref<{
  show: boolean;
  x: number;
  y: number;
  items: ContextMenuItem[];
}>({
  show: false,
  x: 0,
  y: 0,
  items: []
})

// Track current context menu target for text operations
const currentContextMenuTarget = ref<HTMLElement | null>(null)

// Drag selector
const dragSelector = ref<DesktopDragSelector | null>(null)

// Computed properties
const user = computed(() => page.props.user)
const currentTeam = computed(() => page.props.currentTeam)
const allTeams = computed(() => page.props.allTeams || [])
const userPreferences = computed(() => page.props.userPreferences || {})

const hasMultipleTeams = computed(() => 
  allTeams.value && allTeams.value.length > 1
)

// Taskbar settings from global state
const taskbarSettings = computed(() => 
  globalStateManager.getTaskbarSettings()
)

const unreadNotifications = computed(() => 
  (notifications.value || []).filter((n: any) => !n.read).length
)

const walletBalance = computed(() => 
  globalStateManager.getAllSettings().wallet?.balance || 0
)

const isFullscreen = computed(() => 
  globalStateManager.getInterfaceSettings().isFullscreen
)

const interfaceMode = computed(() => 
  globalStateManager.getInterfaceSettings().mode
)

// Desktop initialization
onMounted(async () => {
  if (props.isDesktopMode) {
    try {
      // Initialize the TypeScript desktop application
      await desktopApp.initialize()
      
      // Load desktop data
      await loadDesktopData()
      
      // Setup global event listeners
      setupEventListeners()
      
      // Initialize drag selector
      dragSelector.value = new DesktopDragSelector(eventSystem)
      dragSelector.value.initialize()
      
      initialized.value = true
    } catch (error) {
      console.error('Failed to initialize desktop:', error)
    }
  }
})

onUnmounted(() => {
  if (props.isDesktopMode) {
    cleanup()
  }
})

// Methods
const loadDesktopData = async () => {
  try {
    // Load apps, windows, preferences from API
    const response = await fetch('/api/desktop/data', {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      
      desktopIcons.value = data.desktopIcons || []
      taskbarApps.value = data.taskbarApps || []
      availableApps.value = data.availableApps || []
      notifications.value = data.notifications || []
      widgets.value = data.widgets || []
    }
  } catch (error) {
    console.error('Failed to load desktop data:', error)
  }
}

const setupEventListeners = () => {
  // Global keyboard shortcuts
  document.addEventListener('keydown', handleKeydown)
  document.addEventListener('contextmenu', handleContextMenu)
  
  // Disable default browser context menu except in specific areas
  document.addEventListener('contextmenu', (event) => {
    const target = event.target as HTMLElement
    
    // Allow default context menu in email content areas, all inputs, textareas, and contenteditables
    if (
      target.closest('.email-content') ||
      target.closest('.email-content-section') ||
      target.closest('.window-content') ||
      target.tagName === 'INPUT' ||
      target.tagName === 'TEXTAREA' ||
      target.isContentEditable
    ) {
      // Allow our custom context menu to handle these
      return
    }
    
    // Disable browser's default context menu elsewhere
    event.preventDefault()
  }, { passive: false })
  
  // Window events from TypeScript desktop app
  if (window.desktopApp) {
    window.desktopApp.on('window:created', handleWindowCreated)
    window.desktopApp.on('window:closed', handleWindowClosed)
    window.desktopApp.on('app:launched', handleAppLaunched)
  }
}

const cleanup = () => {
  document.removeEventListener('keydown', handleKeydown)
  document.removeEventListener('contextmenu', handleContextMenu)
  
  // Cleanup drag selector
  if (dragSelector.value) {
    dragSelector.value.cleanup()
    dragSelector.value = null
  }
}

// Event handlers
const handleKeydown = (e: KeyboardEvent) => {
  // Global shortcuts
  if (e.metaKey || e.ctrlKey) {
    switch (e.key) {
      case ' ':
        e.preventDefault()
        toggleGlobalSearch()
        break
      case 'Escape':
        closeOtherPanels()
        break
    }
  }
}

const handleContextMenu = (e: MouseEvent) => {
  e.preventDefault()
  
  // Check if right-clicking on specific elements
  const target = e.target as HTMLElement
  
  // Store the target for context menu actions
  currentContextMenuTarget.value = target
  
  // Check for text selection context menu first
  const hasTextSelection = clipboardService.hasTextSelection(target)
  const isTextEditable = clipboardService.isTextEditable(target)
  const isTextInput = (target.tagName === 'INPUT' && !target.hasAttribute('readonly') && !target.hasAttribute('disabled')) ||
                     (target.tagName === 'TEXTAREA' && !target.hasAttribute('readonly') && !target.hasAttribute('disabled')) ||
                     target.isContentEditable
  
  // Show text context menu if we have selection or if it's an editable text element
  if (hasTextSelection || isTextInput) {
    const textMenuItems = clipboardService.getTextContextMenuItems(target)
    showContextMenuAt(e.clientX, e.clientY, textMenuItems)
    return
  }
  
  // Check for generic text selection (even in non-editable elements)
  const selection = window.getSelection()
  if (selection && !selection.isCollapsed && selection.toString().trim().length > 0) {
    const textMenuItems = clipboardService.getTextContextMenuItems(target)
    showContextMenuAt(e.clientX, e.clientY, textMenuItems)
    return
  }
  
  // Start button context menu
  if (target.closest('.start-button')) {
    showStartButtonContextMenu(e.clientX, e.clientY)
    return
  }
  
  // Desktop icon context menu
  if (target.closest('.desktop-icon')) {
    showDesktopIconContextMenu(e.clientX, e.clientY, target.closest('.desktop-icon'))
    return
  }
  
  // Main desktop context menu (clicking on empty space)
  showMainDesktopContextMenu(e.clientX, e.clientY)
}

const handleIconClick = (icon: any) => {
  // Single click selection
  console.log('Icon clicked:', icon)
}

const handleIconDoubleClick = (icon: any) => {
  // Launch app
  handleAppLaunch(icon.appId)
}

const handleDesktopIconContextMenu = (icon: any, event: MouseEvent) => {
  showDesktopIconContextMenu(event.clientX, event.clientY, icon)
}

const handleAppLaunch = async (appId: string): Promise<string | null> => {
  try {
    if (window.desktopApp) {
      const windowId = await window.desktopApp.launchApp(appId)
      console.log(`Launched app ${appId} in window ${windowId}`)
      
      // Close start menu
      showStartMenu.value = false
      return windowId
    }
  } catch (error) {
    console.error(`Failed to launch app ${appId}:`, error)
  }
  
  // Close start menu
  showStartMenu.value = false
  return null
}

const handleWindowCreated = (event: any) => {
  console.log('Window created:', event)
  // Update windows state
}

const handleWindowClosed = (event: any) => {
  console.log('Window closed:', event)
  // Update windows state
}

const handleWindowActivated = (event: any) => {
  console.log('Window activated:', event)
}

const handleAppLaunched = (event: any) => {
  console.log('App launched:', event)
}

const handleTaskbarAppClick = (app: any) => {
  if (app.windowId) {
    // Activate existing window
    if (window.desktopApp) {
      window.desktopApp.activateWindow(app.windowId)
    }
  } else {
    // Launch new instance
    handleAppLaunch(app.id)
  }
}

const handleGlobalSearch = (query: string) => {
  searchQuery.value = query
  if (query.length > 0) {
    showGlobalSearch.value = true
    performSearch(query)
  }
}

const handleSearch = (query: string) => {
  searchQuery.value = query
  performSearch(query)
}

const performSearch = async (query: string) => {
  try {
    const response = await fetch(`/api/desktop/search?q=${encodeURIComponent(query)}`)
    if (response.ok) {
      const results = await response.json()
      searchResults.value = results
    }
  } catch (error) {
    console.error('Search failed:', error)
  }
}

const handleSearchResultClick = (result: any) => {
  if (result.type === 'app') {
    handleAppLaunch(result.appId)
  }
  showGlobalSearch.value = false
}

const handleTeamSwitch = async (teamId: string) => {
  try {
    if (window.desktopApp) {
      await window.desktopApp.switchTeam(teamId)
    }
    
    // Reload desktop data for new team
    await loadDesktopData()
  } catch (error) {
    console.error('Failed to switch team:', error)
  }
}

const handleTeamCreate = (teamData: any) => {
  // Handle team creation
  console.log('Creating team:', teamData)
}

const handleNotificationClick = (notification: any) => {
  console.log('Notification clicked:', notification)
  // Handle notification action
}

const handleContextMenuClick = async (action: string) => {
  // Handle text context menu actions first
  if (action.startsWith('text-') && currentContextMenuTarget.value) {
    const target = currentContextMenuTarget.value
    
    switch (action) {
      case 'text-cut':
        await clipboardService.cut(target)
        break
      case 'text-copy':
        await clipboardService.copy(target)
        break
      case 'text-paste':
        await clipboardService.paste(target)
        break
      case 'text-delete':
        clipboardService.delete(target)
        break
      case 'text-select-all':
        clipboardService.selectAll(target)
        break
    }
    
    closeContextMenu()
    return
  }

  switch (action) {
    // Taskbar customization actions
    case 'toggle-search-bar':
      globalStateManager.toggleSearchBar()
      break
    case 'toggle-search-icon':
      globalStateManager.toggleSystemTrayIcon('globalSearch')
      break
    case 'toggle-app-launcher':
      globalStateManager.toggleSystemTrayIcon('appLauncher')
      break
    case 'toggle-volume':
      globalStateManager.toggleSystemTrayIcon('volume')
      break
    case 'toggle-wallet':
      globalStateManager.toggleSystemTrayIcon('wallet')
      break
    case 'toggle-fullscreen-icon':
      globalStateManager.toggleSystemTrayIcon('fullscreen')
      break
    
    // Taskbar styles
    case 'taskbar-style-default':
      globalStateManager.setTaskbarStyle('default')
      break
    case 'taskbar-style-windows11':
      globalStateManager.setTaskbarStyle('windows11')
      break
    case 'taskbar-style-left':
      globalStateManager.setTaskbarStyle('left')
      break
    case 'taskbar-style-text':
      globalStateManager.setTaskbarStyle('text')
      break
    
    // Other actions
    case 'show-desktop':
      globalStateManager.showDesktop()
      break
    case 'customize-widgets':
      console.log('Customize widgets')
      break
    
    // Interface mode actions
    case 'desktop-mode':
      await globalStateManager.setInterfaceMode('desktop')
      break
    case 'desktop-app-launcher-mode':
      await globalStateManager.setInterfaceMode('app-launcher')
      break
    case 'app-launcher-easy-mode':
      await globalStateManager.setInterfaceMode('easy')
      break
    case 'widgets-mode':
      globalStateManager.setWidgetsVisible(!showWidgets.value)
      break

    // Desktop sort actions
    case 'sort-name':
      console.log('Sort desktop icons by name')
      // TODO: Implement desktop icon sorting
      break
    case 'sort-date':
      console.log('Sort desktop icons by date')
      // TODO: Implement desktop icon sorting
      break
    case 'sort-type':
      console.log('Sort desktop icons by type')
      // TODO: Implement desktop icon sorting
      break

    // File operations
    case 'paste':
      console.log('Paste operation')
      // TODO: Implement paste functionality
      break
    case 'upload-files':
      console.log('Upload files')
      // TODO: Implement file upload
      break
    case 'new-folder':
      console.log('Create new folder')
      // TODO: Implement new folder creation
      break
    case 'new-file':
      console.log('Create new file')
      // TODO: Implement new file creation
      break
    case 'new-text-file':
      console.log('Create new text file')
      // TODO: Implement new text file creation
      break
    case 'new-spreadsheet':
      console.log('Create new spreadsheet')
      // TODO: Implement new spreadsheet creation
      break
    case 'new-presentation':
      console.log('Create new presentation')
      // TODO: Implement new presentation creation
      break

    // Settings actions (Start button context menu)
    case 'my-profile-settings':
      handleAppLaunch('settings')
      break
    case 'appearance-settings':
      handleAppLaunch('settings')
      break
    case 'notifications-settings':
      handleAppLaunch('settings')
      break
    case 'privacy-settings':
      handleAppLaunch('settings')
      break
    case 'security-settings':
      handleAppLaunch('settings')
      break
    case 'integrations-settings':
      handleAppLaunch('settings')
      break
    case 'active-services':
      handleAppLaunch('settings')
      break
    case 'my-website':
      console.log('Open my website')
      // TODO: Implement website management
      break
    case 'products-settings':
      console.log('Open products settings')
      // TODO: Implement products management
      break
    case 'payments-settings':
      console.log('Open payments settings')
      // TODO: Implement payments management
      break
    case 'shipping-settings':
      console.log('Open shipping settings')
      // TODO: Implement shipping management
      break
    case 'customers-privacy-settings':
      console.log('Open customers & privacy settings')
      // TODO: Implement customer management
      break
    case 'emails-settings':
      console.log('Open emails settings')
      // TODO: Implement email management
      break
    case 'billing-settings':
      console.log('Open billing settings')
      // TODO: Implement billing management
      break
    case 'open-settings':
      handleAppLaunch('settings')
      break

    // Start menu style actions
    case 'start-menu-style-default':
    case 'start-menu-style-default-apps-only':
    case 'start-menu-style-windows11':
    case 'start-menu-style-apps-list-with-sidebar':
    case 'start-menu-style-apps-list-only':
    case 'start-menu-style-app-launcher':
      console.log('Start menu style changed:', action)
      // TODO: Implement start menu style changes
      break

    // Desktop icon actions
    case 'open-app':
      console.log('Open app from desktop icon')
      // TODO: Implement app opening from desktop icon
      break
    case 'pin-to-taskbar':
      console.log('Pin to taskbar')
      // TODO: Implement pin to taskbar
      break
    case 'remove-from-desktop':
      console.log('Remove from desktop')
      // TODO: Implement remove from desktop
      break

    // Default actions
    case 'refresh':
      location.reload()
      break
    case 'personalize':
      handleAppLaunch('settings')
      break
    case 'help-and-support':
      console.log('Open help and support')
      // TODO: Implement help system
      break
    case 'display-settings':
      // Open display settings
      break
  }
  closeContextMenu()
}

const toggleStartMenu = () => {
  showStartMenu.value = !showStartMenu.value
  if (showStartMenu.value) {
    closeOtherPanels(['start'])
  }
}

const toggleGlobalSearch = () => {
  showGlobalSearch.value = !showGlobalSearch.value
  if (showGlobalSearch.value) {
    closeOtherPanels(['search'])
  }
}

const closeContextMenu = () => {
  if (contextMenu.value) {
    contextMenu.value.show = false
  }
  currentContextMenuTarget.value = null
}

const closeOtherPanels = (except: string[] = []) => {
  if (!except.includes('start')) showStartMenu.value = false
  if (!except.includes('notifications')) showNotifications.value = false
  if (!except.includes('widgets')) showWidgets.value = false
  if (!except.includes('search')) showGlobalSearch.value = false
  if (!except.includes('team')) showTeamModal.value = false
}

// Taskbar event handlers
const handleWalletClick = () => {
  console.log('Wallet clicked')
  // TODO: Implement wallet functionality
}

const handleFullscreenToggle = () => {
  globalStateManager.setFullscreenMode(!isFullscreen.value)
}

const handleAIChatClick = () => {
  showAIChat.value = !showAIChat.value
}

const handleInterfaceModeToggle = async () => {
  const currentMode = interfaceMode.value
  const nextMode = currentMode === 'desktop' ? 'app-launcher' : 
                   currentMode === 'app-launcher' ? 'easy' : 'desktop'
  await globalStateManager.setInterfaceMode(nextMode)
}

const handleAppLauncherClick = () => {
  console.log('App Launcher clicked')
  // TODO: Implement app launcher functionality
}

const handleSystemTrayContextMenu = (type: string, event: MouseEvent) => {
  const menuItems = getSystemTrayContextMenuItems(type)
  showContextMenuAt(event.clientX, event.clientY, menuItems)
}

const handleTaskbarContextMenu = (event: MouseEvent) => {
  const menuItems = getTaskbarContextMenuItems()
  showContextMenuAt(event.clientX, event.clientY, menuItems)
}

const handleStartButtonContextMenuFromTaskbar = (event: MouseEvent) => {
  showStartButtonContextMenu(event.clientX, event.clientY)
}

const getSystemTrayContextMenuItems = (type: string) => {
  const settings = taskbarSettings.value
  
  switch (type) {
    case 'widgets':
      return [
        { label: 'Customize Widgets', action: 'customize-widgets', icon: 'fa-cog' },
        { type: 'separator' },
        { label: 'Widget Settings', action: 'widget-settings', icon: 'fa-gear' }
      ]
    case 'search':
      return [
        { label: 'Search Settings', action: 'search-settings', icon: 'fa-gear' },
        { type: 'separator' },
        { label: settings.showSearchBar ? 'Hide Search Bar' : 'Show Search Bar', action: 'toggle-search-bar', icon: 'fa-search' }
      ]
    case 'wallet':
      return [
        { label: 'View Transactions', action: 'wallet-transactions', icon: 'fa-list' },
        { label: 'Add Funds', action: 'wallet-add-funds', icon: 'fa-plus' },
        { type: 'separator' },
        { label: 'Wallet Settings', action: 'wallet-settings', icon: 'fa-gear' }
      ]
    default:
      return []
  }
}

const getTaskbarContextMenuItems = () => {
  const settings = taskbarSettings.value
  
  return [
    {
      label: settings.showSearchBar ? 'Hide search bar' : 'Show search bar',
      action: 'toggle-search-bar',
      icon: 'fa-search'
    },
    {
      label: 'Show / Hide Icons',
      action: 'show-hide-icons',
      icon: 'fa-eye',
      submenu: [
        {
          label: settings.showGlobalSearch ? 'Hide Search icon' : 'Show Search icon',
          action: 'toggle-search-icon',
          icon: 'fa-search'
        },
        {
          label: settings.showAppLauncher ? 'Hide App Launcher' : 'Show App Launcher',
          action: 'toggle-app-launcher',
          icon: 'fa-th'
        },
        {
          label: settings.showVolume ? 'Hide Volume' : 'Show Volume',
          action: 'toggle-volume',
          icon: 'fa-volume-high'
        },
        {
          label: settings.showWallet ? 'Hide Wallet' : 'Show Wallet',
          action: 'toggle-wallet',
          icon: 'fa-wallet'
        },
        { type: 'separator' },
        {
          label: settings.showFullscreen ? 'Hide Full Screen' : 'Show Full Screen',
          action: 'toggle-fullscreen-icon',
          icon: 'fa-expand'
        }
      ]
    },
    { type: 'separator' },
    { label: 'Customize Widgets', action: 'customize-widgets', icon: 'fa-server' },
    { label: 'Show desktop', action: 'show-desktop', icon: 'fa-display' },
    { type: 'separator' },
    {
      label: 'Customize Taskbar',
      action: 'customize-taskbar',
      icon: 'fa-cog',
      submenu: [
        { label: 'Default', action: 'taskbar-style-default', icon: 'fa-ellipsis' },
        { label: 'Windows 11 Style', action: 'taskbar-style-windows11', icon: 'fa-ellipsis-h' },
        { label: 'Icons - Left alignment', action: 'taskbar-style-left', icon: 'fa-server' },
        { label: 'Icons and Text', action: 'taskbar-style-text', icon: 'fa-server' }
      ]
    }
  ]
}

const showContextMenuAt = (x: number, y: number, items: any[]) => {
  contextMenu.value = {
    show: true,
    x,
    y,
    items
  }
}

// Main desktop context menu (right-click on empty space)
const showMainDesktopContextMenu = (x: number, y: number) => {
  const menuItems = [
    {
      label: 'View',
      action: 'view',
      icon: 'fa-eye',
      submenu: [
        { label: 'Desktop mode', action: 'desktop-mode', icon: 'fa-desktop' },
        { label: 'App launcher mode', action: 'desktop-app-launcher-mode', icon: 'fa-grip' },
        { label: 'Easy Mode', action: 'app-launcher-easy-mode', icon: 'fa-chalkboard' },
        { type: 'separator' },
        { label: 'Widgets Mode', action: 'widgets-mode', icon: 'fa-shapes' }
      ]
    },
    {
      label: 'Sort by',
      action: 'sort-by',
      icon: 'fa-sort',
      submenu: [
        { label: 'Name', action: 'sort-name', icon: 'fa-sort-alpha-down' },
        { label: 'Date', action: 'sort-date', icon: 'fa-arrow-down-1-9' },
        { label: 'Type', action: 'sort-type', icon: 'fa-arrow-down-wide-short' }
      ]
    },
    { label: 'Refresh', action: 'refresh', icon: 'fa-arrows-rotate' },
    { type: 'separator' },
    { label: 'Paste', action: 'paste', icon: 'fa-paste', disabled: true },
    { label: 'Upload files', action: 'upload-files', icon: 'fa-upload' },
    {
      label: 'New',
      action: 'create-new',
      icon: 'fa-file-medical',
      submenu: [
        { label: 'Folder', action: 'new-folder', icon: 'fa-folder-plus' },
        { type: 'separator' },
        { label: 'File', action: 'new-file', icon: 'fa-file-medical' },
        { label: 'Text file', action: 'new-text-file', icon: 'fa-file-alt' },
        { label: 'Spreadsheet', action: 'new-spreadsheet', icon: 'fa-file-excel' },
        { label: 'Presentation', action: 'new-presentation', icon: 'fa-file-powerpoint' }
      ]
    },
    { type: 'separator' },
    { label: 'Help & Support', action: 'help-and-support', icon: 'fa-question-circle' },
    { label: 'Personalize', action: 'personalize', icon: 'fa-paint-brush' }
  ]
  
  showContextMenuAt(x, y, menuItems)
}

// Start button context menu
const showStartButtonContextMenu = (x: number, y: number) => {
  const menuItems = [
    { label: 'My Profile', action: 'my-profile-settings', icon: 'fa-user' },
    { label: 'Appearance', action: 'appearance-settings', icon: 'fa-paint-brush' },
    { label: 'Notifications', action: 'notifications-settings', icon: 'fa-bell' },
    { label: 'Privacy', action: 'privacy-settings', icon: 'fa-user-shield' },
    { label: 'Security', action: 'security-settings', icon: 'fa-lock' },
    { label: 'Integrations', action: 'integrations-settings', icon: 'fa-plug' },
    { label: 'Active Services', action: 'active-services', icon: 'fa-cogs' },
    { type: 'separator' },
    { label: 'My Website', action: 'my-website', icon: 'fa-globe' },
    { label: 'Products', action: 'products-settings', icon: 'fa-box-open' },
    { label: 'Payments', action: 'payments-settings', icon: 'fa-credit-card' },
    { label: 'Shipping', action: 'shipping-settings', icon: 'fa-shipping-fast' },
    { label: 'Customers & Privacy', action: 'customers-privacy-settings', icon: 'fa-users' },
    { label: 'Emails', action: 'emails-settings', icon: 'fa-envelope' },
    { label: 'Billing', action: 'billing-settings', icon: 'fa-file-invoice-dollar' },
    { type: 'separator' },
    { label: 'Open Settings', action: 'open-settings', icon: 'fa-cog' },
    { type: 'separator' },
    {
      label: 'Start menu style',
      action: 'start-menu-style',
      icon: 'fa-rocket',
      submenu: [
        { label: 'Default', action: 'start-menu-style-default', icon: 'fa-columns' },
        { label: 'Default Apps only', action: 'start-menu-style-default-apps-only', icon: 'fa-grip' },
        { label: 'Windows 11 Style', action: 'start-menu-style-windows11', icon: 'fa-square-poll-horizontal' },
        { label: 'List with sidebar', action: 'start-menu-style-apps-list-with-sidebar', icon: 'fa-book-open' },
        { label: 'List Apps only', action: 'start-menu-style-apps-list-only', icon: 'fa-table-list' },
        { type: 'separator' },
        { label: 'App Launcher', action: 'start-menu-style-app-launcher', icon: 'fa-table-cells' }
      ]
    }
  ]
  
  showContextMenuAt(x, y, menuItems)
}

// Desktop icon context menu
const showDesktopIconContextMenu = (x: number, y: number, iconElement: any) => {
  const menuItems = [
    { label: 'Open', action: 'open-app', icon: 'fa-folder-open' },
    { label: 'Pin To Taskbar', action: 'pin-to-taskbar', icon: 'fa-thumbtack' },
    { type: 'separator' },
    { label: 'Remove from Desktop', action: 'remove-from-desktop', icon: 'fa-trash' }
  ]
  
  showContextMenuAt(x, y, menuItems)
}

// Expose desktop methods globally for integration
if (typeof window !== 'undefined') {
  window.vueDesktop = {
    launchApp: handleAppLaunch,
    showNotifications: () => { showNotifications.value = true },
    showWidgets: () => { showWidgets.value = true },
    toggleStartMenu,
    toggleGlobalSearch
  }
}
</script>

<style scoped>
/* Component-specific styles if needed */
</style> 