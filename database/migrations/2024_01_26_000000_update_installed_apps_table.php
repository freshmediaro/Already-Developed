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
        Schema::table('installed_apps', function (Blueprint $table) {
            // Add new columns for AppStore integration
            $table->foreignId('app_store_app_id')->nullable()->after('app_id')->constrained()->onDelete('set null');
            $table->string('module_name')->nullable()->after('app_type');
            $table->json('installation_data')->nullable()->after('version');
            $table->foreignId('purchase_id')->nullable()->after('last_used_at')->constrained('app_store_purchases')->onDelete('set null');
            
            // Update app_type enum to include new types
            $table->dropColumn('app_type');
        });

        // Re-add app_type with updated enum
        Schema::table('installed_apps', function (Blueprint $table) {
            $table->enum('app_type', [
                'vue', 
                'iframe', 
                'laravel_module', 
                'wordpress_plugin', 
                'external', 
                'system', 
                'api_integration'
            ])->default('vue')->after('app_name');
        });

        // Add indexes for new columns
        Schema::table('installed_apps', function (Blueprint $table) {
            $table->index('app_store_app_id');
            $table->index('purchase_id');
            $table->index(['team_id', 'app_store_app_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('installed_apps', function (Blueprint $table) {
            // Drop new columns
            $table->dropForeign(['app_store_app_id']);
            $table->dropForeign(['purchase_id']);
            $table->dropIndex(['app_store_app_id']);
            $table->dropIndex(['purchase_id']);
            $table->dropIndex(['team_id', 'app_store_app_id']);
            
            $table->dropColumn([
                'app_store_app_id',
                'module_name',
                'installation_data',
                'purchase_id'
            ]);
            
            // Restore original app_type enum
            $table->dropColumn('app_type');
        });
        
        Schema::table('installed_apps', function (Blueprint $table) {
            $table->enum('app_type', ['vue', 'iframe', 'external', 'system'])->default('vue')->after('app_name');
        });
    }
}; 