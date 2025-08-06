<template>
  <div class="site-builder">
    <div class="site-builder-header">
      <h1 class="title">Site Builder</h1>
      <div class="header-actions">
        <button @click="previewSite" class="btn btn-secondary">Preview</button>
        <button @click="publishSite" class="btn btn-primary">Publish</button>
      </div>
    </div>
    
    <div class="site-builder-content">
      <div class="sidebar">
        <div class="sidebar-section">
          <h3>Pages</h3>
          <ul class="page-list">
            <li v-for="page in pages" :key="page.id" @click="selectPage(page)" 
                :class="{ active: activePage?.id === page.id }">
              {{ page.name }}
            </li>
          </ul>
          <button @click="addPage" class="btn btn-sm">Add Page</button>
        </div>
        
        <div class="sidebar-section">
          <h3>Elements</h3>
          <div class="elements-grid">
            <button v-for="element in elements" :key="element.type" 
                    @click="addElement(element)" class="element-btn">
              {{ element.name }}
            </button>
          </div>
        </div>
      </div>
      
      <div class="canvas">
        <div v-if="activePage" class="page-canvas">
          <h2>{{ activePage.name }}</h2>
          <p>Page content would be rendered here...</p>
          <div class="elements-list">
            <div v-for="element in activePage.elements" :key="element.id" class="element-item">
              {{ element.type }}: {{ element.content }}
            </div>
          </div>
        </div>
        <div v-else class="no-page-selected">
          Select a page to start editing
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'

interface Props {
  windowId: string
  windowData?: Record<string, any>
}

interface Page {
  id: string
  name: string
  elements: any[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  updateTitle: [title: string]
  updateData: [data: Record<string, any>]
}>()

const pages = ref<Page[]>([
  { id: '1', name: 'Home', elements: [] },
  { id: '2', name: 'About', elements: [] }
])

const activePage = ref<Page | null>(pages.value?.[0] || null)

const elements = [
  { type: 'text', name: 'Text' },
  { type: 'image', name: 'Image' },
  { type: 'button', name: 'Button' }
]

const selectPage = (page: Page) => {
  activePage.value = page
}

const addPage = () => {
  const newPage: Page = {
    id: Date.now().toString(),
    name: `Page ${(pages.value?.length || 0) + 1}`,
    elements: []
  }
  pages.value?.push(newPage)
}

const addElement = (element: any) => {
  if (activePage.value) {
    activePage.value.elements.push({
      id: Date.now().toString(),
      type: element.type,
      content: `New ${element.name}`
    })
  }
}

const previewSite = () => {
  console.log('Preview site')
}

const publishSite = () => {
  console.log('Publish site')
}

onMounted(() => {
  emit('updateTitle', 'Site Builder')
})
</script>

<style scoped>
.site-builder {
  width: 100%;
  height: 100%;
  background-color: #ffffff;
  display: flex;
  flex-direction: column;
}

.site-builder.dark {
  background-color: #111827;
}

.site-builder-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.site-builder-header.dark {
  border-bottom-color: #374151;
}

.title {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
}

.title.dark {
  color: #ffffff;
}

.header-actions {
  display: flex;
  gap: 8px;
}

.btn {
  padding: 8px 16px;
  border-radius: 6px;
  font-weight: 500;
  transition: all 0.2s ease;
  border: none;
  cursor: pointer;
}

.btn-primary {
  background-color: #2563eb;
  color: #ffffff;
}

.btn-primary:hover {
  background-color: #1d4ed8;
}

.btn-secondary {
  border: 1px solid #d1d5db;
  background-color: transparent;
}

.btn-secondary.dark {
  border-color: #4b5563;
}

.btn-secondary:hover {
  background-color: #f9fafb;
}

.btn-secondary.dark:hover {
  background-color: #1f2937;
}

.btn-sm {
  padding: 4px 12px;
  font-size: 14px;
}

.site-builder-content {
  display: flex;
  flex: 1;
  overflow: hidden;
}

.sidebar {
  width: 256px;
  background-color: #f9fafb;
  border-right: 1px solid #e5e7eb;
  padding: 16px;
}

.sidebar.dark {
  background-color: #1f2937;
  border-right-color: #374151;
}

.sidebar-section {
  margin-bottom: 24px;
}

.sidebar-section h3 {
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 12px;
  color: #111827;
}

.sidebar-section.dark h3 {
  color: #ffffff;
}

.page-list {
  margin-bottom: 12px;
}

.page-list > * + * {
  margin-top: 4px;
}

.page-list li {
  padding: 8px;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.page-list li:hover {
  background-color: #f3f4f6;
}

.page-list li.dark:hover {
  background-color: #374151;
}

.page-list li.active {
  background-color: #dbeafe;
  color: #2563eb;
}

.page-list li.active.dark {
  background-color: rgba(37, 99, 235, 0.2);
  color: #60a5fa;
}

.elements-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.element-btn {
  padding: 8px;
  font-size: 14px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  background: none;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.element-btn.dark {
  border-color: #4b5563;
}

.element-btn:hover {
  background-color: #f9fafb;
}

.element-btn.dark:hover {
  background-color: #1f2937;
}

.canvas {
  flex: 1;
  padding: 16px;
  overflow: auto;
}

.page-canvas h2 {
  font-size: 20px;
  font-weight: 600;
  margin-bottom: 16px;
  color: #111827;
}

.page-canvas.dark h2 {
  color: #ffffff;
}

.elements-list {
  margin-top: 16px;
}

.elements-list > * + * {
  margin-top: 8px;
}

.element-item {
  padding: 8px;
  background-color: #f3f4f6;
  border-radius: 4px;
}

.element-item.dark {
  background-color: #1f2937;
}

.no-page-selected {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 100%;
  color: #6b7280;
}

.no-page-selected.dark {
  color: #9ca3af;
}
</style> 