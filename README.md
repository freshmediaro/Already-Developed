# OS-Style Desktop Application

A modern OS-style desktop application built with Laravel, Tenancy for Laravel (stancl/tenancy v4), [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/) used for the tenant app, meaning the tenant app is [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/)-based, while the central app is completely custom., Inertia.js, Vue 3, and TypeScript. This multi-tenant SaaS platform provides each tenant with their own isolated desktop environment.

## 🏗️ Architecture Overview

### **Frontend Architecture**
- **Vue 3** with Composition API and TypeScript
- **Inertia.js** for seamless Laravel integration
- **Modular Component System** for scalable development
- **TypeScript** for type safety and better DX
- **Vite** for fast development and optimized builds

### **Backend Architecture**
- **Laravel** as the backend framework
- **Tenancy for Laravel (stancl/tenancy v4)** for multi-database tenancy
- **[Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/)** for authentication, used for the tenant app, meaning the tenant app is [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/)-based, while the central app is completely custom.
- **Multi-tenant isolation** with separate databases per tenant

## 📁 Project Structure

```
├── resources/
│   ├── js/
│   │   ├── app.ts                    # Main application entry point
│   │   ├── bootstrap.ts              # Laravel/Axios setup
│   │   ├── Core/                     # Core desktop system
│   │   │   ├── WindowManager.ts      # Window management
│   │   │   ├── EventSystem.ts        # Event-driven architecture
│   │   │   ├── AppRegistry.ts        # App registration system
│   │   │   ├── AppLoader.ts          # Lazy loading & code splitting
│   │   │   └── Types.ts              # TypeScript definitions
│   │   ├── Apps/                     # Desktop applications
│   │   │   ├── BaseApp.ts            # Base app class
│   │   │   └── CalculatorApp.ts      # Calculator implementation
│   │   ├── Components/               # Vue components
│   │   │   └── Desktop/              # Desktop-specific components
│   │   ├── Layouts/                  # Page layouts
│   │   │   └── DesktopLayout.vue     # Main desktop layout
│   │   ├── Pages/                    # Inertia pages
│   │   │   └── Desktop.vue           # Desktop page
│   │   └── Tenant/                   # Multi-tenancy
│   │       └── ApiService.ts         # Tenant-aware API service
│   └── css/
│       ├── app.css                   # Main CSS entry point
│       └── components/               # Component-specific styles
├── vite.config.js                    # Vite configuration
├── tailwind.config.js                # Tailwind CSS configuration
├── tsconfig.json                     # TypeScript configuration
└── package.json                      # Dependencies and scripts
```

## ✨ Features

### 🖥️ **Desktop Environment**
- **Window Management**: Draggable, resizable windows with minimize/maximize/close
- **Taskbar**: Start menu, app icons, search, system tray, and clock
- **Desktop Icons**: Drag-and-drop desktop shortcuts
- **Start Menu**: Categorized application launcher
- **Context Menus**: Right-click interactions throughout the interface

### 📱 **Multi-Tenant Architecture**
- **Tenant Isolation**: Each tenant gets their own database and desktop environment
- **Laravel Vue Starter KIT**: [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/) 
- **Scoped Applications**: Apps and data are isolated per tenant and team
- **Tenant-Aware APIs**: All API calls respect tenant and team context

### 🔧 **Application System**
- **Modular Apps**: Each app is a separate, lazy-loaded module
- **Base App Class**: Consistent development framework for all apps
- **Dynamic Loading**: Apps load only when needed for optimal performance
- **App Registry**: Centralized app management and discovery
- **Event System**: Decoupled communication between apps and desktop

### 🎨 **Modern UI/UX**
- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile
- **Dark/Light Themes**: Automatic theme switching based on system preferences
- **Smooth Animations**: Polished transitions and micro-interactions
- **Accessibility**: ARIA labels and keyboard navigation support
- **Touch Support**: Mobile gestures and touch-friendly interactions

### ⚡ **Performance**
- **Code Splitting**: Apps load independently for faster initial loads
- **Lazy Loading**: Components and routes load on demand
- **Tree Shaking**: Unused code is eliminated from builds
- **Optimized Bundles**: Separate chunks for core, UI, and individual apps
- **Service Worker Ready**: PWA capabilities (can be added)

## 🚀 **Quick Start**

### **Prerequisites**
- PHP 8.1+
- Node.js 18+
- Composer
- NPM or Yarn

### **Installation**

1. **Install PHP dependencies**
```bash
composer install
```

2. **Install Node.js dependencies**
```bash
npm install
```

3. **Build assets**
```bash
# Development
npm run dev

# Production
npm run build
```

4. **Configure Laravel**
```bash
cp .env.example .env
php artisan key:generate
php artisan migrate
```

5. **Setup Tenancy**
```bash
php artisan tenants:migrate
php artisan tenants:seed
```

### **Development Commands**

```bash
# Start development server
npm run dev

# Type checking
npm run type-check

# Build for production
npm run build

# Preview production build
npm run preview
```

## 🏗️ **Architecture Details**

### **Modular TypeScript System**
The application uses a completely modular architecture with TypeScript:

- **Core System**: Window management, events, app registry
- **Application Framework**: Base classes for consistent app development  
- **Vue Components**: Reusable UI components with proper typing
- **Tenant Integration**: Multi-tenant aware API service
- **Build System**: Optimized Vite configuration with code splitting

### **Multi-Tenancy Implementation**
Built on **stancl/tenancy v4** with:

- **Database per Tenant**: Complete data isolation
- **Domain-based Identification**: Subdomain routing per tenant
- **Laravel Vue Starter Kit**** https://github.com/laravel/vue-starter-kit/   is used for the tenant app, meaning the tenant app is [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/)-based, while the central app is completely custom.
- **Scoped APIs**: All endpoints respect tenant/team context

### **Component Architecture**
Vue 3 components with TypeScript:

- **DesktopLayout.vue**: Main desktop container
- **WindowManager.vue**: Handles all window operations
- **WindowBase.vue**: Base window component for all apps
- **Taskbar.vue**: Bottom taskbar with search and system tray
- **DesktopIcons.vue**: Desktop icon management

## 📦 **Default Applications**

Each tenant receives these pre-installed apps:

- **📁 File Explorer**: File and folder management
- **🧮 Calculator**: Scientific calculator with history
- **⚙️ Settings**: System and user preferences
- **🏪 App Store**: Install additional applications
- **🔧 Site Builder**: Drag-and-drop website creator
- **📧 Email**: Email client integration
- **💬 Messages**: Internal messaging system

## 🔧 **Adding New Applications**

### **1. Create App Class**
```typescript
// resources/js/Apps/MyApp.ts
import { BaseApp } from './BaseApp'

export class MyApp extends BaseApp {
  constructor() {
    super('my-app', {
      id: 'my-app',
      name: 'My Application',
      icon: 'my-icon',
      // ... app configuration
    })
  }

  protected async render(): Promise<void> {
    // App rendering logic
  }

  protected getWindowOptions() {
    return {
      width: 800,
      height: 600,
      resizable: true
    }
  }
}
```

### **2. Register App**
```typescript
// In app.ts or module registration
import { MyApp } from './Apps/MyApp'
appRegistry.register('my-app', MyApp)
```

### **3. Add to App Loader**
```typescript
// In AppLoader.ts moduleMap
'my-app': () => import('../Apps/MyApp')
```

## 🔄 **State Management**

The application uses a decentralized state management approach:

- **Global State**: Managed by DesktopLayout and ApiService
- **App State**: Each app manages its own state via BaseApp
- **Window State**: Handled by WindowManager
- **Tenant Context**: Maintained by ApiService

## 🧪 **Testing Strategy**

### **Frontend Testing**
- **Unit Tests**: Vue component testing with Vitest
- **Integration Tests**: Desktop system integration
- **E2E Tests**: Full user workflows

### **Backend Testing**
- **Feature Tests**: Laravel endpoints with tenancy
- **Unit Tests**: Individual service classes
- **Multi-tenancy Tests**: Tenant isolation verification

## 🚢 **Deployment**

### **Production Build**
```bash
npm run build
php artisan optimize
```

### **Asset Optimization**
- **Code Splitting**: Automatic chunk optimization
- **Tree Shaking**: Dead code elimination  
- **Compression**: Gzip/Brotli compression
- **Caching**: Long-term asset caching

## 🔮 **Future Enhancements**

### **Phase 1** (Current)
- ✅ Modular TypeScript architecture
- ✅ Vue 3 + Inertia.js integration
- ✅ Multi-tenant foundation
- ✅ Basic desktop functionality

### **Phase 2** (Planned)
- 🔄 Additional default applications
- 🔄 Real-time notifications system
- 🔄 File system integration
- 🔄 Plugin marketplace

### **Phase 3** (Future)
- 📋 PWA capabilities
- 📋 Offline functionality
- 📋 Advanced theming system
- 📋 Mobile app companion 

## 🎯 **Usage Guide**

### **For End Users**
1. **Desktop Navigation**: Click desktop icons or use the start menu to launch apps
2. **Window Management**: Drag windows, resize with handles, use window controls
3. **Taskbar**: Access search, switch between apps, manage system settings
4. **Team Switching**: Use team dropdown in taskbar (if multiple teams)

### **For Developers**
1. **Component Development**: Extend Vue components in `resources/js/Components/`
2. **App Development**: Create new apps extending `BaseApp` class
3. **Styling**: Use  CSS from static files with desktop-specific utilities
4. **API Integration**: Use `ApiService` for tenant-aware requests

### **For Administrators**  
1. **Tenant Management**: Use central admin panel for tenant oversight
2. **App Management**: Control which apps are available per tenant
3. **Team Configuration**: Manage team permissions and access levels

## 🐛 **Debugging**

### **Development Tools**
- **Vue DevTools**: Install browser extension for Vue debugging
- **TypeScript**: Full type checking with `npm run type-check`
- **Hot Reload**: Instant updates during development
- **Source Maps**: Detailed error tracing in development

### **Console Logging**
The application includes comprehensive logging:
- **Desktop Events**: Window operations, app launches
- **API Calls**: Request/response logging in development
- **Error Handling**: Graceful error recovery with user feedback

## 🌐 **Browser Support**

- **Chrome**: 90+
- **Firefox**: 88+  
- **Safari**: 14+
- **Edge**: 90+
- **Mobile Safari**: iOS 14+
- **Chrome Mobile**: Android 8+

## 🤝 **Contributing**

### **Development Setup**
1. Fork the repository
2. Create a feature branch
3. Follow TypeScript and Vue 3 best practices
4. Add tests for new functionality
5. Submit a pull request

### **Code Standards**
- **TypeScript**: Strict mode enabled
- **Vue 3**: Composition API preferred
- **ESLint**: Code linting enforced
- **Prettier**: Code formatting enforced

## 📄 **License**

This project is open source and available under the [MIT License](LICENSE).

## 🙏 **Acknowledgments**

Built with these amazing technologies:
- **Laravel** - The web artisans framework
- **Tenancy for Laravel** - Stancl/Tenancy v4 for multi tenants
- **Vue 3** - The progressive JavaScript framework  
- **Inertia.js** - The modern monolith
- **TypeScript** - JavaScript with syntax for types
- **Vite** - Next generation frontend tooling 

### **Code Standards**
- **TypeScript**: Strict mode enabled
- **Vue 3**: Composition API preferred
- **ESLint**: Code linting enforced
- **Prettier**: Code formatting enforced

## Configuration for stancl/tenancy multi tenant - https://tenancy-v4.pages.dev/ 

Boilerplate setup installed on server:
- Laravel [Vue Starter Kit](https://github.com/laravel/vue-starter-kit/) is used for the tenant app, meaning the tenant app is [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/)-based, while the central app is completely custom.
- Domain identification
- Multi Database (Database for every tenant) Postgresql
- InertiaJs / VueJS
- Cloudflare services - Proxy (Nameserver management - if tenant has a domain, he will change nameserver, if not DNS Manager when tenant buys domain within the app)
- Cloudflare services - R2 Storage (tenant can buy storage from SAAS App and will be hosted on Cloudflare R2)
- Cloudflare CDN
- Other Cloudflare services

## Integrated Laravel Packages

# Domain management for tenants and SSL - ploi.io
# Laravel Horizon

# Addons
- Login with face id like Amazon - https://github.com/m1guelpf/laravel-fastlogin
- API Generators - https://github.com/Lomkit/laravel-rest-api  -or-  https://github.com/tailflow/laravel-orion
- Widgets for apps - https://gridstackjs.com/ and https://github.com/arrilot/laravel-widgets
- Permissions - https://github.com/spatie/laravel-permission
## - Appstore package management - https://github.com/nWidart/laravel-modules
- Spotlight search - https://github.com/laravel/scout  with  https://github.com/pacocoursey/cmdk
- Phone validation: https://github.com/worksome/verify-by-phone  -or-  https://github.com/codebar-ag/laravel-twilio-verify
- Activity Log for admins and tenants (App)- https://github.com/spatie/laravel-activitylog



## Notifications ##
- Dedicated Laravel - https://github.com/laravel/reverb with https://tenancy-v4.pages.dev/broadcasting/#prefixed-channel-names 
-Front listener - https://github.com/laravel/echo
Others:
- Amazon SNS - https://github.com/laravel-notification-channels/aws-sns  -or-  https://github.com/Pod-Point/laravel-aws-pubsub
- Paied solutins - (not recommanded) - https://larapush.com/  -or-  https://larasocket.com/ 
- Bell like notifications (we already have something in our OS) - https://github.com/mikebarlow/megaphone
- Vue Notifications - https://github.com/euvl/vue-notification


## Settings app ##
- https://github.com/rawilk/laravel-settings


## Wallet app ##
- https://github.com/bavix/laravel-wallet

## Tenant payments ##
https://github.com/laravel/cashier-stripe
https://github.com/spatie/laravel-webhook-client


# Chat app
- https://github.com/chatwoot/chatwoot


## My Files App (File explorer / manager) ##
 - R2 Bucket for every tenant - https://github.com/vidwanco/tenant-buckets/tree/tenancy-v4
 - File explorer - https://github.com/barryvdh/laravel-elfinder
 - Flysystems - https://github.com/thephpleague/flysystem


## Shopping app and with edits: Bookings app, appointment app, anything that sels online ##
- Full pack - https://github.com/aimeos/aimeos-laravel
- API first for OS apps like Contacts App, Orders App, Products App, etc -  https://github.com/aimeos/aimeos-headless
- Payment - https://github.com/aimeoscom/ai-payments/tree/master
- Omnipay - https://github.com/thephpleague/omnipay
- Import data from Wordpress - https://github.com/aimeos/ai-woocommerce
- File systems - https://github.com/aimeos/ai-filesystem
- Cache - https://github.com/aimeos/ai-cache




# Website builder
- Drag and drop sitebuilder - https://github.com/allamo123/laravel-grapes
- Slider - https://github.com/nolimits4web/swiper  -or-  https://github.com/kaoz70/grapesjs-swiper-slider
- Form builder - https://github.com/JhumanJ/OpnForm			https://github.com/vitormicillo/laravel-formbuilder
- SEO - https://github.com/artesaos/seotools			https://github.com/ralphjsmit/laravel-seo			https://github.com/spatie/schema-org
- Self hosted Analytics - https://plausible.io/		https://github.com/plausible/analytics			https://github.com/umami-software/umami			https://umami.is/		https://github.com/b4mtech/laravel-umami
- Google analytics - https://github.com/spatie/laravel-analytics			https://github.com/LukeTowers/laravel-ga4-event-tracking				
- Ads - https://github.com/5balloons/laravel-smart-ads
- Image optimisation - https://github.com/spatie/laravel-image-optimizer  -or-  https://github.com/Intervention/image with https://github.com/laravelista/picasso


## AI Chat and Control ##
- https://github.com/prism-php/prism and https://github.com/openai-php/laravel


# Affiliate	
https://github.com/jijunair/laravel-referral

# Email Marketing		
https://sendportal.io/		https://www.mailcoach.app/

#SMS Marketing							
- Multiple Gateways - https://github.com/prgayman/laravel-sms
- Amazon - https://github.com/sridharan01234/laravel-sns				
- Self hosted with gatway - https://codecanyon.net/item/smslab-android-based-sms-gateway-server/40311458  -and-  https://codecanyon.net/item/smslab-android-sms-gateway-server/41836215		

# Photo editor	
Photopea	https://www.photopea.com/

# Office word/excel/prezentare
OnlyOffice - https://www.onlyoffice.com/

# Blog App			
https://github.com/austintoddj/canvas/			https://trycanvas.app/

# SEO Tools
https://github.com/artesaos/seotools  -and-  https://github.com/spatie/schema-org


# Website Translator								
-  For automated content translation - https://github.com/ImAbuSayed/laravel-ai-translator				https://github.com/DeepLcom/deepl-php				https://github.com/kargnas/laravel-ai-translator
- Translation UI & Route Localization Packages
- For route localization & middleware - https://github.com/mcamara/laravel-localization
- For manual language management in admin panels (for tenant) - https://github.com/MohmmedAshraf/laravel-translations				
- spatie/laravel-translatable

# Social Media ADS Marketing App - https://github.com/InfyOmLabs/ads-sdk

# Social Media Poster and comment raspuns
https://github.com/mautic/mautic
https://mixpost.app/
https://github.com/orgs/inovector/repositories

Analytics
https://codecanyon.net/item/site-analytics-plugin-multisaas-multitenancy-multipurpose-website-builder-saas/51817600