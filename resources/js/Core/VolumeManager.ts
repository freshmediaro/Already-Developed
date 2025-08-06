// Volume Management System - Extracted from original app.js
import { eventSystem } from './EventSystem';

interface VolumeState {
  level: number;
  isMuted: boolean;
  previousLevel: number;
}

interface MusicState {
  isPlaying: boolean;
  currentTime: number;
  totalTime: number;
  title: string;
  artist: string;
  albumArt: string;
  isRepeat: boolean;
  isShuffle: boolean;
}

class VolumeManager {
  private volumeState: VolumeState = {
    level: 75,
    isMuted: false,
    previousLevel: 75
  };

  private musicState: MusicState = {
    isPlaying: false,
    currentTime: 47,
    totalTime: 315,
    title: 'Alina Eremia - Cea mai frum...',
    artist: 'Alien Station • Alien Rock',
    albumArt: 'https://i.imgur.com/4M34hi2.png',
    isRepeat: false,
    isShuffle: false
  };
  
  private volumePanel: HTMLElement | null = null;
  private volumeSlider: HTMLInputElement | null = null;
  private musicProgressSlider: HTMLInputElement | null = null;
  private isPanelVisible = false;
  private hideTimeout: NodeJS.Timeout | null = null;

  constructor() {
    this.init();
    this.loadVolumeState();
    this.setupEventListeners();
  }

  private init() {
    this.createVolumePanel();
    this.updateVolumeDisplay();
    this.updateMusicDisplay();
  }

  private createVolumePanel() {
    // Find existing volume panel or create it
    this.volumePanel = document.getElementById('volume-panel');
    
    if (!this.volumePanel) {
      this.volumePanel = document.createElement('div');
      this.volumePanel.id = 'volume-panel';
      this.volumePanel.className = 'volume-panel';
      this.volumePanel.style.display = 'none';
      
      // Use the exact HTML structure from static files
      this.volumePanel.innerHTML = `
        <button id="close-volume-panel" class="close-volume-panel-btn" title="Close"><i class="fas fa-times"></i></button>
        <div class="music-panel-box">
          <div class="music-panel-content">
            <div class="music-info-block">
              <div class="music-title">${this.musicState.title}</div>
              <div class="music-meta">${this.musicState.artist}</div>
            </div>
            <div class="music-album-art-block">
              <img src="${this.musicState.albumArt}" alt="Album Art" />
            </div>
          </div>
          <div class="music-progress-row">
            <input type="range" class="music-progress-slider" min="0" max="${this.musicState.totalTime}" value="${this.musicState.currentTime}">
            <div class="music-time-row">
              <span class="music-current-time">${this.formatTime(this.musicState.currentTime)}</span>
              <span class="music-total-time">${this.formatTime(this.musicState.totalTime)}</span>
            </div>
          </div>
          <div class="music-controls-row">
            <button class="music-btn" title="Playlist"><i class="fas fa-list"></i></button>
            <button class="music-btn" title="Devices"><i class="fas fa-laptop"></i></button>
            <button class="music-btn" title="Previous"><i class="fas fa-backward"></i></button>
            <button class="music-btn play-btn" title="Play/Pause"><i class="fas ${this.musicState.isPlaying ? 'fa-pause' : 'fa-play'}"></i></button>
            <button class="music-btn" title="Next"><i class="fas fa-forward"></i></button>
            <button class="music-btn repeat-btn ${this.musicState.isRepeat ? 'active' : ''}" title="Repeat"><i class="fas fa-retweet"></i></button>
            <button class="music-btn shuffle-btn ${this.musicState.isShuffle ? 'active' : ''}" title="Shuffle"><i class="fas fa-random"></i></button>
          </div>
        </div>
        <div class="volume-panel-box">
          <div class="volume-slider-panel">
            <i class="fas fa-volume-up"></i>
            <input type="range" id="browser-volume-slider" min="0" max="100" value="${this.volumeState.level}">
            <span id="volume-percentage">${this.volumeState.level}</span>
          </div>
        </div>
      `;
      
      document.body.appendChild(this.volumePanel);
    }

    // Get references to elements
    this.volumeSlider = this.volumePanel.querySelector('#browser-volume-slider');
    this.musicProgressSlider = this.volumePanel.querySelector('.music-progress-slider');
  }

  private formatTime(seconds: number): string {
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
  }

  private setupEventListeners() {
    if (!this.volumePanel) return;

    // Close button
    const closeBtn = this.volumePanel.querySelector('#close-volume-panel');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => {
        this.hidePanel();
      });
    }

    // Volume slider
    if (this.volumeSlider) {
      this.volumeSlider.addEventListener('input', (e) => {
        const value = parseInt((e.target as HTMLInputElement).value);
        this.setVolume(value);
      });

      this.volumeSlider.addEventListener('change', () => {
        this.saveVolumeState();
      });
    }

    // Music progress slider
    if (this.musicProgressSlider) {
      this.musicProgressSlider.addEventListener('input', (e) => {
        const value = parseInt((e.target as HTMLInputElement).value);
        this.setMusicProgress(value);
      });
    }

    // Music control buttons
    const playBtn = this.volumePanel.querySelector('.play-btn');
    if (playBtn) {
      playBtn.addEventListener('click', () => {
        this.togglePlayPause();
      });
    }

    const prevBtn = this.volumePanel.querySelector('.music-btn[title="Previous"]');
    if (prevBtn) {
      prevBtn.addEventListener('click', () => {
        this.previousTrack();
      });
    }

    const nextBtn = this.volumePanel.querySelector('.music-btn[title="Next"]');
    if (nextBtn) {
      nextBtn.addEventListener('click', () => {
        this.nextTrack();
      });
    }

    const repeatBtn = this.volumePanel.querySelector('.repeat-btn');
    if (repeatBtn) {
      repeatBtn.addEventListener('click', () => {
        this.toggleRepeat();
      });
    }

    const shuffleBtn = this.volumePanel.querySelector('.shuffle-btn');
    if (shuffleBtn) {
      shuffleBtn.addEventListener('click', () => {
        this.toggleShuffle();
      });
    }

    // Hide panel on outside click
    document.addEventListener('click', (e) => {
      if (this.isPanelVisible && this.volumePanel && !this.volumePanel.contains(e.target as Node)) {
        // Check if click was on volume button
        const volumeBtn = document.querySelector('#volume-btn');
        if (!volumeBtn || !volumeBtn.contains(e.target as Node)) {
          this.hidePanel();
        }
      }
    });

    // Keyboard shortcuts
    document.addEventListener('keydown', (e) => {
      if (e.target && (e.target as HTMLElement).tagName === 'INPUT') return;

      switch (e.key) {
        case 'AudioVolumeUp':
        case 'VolumeUp':
          e.preventDefault();
          this.increaseVolume();
          break;
        case 'AudioVolumeDown':
        case 'VolumeDown':
          e.preventDefault();
          this.decreaseVolume();
          break;
        case 'AudioVolumeMute':
        case 'VolumeMute':
          e.preventDefault();
          this.toggleMute();
          break;
        case ' ':
          if (this.isPanelVisible) {
            e.preventDefault();
            this.togglePlayPause();
          }
          break;
      }
    });

    // Handle volume button clicks
    const volumeBtn = document.querySelector('#volume-btn');
    if (volumeBtn) {
      volumeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        this.togglePanel();
      });
    }
  }

  private updateVolumeDisplay() {
    if (!this.volumeSlider) return;

    // Update slider
    this.volumeSlider.value = this.volumeState.level.toString();

    // Update percentage display
    const volumePercentage = this.volumePanel?.querySelector('#volume-percentage');
    if (volumePercentage) {
      volumePercentage.textContent = this.volumeState.level.toString();
    }

    // Update volume icon
    const volumeIcon = this.volumePanel?.querySelector('.volume-slider-panel i');
    if (volumeIcon) {
      // Remove all volume icon classes
      volumeIcon.className = volumeIcon.className.replace(/fa-volume-\w+/g, '');
      volumeIcon.classList.add('fas');

      if (this.volumeState.isMuted || this.volumeState.level === 0) {
        volumeIcon.classList.add('fa-volume-mute');
      } else if (this.volumeState.level < 33) {
        volumeIcon.classList.add('fa-volume-down');
      } else if (this.volumeState.level < 66) {
        volumeIcon.classList.add('fa-volume-low');
      } else {
        volumeIcon.classList.add('fa-volume-up');
      }
    }

    // Update taskbar volume button icon
    this.updateTaskbarVolumeIcon();
  }

  private updateMusicDisplay() {
    if (!this.volumePanel) return;

    // Update music info
    const musicTitle = this.volumePanel.querySelector('.music-title');
    if (musicTitle) {
      musicTitle.textContent = this.musicState.title;
    }

    const musicMeta = this.volumePanel.querySelector('.music-meta');
    if (musicMeta) {
      musicMeta.textContent = this.musicState.artist;
    }

    // Update album art
    const albumArt = this.volumePanel.querySelector('.music-album-art-block img') as HTMLImageElement;
    if (albumArt) {
      albumArt.src = this.musicState.albumArt;
    }

    // Update progress
    if (this.musicProgressSlider) {
      this.musicProgressSlider.max = this.musicState.totalTime.toString();
      this.musicProgressSlider.value = this.musicState.currentTime.toString();
    }

    // Update time displays
    const currentTime = this.volumePanel.querySelector('.music-current-time');
    if (currentTime) {
      currentTime.textContent = this.formatTime(this.musicState.currentTime);
    }

    const totalTime = this.volumePanel.querySelector('.music-total-time');
    if (totalTime) {
      totalTime.textContent = this.formatTime(this.musicState.totalTime);
    }

    // Update play/pause button
    const playBtn = this.volumePanel.querySelector('.play-btn i');
    if (playBtn) {
      playBtn.className = `fas ${this.musicState.isPlaying ? 'fa-pause' : 'fa-play'}`;
    }

    // Update repeat/shuffle buttons
    const repeatBtn = this.volumePanel.querySelector('.repeat-btn');
    if (repeatBtn) {
      repeatBtn.classList.toggle('active', this.musicState.isRepeat);
    }

    const shuffleBtn = this.volumePanel.querySelector('.shuffle-btn');
    if (shuffleBtn) {
      shuffleBtn.classList.toggle('active', this.musicState.isShuffle);
    }
  }

  private updateTaskbarVolumeIcon() {
    const taskbarVolumeBtn = document.querySelector('#volume-btn i');
    if (!taskbarVolumeBtn) return;

    // Remove all volume icon classes
    taskbarVolumeBtn.className = taskbarVolumeBtn.className.replace(/fa-volume-\w+/g, '');
    taskbarVolumeBtn.classList.add('fas'); // Ensure base class is present

    if (this.volumeState.isMuted || this.volumeState.level === 0) {
      taskbarVolumeBtn.classList.add('fa-volume-mute');
    } else if (this.volumeState.level < 33) {
      taskbarVolumeBtn.classList.add('fa-volume-down');
    } else if (this.volumeState.level < 66) {
      taskbarVolumeBtn.classList.add('fa-volume-low');
    } else {
      taskbarVolumeBtn.classList.add('fa-volume-up');
    }
  }

  // Music control methods
  private setMusicProgress(seconds: number) {
    this.musicState.currentTime = seconds;
    this.updateMusicDisplay();
    
    eventSystem.emit('music:progress:changed', {
      currentTime: this.musicState.currentTime,
      totalTime: this.musicState.totalTime
    });
  }

  private togglePlayPause() {
    this.musicState.isPlaying = !this.musicState.isPlaying;
    this.updateMusicDisplay();

    eventSystem.emit('music:playback:toggle', {
      isPlaying: this.musicState.isPlaying
    });
  }

  private previousTrack() {
    eventSystem.emit('music:track:previous', {});
  }

  private nextTrack() {
    eventSystem.emit('music:track:next', {});
  }

  private toggleRepeat() {
    this.musicState.isRepeat = !this.musicState.isRepeat;
    this.updateMusicDisplay();

    eventSystem.emit('music:repeat:toggle', {
      isRepeat: this.musicState.isRepeat
    });
  }

  private toggleShuffle() {
    this.musicState.isShuffle = !this.musicState.isShuffle;
    this.updateMusicDisplay();

    eventSystem.emit('music:shuffle:toggle', {
      isShuffle: this.musicState.isShuffle
    });
  }

  // Public volume methods
  setVolume(level: number) {
    level = Math.max(0, Math.min(100, level));
    
    if (level > 0 && this.volumeState.isMuted) {
      this.volumeState.isMuted = false;
    }
    
    this.volumeState.level = level;
    this.updateVolumeDisplay();
    
    eventSystem.emit('global:volume:changed', {
      level: this.volumeState.level,
      isMuted: this.volumeState.isMuted
    });
  }

  increaseVolume(step = 5) {
    this.setVolume(this.volumeState.level + step);
    this.showVolumeNotification(`Volume: ${this.volumeState.level}%`);
    this.showPanelTemporarily();
  }

  decreaseVolume(step = 5) {
    this.setVolume(this.volumeState.level - step);
    this.showVolumeNotification(`Volume: ${this.volumeState.level}%`);
    this.showPanelTemporarily();
  }

  toggleMute() {
    if (this.volumeState.isMuted) {
      // Unmute
      this.volumeState.isMuted = false;
      this.setVolume(this.volumeState.previousLevel);
      this.showVolumeNotification(`Unmuted: ${this.volumeState.level}%`);
    } else {
      // Mute
      this.volumeState.previousLevel = this.volumeState.level;
      this.volumeState.isMuted = true;
      this.updateVolumeDisplay();
      this.showVolumeNotification('Muted');
    }

    eventSystem.emit('global:volume:changed', {
      level: this.volumeState.level,
      isMuted: this.volumeState.isMuted
    });

    this.saveVolumeState();
  }

  private showVolumeNotification(message: string) {
    // Create temporary volume overlay similar to Windows
    const overlay = document.createElement('div');
    overlay.className = 'volume-overlay';
    overlay.innerHTML = `
      <div class="volume-overlay-content">
        <i class="fas ${this.getVolumeIconClass()}"></i>
        <div class="volume-overlay-text">${message}</div>
        <div class="volume-overlay-bar">
          <div class="volume-overlay-fill" style="width: ${this.volumeState.level}%"></div>
        </div>
      </div>
    `;

    overlay.style.cssText = `
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: rgba(0, 0, 0, 0.8);
      color: white;
      padding: 20px;
      border-radius: 10px;
      z-index: 10000;
      text-align: center;
      font-size: 14px;
      min-width: 200px;
      backdrop-filter: blur(10px);
    `;

    document.body.appendChild(overlay);

    // Remove after 2 seconds
    setTimeout(() => {
      if (overlay.parentNode) {
        overlay.parentNode.removeChild(overlay);
      }
    }, 2000);
  }

  private getVolumeIconClass(): string {
    if (this.volumeState.isMuted || this.volumeState.level === 0) {
      return 'fa-volume-mute';
    } else if (this.volumeState.level < 33) {
      return 'fa-volume-down';
    } else if (this.volumeState.level < 66) {
      return 'fa-volume-low';
    } else {
      return 'fa-volume-up';
    }
  }

  // Panel management
  showPanel() {
    if (!this.volumePanel) return;

    this.isPanelVisible = true;
    this.volumePanel.style.display = 'block';
    
    // Position panel near volume button
    this.positionPanel();
    
    // Clear any existing hide timeout
    if (this.hideTimeout) {
      clearTimeout(this.hideTimeout);
      this.hideTimeout = null;
    }

    eventSystem.emit('volume:panel:show', {});
  }

  hidePanel() {
    if (!this.volumePanel) return;

    this.isPanelVisible = false;
    this.volumePanel.style.display = 'none';

    if (this.hideTimeout) {
      clearTimeout(this.hideTimeout);
      this.hideTimeout = null;
    }

    eventSystem.emit('volume:panel:hide', {});
  }

  togglePanel() {
    if (this.isPanelVisible) {
      this.hidePanel();
    } else {
      this.showPanel();
    }
  }

  showPanelTemporarily(duration = 3000) {
    this.showPanel();
    
    if (this.hideTimeout) {
      clearTimeout(this.hideTimeout);
    }
    
    this.hideTimeout = setTimeout(() => {
      this.hidePanel();
    }, duration);
  }

  private positionPanel() {
    if (!this.volumePanel) return;

    const volumeBtn = document.querySelector('#volume-btn');
    if (!volumeBtn) return;

    const btnRect = volumeBtn.getBoundingClientRect();
    const panelRect = this.volumePanel.getBoundingClientRect();
    
    // Position above the volume button
    let left = btnRect.left + (btnRect.width / 2) - (panelRect.width / 2);
    let top = btnRect.top - panelRect.height - 10;

    // Ensure panel stays within viewport
    const margin = 10;
    left = Math.max(margin, Math.min(left, window.innerWidth - panelRect.width - margin));
    
    if (top < margin) {
      // If no space above, position below
      top = btnRect.bottom + 10;
    }

    this.volumePanel.style.left = `${left}px`;
    this.volumePanel.style.top = `${top}px`;
    this.volumePanel.style.position = 'fixed';
  }

  private openSoundSettings() {
    eventSystem.emit('settings:open', { section: 'sound-settings' });
    this.hidePanel();
  }

  private saveVolumeState() {
    try {
      const state = {
        volume: this.volumeState,
        music: this.musicState
      };
      localStorage.setItem('volumeState', JSON.stringify(state));
    } catch (err) {
      console.warn('Failed to save volume state:', err);
    }
  }

  private loadVolumeState() {
    try {
      const saved = localStorage.getItem('volumeState');
      if (saved) {
        const state = JSON.parse(saved);
        if (state.volume) {
          this.volumeState = {
            level: state.volume.level || 75,
            isMuted: state.volume.isMuted || false,
            previousLevel: state.volume.previousLevel || 75
          };
        }
        if (state.music) {
          this.musicState = { ...this.musicState, ...state.music };
        }
      }
    } catch (err) {
      console.warn('Failed to load volume state:', err);
      // Use default state
    }
  }

  // Public music methods
  updateMusicInfo(info: Partial<MusicState>) {
    this.musicState = { ...this.musicState, ...info };
    this.updateMusicDisplay();
  }

  // Getters
  getVolume(): number {
    return this.volumeState.level;
  }

  isMuted(): boolean {
    return this.volumeState.isMuted;
  }

  getVolumeState(): VolumeState {
    return { ...this.volumeState };
  }

  getMusicState(): MusicState {
    return { ...this.musicState };
  }

  isPanelOpen(): boolean {
    return this.isPanelVisible;
  }
}

export const volumeManager = new VolumeManager();

// Make it globally available for compatibility
(window as any).volumeManager = volumeManager;

// Expose functions globally for backward compatibility
(window as any).setupVolumePanelListeners = () => {
  // Already handled in constructor
  console.log('Volume panel listeners already set up');
}; 