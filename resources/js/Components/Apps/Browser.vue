<template>
  <div class="browser-app">
    <div class="browser-toolbar">
      <div class="navigation-controls">
        <button @click="goBack" :disabled="!canGoBack" class="nav-btn">
          <ArrowLeftIcon class="w-4 h-4" />
        </button>
        <button @click="goForward" :disabled="!canGoForward" class="nav-btn">
          <ArrowRightIcon class="w-4 h-4" />
        </button>
        <button @click="refresh" class="nav-btn">
          <ArrowPathIcon class="w-4 h-4" />
        </button>
      </div>
      
      <div class="address-bar">
        <input 
          v-model="currentUrl" 
          @keyup.enter="navigateToUrl"
          placeholder="Enter URL or search..." 
          class="url-input"
        />
        <button @click="navigateToUrl" class="go-btn">Go</button>
      </div>
      
      <div class="browser-actions">
        <button @click="bookmark" class="action-btn">
          <StarIcon class="w-4 h-4" />
        </button>
        <button @click="showMenu" class="action-btn">
          <EllipsisVerticalIcon class="w-4 h-4" />
        </button>
      </div>
    </div>
    
    <div class="browser-content">
      <iframe 
        v-if="iframeUrl"
        :src="iframeUrl" 
        class="browser-iframe"
        @load="onPageLoad"
      ></iframe>
      <div v-else class="start-page">
        <h1>New Tab</h1>
        <div class="quick-links">
          <button v-for="link in quickLinks" :key="link.url" 
                  @click="navigateTo(link.url)" class="quick-link">
            {{ link.name }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import {
  ArrowLeftIcon,
  ArrowRightIcon,
  ArrowPathIcon,
  StarIcon,
  EllipsisVerticalIcon
} from '@heroicons/vue/24/outline'

interface Props {
  windowId: string
  windowData?: Record<string, any>
}

const props = defineProps<Props>()

const emit = defineEmits<{
  updateTitle: [title: string]
  updateData: [data: Record<string, any>]
}>()

const currentUrl = ref('')
const iframeUrl = ref('')
const canGoBack = ref(false)
const canGoForward = ref(false)

const quickLinks = [
  { name: 'Google', url: 'https://google.com' },
  { name: 'GitHub', url: 'https://github.com' },
  { name: 'Stack Overflow', url: 'https://stackoverflow.com' }
]

const navigateToUrl = () => {
  if (currentUrl.value) {
    let url = currentUrl.value
    if (!url.startsWith('http://') && !url.startsWith('https://')) {
      url = 'https://' + url
    }
    iframeUrl.value = url
  }
}

const navigateTo = (url: string) => {
  currentUrl.value = url
  iframeUrl.value = url
}

const goBack = () => {
  console.log('Go back')
}

const goForward = () => {
  console.log('Go forward')
}

const refresh = () => {
  if (iframeUrl.value) {
    const iframe = document.querySelector('.browser-iframe') as HTMLIFrameElement
    if (iframe) {
      iframe.src = iframe.src
    }
  }
}

const bookmark = () => {
  console.log('Bookmark page')
}

const showMenu = () => {
  console.log('Show menu')
}

const onPageLoad = () => {
  console.log('Page loaded')
}

onMounted(() => {
  emit('updateTitle', 'Browser')
})
</script>

<style scoped>
.browser-app {
  width: 100%;
  height: 100%;
  background-color: #ffffff;
  display: flex;
  flex-direction: column;
}

.browser-app.dark {
  background-color: #111827;
}

.browser-toolbar {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-bottom: 1px solid #e5e7eb;
  background-color: #f9fafb;
}

.browser-toolbar.dark {
  border-bottom-color: #374151;
  background-color: #1f2937;
}

.navigation-controls {
  display: flex;
  align-items: center;
  gap: 4px;
}

.nav-btn {
  padding: 8px;
  border-radius: 4px;
  transition: background-color 0.2s ease;
  border: none;
  background: none;
  cursor: pointer;
}

.nav-btn:hover {
  background-color: #e5e7eb;
}

.nav-btn.dark:hover {
  background-color: #374151;
}

.nav-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.address-bar {
  display: flex;
  flex: 1;
  align-items: center;
  gap: 8px;
}

.url-input {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background-color: #ffffff;
  color: #111827;
  transition: all 0.2s ease;
}

.url-input.dark {
  border-color: #4b5563;
  background-color: #374151;
  color: #ffffff;
}

.url-input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
}

.go-btn {
  padding: 8px 16px;
  background-color: #2563eb;
  color: #ffffff;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.go-btn:hover {
  background-color: #1d4ed8;
}

.browser-actions {
  display: flex;
  align-items: center;
  gap: 4px;
}

.action-btn {
  padding: 8px;
  border-radius: 4px;
  transition: background-color 0.2s ease;
  border: none;
  background: none;
  cursor: pointer;
}

.action-btn:hover {
  background-color: #e5e7eb;
}

.action-btn.dark:hover {
  background-color: #374151;
}

.browser-content {
  flex: 1;
  position: relative;
}

.browser-iframe {
  width: 100%;
  height: 100%;
  border: none;
}

.start-page {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  padding: 32px;
  text-align: center;
}

.start-page h1 {
  font-size: 30px;
  font-weight: 700;
  margin-bottom: 32px;
  color: #111827;
}

.start-page.dark h1 {
  color: #ffffff;
}

.quick-links {
  display: grid;
  grid-template-columns: repeat(1, 1fr);
  gap: 16px;
}

@media (min-width: 768px) {
  .quick-links {
    grid-template-columns: repeat(3, 1fr);
  }
}

.quick-link {
  padding: 12px 24px;
  background-color: #f3f4f6;
  border-radius: 8px;
  color: #111827;
  text-decoration: none;
  transition: background-color 0.2s ease;
}

.quick-link.dark {
  background-color: #1f2937;
  color: #ffffff;
}

.quick-link:hover {
  background-color: #e5e7eb;
}

.quick-link.dark:hover {
  background-color: #374151;
}
</style> 