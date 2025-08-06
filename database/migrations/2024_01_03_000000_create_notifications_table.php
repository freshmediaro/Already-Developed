<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('tenant_id')->nullable(); // For tenant isolation
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Additional fields for enhanced notification management
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('category')->default('general'); // general, orders, payments, security, etc.
            $table->string('channel')->default('database'); // database, broadcast, mail, sms, push, slack
            $table->string('source_app')->nullable(); // Which app generated this notification
            $table->string('action_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();

            // Indexes for performance
            $table->index(['user_id', 'team_id', 'tenant_id', 'read_at', 'created_at'], 'notifications_user_team_read_idx');
            $table->index(['user_id', 'team_id', 'category'], 'notifications_user_team_category_idx');
            $table->index(['user_id', 'team_id', 'priority'], 'notifications_user_team_priority_idx');
            $table->index(['source_app', 'tenant_id'], 'notifications_app_tenant_idx');
            $table->index(['expires_at'], 'notifications_expires_idx');
            $table->index(['tenant_id', 'created_at'], 'notifications_tenant_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
}; 