// Notification Swipe Manager - Swipe-to-delete functionality for notifications
import { eventSystem } from './EventSystem';

interface SwipeState {
  startX: number;
  translateX: number;
  isDragging: boolean;
}

class NotificationSwipeManager {
  private swipeStates = new Map<HTMLElement, SwipeState>();
  private readonly SWIPE_THRESHOLD = 100; // Minimum distance for delete action

  constructor() {
    this.initialize();
  }

  private initialize(): void {
    // Listen for notifications being rendered
    eventSystem.on('notifications:rendered', () => {
      this.setupSwipeForCards();
    });
  }

  public setupSwipeForCards(): void {
    console.log('enableNotificationSwipeToDelete called');
    
    // Try regular desktop notifications first
    let notifCards = document.querySelectorAll('.notif-card');
    
    if (notifCards.length === 0) {
      console.log('No notification cards found, trying mobile screen selector');
      // Try mobile notifications screen selector
      notifCards = document.querySelectorAll('#notifications-screen .notif-card');
    }
    
    if (notifCards.length > 0) {
      this.attachSwipeListeners(notifCards);
    } else {
      console.log('No notification cards found at all');
    }
  }

  private attachSwipeListeners(notifCards: NodeListOf<Element>): void {
    notifCards.forEach(card => {
      const cardElement = card as HTMLElement;
      
      // Skip if already has listeners
      if (this.swipeStates.has(cardElement)) {
        return;
      }

      // Create swipe background if it doesn't exist
      let swipeBg = cardElement.querySelector('.notif-swipe-bg') as HTMLElement;
      if (!swipeBg) {
        swipeBg = document.createElement('div');
        swipeBg.className = 'notif-swipe-bg';
        swipeBg.innerHTML = '<i class="fas fa-trash"></i>';
        cardElement.insertBefore(swipeBg, cardElement.firstChild);
      }

      // Initialize swipe state
      this.swipeStates.set(cardElement, {
        startX: 0,
        translateX: 0,
        isDragging: false
      });

      // Touch start
      cardElement.addEventListener('touchstart', (e) => {
        const state = this.swipeStates.get(cardElement);
        if (!state) return;

        state.startX = e.touches[0].clientX;
        state.isDragging = true;
        state.translateX = 0;
        
        // Reset any existing transform
        cardElement.style.transform = '';
        swipeBg.style.opacity = '0';
      });

      // Touch move
      cardElement.addEventListener('touchmove', (e) => {
        const state = this.swipeStates.get(cardElement);
        if (!state || !state.isDragging) return;

        const currentX = e.touches[0].clientX;
        state.translateX = Math.max(0, currentX - state.startX); // Only allow right swipe

        // Apply transform
        cardElement.style.transform = `translateX(${state.translateX}px)`;
        
        // Update background opacity based on swipe distance
        const opacity = Math.min(1, Math.abs(state.translateX) / this.SWIPE_THRESHOLD);
        swipeBg.style.opacity = opacity.toString();
      });

      // Touch end
      cardElement.addEventListener('touchend', (e) => {
        const state = this.swipeStates.get(cardElement);
        if (!state || !state.isDragging) return;

        state.isDragging = false;

        // Calculate final translateX from current position if not set by touchmove
        if (state.translateX === 0 && e.changedTouches.length > 0) {
          const endX = e.changedTouches[0].clientX;
          state.translateX = Math.max(0, endX - state.startX);
        }

        if (state.translateX > this.SWIPE_THRESHOLD) {
          // Delete notification
          this.deleteNotification(cardElement, swipeBg);
        } else {
          // Reset position
          this.resetCardPosition(cardElement, swipeBg);
        }
      });

      // Touch cancel
      cardElement.addEventListener('touchcancel', () => {
        const state = this.swipeStates.get(cardElement);
        if (!state) return;

        state.isDragging = false;
        this.resetCardPosition(cardElement, swipeBg);
      });
    });
  }

  private deleteNotification(cardElement: HTMLElement, swipeBg: HTMLElement): void {
    const notifId = cardElement.getAttribute('data-notif-id');
    
    if (notifId) {
      // Find and remove from notifications array
      const notificationsData = this.getNotificationsFromGlobalState();
      const index = notificationsData.findIndex((n: any) => n.id.toString() === notifId);
      
      if (index !== -1) {
        notificationsData.splice(index, 1);
        console.log('Mobile swipe: Deleted notification', notifId, 'remaining:', notificationsData.length);
        
        // Update global state
        this.updateNotificationsInGlobalState(notificationsData);
        
        // Animate deletion
        cardElement.style.transform = `translateX(100%)`;
        cardElement.style.opacity = '0';
        cardElement.style.transition = 'transform 0.3s ease-out, opacity 0.3s ease-out';
        
        setTimeout(() => {
          if (cardElement.parentNode) {
            cardElement.parentNode.removeChild(cardElement);
          }
          this.swipeStates.delete(cardElement);
          
          // Re-render notifications
          this.triggerNotificationsUpdate();
          console.log(`Mobile swipe: After DOM cleanup, notifications.length = ${notificationsData.length}`);
        }, 300);
      } else {
        console.log('Mobile swipe: Could not find notification with ID', notifId);
        this.resetCardPosition(cardElement, swipeBg);
      }
    } else {
      console.log('Mobile swipe: No notifId found on card');
      this.resetCardPosition(cardElement, swipeBg);
    }
  }

  private resetCardPosition(cardElement: HTMLElement, swipeBg: HTMLElement): void {
    cardElement.style.transform = '';
    cardElement.style.transition = 'transform 0.3s ease-out';
    swipeBg.style.opacity = '0';
    
    // Clear transition after animation
    setTimeout(() => {
      cardElement.style.transition = '';
    }, 300);
  }

  private getNotificationsFromGlobalState(): any[] {
    // This would integrate with your notification system
    // For now, return an empty array
    return [];
  }

  private updateNotificationsInGlobalState(notifications: any[]): void {
    // This would update your global notification state
    // Emit event to notify other components
    eventSystem.emit('notifications:updated', { notifications });
  }

  private triggerNotificationsUpdate(): void {
    // Trigger a re-render of notifications
    eventSystem.emit('notifications:refresh', {});
  }

  public cleanup(): void {
    this.swipeStates.clear();
  }
}

// Create singleton instance
export const notificationSwipeManager = new NotificationSwipeManager();
export default notificationSwipeManager; 