<template>
  <div class="file-explorer-app">
    <!-- Toolbar -->
    <div class="file-explorer-toolbar">
      <div class="toolbar-left">
        <button @click="goBack" :disabled="!canGoBack" class="toolbar-btn">
          <ArrowLeftIcon class="w-4 h-4" />
        </button>
        <button @click="goForward" :disabled="!canGoForward" class="toolbar-btn">
          <ArrowRightIcon class="w-4 h-4" />
        </button>
        <button @click="goUp" :disabled="!canGoUp" class="toolbar-btn">
          <ArrowUpIcon class="w-4 h-4" />
        </button>
        <button @click="refresh" class="toolbar-btn">
          <ArrowPathIcon class="w-4 h-4" />
        </button>
      </div>
      
      <div class="breadcrumb">
        <span 
          v-for="(crumb, index) in breadcrumbs" 
          :key="index"
          @click="navigateToCrumb(index)"
          class="breadcrumb-item"
        >
          {{ crumb.name }}
          <ChevronRightIcon v-if="index < breadcrumbs.length - 1" class="w-3 h-3" />
        </span>
      </div>
      
      <div class="toolbar-right">
        <button @click="toggleView" class="toolbar-btn">
          <component :is="viewMode === 'grid' ? ListBulletIcon : Squares2X2Icon" class="w-4 h-4" />
        </button>
        <button @click="showElFinderMode = !showElFinderMode" class="toolbar-btn">
          <EyeIcon class="w-4 h-4" />
        </button>
      </div>
    </div>
    
    <!-- ElFinder Mode -->
    <div v-if="showElFinderMode" class="elfinder-container">
      <iframe 
        ref="elfinderIframe"
        :src="elfinderUrl"
        class="elfinder-iframe"
        @load="onElFinderLoad"
      ></iframe>
    </div>
    
    <!-- Custom File Explorer Mode -->
    <div v-else class="file-explorer-content">
      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <ArrowPathIcon class="w-8 h-8 animate-spin" />
        <span>Loading files...</span>
      </div>
      
      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <ExclamationTriangleIcon class="w-8 h-8 text-red-500" />
        <span>{{ error }}</span>
        <button @click="refresh" class="btn-primary">Try Again</button>
      </div>
      
      <!-- File List -->
      <div v-else class="file-list" :class="viewMode">
        <div
          v-for="file in files"
          :key="file.hash || file.name"
          @click="selectFile(file, $event)"
          @dblclick="openFile(file)"
          @contextmenu.prevent="showContextMenu(file, $event)"
          :class="[
            'file-item',
            { 'selected': selectedFiles.has(file.hash || file.name) }
          ]"
        >
          <!-- File Icon -->
          <div class="file-icon">
            <component :is="getFileIcon(file)" class="w-8 h-8" />
          </div>
          
          <!-- File Info -->
          <div class="file-info">
            <div class="file-name">{{ file.name }}</div>
            <div v-if="viewMode === 'list'" class="file-details">
              <span class="file-size">{{ formatFileSize(file.size) }}</span>
              <span class="file-date">{{ formatDate(file.ts) }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Context Menu -->
    <div
      v-if="contextMenu.show"
      :style="{ left: contextMenu.x + 'px', top: contextMenu.y + 'px' }"
      class="context-menu"
      @click.stop
    >
      <button @click="openSelectedFile" class="context-item">
        <DocumentIcon class="w-4 h-4" />
        Open
      </button>
      <button @click="copyFiles" class="context-item">
        <DocumentDuplicateIcon class="w-4 h-4" />
        Copy
      </button>
      <button @click="cutFiles" class="context-item">
        <ScissorsIcon class="w-4 h-4" />
        Cut
      </button>
      <button @click="deleteFiles" class="context-item text-red-600">
        <TrashIcon class="w-4 h-4" />
        Delete
      </button>
      <hr class="context-separator">
      <button @click="showProperties" class="context-item">
        <InformationCircleIcon class="w-4 h-4" />
        Properties
      </button>
    </div>
    
    <!-- Upload Area -->
    <div
      v-if="isDragOver"
      class="upload-overlay"
      @drop.prevent="handleDrop"
      @dragover.prevent
      @dragleave="isDragOver = false"
    >
      <div class="upload-message">
        <CloudArrowUpIcon class="w-16 h-16" />
        <span>Drop files here to upload</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import {
  HomeIcon,
  ChevronRightIcon,
  ArrowLeftIcon,
  ArrowRightIcon,
  PlusIcon,
  FolderIcon,
  DocumentTextIcon,
  PhotoIcon,
  PlayIcon,
  MusicalNoteIcon,
  DocumentIcon,
  ArchiveBoxIcon,
  Bars3Icon,
  Squares2X2Icon,
  MagnifyingGlassIcon,
  EllipsisVerticalIcon,
  DocumentIcon as DocumentDuplicateIcon,
  XMarkIcon,
  ClipboardIcon,
  TrashIcon,
  EyeIcon,
  PencilIcon,
  ShareIcon
} from '@heroicons/vue/24/outline'
import { useAppMetadata } from '@/composables/useAppMetadata'

interface Props {
  windowId: string
  windowData?: Record<string, any>
}

interface FileItem {
  hash?: string
  name: string
  size: number
  ts: number
  mime: string
  isDir?: boolean
  read?: boolean
  write?: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  updateTitle: [title: string]
  updateData: [data: Record<string, any>]
}>()

// Set app metadata
const { updateMetadata } = useAppMetadata()
updateMetadata({
  title: 'File Explorer',
  icon: 'fas fa-folder',
  iconClass: 'pink-icon'
})

// State
const loading = ref(false)
const error = ref('')
const files = ref<FileItem[]>([])
const currentPath = ref('/')
const viewMode = ref<'grid' | 'list'>('grid')
const showElFinderMode = ref(false)
const selectedFiles = ref(new Set<string>())
const navigationHistory = ref<string[]>(['/'])
const historyIndex = ref(0)
const isDragOver = ref(false)

// ElFinder
const elfinderIframe = ref<HTMLIFrameElement>()
const elfinderUrl = computed(() => '/api/files/elfinder')

// Context menu
const contextMenu = ref({
  show: false,
  x: 0,
  y: 0,
  file: null as FileItem | null
})

// Computed
const breadcrumbs = computed(() => {
  const parts = (currentPath.value || '/').split('/').filter(Boolean)
  const crumbs = [{ name: 'Home', path: '/' }]
  
  let path = ''
  for (const part of parts) {
    path += '/' + part
    crumbs.push({ name: part, path })
  }
  
  return crumbs
})

const canGoBack = computed(() => (historyIndex.value || 0) > 0)
const canGoForward = computed(() => (historyIndex.value || 0) < ((navigationHistory.value || []).length - 1))
const canGoUp = computed(() => (currentPath.value || '/') !== '/')

// Methods
const loadFiles = async (path: string = (currentPath.value || '/')) => {
  loading.value = true
  error.value = ''
  
  try {
    const response = await fetch(`/api/files/folder?path=${encodeURIComponent(path)}`, {
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (!response.ok) {
      throw new Error('Failed to load files')
    }
    
    const data = await response.json()
    files.value = data.files || []
    currentPath.value = path
    
    // Update navigation history
    if ((navigationHistory.value || [])[historyIndex.value || 0] !== path) {
      navigationHistory.value = (navigationHistory.value || []).slice(0, (historyIndex.value || 0) + 1)
      navigationHistory.value?.push(path)
      historyIndex.value = (navigationHistory.value?.length || 1) - 1
    }
    
    emit('updateData', { currentPath: path, files: files.value })
    
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load files'
    console.error('Failed to load files:', err)
  } finally {
    loading.value = false
  }
}

const navigateTo = (path: string) => {
  loadFiles(path)
}

const navigateToCrumb = (index: number) => {
  const crumb = breadcrumbs.value[index]
  if (crumb) {
    navigateTo(crumb.path)
  }
}

const goBack = () => {
  if (canGoBack.value) {
    historyIndex.value = (historyIndex.value || 1) - 1
    loadFiles((navigationHistory.value || [])[historyIndex.value || 0])
  }
}

const goForward = () => {
  if (canGoForward.value) {
    historyIndex.value = (historyIndex.value || 0) + 1
    loadFiles((navigationHistory.value || [])[historyIndex.value || 0])
  }
}

const goUp = () => {
  if (canGoUp.value) {
    const parentPath = (currentPath.value || '/').split('/').slice(0, -1).join('/') || '/'
    navigateTo(parentPath)
  }
}

const refresh = () => {
  loadFiles(currentPath.value)
}

const toggleView = () => {
  viewMode.value = viewMode.value === 'grid' ? 'list' : 'grid'
  emit('updateData', { viewMode: viewMode.value })
}

const selectFile = (file: FileItem, event: MouseEvent) => {
  if (!event.ctrlKey && !event.metaKey) {
    selectedFiles.value?.clear()
  }
  
  const fileId = file.hash || file.name
  if (selectedFiles.value?.has(fileId)) {
    selectedFiles.value?.delete(fileId)
  } else {
    selectedFiles.value?.add(fileId)
  }
}

const openFile = (file: FileItem) => {
  if (file.isDir) {
    const newPath = (currentPath.value || '/') === '/' ? `/${file.name}` : `${(currentPath.value || '/')}/${file.name}`
    navigateTo(newPath)
  } else {
    // Handle file opening based on mime type
    console.log('Opening file:', file.name)
  }
}

const showContextMenu = (file: FileItem, event: MouseEvent) => {
  contextMenu.value = {
    show: true,
    x: event.clientX,
    y: event.clientY,
    file
  }
  
  // Add click outside listener
  nextTick(() => {
    document.addEventListener('click', hideContextMenu)
  })
}

const hideContextMenu = () => {
  if (contextMenu.value) {
    contextMenu.value.show = false
  }
  document.removeEventListener('click', hideContextMenu)
}

const openSelectedFile = () => {
  if (contextMenu.value?.file) {
    openFile(contextMenu.value.file)
  }
  hideContextMenu()
}

const copyFiles = () => {
  // Implement copy functionality
  console.log('Copy files')
  hideContextMenu()
}

const cutFiles = () => {
  // Implement cut functionality
  console.log('Cut files')
  hideContextMenu()
}

const deleteFiles = async () => {
  // Implement delete functionality
  console.log('Delete files')
  hideContextMenu()
}

const showProperties = () => {
  // Implement properties dialog
  console.log('Show properties')
  hideContextMenu()
}

const handleDrop = (event: DragEvent) => {
  isDragOver.value = false
  const files = event.dataTransfer?.files
  if (files) {
    uploadFiles(files)
  }
}

const uploadFiles = async (files: FileList) => {
  // Implement file upload
  console.log('Upload files:', files)
}

const getFileIcon = (file: FileItem) => {
  if (file.isDir) return FolderIcon
  
  const mime = file.mime || ''
  if (mime.startsWith('image/')) return PhotoIcon
  if (mime.startsWith('audio/')) return MusicalNoteIcon
  if (mime.startsWith('video/')) return PlayIcon
  if (mime.includes('zip') || mime.includes('archive')) return ArchiveBoxIcon
  
  return DocumentIcon
}

const formatFileSize = (size: number): string => {
  if (size === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(size) / Math.log(k))
  return parseFloat((size / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatDate = (timestamp: number): string => {
  return new Date(timestamp * 1000).toLocaleDateString()
}

const onElFinderLoad = () => {
  console.log('ElFinder loaded')
}

// Drag and drop
const handleDragEnter = (event: DragEvent) => {
  event.preventDefault()
  isDragOver.value = true
}

const handleDragLeave = (event: DragEvent) => {
  event.preventDefault()
  // Only hide if we're leaving the window
  if (!event.relatedTarget) {
    isDragOver.value = false
  }
}

// Lifecycle
onMounted(() => {
  emit('updateTitle', 'File Explorer')
  
  // Restore state
  if (props.windowData) {
    currentPath.value = props.windowData.currentPath || '/'
    viewMode.value = props.windowData.viewMode || 'grid'
  }
  
  // Load initial files
  loadFiles()
  
  // Setup drag and drop
  document.addEventListener('dragenter', handleDragEnter)
  document.addEventListener('dragleave', handleDragLeave)
})

onUnmounted(() => {
  document.removeEventListener('click', hideContextMenu)
  document.removeEventListener('dragenter', handleDragEnter)
  document.removeEventListener('dragleave', handleDragLeave)
})
</script>

<style scoped>
.file-explorer-app {
  width: 100%;
  height: 100%;
  background-color: #ffffff;
  display: flex;
  flex-direction: column;
}

.file-explorer-app.dark {
  background-color: #111827;
}

.file-explorer-toolbar {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px;
  border-bottom: 1px solid #e5e7eb;
  background-color: #f9fafb;
}

.file-explorer-toolbar.dark {
  border-bottom-color: #374151;
  background-color: #1f2937;
}

.toolbar-left {
  display: flex;
  align-items: center;
  gap: 4px;
}

.toolbar-btn {
  padding: 8px;
  border-radius: 4px;
  transition: background-color 0.2s ease;
  border: none;
  background: none;
  cursor: pointer;
}

.toolbar-btn:hover {
  background-color: #e5e7eb;
}

.toolbar-btn.dark:hover {
  background-color: #374151;
}

.toolbar-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 4px;
  flex: 1;
  margin: 0 16px;
  font-size: 14px;
}

.breadcrumb-item {
  display: flex;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  transition: color 0.2s ease;
}

.breadcrumb-item:hover {
  color: #2563eb;
}

.breadcrumb-item.dark:hover {
  color: #60a5fa;
}

.toolbar-right {
  display: flex;
  align-items: center;
  gap: 4px;
}

.elfinder-container {
  flex: 1;
  position: relative;
}

.elfinder-iframe {
  width: 100%;
  height: 100%;
  border: none;
}

.file-explorer-content {
  flex: 1;
  position: relative;
  overflow: auto;
}

.loading-state,
.error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  gap: 16px;
  color: #6b7280;
}

.file-list {
  padding: 16px;
}

.file-list.grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 16px;
}

.file-list.list {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.file-item {
  padding: 8px;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s ease;
  border: 2px solid transparent;
}

.file-item:hover {
  background-color: #f3f4f6;
}

.file-item.dark:hover {
  background-color: #1f2937;
}

.file-item.selected {
  background-color: #eff6ff;
  border-color: #3b82f6;
}

.file-item.selected.dark {
  background-color: rgba(59, 130, 246, 0.1);
}

.file-list.grid .file-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.file-list.list .file-item {
  display: flex;
  align-items: center;
  gap: 12px;
}

.file-icon {
  color: #4b5563;
}

.file-icon.dark {
  color: #9ca3af;
}

.file-info {
  min-width: 0;
}

.file-name {
  font-size: 14px;
  font-weight: 500;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.file-list.grid .file-name {
  margin-top: 8px;
  max-width: 100%;
}

.file-details {
  display: flex;
  gap: 16px;
  font-size: 12px;
  color: #6b7280;
}

.file-details.dark {
  color: #9ca3af;
}

.context-menu {
  position: fixed;
  background-color: #ffffff;
  border-radius: 6px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
  padding: 4px 0;
  min-width: 150px;
  z-index: 50;
}

.context-menu.dark {
  background-color: #1f2937;
  border-color: #374151;
}

.context-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  text-align: left;
  font-size: 14px;
  border: none;
  background: none;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.context-item:hover {
  background-color: #f3f4f6;
}

.context-item.dark:hover {
  background-color: #374151;
}

.context-separator {
  border-color: #e5e7eb;
  margin: 4px 0;
}

.context-separator.dark {
  border-color: #374151;
}

.upload-overlay {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  background-color: rgba(59, 130, 246, 0.2);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 40;
}

.upload-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
  color: #2563eb;
  font-size: 20px;
  font-weight: 600;
}

.upload-message.dark {
  color: #60a5fa;
}

.btn-primary {
  padding: 8px 16px;
  background-color: #2563eb;
  color: #ffffff;
  border-radius: 4px;
  border: none;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.btn-primary:hover {
  background-color: #1d4ed8;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .file-list.grid {
    grid-template-columns: repeat(3, 1fr);
  }
  
  .breadcrumb {
    display: none;
  }
  
  .toolbar-right {
    margin-left: auto;
  }
}
</style> 