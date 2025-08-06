import { eventSystem } from './EventSystem'

interface DragSelectorState {
  isSelecting: boolean
  startX: number
  startY: number
  currentX: number
  currentY: number
  selector?: HTMLElement
}

interface Rectangle {
  left: number
  right: number
  top: number
  bottom: number
}

export class DesktopDragSelector {
  private state: DragSelectorState
  private eventSystem: typeof eventSystem
  private mouseDownHandler: (e: MouseEvent) => void
  private mouseMoveHandler: (e: MouseEvent) => void
  private mouseUpHandler: (e: MouseEvent) => void

  constructor(eventSystem: typeof eventSystem) {
    this.eventSystem = eventSystem
    this.state = {
      isSelecting: false,
      startX: 0,
      startY: 0,
      currentX: 0,
      currentY: 0,
      selector: undefined
    }

    // Bind event handlers
    this.mouseDownHandler = this.handleMouseDown.bind(this)
    this.mouseMoveHandler = this.handleMouseMove.bind(this)
    this.mouseUpHandler = this.handleMouseUp.bind(this)
  }

  initialize(): void {
    this.state.selector = document.getElementById('drag-selector')
    this.state.desktopArea = document.getElementById('desktop-area')

    if (!this.state.selector || !this.state.desktopArea) {
      console.warn('DesktopDragSelector: Required elements not found')
      return
    }

    this.setupEventListeners()
  }

  private setupEventListeners(): void {
    if (!this.state.desktopArea) return

    // Add mousedown listener to desktop area
    this.state.desktopArea.addEventListener('mousedown', this.mouseDownHandler)
    
    // Add global mousemove and mouseup listeners
    document.addEventListener('mousemove', this.mouseMoveHandler)
    document.addEventListener('mouseup', this.mouseUpHandler)
  }

  private handleMouseDown(e: MouseEvent): void {
    if (!this.state.desktopArea || !this.state.selector) return

    // Allow text selection/copy in email-content
    if (e.target instanceof Element && (
      e.target.closest('.email-content') || 
      e.target.closest('.email-content-section')
    )) return

    // Prevent drag selector in app-launcher-mode or easy-mode
    if (
      this.state.desktopArea.classList.contains('app-launcher-mode') ||
      document.body.classList.contains('app-launcher-mode') ||
      this.state.desktopArea.classList.contains('easy-mode') ||
      document.body.classList.contains('easy-mode')
    ) return

    // Allow both left and right click to start drag selector
    if (e.button !== 0 && e.button !== 2) return

    // Prevent drag selector in settings app
    if (e.target instanceof Element && e.target.closest('.settings-app-window')) return

    // Don't start drag selector if clicking on specific elements
    if (e.target instanceof Element && (
      e.target.closest('.desktop-icon') ||
      e.target.closest('.window') ||
      e.target.closest('.taskbar') ||
      e.target.closest('.start-menu') ||
      e.target.closest('#widgets-screen') ||
      e.target.closest('.widget')
    )) {
      // Clear selection if clicking on non-selected desktop icon
      if (e.target.closest('.desktop-icon') && !e.target.closest('.desktop-icon.selected')) {
        this.clearIconSelection()
      }
      return
    }

    // Start drag selector
    this.startDragSelector(e)
  }

  private startDragSelector(e: MouseEvent): void {
    if (!this.state.desktopArea || !this.state.selector) return

    // Append selector to desktop area
    this.state.desktopArea.appendChild(this.state.selector)
    this.state.isSelecting = true
    this.state.selector.classList.remove('hidden')

    // Calculate start position relative to desktop area
    const desktopRect = this.state.desktopArea.getBoundingClientRect()
    this.state.startX = e.clientX - desktopRect.left + this.state.desktopArea.scrollLeft
    this.state.startY = e.clientY - desktopRect.top + this.state.desktopArea.scrollTop

    // Position and size the selector
    this.state.selector.style.left = `${this.state.startX}px`
    this.state.selector.style.top = `${this.state.startY}px`
    this.state.selector.style.width = '0px'
    this.state.selector.style.height = '0px'

    // Clear existing selection
    this.clearIconSelection()

    // Prevent context menu on right click
    if (e.button === 2) {
      e.preventDefault()
      // Hide context menu if it exists
      this.eventSystem.emit('contextmenu:hide', undefined)
    }

    e.preventDefault()
  }

  private handleMouseMove(e: MouseEvent): void {
    if (!this.state.isSelecting || !this.state.desktopArea || !this.state.selector) return
    
    // Ensure selector is still in desktop area
    if (this.state.selector.parentElement !== this.state.desktopArea) return

    // Calculate current position relative to desktop area
    const desktopRect = this.state.desktopArea.getBoundingClientRect()
    const currentX = e.clientX - desktopRect.left + this.state.desktopArea.scrollLeft
    const currentY = e.clientY - desktopRect.top + this.state.desktopArea.scrollTop

    // Calculate selector dimensions
    const newLeft = Math.min(currentX, this.state.startX)
    const newTop = Math.min(currentY, this.state.startY)
    const newWidth = Math.abs(currentX - this.state.startX)
    const newHeight = Math.abs(currentY - this.state.startY)

    // Update selector position and size
    this.state.selector.style.left = `${newLeft}px`
    this.state.selector.style.top = `${newTop}px`
    this.state.selector.style.width = `${newWidth}px`
    this.state.selector.style.height = `${newHeight}px`

    // Update selected icons
    this.updateSelectedIcons()
  }

  private handleMouseUp(e: MouseEvent): void {
    if (this.state.isSelecting) {
      this.state.isSelecting = false
      
      if (this.state.selector) {
        this.state.selector.classList.add('hidden')
      }

      // Clear selection if no icons are selected
      if (this.state.selectedIcons.size === 0) {
        this.clearIconSelection()
      }

      // Emit selection change event
      this.eventSystem.emit('desktop:selection-changed', {
        selectedIcons: Array.from(this.state.selectedIcons)
      })
    }
  }

  private updateSelectedIcons(): void {
    if (!this.state.selector || !this.state.desktopArea) return

    const selectorRect = this.state.selector.getBoundingClientRect()
    const desktopIcons = document.querySelectorAll('.desktop-icon')

    desktopIcons.forEach(icon => {
      const iconRect = icon.getBoundingClientRect()
      
      if (this.isIntersecting(selectorRect, iconRect)) {
        icon.classList.add('selected')
        this.state.selectedIcons.add(icon as HTMLElement)
      } else {
        icon.classList.remove('selected')
        this.state.selectedIcons.delete(icon as HTMLElement)
      }
    })
  }

  private isIntersecting(rect1: Rectangle, rect2: Rectangle): boolean {
    return !(
      rect1.right < rect2.left ||
      rect1.left > rect2.right ||
      rect1.bottom < rect2.top ||
      rect1.top > rect2.bottom
    )
  }

  private clearIconSelection(): void {
    // Clear visual selection
    document.querySelectorAll('.desktop-icon.selected').forEach(icon => {
      icon.classList.remove('selected')
    })

    // Clear internal selection
    this.state.selectedIcons.clear()

    // Emit selection change event
    this.eventSystem.emit('desktop:selection-changed', {
      selectedIcons: []
    })
  }

  public getSelectedIcons(): HTMLElement[] {
    return Array.from(this.state.selectedIcons)
  }

  public clearSelection(): void {
    this.clearIconSelection()
  }

  public cleanup(): void {
    // Remove event listeners
    if (this.state.desktopArea) {
      this.state.desktopArea.removeEventListener('mousedown', this.mouseDownHandler)
    }
    
    document.removeEventListener('mousemove', this.mouseMoveHandler)
    document.removeEventListener('mouseup', this.mouseUpHandler)

    // Clear state
    this.state.selectedIcons.clear()
    this.state.isSelecting = false
  }
} 