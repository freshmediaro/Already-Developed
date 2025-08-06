# Notification System Environment Setup

This document provides comprehensive setup instructions for the tenant notification system, ensuring full compliance with `stancl/tenancy v4` broadcasting requirements.

## Required Environment Variables

### Broadcasting Configuration (Laravel Reverb)

```env
# Laravel Reverb Configuration
BROADCAST_DRIVER=reverb
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

# For production, use HTTPS
# REVERB_HOST=your-domain.com
# REVERB_PORT=443
# REVERB_SCHEME=https

# Frontend Environment Variables (for Vite)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
VITE_BROADCAST_DRIVER="${BROADCAST_DRIVER}"

# Broadcasting Tenant Isolation (required for tenancy v4)
BROADCAST_TENANT_ISOLATION=true
BROADCAST_TENANT_PREFIX=tenant
BROADCAST_USER_PREFIX=user
BROADCAST_TEAM_PREFIX=team
```

### AWS SNS Configuration

```env
# AWS SNS for SMS Notifications
AWS_SNS_ACCESS_KEY_ID=your-access-key
AWS_SNS_SECRET_ACCESS_KEY=your-secret-key
AWS_SNS_REGION=us-east-1
AWS_SNS_SENDER_ID="YourApp"
AWS_SNS_SMS_TYPE=Transactional
AWS_SNS_MAX_PRICE=0.10

# SMS Templates (optional - can be customized)
AWS_SNS_TENANT_REGISTRATION_TEMPLATE="Welcome to {app_name}! Your account has been created. Verification code: {code}"
AWS_SNS_PHONE_VERIFICATION_TEMPLATE="Your {app_name} verification code is: {code}"
AWS_SNS_ORDER_CONFIRMATION_TEMPLATE="Order #{order_id} confirmed! Thank you for shopping with {tenant_name}."
AWS_SNS_SECURITY_ALERT_TEMPLATE="Security alert for your {app_name} account. If this wasn't you, please contact support."
```

### AWS PubSub Configuration

```env
# AWS PubSub for Backend Event-Driven Architecture
AWS_PUBSUB_ACCESS_KEY_ID=your-pubsub-access-key
AWS_PUBSUB_SECRET_ACCESS_KEY=your-pubsub-secret-key
AWS_PUBSUB_REGION=us-east-1
AWS_PUBSUB_TOPIC_ARN=arn:aws:sns:us-east-1:123456789:your-topic
AWS_PUBSUB_SQS_QUEUE=your-sqs-queue

# Event Routing Topics
AWS_PUBSUB_TENANT_TOPIC=arn:aws:sns:us-east-1:123456789:tenant-notifications
AWS_PUBSUB_PLATFORM_TOPIC=arn:aws:sns:us-east-1:123456789:platform-events
AWS_PUBSUB_AI_TOPIC=arn:aws:sns:us-east-1:123456789:ai-processing
AWS_PUBSUB_PAYMENT_TOPIC=arn:aws:sns:us-east-1:123456789:payment-events
```

### Notification Service Configuration

```env
# Rate Limiting
NOTIFICATION_SMS_RATE_LIMIT=5
NOTIFICATION_EMAIL_RATE_LIMIT=10
NOTIFICATION_PUSH_RATE_LIMIT=20

# Delivery Preferences
NOTIFICATION_RETRY_ATTEMPTS=3
NOTIFICATION_RETRY_DELAY=300

# Tenant Customization
NOTIFICATION_ALLOW_CUSTOM_TEMPLATES=true
NOTIFICATION_ALLOW_CUSTOM_CHANNELS=true
NOTIFICATION_ALLOW_DISABLE=true
```

### Redis Configuration (for real-time features)

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0
```

### Database Configuration

```env
# Multi-tenant database setup (ensure proper configuration)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_central_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## Installation Steps

### 1. Install Required PHP Packages

```bash
# Install Laravel Reverb
composer require laravel/reverb

# Install AWS SNS notification channel
composer require laravel-notification-channels/aws-sns

# Install AWS PubSub
composer require pod-point/laravel-aws-pubsub

# Install Pusher PHP Server (for Echo compatibility)
composer require pusher/pusher-php-server
```

### 2. Publish Configuration Files

```bash
# Publish Reverb configuration
php artisan reverb:install

# Publish broadcasting configuration (if not exists)
php artisan vendor:publish --tag=laravel-assets

# Publish SNS configuration
php artisan vendor:publish --provider="NotificationChannels\AwsSns\AwsSnsServiceProvider"
```

### 3. Update Tenancy Configuration

**IMPORTANT**: Add the `BroadcastChannelPrefixBootstrapper` to your tenancy configuration:

```php
// config/tenancy.php
'bootstrappers' => [
    Bootstrappers\DatabaseTenancyBootstrapper::class,
    Bootstrappers\CacheTenancyBootstrapper::class,
    Bootstrappers\FilesystemTenancyBootstrapper::class,
    Bootstrappers\QueueTenancyBootstrapper::class,
    Bootstrappers\BroadcastChannelPrefixBootstrapper::class, // Add this line
    // Bootstrappers\RedisTenancyBootstrapper::class,
],
```

### 4. Configure Broadcasting Routes (Required for Tenancy v4)

Ensure your `bootstrap/app.php` includes the channels route:

```php
// bootstrap/app.php
->withRouting(
    web: __DIR__.'/../routes/web.php',
    api: __DIR__.'/../routes/api.php',
    commands: __DIR__.'/../routes/console.php',
    channels: __DIR__.'/../routes/channels.php', // Make sure this is included
    health: '/up',
    // ... rest of configuration
)
```

The `routes/channels.php` file should contain tenant-aware channel authorization:

```php
// routes/channels.php
use Illuminate\Support\Facades\Broadcast;
use App\Models\User;

// User-specific notification channels (tenant-aware)
Broadcast::channel('tenant.{tenantId}.user.{userId}', function (User $user, string $tenantId, int $userId) {
    return (int) $user->id === (int) $userId && tenant('id') === $tenantId;
});

// Team-specific notification channels (tenant-aware)
Broadcast::channel('tenant.{tenantId}.team.{teamId}', function (User $user, string $tenantId, int $teamId) {
    return $user->belongsToTeam($teamId) && tenant('id') === $tenantId;
});
```

### 5. Run Database Migrations

```bash
php artisan migrate
```

### 6. Start Queue Workers

```bash
# Start the default queue worker
php artisan queue:work

# Or start with specific queue for notifications
php artisan queue:work --queue=notifications,default
```

### 7. Start Laravel Reverb Server

```bash
# Start Reverb server for real-time broadcasting
php artisan reverb:start
```

## package.json Dependencies

Add these dependencies to your frontend:

```json
{
  "devDependencies": {
    "laravel-echo": "^1.16.0",
    "pusher-js": "^8.4.0-rc2",
    "@types/pusher-js": "^5.1.0"
  }
}
```

Install frontend dependencies:

```bash
npm install
npm run build
```

## Frontend Environment Variables (Vite)

Ensure your `vite.config.js` exposes the necessary environment variables:

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
        }),
    ],
    define: {
        'import.meta.env.VITE_BROADCAST_DRIVER': JSON.stringify(process.env.VITE_BROADCAST_DRIVER),
        'import.meta.env.VITE_REVERB_APP_KEY': JSON.stringify(process.env.VITE_REVERB_APP_KEY),
        'import.meta.env.VITE_REVERB_HOST': JSON.stringify(process.env.VITE_REVERB_HOST),
        'import.meta.env.VITE_REVERB_PORT': JSON.stringify(process.env.VITE_REVERB_PORT),
        'import.meta.env.VITE_REVERB_SCHEME': JSON.stringify(process.env.VITE_REVERB_SCHEME),
    },
});
```

## Production Deployment Configuration

### Nginx Configuration for Reverb

```nginx
# WebSocket proxy for Reverb
location /app/ {
    proxy_pass http://127.0.0.1:8080;
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_cache_bypass $http_upgrade;
}
```

### Supervisor Configuration for Reverb

```ini
[program:reverb]
command=php /var/www/html/artisan reverb:start --host=0.0.0.0 --port=8080
directory=/var/www/html
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/reverb.log
```

### Supervisor Configuration for Queue Workers

```ini
[program:notification-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work redis --queue=notifications,default --sleep=3 --tries=3 --max-time=3600
directory=/var/www/html
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/notification-worker.log
```

## AWS Configuration

### SNS Topic Setup

Create SNS topics for different notification types:

```bash
# Create topic for tenant notifications
aws sns create-topic --name tenant-notifications --region us-east-1

# Create topic for platform events
aws sns create-topic --name platform-events --region us-east-1

# Subscribe your application endpoint to the topics
aws sns subscribe --topic-arn arn:aws:sns:us-east-1:123456789:tenant-notifications \
  --protocol https --notification-endpoint https://your-domain.com/webhooks/sns
```

### IAM Policy for SNS

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "sns:Publish",
        "sns:CreateTopic",
        "sns:Subscribe",
        "sns:Unsubscribe",
        "sns:ListTopics",
        "sns:GetTopicAttributes"
      ],
      "Resource": "*"
    }
  ]
}
```

## Security Considerations

### API Authentication

Ensure your broadcasting routes are properly authenticated:

```php
// routes/channels.php
Broadcast::channel('tenant.{tenantId}.user.{userId}', function ($user, $tenantId, $userId) {
    return (int) $user->id === (int) $userId && tenant('id') === $tenantId;
});

Broadcast::channel('tenant.{tenantId}.team.{teamId}', function ($user, $tenantId, $teamId) {
    return $user->belongsToTeam($teamId) && tenant('id') === $tenantId;
});
```

### Rate Limiting

Configure rate limiting for notification endpoints:

```php
// In RouteServiceProvider
RateLimiter::for('notifications', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

## Testing Configuration

### Local Development

For local development, you can use the following simplified configuration:

```env
BROADCAST_DRIVER=log
QUEUE_CONNECTION=sync
NOTIFICATION_SMS_RATE_LIMIT=1
AWS_SNS_ACCESS_KEY_ID=test
AWS_SNS_SECRET_ACCESS_KEY=test
```

### Testing Commands

```bash
# Test notification system
php artisan notification:test

# Test broadcasting
php artisan tinker
>>> broadcast(new App\Events\NotificationEvent(['title' => 'Test'], 1));

# Test queue processing
php artisan queue:work --once
```

## Monitoring and Logging

### Log Configuration

```env
LOG_CHANNEL=stack
LOG_LEVEL=info

# Notification-specific logging
NOTIFICATION_LOG_CHANNEL=notifications
BROADCAST_LOG_ENABLED=true
```

### Monitoring Commands

```bash
# Monitor queue status
php artisan queue:monitor

# Monitor Reverb connections
php artisan reverb:status

# Check notification settings
php artisan tinker
>>> App\Models\NotificationSettings::where('user_id', 1)->first();
```

## Troubleshooting

### Common Issues

1. **Broadcasting not working**: Check Redis connection and Reverb server status
2. **SMS not sending**: Verify AWS SNS credentials and phone number format
3. **Notifications not appearing**: Check user notification settings and do-not-disturb mode
4. **Performance issues**: Monitor queue worker memory usage and restart as needed

### Debug Commands

```bash
# Check broadcasting configuration
php artisan config:show broadcasting

# Test notification delivery
php artisan notification:test --user=1 --type=test

# Monitor real-time events
php artisan reverb:monitor
```

## Production Checklist

- [ ] Redis server configured and running
- [ ] Reverb server configured with proper SSL
- [ ] AWS SNS credentials configured
- [ ] Queue workers running with Supervisor
- [ ] Nginx WebSocket proxy configured
- [ ] Rate limiting configured
- [ ] Monitoring and alerting set up
- [ ] Backup strategy for notification data
- [ ] SSL certificates installed
- [ ] Firewall rules configured for WebSocket connections

This configuration ensures your notification system will work properly in production with full tenant isolation, real-time capabilities, and SMS support. 