/** @type {import('tailwindcss').Config} */
// Tailwind CSS has been replaced with classic CSS from static files
// module.exports = {
//   content: [
//     "./resources/**/*.blade.php",
//     "./resources/**/*.js",
//     "./resources/**/*.ts",
//     "./resources/**/*.vue",
//     "./app/Http/Controllers/**/*.php",
//     "./app/Http/Middleware/**/*.php",
//   ],
//   darkMode: 'class',
//   theme: {
//     extend: {
//       colors: {
//         // Desktop theme colors - from original CSS variables
//         desktop: {
//           primary: '#0c0021',      // --primary-bg
//           secondary: '#100f21',    // --secondary-bg
//           accent: '#3576f6',       // --accent-color
//           accentLight: '#359ff6',  // --accent-color-light
//           background: '#f8fafc',
//           surface: '#ffffff',
//           'surface-dark': '#1f2937',
//           text: '#1f2937',
//           'text-dark': '#f9fafb',
//           window: '#050217d0',     // --window-bg
//           content: '#100f21',      // --window-content
//           header: '#0c0021',       // --window-header
//           context: '#100f21c4',    // --context-menu-bg
//           border: 'rgba(255, 255, 255, 0.05)', // --border-color
//           green: '#00cc66',        // --green-accent
//           yellow: '#fbbb1a',       // --yellow-accent
//           blue: '#1a73e8',         // --blue-accent
//           cyan: '#00bbcc',         // --cyan-accent
//           red: '#ff3a30',          // --red-accent
//           purple: '#a142f4',       // --purple-accent
//           orange: '#ff9500',       // --orange-accent
//           gray: '#646a86',         // --os-gray
//           grayDark: '#2c314d',     // --os-gray-dark
//           widget: '#050217a6',     // --widget-bg
//           input: 'rgba(255, 255, 255, 0.04)',      // --input-bg
//           inputActive: 'rgba(255, 255, 255, 0.08)', // --input-bg-active
//           toolbar: 'rgba(255, 255, 255, 0.05)',     // --window-toolbar-bg
//         },
//         icon: {
//           blue: '#4f6bfe',         // --icon-bg-blue
//           green: '#68c23d',        // --icon-bg-green
//           pink: '#ff3b8d',         // --icon-bg-pink
//           cyan: '#00c8d5',         // --icon-bg-cyan
//           gray: '#5a6377',         // --icon-bg-gray
//           orange: '#ff9500',       // --orange-icon
//           purple: '#a142f4',       // --purple-icon
//           red: '#ff3a30',          // --red-icon
//         }
//       },
//       fontFamily: {
//         sans: ['Inter', 'system-ui', 'sans-serif'],
//         mono: ['JetBrains Mono', 'Menlo', 'Monaco', 'monospace'],
//       },
//       fontSize: {
//         'desktop-icon': '0.75rem',
//         'window-title': '0.875rem',
//         'taskbar': '0.875rem',
//       },
//       spacing: {
//         'desktop-icon': '4.5rem',
//         'taskbar-height': '4rem',
//         'window-header': '2.5rem',
//       },
//       zIndex: {
//         'desktop': 0,
//         'window': 1000,
//         'window-active': 1100,
//         'taskbar': 1200,
//         'dropdown': 1300,
//         'modal': 1400,
//         'tooltip': 1500,
//       },
//       animation: {
//         // Window animations (from original CSS)
//         'window-open-ios': 'windowOpenIOS 0.35s ease',
//         'window-maximize': 'windowMaximize 0.32s cubic-bezier(0.4, 0, 0.2, 1)',
//         'window-restore': 'windowRestore 0.32s cubic-bezier(0.4, 0, 0.2, 1)',
//         'window-close': 'windowClose 0.25s cubic-bezier(0.4, 0, 0.2, 1)',
//         // Start menu animations
//         'start-menu-open': 'startMenuOpen 0.32s cubic-bezier(0.4, 0, 0.2, 1)',
//         'start-menu-close': 'startMenuClose 0.22s cubic-bezier(0.4, 0, 0.2, 1)',
//         // Context menu and notifications
//         'context-menu-pop': 'contextMenuPop 0.15s ease-out',
//         'notif-swipe-out': 'notifSwipeOutRight 0.3s ease-in-out',
//         // Desktop and taskbar animations
//         'desktop-icons-slide-left': 'desktopIconsSlideLeft 0.6s ease-in-out',
//         'taskbar-slide-down': 'taskbarSlideDown 0.6s ease-in-out',
//         'taskbar-app-icon-in': 'taskbarAppIconIn 0.3s ease-out',
//         // Widget and panel animations
//         'widgets-fade-in': 'widgetsFadeIn 0.6s ease-in-out',
//         'slide-in-right-panel': 'slideInRightPanel 0.3s ease-out',
//         'fade-in-panel': 'fadeInPanel 0.2s ease-out',
//         // Basic utility animations (preserved from existing)
//         'window-open': 'windowOpen 0.3s ease-out',
//         'taskbar-slide': 'taskbarSlide 0.3s ease-out',
//         'fade-in': 'fadeIn 0.2s ease-out',
//         'slide-up': 'slideUp 0.2s ease-out',
//         'bounce-in': 'bounceIn 0.4s ease-out',
//       },
//       keyframes: {
//         // Window keyframes from original CSS
//         windowOpenIOS: {
//           '0%': { opacity: '0', transform: 'scale(0.7) translateY(60px)' },
//           '100%': { opacity: '1', transform: 'scale(1) translateY(0)' },
//         },
//         windowMaximize: {
//           '0%': { opacity: '1', transform: 'scale(0.92) translateY(30px)' },
//           '100%': { opacity: '1', transform: 'scale(1) translateY(0)' },
//         },
//         windowRestore: {
//           '0%': { opacity: '1', transform: 'scale(1.08) translateY(-30px)' },
//           '100%': { opacity: '1', transform: 'scale(1) translateY(0)' },
//         },
//         windowClose: {
//           '0%': { opacity: '1', transform: 'scale(1) translateY(0)' },
//           '100%': { opacity: '0', transform: 'scale(0.7) translateY(60px)' },
//         },
//         // Start menu keyframes
//         startMenuOpen: {
//           '0%': { opacity: '0', transform: 'scale(0.95) translateY(30px)' },
//           '100%': { opacity: '1', transform: 'scale(1) translateY(0)' },
//         },
//         startMenuClose: {
//           '0%': { opacity: '1', transform: 'scale(1) translateY(0)' },
//           '100%': { opacity: '0', transform: 'scale(0.95) translateY(30px)' },
//         },
//         // Context menu keyframes
//         contextMenuPop: {
//           '0%': { opacity: '0', transform: 'scale(0.8) translateY(10px)' },
//           '100%': { opacity: '1', transform: 'scale(1) translateY(0)' },
//         },
//         // Notification keyframes
//         notifSwipeOutRight: {
//           '0%': { transform: 'translateX(0)' },
//           '100%': { transform: 'translateX(100%)' },
//         },
//         // Desktop animation keyframes
//         desktopIconsSlideLeft: {
//           '0%': { transform: 'translateX(0%)' },
//           '100%': { transform: 'translateX(-100%)' },
//         },
//         taskbarSlideDown: {
//           '0%': { transform: 'translateY(0%)' },
//           '100%': { transform: 'translateY(100%)' },
//         },
//         taskbarAppIconIn: {
//           '0%': { opacity: '0', transform: 'scale(0.5)' },
//           '50%': { opacity: '1', transform: 'scale(1.1)' },
//           '100%': { opacity: '1', transform: 'scale(1)' },
//         },
//         // Widget keyframes
//         widgetsFadeIn: {
//           '0%': { opacity: '0', transform: 'translateX(100%)' },
//           '100%': { opacity: '1', transform: 'translateX(0%)' },
//         },
//         // Panel keyframes
//         slideInRightPanel: {
//           '0%': { transform: 'translateX(100%)' },
//           '100%': { transform: 'translateX(0%)' },
//         },
//         fadeInPanel: {
//           '0%': { opacity: '0' },
//           '100%': { opacity: '1' },
//         },
//         // Existing keyframes (preserved)
//         windowOpen: {
//           '0%': { opacity: '0', transform: 'scale(0.95) translateY(20px)' },
//           '100%': { opacity: '1', transform: 'scale(1) translateY(0)' },
//         },
//         taskbarSlide: {
//           '0%': { transform: 'translateY(100%)' },
//           '100%': { transform: 'translateY(0)' },
//         },
//         fadeIn: {
//           '0%': { opacity: '0' },
//           '100%': { opacity: '1' },
//         },
//         slideUp: {
//           '0%': { opacity: '0', transform: 'translateY(10px)' },
//           '100%': { opacity: '1', transform: 'translateY(0)' },
//         },
//         bounceIn: {
//           '0%': { opacity: '0', transform: 'scale(0.3)' },
//           '50%': { opacity: '1', transform: 'scale(1.05)' },
//           '70%': { transform: 'scale(0.9)' },
//           '100%': { transform: 'scale(1)' },
//         },
//       },
//       backdropBlur: {
//         'desktop': '20px',
//       },
//       boxShadow: {
//         'window': '0 25px 50px -12px rgba(0, 0, 0, 0.25)',
//         'window-active': '0 25px 50px -12px rgba(59, 130, 246, 0.25)',
//         'taskbar': '0 -4px 6px -1px rgba(0, 0, 0, 0.1)',
//         'desktop-icon': '0 2px 4px rgba(0, 0, 0, 0.1)',
//       },
//       borderRadius: {
//         'window': '0.75rem',
//         'desktop-icon': '0.5rem',
//       },
//       transitionDuration: {
//         'window': '300ms',
//         'desktop': '200ms',
//       },
//       // Custom utilities for desktop and responsive breakpoints
//       screens: {
//         'xs': '400px',      // Tiny screens (from original)
//         'sm': '640px',      // Default Tailwind
//         'md': '768px',      // Default Tailwind
//         'lg': '1024px',     // Default Tailwind
//         'xl': '1280px',     // Default Tailwind
//         '2xl': '1536px',    // Default Tailwind
//         // OS-specific breakpoints from original CSS
//         'mobile': '768px',     // Mobile screens
//         'tablet': '1024px',    // Tablet screens
//         'desktop': '1024px',   // Desktop screens
//         'ultrawide': '1920px', // Ultrawide displays
//         // Additional breakpoints for fine-grained control
//         'tiny': {'max': '400px'},        // max-width: 400px
//         'xs-only': {'max': '639px'},     // max-width: 639px
//         'mobile-only': {'max': '767px'}, // max-width: 767px (from original)
//         'tablet-only': {'min': '768px', 'max': '1023px'}, // 768px to 1023px
//       },
//     },
//   },
//   plugins: [
//     require('@tailwindcss/forms'),
//     // Custom plugin for desktop utilities
//     function({ addUtilities, theme }) {
//       const newUtilities = {
//         '.window-transition': {
//           transition: `all ${theme('transitionDuration.window')} ease-out`,
//         },
//         '.desktop-blur': {
//           backdropFilter: `blur(${theme('backdropBlur.desktop')})`,
//         },
//         '.window-shadow': {
//           boxShadow: theme('boxShadow.window'),
//         },
//         '.window-shadow-active': {
//           boxShadow: theme('boxShadow.window-active'),
//         },
//         '.taskbar-height': {
//           height: theme('spacing.taskbar-height'),
//         },
//         '.desktop-grid': {
//           display: 'grid',
//           gridTemplateColumns: 'repeat(auto-fill, minmax(5rem, 1fr))',
//           gap: theme('spacing.4'),
//         },
//         // Mobile specific utilities
//         '.mobile-window': {
//           '@media (max-width: 768px)': {
//             position: 'fixed !important',
//             inset: '0.5rem 0.5rem 4rem 0.5rem !important',
//             width: 'auto !important',
//             height: 'auto !important',
//             transform: 'none !important',
//           },
//         },
//       }
//       addUtilities(newUtilities)
//     }
//   ],
// }

// Since we're using classic CSS now, export an empty config
module.exports = {} 