# 🚀 Tenant App Development Guide

Welcome to the comprehensive guide for developing and publishing applications in our AppStore! This tutorial will walk you through everything you need to know to create, test, and publish your own apps for the platform.

## 📋 Quick Start Checklist

Before we begin, make sure you have:
- [ ] Active tenant account with development permissions
- [ ] Basic knowledge of Laravel, Vue.js, or web development
- [ ] Understanding of your app's requirements and functionality
- [ ] Access to the AppStore development environment

---

## 🎯 What Can You Build?

Our platform supports multiple types of applications:

### 🔧 **Laravel Modules** (Recommended)
Full-featured backend applications with:
- Custom API endpoints
- Database models and migrations
- Background jobs and queues
- Admin interfaces
- Integration with platform APIs

### 🖼️ **Vue Components**
Frontend applications that integrate with the desktop:
- Interactive user interfaces
- Real-time data visualization
- Form builders and widgets
- Dashboard components

### 🌐 **Iframe Applications**
External service integrations:
- Third-party tools and services
- Web-based applications
- SaaS integrations
- Legacy system bridges

### 🔗 **API Integrations**
Connect external services:
- Payment processors
- Communication tools
- Analytics platforms
- Social media APIs

---

## 🛠️ Available Platform APIs

As a tenant developer, you have access to these powerful APIs:

### 📊 **Core Platform APIs**

#### Authentication & Team Management
```php
// Get current user's teams
GET /api/teams

// Switch team context
PUT /api/current-team
```

#### File Management
```php
// File operations with tenant isolation
GET /api/files/context                 // Get file context
GET /api/files/folder                  // List folder contents
POST /api/files/folder                 // Create folder
POST /api/files/upload                 // Upload files
DELETE /api/files/{fileIds}            // Delete files
```

#### Notifications
```php
// Send notifications to users
POST /api/notifications/send
GET /api/notifications/                // Get notifications
PUT /api/notifications/settings        // Update notification preferences
```

#### Settings Management
```php
// Tenant-specific settings
GET /api/settings/                     // Get all settings
PUT /api/settings/                     // Update settings
GET /api/settings/{key}                // Get specific setting
PUT /api/settings/{key}                // Update specific setting
```

### 💰 **Payment & AI Integration**

#### AI Chat & Tokens
```php
// AI integration (if your app requires AI)
POST /api/ai-chat/message              // Send AI chat message
POST /api/ai-chat/execute              // Execute AI command
GET /api/ai-tokens/balance             // Check AI token balance
POST /api/ai-tokens/purchase           // Purchase AI tokens
```

#### Payment Processing
```php
// If your app handles payments
GET /api/payment-providers/            // Get available providers
POST /api/payment-providers/enable     // Enable payment provider
POST /api/wallet-topup-intent          // Create payment intent
```

### 🛍️ **E-commerce Integration** (AIMEOS)

#### Product Management
```php
// Product operations
GET /api/aimeos/products               // List products
POST /api/aimeos/products              // Create product
PUT /api/aimeos/products/{id}          // Update product
DELETE /api/aimeos/products/{id}       // Delete product
```

#### Order Processing
```php
// Order management
GET /api/aimeos/orders                 // List orders
GET /api/aimeos/orders/{id}            // Get order details
PUT /api/aimeos/orders/{id}/status     // Update order status
```

---

## 🏗️ Laravel Module Development

### Step 1: Plan Your Module

Before coding, define:
- **Purpose**: What problem does your app solve?
- **Features**: List core functionality
- **Dependencies**: What other apps/services are needed?
- **Data**: What information will you store?
- **UI**: How will users interact with your app?

### Step 2: Submit Your App Proposal

1. **Go to AppStore → "Develop Apps"**
2. **Fill out the app submission form**:

```json
{
  "name": "Customer Support Portal",
  "description": "Complete customer support ticket system",
  "app_type": "laravel_module",
  "module_name": "CustomerSupport",
  "pricing_type": "free",
  "category": "productivity",
  "detailed_description": "A comprehensive support system with ticket management, customer chat, and reporting.",
  "features": [
    "Ticket Creation & Management",
    "Real-time Chat Support",
    "Knowledge Base Integration",
    "Analytics Dashboard"
  ],
  "dependencies": [],
  "requires_ai_tokens": true,
  "ai_token_cost_per_use": 10
}
```

3. **Submit for review** - Our team will review and approve your proposal

### Step 3: Development Environment Setup

Once approved, the system automatically creates your module structure:

```
Modules/CustomerSupport/
├── Config/
│   ├── config.php              # Module configuration
│   └── permissions.php         # Permission definitions
├── Database/
│   ├── Migrations/             # Database migrations
│   ├── Seeders/               # Data seeders
│   └── Factories/             # Model factories
├── Entities/                   # Eloquent models
│   ├── Ticket.php
│   ├── Customer.php
│   └── SupportAgent.php
├── Http/
│   ├── Controllers/
│   │   ├── TicketController.php
│   │   ├── ChatController.php
│   │   └── Api/
│   │       └── TicketApiController.php
│   ├── Middleware/
│   └── Requests/
├── Providers/
│   ├── CustomerSupportServiceProvider.php
│   └── RouteServiceProvider.php
├── Resources/
│   ├── assets/
│   │   ├── js/
│   │   └── css/
│   ├── lang/
│   └── views/
│       ├── index.blade.php
│       └── components/
├── Routes/
│   ├── web.php                 # Web routes
│   ├── api.php                 # API routes
│   └── tenant.php              # Tenant-specific routes
├── Tests/
│   ├── Unit/
│   └── Feature/
├── composer.json               # Module dependencies
├── module.json                 # Module metadata
└── webpack.mix.js              # Asset compilation
```

### Step 4: Implement Core Functionality

#### Create Your Models

```php
<?php
// Modules/CustomerSupport/Entities/Ticket.php

namespace Modules\CustomerSupport\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    protected $table = 'customer_support_tickets';
    
    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'customer_id',
        'assigned_to',
        'team_id', // Important: Always include team_id for multi-tenancy
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationship to platform User model
    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'customer_id');
    }

    // Relationship to platform Team model
    public function team(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Team::class);
    }

    // Scope to current team (automatic tenant isolation)
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }
}
```

#### Create Database Migrations

```php
<?php
// Modules/CustomerSupport/Database/Migrations/2024_01_27_000000_create_customer_support_tickets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('team_id')->constrained('teams')->onDelete('cascade'); // Tenant isolation
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['team_id', 'status']);
            $table->index(['customer_id']);
            $table->index(['assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_support_tickets');
    }
};
```

#### Create API Controllers

```php
<?php
// Modules/CustomerSupport/Http/Controllers/Api/TicketApiController.php

namespace Modules\CustomerSupport\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\CustomerSupport\Entities\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TicketApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Automatic team scoping - no need to manually filter by tenant
        $teamId = $request->user()->currentTeam->id;
        
        $tickets = Ticket::forTeam($teamId)
            ->with(['customer', 'assignedTo'])
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->paginate(20);

        return response()->json([
            'data' => $tickets,
            'message' => 'Tickets retrieved successfully'
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'customer_id' => 'required|exists:users,id',
        ]);

        // Automatically include team_id for tenant isolation
        $validated['team_id'] = $request->user()->currentTeam->id;

        $ticket = Ticket::create($validated);

        // Send notification using platform API
        $this->sendTicketNotification($ticket);

        return response()->json([
            'data' => $ticket->load(['customer', 'assignedTo']),
            'message' => 'Ticket created successfully'
        ], 201);
    }

    private function sendTicketNotification(Ticket $ticket): void
    {
        // Use platform notification API
        app(\App\Services\NotificationService::class)->send([
            'type' => 'ticket_created',
            'title' => 'New Support Ticket',
            'message' => "Ticket #{$ticket->id}: {$ticket->title}",
            'data' => ['ticket_id' => $ticket->id],
            'user_id' => $ticket->customer_id,
            'team_id' => $ticket->team_id,
        ]);
    }
}
```

#### Create Frontend Components

```vue
<!-- Modules/CustomerSupport/Resources/views/components/TicketList.vue -->
<template>
  <div class="customer-support-tickets">
    <div class="header">
      <h2>Support Tickets</h2>
      <button @click="createTicket" class="btn btn-primary">
        New Ticket
      </button>
    </div>

    <div class="filters">
      <select v-model="statusFilter" @change="loadTickets">
        <option value="">All Statuses</option>
        <option value="open">Open</option>
        <option value="in_progress">In Progress</option>
        <option value="resolved">Resolved</option>
        <option value="closed">Closed</option>
      </select>
    </div>

    <div class="tickets-list">
      <div 
        v-for="ticket in tickets" 
        :key="ticket.id"
        class="ticket-card"
        @click="viewTicket(ticket)"
      >
        <div class="ticket-header">
          <h3>{{ ticket.title }}</h3>
          <span :class="['status', ticket.status]">{{ ticket.status }}</span>
        </div>
        <p class="ticket-description">{{ ticket.description }}</p>
        <div class="ticket-meta">
          <span>Customer: {{ ticket.customer.name }}</span>
          <span>Priority: {{ ticket.priority }}</span>
          <span>Created: {{ formatDate(ticket.created_at) }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiService } from '@/Tenant/ApiService'

const tickets = ref([])
const statusFilter = ref('')

const loadTickets = async () => {
  try {
    const response = await apiService.get('/api/customer-support/tickets', {
      params: { status: statusFilter.value }
    })
    tickets.value = response.data.data
  } catch (error) {
    console.error('Failed to load tickets:', error)
  }
}

const createTicket = () => {
  // Open ticket creation modal or navigate to form
  // Implementation depends on your UI framework
}

const viewTicket = (ticket) => {
  // Open ticket details view
  // Integration with platform's window manager
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

onMounted(() => {
  loadTickets()
})
</script>

<style scoped>
.customer-support-tickets {
  padding: 20px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.ticket-card {
  border: 1px solid #e0e0e0;
  border-radius: 6px;
  padding: 15px;
  margin-bottom: 10px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.ticket-card:hover {
  background-color: #f5f5f5;
}

.status {
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: bold;
}

.status.open { background: #fef3c7; color: #92400e; }
.status.in_progress { background: #dbeafe; color: #1e40af; }
.status.resolved { background: #d1fae5; color: #065f46; }
.status.closed { background: #f3f4f6; color: #374151; }
</style>
```

### Step 5: Integration with Platform

#### Using Platform Services

```php
<?php
// Modules/CustomerSupport/Services/TicketService.php

namespace Modules\CustomerSupport\Services;

use Modules\CustomerSupport\Entities\Ticket;
use App\Services\NotificationService;
use App\Services\AiChatService;

class TicketService
{
    public function __construct(
        private NotificationService $notificationService,
        private AiChatService $aiChatService
    ) {}

    public function createTicket(array $data): Ticket
    {
        $ticket = Ticket::create($data);

        // Send notification to support team
        $this->notificationService->send([
            'type' => 'ticket_created',
            'title' => 'New Support Ticket',
            'message' => "#{$ticket->id}: {$ticket->title}",
            'data' => ['ticket_id' => $ticket->id],
            'team_id' => $ticket->team_id,
        ]);

        return $ticket;
    }

    public function generateSuggestedResponse(Ticket $ticket): string
    {
        // Use AI to suggest response
        $response = $this->aiChatService->sendMessage([
            'message' => "Generate a helpful response for this support ticket: {$ticket->description}",
            'context' => 'customer_support',
            'user_id' => auth()->id(),
        ]);

        return $response['data']['message'] ?? '';
    }
}
```

#### Register API Routes

```php
<?php
// Modules/CustomerSupport/Routes/api.php

use Modules\CustomerSupport\Http\Controllers\Api\TicketApiController;

Route::middleware(['auth:sanctum'])->prefix('customer-support')->group(function () {
    Route::apiResource('tickets', TicketApiController::class);
    Route::post('tickets/{ticket}/assign', [TicketApiController::class, 'assign']);
    Route::post('tickets/{ticket}/close', [TicketApiController::class, 'close']);
    Route::get('tickets/{ticket}/history', [TicketApiController::class, 'history']);
});
```

### Step 6: Testing Your Module

#### Create Feature Tests

```php
<?php
// Modules/CustomerSupport/Tests/Feature/TicketManagementTest.php

namespace Modules\CustomerSupport\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Team;
use Modules\CustomerSupport\Entities\Ticket;

class TicketManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->team = Team::factory()->create();
        $this->user->teams()->attach($this->team);
    }

    public function test_user_can_create_ticket(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/customer-support/tickets', [
            'title' => 'Test Ticket',
            'description' => 'This is a test ticket',
            'priority' => 'medium',
            'customer_id' => $this->user->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('customer_support_tickets', [
            'title' => 'Test Ticket',
            'team_id' => $this->team->id,
        ]);
    }

    public function test_tickets_are_scoped_to_team(): void
    {
        // Create tickets for different teams
        $otherTeam = Team::factory()->create();
        
        $myTicket = Ticket::factory()->create(['team_id' => $this->team->id]);
        $otherTicket = Ticket::factory()->create(['team_id' => $otherTeam->id]);

        $this->actingAs($this->user);

        $response = $this->getJson('/api/customer-support/tickets');

        $response->assertStatus(200);
        $ticketIds = collect($response->json('data.data'))->pluck('id');
        
        $this->assertContains($myTicket->id, $ticketIds);
        $this->assertNotContains($otherTicket->id, $ticketIds);
    }
}
```

---

## 📱 Vue Component Development

### Simple Widget Example

```vue
<!-- CustomerSatisfactionWidget.vue -->
<template>
  <div class="satisfaction-widget">
    <h3>Customer Satisfaction</h3>
    <div class="metrics">
      <div class="metric">
        <span class="value">{{ averageRating }}</span>
        <span class="label">Average Rating</span>
      </div>
      <div class="metric">
        <span class="value">{{ totalResponses }}</span>
        <span class="label">Total Responses</span>
      </div>
    </div>
    <canvas ref="chartCanvas"></canvas>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiService } from '@/Tenant/ApiService'

const averageRating = ref(0)
const totalResponses = ref(0)
const chartCanvas = ref<HTMLCanvasElement>()

const loadSatisfactionData = async () => {
  try {
    const response = await apiService.get('/api/customer-satisfaction/metrics')
    averageRating.value = response.data.average_rating
    totalResponses.value = response.data.total_responses
    
    // Render chart using the data
    renderChart(response.data.ratings_distribution)
  } catch (error) {
    console.error('Failed to load satisfaction data:', error)
  }
}

const renderChart = (data: any) => {
  // Implementation using Chart.js or similar
}

onMounted(() => {
  loadSatisfactionData()
})
</script>
```

---

## 🌐 Iframe App Integration

For external services, create an iframe app configuration:

```json
{
  "name": "Photoshop Web",
  "app_type": "iframe",
  "iframe_config": {
    "url": "https://photopea.com",
    "sandbox": "allow-scripts allow-same-origin allow-forms",
    "permissions": ["camera", "microphone"],
    "auto_resize": true,
    "secure_context": true
  },
  "pricing_type": "monthly",
  "monthly_price": 29.99,
  "requires_external_api": true,
  "external_api_config": {
    "provider": "Adobe Creative SDK",
    "auth_required": true,
    "setup_instructions": "Connect your Adobe account to enable full features"
  }
}
```

---

## 🔗 API Integration Apps

Connect external services with configuration:

```php
<?php
// Modules/SlackIntegration/Services/SlackApiService.php

namespace Modules\SlackIntegration\Services;

class SlackApiService
{
    private string $accessToken;
    private string $baseUrl = 'https://slack.com/api';

    public function __construct()
    {
        // Get tenant-specific Slack configuration
        $this->accessToken = app(\App\Services\SettingsService::class)
            ->get('slack.access_token');
    }

    public function sendMessage(string $channel, string $message): array
    {
        return $this->apiCall('chat.postMessage', [
            'channel' => $channel,
            'text' => $message,
        ]);
    }

    public function getChannels(): array
    {
        return $this->apiCall('conversations.list');
    }

    private function apiCall(string $method, array $params = []): array
    {
        // Implementation of Slack API calls
        // Include proper error handling and rate limiting
    }
}
```

---

## 📋 Publishing Your App

### Step 1: Submit for Review

1. **Complete development and testing**
2. **Update module metadata**:

```json
{
  "name": "CustomerSupport",
  "version": "1.0.0",
  "description": "Complete customer support solution",
  "keywords": ["support", "tickets", "customer-service"],
  "license": "MIT",
  "authors": [
    {
      "name": "Your Name",
      "email": "your@email.com"
    }
  ],
  "require": {
    "php": "^8.1",
    "laravel/framework": "^10.0"
  },
  "autoload": {
    "psr-4": {
      "Modules\\CustomerSupport\\": ""
    }
  }
}
```

3. **Submit through AppStore interface**

### Step 2: Review Process

Our team will review:
- **Code quality** and security
- **Performance** and scalability
- **User experience** and design
- **Documentation** completeness
- **Testing** coverage

### Step 3: Publication

Once approved:
- App becomes available in the store
- Users can install and purchase
- You receive revenue share (if paid app)
- Regular updates and support

---

## 💡 Best Practices

### Security
- ✅ Always validate user input
- ✅ Use proper authorization checks
- ✅ Implement team-based data scoping
- ✅ Sanitize output to prevent XSS
- ✅ Use CSRF protection for forms

### Performance
- ✅ Use database indexes appropriately
- ✅ Implement caching for frequent queries
- ✅ Optimize API response sizes
- ✅ Use lazy loading for large datasets
- ✅ Minimize database queries

### User Experience
- ✅ Follow platform design patterns
- ✅ Provide clear error messages
- ✅ Include loading states
- ✅ Implement responsive design
- ✅ Add keyboard shortcuts

### Multi-Tenancy
- ✅ Always include team_id in database queries
- ✅ Use team-scoped relationships
- ✅ Validate team membership
- ✅ Isolate data between tenants
- ✅ Test cross-tenant data leakage

---

## 🆘 Getting Help

### Documentation
- **Platform APIs**: Available in the developer portal
- **Laravel Modules**: https://laravelmodules.com/docs/12/
- **Vue.js Guide**: https://vuejs.org/guide/
- **Tenancy Documentation**: Your tenant-specific docs

### Support Channels
- **Developer Forum**: Community discussions and Q&A
- **Email Support**: developer-support@platform.com
- **Live Chat**: Available in the developer dashboard
- **Video Tutorials**: Step-by-step guides and examples

### Code Examples
- **Sample Apps**: Browse our collection of example applications
- **Starter Templates**: Pre-built module templates
- **API Documentation**: Interactive API explorer
- **Best Practices**: Curated examples and patterns

---

## 🎉 Success Stories

### Customer Portal Module
**Developer**: TechCorp Tenant  
**Revenue**: $2,400/month  
**Downloads**: 850+  

*"The modular architecture made it easy to build exactly what our customers needed. The built-in APIs saved us months of development time."*

### Analytics Dashboard Widget
**Developer**: DataViz Solutions  
**Revenue**: $180/month  
**Downloads**: 1,200+  

*"Vue component development was straightforward, and the platform's styling made our widget look professional immediately."*

---

**Ready to start building? 🚀**

Visit the **AppStore → Developer Portal** to submit your first app idea and begin your journey as a platform developer!

Remember: Great apps solve real problems. Focus on your users' needs, follow our best practices, and don't hesitate to reach out for help. Our success is your success! 🌟 