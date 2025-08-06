<template>
  <div 
    v-show="show"
    class="ai-chat-window"
    :class="{ 'ai-chat-visible': show }"
  >
    <div class="ai-chat-header">
      <button class="ai-menu-toggle" aria-label="Menu">
        <i class="fas fa-bars"></i>
      </button>
      <div class="ai-chat-title">
        <div class="ai-chat-content-ai-answer-avatar">
          <img src="/img/alien.png" alt="AI" class="user-avatar">
        </div>
        <span>Alien Intelligence</span>
      </div>
      <button 
        class="panel-close-btn" 
        aria-label="Close chat"
        @click="$emit('close')"
      >
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <div class="ai-chat-window-inner">
      <div class="ai-chat-content" ref="chatContent">
        <!-- Chat messages -->
        <template v-for="message in messages" :key="message.id">
          <!-- AI Response -->
          <div v-if="message.type === 'ai'" class="ai-chat-content-ai-answer">
            <div class="ai-chat-content-ai-answer-avatar">
              <img src="/img/alien.png" alt="AI" class="user-avatar">
            </div>
            <div class="ai-chat-content-answer">
              <span v-html="formatMessage(message.content)"></span>
              <div class="ai-chat-content-ai-answer-actions">
                <button class="ai-chat-action-btn" @click="copyMessage(message.content)" title="Copy">
                  <i class="fas fa-copy"></i>
                </button>
                <button class="ai-chat-action-btn" @click="likeMessage(message.id)" title="Like">
                  <i class="fas fa-thumbs-up"></i>
                </button>
                <button class="ai-chat-action-btn" @click="dislikeMessage(message.id)" title="Dislike">
                  <i class="fas fa-thumbs-down"></i>
                </button>
                <button class="ai-chat-action-btn" @click="speakMessage(message.content)" title="Read aloud">
                  <i class="fas fa-volume-high"></i>
                </button>
                <button class="ai-chat-action-btn" @click="reportMessage(message.id)" title="Report">
                  <i class="fas fa-flag"></i>
                </button>
              </div>
            </div>
          </div>
          
          <!-- User Question -->
          <div v-else class="ai-chat-content-user-question">
            <div class="ai-chat-content-question">
              <span>{{ message.content }}</span>
            </div>
          </div>
        </template>
        
        <!-- Typing indicator -->
        <div v-if="isTyping" class="ai-chat-content-ai-answer">
          <div class="ai-chat-content-ai-answer-avatar">
            <img src="/img/alien.png" alt="AI" class="user-avatar">
          </div>
          <div class="ai-chat-content-answer">
            <span class="typing-indicator">
              <span class="typing-dot"></span>
              <span class="typing-dot"></span>
              <span class="typing-dot"></span>
            </span>
          </div>
        </div>
      </div>
      
      <div class="ai-chat-box">
        <div class="ai-chat-box-content">
          <textarea 
            v-model="currentMessage"
            class="ai-chat-input" 
            placeholder="Ask me anything, or give me commands like 'write a blog post about AI' or 'update product price for iPhone to $999'..."
            @keydown.enter.exact.prevent="sendMessage"
            @keydown.enter.shift.exact="() => {}"
            ref="messageInput"
          ></textarea>
        </div>
        
        <div class="ai-chat-content-actions">
          <div class="ai-chat-content-actions-left">
            <button class="ai-chat-action-btn" @click="startVoiceInput" title="Voice input">
              <i class="fas fa-microphone"></i>
            </button>
            <button class="ai-chat-action-btn" @click="openCamera" title="Camera">
              <i class="fas fa-camera"></i>
            </button>
            <button class="ai-chat-action-btn" @click="uploadImage" title="Upload image">
              <i class="fas fa-image"></i>
            </button>
            <button class="ai-chat-action-btn" @click="uploadFile" title="Upload file">
              <i class="fas fa-file-alt"></i>
            </button>
            <button class="ai-chat-action-btn" @click="addLink" title="Add link">
              <i class="fas fa-link"></i>
            </button>
          </div>
          <div class="ai-chat-content-actions-right">
            <button 
              class="ai-chat-send-btn" 
              @click="sendMessage"
              :disabled="!currentMessage.trim() || isTyping"
              title="Send message"
            >
              <i class="fas fa-paper-plane"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, nextTick, watch, onMounted } from 'vue'
import { globalStateManager } from '@/Core/GlobalStateManager'

interface ChatMessage {
  id: string
  type: 'user' | 'ai'
  content: string
  timestamp: Date
  metadata?: {
    command?: string
    app?: string
    action?: string
    parameters?: Record<string, unknown>
  }
}

interface Props {
  show: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
}>()

// State
const messages = ref<ChatMessage[]>([
  {
    id: '1',
    type: 'ai',
    content: 'Hello! I\'m your Alien Intelligence assistant. I can help you with anything - from answering questions to executing commands like writing blog posts, managing products, analyzing reports, or controlling your desktop applications. What would you like me to help you with?',
    timestamp: new Date()
  }
])

const currentMessage = ref('')
const isTyping = ref(false)
const chatContent = ref<HTMLElement>()
const messageInput = ref<HTMLTextAreaElement>()

// Methods
const sendMessage = async () => {
  if (!currentMessage.value?.trim() || isTyping.value) return

  const userMessage: ChatMessage = {
    id: Date.now().toString(),
    type: 'user',
    content: currentMessage.value.trim(),
    timestamp: new Date()
  }
  
  messages.value?.push(userMessage)
  const messageContent = currentMessage.value.trim()
  currentMessage.value = ''
  
  isTyping.value = true
  
  try {
    // Send to AI backend
    const response = await sendToAI(messageContent)
    
    const aiMessage: ChatMessage = {
      id: (Date.now() + 1).toString(),
      type: 'ai',
      content: response.content || 'I apologize, but I encountered an error processing your request.',
      timestamp: new Date(),
      metadata: response.metadata
    }
    
    messages.value?.push(aiMessage)
    
    // Execute command if needed
    if (response.metadata?.command) {
      await executeCommand(response.metadata)
    }
    
  } catch (error) {
    console.error('Failed to send message:', error)
    const errorMessage: ChatMessage = {
      id: (Date.now() + 1).toString(),
      type: 'ai',
      content: 'I apologize, but I\'m having trouble connecting right now. Please try again in a moment.',
      timestamp: new Date()
    }
    messages.value?.push(errorMessage)
  } finally {
    isTyping.value = false
    scrollToBottom()
  }
}

const sendToAI = async (message: string): Promise<{ content: string; metadata?: any }> => {
  // Get current context
  const context = await gatherContext()
  
  const response = await fetch('/api/ai-chat/message', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    },
    body: JSON.stringify({
      message,
      context,
      history: (messages.value || []).slice(-10) // Last 10 messages for context
    })
  })
  
  if (!response.ok) {
    throw new Error('Failed to get AI response')
  }
  
  return await response.json()
}

const gatherContext = async () => {
  // Gather current desktop context
  // TODO: These should access appropriate services once they're implemented
  // const user = globalStateManager.getState().user
  // const currentTeam = globalStateManager.getState().currentTeam
  // const openWindows = globalStateManager.getState().openWindows || {}
  // const installedApps = globalStateManager.getState().installedApps || []
  
  return {
    user: {
      // id: user?.id,
      // name: user?.name,
      // email: user?.email
      id: 1,
      name: 'Current User',
      email: 'user@example.com'
    },
    team: {
      id: 1,
      name: 'Current Team'
    },
    openWindows: ['Window 1', 'Window 2'],
    installedApps: [
      { id: 'app1', name: 'App 1', category: 'Utility' },
      { id: 'app2', name: 'App 2', category: 'Productivity' }
    ],
    timestamp: new Date().toISOString()
  }
}

const executeCommand = async (metadata: any) => {
  if (!metadata.command) return
  
  try {
    const response = await fetch('/api/ai-chat/execute', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(metadata)
    })
    
    if (response.ok) {
      const result = await response.json()
      
      // Handle different command results
      if (result.action === 'launch_app') {
        // Launch the app through the desktop system
        if (window.vueDesktop?.launchApp) {
          window.vueDesktop.launchApp(result.app_id)
        }
      } else if (result.action === 'show_notification') {
        // Show a notification
        if (window.vueDesktop?.showNotifications) {
          window.vueDesktop.showNotifications()
        }
      }
      // Add more command handling as needed
    }
  } catch (error) {
    console.error('Command execution error:', error)
  }
}

const copyMessage = async (content: string) => {
  try {
    await navigator.clipboard.writeText(content)
    // Show brief success feedback
  } catch (error) {
    console.error('Copy failed:', error)
  }
}

const likeMessage = (messageId: string) => {
  // Send feedback to backend
  sendFeedback(messageId, 'like')
}

const dislikeMessage = (messageId: string) => {
  // Send feedback to backend
  sendFeedback(messageId, 'dislike')
}

const sendFeedback = async (messageId: string, type: 'like' | 'dislike') => {
  try {
    await fetch('/api/ai-chat/feedback', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({ messageId, type })
    })
  } catch (error) {
    console.error('Feedback error:', error)
  }
}

const speakMessage = (content: string) => {
  // Use Web Speech API to read message aloud
  if ('speechSynthesis' in window) {
    const utterance = new SpeechSynthesisUtterance(content)
    utterance.rate = 0.9
    utterance.pitch = 1
    speechSynthesis.speak(utterance)
  }
}

const reportMessage = (messageId: string) => {
  // Send report to backend
  sendFeedback(messageId, 'report' as any)
}

const formatMessage = (content: string): string => {
  // Basic markdown-like formatting
  return content
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
    .replace(/`(.*?)`/g, '<code>$1</code>')
    .replace(/\n/g, '<br>')
}

const scrollToBottom = () => {
  if (chatContent.value) {
    chatContent.value.scrollTop = chatContent.value.scrollHeight
  }
}

const generateId = (): string => {
  return Date.now().toString(36) + Math.random().toString(36).substr(2)
}

// File upload handlers
const uploadImage = () => {
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*'
  input.onchange = (e: any) => {
    const file = e.target.files[0]
    if (file) {
      // Handle image upload
      handleFileUpload(file, 'image')
    }
  }
  input.click()
}

const uploadFile = () => {
  const input = document.createElement('input')
  input.type = 'file'
  input.onchange = (e: any) => {
    const file = e.target.files[0]
    if (file) {
      // Handle file upload
      handleFileUpload(file, 'file')
    }
  }
  input.click()
}

const handleFileUpload = async (file: File, type: string) => {
  // Upload file and add to chat
  const formData = new FormData()
  formData.append('file', file)
  formData.append('type', type)
  
  try {
    const response = await fetch('/api/ai-chat/upload', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: formData
    })
    
    if (response.ok) {
      const result = await response.json()
      currentMessage.value += `\n[${type}: ${file.name}]`
    }
  } catch (error) {
    console.error('Upload error:', error)
  }
}

const startVoiceInput = () => {
  // Implement voice input using Web Speech API
  if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
    const SpeechRecognition = (window as any).webkitSpeechRecognition || (window as any).SpeechRecognition
    const recognition = new SpeechRecognition()
    
    recognition.continuous = false
    recognition.interimResults = false
    recognition.lang = 'en-US'
    
    recognition.onresult = (event: any) => {
      const transcript = event.results[0][0].transcript
      currentMessage.value += transcript
    }
    
    recognition.onerror = (event: any) => {
      console.error('Speech recognition error:', event.error)
    }
    
    recognition.start()
  }
}

const openCamera = () => {
  // Implement camera functionality
  console.log('Camera functionality would be implemented here')
}

const addLink = () => {
  const url = prompt('Enter URL:')
  if (url) {
    currentMessage.value += `\n${url}`
  }
}

// Watch for show changes to focus input
watch(() => props.show, (newShow: boolean) => {
  if (newShow) {
    nextTick(() => {
      messageInput.value?.focus()
      scrollToBottom()
    })
  }
})

onMounted(() => {
  scrollToBottom()
})
</script>

<style scoped>
/* AI Chat Window - Exact match to static files */
.ai-chat-window {
  position: fixed;
  right: 10px;
  top: auto;
  bottom: 60px;
  height: calc(100vh - 70px);
  width: 400px;
  z-index: 4000;
  background: #050217c5;
  box-shadow: -4px 0 24px rgba(0, 0, 0, 0.25);
  border-radius: 20px;
  transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  transform: translateX(100%);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  backdrop-filter: blur(30px);
  border: 1px solid var(--border-color);
}

.ai-chat-window.ai-chat-visible {
  transform: translateX(0);
}

.ai-chat-header {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  justify-content: space-between;
  align-items: center;
  align-content: center;
  padding: 15px 20px 15px 20px;
  background: linear-gradient(180deg, #ffffff0a, #05021700);
}

.ai-chat-title {
  display: flex;
  align-items: center;
  gap: 15px;
}

.ai-chat-title span {
  font-size: 14px;
  font-weight: 600;
  color: #fff;
}

.ai-menu-toggle,
.panel-close-btn {
  background: none;
  border: none;
  color: #fff;
  font-size: 16px;
  cursor: pointer;
  z-index: 10;
  opacity: 0.7;
  transition: opacity 0.2s;
  padding: 8px;
}

.ai-menu-toggle:hover,
.panel-close-btn:hover {
  opacity: 1;
}

.ai-chat-window-inner {
  flex: 1;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  height: calc(100vh - 50px);
  margin-top: 0;
}

.ai-chat-content {
  display: flex;
  flex-direction: column;
  padding: 20px;
  line-height: 1.5;
  flex: 1;
  overflow-y: auto;
}

.ai-chat-content-ai-answer {
  display: flex;
  gap: 15px;
  align-content: flex-start;
  align-items: flex-start;
  margin-bottom: 20px;
}

.ai-chat-content-ai-answer-avatar {
  min-width: 18px;
  max-width: 18px;
  width: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.user-avatar {
  width: 18px;
  height: 18px;
  border-radius: 50%;
  object-fit: cover;
}

.ai-chat-content-answer {
  flex: 1;
}

.ai-chat-content-answer span {
  color: #fff;
  font-size: 14px;
  line-height: 1.5;
}

.ai-chat-content-ai-answer-actions {
  visibility: hidden;
  height: 30px;
  display: flex;
  flex-direction: row;
  align-items: flex-start;
  justify-content: flex-start;
  gap: 3px;
  border-radius: 15px;
  margin-top: 5px;
  margin-bottom: 15px;
}

.ai-chat-content-ai-answer:hover .ai-chat-content-ai-answer-actions {
  visibility: visible;
  display: flex;
  flex-direction: row;
}

.ai-chat-content-user-question {
  display: flex;
  justify-content: flex-end;
  border-radius: 15px;
  background-color: #ffffff0a;
  width: fit-content;
  max-width: 80%;
  margin-left: auto;
  padding: 12px 20px;
  margin-bottom: 40px;
}

.ai-chat-content-question span {
  color: #fff;
  font-size: 14px;
}

.ai-chat-box {
  width: 100%;
  background-color: #ffffff0a;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding-top: 10px;
}

.ai-chat-box-content textarea {
  color: #fff;
  background: transparent;
  border: none;
  width: 100%;
  height: 60px;
  resize: none;
  font-size: 14px;
  line-height: 1.5;
  padding: 0 15px;
  font-family: inherit;
}

.ai-chat-box-content textarea:focus {
  outline: none;
}

.ai-chat-box-content textarea::placeholder {
  color: rgba(255, 255, 255, 0.5);
}

.ai-chat-content-actions {
  display: flex;
  justify-content: space-between;
  flex-direction: row;
  align-items: center;
  padding: 0 10px;
  margin-bottom: 10px;
}

.ai-chat-content-actions-left,
.ai-chat-content-actions-right {
  display: flex;
  gap: 2px;
  justify-content: flex-start;
  align-items: center;
}

.ai-chat-action-btn {
  color: var(--os-gray);
  background: transparent;
  border: none;
  cursor: pointer;
  font-size: 14px;
  padding: 10px;
  border-radius: 5px;
  transition: all 0.2s ease;
  display: flex;
  justify-content: center;
  width: 30px;
  height: 30px;
  align-items: center;
}

.ai-chat-action-btn:hover:not(:disabled) {
  color: #fff;
  background: #ffffff0a;
}

.ai-chat-action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.ai-chat-send-btn {
  font-size: 18px !important;
}

/* Typing indicator */
.typing-indicator {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.typing-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: #fff;
  opacity: 0.4;
  animation: typing-pulse 1.4s infinite ease-in-out;
}

.typing-dot:nth-child(1) { animation-delay: 0s; }
.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing-pulse {
  0%, 60%, 100% {
    opacity: 0.4;
    transform: scale(1);
  }
  30% {
    opacity: 1;
    transform: scale(1.2);
  }
}

/* Mobile responsive */
@media (max-width: 1023px) {
  .ai-chat-window {
    right: 0;
    bottom: 0;
    width: 100vw;
    height: 100vh;
    border-radius: 0;
  }
}
</style> 