<template>
  <div v-if="showWarning" class="orientation-warning">
    <i class="fas fa-mobile-alt"></i>
    <h3>Please rotate your device</h3>
    <p>This application works best in portrait mode</p>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

const showWarning = ref(false)

const checkOrientation = () => {
  const isMobile = window.innerWidth <= 767
  const isLandscape = window.orientation === 90 || window.orientation === -90 || 
                     (window.innerWidth > window.innerHeight && isMobile)
  
  showWarning.value = isMobile && isLandscape
}

onMounted(() => {
  checkOrientation()
  window.addEventListener('orientationchange', checkOrientation)
  window.addEventListener('resize', checkOrientation)
})

onUnmounted(() => {
  window.removeEventListener('orientationchange', checkOrientation)
  window.removeEventListener('resize', checkOrientation)
})
</script>

<style scoped>
.orientation-warning {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.9);
  color: white;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  text-align: center;
  padding: 20px;
}

.orientation-warning i {
  font-size: 48px;
  margin-bottom: 16px;
}

.orientation-warning h3 {
  margin-bottom: 8px;
  font-size: 20px;
  font-weight: 600;
}

.orientation-warning p {
  opacity: 0.8;
  font-size: 16px;
  margin: 0;
}
</style> 