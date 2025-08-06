<template>
  <div v-if="show" class="team-management-modal-overlay" @click="handleOverlayClick">
    <div class="team-management-modal" @click.stop>
      <div class="modal-header">
        <div class="header-content">
          <div class="team-avatar">
            <img v-if="team?.avatar" :src="team.avatar" :alt="team.name" />
            <div v-else class="team-avatar-placeholder">
              <UsersIcon class="team-icon" />
            </div>
          </div>
          <div class="team-info">
            <h2 class="team-name">{{ team?.name || 'Team Settings' }}</h2>
            <p class="team-id">Team ID: {{ team?.id || 'N/A' }}</p>
          </div>
        </div>
        <button @click="close" class="close-btn">
          <XMarkIcon class="close-icon" />
        </button>
      </div>
      
      <div class="modal-content">
        <div class="tabs-container">
          <div class="tabs">
            <button 
              v-for="tab in tabs" 
              :key="tab.id"
              @click="activeTab = tab.id"
              class="tab-btn"
              :class="{ 'active': activeTab === tab.id }"
            >
              <component :is="tab.icon" class="tab-icon" />
              {{ tab.label }}
            </button>
          </div>
        </div>
        
        <div class="tab-content">
          <!-- General Settings Tab -->
          <div v-if="activeTab === 'general'" class="tab-panel">
            <div class="section">
              <h3 class="section-title">Team Information</h3>
              <div class="form-group">
                <label class="form-label">Team Name</label>
                <input 
                  v-model="teamForm.name"
                  type="text"
                  class="form-input"
                  placeholder="Enter team name"
                />
              </div>
              <div class="form-group">
                <label class="form-label">Description</label>
                <textarea 
                  v-model="teamForm.description"
                  class="form-textarea"
                  placeholder="Describe your team..."
                  rows="3"
                ></textarea>
              </div>
              <div class="form-group">
                <label class="form-label">Team Avatar</label>
                <div class="avatar-upload">
                  <div class="current-avatar">
                    <img v-if="teamForm.avatar" :src="teamForm.avatar" alt="Team avatar" />
                    <div v-else class="avatar-placeholder">
                      <UsersIcon class="placeholder-icon" />
                    </div>
                  </div>
                  <div class="upload-controls">
                    <button @click="uploadAvatar" class="upload-btn">
                      <PhotoIcon class="btn-icon" />
                      Upload Image
                    </button>
                    <button v-if="teamForm.avatar" @click="removeAvatar" class="remove-btn">
                      <TrashIcon class="btn-icon" />
                      Remove
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="section">
              <h3 class="section-title">Team Settings</h3>
              <div class="setting-item">
                <div class="setting-info">
                  <span class="setting-name">Public Team</span>
                  <span class="setting-description">Allow anyone to discover and join your team</span>
                </div>
                <label class="toggle-switch">
                  <input v-model="teamForm.isPublic" type="checkbox" />
                  <span class="toggle-slider"></span>
                </label>
              </div>
              <div class="setting-item">
                <div class="setting-info">
                  <span class="setting-name">Require Approval</span>
                  <span class="setting-description">New members must be approved by administrators</span>
                </div>
                <label class="toggle-switch">
                  <input v-model="teamForm.requireApproval" type="checkbox" />
                  <span class="toggle-slider"></span>
                </label>
              </div>
              <div class="setting-item">
                <div class="setting-info">
                  <span class="setting-name">Allow Guest Access</span>
                  <span class="setting-description">Enable temporary guest access to team resources</span>
                </div>
                <label class="toggle-switch">
                  <input v-model="teamForm.allowGuests" type="checkbox" />
                  <span class="toggle-slider"></span>
                </label>
              </div>
            </div>
          </div>
          
          <!-- Members Tab -->
          <div v-if="activeTab === 'members'" class="tab-panel">
            <div class="section">
              <div class="section-header">
                <h3 class="section-title">Team Members ({{ members.length }})</h3>
                <button @click="inviteMember" class="invite-btn">
                  <UserPlusIcon class="btn-icon" />
                  Invite Member
                </button>
              </div>
              
              <div class="members-list">
                <div 
                  v-for="member in members" 
                  :key="member.id"
                  class="member-card"
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
                    <span class="member-email">{{ member.email }}</span>
                    <span class="member-role">{{ member.role }}</span>
                    <span class="member-joined">Joined {{ formatDate(member.joinedAt) }}</span>
                  </div>
                  <div class="member-actions">
                    <select 
                      v-if="canManageRoles(member)"
                      v-model="member.role"
                      @change="updateMemberRole(member)"
                      class="role-select"
                    >
                      <option value="member">Member</option>
                      <option value="moderator">Moderator</option>
                      <option value="admin">Admin</option>
                      <option value="owner">Owner</option>
                    </select>
                    <button 
                      v-if="canRemoveMember(member)"
                      @click="removeMember(member)"
                      class="remove-member-btn"
                      title="Remove member"
                    >
                      <XMarkIcon class="btn-icon" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="section">
              <h3 class="section-title">Pending Invitations</h3>
              <div v-if="pendingInvitations.length === 0" class="empty-state">
                <span>No pending invitations</span>
              </div>
              <div v-else class="invitations-list">
                <div 
                  v-for="invitation in pendingInvitations" 
                  :key="invitation.id"
                  class="invitation-card"
                >
                  <div class="invitation-info">
                    <span class="invitation-email">{{ invitation.email }}</span>
                    <span class="invitation-role">{{ invitation.role }}</span>
                    <span class="invitation-date">Sent {{ formatDate(invitation.sentAt) }}</span>
                  </div>
                  <div class="invitation-actions">
                    <button @click="resendInvitation(invitation)" class="resend-btn">
                      <ArrowPathIcon class="btn-icon" />
                      Resend
                    </button>
                    <button @click="cancelInvitation(invitation)" class="cancel-btn">
                      <XMarkIcon class="btn-icon" />
                      Cancel
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Permissions Tab -->
          <div v-if="activeTab === 'permissions'" class="tab-panel">
            <div class="section">
              <h3 class="section-title">Role Permissions</h3>
              
              <div class="permissions-matrix">
                <div class="permission-header">
                  <span class="permission-name">Permission</span>
                  <span class="role-header">Member</span>
                  <span class="role-header">Moderator</span>
                  <span class="role-header">Admin</span>
                  <span class="role-header">Owner</span>
                </div>
                
                <div 
                  v-for="permission in permissions" 
                  :key="permission.id"
                  class="permission-row"
                >
                  <div class="permission-info">
                    <span class="permission-title">{{ permission.name }}</span>
                    <span class="permission-description">{{ permission.description }}</span>
                  </div>
                  <div 
                    v-for="role in ['member', 'moderator', 'admin', 'owner']" 
                    :key="role"
                    class="permission-cell"
                  >
                    <label class="permission-checkbox">
                      <input 
                        v-model="permission.roles[role]"
                        type="checkbox"
                        :disabled="!canEditPermissions || role === 'owner'"
                      />
                      <span class="checkmark"></span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Storage Tab -->
          <div v-if="activeTab === 'storage'" class="tab-panel">
            <div class="section">
              <h3 class="section-title">Storage Usage</h3>
              
              <div class="storage-overview">
                <div class="storage-stat">
                  <span class="stat-label">Used</span>
                  <span class="stat-value">{{ formatStorage(storageStats.used) }}</span>
                </div>
                <div class="storage-stat">
                  <span class="stat-label">Available</span>
                  <span class="stat-value">{{ formatStorage(storageStats.total - storageStats.used) }}</span>
                </div>
                <div class="storage-stat">
                  <span class="stat-label">Total</span>
                  <span class="stat-value">{{ formatStorage(storageStats.total) }}</span>
                </div>
              </div>
              
              <div class="storage-bar">
                <div 
                  class="storage-progress"
                  :style="{ width: `${storagePercentage}%` }"
                ></div>
              </div>
              
              <div class="storage-breakdown">
                <div 
                  v-for="category in storageStats.breakdown" 
                  :key="category.type"
                  class="storage-category"
                >
                  <div class="category-info">
                    <span class="category-name">{{ category.name }}</span>
                    <span class="category-size">{{ formatStorage(category.size) }}</span>
                  </div>
                  <div class="category-bar">
                    <div 
                      class="category-progress"
                      :style="{ 
                        width: `${(category.size / storageStats.used) * 100}%`,
                        backgroundColor: category.color 
                      }"
                    ></div>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="section">
              <h3 class="section-title">Storage Management</h3>
              <div class="storage-actions">
                <button @click="cleanupStorage" class="storage-action-btn">
                  <TrashIcon class="btn-icon" />
                  Clean Up Storage
                </button>
                <button @click="exportData" class="storage-action-btn">
                  <ArrowDownTrayIcon class="btn-icon" />
                  Export Data
                </button>
                <button @click="upgradeStorage" class="storage-action-btn primary">
                  <ArrowUpIcon class="btn-icon" />
                  Upgrade Storage
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="modal-footer">
        <div class="footer-actions">
          <button @click="close" class="cancel-btn">Cancel</button>
          <button @click="saveChanges" class="save-btn" :disabled="!hasChanges">
            Save Changes
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive, watch } from 'vue'
import {
  UsersIcon,
  EyeIcon,
  UserIcon,
  UsersIcon as UserPlusIcon,
  XMarkIcon,
  CheckIcon,
  XMarkIcon as ClockIcon,
  ExclamationTriangleIcon,
  PencilIcon,
  TrashIcon,
  MagnifyingGlassIcon,
  PlusIcon,
  AdjustmentsHorizontalIcon,
  EllipsisVerticalIcon,
  CogIcon,
  UsersIcon as UserGroupIcon,
  EyeIcon as KeyIcon,
  ComputerDesktopIcon as ServerIcon,
  PhotoIcon,
  ArrowPathIcon,
  ArrowDownTrayIcon,
  ArrowUpIcon
} from '@heroicons/vue/24/outline'

interface TeamMember {
  id: string
  name: string
  email: string
  avatar?: string
  role: 'member' | 'moderator' | 'admin' | 'owner'
  isOnline: boolean
  joinedAt: string
}

interface PendingInvitation {
  id: string
  email: string
  role: string
  sentAt: string
}

interface Permission {
  id: string
  name: string
  description: string
  roles: {
    member: boolean
    moderator: boolean
    admin: boolean
    owner: boolean
  }
}

interface StorageCategory {
  type: string
  name: string
  size: number
  color: string
}

interface StorageStats {
  used: number
  total: number
  breakdown: StorageCategory[]
}

interface Team {
  id: string
  name: string
  description?: string
  avatar?: string
  isPublic?: boolean
  requireApproval?: boolean
  allowGuests?: boolean
}

interface Props {
  show: boolean
  team?: Team
  members?: TeamMember[]
  pendingInvitations?: PendingInvitation[]
  storageStats?: StorageStats
  currentUserId?: string
  userRole?: string
}

interface Emits {
  close: []
  save: [data: any]
  inviteMember: []
  removeMember: [member: TeamMember]
  updateMemberRole: [member: TeamMember]
  resendInvitation: [invitation: PendingInvitation]
  cancelInvitation: [invitation: PendingInvitation]
  cleanupStorage: []
  exportData: []
  upgradeStorage: []
}

const props = withDefaults(defineProps<Props>(), {
  members: () => [],
  pendingInvitations: () => [],
  storageStats: () => ({
    used: 0,
    total: 1000000000, // 1GB default
    breakdown: []
  }),
  userRole: 'member'
})

const emit = defineEmits<Emits>()

const activeTab = ref('general')
const hasChanges = ref(false)

const tabs = [
  { id: 'general', label: 'General', icon: CogIcon },
  { id: 'members', label: 'Members', icon: UserGroupIcon },
  { id: 'permissions', label: 'Permissions', icon: KeyIcon },
  { id: 'storage', label: 'Storage', icon: ServerIcon }
]

const teamForm = reactive({
  name: props.team?.name || '',
  description: props.team?.description || '',
  avatar: props.team?.avatar || '',
  isPublic: props.team?.isPublic || false,
  requireApproval: props.team?.requireApproval || true,
  allowGuests: props.team?.allowGuests || false
})

const permissions = ref<Permission[]>([
  {
    id: 'create_projects',
    name: 'Create Projects',
    description: 'Ability to create new projects',
    roles: { member: false, moderator: true, admin: true, owner: true }
  },
  {
    id: 'delete_projects',
    name: 'Delete Projects',
    description: 'Ability to delete projects',
    roles: { member: false, moderator: false, admin: true, owner: true }
  },
  {
    id: 'manage_members',
    name: 'Manage Members',
    description: 'Invite, remove, and manage team members',
    roles: { member: false, moderator: false, admin: true, owner: true }
  },
  {
    id: 'manage_roles',
    name: 'Manage Roles',
    description: 'Change member roles and permissions',
    roles: { member: false, moderator: false, admin: true, owner: true }
  },
  {
    id: 'access_billing',
    name: 'Access Billing',
    description: 'View and manage billing information',
    roles: { member: false, moderator: false, admin: false, owner: true }
  }
])

const storagePercentage = computed(() => {
  return Math.min((props.storageStats.used / props.storageStats.total) * 100, 100)
})

const canEditPermissions = computed(() => {
  return ['admin', 'owner'].includes(props.userRole)
})

const canManageRoles = (member: TeamMember) => {
  return ['admin', 'owner'].includes(props.userRole) && member.id !== props.currentUserId
}

const canRemoveMember = (member: TeamMember) => {
  return ['admin', 'owner'].includes(props.userRole) && 
         member.id !== props.currentUserId && 
         member.role !== 'owner'
}

// Watch for changes in form
watch(
  () => teamForm, 
  () => {
    hasChanges.value = true
  }, 
  { deep: true }
)

const handleOverlayClick = (event: MouseEvent) => {
  if (event.target === event.currentTarget) {
    close()
  }
}

const close = () => {
  emit('close')
}

const saveChanges = () => {
  emit('save', {
    team: teamForm,
    permissions: permissions.value
  })
  hasChanges.value = false
}

const uploadAvatar = () => {
  // Implementation for avatar upload
  const input = document.createElement('input')
  input.type = 'file'
  input.accept = 'image/*'
  input.onchange = (e) => {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (file) {
      // Handle file upload
      const reader = new FileReader()
      reader.onload = (e) => {
        teamForm.avatar = e.target?.result as string
      }
      reader.readAsDataURL(file)
    }
  }
  input.click()
}

const removeAvatar = () => {
  teamForm.avatar = ''
}

const inviteMember = () => {
  emit('inviteMember')
}

const removeMember = (member: TeamMember) => {
  emit('removeMember', member)
}

const updateMemberRole = (member: TeamMember) => {
  emit('updateMemberRole', member)
}

const resendInvitation = (invitation: PendingInvitation) => {
  emit('resendInvitation', invitation)
}

const cancelInvitation = (invitation: PendingInvitation) => {
  emit('cancelInvitation', invitation)
}

const cleanupStorage = () => {
  emit('cleanupStorage')
}

const exportData = () => {
  emit('exportData')
}

const upgradeStorage = () => {
  emit('upgradeStorage')
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

const formatDate = (dateString: string): string => {
  return new Date(dateString).toLocaleDateString()
}
</script>

<style scoped>
.team-management-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  padding: 20px;
}

.team-management-modal {
  background: rgba(31, 41, 55, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  width: 100%;
  max-width: 900px;
  max-height: 90vh;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.header-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.team-avatar {
  width: 48px;
  height: 48px;
  border-radius: 12px;
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
  width: 24px;
  height: 24px;
  color: #60a5fa;
}

.team-info {
  display: flex;
  flex-direction: column;
}

.team-name {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
  color: white;
}

.team-id {
  margin: 4px 0 0;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.6);
}

.close-btn {
  padding: 8px;
  background: none;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.close-btn:hover {
  background: rgba(255, 255, 255, 0.1);
}

.close-icon {
  width: 24px;
  height: 24px;
  color: rgba(255, 255, 255, 0.7);
}

.modal-content {
  flex: 1;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.tabs-container {
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.tabs {
  display: flex;
  padding: 0 24px;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 16px 20px;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  color: rgba(255, 255, 255, 0.6);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.tab-btn:hover {
  color: rgba(255, 255, 255, 0.8);
}

.tab-btn.active {
  color: #60a5fa;
  border-bottom-color: #60a5fa;
}

.tab-icon {
  width: 18px;
  height: 18px;
}

.tab-content {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}

.tab-panel {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.section {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.section-title {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
  color: white;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-label {
  font-size: 14px;
  font-weight: 500;
  color: rgba(255, 255, 255, 0.9);
}

.form-input,
.form-textarea {
  padding: 12px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 8px;
  color: white;
  font-size: 14px;
  transition: border-color 0.2s ease;
}

.form-input:focus,
.form-textarea:focus {
  outline: none;
  border-color: #60a5fa;
}

.avatar-upload {
  display: flex;
  align-items: center;
  gap: 16px;
}

.current-avatar {
  width: 64px;
  height: 64px;
  border-radius: 12px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.05);
  display: flex;
  align-items: center;
  justify-content: center;
}

.current-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.placeholder-icon {
  width: 24px;
  height: 24px;
  color: rgba(255, 255, 255, 0.4);
}

.upload-controls {
  display: flex;
  gap: 8px;
}

.upload-btn,
.remove-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  background: rgba(59, 130, 246, 0.2);
  border: 1px solid rgba(59, 130, 246, 0.3);
  border-radius: 6px;
  color: #60a5fa;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.upload-btn:hover,
.remove-btn:hover {
  background: rgba(59, 130, 246, 0.3);
}

.remove-btn {
  background: rgba(239, 68, 68, 0.2);
  border-color: rgba(239, 68, 68, 0.3);
  color: #ef4444;
}

.remove-btn:hover {
  background: rgba(239, 68, 68, 0.3);
}

.btn-icon {
  width: 16px;
  height: 16px;
}

.setting-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
}

.setting-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.setting-name {
  font-size: 14px;
  font-weight: 500;
  color: white;
}

.setting-description {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.6);
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 44px;
  height: 24px;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(255, 255, 255, 0.2);
  transition: 0.2s;
  border-radius: 24px;
}

.toggle-slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.2s;
  border-radius: 50%;
}

input:checked + .toggle-slider {
  background-color: #60a5fa;
}

input:checked + .toggle-slider:before {
  transform: translateX(20px);
}

.invite-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  background: rgba(59, 130, 246, 0.2);
  border: 1px solid rgba(59, 130, 246, 0.3);
  border-radius: 8px;
  color: #60a5fa;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.invite-btn:hover {
  background: rgba(59, 130, 246, 0.3);
}

.members-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.member-card {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  transition: background-color 0.2s ease;
}

.member-card:hover {
  background: rgba(255, 255, 255, 0.08);
}

.member-avatar {
  position: relative;
  width: 40px;
  height: 40px;
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
  font-size: 16px;
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
  border: 2px solid rgba(31, 41, 55, 0.95);
  border-radius: 50%;
}

.member-info {
  flex: 1;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4px;
}

.member-name {
  font-size: 14px;
  font-weight: 500;
  color: white;
}

.member-email {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.6);
}

.member-role {
  font-size: 13px;
  color: #60a5fa;
  text-transform: capitalize;
}

.member-joined {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.5);
}

.member-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.role-select {
  padding: 6px 8px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 4px;
  color: white;
  font-size: 12px;
}

.remove-member-btn {
  padding: 6px;
  background: none;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.remove-member-btn:hover {
  background: rgba(239, 68, 68, 0.2);
}

.remove-member-btn:hover .btn-icon {
  color: #ef4444;
}

.empty-state {
  padding: 40px;
  text-align: center;
  color: rgba(255, 255, 255, 0.5);
  font-size: 14px;
}

.invitations-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.invitation-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
}

.invitation-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.invitation-email {
  font-size: 14px;
  color: white;
}

.invitation-role {
  font-size: 12px;
  color: #60a5fa;
  text-transform: capitalize;
}

.invitation-date {
  font-size: 11px;
  color: rgba(255, 255, 255, 0.5);
}

.invitation-actions {
  display: flex;
  gap: 8px;
}

.resend-btn,
.cancel-btn {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 6px 8px;
  border: none;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.resend-btn {
  background: rgba(59, 130, 246, 0.2);
  color: #60a5fa;
}

.resend-btn:hover {
  background: rgba(59, 130, 246, 0.3);
}

.cancel-btn {
  background: rgba(239, 68, 68, 0.2);
  color: #ef4444;
}

.cancel-btn:hover {
  background: rgba(239, 68, 68, 0.3);
}

.permissions-matrix {
  display: grid;
  grid-template-columns: 2fr repeat(4, 1fr);
  gap: 8px;
  align-items: center;
}

.permission-header {
  display: contents;
  font-weight: 600;
  color: rgba(255, 255, 255, 0.9);
  font-size: 13px;
}

.permission-name {
  padding: 12px 0;
}

.role-header {
  padding: 12px 0;
  text-align: center;
}

.permission-row {
  display: contents;
}

.permission-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 12px 0;
}

.permission-title {
  font-size: 14px;
  font-weight: 500;
  color: white;
}

.permission-description {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
}

.permission-cell {
  display: flex;
  justify-content: center;
  padding: 12px 0;
}

.permission-checkbox {
  position: relative;
  display: inline-block;
  width: 18px;
  height: 18px;
}

.permission-checkbox input {
  opacity: 0;
  width: 0;
  height: 0;
}

.checkmark {
  position: absolute;
  top: 0;
  left: 0;
  height: 18px;
  width: 18px;
  background-color: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 3px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.permission-checkbox:hover .checkmark {
  background-color: rgba(255, 255, 255, 0.15);
}

.permission-checkbox input:checked ~ .checkmark {
  background-color: #60a5fa;
  border-color: #60a5fa;
}

.checkmark:after {
  content: "";
  position: absolute;
  display: none;
}

.permission-checkbox input:checked ~ .checkmark:after {
  display: block;
}

.permission-checkbox .checkmark:after {
  left: 6px;
  top: 3px;
  width: 4px;
  height: 8px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.storage-overview {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-bottom: 16px;
}

.storage-stat {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 16px;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  text-align: center;
}

.stat-label {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  font-size: 18px;
  font-weight: 600;
  color: white;
}

.storage-bar {
  width: 100%;
  height: 8px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 20px;
}

.storage-progress {
  height: 100%;
  background: linear-gradient(90deg, #60a5fa, #3b82f6);
  transition: width 0.3s ease;
}

.storage-breakdown {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.storage-category {
  display: flex;
  align-items: center;
  gap: 12px;
}

.category-info {
  flex: 1;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.category-name {
  font-size: 14px;
  color: white;
}

.category-size {
  font-size: 13px;
  color: rgba(255, 255, 255, 0.7);
}

.category-bar {
  width: 100px;
  height: 6px;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 3px;
  overflow: hidden;
}

.category-progress {
  height: 100%;
  transition: width 0.3s ease;
}

.storage-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.storage-action-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  color: white;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.storage-action-btn:hover {
  background: rgba(255, 255, 255, 0.15);
}

.storage-action-btn.primary {
  background: rgba(59, 130, 246, 0.2);
  border-color: rgba(59, 130, 246, 0.3);
  color: #60a5fa;
}

.storage-action-btn.primary:hover {
  background: rgba(59, 130, 246, 0.3);
}

.modal-footer {
  padding: 20px 24px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.cancel-btn,
.save-btn {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.cancel-btn {
  background: rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.8);
}

.cancel-btn:hover {
  background: rgba(255, 255, 255, 0.15);
}

.save-btn {
  background: #60a5fa;
  color: white;
}

.save-btn:hover:not(:disabled) {
  background: #3b82f6;
}

.save-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style> 