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
        Schema::create('desktop_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');
            
            // Appearance settings
            $table->string('theme')->default('auto'); // auto, light, dark
            $table->string('accent_color')->default('blue');
            $table->string('wallpaper')->default('default');
            
            // Desktop settings
            $table->boolean('desktop_icons_enabled')->default(true);
            $table->boolean('desktop_auto_arrange')->default(false);
            $table->string('desktop_icon_size')->default('medium'); // small, medium, large
            
            // System settings
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('sound_enabled')->default(true);
            $table->string('language')->default('en');
            
            // Privacy settings
            $table->boolean('analytics_enabled')->default(false);
            $table->boolean('crash_reports_enabled')->default(false);
            
            // UI settings
            $table->boolean('window_animations')->default(true);
            $table->boolean('transparency_effects')->default(true);
            
            // Layout configuration (JSON)
            $table->json('desktop_layout')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'team_id']);
            $table->unique(['user_id', 'team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('desktop_configurations');
    }
}; 