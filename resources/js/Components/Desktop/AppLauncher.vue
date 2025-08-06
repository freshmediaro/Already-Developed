<template>
  <div id="app-launcher-desktop" class="app-launcher-container">
    <!-- Top Bar -->
    <div class="app-launcher-top-bar">
      <h1>Applications</h1>
      <div class="user-info">
        <div class="user-avatar">{{ userInitial }}</div>
        <span>{{ userName }}</span>
      </div>
    </div>
    
    <!-- App Grid -->
    <div class="app-launcher-grid">
      <div 
        v-for="app in apps" 
        :key="app.id"
        class="app-launcher-app"
        :data-app="app.id"
        @click="$emit('appLaunch', app.id)"
        @keydown.enter="$emit('appLaunch', app.id)"
        tabindex="0"
      >
        <div class="icon-container" :class="app.iconBgClass">
          <i class="fas" :class="app.iconClass"></i>
        </div>
        <span>{{ app.name }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface App {
  id: string
  name: string
  iconClass: string
  iconBgClass: string
}

interface Props {
  apps?: App[]
  userName?: string
}

const props = withDefaults(defineProps<Props>(), {
  apps: () => [
    { id: 'calculator', name: 'Calculator', iconClass: 'fa-calculator', iconBgClass: 'blue-icon' },
    { id: 'file-explorer', name: 'Files', iconClass: 'fa-folder', iconBgClass: 'yellow-icon' },
    { id: 'settings', name: 'Settings', iconClass: 'fa-cog', iconBgClass: 'gray-icon' },
    { id: 'browser', name: 'Browser', iconClass: 'fa-globe', iconBgClass: 'green-icon' },
    { id: 'email', name: 'Email', iconClass: 'fa-envelope', iconBgClass: 'red-icon' },
    { id: 'photos', name: 'Photos', iconClass: 'fa-image', iconBgClass: 'purple-icon' }
  ],
  userName: 'User'
})

defineEmits<{
  appLaunch: [appId: string]
}>()

const userInitial = computed(() => 
  props.userName.charAt(0).toUpperCase()
)
</script>

<style scoped>
.app-launcher-container {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
}

.app-launcher-top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  background: rgba(0, 0, 0, 0.1);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.app-launcher-top-bar h1 {
  color: white;
  margin: 0;
  font-size: 24px;
  font-weight: 600;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(59, 130, 246, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 14px;
}

.user-info span {
  color: white;
  font-size: 16px;
}

.app-launcher-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
  gap: 32px;
  width: min(90vw, 900px);
  max-width: 100vw;
  margin: auto;
  justify-items: center;
  align-items: start;
  padding: 32px 0 90px 0;
  flex: 1 1 0;
  align-content: center;
  justify-content: center;
}

.app-launcher-app {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 120px;
  cursor: pointer;
  user-select: none;
  transition: transform 0.2s ease;
}

.app-launcher-app:hover,
.app-launcher-app:focus {
  transform: scale(1.05);
  outline: none;
}

.icon-container {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 28px;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.18);
  margin-bottom: 10px;
}

.app-launcher-app span {
  font-size: 14px;
  color: #fff;
  margin-top: 5px;
  text-shadow: 0 1px 4px #222;
  text-align: center;
  width: 100%;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: block;
}

/* Icon background colors */
.blue-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.yellow-icon { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.gray-icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.green-icon { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.red-icon { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
.purple-icon { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }

/* Responsive design */
@media (max-width: 768px) {
  .app-launcher-grid {
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 24px;
    padding: 24px 16px 60px 16px;
  }
  
  .icon-container {
    width: 56px;
    height: 56px;
    font-size: 24px;
  }
  
  .app-launcher-app span {
    font-size: 12px;
  }
}
</style> 