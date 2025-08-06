<template>
  <div class="iframe-app-container">
    <!-- Loading State -->
    <div v-if="loading" class="iframe-loading">
      <div class="loading-spinner"></div>
      <p>{{ loadingMessage }}</p>
    </div>
    
    <!-- Error State -->
    <div v-else-if="error" class="iframe-error">
      <div class="error-icon">
        <i class="fas fa-exclamation-triangle"></i>
      </div>
      <h3>Failed to Load Application</h3>
      <p>{{ error }}</p>
      <button @click="reload" class="btn btn-primary">
        <i class="fas fa-sync-alt"></i>
        Try Again
      </button>
    </div>
    
    <!-- Iframe Container -->
    <div v-else class="iframe-wrapper">
      <iframe
        ref="iframe"
        :src="iframeUrl"
        :sandbox="sandboxPermissions"
        :allow="allowedFeatures"
        allowfullscreen
        class="iframe-content"
        @load="onIframeLoad"
        @error="onIframeError"
      ></iframe>
    </div>
    
    <!-- Context Menu -->
    <div
      v-if="showContextMenu"
      class="iframe-context-menu"
      :style="{ left: contextMenu.x + 'px', top: contextMenu.y + 'px' }"
      @click.stop
    >
      <ul class="context-menu-list">
        <li @click="reloadIframe">
          <i class="fas fa-sync-alt"></i>
          Reload Application
        </li>
        <li v-if="appId === 'photoshop'" @click="saveProject">
          <i class="fas fa-save"></i>
          Save Project
        </li>
        <li v-if="appId === 'mail'" @click="composeEmail">
          <i class="fas fa-envelope"></i>
          Compose Email
        </li>
        <li @click="openInNewTab">
          <i class="fas fa-external-link-alt"></i>
          Open in New Tab
        </li>
        <li @click="showHelp">
          <i class="fas fa-question-circle"></i>
          Help
        </li>
      </ul>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'

interface Props {
  appId: string
  appConfig: {
    url: string
    allowedOrigins?: string[]
    enableCommunication?: boolean
    loadingMessage?: string
    errorMessage?: string
  }
  securityConfig: {
    sandboxPermissions: string[]
    allowedFeatures: string[]
    referrerPolicy?: string
  }
  windowId: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  updateTitle: [title: string]
  updateData: [data: Record<string, any>]
  iframeReady: []
  iframeError: [error: string]
  messageReceived: [message: any, origin: string]
}>()

// Reactive state
const iframe = ref<HTMLIFrameElement>()
const loading = ref(true)
const error = ref('')
const showContextMenu = ref(false)
const contextMenu = ref({ x: 0, y: 0 })

// Computed properties
const iframeUrl = computed(() => props.appConfig.url)
const loadingMessage = computed(() => props.appConfig.loadingMessage || 'Loading application...')
const sandboxPermissions = computed(() => props.securityConfig.sandboxPermissions.join(' '))
const allowedFeatures = computed(() => props.securityConfig.allowedFeatures.join('; '))

// Message handlers
const messageHandlers = new Map<string, (data?: any) => void>()

// Lifecycle hooks
onMounted(() => {
  setupMessageListener()
  setupContextMenu()
  fetchAppConfig()
})

onUnmounted(() => {
  cleanup()
})

// Methods
const fetchAppConfig = async () => {
  try {
    const response = await fetch(`/api/iframe-apps/${props.appId}/config`)
    if (!response.ok) {
      throw new Error('Failed to fetch app configuration')
    }
    
    const config = await response.json()
    console.log(`${props.appId} app config:`, config)
    
  } catch (err) {
    console.error('Failed to fetch app config:', err)
    error.value = 'Failed to load application configuration'
    loading.value = false
  }
}

const onIframeLoad = () => {
  loading.value = false
  error.value = ''
  
  // Send initial configuration to iframe if communication is enabled
  if (props.appConfig.enableCommunication) {
    sendInitialConfig()
  }
  
  emit('iframeReady')
}

const onIframeError = () => {
  loading.value = false
  error.value = props.appConfig.errorMessage || 'Failed to load application'
  emit('iframeError', error.value)
}

const sendInitialConfig = () => {
  const config = {
    type: 'config',
    payload: {
      appId: props.appId,
      windowId: props.windowId,
      theme: getTheme(),
      language: getLanguage(),
    }
  }
  
  sendMessageToIframe(config)
}

const sendMessageToIframe = (message: any) => {
  if (!iframe.value?.contentWindow) return
  
  try {
    iframe.value.contentWindow.postMessage(message, '*')
  } catch (err) {
    console.error('Failed to send message to iframe:', err)
  }
}

const setupMessageListener = () => {
  const handleMessage = (event: MessageEvent) => {
    // Verify origin if specified
    if (props.appConfig.allowedOrigins?.length && 
        !props.appConfig.allowedOrigins.includes(event.origin)) {
      console.warn('Message from untrusted origin:', event.origin)
      return
    }
    
    handleIframeMessage(event.data, event.origin)
    emit('messageReceived', event.data, event.origin)
  }
  
  window.addEventListener('message', handleMessage)
  
  // Store cleanup function
  messageHandlers.set('cleanup', (_data?: any) => {
    window.removeEventListener('message', handleMessage)
  })
}

const handleIframeMessage = (data: any, origin: string) => {
  if (typeof data !== 'object' || !data.type) return
  
  switch (data.type) {
    case 'titleUpdate':
      if (data.title) {
        emit('updateTitle', data.title)
      }
      break
      
    case 'dataUpdate':
      if (data.payload) {
        emit('updateData', data.payload)
      }
      break
      
    case 'ready':
      console.log(`${props.appId} iframe is ready`)
      break
      
    case 'error':
      console.error(`${props.appId} iframe error:`, data.message)
      break
      
    default:
      // Handle app-specific messages
      const handler = messageHandlers.get(data.type)
      if (handler) {
        handler(data.payload)
      }
  }
}

const setupContextMenu = () => {
  const handleContextMenu = (event: MouseEvent) => {
    event.preventDefault()
    contextMenu.value = { x: event.clientX, y: event.clientY }
    showContextMenu.value = true
  }
  
  const handleClick = () => {
    showContextMenu.value = false
  }
  
  nextTick(() => {
    iframe.value?.addEventListener('contextmenu', handleContextMenu)
    document.addEventListener('click', handleClick)
    
    messageHandlers.set('contextMenu', () => {
      iframe.value?.removeEventListener('contextmenu', handleContextMenu)
      document.removeEventListener('click', handleClick)
    })
  })
}

const reloadIframe = () => {
  if (iframe.value) {
    loading.value = true
    error.value = ''
    iframe.value.src = iframe.value.src
  }
  showContextMenu.value = false
}

const reload = () => {
  reloadIframe()
}

const saveProject = async () => {
  if (props.appId !== 'photoshop') return
  
  try {
    // Send save request to Photoshop iframe
    sendMessageToIframe({
      type: 'action',
      payload: { action: 'save' }
    })
    
  } catch (err) {
    console.error('Failed to save project:', err)
  }
  
  showContextMenu.value = false
}

const composeEmail = () => {
  if (props.appId !== 'mail') return
  
  sendMessageToIframe({
    type: 'compose',
    payload: {}
  })
  
  showContextMenu.value = false
}

const openInNewTab = () => {
  window.open(iframeUrl.value, '_blank')
  showContextMenu.value = false
}

const showHelp = () => {
  const helpUrls = {
    photoshop: 'https://www.photopea.com/learn',
    mail: '#', // Would link to mail app documentation
  }
  
  const url = helpUrls[props.appId as keyof typeof helpUrls]
  if (url && url !== '#') {
    window.open(url, '_blank')
  }
  
  showContextMenu.value = false
}

const getTheme = (): string => {
  return document.documentElement.classList.contains('dark') ? 'dark' : 'light'
}

const getLanguage = (): string => {
  return navigator.language || 'en-US'
}

const cleanup = () => {
  // Call specific cleanup functions
  const cleanupHandler = messageHandlers.get('cleanup')
  if (cleanupHandler) {
    cleanupHandler({})
  }
  
  const contextMenuHandler = messageHandlers.get('contextMenu')
  if (contextMenuHandler) {
    contextMenuHandler({})
  }
  
  messageHandlers.clear()
}

// Register message handler for external use
const registerMessageHandler = (type: string, handler: (data: any) => void) => {
  messageHandlers.set(type, handler)
}

// Expose methods for parent component
defineExpose({
  sendMessageToIframe,
  registerMessageHandler,
  reloadIframe,
})
</script>

<style scoped>
.iframe-app-container {
  width: 100%;
  height: 100%;
  position: relative;
  overflow: hidden;
}

.iframe-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  padding: 2rem;
  color: var(--text-color);
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid var(--border-color);
  border-top: 3px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.iframe-error {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  padding: 2rem;
  text-align: center;
  color: var(--text-color);
}

.error-icon {
  font-size: 3rem;
  color: var(--error-color);
  margin-bottom: 1rem;
}

.iframe-wrapper {
  width: 100%;
  height: 100%;
}

.iframe-content {
  width: 100%;
  height: 100%;
  border: none;
  display: block;
}

.iframe-context-menu {
  position: fixed;
  background: var(--surface-color);
  border: 1px solid var(--border-color);
  border-radius: 6px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 10000;
  min-width: 180px;
}

.context-menu-list {
  list-style: none;
  margin: 0;
  padding: 4px 0;
}

.context-menu-list li {
  padding: 8px 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--text-color);
  transition: background-color 0.2s;
}

.context-menu-list li:hover {
  background-color: var(--hover-color);
}

.context-menu-list li i {
  width: 16px;
  text-align: center;
  opacity: 0.7;
}

.btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
}

.btn-primary:hover {
  background-color: var(--primary-color-dark);
}
</style> 