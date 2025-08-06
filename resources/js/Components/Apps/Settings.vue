<template>
  <div class="settings-app">
    <!-- Settings Sidebar -->
    <div class="settings-sidebar">
      <nav class="settings-nav">
        <button
          v-for="section in sections"
          :key="section.id"
          @click="activeSection = section.id"
          :class="['nav-item', { active: activeSection === section.id }]"
          :data-category="section.category"
        >
          <component :is="section.icon" class="nav-icon" />
          <span>{{ section.name }}</span>
        </button>
      </nav>
    </div>

    <!-- Settings Content -->
    <div class="settings-content">
      <!-- Appearance Settings -->
      <div v-if="activeSection === 'appearance'" class="settings-section">
        <h2 class="section-title">Appearance</h2>
        
        <div class="setting-group">
          <label class="setting-label">Theme</label>
          <div class="theme-options">
            <button
              v-for="theme in themes"
              :key="theme.id"
              @click="setTheme(theme.id)"
              :class="['theme-option', { active: currentTheme === theme.id }]"
            >
              <div :class="['theme-preview', theme.id]">
                <div class="theme-header"></div>
                <div class="theme-body"></div>
              </div>
              <span>{{ theme.name }}</span>
            </button>
          </div>
        </div>

        <div class="setting-group">
          <label class="setting-label">Accent Color</label>
          <div class="color-grid">
            <button
              v-for="color in accentColors"
              :key="color.id"
              @click="setAccentColor(color.id)"
              :class="['color-option', { active: currentAccentColor === color.id }]"
              :style="{ backgroundColor: color.value }"
            >
              <CheckIcon v-if="currentAccentColor === color.id" class="check-icon-small check-icon-white" />
            </button>
          </div>
        </div>

        <div class="setting-group">
          <label class="setting-label">Wallpaper</label>
          <div class="wallpaper-grid">
            <button
              v-for="wallpaper in wallpapers"
              :key="wallpaper.id"
              @click="setWallpaper(wallpaper.id)"
              :class="['wallpaper-option', { active: currentWallpaper === wallpaper.id }]"
            >
              <img :src="wallpaper.thumbnail" :alt="wallpaper.name" />
              <div class="wallpaper-overlay">
                <CheckIcon v-if="currentWallpaper === wallpaper.id" class="check-icon-large check-icon-white" />
              </div>
            </button>
          </div>
        </div>
      </div>

      <!-- Desktop Settings -->
      <div v-if="activeSection === 'desktop'" class="settings-section">
        <h2 class="section-title">Desktop</h2>
        
        <div class="setting-group">
          <div class="setting-item">
            <div>
              <label class="setting-label">Show desktop icons</label>
              <p class="setting-description">Display application shortcuts on the desktop</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="desktopSettings.showIcons" @change="updateDesktopSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>

        <div class="setting-group">
          <div class="setting-item">
            <div>
              <label class="setting-label">Auto-arrange icons</label>
              <p class="setting-description">Automatically organize desktop icons</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="desktopSettings.autoArrange" @change="updateDesktopSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>

        <div class="setting-group">
          <label class="setting-label">Icon size</label>
          <div class="slider-container">
            <input
              type="range"
              min="50"
              max="120"
              v-model="desktopSettings.iconSize"
              @input="updateDesktopSettings"
              class="slider"
            >
            <span class="slider-value">{{ desktopSettings.iconSize }}px</span>
          </div>
        </div>
      </div>

      <!-- System Settings -->
      <div v-if="activeSection === 'system'" class="settings-section">
        <h2 class="section-title">System</h2>
        
        <div class="setting-group">
          <div class="setting-item">
            <div>
              <label class="setting-label">Notifications</label>
              <p class="setting-description">Allow desktop notifications</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="systemSettings.notifications" @change="updateSystemSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>

        <div class="setting-group">
          <div class="setting-item">
            <div>
              <label class="setting-label">Sound effects</label>
              <p class="setting-description">Play sounds for system events</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="systemSettings.soundEffects" @change="updateSystemSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>

        <div class="setting-group">
          <label class="setting-label">Language</label>
          <select v-model="systemSettings.language" @change="updateSystemSettings" class="select-input">
            <option value="en">English</option>
            <option value="es">Español</option>
            <option value="fr">Français</option>
            <option value="de">Deutsch</option>
            <option value="it">Italiano</option>
          </select>
        </div>
      </div>

      <!-- Privacy Settings -->
      <div v-if="activeSection === 'privacy'" class="settings-section">
        <h2 class="section-title">Privacy</h2>
        
        <div class="setting-group">
          <div class="setting-item">
            <div>
              <label class="setting-label">Analytics</label>
              <p class="setting-description">Help improve the experience by sharing usage data</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="privacySettings.analytics" @change="updatePrivacySettings">
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>

        <div class="setting-group">
          <div class="setting-item">
            <div>
              <label class="setting-label">Crash reports</label>
              <p class="setting-description">Automatically send crash reports to help fix bugs</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="privacySettings.crashReports" @change="updatePrivacySettings">
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>
      </div>

      <!-- Integrations Section -->
      <div v-if="activeSection === 'integrations'" class="settings-section">
        <h2 class="section-title">Integrations</h2>
        
        <!-- Alien Intelligence Section -->
        <div class="setting-group">
          <div class="integration-header">
            <div class="integration-icon">🤖</div>
            <div class="integration-info">
              <h3 class="integration-title">Alien Intelligence</h3>
              <p class="integration-description">Configure AI assistant settings and capabilities</p>
            </div>
          </div>

          <!-- Default Settings Toggle -->
          <div class="setting-item">
            <div>
              <label class="setting-label">Default Settings</label>
              <p class="setting-description">Use system default AI configuration</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aiSettings.useDefaults" @change="updateAiSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>

          <!-- Custom AI Model Selection (shown when defaults are off) -->
          <div v-if="!aiSettings.useDefaults" class="custom-ai-section">
            <div class="setting-item">
              <div>
                <label class="setting-label">AI Model</label>
                <p class="setting-description">Select your preferred AI model</p>
              </div>
              <select v-model="aiSettings.customModel" @change="updateAiSettings" class="select-input">
                <optgroup v-for="provider in ['OpenAI', 'Anthropic', 'Google', 'Meta']" :key="provider" :label="provider">
                  <option 
                    v-for="model in aiModels.filter((m: { provider: string }) => m.provider === provider)" 
                    :key="model.id" 
                    :value="model.id"
                  >
                    {{ model.name }} - {{ model.description }}
                  </option>
                </optgroup>
              </select>
            </div>

            <div class="setting-item">
              <div>
                <label class="setting-label">API Key</label>
                <p class="setting-description">Enter your API key for the selected model</p>
              </div>
              <div class="api-key-input">
                <input 
                  type="password" 
                  v-model="aiSettings.apiKey" 
                  @input="updateAiSettings"
                  placeholder="sk-..." 
                  class="text-input"
                >
                <button class="test-api-button" @click="testApiKey">Test</button>
              </div>
            </div>
          </div>

          <!-- Privacy Selector -->
          <div class="setting-item">
            <div>
              <label class="setting-label">Privacy Level</label>
              <p class="setting-description">Control AI access to your content and actions</p>
            </div>
          </div>

          <div class="privacy-levels">
            <div 
              v-for="level in privacyLevels" 
              :key="level.id"
              :class="['privacy-level-card', { active: aiSettings.privacyLevel === level.id }]"
              @click="setPrivacyLevel(level.id)"
            >
              <div class="privacy-level-header">
                <div class="privacy-level-icon">{{ level.icon }}</div>
                <div class="privacy-level-info">
                  <h4 class="privacy-level-name">{{ level.name }}</h4>
                  <p class="privacy-level-description">{{ level.description }}</p>
                </div>
                <div class="privacy-level-check">
                  <CheckIcon v-if="aiSettings.privacyLevel === level.id" class="check-icon-small check-icon-blue" />
                </div>
              </div>
              <div class="privacy-level-features">
                <ul>
                  <li v-for="feature in level.features" :key="feature" class="privacy-feature">
                    <span class="feature-bullet">•</span>
                    {{ feature }}
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Website Settings Section -->
      <div v-if="activeSection === 'website'" class="settings-section">
        <h2 class="section-title">Website Settings</h2>
        
        <div class="setting-group">
          <label class="setting-label">Store Identity</label>
          
          <div class="setting-item">
            <div>
              <label class="setting-label">Store Name</label>
              <p class="setting-description">Your store's display name</p>
            </div>
            <input 
              type="text" 
              v-model="aimeosSettings.website.storeName" 
              @input="updateAimeosSettings"
              placeholder="My Store" 
              class="text-input"
            >
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Store Logo</label>
              <p class="setting-description">Upload your store logo (recommended: 200x50px)</p>
            </div>
            <div class="logo-upload">
              <input type="file" ref="logoInput" @change="uploadLogo" accept="image/*" class="file-input-hidden">
              <button @click="$refs.logoInput?.click()" class="upload-button">
                <span v-if="!aimeosSettings.website.logoUrl">Choose Logo</span>
                <span v-else>Change Logo</span>
              </button>
              <img v-if="aimeosSettings.website.logoUrl" :src="aimeosSettings.website.logoUrl" alt="Store Logo" class="logo-preview">
            </div>
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Store Description</label>
              <p class="setting-description">Brief description of your store</p>
            </div>
            <textarea 
              v-model="aimeosSettings.website.description" 
              @input="updateAimeosSettings"
              placeholder="Describe your store and what you sell..."
              rows="3"
              class="text-input"
            ></textarea>
          </div>
        </div>

        <div class="setting-group">
          <label class="setting-label">Contact Information</label>
          
          <div class="setting-item">
            <div>
              <label class="setting-label">Contact Email</label>
              <p class="setting-description">Email for customer inquiries</p>
            </div>
            <input 
              type="email" 
              v-model="aimeosSettings.website.contactEmail" 
              @input="updateAimeosSettings"
              placeholder="contact@example.com" 
              class="text-input"
            >
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Phone Number</label>
              <p class="setting-description">Contact phone number</p>
            </div>
            <input 
              type="tel" 
              v-model="aimeosSettings.website.phone" 
              @input="updateAimeosSettings"
              placeholder="+1 (555) 123-4567" 
              class="text-input"
            >
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Address</label>
              <p class="setting-description">Business address</p>
            </div>
            <textarea 
              v-model="aimeosSettings.website.address" 
              @input="updateAimeosSettings"
              placeholder="123 Business St, City, State 12345"
              rows="3"
              class="text-input"
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Products Section -->
      <div v-if="activeSection === 'products'" class="settings-section">
        <h2 class="section-title">Products</h2>
        
        <div class="setting-group">
          <label class="setting-label">Catalog Settings</label>
          
          <div class="setting-item">
            <div>
              <label class="setting-label">Products per page</label>
              <p class="setting-description">Number of products to display per page</p>
            </div>
            <select v-model="aimeosSettings.products.perPage" @change="updateAimeosSettings" class="select-input">
              <option value="12">12</option>
              <option value="24">24</option>
              <option value="36">36</option>
              <option value="48">48</option>
            </select>
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Default sorting</label>
              <p class="setting-description">How products are sorted by default</p>
            </div>
            <select v-model="aimeosSettings.products.defaultSort" @change="updateAimeosSettings" class="select-input">
              <option value="relevance">Relevance</option>
              <option value="name">Name</option>
              <option value="price-asc">Price: Low to High</option>
              <option value="price-desc">Price: High to Low</option>
              <option value="created">Newest First</option>
            </select>
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Enable product reviews</label>
              <p class="setting-description">Allow customers to leave product reviews</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.products.enableReviews" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Enable wishlist</label>
              <p class="setting-description">Allow customers to save products to wishlist</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.products.enableWishlist" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>

        <div class="setting-group">
          <label class="setting-label">Inventory Management</label>
          
          <div class="setting-item">
            <div>
              <label class="setting-label">Track inventory</label>
              <p class="setting-description">Monitor product stock levels</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.products.trackInventory" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Low stock threshold</label>
              <p class="setting-description">Alert when stock falls below this number</p>
            </div>
            <input 
              type="number" 
              v-model.number="aimeosSettings.products.lowStockThreshold" 
              @input="updateAimeosSettings"
              min="0"
              class="text-input"
            >
          </div>
        </div>
      </div>

      <!-- Payments Section -->
      <div v-if="activeSection === 'payments'" class="settings-section">
        <h2 class="section-title">Payments</h2>
        
        <div class="setting-group">
          <label class="setting-label">Payment Methods</label>
          
          <div class="setting-item">
            <div>
              <label class="setting-label">Accept credit cards</label>
              <p class="setting-description">Enable credit card payments via Stripe</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.payments.enableCards" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Accept PayPal</label>
              <p class="setting-description">Enable PayPal payments</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.payments.enablePaypal" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Accept bank transfers</label>
              <p class="setting-description">Allow direct bank transfer payments</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.payments.enableBankTransfer" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>

        <div class="setting-group">
          <label class="setting-label">Currency Settings</label>
          
          <div class="setting-item">
            <div>
              <label class="setting-label">Default currency</label>
              <p class="setting-description">Primary currency for your store</p>
            </div>
            <select v-model="aimeosSettings.payments.defaultCurrency" @change="updateAimeosSettings" class="select-input">
              <option value="USD">USD - US Dollar</option>
              <option value="EUR">EUR - Euro</option>
              <option value="GBP">GBP - British Pound</option>
              <option value="CAD">CAD - Canadian Dollar</option>
              <option value="AUD">AUD - Australian Dollar</option>
            </select>
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Tax calculation</label>
              <p class="setting-description">How to calculate taxes</p>
            </div>
            <select v-model="aimeosSettings.payments.taxCalculation" @change="updateAimeosSettings" class="select-input">
              <option value="none">No tax calculation</option>
              <option value="fixed">Fixed rate</option>
              <option value="location">Based on customer location</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Shipping Section -->
      <div v-if="activeSection === 'shipping'" class="settings-section">
        <h2 class="section-title">Shipping</h2>
        
        <div class="setting-group">
          <label class="setting-label">Shipping Options</label>
          
          <div class="setting-item">
            <div>
              <label class="setting-label">Free shipping threshold</label>
              <p class="setting-description">Minimum order amount for free shipping</p>
            </div>
            <input 
              type="number" 
              v-model.number="aimeosSettings.shipping.freeShippingThreshold" 
              @input="updateAimeosSettings"
              min="0"
              step="0.01"
              class="text-input"
            >
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Standard shipping cost</label>
              <p class="setting-description">Cost for standard shipping</p>
            </div>
            <input 
              type="number" 
              v-model.number="aimeosSettings.shipping.standardCost" 
              @input="updateAimeosSettings"
              min="0"
              step="0.01"
              class="text-input"
            >
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Express shipping cost</label>
              <p class="setting-description">Cost for express shipping</p>
            </div>
            <input 
              type="number" 
              v-model.number="aimeosSettings.shipping.expressCost" 
              @input="updateAimeosSettings"
              min="0"
              step="0.01"
              class="text-input"
            >
          </div>
        </div>

        <div class="setting-group">
          <label class="setting-label">Delivery Settings</label>
          
          <div class="setting-item">
            <div>
              <label class="setting-label">Processing time</label>
              <p class="setting-description">Days needed to process orders</p>
            </div>
            <select v-model="aimeosSettings.shipping.processingTime" @change="updateAimeosSettings" class="select-input">
              <option value="1">1 business day</option>
              <option value="2">2 business days</option>
              <option value="3">3 business days</option>
              <option value="5">5 business days</option>
              <option value="7">1 week</option>
            </select>
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Enable local pickup</label>
              <p class="setting-description">Allow customers to pick up orders</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.shipping.enablePickup" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>
      </div>

      <!-- Customers & Privacy Section -->
      <div v-if="activeSection === 'customers'" class="settings-section">
        <h2 class="section-title">Customers & Privacy</h2>
        
        <div class="setting-group">
          <label class="setting-label">Customer Accounts</label>
          
          <div class="setting-item">
            <div>
              <label class="setting-label">Require account creation</label>
              <p class="setting-description">Force customers to create accounts before purchasing</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.customers.requireAccount" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Enable guest checkout</label>
              <p class="setting-description">Allow purchases without account creation</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.customers.enableGuestCheckout" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Email verification required</label>
              <p class="setting-description">Require email verification for new accounts</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.customers.requireEmailVerification" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>

        <div class="setting-group">
          <label class="setting-label">Privacy Settings</label>
          
          <div class="setting-item">
            <div>
              <label class="setting-label">Cookie consent</label>
              <p class="setting-description">Show cookie consent banner</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.customers.showCookieConsent" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Data retention period</label>
              <p class="setting-description">How long to keep customer data</p>
            </div>
            <select v-model="aimeosSettings.customers.dataRetentionPeriod" @change="updateAimeosSettings" class="select-input">
              <option value="30">30 days</option>
              <option value="90">90 days</option>
              <option value="365">1 year</option>
              <option value="1095">3 years</option>
              <option value="unlimited">Unlimited</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Emails Section -->
      <div v-if="activeSection === 'emails'" class="settings-section">
        <h2 class="section-title">Emails</h2>
        
        <div class="setting-group">
          <label class="setting-label">Order Notifications</label>
          
          <div class="setting-item">
            <div>
              <label class="setting-label">Order confirmation</label>
              <p class="setting-description">Send confirmation emails when orders are placed</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.emails.sendOrderConfirmation" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Shipping notifications</label>
              <p class="setting-description">Send emails when orders are shipped</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.emails.sendShippingNotification" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Delivery confirmations</label>
              <p class="setting-description">Send emails when orders are delivered</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.emails.sendDeliveryConfirmation" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>

        <div class="setting-group">
          <label class="setting-label">Marketing Emails</label>
          
          <div class="setting-item">
            <div>
              <label class="setting-label">Newsletter signup</label>
              <p class="setting-description">Show newsletter signup options</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.emails.enableNewsletter" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Promotional emails</label>
              <p class="setting-description">Send promotional and marketing emails</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.emails.enablePromotional" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Abandoned cart emails</label>
              <p class="setting-description">Send reminders for abandoned shopping carts</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.emails.sendAbandonedCartReminders" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>
      </div>

      <!-- Billing Section -->
      <div v-if="activeSection === 'billing'" class="settings-section">
        <h2 class="section-title">Billing</h2>
        
        <div class="setting-group">
          <label class="setting-label">Invoice Settings</label>
          
          <div class="setting-item">
            <div>
              <label class="setting-label">Invoice prefix</label>
              <p class="setting-description">Prefix for invoice numbers</p>
            </div>
            <input 
              type="text" 
              v-model="aimeosSettings.billing.invoicePrefix" 
              @input="updateAimeosSettings"
              placeholder="INV-" 
              class="text-input"
            >
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Auto-generate invoices</label>
              <p class="setting-description">Automatically create invoices for paid orders</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.billing.autoGenerateInvoices" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Include logo on invoices</label>
              <p class="setting-description">Show store logo on generated invoices</p>
            </div>
            <label class="toggle-switch">
              <input type="checkbox" v-model="aimeosSettings.billing.includeLogoOnInvoices" @change="updateAimeosSettings">
              <span class="toggle-slider"></span>
            </label>
          </div>
        </div>

        <div class="setting-group">
          <label class="setting-label">Terms & Conditions</label>
          
          <div class="setting-item">
            <div>
              <label class="setting-label">Terms of service URL</label>
              <p class="setting-description">Link to your terms of service</p>
            </div>
            <input 
              type="url" 
              v-model="aimeosSettings.billing.termsOfServiceUrl" 
              @input="updateAimeosSettings"
              placeholder="https://yourstore.com/terms" 
              class="text-input"
            >
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Privacy policy URL</label>
              <p class="setting-description">Link to your privacy policy</p>
            </div>
            <input 
              type="url" 
              v-model="aimeosSettings.billing.privacyPolicyUrl" 
              @input="updateAimeosSettings"
              placeholder="https://yourstore.com/privacy" 
              class="text-input"
            >
          </div>

          <div class="setting-item">
            <div>
              <label class="setting-label">Return policy URL</label>
              <p class="setting-description">Link to your return policy</p>
            </div>
            <input 
              type="url" 
              v-model="aimeosSettings.billing.returnPolicyUrl" 
              @input="updateAimeosSettings"
              placeholder="https://yourstore.com/returns" 
              class="text-input"
            >
          </div>
        </div>
      </div>

      <!-- About Section -->
      <div v-if="activeSection === 'about'" class="settings-section">
        <h2 class="section-title">About</h2>
        
        <div class="about-content">
          <div class="app-logo">
            <div class="logo-placeholder">OS</div>
          </div>
          
          <div class="app-info">
            <h3>Desktop OS</h3>
            <p>Version 1.0.0</p>
            <p class="build-info">Built with Vue 3, Laravel, and TypeScript</p>
          </div>

          <div class="system-info">
            <div class="info-item">
              <span class="info-label">Team:</span>
              <span class="info-value">{{ currentTeam?.name || 'Personal' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">User:</span>
              <span class="info-value">{{ currentUser?.name || 'Unknown' }}</span>
            </div>
            <div class="info-item">
              <span class="info-label">Browser:</span>
              <span class="info-value">{{ browserInfo }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import {
  PaintBrushIcon,
  ComputerDesktopIcon,
  CogIcon,
  ShieldCheckIcon,
  InformationCircleIcon,
  CheckIcon,
  LightBulbIcon,
  GlobeAltIcon,
  SquaresPlusIcon,
  CreditCardIcon,
  TruckIcon,
  UsersIcon,
  EnvelopeIcon,
  DocumentTextIcon
} from '@heroicons/vue/24/outline'
import { useAppMetadata } from '@/composables/useAppMetadata'

interface Props {
  windowId: string
  windowData?: Record<string, any>
}

const props = defineProps<Props>()

const emit = defineEmits<{
  updateTitle: [title: string]
  updateData: [data: Record<string, any>]
}>()

// Set app metadata
const { updateMetadata } = useAppMetadata()
updateMetadata({
  title: 'Settings',
  icon: 'fas fa-cog',
  iconClass: 'green-icon'
})

// State
const activeSection = ref('appearance')

// Example: Apps can update their title dynamically
// In a real implementation, you would update metadata based on app state changes
const currentTheme = ref('dark')
const currentAccentColor = ref('blue')
const currentWallpaper = ref('default')

const desktopSettings = ref({
  showIcons: true,
  autoArrange: false,
  iconSize: 80
})

const systemSettings = ref({
  notifications: true,
  soundEffects: true,
  language: 'en'
})

const privacySettings = ref({
  analytics: false,
  crashReports: true
})

const aiSettings = ref({
  useDefaults: true,
  customModel: 'gpt-4',
  apiKey: '',
  privacyLevel: 'public' // 'public', 'private', 'agent'
})

const aimeosSettings = ref({
  website: {
    storeName: '',
    logoUrl: '',
    description: '',
    contactEmail: '',
    phone: '',
    address: ''
  },
  products: {
    perPage: 24,
    defaultSort: 'relevance',
    enableReviews: true,
    enableWishlist: true,
    trackInventory: true,
    lowStockThreshold: 10
  },
  payments: {
    enableCards: true,
    enablePaypal: false,
    enableBankTransfer: false,
    defaultCurrency: 'USD',
    taxCalculation: 'none'
  },
  shipping: {
    freeShippingThreshold: 100,
    standardCost: 9.99,
    expressCost: 19.99,
    processingTime: '2',
    enablePickup: false
  },
  customers: {
    requireAccount: false,
    enableGuestCheckout: true,
    requireEmailVerification: true,
    showCookieConsent: true,
    dataRetentionPeriod: '365'
  },
  emails: {
    sendOrderConfirmation: true,
    sendShippingNotification: true,
    sendDeliveryConfirmation: false,
    enableNewsletter: true,
    enablePromotional: false,
    sendAbandonedCartReminders: true
  },
  billing: {
    invoicePrefix: 'INV-',
    autoGenerateInvoices: true,
    includeLogoOnInvoices: true,
    termsOfServiceUrl: '',
    privacyPolicyUrl: '',
    returnPolicyUrl: ''
  }
})

// Mock data for current user/team
const currentUser = ref({ name: 'Demo User' })
const currentTeam = ref({ name: 'Demo Team' })

// Configuration
const sections = [
  { id: 'appearance', name: 'Appearance', icon: PaintBrushIcon },
  { id: 'desktop', name: 'Desktop', icon: ComputerDesktopIcon },
  { id: 'website', name: 'Website Settings', icon: GlobeAltIcon, category: 'aimeos' },
  { id: 'products', name: 'Products', icon: SquaresPlusIcon, category: 'aimeos' },
  { id: 'payments', name: 'Payments', icon: CreditCardIcon, category: 'aimeos' },
  { id: 'shipping', name: 'Shipping', icon: TruckIcon, category: 'aimeos' },
  { id: 'customers', name: 'Customers & Privacy', icon: UsersIcon, category: 'aimeos' },
  { id: 'emails', name: 'Emails', icon: EnvelopeIcon, category: 'aimeos' },
  { id: 'billing', name: 'Billing', icon: DocumentTextIcon, category: 'aimeos' },
  { id: 'system', name: 'System', icon: CogIcon },
  { id: 'privacy', name: 'Privacy', icon: ShieldCheckIcon },
  { id: 'integrations', name: 'Integrations', icon: LightBulbIcon },
  { id: 'about', name: 'About', icon: InformationCircleIcon }
]

const themes = [
  { id: 'light', name: 'Light' },
  { id: 'dark', name: 'Dark' },
  { id: 'auto', name: 'Auto' }
]

const accentColors = [
  { id: 'blue', name: 'Blue', value: '#3b82f6' },
  { id: 'green', name: 'Green', value: '#10b981' },
  { id: 'purple', name: 'Purple', value: '#8b5cf6' },
  { id: 'pink', name: 'Pink', value: '#ec4899' },
  { id: 'orange', name: 'Orange', value: '#f59e0b' },
  { id: 'red', name: 'Red', value: '#ef4444' }
]

const wallpapers = [
  { id: 'default', name: 'Default', thumbnail: '/img/placeholder-dark-theme.png' },
  { id: 'light', name: 'Light', thumbnail: '/img/placeholder-light-theme.png' },
  { id: 'auto', name: 'Auto', thumbnail: '/img/placeholder-auto-theme.png' }
]

const aiModels = [
  { id: 'gpt-4', name: 'GPT-4', provider: 'OpenAI', description: 'Most capable model, best for complex tasks' },
  { id: 'gpt-4-turbo', name: 'GPT-4 Turbo', provider: 'OpenAI', description: 'Faster and more efficient' },
  { id: 'gpt-3.5-turbo', name: 'GPT-3.5 Turbo', provider: 'OpenAI', description: 'Fast and cost-effective' },
  { id: 'claude-3-opus', name: 'Claude 3 Opus', provider: 'Anthropic', description: 'Advanced reasoning capabilities' },
  { id: 'claude-3-sonnet', name: 'Claude 3 Sonnet', provider: 'Anthropic', description: 'Balanced performance and speed' },
  { id: 'claude-3-haiku', name: 'Claude 3 Haiku', provider: 'Anthropic', description: 'Fast and lightweight' },
  { id: 'gemini-pro', name: 'Gemini Pro', provider: 'Google', description: 'Multimodal AI capabilities' },
  { id: 'llama-3-70b', name: 'Llama 3 70B', provider: 'Meta', description: 'Open source, high performance' }
]

const privacyLevels = [
  { 
    id: 'public', 
    name: 'Public', 
    description: 'Access only website data for answers',
    icon: '🌐',
    features: ['Website content access', 'Public data only', 'No file access', 'No app data access']
  },
  { 
    id: 'private', 
    name: 'Private', 
    description: 'Access files and all app info for detailed answers',
    icon: '🔒',
    features: ['Full file access', 'App data access', 'Reports access', 'Analytics data', 'No action execution']
  },
  { 
    id: 'agent', 
    name: 'Agent', 
    description: 'Take actions on your behalf when requested',
    icon: '🤖',
    features: ['Full system access', 'Action execution', 'Command running', 'File modifications', 'App control']
  }
]

// Computed
const browserInfo = computed(() => {
  const userAgent = navigator.userAgent
  if (userAgent.includes('Chrome')) return 'Chrome'
  if (userAgent.includes('Firefox')) return 'Firefox'
  if (userAgent.includes('Safari')) return 'Safari'
  if (userAgent.includes('Edge')) return 'Edge'
  return 'Unknown'
})

// Methods
const setTheme = async (theme: string) => {
  currentTheme.value = theme
  await saveSettings()
  
  // Apply theme to document
  const root = document.documentElement
  if (theme === 'dark') {
    root.classList.add('dark')
  } else if (theme === 'light') {
    root.classList.remove('dark')
  } else {
    // Auto theme
    const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches
    root.classList.toggle('dark', isDark)
  }
}

const setAccentColor = async (color: string) => {
  currentAccentColor.value = color
  await saveSettings()
  
  // Apply accent color to CSS variables
  const colorValue = accentColors.find(c => c.id === color)?.value
  if (colorValue) {
    document.documentElement.style.setProperty('--accent-color', colorValue)
  }
}

const setWallpaper = async (wallpaper: string) => {
  currentWallpaper.value = wallpaper
  await saveSettings()
}

const updateDesktopSettings = async () => {
  await saveSettings()
}

const updateSystemSettings = async () => {
  await saveSettings()
}

const updatePrivacySettings = async () => {
  await saveSettings()
}

const updateAiSettings = async () => {
  await saveSettings()
}

const updateAimeosSettings = async () => {
  await saveSettings()
}

const uploadLogo = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  
  if (!file) return
  
  // Validate file type
  if (!file.type.startsWith('image/')) {
    alert('Please select an image file')
    return
  }
  
  // Validate file size (max 2MB)
  if (file.size > 2 * 1024 * 1024) {
    alert('Please select an image smaller than 2MB')
    return
  }
  
  try {
    const formData = new FormData()
    formData.append('logo', file)
    
    const response = await fetch('/api/aimeos-settings/upload-logo', {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'include',
      body: formData
    })
    
    const result = await response.json()
    
    if (response.ok && result.success) {
      aimeosSettings.value!.website.logoUrl = result.url
      await updateAimeosSettings()
    } else {
      alert(result.error || 'Failed to upload logo')
    }
  } catch (error) {
    console.error('Logo upload failed:', error)
    alert('Failed to upload logo')
  }
}

const setPrivacyLevel = async (level: string) => {
  aiSettings.value!.privacyLevel = level
  await saveSettings()
}

const testApiKey = async () => {
  if (!aiSettings.value?.apiKey) {
    alert('Please enter an API key first')
    return
  }
  
  try {
    const response = await fetch('/api/ai-settings/test-api-key', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'include',
      body: JSON.stringify({
        model: aiSettings.value?.customModel,
        api_key: aiSettings.value?.apiKey
      })
    })
    
    const result = await response.json()
    
    if (response.ok && result.success) {
      alert('API key is valid!')
    } else {
      alert(result.error || result.message || 'API key test failed')
    }
  } catch (error) {
    console.error('API key test failed:', error)
    alert('Failed to test API key')
  }
}

const saveSettings = async () => {
  const settings = {
    theme: currentTheme.value,
    accentColor: currentAccentColor.value,
    wallpaper: currentWallpaper.value,
    desktop: desktopSettings.value,
    system: systemSettings.value,
    privacy: privacySettings.value
  }
  
  try {
    // Save regular desktop settings
    const response = await fetch('/api/desktop/settings', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'include',
      body: JSON.stringify(settings)
    })
    
    if (!response.ok) {
      throw new Error('Failed to save settings')
    }

    // Save AI settings separately
    await saveAiSettings()
    
    emit('updateData', { ...settings, ai: aiSettings.value })
    
  } catch (error) {
    console.error('Failed to save settings:', error)
  }
}

const saveAiSettings = async () => {
  try {
    const response = await fetch('/api/ai-settings', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'include',
      body: JSON.stringify({
        use_defaults: aiSettings.value?.useDefaults,
        custom_model: aiSettings.value?.customModel,
        api_key: aiSettings.value?.apiKey,
        privacy_level: aiSettings.value?.privacyLevel
      })
    })
    
    if (!response.ok) {
      throw new Error('Failed to save AI settings')
    }
    
  } catch (error) {
    console.error('Failed to save AI settings:', error)
    throw error
  }
}

const loadSettings = async () => {
  try {
    const response = await fetch('/api/desktop/settings', {
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const settings = await response.json()
      
      currentTheme.value = settings.theme || 'dark'
      currentAccentColor.value = settings.accentColor || 'blue'
      currentWallpaper.value = settings.wallpaper || 'default'
      
      if (settings.desktop) {
        desktopSettings.value = { ...desktopSettings.value, ...settings.desktop }
      }
      
      if (settings.system) {
        systemSettings.value = { ...systemSettings.value, ...settings.system }
      }
      
      if (settings.privacy) {
        privacySettings.value = { ...privacySettings.value, ...settings.privacy }
      }
      
      // Load AI settings separately
      await loadAiSettings()
      
      // Apply loaded theme
      if (currentTheme.value && currentAccentColor.value) {
        await setTheme(currentTheme.value)
        await setAccentColor(currentAccentColor.value)
      }
    }
  } catch (error) {
    console.error('Failed to load settings:', error)
  }
}

const loadAiSettings = async () => {
  try {
    const response = await fetch('/api/ai-settings', {
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const result = await response.json()
      
      if (result.success && result.data) {
        aiSettings.value = {
          useDefaults: result.data.use_defaults ?? true,
          customModel: result.data.custom_model ?? 'gpt-4',
          apiKey: '', // Never load the actual API key for security
          privacyLevel: result.data.privacy_level ?? 'public'
        }
      }
    }
  } catch (error) {
    console.error('Failed to load AI settings:', error)
  }
}

// Lifecycle
onMounted(() => {
  emit('updateTitle', 'Settings')
  
  // Restore state
  if (props.windowData) {
    activeSection.value = props.windowData.activeSection || 'appearance'
  }
  
  // Load current settings
  loadSettings()
})
</script>

<style scoped>
.settings-app {
  width: 100%;
  height: 100%;
  background-color: #fff;
  display: flex;
  flex-direction: column;
}

.settings-sidebar {
  width: 256px;
  background-color: #f5f5f5;
  border-right: 1px solid #e0e0e0;
  display: flex;
  flex-shrink: 0;
}

.settings-nav {
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.nav-item {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  text-align: left;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.nav-item:hover {
  background-color: #e0e0e0;
}

.nav-item.active {
  background-color: #e0e0e0;
  color: #1976d2;
}

.nav-icon {
  width: 24px;
  height: 24px;
}

.settings-content {
  flex: 1;
  overflow-y: auto;
}

.settings-section {
  padding: 24px;
  max-width: 600px;
}

.section-title {
  font-size: 24px;
  font-weight: 600;
  margin-bottom: 24px;
  color: #333;
}

.setting-group {
  margin-bottom: 24px;
  padding: 16px;
  background-color: #f9f9f9;
  border-radius: 12px;
}

.setting-group.dark {
  background-color: #424242;
}

.setting-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #555;
  margin-bottom: 8px;
}

.setting-group.dark .setting-label {
  color: #b0b0b0;
}

.setting-description {
  font-size: 13px;
  color: #888;
}

.setting-group.dark .setting-description {
  color: #a0a0a0;
}

.setting-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.theme-options {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.theme-option {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
  padding: 12px;
  border: 2px solid transparent;
  border-radius: 12px;
  cursor: pointer;
  transition: border-color 0.2s ease;
}

.theme-option:hover {
  border-color: #e0e0e0;
}

.theme-option.active {
  border-color: #1976d2;
}

.theme-preview {
  width: 64px;
  height: 48px;
  border-radius: 10px;
  overflow: hidden;
}

.theme-preview.light {
  background-color: #fff;
}

.theme-preview.dark {
  background-color: #121212;
}

.theme-preview.auto {
  background: linear-gradient(to right, #fff, #121212);
}

.theme-header {
  height: 12px;
}

.theme-preview.light .theme-header {
  background-color: #f0f0f0;
}

.theme-preview.dark .theme-header {
  background-color: #303030;
}

.theme-preview.auto .theme-header {
  background: linear-gradient(to right, #f0f0f0, #303030);
}

.theme-body {
  height: 36px;
}

.theme-preview.light .theme-body {
  background-color: #fff;
}

.theme-preview.dark .theme-body {
  background-color: #121212;
}

.theme-preview.auto .theme-body {
  background: linear-gradient(to right, #fff, #121212);
}

.color-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 12px;
}

.color-option {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  border: 4px solid #fff;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: transform 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.color-option:hover {
  transform: scale(1.1);
}

.color-option.active {
  border: 2px solid #1976d2;
  box-shadow: 0 0 0 4px #e0e0e0;
}

.wallpaper-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.wallpaper-option {
  position: relative;
  border-radius: 12px;
  overflow: hidden;
  border: 2px solid transparent;
  cursor: pointer;
  transition: border-color 0.2s ease;
}

.wallpaper-option:hover {
  border-color: #e0e0e0;
}

.wallpaper-option.active {
  border-color: #1976d2;
}

.wallpaper-option img {
  width: 100%;
  height: 80px;
  object-fit: cover;
}

.wallpaper-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.wallpaper-option.active .wallpaper-overlay {
  opacity: 1;
}

.toggle-switch {
  position: relative;
  width: 48px;
  height: 28px;
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
  background-color: #ccc;
  border-radius: 28px;
  transition: background-color 0.2s ease;
}

.toggle-slider:before {
  content: "";
  height: 22px;
  width: 22px;
  left: 3px;
  top: 3px;
  background-color: #fff;
  border-radius: 50%;
  transition: transform 0.2s ease;
}

.toggle-switch input:checked + .toggle-slider {
  background-color: #1976d2;
}

.toggle-switch input:checked + .toggle-slider:before {
  transform: translateX(20px);
}

.slider-container {
  display: flex;
  align-items: center;
  gap: 16px;
}

.slider {
  flex: 1;
  height: 8px;
  background-color: #e0e0e0;
  border-radius: 4px;
  appearance: none;
  -webkit-appearance: none;
}

.slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  width: 24px;
  height: 24px;
  background-color: #1976d2;
  border-radius: 50%;
  cursor: pointer;
  margin-top: -8px;
}

.slider::-moz-range-thumb {
  width: 24px;
  height: 24px;
  background-color: #1976d2;
  border-radius: 50%;
  cursor: pointer;
}

.slider-value {
  font-size: 14px;
  font-weight: 500;
  color: #666;
  min-width: 50px;
}

.select-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ccc;
  border-radius: 8px;
  background-color: #fff;
  color: #333;
  font-size: 14px;
  cursor: pointer;
  transition: border-color 0.2s ease;
}

.select-input:focus {
  outline: none;
  border-color: #1976d2;
  box-shadow: 0 0 0 2px #1976d2;
}

.select-input.dark {
  background-color: #424242;
  color: #fff;
  border-color: #666;
}

.select-input.dark:focus {
  border-color: #1976d2;
  box-shadow: 0 0 0 2px #1976d2;
}

.about-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 24px;
}

.app-logo {
  display: flex;
  justify-content: center;
}

.logo-placeholder {
  width: 80px;
  height: 80px;
  background-color: #1976d2;
  color: #fff;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 32px;
  font-weight: bold;
}

.app-info {
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.app-info h3 {
  font-size: 20px;
  font-weight: 600;
  color: #333;
}

.app-info.dark h3 {
  color: #fff;
}

.build-info {
  font-size: 13px;
  color: #888;
}

.system-info {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #eee;
}

.info-item.dark {
  border-bottom-color: #666;
}

.info-label {
  font-weight: 500;
  color: #555;
}

.info-item.dark .info-label {
  color: #b0b0b0;
}

.info-value {
  color: #333;
}

.info-item.dark .info-value {
  color: #fff;
}

/* AI Settings Styles */
.integration-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
  padding: 16px;
  background: linear-gradient(to right, #e0f7fa, #e0f2f7);
  border: 1px solid #b2ebf2;
  border-radius: 12px;
}

.integration-header.dark {
  background: linear-gradient(to right, #00796b, #00695c);
  border-color: #004d40;
}

.integration-icon {
  font-size: 48px;
}

.integration-info {
  flex: 1;
}

.integration-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
}

.integration-title.dark {
  color: #fff;
}

.integration-description {
  font-size: 13px;
  color: #666;
}

.integration-description.dark {
  color: #a0a0a0;
}

.custom-ai-section {
  margin-top: 16px;
  padding: 16px;
  background-color: #f0f7f7;
  border-left: 4px solid #1976d2;
  border-radius: 12px;
}

.custom-ai-section.dark {
  background-color: #263238;
  border-left-color: #00796b;
}

.api-key-input {
  display: flex;
  gap: 12px;
  align-items: center;
}

.text-input {
  flex: 1;
  padding: 8px 12px;
  border: 1px solid #ccc;
  border-radius: 8px;
  background-color: #fff;
  color: #333;
  font-size: 14px;
  transition: border-color 0.2s ease;
}

.text-input:focus {
  outline: none;
  border-color: #1976d2;
  box-shadow: 0 0 0 2px #1976d2;
}

.text-input.dark {
  background-color: #424242;
  color: #fff;
  border-color: #666;
}

.text-input.dark:focus {
  border-color: #1976d2;
  box-shadow: 0 0 0 2px #1976d2;
}

.test-api-button {
  padding: 8px 16px;
  background-color: #1976d2;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.test-api-button:hover {
  background-color: #1565c0;
}

.test-api-button:focus {
  outline: none;
  box-shadow: 0 0 0 2px #1976d2;
}

.test-api-button:disabled {
  background-color: #90caf9;
  cursor: not-allowed;
}

.privacy-levels {
  display: grid;
  gap: 12px;
  margin-top: 16px;
}

.privacy-level-card {
  padding: 16px;
  border: 2px solid #e0e0e0;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  background-color: #fff;
}

.privacy-level-card.dark {
  background-color: #424242;
  border-color: #666;
}

.privacy-level-card:hover {
  border-color: #1976d2;
}

.privacy-level-card.dark:hover {
  border-color: #00796b;
}

.privacy-level-card.active {
  border-color: #1976d2;
  background-color: #e0f7fa;
}

.privacy-level-card.dark.active {
  background-color: #004d40;
  border-color: #00796b;
}

.privacy-level-header {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.privacy-level-icon {
  font-size: 32px;
}

.privacy-level-info {
  flex: 1;
}

.privacy-level-name {
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.privacy-level-name.dark {
  color: #fff;
}

.privacy-level-description {
  font-size: 13px;
  color: #666;
  margin-top: 4px;
}

.privacy-level-description.dark {
  color: #a0a0a0;
}

.privacy-level-check {
  flex-shrink: 0;
}

.privacy-level-features {
  margin-left: 24px;
  padding-left: 16px;
  border-left: 2px solid #e0e0e0;
}

.privacy-level-features.dark {
  border-left-color: #666;
}

.privacy-level-features ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.privacy-feature {
  font-size: 13px;
  color: #666;
  display: flex;
  align-items: center;
  gap: 8px;
}

.feature-bullet {
  font-weight: bold;
  color: #1976d2;
}

.privacy-feature.dark {
  color: #a0a0a0;
}

.select-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #ccc;
  border-radius: 8px;
  background-color: #fff;
  color: #333;
  font-size: 14px;
  cursor: pointer;
  transition: border-color 0.2s ease;
}

.select-input:focus {
  outline: none;
  border-color: #1976d2;
  box-shadow: 0 0 0 2px #1976d2;
}

.select-input.dark {
  background-color: #424242;
  color: #fff;
  border-color: #666;
}

.select-input.dark:focus {
  border-color: #1976d2;
  box-shadow: 0 0 0 2px #1976d2;
}

.check-icon-small {
  width: 16px; /* w-4 */
  height: 16px; /* h-4 */
}

.check-icon-large {
  width: 24px; /* w-6 */
  height: 24px; /* h-6 */
}

.check-icon-white {
  color: #ffffff; /* text-white */
}

.check-icon-blue {
  width: 20px; /* w-5 */
  height: 20px; /* h-5 */
  color: #2196f3; /* text-blue-500 */
}

.file-input-hidden {
  display: none; /* hidden */
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .settings-app {
    flex-direction: column;
  }
  
  .settings-sidebar {
    width: 100%;
  }
  
  .settings-nav {
    flex-wrap: wrap;
    padding: 8px;
    overflow-x: auto;
  }
  
  .nav-item {
    flex-shrink: 0;
  }
  
  .theme-options {
    grid-template-columns: 1fr;
  }
  
  .color-grid {
    grid-template-columns: repeat(4, 1fr);
  }
  
  .wallpaper-grid {
    grid-template-columns: 1fr;
  }
}
</style> 