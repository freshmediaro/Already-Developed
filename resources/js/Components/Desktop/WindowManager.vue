<template>
  <div class="windows-container">
    <TransitionGroup name="window" tag="div">
      <WindowBase
        v-for="window in windowList"
        :key="window.id"
        :window="window"
        :is-active="window.id === activeWindowId"
        @activate="handleWindowActivate"
        @close="handleWindowClose"
        @minimize="handleWindowMinimize"
        @maximize="handleWindowMaximize"
        @move="handleWindowMove"
        @resize="handleWindowResize"
      />
    </TransitionGroup>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue'
import WindowBase from './WindowBase.vue'

interface Window {
  id: string
  appId: string
  title: string
  component: string
  x: number
  y: number
  width: number
  height: number
  zIndex: number
  minimized: boolean
  maximized: boolean
  data?: Record<string, any>
}

interface Props {
  windows: Map<string, Window>
}

const props = defineProps<Props>()

const emit = defineEmits<{
  windowCreated: [window: Window]
  windowClosed: [windowId: string]
  windowActivated: [windowId: string]
  windowMinimized: [windowId: string]
  windowMaximized: [windowId: string]
  windowRestored: [windowId: string]
  windowMoved: [windowId: string, x: number, y: number]
  windowResized: [windowId: string, width: number, height: number]
}>()

// Convert Map to reactive array for v-for
const windowList = computed(() => {
  const windows = Array.from(props.windows.values()) as Window[]
  return windows
    .filter((window: Window) => !window.minimized)
    .sort((a: Window, b: Window) => a.zIndex - b.zIndex)
})

const activeWindowId = ref<string | null>(null)

// Window management methods
const handleWindowActivate = (windowId: string) => {
  activeWindowId.value = windowId
  
  // Update z-index for the activated window
  const window = props.windows.get(windowId)
  if (window) {
    window.zIndex = Date.now()
  }
  
  emit('windowActivated', windowId)
}

const handleWindowClose = (windowId: string) => {
  const window = props.windows.get(windowId)
  if (window) {
    // Remove from windows map
    props.windows.delete(windowId)
    
    // Update active window if this was the active one
    if (activeWindowId.value === windowId) {
      const remainingWindows = (Array.from(props.windows.values()) as Window[])
        .filter(w => !w.minimized)
        .sort((a, b) => b.zIndex - a.zIndex)
      
      activeWindowId.value = remainingWindows.length > 0 ? remainingWindows[0].id : null
    }
  }
  
  emit('windowClosed', windowId)
}

const handleWindowMinimize = (windowId: string) => {
  const window = props.windows.get(windowId)
  if (window) {
    window.minimized = true
    
    // Update active window
    if (activeWindowId.value === windowId) {
      const remainingWindows = (Array.from(props.windows.values()) as Window[])
        .filter(w => !w.minimized)
        .sort((a, b) => b.zIndex - a.zIndex)
      
      activeWindowId.value = remainingWindows.length > 0 ? remainingWindows[0].id : null
    }
  }
  
  emit('windowMinimized', windowId)
}

const handleWindowMaximize = (windowId: string) => {
  const window = props.windows.get(windowId)
  if (window) {
    if (window.maximized) {
      // Restore window
      window.maximized = false
      emit('windowRestored', windowId)
    } else {
      // Maximize window
      window.maximized = true
      emit('windowMaximized', windowId)
    }
  }
}

const handleWindowMove = (windowId: string, x: number, y: number) => {
  const window = props.windows.get(windowId)
  if (window && !window.maximized) {
    window.x = x
    window.y = y
  }
  
  emit('windowMoved', windowId, x, y)
}

const handleWindowResize = (windowId: string, width: number, height: number) => {
  const window = props.windows.get(windowId)
  if (window && !window.maximized) {
    window.width = Math.max(300, width)
    window.height = Math.max(200, height)
  }
  
  emit('windowResized', windowId, width, height)
}

// Global window management
const handleGlobalClick = (event: MouseEvent) => {
  // Check if click is outside all windows
  const windowElements = document.querySelectorAll('.window')
  let clickedInsideWindow = false
  
  for (const windowEl of windowElements) {
    if (windowEl.contains(event.target as Node)) {
      clickedInsideWindow = true
      break
    }
  }
  
  if (!clickedInsideWindow) {
    // Clicked on desktop - deactivate all windows
    activeWindowId.value = null
  }
}

onMounted(() => {
  document.addEventListener('click', handleGlobalClick)
})

onUnmounted(() => {
  document.removeEventListener('click', handleGlobalClick)
})

// Expose methods for external access
defineExpose({
  activateWindow: handleWindowActivate,
  closeWindow: handleWindowClose,
  minimizeWindow: handleWindowMinimize,
  maximizeWindow: handleWindowMaximize
})
</script>

<style scoped>
.windows-container {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

.window-enter-active,
.window-leave-active {
  transition: all 0.3s ease-out;
}

.window-enter-from {
  opacity: 0;
  transform: scale(0.95) translateY(20px);
}

.window-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(-20px);
}
</style> 