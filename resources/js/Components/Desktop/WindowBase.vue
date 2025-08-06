<template>
  <div
    ref="windowElement"
    class="window"
    :class="{
      active: isActive,
      maximized: window.maximized,
      minimized: window.minimized
    }"
    :style="windowStyle"
    @mousedown="handleActivate"
  >
    <!-- Window Header -->
    <div
      ref="headerElement"
      class="window-header"
      @mousedown="startDrag"
      @dblclick="handleHeaderDoubleClick"
    >
      <div class="window-title">
        <div class="window-icon" :class="appMetadata.iconClass">
          <i :class="appMetadata.icon"></i>
        </div>
        <span>{{ appMetadata.title }}</span>
      </div>
      
      <div class="window-controls">
        <button
          class="window-minimize"
          @click.stop="$emit('minimize', window.id)"
          title="Minimize"
        >
        </button>
        
        <button
          class="window-popout"
          @click.stop="$emit('popout', window.id)"
          title="Pop out"
        >
        </button>
        
        <button
          class="window-maximize"
          :class="{ restore: window.maximized }"
          @click.stop="$emit('maximize', window.id)"
          :title="window.maximized ? 'Restore' : 'Maximize'"
        >
        </button>
        
        <button
          class="window-close"
          @click.stop="$emit('close', window.id)"
          title="Close"
        >
        </button>
      </div>
    </div>
    
    <!-- Window Content -->
    <div class="window-content">
      <div class="window-content-scrollable">
        <!-- Dynamic App Component -->
        <Suspense>
          <template #default>
            <component
              :is="appComponent"
              :window-id="window.id"
              :window-data="window.data"
              v-bind="window.data"
              @update-title="handleTitleUpdate"
              @update-data="handleDataUpdate"
            />
          </template>
          <template #fallback>
            <div class="window-loading">
              <ArrowPathIcon class="window-loading-spinner" />
              Loading {{ window.title }}...
            </div>
          </template>
        </Suspense>
      </div>
    </div>
    
    <!-- Resize Handles (only when not maximized) -->
    <template v-if="!window.maximized && !isMobile">
      <div class="window-resize-handle n" @mousedown="startResize('n')"></div>
      <div class="window-resize-handle s" @mousedown="startResize('s')"></div>
      <div class="window-resize-handle e" @mousedown="startResize('e')"></div>
      <div class="window-resize-handle w" @mousedown="startResize('w')"></div>
      <div class="window-resize-handle ne" @mousedown="startResize('ne')"></div>
      <div class="window-resize-handle nw" @mousedown="startResize('nw')"></div>
      <div class="window-resize-handle se" @mousedown="startResize('se')"></div>
      <div class="window-resize-handle sw" @mousedown="startResize('sw')"></div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, defineAsyncComponent } from 'vue'
import {
  ArrowPathIcon
} from '@heroicons/vue/24/outline'
import { provideAppMetadata } from '@/composables/useAppMetadata'

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
  data?: Record<string, unknown>
}

interface Props {
  window: Window
  isActive: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  activate: [windowId: string]
  close: [windowId: string]
  minimize: [windowId: string]
  maximize: [windowId: string]
  popout: [windowId: string]
  move: [windowId: string, x: number, y: number]
  resize: [windowId: string, width: number, height: number]
}>()

// App metadata provider
const { metadata: appMetadata } = provideAppMetadata()

// Refs
const windowElement = ref<HTMLElement>()
const headerElement = ref<HTMLElement>()

// State
const isDragging = ref(false)
const isResizing = ref(false)
const resizeDirection = ref('')
const dragOffset = ref({ x: 0, y: 0 })

// Computed
const isMobile = computed(() => window.innerWidth <= 768)

const windowStyle = computed(() => {
  if (props.window.maximized) {
    return {
      zIndex: props.window.zIndex
    }
  }

  return {
    left: `${props.window.x}px`,
    top: `${props.window.y}px`,
    width: `${props.window.width}px`,
    height: `${props.window.height}px`,
    zIndex: props.window.zIndex
  }
})

// Dynamic component loading
const appComponents = {
  'Calculator': defineAsyncComponent(() => import('@/Components/Apps/Calculator.vue')),
  'FileExplorer': defineAsyncComponent(() => import('@/Components/Apps/FileExplorer.vue')),
  'Settings': defineAsyncComponent(() => import('@/Components/Apps/Settings.vue')),
  'SiteBuilder': defineAsyncComponent(() => import('@/Components/Apps/SiteBuilder.vue')),
  'AppStore': defineAsyncComponent(() => import('@/Components/Apps/AppStore.vue')),
  'Email': defineAsyncComponent(() => import('@/Components/Apps/Email.vue')),
  'Browser': defineAsyncComponent(() => import('@/Components/Apps/Browser.vue'))
}

const appComponent = computed(() => {
  return appComponents[props.window.component as keyof typeof appComponents] || 
         defineAsyncComponent(() => import('@/Components/Apps/DefaultApp.vue'))
})

// Event handlers
const handleActivate = () => {
  if (!props.isActive) {
    emit('activate', props.window.id)
  }
}

const handleHeaderDoubleClick = () => {
  if (!isMobile.value) {
    emit('maximize', props.window.id)
  }
}

const handleTitleUpdate = (newTitle: string) => {
  props.window.title = newTitle
}

const handleDataUpdate = (newData: Record<string, any>) => {
  props.window.data = { ...props.window.data, ...newData }
}

// Drag functionality
const startDrag = (event: MouseEvent) => {
  if (props.window.maximized || isMobile.value) return
  
  isDragging.value = true
  dragOffset.value = {
    x: event.clientX - props.window.x,
    y: event.clientY - props.window.y
  }
  
  document.addEventListener('mousemove', handleDrag)
  document.addEventListener('mouseup', stopDrag)
  event.preventDefault()
}

const handleDrag = (event: MouseEvent) => {
  if (!isDragging.value) return
  
  const newX = event.clientX - (dragOffset.value?.x || 0)
  const newY = event.clientY - (dragOffset.value?.y || 0)
  
  // Constrain to viewport
  const maxX = window.innerWidth - props.window.width
  const maxY = window.innerHeight - props.window.height
  
  const constrainedX = Math.max(0, Math.min(maxX, newX))
  const constrainedY = Math.max(0, Math.min(maxY, newY))
  
  emit('move', props.window.id, constrainedX, constrainedY)
}

const stopDrag = () => {
  isDragging.value = false
  document.removeEventListener('mousemove', handleDrag)
  document.removeEventListener('mouseup', stopDrag)
}

// Resize functionality
const startResize = (direction: string) => {
  if (props.window.maximized || isMobile.value) return
  
  isResizing.value = true
  resizeDirection.value = direction
  
  document.addEventListener('mousemove', handleResize)
  document.addEventListener('mouseup', stopResize)
}

const handleResize = (event: MouseEvent) => {
  if (!isResizing.value) return
  
  const rect = windowElement.value?.getBoundingClientRect()
  if (!rect) return
  
  let newWidth = props.window.width
  let newHeight = props.window.height
  let newX = props.window.x
  let newY = props.window.y
  
  const direction = resizeDirection.value || ''
  
  if (direction.includes('e')) {
    newWidth = event.clientX - rect.left
  }
  if (direction.includes('w')) {
    newWidth = rect.right - event.clientX
    newX = event.clientX
  }
  if (direction.includes('s')) {
    newHeight = event.clientY - rect.top
  }
  if (direction.includes('n')) {
    newHeight = rect.bottom - event.clientY
    newY = event.clientY
  }
  
  // Apply constraints
  newWidth = Math.max(300, Math.min(window.innerWidth, newWidth))
  newHeight = Math.max(200, Math.min(window.innerHeight, newHeight))
  
  emit('resize', props.window.id, newWidth, newHeight)
  if (newX !== props.window.x || newY !== props.window.y) {
    emit('move', props.window.id, newX, newY)
  }
}

const stopResize = () => {
  isResizing.value = false
  resizeDirection.value = ''
  document.removeEventListener('mousemove', handleResize)
  document.removeEventListener('mouseup', stopResize)
}

// Cleanup
onUnmounted(() => {
  document.removeEventListener('mousemove', handleDrag)
  document.removeEventListener('mouseup', stopDrag)
  document.removeEventListener('mousemove', handleResize)
  document.removeEventListener('mouseup', stopResize)
})
</script>

<style scoped>
/* Additional window-specific styles */
.window {
  pointer-events: auto;
}

.window-header {
  user-select: none;
}

.window-loading {
  padding: 2rem;
}

.window-loading-spinner {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style> 