// Tenant-Aware API Service for Laravel Backend Communication
// Integrates with stancl/tenancy v4 for automatic tenant context resolution
import type { ApiResponse, PaginatedResponse, UseApiOptions } from '../Core/Types';

/**
 * Request configuration interface for API calls
 */
interface RequestConfig {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  headers?: Record<string, string>;
  body?: unknown;
  params?: Record<string, string | number>;
  timeout?: number;
  retries?: number;
}

/**
 * Tenant context interface for multi-tenant API communication
 */
interface TenantContext {
  // Tenant context is automatically resolved via domain by stancl/tenancy middleware
  // No manual tenant ID passing required - handled by InitializeTenancyByDomain
  teamId?: number;
  userId?: number;
  domain?: string;
  tenantId?: string; // Added for compatibility
  currentTeam?: {
    id: number;
    name: string;
    role: string;
  };
}

/**
 * API Service - Tenant-aware HTTP client for Laravel backend communication
 *
 * This class provides a comprehensive HTTP client for communicating with the
 * Laravel backend API, with automatic tenant context resolution through
 * stancl/tenancy v4 middleware. It handles authentication, CSRF protection,
 * error handling, and request/response processing.
 *
 * Key features:
 * - Automatic tenant context resolution via domain
 * - CSRF token management and protection
 * - Comprehensive HTTP method support (GET, POST, PUT, PATCH, DELETE)
 * - File upload support with progress tracking
 * - Request/response interceptors
 * - Error handling and retry logic
 * - Team switching and context management
 * - Multi-tenant API endpoint support
 * - Request timeout and retry configuration
 *
 * Supported operations:
 * - Generic HTTP requests (GET, POST, PUT, PATCH, DELETE)
 * - File uploads with progress tracking
 * - App store operations (install, uninstall, list)
 * - Window management operations
 * - User preferences management
 * - Team switching and management
 * - File system operations
 * - User and team data retrieval
 *
 * The service provides:
 * - Tenant-aware API communication
 * - Automatic context resolution
 * - Comprehensive error handling
 * - Request/response processing
 * - File upload capabilities
 * - Team and user management
 * - App store integration
 * - Window state management
 *
 * @class ApiService
 * @since 1.0.0
 */
class ApiService {
  /** @type {string} Base URL for API requests */
  private baseUrl: string;
  
  /** @type {Record<string, string>} Default headers for all requests */
  private defaultHeaders: Record<string, string>;
  
  /** @type {TenantContext} Current tenant context for requests */
  private tenantContext: TenantContext = {};
  
  /** @type {string|null} CSRF token for Laravel protection */
  private csrfToken: string | null = null;

  /**
   * Create a new API service instance
   *
   * This constructor initializes the API service with base URL configuration,
   * default headers, CSRF token setup, and request interceptors.
   */
  constructor() {
    this.baseUrl = this.getBaseUrl();
    this.defaultHeaders = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      // Note: X-Tenant-ID not needed - tenancy resolved via domain middleware
    };
    
    this.initializeCsrfToken();
    this.setupInterceptors();
  }

  /**
   * Set team/user context for requests
   *
   * This method updates the tenant context for API requests. Note that
   * tenant context is automatically resolved by stancl/tenancy middleware
   * based on the current domain.
   *
   * @param {Partial<TenantContext>} context The tenant context to set
   */
  setTenantContext(context: Partial<TenantContext>): void {
    this.tenantContext = { ...this.tenantContext, ...context };
  }

  /**
   * Get current tenant context
   *
   * This method returns the current tenant context. Note that tenant ID
   * is handled automatically by Laravel tenancy middleware.
   *
   * @returns {TenantContext} The current tenant context
   */
  getTenantContext(): TenantContext {
    return { ...this.tenantContext };
  }

  /**
   * Generic GET request
   *
   * This method performs a GET request to the specified endpoint with
   * optional query parameters and request options.
   *
   * @template T The expected response type
   * @param {string} endpoint The API endpoint to request
   * @param {UseApiOptions & { params?: Record<string, any> }} options Request options including query parameters
   * @returns {Promise<ApiResponse<T>>} Promise that resolves to the API response
   */
  async get<T = any>(
    endpoint: string,
    options: UseApiOptions & { params?: Record<string, any> } = {}
  ): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'GET',
      params: options.params,
      ...this.getRequestOptions(options),
    });
  }

  /**
   * Generic POST request
   *
   * This method performs a POST request to the specified endpoint with
   * optional request body and options.
   *
   * @template T The expected response type
   * @param {string} endpoint The API endpoint to request
   * @param {any} data Optional request body data
   * @param {UseApiOptions} options Request options
   * @returns {Promise<ApiResponse<T>>} Promise that resolves to the API response
   */
  async post<T = any>(
    endpoint: string,
    data?: any,
    options: UseApiOptions = {}
  ): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'POST',
      body: data,
      ...this.getRequestOptions(options),
    });
  }

  /**
   * Generic PUT request
   *
   * This method performs a PUT request to the specified endpoint with
   * optional request body and options.
   *
   * @template T The expected response type
   * @param {string} endpoint The API endpoint to request
   * @param {any} data Optional request body data
   * @param {UseApiOptions} options Request options
   * @returns {Promise<ApiResponse<T>>} Promise that resolves to the API response
   */
  async put<T = any>(
    endpoint: string,
    data?: any,
    options: UseApiOptions = {}
  ): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'PUT',
      body: data,
      ...this.getRequestOptions(options),
    });
  }

  /**
   * Generic PATCH request
   *
   * This method performs a PATCH request to the specified endpoint with
   * optional request body and options.
   *
   * @template T The expected response type
   * @param {string} endpoint The API endpoint to request
   * @param {any} data Optional request body data
   * @param {UseApiOptions} options Request options
   * @returns {Promise<ApiResponse<T>>} Promise that resolves to the API response
   */
  async patch<T = any>(
    endpoint: string,
    data?: any,
    options: UseApiOptions = {}
  ): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'PATCH',
      body: data,
      ...this.getRequestOptions(options),
    });
  }

  /**
   * Generic DELETE request
   *
   * This method performs a DELETE request to the specified endpoint with
   * optional request options.
   *
   * @template T The expected response type
   * @param {string} endpoint The API endpoint to request
   * @param {UseApiOptions} options Request options
   * @returns {Promise<ApiResponse<T>>} Promise that resolves to the API response
   */
  async delete<T = any>(
    endpoint: string,
    options: UseApiOptions = {}
  ): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'DELETE',
      ...this.getRequestOptions(options),
    });
  }

  /**
   * Upload files with form data
   *
   * This method performs a file upload request to the specified endpoint
   * with optional progress tracking.
   *
   * @template T The expected response type
   * @param {string} endpoint The API endpoint to request
   * @param {FormData} formData Form data containing files and data
   * @param {UseApiOptions & { onProgress?: (progress: number) => void }} options Request options including progress callback
   * @returns {Promise<ApiResponse<T>>} Promise that resolves to the API response
   */
  async upload<T = any>(
    endpoint: string,
    formData: FormData,
    options: UseApiOptions & { 
      onProgress?: (progress: number) => void 
    } = {}
  ): Promise<ApiResponse<T>> {
    const headers = this.buildHeaders(options);
    delete headers['Content-Type']; // Let browser set multipart boundary

    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      
      xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable && options.onProgress) {
          const progress = (e.loaded / e.total) * 100;
          options.onProgress(progress);
        }
      });

      xhr.addEventListener('load', () => {
        try {
          const response = JSON.parse(xhr.responseText);
          resolve(response);
        } catch (error) {
          reject(new Error('Invalid JSON response'));
        }
      });

      xhr.addEventListener('error', () => {
        reject(new Error('Upload failed'));
      });

      xhr.open('POST', this.buildUrl(endpoint, options));
      
      // Set headers
      Object.entries(headers).forEach(([key, value]) => {
        xhr.setRequestHeader(key, value);
      });

      xhr.send(formData);
    });
  }

  // Desktop App Specific Methods
  // Note: All requests automatically include tenant context via stancl/tenancy middleware

  /**
   * Get available apps for current tenant/team
   *
   * This method retrieves the list of available apps for the current tenant/team.
   * Tenant isolation is handled automatically by stancl/tenancy database switching.
   *
   * @param {number} [teamId] Optional team ID to override the default tenant context
   * @returns {Promise<ApiResponse<any[]>>} Promise that resolves to the API response
   */
  async getAvailableApps(teamId?: number): Promise<ApiResponse<any[]>> {
    return this.get('/desktop/apps', {
      params: { team_id: teamId || this.tenantContext.teamId },
    });
  }

  /**
   * Get installed apps for current tenant/team
   *
   * This method retrieves the list of installed apps for the current tenant/team.
   * Team scoping is handled in the Laravel controller with proper authorization.
   *
   * @param {number} [teamId] Optional team ID to override the default tenant context
   * @returns {Promise<ApiResponse<any[]>>} Promise that resolves to the API response
   */
  async getInstalledApps(teamId?: number): Promise<ApiResponse<any[]>> {
    return this.get('/desktop/apps/installed', {
      params: { team_id: teamId || this.tenantContext.teamId },
    });
  }

  /**
   * Install an app (tenant and team scoped)
   *
   * This method installs an app for the current tenant and team.
   * Installation is isolated per tenant database automatically.
   *
   * @param {string} appId The ID of the app to install
   * @param {number} [teamId] Optional team ID to override the default tenant context
   * @returns {Promise<ApiResponse<any>>} Promise that resolves to the API response
   */
  async installApp(appId: string, teamId?: number): Promise<ApiResponse<any>> {
    return this.post('/desktop/apps/install', {
      app_id: appId,
      team_id: teamId || this.tenantContext.teamId,
    });
  }

  /**
   * Uninstall an app
   *
   * This method uninstalls an app for the current tenant/team.
   *
   * @param {string} appId The ID of the app to uninstall
   * @param {number} [teamId] Optional team ID to override the default tenant context
   * @returns {Promise<ApiResponse<any>>} Promise that resolves to the API response
   */
  async uninstallApp(appId: string, teamId?: number): Promise<ApiResponse<any>> {
    const teamIdParam = teamId || this.tenantContext.teamId;
    return this.delete(`/desktop/apps/${appId}?team_id=${teamIdParam}`);
  }

  // Window Management API

  /**
   * Get open windows for current user/team
   *
   * This method retrieves the list of open windows for the current user/team.
   *
   * @param {number} [teamId] Optional team ID to override the default tenant context
   * @returns {Promise<ApiResponse<any[]>>} Promise that resolves to the API response
   */
  async getOpenWindows(teamId?: number): Promise<ApiResponse<any[]>> {
    return this.get('/desktop/windows', {
      params: { team_id: teamId || this.tenantContext.teamId },
    });
  }

  /**
   * Save window state
   *
   * This method saves the state of a window for the current user/team.
   *
   * @param {any} windowData The window data to save
   * @returns {Promise<ApiResponse<any>>} Promise that resolves to the API response
   */
  async saveWindowState(windowData: any): Promise<ApiResponse<any>> {
    return this.post('/desktop/windows', {
      ...windowData,
      team_id: this.tenantContext.teamId,
    });
  }

  /**
   * Update window position/size
   *
   * This method updates the position and size of a window for the current user/team.
   *
   * @param {string} windowId The ID of the window to update
   * @param {Object} position The new position and size { x: number, y: number, width: number, height: number }
   * @returns {Promise<ApiResponse<any>>} Promise that resolves to the API response
   */
  async updateWindowPosition(
    windowId: string,
    position: { x: number; y: number; width: number; height: number }
  ): Promise<ApiResponse<any>> {
    return this.patch(`/desktop/windows/${windowId}/position`, position);
  }

  /**
   * Close window
   *
   * This method closes a window for the current user/team.
   *
   * @param {string} windowId The ID of the window to close
   * @returns {Promise<ApiResponse<any>>} Promise that resolves to the API response
   */
  async closeWindow(windowId: string): Promise<ApiResponse<any>> {
    return this.delete(`/desktop/windows/${windowId}`);
  }

  // User Preferences API

  /**
   * Get user preferences
   *
   * This method retrieves the user preferences for the current user/team.
   *
   * @param {number} [teamId] Optional team ID to override the default tenant context
   * @returns {Promise<ApiResponse<any>>} Promise that resolves to the API response
   */
  async getUserPreferences(teamId?: number): Promise<ApiResponse<any>> {
    return this.get('/desktop/preferences', {
      params: { team_id: teamId || this.tenantContext.teamId },
    });
  }

  /**
   * Update user preferences
   *
   * This method updates the user preferences for the current user/team.
   *
   * @param {any} preferences The new user preferences
   * @returns {Promise<ApiResponse<any>>} Promise that resolves to the API response
   */
  async updateUserPreferences(preferences: any): Promise<ApiResponse<any>> {
    return this.put('/desktop/preferences', {
      ...preferences,
      team_id: this.tenantContext.teamId,
    });
  }

  // Team Management API

  /**
   * Get current user's teams
   *
   * This method retrieves the list of teams the current user belongs to.
   *
   * @returns {Promise<ApiResponse<any[]>>} Promise that resolves to the API response
   */
  async getUserTeams(): Promise<ApiResponse<any[]>> {
    return this.get('/teams');
  }

  /**
   * Switch to a different team
   *
   * This method switches the current user's context to a different team.
   *
   * @param {number} teamId The ID of the team to switch to
   * @returns {Promise<ApiResponse<any>>} Promise that resolves to the API response
   */
  async switchTeam(teamId: number): Promise<ApiResponse<any>> {
    const response = await this.put('/current-team', { team_id: teamId });
    
    if (response.status === 200) {
      this.setTenantContext({ teamId });
    }
    
    return response;
  }

  /**
   * Get team members
   *
   * This method retrieves the members of a specific team.
   *
   * @param {number} [teamId] Optional team ID to override the default tenant context
   * @returns {Promise<ApiResponse<any[]>>} Promise that resolves to the API response
   */
  async getTeamMembers(teamId?: number): Promise<ApiResponse<any[]>> {
    const team = teamId || this.tenantContext.teamId;
    return this.get(`/teams/${team}/members`);
  }

  // File System API (for File Explorer app)

  /**
   * Get files/folders
   *
   * This method retrieves the contents of a directory for the current user/team.
   *
   * @param {string} [path] The path to the directory (default: '/')
   * @param {number} [teamId] Optional team ID to override the default tenant context
   * @returns {Promise<ApiResponse<any[]>>} Promise that resolves to the API response
   */
  async getFiles(path = '/', teamId?: number): Promise<ApiResponse<any[]>> {
    return this.get('/desktop/files', {
      params: { 
        path,
        team_id: teamId || this.tenantContext.teamId,
      },
    });
  }

  /**
   * Create folder
   *
   * This method creates a new folder in the specified path for the current user/team.
   *
   * @param {string} name The name of the folder to create
   * @param {string} [path] The path to create the folder (default: '/')
   * @returns {Promise<ApiResponse<any>>} Promise that resolves to the API response
   */
  async createFolder(name: string, path = '/'): Promise<ApiResponse<any>> {
    return this.post('/desktop/files/folder', {
      name,
      path,
      team_id: this.tenantContext.teamId,
    });
  }

  /**
   * Delete file/folder
   *
   * This method deletes a file or folder from the specified path for the current user/team.
   *
   * @param {string} fileId The ID of the file or folder to delete
   * @returns {Promise<ApiResponse<any>>} Promise that resolves to the API response
   */
  async deleteFile(fileId: string): Promise<ApiResponse<any>> {
    return this.delete(`/desktop/files/${fileId}`);
  }

  /**
   * Upload file
   *
   * This method uploads a single file to the specified path for the current user/team.
   *
   * @param {File} file The file to upload
   * @param {string} [path] The path to upload the file (default: '/')
   * @param {(progress: number) => void} [onProgress] Optional callback for progress tracking
   * @returns {Promise<ApiResponse<any>>} Promise that resolves to the API response
   */
  async uploadFile(
    file: File,
    path = '/',
    onProgress?: (progress: number) => void
  ): Promise<ApiResponse<any>> {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('path', path);
    formData.append('team_id', String(this.tenantContext.teamId || ''));

    return this.upload('/desktop/files/upload', formData, { onProgress });
  }

  // Private methods

  /**
   * Generic request method for all HTTP methods
   *
   * This method handles the actual HTTP request, including error handling,
   * retry logic, and response processing.
   *
   * @template T The expected response type
   * @param {string} endpoint The API endpoint to request
   * @param {RequestConfig} config Request configuration including method, body, params, etc.
   * @returns {Promise<ApiResponse<T>>} Promise that resolves to the API response
   */
  private async request<T>(endpoint: string, config: RequestConfig): Promise<ApiResponse<T>> {
    const url = this.buildUrl(endpoint, config);
    const headers = this.buildHeaders(config);
    
    let body: string | FormData | undefined;
    if (config.body) {
      if (config.body instanceof FormData) {
        body = config.body;
      } else {
        body = JSON.stringify(config.body);
      }
    }

    const requestConfig: RequestInit = {
      method: config.method || 'GET',
      headers,
      body,
      credentials: 'include', // Include cookies for session auth
    };

    try {
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), config.timeout || 30000);
      
      requestConfig.signal = controller.signal;

      const response = await fetch(url, requestConfig);
      clearTimeout(timeoutId);

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || `HTTP ${response.status}`);
      }

      return {
        data: data.data || data,
        message: data.message,
        status: response.status,
      };
    } catch (error) {
      if (config.retries && config.retries > 0) {
        await this.delay(1000); // Wait 1 second before retry
        return this.request(endpoint, { ...config, retries: config.retries - 1 });
      }

      throw new Error(error instanceof Error ? error.message : 'Network error');
    }
  }

  /**
   * Build the full URL for an API request
   *
   * This method constructs the complete URL for an API endpoint, including
   * base URL, query parameters, and tenant context.
   *
   * @param {string} endpoint The API endpoint to build
   * @param {RequestConfig & UseApiOptions} config Request configuration including query parameters and options
   * @returns {string} The constructed full URL
   */
  private buildUrl(endpoint: string, config: RequestConfig & UseApiOptions = {}): string {
    // Handle tenant/team override
    let baseUrl = this.baseUrl;
    
    if (config.tenant) {
      baseUrl = `https://${config.tenant}.${window.location.hostname}`;
    }

    let url = `${baseUrl}${endpoint.startsWith('/') ? endpoint : '/' + endpoint}`;

    // Add query parameters
    const params = new URLSearchParams();
    
    if (config.params) {
      Object.entries(config.params).forEach(([key, value]) => {
        params.append(key, String(value));
      });
    }

    // Add team context if not already specified
    if (config.team || this.tenantContext.teamId) {
      params.append('team_id', String(config.team || this.tenantContext.teamId));
    }

    const queryString = params.toString();
    if (queryString) {
      url += (url.includes('?') ? '&' : '?') + queryString;
    }

    return url;
  }

  /**
   * Build headers for an API request
   *
   * This method constructs the headers for an API request, including default
   * headers, CSRF token, and tenant context headers.
   *
   * @param {RequestConfig & UseApiOptions} config Request configuration including headers
   * @returns {Record<string, string>} The constructed headers
   */
  private buildHeaders(config: RequestConfig & UseApiOptions = {}): Record<string, string> {
    const headers = { ...this.defaultHeaders };

    // Add CSRF token
    if (this.csrfToken) {
      headers['X-CSRF-TOKEN'] = this.csrfToken;
    }

    // Add tenant context headers
    if (this.tenantContext.tenantId) {
      headers['X-Tenant-ID'] = this.tenantContext.tenantId;
    }

    if (this.tenantContext.teamId) {
      headers['X-Team-ID'] = String(this.tenantContext.teamId);
    }

    // Merge custom headers
    if (config.headers) {
      Object.assign(headers, config.headers);
    }

    return headers;
  }

  /**
   * Get request options for a specific API call
   *
   * This method extracts the timeout and retry options from the provided
   * options object for a generic request.
   *
   * @param {UseApiOptions} options The request options
   * @returns {Partial<RequestConfig>} The extracted request options
   */
  private getRequestOptions(options: UseApiOptions): Partial<RequestConfig> {
    return {
      timeout: options.timeout,
      retries: options.retries,
    };
  }

  /**
   * Get the base URL for API requests
   *
   * This method determines the base URL for API requests, typically the
   * current domain or a specific tenant domain.
   *
   * @returns {string} The base URL
   */
  private getBaseUrl(): string {
    // Use current domain for tenant-specific requests
    return window.location.origin;
  }

  /**
   * Initialize CSRF token from meta tag
   *
   * This method retrieves the CSRF token from the meta tag in the HTML
   * document for CSRF protection.
   */
  private initializeCsrfToken(): void {
    // Get CSRF token from meta tag
    const metaTag = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
    if (metaTag) {
      this.csrfToken = metaTag.getAttribute('content');
    }
  }

  /**
   * Setup interceptors for request/response handling
   *
   * This method can be extended to add global error handling,
   * authentication redirects, etc.
   */
  private setupInterceptors(): void {
    // This could be extended to handle global error responses,
    // authentication redirects, etc.
  }

  /**
   * Delay execution for a specified number of milliseconds
   *
   * This method provides a simple way to pause execution for a given
   * number of milliseconds.
   *
   * @param {number} ms The number of milliseconds to delay
   * @returns {Promise<void>} A promise that resolves after the delay
   */
  private delay(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
  }
}

// Create singleton instance
export const apiService = new ApiService();

// Initialize tenant context from current domain
const hostname = window.location.hostname;
const subdomain = hostname.split('.')[0];
if (subdomain && subdomain !== 'www' && !hostname.includes('localhost')) {
  apiService.setTenantContext({ 
    tenantId: subdomain,
    domain: hostname,
  });
}

export default apiService; 