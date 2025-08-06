<!-- 
  Desktop Icons Component - Manages desktop icon grid and interactions
  
  This component provides the desktop icon grid functionality including
  icon display, selection, drag and drop, and context menu handling.
  It integrates with the global event system for desktop-wide interactions.
  
  Features:
  - Icon grid layout and rendering
  - Multi-select with drag selection
  - Drag and drop functionality
  - Context menu support
  - Event system integration
  - Responsive design
-->
<template>
  <div 
    class="desktop-icons-grid"
    @click="handleDesktopClick"
    @dragover.prevent
    @drop="handleDrop"
  >
    <div
      v-for="icon in icons"
      :key="icon.id"
      class="desktop-icon"
      :class="{ selected: selectedIcons.has(icon.id) }"
      :data-icon-id="icon.id"
      @click.stop="handleIconClick(icon, $event)"
      @dblclick="handleIconDoubleClick(icon)"
      @contextmenu.prevent="handleIconContextMenu(icon, $event)"
      @dragstart="handleDragStart(icon, $event)"
      @dragend="handleDragEnd"
      draggable="true"
    >
      <!-- Icon Image/Symbol -->
      <component
        :is="getIconComponent(icon)"
        class="desktop-icon-image"
        :class="icon.iconClass"
      />
      
      <!-- Icon Label -->
      <span class="desktop-icon-label">
        {{ icon.name }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { eventSystem } from '@/Core/EventSystem'
import { 
  FolderIcon, 
  DocumentIcon, 
  PhotoIcon,
  CalculatorIcon,
  CogIcon,
  GlobeAltIcon,
  EnvelopeIcon,
  PuzzlePieceIcon
} from '@heroicons/vue/24/outline'

/**
 * Desktop icon data structure
 */
interface DesktopIcon {
  /** Unique identifier for the icon */
  id: string
  /** Display name for the icon */
  name: string
  /** Type of icon (app, file, or folder) */
  type: 'app' | 'file' | 'folder'
  /** Application ID if this is an app icon */
  appId?: string
  /** File path if this is a file/folder icon */
  path?: string
  /** Icon identifier or URL */
  icon?: string
  /** CSS class for icon styling */
  iconClass?: string
  /** X coordinate for positioned icons */
  x?: number
  /** Y coordinate for positioned icons */
  y?: number
}

/**
 * Component props interface
 */
interface Props {
  /** Array of desktop icons to display */
  icons: DesktopIcon[]
}

const props = defineProps<Props>()

/**
 * Component emits interface
 */
const emit = defineEmits<{
  /** Emitted when an icon is clicked */
  iconClick: [icon: DesktopIcon, event: MouseEvent]
  /** Emitted when an icon is double-clicked */
  iconDoubleClick: [icon: DesktopIcon]
  /** Emitted when context menu is triggered on an icon */
  iconContextMenu: [icon: DesktopIcon, event: MouseEvent]
  /** Emitted when an icon is moved via drag and drop */
  iconMove: [icon: DesktopIcon, x: number, y: number]
  /** Emitted when icon selection changes */
  selectionChange: [selectedIds: string[]]
}>()

// State
/** Set of currently selected icon IDs */
const selectedIcons = ref<Set<string>>(new Set())
/** Currently dragged icon, if any */
const draggedIcon = ref<DesktopIcon | null>(null)

// Listen for drag selector events
const unsubscribe = ref<(() => void) | null>(null)

/**
 * Initialize component and set up event listeners
 */
onMounted(() => {
  // Listen for desktop selection changes from drag selector
  unsubscribe.value = eventSystem.on('desktop:selection-changed', (event: any) => {
    if (event.selectedIcons && Array.isArray(event.selectedIcons)) {
      // Sync selection state with drag selector
      const newSelection = new Set<string>()
      event.selectedIcons.forEach((iconElement: HTMLElement) => {
        const iconId = iconElement.dataset?.iconId
        if (iconId) {
          newSelection.add(iconId)
        }
      })
      selectedIcons.value = newSelection
      emit('selectionChange', Array.from(newSelection))
    }
  })
})

onUnmounted(() => {
  if (unsubscribe.value) {
    unsubscribe.value()
  }
})

// Icon component mapping
const iconComponents = {
  'folder': FolderIcon,
  'document': DocumentIcon,
  'photo': PhotoIcon,
  'calculator': CalculatorIcon,
  'settings': CogIcon,
  'browser': GlobeAltIcon,
  'email': EnvelopeIcon,
  'default': PuzzlePieceIcon
}

// Methods
const getIconComponent = (icon: DesktopIcon) => {
  if (icon.icon && iconComponents[icon.icon as keyof typeof iconComponents]) {
    return iconComponents[icon.icon as keyof typeof iconComponents]
  }
  
  // Determine icon based on type
  switch (icon.type) {
    case 'folder':
      return FolderIcon
    case 'file':
      return DocumentIcon
    case 'app':
      // Map app IDs to specific icons
      const appIconMap: Record<string, any> = {
        'calculator': CalculatorIcon,
        'settings': CogIcon,
        'file-explorer': FolderIcon,
        'browser': GlobeAltIcon,
        'email': EnvelopeIcon
      }
      return appIconMap[icon.appId || ''] || PuzzlePieceIcon
    default:
      return PuzzlePieceIcon
  }
}

const handleDesktopClick = (event: MouseEvent) => {
  // Clear selection when clicking empty desktop area
  if (event.target === event.currentTarget) {
    selectedIcons.value?.clear()
    updateDOMSelection()
    emit('selectionChange', [])
  }
}

const updateDOMSelection = () => {
  // Update DOM classes to match the selectedIcons state
  document.querySelectorAll('.desktop-icon').forEach(iconElement => {
    const htmlElement = iconElement as HTMLElement
    const iconId = htmlElement.dataset?.iconId
    if (iconId && selectedIcons.value?.has(iconId)) {
      iconElement.classList.add('selected')
    } else {
      iconElement.classList.remove('selected')
    }
  })
}

const handleIconClick = (icon: DesktopIcon, event: MouseEvent) => {
  if (event.ctrlKey || event.metaKey) {
    // Multi-select mode
    if (selectedIcons.value?.has(icon.id)) {
      selectedIcons.value.delete(icon.id)
    } else {
      selectedIcons.value?.add(icon.id)
    }
  } else if (event.shiftKey && (selectedIcons.value?.size || 0) > 0) {
    // Range select mode
    selectedIcons.value?.add(icon.id)
  } else {
    // Single select mode
    selectedIcons.value?.clear()
    selectedIcons.value?.add(icon.id)
  }
  
  // Update DOM classes to sync with drag selector
  updateDOMSelection()
  
  emit('selectionChange', Array.from(selectedIcons.value || []))
  emit('iconClick', icon, event)
}

const handleIconDoubleClick = (icon: DesktopIcon) => {
  if (!selectedIcons.value?.has(icon.id)) {
    selectedIcons.value?.clear()
    selectedIcons.value?.add(icon.id)
  }
  
  emit('selectionChange', Array.from(selectedIcons.value || []))
  emit('iconDoubleClick', icon)
}

const handleIconContextMenu = (icon: DesktopIcon, event: MouseEvent) => {
  // Select icon if not already selected
  if (!selectedIcons.value?.has(icon.id)) {
    selectedIcons.value?.clear()
    selectedIcons.value?.add(icon.id)
    updateDOMSelection()
    emit('selectionChange', Array.from(selectedIcons.value || []))
  }
  
  emit('iconContextMenu', icon, event)
}

const handleDragStart = (icon: DesktopIcon, event: DragEvent) => {
  draggedIcon.value = icon
  
  // Set drag data
  if (event.dataTransfer) {
    event.dataTransfer.effectAllowed = 'move'
    event.dataTransfer.setData('text/plain', icon.id)
  }
  
  // Add dragging class
  setTimeout(() => {
    const iconElement = event.target as HTMLElement
    iconElement.classList.add('opacity-50')
  }, 0)
}

const handleDragEnd = (event: DragEvent) => {
  // Remove dragging class
  const iconElement = event.target as HTMLElement
  iconElement.classList.remove('opacity-50')
  
  draggedIcon.value = null
}

const handleDrop = (event: DragEvent) => {
  event.preventDefault()
  
  if (!draggedIcon.value) return
  
  // Calculate grid position from drop coordinates
  const rect = (event.currentTarget as HTMLElement).getBoundingClientRect()
  const x = event.clientX - rect.left
  const y = event.clientY - rect.top
  
  // Emit icon move event
  emit('iconMove', draggedIcon.value, x, y)
}
</script>

<style scoped>
.desktop-icon {
  transition: all 0.2s ease;
}

.desktop-icon:hover {
  transform: scale(1.05);
}

.desktop-icon.selected {
  transform: scale(1.1);
}

.desktop-icon.opacity-50 {
  opacity: 0.5;
}
</style> 