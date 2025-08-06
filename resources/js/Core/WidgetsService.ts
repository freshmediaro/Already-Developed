import { eventSystem } from './EventSystem';

interface Widget {
  id: string;
  title: string;
  type: 'weather' | 'calendar' | 'clock' | 'news' | 'stocks' | 'notes' | 'music' | 'system';
  size: 'small' | 'medium' | 'large';
  data?: any;
  enabled: boolean;
  position: number;
}

interface WeatherData {
  location: string;
  temperature: number;
  condition: string;
  icon: string;
  humidity: number;
  windSpeed: number;
  forecast: Array<{
    day: string;
    high: number;
    low: number;
    icon: string;
  }>;
}

class WidgetsService {
  private isVisible = false;
  private widgetsScreen: HTMLElement | null = null;
  private widgets: Widget[] = [];

  constructor() {
    this.init();
    this.loadWidgets();
    this.setupEventListeners();
  }

  private init() {
    this.findOrCreateWidgetsScreen();
    this.renderWidgets();
  }

  private findOrCreateWidgetsScreen() {
    this.widgetsScreen = document.getElementById('widgets-screen');
    
    if (!this.widgetsScreen) {
      this.widgetsScreen = document.createElement('div');
      this.widgetsScreen.id = 'widgets-screen';
      this.widgetsScreen.className = 'widgets-hidden';
      
      // Position on the right side of the screen
      this.widgetsScreen.style.cssText = `
        position: absolute;
        top: 0;
        right: 0;
        width: 340px;
        height: 100%;
        box-sizing: border-box;
        padding: 15px 15px 65px 15px;
        pointer-events: auto;
        user-select: none;
        overflow-y: auto;
        overflow-x: hidden;
        scrollbar-width: thin;
        z-index: 1000;
      `;
      
      // Find the main content area to append to
      const mainContent = document.querySelector('.main-content-area') || document.body;
      mainContent.appendChild(this.widgetsScreen);
    }
  }

  private setupEventListeners() {
    // Listen for widgets toggle events
    eventSystem.on('widgets:toggle', () => {
      this.toggle();
    });

    // Listen for widget updates
    eventSystem.on('widget:update', (data: any) => {
      if (data.widgetId) {
        this.updateWidget(data.widgetId, data.data);
      }
    });

    // Handle widget interactions
    if (this.widgetsScreen) {
      this.widgetsScreen.addEventListener('click', (e) => {
        const target = e.target as HTMLElement;
        const widget = target.closest('.widget');
        const button = target.closest('.widget-btn');
        
        if (button && widget) {
          const widgetId = widget.getAttribute('data-widget-id');
          const action = button.getAttribute('data-action');
          
          if (widgetId && action) {
            this.handleWidgetAction(widgetId, action);
          }
        }
      });
    }
  }

  private loadWidgets() {
    // Load default widgets
    this.widgets = [
      {
        id: 'weather',
        title: 'Weather',
        type: 'weather',
        size: 'large',
        enabled: true,
        position: 0,
        data: {
          location: 'New York',
          temperature: 72,
          condition: 'Partly Cloudy',
          icon: 'fas fa-cloud-sun',
          humidity: 65,
          windSpeed: 8,
          forecast: [
            { day: 'Today', high: 75, low: 62, icon: 'fas fa-cloud-sun' },
            { day: 'Tomorrow', high: 78, low: 65, icon: 'fas fa-sun' },
            { day: 'Wed', high: 73, low: 60, icon: 'fas fa-cloud-rain' }
          ]
        }
      },
      {
        id: 'calendar',
        title: 'Calendar',
        type: 'calendar',
        size: 'medium',
        enabled: true,
        position: 1,
        data: {
          currentDate: new Date(),
          events: [
            { time: '9:00 AM', title: 'Team Meeting', color: '#007ACC' },
            { time: '2:00 PM', title: 'Client Call', color: '#FF6B6B' },
            { time: '4:30 PM', title: 'Code Review', color: '#4ECB71' }
          ]
        }
      },
      {
        id: 'clock',
        title: 'World Clock',
        type: 'clock',
        size: 'small',
        enabled: true,
        position: 2,
        data: {
          zones: [
            { name: 'New York', timezone: 'America/New_York' },
            { name: 'London', timezone: 'Europe/London' },
            { name: 'Tokyo', timezone: 'Asia/Tokyo' }
          ]
        }
      },
      {
        id: 'stocks',
        title: 'Stocks',
        type: 'stocks',
        size: 'medium',
        enabled: true,
        position: 3,
        data: {
          watchlist: [
            { symbol: 'AAPL', name: 'Apple Inc.', price: 175.43, change: +2.15, changePercent: 1.24 },
            { symbol: 'MSFT', name: 'Microsoft Corp.', price: 332.89, change: -1.23, changePercent: -0.37 },
            { symbol: 'GOOGL', name: 'Alphabet Inc.', price: 2847.63, change: +15.47, changePercent: 0.55 }
          ]
        }
      },
      {
        id: 'news',
        title: 'News',
        type: 'news',
        size: 'large',
        enabled: true,
        position: 4,
        data: {
          headlines: [
            { title: 'Tech Giants Report Strong Q3 Earnings', source: 'Tech News', time: '2h ago' },
            { title: 'New AI Breakthrough in Healthcare', source: 'Science Today', time: '4h ago' },
            { title: 'Global Markets Show Positive Trends', source: 'Financial Times', time: '6h ago' }
          ]
        }
      },
      {
        id: 'system',
        title: 'System Info',
        type: 'system',
        size: 'small',
        enabled: true,
        position: 5,
        data: {
          cpu: 45,
          memory: 67,
          storage: 82,
          network: 'Connected'
        }
      }
    ];

    // Try to load saved widget preferences
    try {
      const saved = localStorage.getItem('widgets-config');
      if (saved) {
        const config = JSON.parse(saved);
        this.widgets = this.widgets.map(widget => {
          const savedWidget = config.find((w: Widget) => w.id === widget.id);
          return savedWidget ? { ...widget, ...savedWidget } : widget;
        });
      }
    } catch (err) {
      console.warn('Failed to load widget configuration:', err);
    }

    // Sort widgets by position
    this.widgets.sort((a, b) => a.position - b.position);
  }

  private renderWidgets() {
    if (!this.widgetsScreen) return;

    const enabledWidgets = this.widgets.filter(w => w.enabled);
    
    this.widgetsScreen.innerHTML = enabledWidgets.map(widget => {
      return this.renderWidget(widget);
    }).join('');

    // Start any timers for widgets that need them
    this.startWidgetTimers();
  }

  private renderWidget(widget: Widget): string {
    switch (widget.type) {
      case 'weather':
        return this.renderWeatherWidget(widget);
      case 'calendar':
        return this.renderCalendarWidget(widget);
      case 'clock':
        return this.renderClockWidget(widget);
      case 'stocks':
        return this.renderStocksWidget(widget);
      case 'news':
        return this.renderNewsWidget(widget);
      case 'system':
        return this.renderSystemWidget(widget);
      default:
        return this.renderDefaultWidget(widget);
    }
  }

  private renderWeatherWidget(widget: Widget): string {
    const data = widget.data as WeatherData;
    
    return `
      <div class="widget weather-widget" data-widget-id="${widget.id}">
        <div class="widget-header">
          <h3>${widget.title}</h3>
          <button class="widget-btn" data-action="refresh" title="Refresh">
            <i class="fas fa-sync-alt"></i>
          </button>
        </div>
        <div class="widget-content">
          <div class="weather-current">
            <div class="weather-location">${data.location}</div>
            <div class="weather-temp">${data.temperature}°</div>
            <div class="weather-condition">
              <i class="${data.icon}"></i>
              <span>${data.condition}</span>
            </div>
          </div>
          <div class="weather-details">
            <div class="weather-detail">
              <i class="fas fa-tint"></i>
              <span>Humidity: ${data.humidity}%</span>
            </div>
            <div class="weather-detail">
              <i class="fas fa-wind"></i>
              <span>Wind: ${data.windSpeed} mph</span>
            </div>
          </div>
          <div class="weather-forecast">
            ${data.forecast.map(day => `
              <div class="forecast-item">
                <div class="forecast-day">${day.day}</div>
                <i class="${day.icon}"></i>
                <div class="forecast-temps">${day.high}°/${day.low}°</div>
              </div>
            `).join('')}
          </div>
        </div>
      </div>
    `;
  }

  private renderCalendarWidget(widget: Widget): string {
    const data = widget.data;
    const date = new Date(data.currentDate);
    
    return `
      <div class="widget calendar-widget" data-widget-id="${widget.id}">
        <div class="widget-header">
          <h3>${widget.title}</h3>
          <button class="widget-btn" data-action="open-calendar" title="Open Calendar App">
            <i class="fas fa-external-link-alt"></i>
          </button>
        </div>
        <div class="widget-content">
          <div class="calendar-date">
            <div class="calendar-day">${date.toLocaleDateString('en-US', { weekday: 'long' })}</div>
            <div class="calendar-month-day">${date.getDate()}</div>
            <div class="calendar-month">${date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })}</div>
          </div>
          <div class="calendar-events">
            <h4>Today's Events</h4>
            ${data.events.map((event: any) => `
              <div class="calendar-event" style="border-left: 3px solid ${event.color}">
                <div class="event-time">${event.time}</div>
                <div class="event-title">${event.title}</div>
              </div>
            `).join('')}
          </div>
        </div>
      </div>
    `;
  }

  private renderClockWidget(widget: Widget): string {
    const data = widget.data;
    
    return `
      <div class="widget clock-widget" data-widget-id="${widget.id}">
        <div class="widget-header">
          <h3>${widget.title}</h3>
        </div>
        <div class="widget-content">
          ${data.zones.map((zone: any) => {
            const time = new Date().toLocaleTimeString('en-US', { 
              timeZone: zone.timezone,
              hour: '2-digit',
              minute: '2-digit'
            });
            
            return `
              <div class="clock-zone">
                <div class="zone-name">${zone.name}</div>
                <div class="zone-time">${time}</div>
              </div>
            `;
          }).join('')}
        </div>
      </div>
    `;
  }

  private renderStocksWidget(widget: Widget): string {
    const data = widget.data;
    
    return `
      <div class="widget stocks-widget" data-widget-id="${widget.id}">
        <div class="widget-header">
          <h3>${widget.title}</h3>
          <button class="widget-btn" data-action="refresh" title="Refresh">
            <i class="fas fa-sync-alt"></i>
          </button>
        </div>
        <div class="widget-content">
          ${data.watchlist.map((stock: any) => `
            <div class="stock-item">
              <div class="stock-info">
                <div class="stock-symbol">${stock.symbol}</div>
                <div class="stock-name">${stock.name}</div>
              </div>
              <div class="stock-price">
                <div class="stock-current">$${stock.price}</div>
                <div class="stock-change ${stock.change >= 0 ? 'positive' : 'negative'}">
                  ${stock.change >= 0 ? '+' : ''}${stock.change} (${stock.changePercent}%)
                </div>
              </div>
            </div>
          `).join('')}
        </div>
      </div>
    `;
  }

  private renderNewsWidget(widget: Widget): string {
    const data = widget.data;
    
    return `
      <div class="widget news-widget" data-widget-id="${widget.id}">
        <div class="widget-header">
          <h3>${widget.title}</h3>
          <button class="widget-btn" data-action="refresh" title="Refresh">
            <i class="fas fa-sync-alt"></i>
          </button>
        </div>
        <div class="widget-content">
          ${data.headlines.map((article: any) => `
            <div class="news-item">
              <div class="news-title">${article.title}</div>
              <div class="news-meta">
                <span class="news-source">${article.source}</span>
                <span class="news-time">${article.time}</span>
              </div>
            </div>
          `).join('')}
        </div>
      </div>
    `;
  }

  private renderSystemWidget(widget: Widget): string {
    const data = widget.data;
    
    return `
      <div class="widget system-widget" data-widget-id="${widget.id}">
        <div class="widget-header">
          <h3>${widget.title}</h3>
          <button class="widget-btn" data-action="open-task-manager" title="Open Task Manager">
            <i class="fas fa-external-link-alt"></i>
          </button>
        </div>
        <div class="widget-content">
          <div class="system-metric">
            <div class="metric-label">CPU Usage</div>
            <div class="metric-bar">
              <div class="metric-fill" style="width: ${data.cpu}%"></div>
            </div>
            <div class="metric-value">${data.cpu}%</div>
          </div>
          <div class="system-metric">
            <div class="metric-label">Memory</div>
            <div class="metric-bar">
              <div class="metric-fill" style="width: ${data.memory}%"></div>
            </div>
            <div class="metric-value">${data.memory}%</div>
          </div>
          <div class="system-metric">
            <div class="metric-label">Storage</div>
            <div class="metric-bar">
              <div class="metric-fill" style="width: ${data.storage}%"></div>
            </div>
            <div class="metric-value">${data.storage}%</div>
          </div>
          <div class="system-status">
            <i class="fas fa-wifi"></i>
            <span>${data.network}</span>
          </div>
        </div>
      </div>
    `;
  }

  private renderDefaultWidget(widget: Widget): string {
    return `
      <div class="widget default-widget" data-widget-id="${widget.id}">
        <div class="widget-header">
          <h3>${widget.title}</h3>
        </div>
        <div class="widget-content">
          <p>Widget content not available</p>
        </div>
      </div>
    `;
  }

  private startWidgetTimers() {
    // Update clock widgets every minute
    setInterval(() => {
      this.updateClockWidgets();
    }, 60000);

    // Update system widgets every 5 seconds
    setInterval(() => {
      this.updateSystemWidgets();
    }, 5000);
  }

  private updateClockWidgets() {
    const clockWidgets = this.widgets.filter(w => w.type === 'clock' && w.enabled);
    clockWidgets.forEach(widget => {
      const element = this.widgetsScreen?.querySelector(`[data-widget-id="${widget.id}"]`);
      if (element) {
        const content = element.querySelector('.widget-content');
        if (content) {
          content.innerHTML = this.renderClockWidget(widget).match(/<div class="widget-content">(.*?)<\/div>/s)?.[1] || '';
        }
      }
    });
  }

  private updateSystemWidgets() {
    const systemWidgets = this.widgets.filter(w => w.type === 'system' && w.enabled);
    systemWidgets.forEach(widget => {
      // Simulate system metrics (in a real app, these would come from actual system APIs)
      widget.data = {
        cpu: Math.floor(Math.random() * 100),
        memory: Math.floor(Math.random() * 100),
        storage: 82, // Keep storage static
        network: 'Connected'
      };

      const element = this.widgetsScreen?.querySelector(`[data-widget-id="${widget.id}"]`);
      if (element) {
        const content = element.querySelector('.widget-content');
        if (content) {
          content.innerHTML = this.renderSystemWidget(widget).match(/<div class="widget-content">(.*?)<\/div>/s)?.[1] || '';
        }
      }
    });
  }

  private handleWidgetAction(widgetId: string, action: string) {
    const widget = this.widgets.find(w => w.id === widgetId);
    if (!widget) return;

    switch (action) {
      case 'refresh':
        this.refreshWidget(widgetId);
        break;
      case 'open-calendar':
        eventSystem.emit('app:launch', { appId: 'calendar' });
        break;
      case 'open-task-manager':
        eventSystem.emit('app:launch', { appId: 'task-manager' });
        break;
      default:
        console.log(`Unknown widget action: ${action}`);
    }
  }

  private refreshWidget(widgetId: string) {
    const widget = this.widgets.find(w => w.id === widgetId);
    if (!widget) return;

    // Show refresh animation
    const element = this.widgetsScreen?.querySelector(`[data-widget-id="${widgetId}"] .fa-sync-alt`);
    if (element) {
      element.classList.add('fa-spin');
      setTimeout(() => {
        element.classList.remove('fa-spin');
      }, 1000);
    }

    // Simulate data refresh (in a real app, this would fetch from APIs)
    switch (widget.type) {
      case 'weather':
        // Update weather data
        eventSystem.emit('widget:refreshed', { widgetId, type: 'weather' });
        break;
      case 'stocks':
        // Update stock prices
        eventSystem.emit('widget:refreshed', { widgetId, type: 'stocks' });
        break;
      case 'news':
        // Update news headlines
        eventSystem.emit('widget:refreshed', { widgetId, type: 'news' });
        break;
    }
  }

  private updateWidget(widgetId: string, data: any) {
    const widget = this.widgets.find(w => w.id === widgetId);
    if (!widget) return;

    widget.data = { ...widget.data, ...data };
    
    // Re-render the specific widget
    const element = this.widgetsScreen?.querySelector(`[data-widget-id="${widgetId}"]`);
    if (element) {
      element.outerHTML = this.renderWidget(widget);
    }
  }

  private saveConfiguration() {
    try {
      localStorage.setItem('widgets-config', JSON.stringify(this.widgets));
    } catch (err) {
      console.warn('Failed to save widgets configuration:', err);
    }
  }

  // Public methods
  show() {
    if (!this.widgetsScreen) return;

    this.isVisible = true;
    this.widgetsScreen.classList.remove('widgets-hidden');
    
    eventSystem.emit('global:widgets:visibility:changed', { visible: true });
  }

  hide() {
    if (!this.widgetsScreen) return;

    this.isVisible = false;
    this.widgetsScreen.classList.add('widgets-hidden');
    
    eventSystem.emit('global:widgets:visibility:changed', { visible: false });
  }

  toggle() {
    if (this.isVisible) {
      this.hide();
    } else {
      this.show();
    }
  }

  isWidgetsVisible(): boolean {
    return this.isVisible;
  }

  enableWidget(widgetId: string) {
    const widget = this.widgets.find(w => w.id === widgetId);
    if (widget) {
      widget.enabled = true;
      this.renderWidgets();
      this.saveConfiguration();
    }
  }

  disableWidget(widgetId: string) {
    const widget = this.widgets.find(w => w.id === widgetId);
    if (widget) {
      widget.enabled = false;
      this.renderWidgets();
      this.saveConfiguration();
    }
  }

  getWidgets(): Widget[] {
    return [...this.widgets];
  }
}

export const widgetsService = new WidgetsService();

// Make it globally available for compatibility
(window as any).widgetsService = widgetsService;

// Expose functions globally for backward compatibility
(window as any).toggleWidgets = () => {
  widgetsService.toggle();
};

(window as any).showWidgets = () => {
  widgetsService.show();
};

(window as any).hideWidgets = () => {
  widgetsService.hide();
}; 