<template>
  <div class="team-banner" :class="{ 'expanded': isExpanded }">
    <div class="team-banner-header" @click="toggleExpanded">
      <div class="team-info">
        <div class="team-avatar">
          <img v-if="team?.avatar" :src="team.avatar" :alt="team.name" />
          <div v-else class="team-avatar-placeholder">
            <UsersIcon class="team-icon" />
          </div>
        </div>
        <div class="team-details">
          <h3 class="team-name">{{ team?.name || 'No Team' }}</h3>
          <p class="team-description">{{ team?.description || 'Join or create a team' }}</p>
        </div>
      </div>
      <div class="team-actions">
        <span class="member-count">{{ memberCount }} members</span>
        <ChevronDownIcon class="expand-icon" :class="{ 'rotated': isExpanded }" />
      </div>
    </div>
    
    <transition name="team-content">
      <div v-if="isExpanded" class="team-banner-content">
        <div class="team-members">
          <h4 class="section-title">Team Members</h4>
          <div class="members-list">
            <div 
              v-for="member in displayMembers" 
              :key="member.id"
              class="member-item"
              :class="{ 'online': member.isOnline }"
            >
              <div class="member-avatar">
                <img v-if="member.avatar" :src="member.avatar" :alt="member.name" />
                <div v-else class="member-avatar-placeholder">
                  {{ member.name.charAt(0).toUpperCase() }}
                </div>
                <div v-if="member.isOnline" class="online-indicator"></div>
              </div>
              <div class="member-info">
                <span class="member-name">{{ member.name }}</span>
                <span class="member-role">{{ member.role }}</span>
              </div>
              <div class="member-actions">
                <button 
                  v-if="canManageMembers && member.id !== currentUserId"
                  @click="removeMember(member.id)"
                  class="remove-member-btn"
                  title="Remove member"
                >
                  <XMarkIcon class="action-icon" />
                </button>
              </div>
            </div>
            
            <button 
              v-if="members.length > maxDisplayMembers"
              @click="showAllMembers"
              class="show-more-btn"
            >
              +{{ members.length - maxDisplayMembers }} more
            </button>
          </div>
        </div>
        
        <div class="team-quick-actions">
          <button 
            v-if="canInviteMembers"
            @click="inviteMember"
            class="quick-action-btn primary"
          >
            <UsersIcon class="action-icon" />
            Invite Member
          </button>
          
          <button 
            v-if="canManageTeam"
            @click="openTeamSettings"
            class="quick-action-btn"
          >
            <CogIcon class="action-icon" />
            Team Settings
          </button>
          
          <button 
            @click="openTeamChat"
            class="quick-action-btn"
          >
            <ChatBubbleLeftRightIcon class="action-icon" />
            Team Chat
          </button>
          
          <button 
            v-if="!team"
            @click="createOrJoinTeam"
            class="quick-action-btn primary"
          >
            <PlusIcon class="action-icon" />
            Create/Join Team
          </button>
        </div>
        
        <div v-if="team" class="team-stats">
          <div class="stat-item">
            <span class="stat-label">Active Projects</span>
            <span class="stat-value">{{ team.activeProjects || 0 }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Total Storage</span>
            <span class="stat-value">{{ formatStorage(team.storageUsed || 0) }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Plan</span>
            <span class="stat-value">{{ team.plan || 'Free' }}</span>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import {
  UsersIcon,
  ChevronDownIcon,
  // UserPlusIcon, // Replaced with UsersIcon
  CogIcon,
  ChatBubbleLeftRightIcon,
  PlusIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'

interface TeamMember {
  id: string
  name: string
  avatar?: string
  role: string
  isOnline: boolean
}

interface Team {
  id: string
  name: string
  description?: string
  avatar?: string
  activeProjects?: number
  storageUsed?: number
  plan?: string
}

interface Props {
  team?: Team
  members?: TeamMember[]
  currentUserId?: string
  canManageTeam?: boolean
  canManageMembers?: boolean
  canInviteMembers?: boolean
}

interface Emits {
  teamSettingsClick: []
  inviteMemberClick: []
  teamChatClick: []
  createTeamClick: []
  joinTeamClick: []
  removeMemberClick: [memberId: string]
  showAllMembersClick: []
}

const props = withDefaults(defineProps<Props>(), {
  members: () => [],
  canManageTeam: false,
  canManageMembers: false,
  canInviteMembers: true
})

const emit = defineEmits<Emits>()

const isExpanded = ref(false)
const maxDisplayMembers = ref(5)

const memberCount = computed(() => props.members?.length || 0)

const displayMembers = computed(() => {
  return props.members?.slice(0, maxDisplayMembers.value) || []
})

const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value
}

const inviteMember = () => {
  emit('inviteMemberClick')
}

const openTeamSettings = () => {
  emit('teamSettingsClick')
}

const openTeamChat = () => {
  emit('teamChatClick')
}

const createOrJoinTeam = () => {
  emit('createTeamClick')
}

const removeMember = (memberId: string) => {
  emit('removeMemberClick', memberId)
}

const showAllMembers = () => {
  emit('showAllMembersClick')
}

const formatStorage = (bytes: number): string => {
  const units = ['B', 'KB', 'MB', 'GB', 'TB']
  let size = bytes
  let unitIndex = 0
  
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024
    unitIndex++
  }
  
  return `${size.toFixed(1)} ${units[unitIndex]}`
}

onMounted(() => {
  // Auto-expand if no team (to show create/join options)
  if (!props.team) {
    isExpanded.value = true
  }
})
</script>

<style scoped>
.team-banner {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.team-banner.expanded {
  background: rgba(255, 255, 255, 0.15);
}

.team-banner-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 20px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.team-banner-header:hover {
  background: rgba(255, 255, 255, 0.05);
}

.team-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.team-avatar {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  overflow: hidden;
  background: rgba(59, 130, 246, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.team-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.team-avatar-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.team-icon {
  width: 20px;
  height: 20px;
  color: #60a5fa;
}

.team-details {
  flex: 1;
}

.team-name {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
  color: white;
}

.team-description {
  margin: 4px 0 0;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
}

.team-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.member-count {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
}

.expand-icon {
  width: 20px;
  height: 20px;
  color: rgba(255, 255, 255, 0.7);
  transition: transform 0.2s ease;
}

.expand-icon.rotated {
  transform: rotate(180deg);
}

.team-banner-content {
  padding: 0 20px 20px;
}

.section-title {
  margin: 0 0 12px;
  font-size: 14px;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.9);
}

.members-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 20px;
}

.member-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  transition: background-color 0.2s ease;
}

.member-item:hover {
  background: rgba(255, 255, 255, 0.1);
}

.member-item.online {
  border-left: 3px solid #10b981;
}

.member-avatar {
  position: relative;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  overflow: hidden;
  background: rgba(59, 130, 246, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.member-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.member-avatar-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  font-weight: 600;
  color: #60a5fa;
}

.online-indicator {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 12px;
  height: 12px;
  background: #10b981;
  border: 2px solid rgba(0, 0, 0, 0.2);
  border-radius: 50%;
}

.member-info {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.member-name {
  font-size: 14px;
  font-weight: 500;
  color: white;
}

.member-role {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
}

.member-actions {
  display: flex;
  gap: 4px;
}

.remove-member-btn {
  padding: 4px;
  background: none;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.remove-member-btn:hover {
  background: rgba(239, 68, 68, 0.2);
}

.action-icon {
  width: 16px;
  height: 16px;
  color: rgba(255, 255, 255, 0.6);
}

.remove-member-btn:hover .action-icon {
  color: #ef4444;
}

.show-more-btn {
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  color: rgba(255, 255, 255, 0.8);
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.show-more-btn:hover {
  background: rgba(255, 255, 255, 0.15);
}

.team-quick-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 20px;
}

.quick-action-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 6px;
  color: white;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.quick-action-btn:hover {
  background: rgba(255, 255, 255, 0.15);
}

.quick-action-btn.primary {
  background: rgba(59, 130, 246, 0.3);
  border-color: rgba(59, 130, 246, 0.5);
}

.quick-action-btn.primary:hover {
  background: rgba(59, 130, 246, 0.4);
}

.team-stats {
  display: flex;
  gap: 20px;
  padding-top: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.stat-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-label {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
}

.stat-value {
  font-size: 14px;
  font-weight: 600;
  color: white;
}

/* Transitions */
.team-content-enter-active,
.team-content-leave-active {
  transition: all 0.3s ease;
  max-height: 400px;
  overflow: hidden;
}

.team-content-enter-from,
.team-content-leave-to {
  max-height: 0;
  opacity: 0;
}
</style> 