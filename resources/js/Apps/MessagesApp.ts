// Messages Application - Real-time messaging and chat
import { BaseApp, type AppContext } from './BaseApp';
import type { App } from '../Core/Types';
import { eventSystem } from '../Core/EventSystem';
import { apiService } from '../Tenant/ApiService';

interface MessageThread {
  id: string;
  name: string;
  participants: string[];
  lastMessage?: Message;
  unreadCount: number;
  isGroup: boolean;
  avatar?: string;
  isOnline?: boolean;
  lastSeen?: Date;
}

interface Message {
  id: string;
  threadId: string;
  senderId: string;
  senderName: string;
  content: string;
  timestamp: Date;
  type: 'text' | 'image' | 'file' | 'system';
  status: 'sending' | 'sent' | 'delivered' | 'read';
  attachments?: MessageAttachment[];
}

interface MessageAttachment {
  id: string;
  name: string;
  size: number;
  type: string;
  url: string;
}

interface TypingIndicator {
  userId: string;
  userName: string;
  threadId: string;
}

export class MessagesApp extends BaseApp {
  private threads: MessageThread[] = [];
  private messages: Map<string, Message[]> = new Map();
  private activeThreadId: string | null = null;
  private typingUsers: TypingIndicator[] = [];
  private searchQuery = '';
  private isConnected = false;
  private reconnectAttempts = 0;
  private maxReconnectAttempts = 5;

  constructor() {
    const appInfo: App = {
      id: 'messages',
      name: 'Messages',
      icon: 'fas fa-comments',
      iconType: 'fontawesome',
      iconBackground: 'green-icon',
      component: 'MessagesApp',
      category: 'communication',
      permissions: ['read', 'write'],
      installed: true,
      system: false,
      teamScoped: true,
      version: '1.0.0',
      description: 'Real-time messaging and chat application',
    };

    super('messages', appInfo);
  }

  async onMount(context: AppContext): Promise<void> {
    await this.loadThreads();
    await this.setupWebSocketConnection();
    this.renderThreadList(context.windowElement);
    this.setupEventHandlers(context.windowElement);
    this.setupMobileHandlers(context.windowElement);
  }

  onUnmount(context: AppContext): void {
    this.disconnectWebSocket();
    this.stopTypingIndicator();
  }

  private async loadThreads(): Promise<void> {
    try {
      const response = await apiService.get('/api/messages/threads');
      this.threads = response.data.threads;
      
      // Load recent messages for each thread
      for (const thread of this.threads) {
        await this.loadThreadMessages(thread.id, 50);
      }
    } catch (error) {
      console.error('Failed to load message threads:', error);
      this.showError('Failed to load conversations');
    }
  }

  private async loadThreadMessages(threadId: string, limit = 50): Promise<void> {
    try {
      const response = await apiService.get(`/api/messages/threads/${threadId}/messages`, {
        params: {
          limit,
          before: this.getOldestMessageId(threadId)
        }
      });
      
      const existingMessages = this.messages.get(threadId) || [];
      const newMessages = response.data.messages;
      
      this.messages.set(threadId, [...newMessages.reverse(), ...existingMessages]);
    } catch (error) {
      console.error(`Failed to load messages for thread ${threadId}:`, error);
    }
  }

  private getOldestMessageId(threadId: string): string | undefined {
    const messages = this.messages.get(threadId);
    return messages && messages.length > 0 ? messages[0].id : undefined;
  }

  private async setupWebSocketConnection(): Promise<void> {
    try {
      // Initialize WebSocket connection for real-time messaging
      const wsUrl = `${window.location.protocol === 'https:' ? 'wss:' : 'ws:'}//${window.location.host}/ws/messages`;
      
      eventSystem.emit('websocket:connect', {
        url: wsUrl,
        channels: ['messages', 'typing', 'presence'],
        onMessage: this.handleWebSocketMessage.bind(this),
        onConnect: this.handleWebSocketConnect.bind(this),
        onDisconnect: this.handleWebSocketDisconnect.bind(this)
      });
    } catch (error) {
      console.error('Failed to setup WebSocket connection:', error);
      this.scheduleReconnect();
    }
  }

  private handleWebSocketMessage(data: any): void {
    switch (data.type) {
      case 'new_message':
        this.handleNewMessage(data.message);
        break;
      case 'message_status':
        this.updateMessageStatus(data.messageId, data.status);
        break;
      case 'typing_start':
        this.handleTypingStart(data);
        break;
      case 'typing_stop':
        this.handleTypingStop(data);
        break;
      case 'thread_updated':
        this.handleThreadUpdate(data.thread);
        break;
      case 'user_presence':
        this.updateUserPresence(data.userId, data.isOnline, data.lastSeen);
        break;
    }
  }

  private handleNewMessage(message: Message): void {
    const threadMessages = this.messages.get(message.threadId) || [];
    threadMessages.push(message);
    this.messages.set(message.threadId, threadMessages);

    // Update thread last message
    const thread = this.threads.find(t => t.id === message.threadId);
    if (thread) {
      thread.lastMessage = message;
      if (message.threadId !== this.activeThreadId) {
        thread.unreadCount++;
      }
    }

    this.rerenderActiveChat();
    this.rerenderThreadList();
    this.playNotificationSound();
  }

  private renderThreadList(windowElement: HTMLElement): void {
    const sidebar = windowElement.querySelector('.app-sidebar') as HTMLElement;
    if (!sidebar) return;

    const filteredThreads = this.threads.filter(thread =>
      this.searchQuery === '' || 
      thread.name.toLowerCase().includes(this.searchQuery.toLowerCase())
    );

    sidebar.innerHTML = `
      <div class="messages-sidebar">
        <div class="messages-header">
          <h2 class="messages-title">Messages</h2>
          <button class="new-message-btn" title="New Message">
            <i class="fas fa-edit"></i>
          </button>
        </div>
        
        <div class="search-container">
          <div class="search-input-wrapper">
            <input type="text" placeholder="Search conversations..." 
                   class="search-input search-input-full" value="${this.searchQuery}">
            <i class="fas fa-search search-icon"></i>
          </div>
        </div>

        <div class="thread-list">
          ${filteredThreads.map(thread => this.renderThreadItem(thread)).join('')}
        </div>
      </div>
    `;

    this.setupSidebarHandlers(sidebar);
  }

  private renderThreadItem(thread: MessageThread): string {
    const isActive = thread.id === this.activeThreadId;
    const lastMessageTime = thread.lastMessage 
      ? this.formatTime(thread.lastMessage.timestamp)
      : '';
    
    const onlineIndicator = thread.isOnline ? 
      '<div class="online-indicator"></div>' : '';

    return `
      <div class="thread-item ${isActive ? 'active' : ''}" data-thread-id="${thread.id}">
        <div class="thread-avatar">
          ${thread.avatar ? 
            `<img src="${thread.avatar}" alt="${thread.name}" class="avatar-img">` :
            `<div class="avatar-placeholder">${thread.name.charAt(0).toUpperCase()}</div>`
          }
          ${onlineIndicator}
        </div>
        
        <div class="thread-content">
          <div class="thread-header">
            <span class="thread-name">${thread.name}</span>
            <span class="thread-time">${lastMessageTime}</span>
          </div>
          
          <div class="thread-preview">
            <span class="last-message">
              ${thread.lastMessage ? this.truncateMessage(thread.lastMessage.content) : 'No messages yet'}
            </span>
            ${thread.unreadCount > 0 ? `<span class="unread-badge">${thread.unreadCount}</span>` : ''}
          </div>
        </div>
      </div>
    `;
  }

  private renderChatView(windowElement: HTMLElement, threadId: string): void {
    const mainContent = windowElement.querySelector('.app-main-content') as HTMLElement;
    if (!mainContent) return;

    const thread = this.threads.find(t => t.id === threadId);
    if (!thread) return;

    const messages = this.messages.get(threadId) || [];
    const typingIndicator = this.getTypingIndicator(threadId);

    mainContent.innerHTML = `
      <div class="chat-container">
        <div class="chat-header">
          <button class="back-btn mobile-only">
            <i class="fas fa-arrow-left"></i>
          </button>
          
          <div class="chat-participant-info">
            <div class="participant-avatar">
              ${thread.avatar ? 
                `<img src="${thread.avatar}" alt="${thread.name}" class="avatar-img">` :
                `<div class="avatar-placeholder">${thread.name.charAt(0).toUpperCase()}</div>`
              }
            </div>
            
            <div class="participant-details">
              <h3 class="participant-name">${thread.name}</h3>
              <p class="participant-status">
                ${thread.isOnline ? 'Online' : 
                  thread.lastSeen ? `Last seen ${this.formatTime(thread.lastSeen)}` : 'Offline'
                }
              </p>
            </div>
          </div>

          <div class="chat-actions">
            <button class="action-btn" title="Call">
              <i class="fas fa-phone"></i>
            </button>
            <button class="action-btn" title="Video Call">
              <i class="fas fa-video"></i>
            </button>
            <button class="action-btn" title="Thread Info">
              <i class="fas fa-info-circle"></i>
            </button>
          </div>
        </div>

        <div class="messages-container" id="messages-container">
          <div class="messages-scroll">
            ${messages.map(message => this.renderMessage(message)).join('')}
            ${typingIndicator}
          </div>
        </div>

        <div class="message-composer">
          <div class="composer-toolbar">
            <button class="composer-btn" title="Attach File">
              <i class="fas fa-paperclip"></i>
            </button>
            <button class="composer-btn" title="Emoji">
              <i class="fas fa-smile"></i>
            </button>
          </div>
          
          <div class="composer-input-container">
            <textarea class="message-input" placeholder="Type a message..." 
                      rows="1" id="message-input"></textarea>
            <button class="send-btn" id="send-btn" disabled>
              <i class="fas fa-paper-plane"></i>
            </button>
          </div>
        </div>
      </div>
    `;

    this.setupChatHandlers(mainContent, threadId);
    this.scrollToBottom();
    this.markThreadAsRead(threadId);
  }

  private renderMessage(message: Message): string {
    const isOwn = message.senderId === this.getCurrentUserIdString();
    const statusIcon = this.getMessageStatusIcon(message.status);
    
    return `
      <div class="message ${isOwn ? 'own' : 'other'}" data-message-id="${message.id}">
        ${!isOwn ? `
          <div class="message-avatar">
            <div class="avatar-placeholder">${message.senderName.charAt(0).toUpperCase()}</div>
          </div>
        ` : ''}
        
        <div class="message-content">
          ${!isOwn ? `<div class="message-sender">${message.senderName}</div>` : ''}
          
          <div class="message-bubble">
            ${message.content}
            ${message.attachments ? this.renderAttachments(message.attachments) : ''}
          </div>
          
          <div class="message-meta">
            <span class="message-time">${this.formatTime(message.timestamp)}</span>
            ${isOwn ? `<span class="message-status">${statusIcon}</span>` : ''}
          </div>
        </div>
      </div>
    `;
  }

  private setupEventHandlers(windowElement: HTMLElement): void {
    // Global message events
    eventSystem.on('message:send', this.handleSendMessage.bind(this));
    eventSystem.on('message:typing', this.handleTyping.bind(this));
    eventSystem.on('thread:select', this.handleThreadSelect.bind(this));
  }

  private setupSidebarHandlers(sidebar: HTMLElement): void {
    // Search functionality
    const searchInput = sidebar.querySelector('.search-input') as HTMLInputElement;
    searchInput?.addEventListener('input', (e) => {
      this.searchQuery = (e.target as HTMLInputElement).value;
      this.rerenderThreadList();
    });

    // Thread selection
    sidebar.addEventListener('click', (e) => {
      const threadItem = (e.target as HTMLElement).closest('.thread-item');
      if (threadItem) {
        const threadId = threadItem.getAttribute('data-thread-id');
        if (threadId) {
          this.selectThread(threadId);
        }
      }
    });

    // New message button
    const newMessageBtn = sidebar.querySelector('.new-message-btn');
    newMessageBtn?.addEventListener('click', () => {
      this.showNewMessageDialog();
    });
  }

  private setupChatHandlers(mainContent: HTMLElement, threadId: string): void {
    const messageInput = mainContent.querySelector('#message-input') as HTMLTextAreaElement;
    const sendBtn = mainContent.querySelector('#send-btn') as HTMLButtonElement;

    // Auto-resize textarea
    messageInput?.addEventListener('input', (e) => {
      const target = e.target as HTMLTextAreaElement;
      target.style.height = 'auto';
      target.style.height = target.scrollHeight + 'px';
      
      sendBtn.disabled = !target.value.trim();
      
      // Typing indicator
      this.sendTypingIndicator(threadId);
    });

    // Send message on Enter (but not Shift+Enter)
    messageInput?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        this.sendMessage(threadId, messageInput.value.trim());
      }
    });

    // Send button click
    sendBtn?.addEventListener('click', () => {
      this.sendMessage(threadId, messageInput.value.trim());
    });

    // Back button for mobile
    const backBtn = mainContent.querySelector('.back-btn');
    backBtn?.addEventListener('click', () => {
      this.showThreadList();
    });
  }

  private async sendMessage(threadId: string, content: string): Promise<void> {
    if (!content.trim()) return;

    const messageInput = document.querySelector('#message-input') as HTMLTextAreaElement;
    if (messageInput) {
      messageInput.value = '';
      messageInput.style.height = 'auto';
    }

    const tempMessage: Message = {
      id: `temp-${Date.now()}`,
      threadId,
      senderId: this.getCurrentUserIdString(),
      senderName: 'You',
      content,
      timestamp: new Date(),
      type: 'text',
      status: 'sending'
    };

    // Add to UI immediately
    const threadMessages = this.messages.get(threadId) || [];
    threadMessages.push(tempMessage);
    this.messages.set(threadId, threadMessages);
    this.rerenderActiveChat();

    try {
      const response = await apiService.post(`/api/messages/threads/${threadId}/messages`, {
        content,
        type: 'text'
      });

      // Replace temp message with real one
      const realMessage = response.data.message;
      const messages = this.messages.get(threadId) || [];
      const tempIndex = messages.findIndex(m => m.id === tempMessage.id);
      if (tempIndex !== -1) {
        messages[tempIndex] = realMessage;
        this.messages.set(threadId, messages);
        this.rerenderActiveChat();
      }
    } catch (error) {
      console.error('Failed to send message:', error);
      this.showError('Failed to send message');
      
      // Mark message as failed
      tempMessage.status = 'sent'; // We'll show error state differently
      this.rerenderActiveChat();
    }
  }

  private selectThread(threadId: string): void {
    this.activeThreadId = threadId;
    this.renderChatView(this.context?.windowElement!, threadId);
    
    // Update sidebar selection
    this.rerenderThreadList();
    
    // Show chat on mobile
    this.showChatView();
  }

  private getCurrentUserIdString(): string {
    // Get user ID from context instead of private method
    return String(this.context?.userId || 1);
  }

  private formatTime(date: Date): string {
    const now = new Date();
    const messageDate = new Date(date);
    
    if (now.toDateString() === messageDate.toDateString()) {
      return messageDate.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
      });
    } else {
      return messageDate.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric' 
      });
    }
  }

  private truncateMessage(content: string, maxLength = 50): string {
    return content.length > maxLength ? 
      content.substring(0, maxLength) + '...' : 
      content;
  }

  private getMessageStatusIcon(status: string): string {
    switch (status) {
      case 'sending': return '<i class="fas fa-clock"></i>';
      case 'sent': return '<i class="fas fa-check"></i>';
      case 'delivered': return '<i class="fas fa-check-double"></i>';
      case 'read': return '<i class="fas fa-check-double message-status-read"></i>';
      default: return '';
    }
  }

  private setupMobileHandlers(windowElement: HTMLElement): void {
    // Handle mobile responsive behavior
    const updateMobileView = () => {
      const isMobile = window.innerWidth < 768;
      windowElement.classList.toggle('mobile-view', isMobile);
      
      if (isMobile && this.activeThreadId) {
        this.showChatView();
      } else if (!isMobile) {
        this.showBothViews();
      }
    };

    window.addEventListener('resize', updateMobileView);
    updateMobileView();
  }

  private showChatView(): void {
    const windowElement = this.context?.windowElement;
    if (windowElement) {
      windowElement.classList.add('show-chat');
      windowElement.classList.remove('show-threads');
    }
  }

  private showThreadList(): void {
    const windowElement = this.context?.windowElement;
    if (windowElement) {
      windowElement.classList.add('show-threads');
      windowElement.classList.remove('show-chat');
    }
  }

  private showBothViews(): void {
    const windowElement = this.context?.windowElement;
    if (windowElement) {
      windowElement.classList.remove('show-chat', 'show-threads');
    }
  }

  private rerenderThreadList(): void {
    if (this.context?.windowElement) {
      this.renderThreadList(this.context.windowElement);
    }
  }

  private rerenderActiveChat(): void {
    if (this.context?.windowElement && this.activeThreadId) {
      this.renderChatView(this.context.windowElement, this.activeThreadId);
    }
  }

  private scrollToBottom(): void {
    setTimeout(() => {
      const messagesContainer = document.querySelector('#messages-container .messages-scroll');
      if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
      }
    }, 100);
  }

  private playNotificationSound(): void {
    eventSystem.emit('audio:play', { type: 'message' });
  }



  // Stub methods for features to be implemented
  private async setupWebSocketReconnection(): Promise<void> { /* Implementation needed */ }
  private disconnectWebSocket(): void { /* Implementation needed */ }
  private sendTypingIndicator(threadId: string): void { /* Implementation needed */ }
  private stopTypingIndicator(): void { /* Implementation needed */ }
  private handleWebSocketConnect(): void { /* Implementation needed */ }
  private handleWebSocketDisconnect(): void { /* Implementation needed */ }
  private scheduleReconnect(): void { /* Implementation needed */ }
  private updateMessageStatus(messageId: string, status: string): void { /* Implementation needed */ }
  private handleTypingStart(data: any): void { /* Implementation needed */ }
  private handleTypingStop(data: any): void { /* Implementation needed */ }
  private handleThreadUpdate(thread: MessageThread): void { /* Implementation needed */ }
  private updateUserPresence(userId: string, isOnline: boolean, lastSeen?: Date): void { /* Implementation needed */ }
  private getTypingIndicator(threadId: string): string { return ''; }
  private renderAttachments(attachments: MessageAttachment[]): string { return ''; }
  private markThreadAsRead(threadId: string): void { /* Implementation needed */ }
  private showNewMessageDialog(): void { /* Implementation needed */ }
  private handleSendMessage(data: any): void { /* Implementation needed */ }
  private handleTyping(data: any): void { /* Implementation needed */ }
  private handleThreadSelect(data: any): void { /* Implementation needed */ }
}

export default MessagesApp; 