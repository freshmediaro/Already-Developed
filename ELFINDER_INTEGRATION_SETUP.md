# ElFinder Integration Setup Guide

This guide walks you through setting up the File Explorer app with ElFinder, Flysystem, tenant buckets, and Cloudflare R2 storage.

## Prerequisites

- Laravel 11.x
- PHP 8.2+
- Node.js 18+
- Cloudflare R2 account
- MySQL/PostgreSQL database

## Installation Steps

### 1. Install Backend Dependencies

```bash
composer require barryvdh/laravel-elfinder:^0.5.3
composer require league/flysystem:^3.0
composer require league/flysystem-aws-s3-v3:^3.0
composer require stancl/tenancy:^4.0
# Note: This project should be based on [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/)
# If starting fresh, use: composer create-project laravel/vue-starter-kit your-project-name
# For existing projects, the starter kit components are already integrated
composer require laravel/sanctum:^4.0
composer require spatie/laravel-permission:^6.0
composer require spatie/laravel-activitylog:^4.8
composer require spatie/laravel-media-library:^11.0

# Install tenant-buckets (from the specified repo)
composer require vidwanco/tenant-buckets:dev-tenancy-v4
```

### 2. Install Frontend Dependencies

```bash
npm install elfinder @types/elfinder
```

### 3. Publish Configuration Files

```bash
# Publish ElFinder config
php artisan vendor:publish --provider="Barryvdh\Elfinder\ElfinderServiceProvider" --tag=config

# Publish Tenancy config
php artisan vendor:publish --tag=tenancy-config
php artisan vendor:publish --tag=tenancy-migrations

# Note: [Laravel Vue Starter Kit](https://github.com/laravel/vue-starter-kit/) components are pre-configured
# No additional installation commands needed for the starter kit
```

### 4. Environment Configuration

Create your `.env` file based on the provided `.env.example` and configure these essential variables:

```env
# Cloudflare R2 Configuration
CLOUDFLARE_R2_ACCESS_KEY_ID=your_r2_access_key_id
CLOUDFLARE_R2_SECRET_ACCESS_KEY=your_r2_secret_access_key
CLOUDFLARE_R2_BUCKET=your_r2_bucket_name
CLOUDFLARE_R2_ENDPOINT=https://your_account_id.r2.cloudflarestorage.com
CLOUDFLARE_R2_URL=https://your_custom_domain.com

# File Management
ELFINDER_MAX_UPLOAD_SIZE=100M
ELFINDER_ALLOWED_EXTENSIONS="jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,json,xml,zip,rar,7z,mp3,mp4,avi,mov,wmv"

# Tenancy
TENANCY_CACHE_TTL=3600
TEAM_MAX_STORAGE_GB=100
```

### 5. Database Migrations

```bash
# Run central migrations
php artisan migrate

# Run tenant migrations
php artisan tenants:migrate
```

### 6. Register Service Providers

Add to `config/app.php` (if not auto-discovered):

```php
'providers' => [
    // ... existing providers
    Barryvdh\Elfinder\ElfinderServiceProvider::class,
    App\Providers\TenantServiceProvider::class,
],
```

### 7. Configure Routes

Add to your route configuration (e.g., `routes/web.php` or create a dedicated routes file):

```php
// Include the tenant file routes
require __DIR__.'/tenant-files.php';
```

### 8. Create Required Directories

```bash
mkdir -p public/packages/barryvdh/elfinder
mkdir -p storage/app/tenant-files
mkdir -p storage/app/temp
```

### 9. Set Up ElFinder Assets

```bash
# Publish ElFinder assets
php artisan vendor:publish --provider="Barryvdh\Elfinder\ElfinderServiceProvider" --tag=public
```

Or manually copy ElFinder assets to `public/packages/barryvdh/elfinder/`:
- elfinder.min.js
- elfinder.min.css
- And other required assets

## Configuration Details

### 1. Cloudflare R2 Setup

1. Create a Cloudflare R2 bucket
2. Generate API tokens with R2 permissions
3. Configure a custom domain (optional but recommended)
4. Set up CORS if needed:

```json
[
  {
    "AllowedOrigins": ["https://your-domain.com"],
    "AllowedMethods": ["GET", "PUT", "POST", "DELETE"],
    "AllowedHeaders": ["*"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3000
  }
]
```

### 2. Tenant Configuration

The system uses tenant isolation with the following structure:
```
bucket/
├── tenant-{tenant_id}/
│   ├── team-{team_id}/
│   │   ├── documents/
│   │   ├── images/
│   │   ├── uploads/
│   │   ├── shared/
│   │   └── trash/
│   └── team-{other_team_id}/
└── tenant-{other_tenant_id}/
```

### 3. File Permissions

The system implements multi-layer security:
- **Tenant Isolation**: Users can only access their tenant's files
- **Team Isolation**: Users can only access their current team's files
- **Permission-based Access**: Read, write, delete permissions per user/role
- **File Locking**: Prevents concurrent editing conflicts

## Frontend Integration

### 1. ElFinder Mode

The File Explorer app supports two modes:
- **Custom UI Mode**: Uses the existing custom file explorer interface
- **ElFinder Mode**: Shows the full ElFinder interface within the app window

Toggle between modes using the toolbar button or programmatically:

```typescript
// Switch to ElFinder mode
this.toggleElFinderMode();
```

### 2. Custom Styling

The ElFinder interface uses your app's styling while maintaining only the file icons from ElFinder. The custom CSS overrides ensure consistent theming.

### 3. Event Integration

The File Explorer app integrates with the global event system:

```typescript
// Listen for file operations
eventSystem.on('file-explorer:folder-changed', (payload) => {
  console.log('Folder changed:', payload.data);
});

eventSystem.on('file-explorer:selection-changed', (payload) => {
  console.log('Selection changed:', payload.data);
});
```

## API Endpoints

### Core File Management

- `GET /api/files/context` - Get tenant/team context
- `GET /api/files/folder?path={path}` - Get folder contents (custom UI)
- `POST /api/files/folder` - Create new folder
- `POST /api/files/upload` - Upload files
- `DELETE /api/files/{fileIds}` - Delete files
- `ANY /api/files/elfinder` - ElFinder connector endpoint

### Admin Endpoints

- `POST /api/files/admin/initialize-storage` - Initialize tenant storage structure
- `GET /api/files/admin/storage-stats` - Get storage usage statistics
- `POST /api/files/admin/empty-trash` - Empty trash folder

## Security Features

### 1. Access Control

- Middleware-based tenant and team isolation
- Role-based permissions for file operations
- File type validation and size limits
- Hidden system files and directories

### 2. File Validation

```php
// Allowed file extensions are configurable
'allowed_extensions' => [
    'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    'txt', 'csv', 'json', 'xml',
    'zip', 'rar', '7z',
    'mp3', 'mp4', 'avi', 'mov', 'wmv',
],
```

### 3. Storage Quotas

Teams have configurable storage limits:
- Default: 100GB per team
- Automatic quota checking before uploads
- Storage usage statistics and monitoring

## Performance Optimization

### 1. Caching

- Tenant configuration caching (1 hour TTL)
- File permission caching (5 minutes TTL)
- Storage statistics caching (5 minutes TTL)

### 2. Thumbnail Generation

```php
// Configure thumbnail sizes
'thumbnail_sizes' => [
    'small' => [150, 150],
    'medium' => [300, 300],
    'large' => [600, 600],
],
```

## Troubleshooting

### Common Issues

1. **ElFinder not loading**: Check that assets are published and accessible
2. **File upload failures**: Verify R2 credentials and bucket permissions
3. **Tenant isolation issues**: Ensure middleware is properly configured
4. **Permission errors**: Check user roles and team memberships

### Debug Mode

Enable debug mode in ElFinder configuration:

```php
'options' => [
    'debug' => env('APP_DEBUG', false),
    // ... other options
],
```

### Logs

Check these log files for troubleshooting:
- `storage/logs/laravel.log` - General application logs
- Browser developer console - Frontend errors
- Cloudflare R2 access logs - Storage-related issues

## Advanced Features

### 1. File Versioning

Implement file versioning by storing multiple copies:
```
files/
├── document.pdf (current)
├── .versions/
│   ├── document_v1.pdf
│   └── document_v2.pdf
```

### 2. File Sharing

Extend the permission system to support file sharing between teams or external users.

### 3. Real-time Collaboration

Integrate with WebSocket for real-time file operations and collaborative editing.

## Production Deployment

### 1. Environment Setup

- Use production-grade database (PostgreSQL recommended)
- Configure Redis for caching and sessions
- Set up proper file permissions (755 for directories, 644 for files)
- Enable OPcache for PHP performance

### 2. Security Hardening

- Use HTTPS for all connections
- Configure proper CORS headers
- Implement rate limiting for file operations
- Regular security updates for all dependencies

### 3. Monitoring

- Set up application performance monitoring
- Monitor storage usage and costs
- Log file operations for audit trails
- Alert on unusual activity patterns

This completes the ElFinder integration setup with tenant isolation, Cloudflare R2 storage, and comprehensive security features. 