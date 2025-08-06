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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, array, object
            $table->boolean('is_encrypted')->default(false);
            
            // Multi-tenancy support
            $table->string('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('team_id')->nullable()->index();
            
            // Context metadata
            $table->json('context')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Ensure unique settings per context
            $table->unique(['key', 'tenant_id', 'user_id', 'team_id'], 'settings_unique_context');
            
            // Performance indexes
            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'team_id']);
            $table->index(['key', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
}; 