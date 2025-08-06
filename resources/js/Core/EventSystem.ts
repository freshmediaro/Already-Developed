// Core Event System for module communication
import type { DesktopEvent, DesktopEventPayload } from './Types';

/** Type definition for event callback functions */
type EventCallback = (payload: DesktopEventPayload) => void;

/**
 * Event System - Central event management for desktop application
 * 
 * This class provides a centralized event system for communication between
 * different modules and components in the desktop application. It supports
 * event subscription, emission, history tracking, and cleanup management.
 * 
 * Key features:
 * - Event subscription and unsubscription
 * - Event emission with payload data
 * - Event history tracking
 * - Error handling for event listeners
 * - Memory leak prevention through cleanup
 * - Type-safe event handling
 * 
 * The event system is used throughout the application for:
 * - Window management events
 * - Application lifecycle events
 * - User interaction events
 * - System state changes
 * - Cross-module communication
 * 
 * @since 1.0.0
 */
class EventSystem {
  /** Map of event types to their registered callback functions */
  private listeners: Map<DesktopEvent, Set<EventCallback>> = new Map();
  
  /** Array of recent event payloads for debugging and analytics */
  private history: DesktopEventPayload[] = [];
  
  /** Maximum number of events to keep in history */
  private maxHistorySize = 100;

  /**
   * Subscribe to an event with a callback function
   * 
   * This method allows components to listen for specific events and
   * receive notifications when those events occur. The callback function
   * will be called with the event payload when the event is emitted.
   * 
   * @param event - The event type to subscribe to
   * @param callback - Function to call when the event occurs
   * @returns Function - Unsubscribe function to remove the listener
   * 
   * @example
   * ```typescript
   * const unsubscribe = eventSystem.on('window:created', (payload) => {
   *   console.log('Window created:', payload.data);
   * });
   * 
   * // Later, to unsubscribe:
   * unsubscribe();
   * ```
   */
  on(event: DesktopEvent, callback: EventCallback): () => void {
    if (!this.listeners.has(event)) {
      this.listeners.set(event, new Set());
    }
    
    this.listeners.get(event)!.add(callback);
    
    // Return unsubscribe function
    return () => {
      this.listeners.get(event)?.delete(callback);
    };
  }

  /**
   * Subscribe to an event only once, automatically unsubscribing after first call
   * 
   * This method is useful for one-time event listeners that should
   * automatically clean up after the first event occurrence.
   * 
   * @param event - The event type to subscribe to
   * @param callback - Function to call when the event occurs (only once)
   * @returns Function - Unsubscribe function (though it will auto-unsubscribe)
   * 
   * @example
   * ```typescript
   * eventSystem.once('app:initialized', (payload) => {
   *   console.log('App initialized, setting up UI');
   * });
   * ```
   */
  once(event: DesktopEvent, callback: EventCallback): () => void {
    const unsubscribe = this.on(event, (payload) => {
      callback(payload);
      unsubscribe();
    });
    
    return unsubscribe;
  }

  /**
   * Emit an event with optional data payload
   * 
   * This method broadcasts an event to all registered listeners.
   * The event payload includes the event type, data, timestamp, and
   * optional source information for debugging.
   * 
   * @param event - The event type to emit
   * @param data - Optional data payload to include with the event
   * @param source - Optional source identifier for debugging
   * 
   * @example
   * ```typescript
   * eventSystem.emit('window:created', {
   *   windowId: 'window-1',
   *   appId: 'calculator'
   * }, 'WindowManager');
   * ```
   */
  emit(event: DesktopEvent, data: unknown, source?: string): void {
    const payload: DesktopEventPayload = {
      type: event,
      data,
      timestamp: Date.now(),
      source,
    };

    // Add to history
    this.addToHistory(payload);

    // Notify listeners
    const listeners = this.listeners.get(event);
    if (listeners) {
      listeners.forEach(callback => {
        try {
          callback(payload);
        } catch (error) {
          if (import.meta.env.VITE_APP_ENV === 'local') {
            console.error(`Error in event listener for ${event}:`, error);
          }
        }
      });
    }
  }

  /**
   * Remove all listeners for a specific event type
   * 
   * This method cleans up all registered listeners for the specified
   * event, preventing memory leaks and ensuring proper cleanup.
   * 
   * @param event - The event type to remove all listeners for
   */
  off(event: DesktopEvent): void {
    this.listeners.delete(event);
  }

  /**
   * Remove all event listeners from the system
   * 
   * This method performs a complete cleanup of all event listeners,
   * useful for application shutdown or when resetting the event system.
   */
  removeAllListeners(): void {
    this.listeners.clear();
  }

  /**
   * Get event history for debugging and analytics
   * 
   * This method returns recent event payloads, optionally filtered
   * by event type and limited by count for performance.
   * 
   * @param event - Optional event type to filter by
   * @param limit - Optional maximum number of events to return
   * @returns Array of recent event payloads
   */
  getHistory(event?: DesktopEvent, limit?: number): DesktopEventPayload[] {
    let history = this.history;
    
    if (event) {
      history = history.filter(payload => payload.type === event);
    }
    
    if (limit) {
      history = history.slice(-limit);
    }
    
    return [...history];
  }

  /**
   * Clear event history
   */
  clearHistory(): void {
    this.history = [];
  }

  /**
   * Get current listeners count for debugging
   */
  getListenersCount(event?: DesktopEvent): number {
    if (event) {
      return this.listeners.get(event)?.size || 0;
    }
    
    let total = 0;
    this.listeners.forEach(listeners => {
      total += listeners.size;
    });
    
    return total;
  }

  private addToHistory(payload: DesktopEventPayload): void {
    this.history.push(payload);
    
    // Maintain history size limit
    if (this.history.length > this.maxHistorySize) {
      this.history = this.history.slice(-this.maxHistorySize);
    }
  }
}

// Create singleton instance
export const eventSystem = new EventSystem();

// Helper functions for common events
export const windowEvents = {
  created: (windowData: any) => eventSystem.emit('window:created', windowData, 'WindowManager'),
  closed: (windowId: string) => eventSystem.emit('window:closed', { windowId }, 'WindowManager'),
  minimized: (windowId: string) => eventSystem.emit('window:minimized', { windowId }, 'WindowManager'),
  maximized: (windowId: string) => eventSystem.emit('window:maximized', { windowId }, 'WindowManager'),
  restored: (windowId: string) => eventSystem.emit('window:restored', { windowId }, 'WindowManager'),
  moved: (windowId: string, position: { x: number; y: number }) => 
    eventSystem.emit('window:moved', { windowId, ...position }, 'WindowManager'),
  resized: (windowId: string, size: { width: number; height: number }) => 
    eventSystem.emit('window:resized', { windowId, ...size }, 'WindowManager'),
};

export const appEvents = {
  installed: (appId: string) => eventSystem.emit('app:installed', { appId }, 'AppStore'),
  uninstalled: (appId: string) => eventSystem.emit('app:uninstalled', { appId }, 'AppStore'),
  launched: (appId: string, windowId: string) => 
    eventSystem.emit('app:launched', { appId, windowId }, 'Desktop'),
};

export const teamEvents = {
  switched: (teamId: number, previousTeamId?: number) => 
    eventSystem.emit('team:switched', { teamId, previousTeamId }, 'TeamManager'),
};

export const notificationEvents = {
  created: (notification: any) => eventSystem.emit('notification:created', notification, 'NotificationManager'),
  dismissed: (notificationId: string) => 
    eventSystem.emit('notification:dismissed', { notificationId }, 'NotificationManager'),
};

export default eventSystem; 