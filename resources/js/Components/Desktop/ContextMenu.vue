<template>
  <div 
    v-show="show"
    class="context-menu"
    :style="menuStyle"
    @click.stop
  >
    <div class="context-menu-items">
      <template v-for="(item, index) in items" :key="index">
        <!-- Separator -->
        <div v-if="item.type === 'separator'" class="context-menu-separator"></div>
        
        <!-- Regular item -->
        <div 
          v-else-if="!item.submenu"
          class="context-menu-item"
          :class="{ 
            'context-menu-item-disabled': item.disabled,
            'context-menu-item-checked': item.checked
          }"
          @click="handleItemClick(item)"
        >
          <i v-if="item.icon" :class="['context-menu-icon', item.icon]"></i>
          <span class="context-menu-label">{{ item.label }}</span>
          <i v-if="item.checked" class="context-menu-check fas fa-check"></i>
        </div>
        
        <!-- Submenu item -->
        <div 
          v-else
          class="context-menu-item context-menu-submenu"
          :class="{ 'context-menu-item-disabled': item.disabled }"
          @mouseenter="showSubmenu(index)"
          @mouseleave="hideSubmenu(index)"
        >
          <i v-if="item.icon" :class="['context-menu-icon', item.icon]"></i>
          <span class="context-menu-label">{{ item.label }}</span>
          <i class="context-menu-arrow fas fa-chevron-right"></i>
          
          <!-- Submenu -->
          <div 
            v-if="activeSubmenu === index"
            class="context-submenu"
            :style="submenuStyle"
          >
            <div class="context-menu-items">
              <template v-for="(subItem, subIndex) in item.submenu" :key="subIndex">
                <div v-if="subItem.type === 'separator'" class="context-menu-separator"></div>
                <div 
                  v-else
                  class="context-menu-item"
                  :class="{ 
                    'context-menu-item-disabled': subItem.disabled,
                    'context-menu-item-checked': subItem.checked
                  }"
                  @click="handleItemClick(subItem)"
                >
                  <i v-if="subItem.icon" :class="['context-menu-icon', subItem.icon]"></i>
                  <span class="context-menu-label">{{ subItem.label }}</span>
                  <i v-if="subItem.checked" class="context-menu-check fas fa-check"></i>
                </div>
              </template>
            </div>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick } from 'vue'

interface ContextMenuItem {
  label?: string
  icon?: string
  action?: string
  disabled?: boolean
  checked?: boolean
  type?: 'separator'
  submenu?: ContextMenuItem[]
}

interface Props {
  show?: boolean
  x: number
  y: number
  items: ContextMenuItem[]
}

const props = withDefaults(defineProps<Props>(), {
  show: false
})

const emit = defineEmits<{
  itemClick: [action: string]
  close: []
}>()

const activeSubmenu = ref<number | null>(null)
const menuRef = ref<HTMLElement>()

const menuStyle = computed(() => {
  let left = props.x
  let top = props.y
  
  // Adjust position to keep menu on screen
  if (typeof window !== 'undefined') {
    const menuWidth = 200 // Approximate menu width
    const menuHeight = props.items.length * 32 // Approximate item height
    
    if (left + menuWidth > window.innerWidth) {
      left = window.innerWidth - menuWidth - 10
    }
    
    if (top + menuHeight > window.innerHeight) {
      top = window.innerHeight - menuHeight - 10
    }
    
    if (left < 0) left = 10
    if (top < 0) top = 10
  }
  
  return {
    left: `${left}px`,
    top: `${top}px`,
    zIndex: 9999
  }
})

const submenuStyle = computed(() => {
  return {
    left: '100%',
    top: '0'
  }
})

const handleItemClick = (item: ContextMenuItem) => {
  if (item.disabled) return
  
  if (item.action) {
    emit('itemClick', item.action)
  }
  
  emit('close')
}

const showSubmenu = (index: number) => {
  activeSubmenu.value = index
}

const hideSubmenu = (index: number) => {
  if (activeSubmenu.value === index) {
    activeSubmenu.value = null
  }
}

// Close menu when clicking outside
watch(() => props.show, (newShow: boolean) => {
  if (newShow) {
    nextTick(() => {
      const handleClickOutside = (event: MouseEvent) => {
        if (menuRef.value && !menuRef.value.contains(event.target as Node)) {
          emit('close')
          document.removeEventListener('click', handleClickOutside)
        }
      }
      
      document.addEventListener('click', handleClickOutside)
    })
  }
})
</script>

<style scoped>
.context-menu {
  position: fixed;
  background: rgba(44, 44, 44, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
  padding: 4px 0;
  min-width: 180px;
  font-size: 14px;
  user-select: none;
  animation: contextMenuAppear 0.15s ease-out;
}

.context-menu-items {
  display: flex;
  flex-direction: column;
}

.context-menu-item {
  display: flex;
  align-items: center;
  padding: 8px 16px;
  color: #ffffff;
  cursor: pointer;
  position: relative;
  transition: background-color 0.1s ease;
}

.context-menu-item:hover:not(.context-menu-item-disabled) {
  background: rgba(255, 255, 255, 0.1);
}

.context-menu-item-disabled {
  color: rgba(255, 255, 255, 0.4);
  cursor: not-allowed;
}

.context-menu-item-checked {
  background: rgba(59, 130, 246, 0.2);
}

.context-menu-icon {
  width: 16px;
  margin-right: 12px;
  text-align: center;
  font-size: 12px;
}

.context-menu-label {
  flex: 1;
}

.context-menu-check {
  margin-left: 8px;
  font-size: 10px;
  color: #10b981;
}

.context-menu-arrow {
  margin-left: 8px;
  font-size: 10px;
  color: rgba(255, 255, 255, 0.6);
}

.context-menu-separator {
  height: 1px;
  background: rgba(255, 255, 255, 0.1);
  margin: 4px 0;
}

.context-menu-submenu {
  position: relative;
}

.context-submenu {
  position: absolute;
  background: rgba(44, 44, 44, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
  padding: 4px 0;
  min-width: 160px;
  margin-left: 8px;
  animation: submenuAppear 0.1s ease-out;
}

@keyframes contextMenuAppear {
  from {
    opacity: 0;
    transform: scale(0.95) translateY(-5px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

@keyframes submenuAppear {
  from {
    opacity: 0;
    transform: translateX(-10px);
  }
  to {
    opacity: 1;
    transform: translateX(0);
  }

}

/* Light theme support */
.light-theme .context-menu,
.light-theme .context-submenu {
  background: rgba(255, 255, 255, 0.95);
  border-color: rgba(0, 0, 0, 0.1);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.light-theme .context-menu-item {
  color: #1f2937;
}

.light-theme .context-menu-item:hover:not(.context-menu-item-disabled) {
  background: rgba(0, 0, 0, 0.05);
}

.light-theme .context-menu-item-disabled {
  color: rgba(31, 41, 55, 0.4);
}

.light-theme .context-menu-separator {
  background: rgba(0, 0, 0, 0.1);
}

.light-theme .context-menu-arrow {
  color: rgba(31, 41, 55, 0.6);
}
</style> 