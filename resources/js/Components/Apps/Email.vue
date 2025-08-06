<template>
  <div class="email-app">
    <div class="email-header">
      <h1 class="email-title">Email</h1>
      <button @click="composeEmail" class="compose-btn">
        <PlusIcon class="w-4 h-4" />
        Compose
      </button>
    </div>
    
    <div class="email-content">
      <div class="email-sidebar">
        <nav class="email-nav">
          <button 
            v-for="folder in folders" 
            :key="folder.id"
            @click="activeFolder = folder.id"
            :class="['nav-item', { active: activeFolder === folder.id }]"
          >
            <component :is="folder.icon" class="w-4 h-4" />
            {{ folder.name }}
            <span v-if="folder.count > 0" class="count-badge">{{ folder.count }}</span>
          </button>
        </nav>
      </div>
      
      <div class="email-main">
        <div class="email-list">
          <div 
            v-for="email in emails" 
            :key="email.id"
            @click="selectEmail(email)"
            :class="['email-item', { selected: selectedEmail?.id === email.id, unread: !email.read }]"
          >
            <div class="email-meta">
              <span class="sender">{{ email.sender }}</span>
              <span class="time">{{ formatTime(email.timestamp) }}</span>
            </div>
            <div class="subject">{{ email.subject }}</div>
            <div class="preview">{{ email.preview }}</div>
          </div>
        </div>
        
        <div v-if="selectedEmail" class="email-viewer">
          <div class="email-viewer-header">
            <h3>{{ selectedEmail.subject }}</h3>
            <div class="email-actions">
              <button @click="replyToEmail" class="action-btn">Reply</button>
              <button @click="deleteEmail" class="action-btn">Delete</button>
            </div>
          </div>
          <div class="email-viewer-meta">
            <span>From: {{ selectedEmail.sender }}</span>
            <span>{{ formatFullTime(selectedEmail.timestamp) }}</span>
          </div>
          <div class="email-viewer-body">
            {{ selectedEmail.body }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { 
  PlusIcon, 
  InboxIcon, 
  ArrowUpIcon, 
  ArchiveBoxIcon,
  TrashIcon
} from '@heroicons/vue/24/outline'

interface Props {
  windowId: string
  windowData?: Record<string, any>
}

interface Email {
  id: string
  sender: string
  subject: string
  preview: string
  body: string
  timestamp: Date
  read: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  updateTitle: [title: string]
  updateData: [data: Record<string, any>]
}>()

// State
const activeFolder = ref('inbox')
const selectedEmail = ref<Email | null>(null)
const emails = ref<Email[]>([
  {
    id: '1',
    sender: 'demo@example.com',
    subject: 'Welcome to Desktop OS',
    preview: 'Thank you for trying our desktop operating system...',
    body: 'Thank you for trying our desktop operating system. This is a demo email to show the email app functionality.',
    timestamp: new Date(),
    read: false
  }
])

const folders = [
  { id: 'inbox', name: 'Inbox', icon: InboxIcon, count: 1 },
  { id: 'sent', name: 'Sent', icon: ArrowUpIcon, count: 0 },
  { id: 'archive', name: 'Archive', icon: ArchiveBoxIcon, count: 0 },
  { id: 'trash', name: 'Trash', icon: TrashIcon, count: 0 }
]

// Methods
const selectEmail = (email: Email) => {
  selectedEmail.value = email
  if (!email.read) {
    email.read = true
  }
}

const composeEmail = () => {
  console.log('Compose email')
}

const replyToEmail = () => {
  console.log('Reply to email')
}

const deleteEmail = () => {
  console.log('Delete email')
}

const formatTime = (timestamp: Date) => {
  return timestamp.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const formatFullTime = (timestamp: Date) => {
  return timestamp.toLocaleString()
}

// Lifecycle
onMounted(() => {
  emit('updateTitle', 'Email')
})
</script>

<style scoped>
.email-app {
  width: 100%;
  height: 100%;
  background-color: #ffffff;
  display: flex;
  flex-direction: column;
}

.email-app.dark {
  background-color: #111827;
}

.email-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.email-header.dark {
  border-bottom-color: #374151;
}

.email-title {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
}

.email-title.dark {
  color: #ffffff;
}

.compose-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background-color: #2563eb;
  color: #ffffff;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.compose-btn:hover {
  background-color: #1d4ed8;
}

.email-content {
  display: flex;
  flex: 1;
  overflow: hidden;
}

.email-sidebar {
  width: 192px;
  background-color: #f9fafb;
  border-right: 1px solid #e5e7eb;
}

.email-sidebar.dark {
  background-color: #1f2937;
  border-right-color: #374151;
}

.email-nav {
  padding: 16px;
}

.email-nav > * + * {
  margin-top: 4px;
}

.nav-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 12px;
  text-align: left;
  border-radius: 6px;
  background: none;
  border: none;
  cursor: pointer;
  transition: background-color 0.2s ease;
  color: inherit;
  text-decoration: none;
}

.nav-item:hover {
  background-color: #f3f4f6;
}

.nav-item.dark:hover {
  background-color: #374151;
}

.nav-item.active {
  background-color: #dbeafe;
  color: #2563eb;
}

.nav-item.active.dark {
  background-color: rgba(37, 99, 235, 0.2);
  color: #60a5fa;
}

.count-badge {
  margin-left: auto;
  background-color: #2563eb;
  color: #ffffff;
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 9999px;
}

.email-main {
  display: flex;
  flex: 1;
}

.email-list {
  width: 320px;
  border-right: 1px solid #e5e7eb;
  overflow-y: auto;
}

.email-list.dark {
  border-right-color: #374151;
}

.email-item {
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.email-item.dark {
  border-bottom-color: #374151;
}

.email-item:hover {
  background-color: #f9fafb;
}

.email-item.dark:hover {
  background-color: #1f2937;
}

.email-item.selected {
  background-color: #eff6ff;
}

.email-item.selected.dark {
  background-color: rgba(37, 99, 235, 0.2);
}

.email-item.unread {
  font-weight: 600;
}

.email-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 4px;
}

.sender {
  font-size: 14px;
  color: #111827;
}

.sender.dark {
  color: #ffffff;
}

.time {
  font-size: 12px;
  color: #6b7280;
}

.time.dark {
  color: #9ca3af;
}

.subject {
  font-size: 14px;
  font-weight: 500;
  color: #111827;
  margin-bottom: 4px;
}

.subject.dark {
  color: #ffffff;
}

.preview {
  font-size: 14px;
  color: #4b5563;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.preview.dark {
  color: #d1d5db;
}

.email-viewer {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.email-viewer-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
}

.email-viewer-header.dark {
  border-bottom-color: #374151;
}

.email-viewer-header h3 {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
}

.email-viewer-header.dark h3 {
  color: #ffffff;
}

.email-actions {
  display: flex;
  gap: 8px;
}

.action-btn {
  padding: 4px 12px;
  font-size: 14px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: none;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.action-btn.dark {
  border-color: #4b5563;
}

.action-btn:hover {
  background-color: #f9fafb;
}

.action-btn.dark:hover {
  background-color: #1f2937;
}

.email-viewer-meta {
  padding: 16px;
  border-bottom: 1px solid #e5e7eb;
  font-size: 14px;
  color: #4b5563;
}

.email-viewer-meta.dark {
  border-bottom-color: #374151;
  color: #d1d5db;
}

.email-viewer-body {
  padding: 16px;
  flex: 1;
  overflow-y: auto;
}
</style> 