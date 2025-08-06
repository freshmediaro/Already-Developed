<template>
  <div 
    class="desktop-background"
    :style="backgroundStyle"
    @contextmenu="$emit('contextmenu', $event)"
  >
    <!-- Wallpaper overlay for gradients or patterns -->
    <div v-if="hasOverlay" class="desktop-overlay" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  wallpaper?: string
  overlay?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  wallpaper: '',
  overlay: false
})

defineEmits<{
  contextmenu: [event: MouseEvent]
}>()

const backgroundStyle = computed(() => {
  if (!props.wallpaper) {
    return {}
  }

  // Check if it's a URL or a color/gradient
  if (props.wallpaper.startsWith('http') || props.wallpaper.startsWith('/')) {
    return {
      backgroundImage: `url(${props.wallpaper})`,
      backgroundSize: 'cover',
      backgroundPosition: 'center',
      backgroundRepeat: 'no-repeat'
    }
  } else {
    // Assume it's a CSS color or gradient
    return {
      background: props.wallpaper
    }
  }
})

const hasOverlay = computed(() => {
  return props.overlay && props.wallpaper
})
</script>

<style scoped>
.desktop-background {
  position: relative;
  width: 100%;
  height: 100%;
}

.desktop-overlay {
  position: absolute; /* absolute */
  top: 0; /* inset-0 */
  left: 0; /* inset-0 */
  right: 0; /* inset-0 */
  bottom: 0; /* inset-0 */
  background: linear-gradient(to bottom right, rgba(0, 0, 0, 0.2), transparent); /* bg-gradient-to-br from-black/20 to-transparent */
}
</style> 