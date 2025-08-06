<!-- AppStore Component - Exact Match to Static Implementation -->
<template>
  <div class="app-store-app">
    <div class="app-store-header">
      <h1>App Store</h1>
      <p>Discover and install apps for your desktop</p>
    </div>
    
    <div class="app-store-content">
      <div class="featured-apps">
        <h2>Featured Apps</h2>
        <div class="apps-grid">
          <!-- Apps will be loaded dynamically -->
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAppMetadata } from '@/composables/useAppMetadata'

interface Props {
  windowId: string
  windowData?: Record<string, any>
}

const props = defineProps<Props>()

const emit = defineEmits<{
  updateTitle: [title: string]
  updateData: [data: Record<string, any>]
}>()

// Set app metadata
const { updateMetadata } = useAppMetadata()
updateMetadata({
  title: 'App Store',
  icon: 'fas fa-store',
  iconClass: 'blue-app-icon'
})

// State
const loading = ref(false)
const apps = ref([])

onMounted(() => {
  // Load apps
})
</script>

<style scoped>
.app-store-app {
  padding: 20px;
  height: 100%;
  overflow-y: auto;
}

.app-store-header {
  margin-bottom: 30px;
}

.app-store-header h1 {
  font-size: 28px;
  margin-bottom: 10px;
}

.app-store-header p {
  color: #aaa;
  font-size: 16px;
}

.featured-apps h2 {
  font-size: 20px;
  margin-bottom: 20px;
}

.apps-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 20px;
}
</style> 