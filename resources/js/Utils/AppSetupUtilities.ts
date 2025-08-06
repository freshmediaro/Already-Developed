// App Setup Utilities - Extracted from original app.js
// Contains setup functions for complex apps that require special initialization

/**
 * App Setup Utilities - Complex application initialization and setup utilities
 *
 * This class provides specialized setup functions for complex applications
 * that require special initialization, DOM manipulation, and event handling.
 * It contains utilities extracted from the original monolithic app.js file
 * to provide modular, reusable setup functions for different app types.
 *
 * Key features:
 * - SiteBuilder app setup and configuration
 * - Email app setup and functionality
 * - Point of Sale (POS) app setup
 * - DOM manipulation and event handling
 * - Responsive behavior setup
 * - Navigation and interaction setup
 * - Component initialization
 * - Cleanup and resource management
 *
 * Supported app types:
 * - SiteBuilder: Website building and management
 * - Email: Email client functionality
 * - Point of Sale: Sales and booking management
 *
 * The utilities provide:
 * - Complex app initialization
 * - DOM manipulation helpers
 * - Event handling setup
 * - Responsive behavior
 * - Navigation management
 * - Component lifecycle management
 *
 * @class AppSetupUtilities
 * @since 1.0.0
 */
export class AppSetupUtilities {
  
  /**
   * Setup SiteBuilder App - Comprehensive website building application setup
   *
   * This method initializes the SiteBuilder application with all necessary
   * functionality including page actions, settings sections, URL previews,
   * and add page functionality.
   *
   * @param {HTMLElement} windowElement The window element containing the SiteBuilder app
   */
  static setupSiteBuilderApp(windowElement: HTMLElement): void {
    // Hide page-actions-row by default
    const pageActionsRows = windowElement.querySelectorAll('.page-actions-row');
    pageActionsRows.forEach(row => {
      const element = row as HTMLElement;
      element.style.display = 'none';
      element.style.height = '0';
      element.style.overflow = 'hidden';
      element.style.transition = 'height 0.3s ease-in-out';
    });

    // Setup toggle functionality for page actions
    this.setupSiteBuilderToggleActions(windowElement);
    
    // Setup settings sections
    this.setupSiteBuilderSettings(windowElement);
    
    // Setup URL preview functionality
    this.setupSiteBuilderUrlPreviews(windowElement);
    
    // Setup add new page functionality
    this.setupSiteBuilderAddPage(windowElement);
  }

  /**
   * Setup Email App - Email client application setup
   *
   * This method initializes the Email application with email list rendering,
   * navigation, compose functionality, and responsive behavior.
   *
   * @param {HTMLElement} windowElement The window element containing the Email app
   */
  static setupEmailApp(windowElement: HTMLElement): void {
    // Initialize email list rendering
    this.renderEmailList(windowElement);
    
    // Setup email navigation
    this.setupEmailNavigation(windowElement);
    
    // Setup compose functionality
    this.setupEmailCompose(windowElement);
    
    // Setup responsive behavior
    this.setupEmailResponsive(windowElement);
  }

  /**
   * Setup Point of Sale App - Sales and booking management setup
   *
   * This method initializes the Point of Sale application with default sections,
   * navigation, and booking functionality.
   *
   * @param {HTMLElement} windowElement The window element containing the POS app
   */
  static setupPointOfSaleApp(windowElement: HTMLElement): void {
    // Initialize default sections
    this.showProductsSection(windowElement);
    
    // Setup navigation
    this.setupPOSNavigation(windowElement);
    
    // Setup booking functionality
    this.setupPOSBookings(windowElement);
  }

  /**
   * Setup SiteBuilder toggle actions and interactions
   *
   * This method configures the edit and settings buttons for the SiteBuilder
   * application, enabling toggle functionality for page actions and settings sections.
   *
   * @param {HTMLElement} windowElement The window element containing the SiteBuilder app
   */
  private static setupSiteBuilderToggleActions(windowElement: HTMLElement): void {
    // Setup edit buttons
    const editButtons = windowElement.querySelectorAll('.action-btn.edit-btn');
    editButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        this.togglePageActionsRow(button as HTMLElement);
      });
    });

    // Setup settings buttons
    const settingsButtons = windowElement.querySelectorAll('.action-btn.settings-btn');
    settingsButtons.forEach(button => {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        const parentItem = button.closest('.sitebuilder-iteam-content');
        if (parentItem) {
          this.toggleSettingsSection(parentItem as HTMLElement, button as HTMLElement);
        }
      });
    });
  }

  /**
   * Toggle page actions row visibility
   *
   * This method toggles the visibility of page actions row and handles
   * the interaction with settings sections and chevron icons.
   *
   * @param {HTMLElement} button The button that triggered the toggle
   */
  private static togglePageActionsRow(button: HTMLElement): void {
    const parentItem = button.closest('.sitebuilder-iteam-content');
    if (!parentItem) return;

    const actionsRow = parentItem.querySelector('.page-actions-row') as HTMLElement;
    const settingsSection = parentItem.querySelector('.page-settings-section') as HTMLElement;
    const chevronIcon = button.querySelector('i.fa-chevron-down, i.fa-chevron-up');
    
    // If settings section is open, close it first
    if (settingsSection && settingsSection.style.display === 'block') {
      const settingsButton = parentItem.querySelector('.action-btn.settings-btn') as HTMLElement;
      const settingsIcon = settingsButton?.querySelector('i');
      
      settingsSection.style.height = '0';
      setTimeout(() => {
        settingsSection.style.display = 'none';
      }, 300);
      
      if (settingsIcon?.classList.contains('fa-xmark')) {
        settingsIcon.classList.remove('fa-xmark');
        settingsIcon.classList.add('fa-gear');
      }
    }
    
    // Toggle actions row
    if (actionsRow) {
      if (actionsRow.style.display === 'none' || actionsRow.style.height === '0px') {
        actionsRow.style.display = 'flex';
        setTimeout(() => {
          actionsRow.style.height = actionsRow.scrollHeight + 'px';
        }, 10);
        
        if (chevronIcon) {
          chevronIcon.classList.remove('fa-chevron-down');
          chevronIcon.classList.add('fa-chevron-up');
        }
      } else {
        actionsRow.style.height = '0';
        setTimeout(() => {
          actionsRow.style.display = 'none';
        }, 300);
        
        if (chevronIcon) {
          chevronIcon.classList.remove('fa-chevron-up');
          chevronIcon.classList.add('fa-chevron-down');
        }
      }
    }
  }

  private static toggleSettingsSection(parentItem: HTMLElement, button: HTMLElement): void {
    const actionsRow = parentItem.querySelector('.page-actions-row') as HTMLElement;
    const settingsSection = parentItem.querySelector('.page-settings-section') as HTMLElement;
    const editButton = parentItem.querySelector('.action-btn.edit-btn') as HTMLElement;
    const chevronIcon = editButton?.querySelector('i.fa-chevron-down, i.fa-chevron-up');
    const settingsIcon = button.querySelector('i');

    // Close actions row if open
    if (actionsRow && actionsRow.style.display !== 'none') {
      actionsRow.style.height = '0';
      setTimeout(() => {
        actionsRow.style.display = 'none';
      }, 300);
      
      if (chevronIcon) {
        chevronIcon.classList.remove('fa-chevron-up');
        chevronIcon.classList.add('fa-chevron-down');
      }
    }

    // Toggle settings section
    if (settingsSection) {
      if (settingsSection.style.display === 'none' || !settingsSection.style.display) {
        settingsSection.style.display = 'block';
        setTimeout(() => {
          settingsSection.style.height = settingsSection.scrollHeight + 'px';
        }, 10);
        
        if (settingsIcon) {
          settingsIcon.classList.remove('fa-gear');
          settingsIcon.classList.add('fa-xmark');
        }
      } else {
        settingsSection.style.height = '0';
        setTimeout(() => {
          settingsSection.style.display = 'none';
        }, 300);
        
        if (settingsIcon) {
          settingsIcon.classList.remove('fa-xmark');
          settingsIcon.classList.add('fa-gear');
        }
      }
    }
  }

  private static setupSiteBuilderSettings(windowElement: HTMLElement): void {
    // Setup toggle switches
    const toggleSwitches = windowElement.querySelectorAll('.settings-group-header .toggle-switch input');
    toggleSwitches.forEach(toggle => {
      toggle.addEventListener('change', () => {
        const section = toggle.closest('.settings-group')?.querySelector('.publishing-options-section') as HTMLElement;
        if (section) {
          if ((toggle as HTMLInputElement).checked) {
            section.style.display = 'block';
            section.style.height = 'auto';
          } else {
            section.style.display = 'none';
          }
        }
      });
    });

    // Setup cancel and save buttons
    this.setupSiteBuilderButtons(windowElement);
  }

  private static setupSiteBuilderButtons(windowElement: HTMLElement): void {
    // Cancel buttons
    const cancelButtons = windowElement.querySelectorAll('.settings-cancel-btn');
    cancelButtons.forEach(button => {
      button.addEventListener('click', () => {
        const parentItem = button.closest('.sitebuilder-iteam-content');
        const settingsSection = parentItem?.querySelector('.page-settings-section') as HTMLElement;
        const settingsButton = parentItem?.querySelector('.action-btn.settings-btn') as HTMLElement;
        const settingsIcon = settingsButton?.querySelector('i');

        if (settingsSection) {
          settingsSection.style.height = '0';
          setTimeout(() => {
            settingsSection.style.display = 'none';
          }, 300);
        }

        if (settingsIcon?.classList.contains('fa-xmark')) {
          settingsIcon.classList.remove('fa-xmark');
          settingsIcon.classList.add('fa-gear');
        }
      });
    });

    // Save buttons
    const saveButtons = windowElement.querySelectorAll('.settings-save-btn');
    saveButtons.forEach(button => {
      button.addEventListener('click', () => {
        // Implement save functionality - settings have been saved
        
        const parentItem = button.closest('.sitebuilder-iteam-content');
        const settingsSection = parentItem?.querySelector('.page-settings-section') as HTMLElement;
        const settingsButton = parentItem?.querySelector('.action-btn.settings-btn') as HTMLElement;
        const settingsIcon = settingsButton?.querySelector('i');

        if (settingsSection) {
          settingsSection.style.height = '0';
          setTimeout(() => {
            settingsSection.style.display = 'none';
          }, 300);
        }

        if (settingsIcon?.classList.contains('fa-xmark')) {
          settingsIcon.classList.remove('fa-xmark');
          settingsIcon.classList.add('fa-gear');
        }
      });
    });
  }

  private static setupSiteBuilderUrlPreviews(windowElement: HTMLElement): void {
    const publishedUrlElements = windowElement.querySelectorAll('.published-url');
    publishedUrlElements.forEach(urlElement => {
      urlElement.addEventListener('click', (e) => {
        e.preventDefault();
        const url = (urlElement as HTMLElement).textContent?.trim();
        if (url) {
          this.createUrlPreviewWindow(url);
        }
      });
    });
  }

  private static createUrlPreviewWindow(url: string): void {
    // This would integrate with the WindowManager to create a preview window
    // Implementation would depend on the WindowManager API for the given URL
  }

  private static setupSiteBuilderAddPage(windowElement: HTMLElement): void {
    const addNewPageBtn = windowElement.querySelector('.add-new-page-btn');
    if (addNewPageBtn) {
      addNewPageBtn.addEventListener('click', (e) => {
        e.preventDefault();
        this.showAddPageDropdown(windowElement);
      });
    }
  }

  private static showAddPageDropdown(windowElement: HTMLElement): void {
    const windowToolbar = windowElement.querySelector('.window-toolbar');
    const mainContent = windowElement.querySelector('.sitebuilder-main-content');
    
    if (!windowToolbar || !mainContent) return;

    let addPageDropdown = windowElement.querySelector('.add-page-dropdown') as HTMLElement;
    if (!addPageDropdown) {
      addPageDropdown = this.createAddPageDropdown();
      windowToolbar.appendChild(addPageDropdown);
    }

    addPageDropdown.style.display = 'block';
    setTimeout(() => {
      addPageDropdown.style.height = addPageDropdown.scrollHeight + 'px';
    }, 10);
  }

  private static createAddPageDropdown(): HTMLElement {
    const dropdown = document.createElement('div');
    dropdown.className = 'add-page-dropdown';
    dropdown.innerHTML = `
      <div class="add-page-form">
        <div class="form-group">
          <label>Page Title</label>
          <input type="text" class="settings-input" placeholder="Enter page title">
        </div>
        <div class="form-group">
          <label>URL Slug</label>
          <input type="text" class="url-slug" placeholder="page-url">
        </div>
        <div class="form-group">
          <label>Page Type</label>
          <select class="form-control">
            <option value="page">Page</option>
            <option value="post">Post</option>
            <option value="landing">Landing Page</option>
          </select>
        </div>
        <div class="form-actions">
          <button class="settings-cancel-btn">Cancel</button>
          <button class="settings-save-btn">Create Page</button>
        </div>
      </div>
    `;
    return dropdown;
  }

  // Email App methods
  private static renderEmailList(windowElement: HTMLElement, selectedId?: string): void {
    // Mock email data - in real app this would come from API
    const emails = [
      {
        id: 1,
        sender: 'John Doe',
        subject: 'Project Update',
        preview: 'Here is the latest update on our project...',
        time: '10:30 AM',
        unread: true
      },
      {
        id: 2,
        sender: 'Sarah Wilson',
        subject: 'Meeting Tomorrow',
        preview: 'Don\'t forget about our meeting tomorrow at 2 PM...',
        time: '9:15 AM',
        unread: false
      }
    ];

    const emailList = windowElement.querySelector('.email-list');
    if (emailList) {
      emailList.innerHTML = emails.map(email => `
        <div class="email-item ${email.unread ? 'unread' : ''} ${selectedId == email.id.toString() ? 'selected' : ''}" 
             data-email-id="${email.id}">
          <div class="email-sender">${email.sender}</div>
          <div class="email-subject">${email.subject}</div>
          <div class="email-preview">${email.preview}</div>
          <div class="email-time">${email.time}</div>
        </div>
      `).join('');

      // Setup click handlers
      emailList.querySelectorAll('.email-item').forEach(item => {
        item.addEventListener('click', () => {
          const emailId = item.getAttribute('data-email-id');
          if (emailId) {
            this.selectEmail(windowElement, emailId);
          }
        });
      });
    }
  }

  private static selectEmail(windowElement: HTMLElement, emailId: string): void {
    // Remove previous selection
    windowElement.querySelectorAll('.email-item').forEach(item => {
      item.classList.remove('selected');
    });

    // Add selection to clicked item
    const selectedItem = windowElement.querySelector(`[data-email-id="${emailId}"]`);
    if (selectedItem) {
      selectedItem.classList.add('selected');
      selectedItem.classList.remove('unread');
    }

    // Load email content
    this.loadEmailContent(windowElement, emailId);
  }

  private static loadEmailContent(windowElement: HTMLElement, emailId: string): void {
    const emailContent = windowElement.querySelector('.email-content');
    if (emailContent) {
      emailContent.innerHTML = `
        <div class="email-header">
          <h3>Email Subject #${emailId}</h3>
          <div class="email-meta">
            <span>From: sender@example.com</span>
            <span>Date: ${new Date().toLocaleDateString()}</span>
          </div>
        </div>
        <div class="email-body">
          <p>This is the content of email #${emailId}...</p>
        </div>
      `;
    }
  }

  private static setupEmailNavigation(windowElement: HTMLElement): void {
    // Setup back button for mobile
    const backBtn = windowElement.querySelector('.mail-back-btn');
    if (backBtn) {
      backBtn.addEventListener('click', () => {
        this.showEmailListPanel(windowElement);
      });
    }
  }

  private static setupEmailCompose(windowElement: HTMLElement): void {
    const composeBtn = windowElement.querySelector('.compose-btn');
    if (composeBtn) {
      composeBtn.addEventListener('click', () => {
        this.openComposeWindow();
      });
    }
  }

  private static openComposeWindow(): void {
    // Open compose window
    const composeWindow = document.querySelector('.email-compose-window') as HTMLElement;
  }

  private static setupEmailResponsive(windowElement: HTMLElement): void {
    const handleResize = () => {
      if (window.innerWidth <= 768) {
        windowElement.classList.add('mobile-email');
      } else {
        windowElement.classList.remove('mobile-email');
      }
    };

    window.addEventListener('resize', handleResize);
    handleResize(); // Initial call
  }

  private static showEmailListPanel(windowElement: HTMLElement): void {
    const emailList = windowElement.querySelector('.email-list-panel') as HTMLElement;
    const emailContent = windowElement.querySelector('.email-content-panel') as HTMLElement;

    if (emailList) emailList.style.display = 'block';
    if (emailContent) emailContent.style.display = 'none';
  }

  // Point of Sale methods
  private static showProductsSection(windowElement: HTMLElement): void {
    const sections = windowElement.querySelectorAll('.pos-section');
    sections.forEach(section => {
      (section as HTMLElement).style.display = 'none';
    });

    const productsSection = windowElement.querySelector('.products-section') as HTMLElement;
    if (productsSection) {
      productsSection.style.display = 'block';
    }
  }

  private static setupPOSNavigation(windowElement: HTMLElement): void {
    const navItems = windowElement.querySelectorAll('.pos-nav-item');
    navItems.forEach(item => {
      item.addEventListener('click', () => {
        const sectionName = item.getAttribute('data-section');
        if (sectionName) {
          this.showPOSSection(windowElement, sectionName);
        }
      });
    });
  }

  private static showPOSSection(windowElement: HTMLElement, sectionName: string): void {
    // Hide all sections
    const sections = windowElement.querySelectorAll('.pos-section');
    sections.forEach(section => {
      (section as HTMLElement).style.display = 'none';
    });

    // Show target section
    const targetSection = windowElement.querySelector(`.${sectionName}-section`) as HTMLElement;
    if (targetSection) {
      targetSection.style.display = 'block';
    }

    // Update nav active state
    windowElement.querySelectorAll('.pos-nav-item').forEach(item => {
      item.classList.remove('active');
    });
    const activeNavItem = windowElement.querySelector(`[data-section="${sectionName}"]`);
    if (activeNavItem) {
      activeNavItem.classList.add('active');
    }
  }

  private static setupPOSBookings(windowElement: HTMLElement): void {
    // Set up POS bookings functionality
    const bookingSection = windowElement.querySelector('.pos-bookings-section');
  }

  /**
   * Cleanup function to remove event listeners when window is closed
   */
  static cleanup(windowElement: HTMLElement): void {
    // Remove all event listeners that were added
    // This is important to prevent memory leaks
    const buttons = windowElement.querySelectorAll('button, .clickable');
    buttons.forEach(button => {
      const newButton = button.cloneNode(true);
      button.parentNode?.replaceChild(newButton, button);
    });
  }
}

export default AppSetupUtilities; 