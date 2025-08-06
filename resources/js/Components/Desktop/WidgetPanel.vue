<template>
  <div 
    id="widgets-screen"
    :class="{ 'widgets-hidden': !show }"
  >
    <!-- Widgets Content -->
    <div class="widgets-container">
      <!-- Default widgets that match static files -->
      <div class="widget">
        <div class="widget-header">
          <h4 class="widget-title">Weather</h4>
        </div>
        <div class="widget-content weather-widget">
          <div class="weather-main">
            <span class="weather-temp">22°</span>
            <div class="weather-info">
              <span class="weather-condition">Partly Cloudy</span>
              <span class="weather-location">New York</span>
            </div>
          </div>
          <div class="weather-details">
            <div class="weather-detail">
              <span class="weather-detail-label">Feels like</span>
              <span class="weather-detail-value">25°</span>
            </div>
            <div class="weather-detail">
              <span class="weather-detail-label">Humidity</span>
              <span class="weather-detail-value">65%</span>
            </div>
            <div class="weather-detail">
              <span class="weather-detail-label">Wind</span>
              <span class="weather-detail-value">12 km/h</span>
            </div>
          </div>
        </div>
      </div>

      <div class="widget">
        <div class="widget-header">
          <h4 class="widget-title">Calendar</h4>
        </div>
        <div class="widget-content calendar-widget">
          <div class="calendar-header">
            <span class="calendar-month">{{ currentMonth }}</span>
            <span class="calendar-year">{{ currentYear }}</span>
          </div>
          <div class="calendar-grid">
            <div class="calendar-day-header" v-for="day in ['S', 'M', 'T', 'W', 'T', 'F', 'S']" :key="day">
              {{ day }}
            </div>
            <div 
              v-for="date in calendarDates" 
              :key="date"
              class="calendar-day"
              :class="{ 'calendar-today': date === today }"
            >
              {{ date }}
            </div>
          </div>
        </div>
      </div>

      <div class="widget">
        <div class="widget-header">
          <h4 class="widget-title">Quick Notes</h4>
        </div>
        <div class="widget-content notes-widget">
          <div class="notes-list">
            <div class="note-item">
              <div class="note-text">Team meeting at 3 PM</div>
              <div class="note-time">2 hours ago</div>
            </div>
            <div class="note-item">
              <div class="note-text">Review quarterly reports</div>
              <div class="note-time">1 day ago</div>
            </div>
            <div class="note-item">
              <div class="note-text">Call client about proposal</div>
              <div class="note-time">2 days ago</div>
            </div>
          </div>
          <button class="note-add-btn">
            <i class="fas fa-plus"></i>
            Add Note
          </button>
        </div>
      </div>

      <div class="widget">
        <div class="widget-header">
          <h4 class="widget-title">System Status</h4>
        </div>
        <div class="widget-content system-widget">
          <div class="system-item">
            <span class="system-label">CPU Usage</span>
            <div class="system-bar">
              <div class="system-bar-fill" style="width: 45%"></div>
            </div>
            <span class="system-value">45%</span>
          </div>
          <div class="system-item">
            <span class="system-label">Memory</span>
            <div class="system-bar">
              <div class="system-bar-fill" style="width: 62%"></div>
            </div>
            <span class="system-value">62%</span>
          </div>
          <div class="system-item">
            <span class="system-label">Storage</span>
            <div class="system-bar">
              <div class="system-bar-fill" style="width: 78%"></div>
            </div>
            <span class="system-value">78%</span>
          </div>
        </div>
      </div>

      <div class="widget">
        <div class="widget-header">
          <h4 class="widget-title">Recent Files</h4>
        </div>
        <div class="widget-content files-widget">
          <div class="file-list">
            <div class="file-item">
              <i class="fas fa-file-pdf file-icon pdf"></i>
              <div class="file-info">
                <div class="file-name">Project_Report.pdf</div>
                <div class="file-date">Modified 2 hours ago</div>
              </div>
            </div>
            <div class="file-item">
              <i class="fas fa-file-excel file-icon excel"></i>
              <div class="file-info">
                <div class="file-name">Budget_2024.xlsx</div>
                <div class="file-date">Modified yesterday</div>
              </div>
            </div>
            <div class="file-item">
              <i class="fas fa-file-image file-icon image"></i>
              <div class="file-info">
                <div class="file-name">Logo_Design.png</div>
                <div class="file-date">Modified 3 days ago</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="widget">
        <div class="widget-header">
          <h4 class="widget-title">Tasks</h4>
        </div>
        <div class="widget-content tasks-widget">
          <div class="task-list">
            <div class="task-item">
              <input type="checkbox" class="task-checkbox" checked>
              <span class="task-text completed">Review project proposal</span>
            </div>
            <div class="task-item">
              <input type="checkbox" class="task-checkbox">
              <span class="task-text">Prepare presentation slides</span>
            </div>
            <div class="task-item">
              <input type="checkbox" class="task-checkbox">
              <span class="task-text">Update website content</span>
            </div>
            <div class="task-item">
              <input type="checkbox" class="task-checkbox">
              <span class="task-text">Schedule team meeting</span>
            </div>
          </div>
          <button class="task-add-btn">
            <i class="fas fa-plus"></i>
            Add Task
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

interface Props {
  show: boolean
  widgets?: any[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  close: []
}>()

// Calendar data
const currentMonth = ref(new Date().toLocaleDateString('en-US', { month: 'long' }))
const currentYear = ref(new Date().getFullYear())
const today = ref(new Date().getDate())

const calendarDates = computed(() => {
  const date = new Date()
  const year = date.getFullYear()
  const month = date.getMonth()
  
  const firstDay = new Date(year, month, 1).getDay()
  const daysInMonth = new Date(year, month + 1, 0).getDate()
  
  const dates = []
  
  // Add empty cells for days before month starts
  for (let i = 0; i < firstDay; i++) {
    dates.push('')
  }
  
  // Add days of the month
  for (let day = 1; day <= daysInMonth; day++) {
    dates.push(day)
  }
  
  return dates
})

// Widget scroll handling - exactly like static files
let scrollEventListener: ((e: WheelEvent) => void) | null = null

const enableWidgetsScreenScroll = () => {
  const widgetsScreen = document.getElementById('widgets-screen')
  if (!widgetsScreen) return
  
  // Remove existing listener
  if (scrollEventListener) {
    widgetsScreen.removeEventListener('wheel', scrollEventListener)
  }
  
  if (window.innerWidth >= 1024) {
    scrollEventListener = (e: WheelEvent) => {
      widgetsScreen.scrollTop += e.deltaY
      // Do not preventDefault, so scroll bubbles if at top/bottom
    }
    widgetsScreen.addEventListener('wheel', scrollEventListener, { passive: true })
  }
}

const initWidgetsInteraction = () => {
  const widgetsScreen = document.getElementById('widgets-screen')
  if (!widgetsScreen) return

  if (window.innerWidth <= 1023) {
    // Mobile/tablet: always allow scroll and pointer events for touch
    widgetsScreen.style.overflowY = 'auto'
    widgetsScreen.style.pointerEvents = 'auto'
  } else {
    // Desktop: always allow scroll, but do NOT set pointer-events (let CSS z-index handle stacking)
    widgetsScreen.style.overflowY = 'auto'
    // Do NOT set pointerEvents to 'auto' - this is key for drag selector to work
  }
}

const setupMainContentAreaScroll = () => {
  const mainContentArea = document.querySelector('.main-content-area')
  const widgetsScreen = document.getElementById('widgets-screen')
  
  if (mainContentArea && widgetsScreen) {
    mainContentArea.addEventListener('wheel', (e: Event) => {
      const wheelEvent = e as WheelEvent;
      if (window.innerWidth >= 1024) {
        const rect = widgetsScreen.getBoundingClientRect()
        if (
          wheelEvent.clientX >= rect.left &&
          wheelEvent.clientX <= rect.right &&
          wheelEvent.clientY >= rect.top &&
          wheelEvent.clientY <= rect.bottom
        ) {
          widgetsScreen.scrollTop += wheelEvent.deltaY
        }
      }
    }, { passive: true })
  }
}

onMounted(() => {
  enableWidgetsScreenScroll()
  initWidgetsInteraction()
  setupMainContentAreaScroll()
  
  window.addEventListener('resize', () => {
    enableWidgetsScreenScroll()
    initWidgetsInteraction()
  })
})

onUnmounted(() => {
  const widgetsScreen = document.getElementById('widgets-screen')
  if (widgetsScreen && scrollEventListener) {
    widgetsScreen.removeEventListener('wheel', scrollEventListener)
  }
})
</script>

<style scoped>
/* Widget Panel follows the exact structure from static files */
#widgets-screen {
  /* Base styles from static files */
  position: fixed;
  right: 0;
  top: 0;
  width: 350px;
  height: 100vh;
  background: rgba(20, 20, 30, 0.95);
  border-left: 1px solid rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  z-index: 1; /* Important: low z-index so it doesn't interfere with drag selector */
  overflow-y: auto;
  overflow-x: hidden;
  scrollbar-width: none; /* Firefox */
  transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1);
  will-change: transform;
  transform: translateX(0);
  /* Key: No pointer-events set here - let individual widgets handle it */
  user-select: auto;
}

/* Hide scrollbar for webkit browsers */
#widgets-screen::-webkit-scrollbar {
  display: none !important;
}

/* Hidden state */
#widgets-screen.widgets-hidden {
  transform: translateX(100%);
}

/* Widgets container */
.widgets-container {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Individual widget styling */
.widget {
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  overflow: hidden;
  transition: all 0.3s ease;
  /* Individual widgets can have pointer events */
  pointer-events: auto;
}

.widget:hover {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.widget-header {
  padding: 16px 20px 12px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.widget-title {
  color: #ffffff;
  font-size: 14px;
  font-weight: 600;
  margin: 0;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.widget-content {
  padding: 16px 20px 20px;
}

/* Weather Widget */
.weather-widget .weather-main {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 16px;
}

.weather-temp {
  font-size: 36px;
  font-weight: 300;
  color: #ffffff;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.weather-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.weather-condition {
  color: #ffffff;
  font-size: 14px;
  font-weight: 500;
}

.weather-location {
  color: rgba(255, 255, 255, 0.7);
  font-size: 12px;
}

.weather-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.weather-detail {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.weather-detail-label {
  color: rgba(255, 255, 255, 0.7);
  font-size: 12px;
}

.weather-detail-value {
  color: #ffffff;
  font-size: 12px;
  font-weight: 500;
}

/* Calendar Widget */
.calendar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.calendar-month, .calendar-year {
  color: #ffffff;
  font-size: 14px;
  font-weight: 600;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
}

.calendar-day-header {
  color: rgba(255, 255, 255, 0.7);
  font-size: 11px;
  font-weight: 600;
  text-align: center;
  padding: 6px;
}

.calendar-day {
  color: rgba(255, 255, 255, 0.8);
  font-size: 12px;
  text-align: center;
  padding: 6px;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.2s;
}

.calendar-day:hover {
  background: rgba(255, 255, 255, 0.1);
}

.calendar-today {
  background: #0078D4;
  color: #ffffff;
  font-weight: 600;
}

/* Notes Widget */
.notes-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  margin-bottom: 16px;
}

.note-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 12px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.note-text {
  color: #ffffff;
  font-size: 13px;
  line-height: 1.4;
}

.note-time {
  color: rgba(255, 255, 255, 0.5);
  font-size: 11px;
}

.note-add-btn {
  width: 100%;
  padding: 10px;
  background: rgba(0, 120, 212, 0.2);
  border: 1px solid rgba(0, 120, 212, 0.3);
  border-radius: 6px;
  color: #ffffff;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.note-add-btn:hover {
  background: rgba(0, 120, 212, 0.3);
  border-color: rgba(0, 120, 212, 0.5);
}

/* System Widget */
.system-item {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.system-label {
  color: rgba(255, 255, 255, 0.8);
  font-size: 12px;
  min-width: 60px;
}

.system-bar {
  flex: 1;
  height: 8px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  overflow: hidden;
}

.system-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #00c851, #ffbb33, #ff4444);
  border-radius: 4px;
  transition: width 0.3s ease;
}

.system-value {
  color: #ffffff;
  font-size: 12px;
  font-weight: 500;
  min-width: 35px;
  text-align: right;
}

/* Files Widget */
.file-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.file-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  cursor: pointer;
  transition: all 0.2s;
}

.file-item:hover {
  background: rgba(255, 255, 255, 0.1);
  border-color: rgba(255, 255, 255, 0.2);
}

.file-icon {
  font-size: 16px;
  width: 20px;
  text-align: center;
}

.file-icon.pdf { color: #ff4444; }
.file-icon.excel { color: #00c851; }
.file-icon.image { color: #33b5e5; }

.file-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.file-name {
  color: #ffffff;
  font-size: 12px;
  font-weight: 500;
}

.file-date {
  color: rgba(255, 255, 255, 0.5);
  font-size: 11px;
}

/* Tasks Widget */
.task-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 16px;
}

.task-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 6px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.task-checkbox {
  width: 16px;
  height: 16px;
  accent-color: #0078D4;
}

.task-text {
  color: #ffffff;
  font-size: 12px;
  flex: 1;
}

.task-text.completed {
  text-decoration: line-through;
  color: rgba(255, 255, 255, 0.5);
}

.task-add-btn {
  width: 100%;
  padding: 10px;
  background: rgba(0, 120, 212, 0.2);
  border: 1px solid rgba(0, 120, 212, 0.3);
  border-radius: 6px;
  color: #ffffff;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
}

.task-add-btn:hover {
  background: rgba(0, 120, 212, 0.3);
  border-color: rgba(0, 120, 212, 0.5);
}

/* Mobile responsive adjustments */
@media (max-width: 1023px) {
  #widgets-screen {
    width: 100%;
    pointer-events: auto !important;
  }
  
  .widget {
    margin: 0 10px;
  }
}
</style> 