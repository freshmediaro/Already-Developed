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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('tenant_id')->nullable(); // For tenant isolation
            
            // Basic notification channels
            $table->boolean('desktop_enabled')->default(true);
            $table->boolean('email_enabled')->default(true);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('push_enabled')->default(true);
            $table->boolean('sound_enabled')->default(true);
            
            // Display preferences
            $table->string('stacking_mode')->default('three'); // one, three, all
            $table->boolean('do_not_disturb')->default(false);
            
            // Quiet hours
            $table->boolean('quiet_hours_enabled')->default(false);
            $table->time('quiet_hours_start')->default('22:00');
            $table->time('quiet_hours_end')->default('08:00');
            
            // Category and app preferences
            $table->json('notification_categories')->nullable(); // Which categories are enabled
            $table->json('muted_apps')->nullable(); // List of muted app IDs
            
            // Email preferences
            $table->string('email_frequency')->default('immediate'); // immediate, daily, weekly, never
            $table->boolean('summary_email_enabled')->default(true);
            
            // Special notification types
            $table->boolean('security_alerts_enabled')->default(true);
            $table->boolean('marketing_enabled')->default(false);
            
            $table->timestamps();
            
            // Indexes
            $table->unique(['user_id', 'team_id'], 'notification_settings_user_team_unique');
            $table->index(['tenant_id'], 'notification_settings_tenant_idx');
            $table->index(['user_id', 'tenant_id'], 'notification_settings_user_tenant_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
}; 