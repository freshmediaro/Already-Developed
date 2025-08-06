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
        Schema::create('installed_apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            
            // App identification
            $table->string('app_id'); // Unique identifier for the app type
            $table->string('app_name');
            $table->enum('app_type', ['vue', 'iframe', 'external', 'system'])->default('vue');
            $table->string('icon')->nullable();
            $table->string('url')->nullable(); // For iframe/external apps
            $table->json('iframe_config')->nullable(); // Iframe-specific configuration
            
            // Installation info
            $table->foreignId('installed_by')->constrained('users')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->string('version')->nullable();
            
            // Desktop positioning
            $table->integer('position_x')->nullable();
            $table->integer('position_y')->nullable();
            $table->boolean('desktop_visible')->default(true);
            $table->boolean('pinned_to_taskbar')->default(false);
            
            // Permissions and metadata
            $table->json('permissions')->nullable();
            $table->timestamp('last_used_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['team_id', 'app_id']);
            $table->index(['team_id', 'is_active']);
            $table->index('last_used_at');
            
            // Unique constraint to prevent duplicate apps per team
            $table->unique(['team_id', 'app_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installed_apps');
    }
}; 