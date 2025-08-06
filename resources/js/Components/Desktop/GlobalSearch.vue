<template>
  <div 
    v-show="show"
    class="global-search-overlay"
    @click="handleOverlayClick"
  >
    <div class="global-search-container" @click.stop>
      <!-- Search Header -->
      <div class="search-header">
        <div class="search-input-container">
          <MagnifyingGlassIcon class="search-input-icon" />
          <input
            ref="searchInput"
            v-model="searchQuery"
            class="search-input"
            placeholder="Search apps, files, settings, and more..."
            @input="handleSearchInput"
            @keydown.escape="$emit('close')"
            @keydown.enter="handleSearchEnter"
            @keydown.arrow-down="navigateDown"
            @keydown.arrow-up="navigateUp"
          />
          <button v-if="searchQuery" class="search-clear" @click="clearSearch">
            <XMarkIcon class="clear-icon" />
          </button>
        </div>
        <button class="search-close" @click="$emit('close')" title="Close">
          <XMarkIcon class="close-icon" />
        </button>
      </div>

      <!-- Search Filters -->
      <div class="search-filters">
        <button
          v-for="filter in searchFilters"
          :key="filter.key"
          class="search-filter"
          :class="{ 'filter-active': activeFilter === filter.key }"
          @click="setActiveFilter(filter.key)"
        >
          <component :is="filter.icon" class="filter-icon" />
          {{ filter.label }}
        </button>
      </div>

      <!-- Search Results -->
      <div class="search-results">
        <!-- Loading State -->
        <div v-if="isSearching" class="search-loading">
          <div class="loading-spinner"></div>
          <p>Searching...</p>
        </div>

        <!-- No Results -->
        <div v-else-if="searchQuery && filteredResults.length === 0" class="search-empty">
          <MagnifyingGlassIcon class="empty-search-icon" />
          <p class="empty-text">No results found</p>
          <p class="empty-subtext">
            Try adjusting your search terms or filters
          </p>
        </div>

        <!-- Recent Searches (when no query) -->
        <div v-else-if="!searchQuery && recentSearches.length > 0" class="recent-searches">
          <h3 class="section-title">Recent Searches</h3>
          <div class="recent-list">
            <div
              v-for="recent in recentSearches"
              :key="recent.id"
              class="recent-item"
              @click="setSearchQuery(recent.query)"
            >
              <XMarkIcon class="recent-icon" />
              <span class="recent-query">{{ recent.query }}</span>
              <button 
                class="recent-remove"
                @click.stop="removeRecentSearch(recent.id)"
                title="Remove"
              >
                <XMarkIcon class="remove-icon" />
              </button>
            </div>
          </div>
        </div>

        <!-- Search Results -->
        <div v-else-if="filteredResults.length > 0" class="results-container">
          <!-- Apps Results -->
          <div v-if="appsResults.length > 0" class="results-section">
            <h3 class="section-title">Apps</h3>
            <div class="results-list">
              <div
                v-for="(result, index) in appsResults"
                :key="result.id"
                class="result-item"
                :class="{ 'result-highlighted': highlightedIndex === getResultIndex('apps', index) }"
                @click="handleResultClick(result)"
                @mouseenter="setHighlightedIndex(getResultIndex('apps', index))"
              >
                <div class="result-icon">
                  <component :is="getAppIcon(result.appId)" class="result-icon-image" />
                </div>
                <div class="result-content">
                  <span class="result-title" v-html="highlightMatch(result.title)"></span>
                  <span class="result-subtitle">{{ result.description }}</span>
                </div>
                <span class="result-type">App</span>
              </div>
            </div>
          </div>

          <!-- Files Results -->
          <div v-if="filesResults.length > 0" class="results-section">
            <h3 class="section-title">Files</h3>
            <div class="results-list">
              <div
                v-for="(result, index) in filesResults"
                :key="result.id"
                class="result-item"
                :class="{ 'result-highlighted': highlightedIndex === getResultIndex('files', index) }"
                @click="handleResultClick(result)"
                @mouseenter="setHighlightedIndex(getResultIndex('files', index))"
              >
                <div class="result-icon">
                  <DocumentIcon class="result-icon-image" />
                </div>
                <div class="result-content">
                  <span class="result-title" v-html="highlightMatch(result.title)"></span>
                  <span class="result-subtitle">{{ result.path }}</span>
                </div>
                <span class="result-type">File</span>
              </div>
            </div>
          </div>

          <!-- Settings Results -->
          <div v-if="settingsResults.length > 0" class="results-section">
            <h3 class="section-title">Settings</h3>
            <div class="results-list">
              <div
                v-for="(result, index) in settingsResults"
                :key="result.id"
                class="result-item"
                :class="{ 'result-highlighted': highlightedIndex === getResultIndex('settings', index) }"
                @click="handleResultClick(result)"
                @mouseenter="setHighlightedIndex(getResultIndex('settings', index))"
              >
                <div class="result-icon">
                  <CogIcon class="result-icon-image" />
                </div>
                <div class="result-content">
                  <span class="result-title" v-html="highlightMatch(result.title)"></span>
                  <span class="result-subtitle">{{ result.category }}</span>
                </div>
                <span class="result-type">Setting</span>
              </div>
            </div>
          </div>

          <!-- Quick Actions -->
          <div v-if="quickActions.length > 0" class="results-section">
            <h3 class="section-title">Quick Actions</h3>
            <div class="results-list">
              <div
                v-for="(action, index) in quickActions"
                :key="action.id"
                class="result-item action-item"
                :class="{ 'result-highlighted': highlightedIndex === getResultIndex('actions', index) }"
                @click="handleActionClick(action)"
                @mouseenter="setHighlightedIndex(getResultIndex('actions', index))"
              >
                <div class="result-icon">
                  <component :is="action.icon" class="result-icon-image" />
                </div>
                <div class="result-content">
                  <span class="result-title">{{ action.title }}</span>
                  <span class="result-subtitle">{{ action.description }}</span>
                </div>
                <span class="result-type">Action</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Search Footer -->
      <div class="search-footer">
        <div class="search-tips">
          <kbd>↑</kbd><kbd>↓</kbd> to navigate • <kbd>Enter</kbd> to select • <kbd>Esc</kbd> to close
        </div>
        <div class="search-stats">
          {{ filteredResults.length }} result{{ filteredResults.length !== 1 ? 's' : '' }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import {
  MagnifyingGlassIcon,
  XMarkIcon,
  XMarkIcon as ClockIcon,
  DocumentIcon,
  HomeIcon,
  CogIcon,
  UserIcon,
  LightBulbIcon,
  DocumentTextIcon,
  PlusIcon,
  FolderIcon
} from '@heroicons/vue/24/outline'

interface SearchResult {
  id: string
  type: 'app' | 'file' | 'setting' | 'action'
  title: string
  description?: string
  path?: string
  category?: string
  appId?: string
  action?: string
  icon?: any
}

interface RecentSearch {
  id: string
  query: string
  timestamp: Date
}

interface QuickAction {
  id: string
  title: string
  description: string
  action: string
  icon: any
}

interface Props {
  show?: boolean
  query?: string
  results: SearchResult[]
}

const props = withDefaults(defineProps<Props>(), {
  show: false,
  query: '',
  results: () => []
})

const emit = defineEmits<{
  close: []
  search: [query: string]
  resultClick: [result: SearchResult]
  actionClick: [action: QuickAction]
}>()

// State
const searchQuery = ref('')
const searchInput = ref<HTMLInputElement>()
const activeFilter = ref('all')
const highlightedIndex = ref(-1)
const isSearching = ref(false)
const recentSearches = ref<RecentSearch[]>([])

// Search filters
const searchFilters = computed(() => [
  { key: 'all', label: 'All', icon: MagnifyingGlassIcon },
  { key: 'apps', label: 'Apps', icon: HomeIcon },
  { key: 'files', label: 'Files', icon: FolderIcon },
  { key: 'settings', label: 'Settings', icon: CogIcon }
])

// Filter results by type
const appsResults = computed(() => {
  return filteredResults.value?.filter((r: any) => r.type === 'app').slice(0, 5) || []
})

const filesResults = computed(() => {
  return filteredResults.value?.filter((r: any) => r.type === 'file').slice(0, 5) || []
})

const settingsResults = computed(() => {
  return filteredResults.value?.filter((r: any) => r.type === 'setting').slice(0, 5) || []
})

const filteredResults = computed(() => {
  let results = props.results

  if (activeFilter.value !== 'all') {
    results = results.filter((r: any) => r.type === activeFilter.value)
  }

  return results
})

// Quick actions based on search query
const quickActions = computed(() => {
  const actions: QuickAction[] = []
  const query = searchQuery.value?.toLowerCase() || ''

  if (query.includes('calculator') || query.includes('calc')) {
    actions.push({
      id: 'open-calculator',
      title: 'Open Calculator',
      description: 'Launch the calculator app',
      action: 'launch-app:calculator',
      icon: PlusIcon
    })
  }

  if (query.includes('shutdown') || query.includes('power off')) {
    actions.push({
      id: 'shutdown',
      title: 'Shutdown Computer',
      description: 'Power off the system',
      action: 'power:shutdown',
      icon: LightBulbIcon
    })
  }

  if (query.includes('restart') || query.includes('reboot')) {
    actions.push({
      id: 'restart',
      title: 'Restart Computer',
      description: 'Restart the system',
      action: 'power:restart',
      icon: LightBulbIcon
    })
  }

  return actions
})

// App icon mapping
const appIcons = {
  'calculator': PlusIcon,
  'file-explorer': FolderIcon,
  'settings': CogIcon,
  'browser': HomeIcon,
  'email': UserIcon,
  'default': HomeIcon
}

// Methods
const getAppIcon = (appId?: string) => {
  if (!appId) return HomeIcon
  return appIcons[appId as keyof typeof appIcons] || HomeIcon
}

const highlightMatch = (text: string) => {
  if (!searchQuery.value) return text
  
  const regex = new RegExp(`(${searchQuery.value})`, 'gi')
  return text.replace(regex, '<mark>$1</mark>')
}

const getResultIndex = (section: string, index: number) => {
  let baseIndex = 0
  
  if (section === 'files') baseIndex += appsResults.value.length
  if (section === 'settings') baseIndex += appsResults.value.length + filesResults.value.length
  if (section === 'actions') {
    baseIndex += appsResults.value.length + filesResults.value.length + settingsResults.value.length
  }
  
  return baseIndex + index
}

const setHighlightedIndex = (index: number) => {
  highlightedIndex.value = index
}

const setActiveFilter = (filter: string) => {
  activeFilter.value = filter
  highlightedIndex.value = -1
}

const setSearchQuery = (query: string) => {
  searchQuery.value = query
  handleSearchInput()
}

const clearSearch = () => {
  searchQuery.value = ''
  highlightedIndex.value = -1
  nextTick(() => {
    searchInput.value?.focus()
  })
}

const handleSearchInput = () => {
  highlightedIndex.value = -1
  
  if (searchQuery.value?.trim()) {
    isSearching.value = true
    
    // Simulate search delay
    setTimeout(() => {
      emit('search', searchQuery.value?.trim() || '')
      isSearching.value = false
    }, 200)
  }
}

const handleSearchEnter = () => {
  const totalResults = [...appsResults.value, ...filesResults.value, ...settingsResults.value, ...quickActions.value]
  
  if ((highlightedIndex.value ?? -1) >= 0 && totalResults[highlightedIndex.value ?? 0]) {
    const result = totalResults[highlightedIndex.value ?? 0]
    if ('action' in result) {
      handleActionClick(result as QuickAction)
    } else {
      handleResultClick(result as SearchResult)
    }
  } else if (totalResults.length > 0) {
    const firstResult = totalResults[0]
    if ('action' in firstResult) {
      handleActionClick(firstResult as QuickAction)
    } else {
      handleResultClick(firstResult as SearchResult)
    }
  }
}

const navigateDown = () => {
  const totalResults = [...appsResults.value, ...filesResults.value, ...settingsResults.value, ...quickActions.value]
  if ((highlightedIndex.value ?? -1) < totalResults.length - 1) {
    highlightedIndex.value = (highlightedIndex.value ?? -1) + 1
  }
}

const navigateUp = () => {
  if ((highlightedIndex.value ?? 0) > 0) {
    highlightedIndex.value = (highlightedIndex.value ?? 0) - 1
  }
}

const handleResultClick = (result: SearchResult) => {
  addToRecentSearches(searchQuery.value || '')
  emit('resultClick', result)
  emit('close')
}

const handleActionClick = (action: QuickAction) => {
  addToRecentSearches(searchQuery.value || '')
  emit('actionClick', action)
  emit('close')
}

const handleOverlayClick = () => {
  emit('close')
}

const addToRecentSearches = (query: string) => {
  if (!query.trim()) return
  
  // Remove existing entry
  recentSearches.value = recentSearches.value?.filter(r => r.query !== query) || []
  
  // Add to beginning
  recentSearches.value.unshift({
    id: Date.now().toString(),
    query,
    timestamp: new Date()
  })
  
  // Keep only 10 recent searches
  recentSearches.value = recentSearches.value.slice(0, 10)
  
  // Save to localStorage
  localStorage.setItem('global-search-recent', JSON.stringify(recentSearches.value))
}

const removeRecentSearch = (id: string) => {
  recentSearches.value = recentSearches.value?.filter(r => r.id !== id) || []
  localStorage.setItem('global-search-recent', JSON.stringify(recentSearches.value))
}

const loadRecentSearches = () => {
  try {
    const stored = localStorage.getItem('global-search-recent')
    if (stored) {
      recentSearches.value = JSON.parse(stored)
    }
  } catch (error) {
    console.warn('Failed to load recent searches:', error)
  }
}

// Watchers
watch(() => props.show, (newShow: boolean) => {
  if (newShow) {
    nextTick(() => {
      searchInput.value?.focus()
    })
  } else {
    searchQuery.value = ''
    highlightedIndex.value = -1
    activeFilter.value = 'all'
  }
})

watch(() => props.query, (newQuery: string | undefined) => {
  searchQuery.value = newQuery || ''
})

// Lifecycle
onMounted(() => {
  loadRecentSearches()
})
</script>

<style scoped>
.global-search-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(8px);
  z-index: 2000;
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 80px 20px 20px;
  animation: fadeIn 0.2s ease-out;
}

.global-search-container {
  width: 100%;
  max-width: 640px;
  background: rgba(31, 41, 55, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
  overflow: hidden;
  animation: slideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.search-header {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.search-input-container {
  flex: 1;
  position: relative;
  display: flex;
  align-items: center;
}

.search-input-icon {
  position: absolute;
  left: 16px;
  width: 20px;
  height: 20px;
  color: rgba(255, 255, 255, 0.5);
}

.search-input {
  width: 100%;
  padding: 12px 20px 12px 48px;
  background: rgba(55, 65, 81, 0.8);
  border: 2px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  color: #ffffff;
  font-size: 16px;
  outline: none;
  transition: border-color 0.2s ease;
}

.search-input:focus {
  border-color: rgba(59, 130, 246, 0.5);
}

.search-input::placeholder {
  color: rgba(255, 255, 255, 0.5);
}

.search-clear {
  position: absolute;
  right: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 50%;
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  transition: all 0.2s ease;
}

.search-clear:hover {
  background: rgba(255, 255, 255, 0.2);
  color: #ffffff;
}

.search-close {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: rgba(255, 255, 255, 0.1);
  border: none;
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.7);
  cursor: pointer;
  transition: all 0.2s ease;
}

.search-close:hover {
  background: rgba(255, 255, 255, 0.2);
  color: #ffffff;
}

.clear-icon {
  width: 16px; /* w-4 */
  height: 16px; /* h-4 */
}

.close-icon {
  width: 20px; /* w-5 */
  height: 20px; /* h-5 */
}

.empty-search-icon {
  width: 48px; /* w-12 */
  height: 48px; /* h-12 */
  color: #6b7280; /* text-gray-500 */
  margin-bottom: 16px; /* mb-4 */
}

.empty-text {
  color: #6b7280; /* text-gray-500 */
}

.empty-subtext {
  font-size: 12px; /* text-xs */
  color: #9ca3af; /* text-gray-400 */
  margin-top: 8px; /* mt-2 */
}

.remove-icon {
  width: 12px; /* w-3 */
  height: 12px; /* h-3 */
}

.search-filters {
  display: flex;
  gap: 8px;
  padding: 16px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(17, 24, 39, 0.5);
}

.search-filter {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  background: rgba(55, 65, 81, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 20px;
  color: rgba(255, 255, 255, 0.7);
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.search-filter:hover {
  background: rgba(75, 85, 99, 0.7);
  color: #ffffff;
}

.search-filter.filter-active {
  background: rgba(59, 130, 246, 0.3);
  border-color: rgba(59, 130, 246, 0.5);
  color: #60a5fa;
}

.filter-icon {
  width: 14px;
  height: 14px;
}

.search-results {
  max-height: 400px;
  overflow-y: auto;
  padding: 8px 0;
}

.search-loading,
.search-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
  text-align: center;
}

.loading-spinner {
  width: 24px;
  height: 24px;
  border: 2px solid rgba(255, 255, 255, 0.2);
  border-top: 2px solid #60a5fa;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 16px;
}

.recent-searches,
.results-container {
  padding: 8px 0;
}

.section-title {
  color: rgba(255, 255, 255, 0.6);
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 8px 20px;
  margin: 0;
}

.recent-list,
.results-list {
  display: flex;
  flex-direction: column;
}

.recent-item,
.result-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 20px;
  cursor: pointer;
  transition: background-color 0.1s ease;
}

.recent-item:hover,
.result-item:hover,
.result-highlighted {
  background: rgba(55, 65, 81, 0.5);
}

.recent-icon,
.result-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(59, 130, 246, 0.2);
  border-radius: 8px;
  flex-shrink: 0;
}

.recent-icon {
  width: 24px;
  height: 24px;
  color: rgba(255, 255, 255, 0.5);
}

.result-icon-image {
  width: 18px;
  height: 18px;
  color: #60a5fa;
}

.recent-query,
.result-content {
  flex: 1;
  min-width: 0;
}

.recent-query {
  color: rgba(255, 255, 255, 0.8);
  font-size: 14px;
}

.result-title {
  display: block;
  color: #ffffff;
  font-size: 14px;
  font-weight: 500;
  line-height: 1.2;
  margin-bottom: 2px;
}

.result-subtitle {
  display: block;
  color: rgba(255, 255, 255, 0.6);
  font-size: 12px;
  line-height: 1.2;
}

.result-type {
  color: rgba(255, 255, 255, 0.4);
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  flex-shrink: 0;
}

.recent-remove {
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(239, 68, 68, 0.2);
  border: none;
  border-radius: 50%;
  color: #f87171;
  cursor: pointer;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.recent-item:hover .recent-remove {
  opacity: 1;
}

.action-item {
  border-left: 3px solid #10b981;
}

.search-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(17, 24, 39, 0.5);
}

.search-tips {
  display: flex;
  align-items: center;
  gap: 4px;
  color: rgba(255, 255, 255, 0.5);
  font-size: 11px;
}

.search-tips kbd {
  padding: 2px 6px;
  background: rgba(55, 65, 81, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 3px;
  font-size: 10px;
  color: rgba(255, 255, 255, 0.7);
}

.search-stats {
  color: rgba(255, 255, 255, 0.5);
  font-size: 11px;
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Highlight matches */
:deep(mark) {
  background: rgba(59, 130, 246, 0.3);
  color: #60a5fa;
  padding: 0 2px;
  border-radius: 2px;
}

/* Light theme support */
.light-theme .global-search-container {
  background: rgba(249, 250, 251, 0.95);
  border-color: rgba(0, 0, 0, 0.1);
}

.light-theme .search-input {
  background: rgba(255, 255, 255, 0.8);
  border-color: rgba(0, 0, 0, 0.1);
  color: #1f2937;
}

.light-theme .result-title {
  color: #1f2937;
}

.light-theme .section-title {
  color: rgba(31, 41, 55, 0.6);
}
</style> 