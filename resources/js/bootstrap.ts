// Bootstrap file - Basic setup and dependencies
import axios from 'axios';

// Configure Axios defaults
window.axios = axios;

axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// CSRF token setup for Laravel
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    const content = token.getAttribute('content');
    if (content) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = content;
    }
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}

// Basic polyfills for older browsers
if (!window.Promise) {
    window.Promise = require('es6-promise').Promise;
}

// Global error handling
window.addEventListener('error', (e) => {
    console.error('Global error:', e.error);
});

window.addEventListener('unhandledrejection', (e) => {
    console.error('Unhandled promise rejection:', e.reason);
});

// Development helpers
if (process.env.NODE_ENV === 'development') {
    console.log('Desktop Application - Development Mode');
}

export {}; 