// Core TypeScript types for the OS-style interface

export interface Window {
  id: string;
  appId: string;
  title: string;
  x: number;
  y: number;
  width: number;
  height: number;
  minimized: boolean;
  maximized: boolean;
  zIndex: number;
  teamId?: string;
  data: unknown;
  created_at: string;
  updated_at: string;
}

export interface App {
  id: string;
  name: string;
  icon: string;
  iconType?: 'image' | 'css' | 'fontawesome'; // Type of icon
  iconBackground?: string; // Background color class or hex
  iconImage?: string; // Image URL for icon
  component: string;
  category: string;
  permissions: string[];
  installed: boolean;
  system: boolean;
  teamScoped: boolean;
  version: string;
  description?: string;
  author?: string;
  price?: number;
}

export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at?: string;
  current_team_id?: number;
  profile_photo_path?: string;
  profile_photo_url?: string;
  created_at: string;
  updated_at: string;
}

export interface Team {
  id: number;
  name: string;
  user_id: number;
  personal_team: boolean;
  created_at: string;
  updated_at: string;
  owner?: User;
  users?: User[];
  team_invitations?: TeamInvitation[];
}

export interface TeamInvitation {
  id: number;
  team_id: number;
  email: string;
  role?: string;
  created_at: string;
  updated_at: string;
}

export interface Tenant {
  id: string;
  name: string;
  domain: string;
  database: string;
  created_at: string;
  updated_at: string;
}

export interface UserPreferences {
  id: number;
  user_id: number;
  team_id?: number;
  desktop_wallpaper: string;
  theme: 'light' | 'dark' | 'system';
  notification_settings: NotificationSettings;
  volume_settings: VolumeSettings;
  privacy_settings: PrivacySettings;
  created_at: string;
  updated_at: string;
}

export interface NotificationSettings {
  desktop_enabled: boolean;
  email_enabled: boolean;
  push_enabled: boolean;
  sound_enabled: boolean;
  stacking_mode: 'one' | 'three' | 'all';
}

export interface VolumeSettings {
  master_volume: number;
  muted: boolean;
  app_volumes: Record<string, number>;
}

export interface PrivacySettings {
  show_online_status: boolean;
  allow_team_notifications: boolean;
  share_usage_data: boolean;
}

export interface ApiResponse<T = any> {
  data: T;
  message?: string;
  errors?: Record<string, string[]>;
  status: number;
}

export interface PaginatedResponse<T = any> extends ApiResponse<T[]> {
  meta: {
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
  };
  links: {
    first: string;
    last: string;
    prev?: string;
    next?: string;
  };
}

export interface WindowPosition {
  x: number;
  y: number;
  width: number;
  height: number;
}

export interface AppConfig {
  id: string;
  settings: Record<string, any>;
  permissions: string[];
  teamAccess: boolean;
}

// Event types for the event system
export type DesktopEvent = 
  | 'window:created'
  | 'window:closed'
  | 'window:minimized'
  | 'window:maximized'
  | 'window:restored'
  | 'window:moved'
  | 'window:resized'
  | 'window:create'
  | 'app:installed'
  | 'app:uninstalled'
  | 'app:launched'
  | 'team:switched'
  | 'notification:created'
  | 'notification:dismissed'
  | 'notification:show'
  | 'notifications:rendered'
  | 'settings:section-changed'
  | 'settings:theme-changed'
  | 'settings:accent-color-changed'
  | 'settings:popout-behavior-changed'
  | 'file-explorer:selection-changed'
  | 'file-explorer:folder-changed'
  | 'file-explorer:view-mode-changed'
  | 'file-explorer:file-open'
  | 'context-menu:show'
  | 'context-menu:hide'
  | 'volume:changed'
  | 'theme:changed'
  | 'orientation:changed'
  | 'desktop:initialized'
  | 'app:launch'
  | 'app-store:app-detail-closed'
  | 'websocket:connect'
  | 'message:send'
  | 'message:typing'
  | 'thread:select'
  | 'site-builder:edit-page'
  | 'site-builder:duplicate-page'
  | 'site-builder:page-deleted'
  | 'site-builder:preview-page'
  | 'site-builder:page-published'
  | 'site-builder:auto-save'
  | 'mobile:state-changed'
  | 'notifications:updated'
  | 'notifications:refresh'
  | 'music:progress:changed'
  | 'music:playback:toggle'
  | 'music:track:previous'
  | 'music:track:next'
  | 'music:repeat:toggle'
  | 'music:shuffle:toggle'
  | 'desktop:selection-changed'
  | 'contextmenu:hide'
  | 'app-store:app-detail-opened'
  | 'app-store:app-detail-closed'
  | 'app-store:open-app'
  | 'app-store:app-installed'
  | 'app-store:payment-dialog'
  | 'email:selected'
  | 'email:action'
  | 'email:compose'
  | 'email:delete'
  | 'email:move'
  | 'email:spam'
  | 'audio:play'
  | 'site-builder:page-actions-toggled'
  | 'site-builder:page-settings-toggled'
  | 'site-builder:page-action'
  | 'site-builder:setting-changed'
  | 'site-builder:toggle-changed'
  | 'site-builder:date-changed'
  | 'site-builder:image-uploaded'
  | 'site-builder:pages-loaded'
  | 'global:volume:changed'
  | 'global:notifications:mute:changed'
  | 'global:notifications:stacking:changed'
  | 'global:notifications:badge:changed'
  | 'global:theme:changed'
  | 'global:layout:changed'
  | 'global:taskbar:position:changed'
  | 'global:taskbar:autohide:changed'
  | 'global:taskbar:style:changed'
  | 'global:taskbar:searchbar:changed'
  | 'global:taskbar:icon:changed'
  | 'global:desktop:show'
  | 'global:orientation:changed'
  | 'global:interface:mode:changed'
  | 'global:widgets:visibility:changed'
  | 'global:fullscreen:changed'
  | 'global:state:reset'
  | 'window:popout'
  | 'user:logout'
  | 'alert:dialog:confirmed'
  | 'context-menu:action'
  | 'context-menu:show'
  | 'context-menu:hide'
  | 'clipboard:selection-changed'
  | 'clipboard:copied'
  | 'clipboard:cut'
  | 'clipboard:pasted'
  | 'clipboard:select-all'
  | 'clipboard:cleared'
  | 'global-search:show'
  | 'global-search:hide'
  | 'settings:open'
  | 'file-explorer:open'
  | 'volume:panel:show'
  | 'volume:panel:hide'
  | 'widgets:toggle'
  | 'widget:update'
  | 'widget:refreshed';

export interface DesktopEventPayload {
  type: DesktopEvent;
  data: any;
  timestamp: number;
  source?: string;
}

// Hook types for Vue composables
export interface UseWindowOptions {
  minWidth?: number;
  minHeight?: number;
  maxWidth?: number;
  maxHeight?: number;
  resizable?: boolean;
  draggable?: boolean;
  centered?: boolean;
}

export interface UseApiOptions {
  tenant?: string;
  team?: number;
  retries?: number;
  timeout?: number;
}

// ClipboardService interface
export interface ClipboardService {
  hasTextSelection: (target?: HTMLElement) => boolean;
  isTextEditable: (target: HTMLElement) => boolean;
  getTextContextMenuItems: (target: HTMLElement) => ContextMenuItem[];
  delete: (target?: HTMLElement) => void;
  copy: (target?: HTMLElement) => Promise<boolean>;
  cut: (target?: HTMLElement) => Promise<boolean>;
  paste: (target?: HTMLElement) => Promise<boolean>;
  selectAll: (target?: HTMLElement) => boolean;
}

// Context menu types
export interface ContextMenuItem {
  label?: string;
  action?: string;
  icon?: string;
  disabled?: boolean;
  type?: 'separator';
  separator?: boolean;
  submenu?: ContextMenuItem[];
  text?: string; // Alternative to label for compatibility
}

// Mobile Swipe Manager interface
export interface MobileSwipeManager {
  initialize: () => void;
  isMobile: () => boolean;
  getCurrentState: () => string;
  goToScreen: (screen: string) => void;
  isInTransition: () => boolean;
  cleanup: () => void;
} 