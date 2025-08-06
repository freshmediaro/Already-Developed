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
        Schema::create('app_store_apps', function (Blueprint $table) {
            $table->id();
            
            // Basic app information
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->longText('detailed_description')->nullable();
            $table->string('icon')->nullable();
            $table->json('screenshots')->nullable();
            
            // App type and module info
            $table->enum('app_type', [
                'vue', 
                'iframe', 
                'laravel_module', 
                'wordpress_plugin', 
                'external', 
                'system', 
                'api_integration'
            ])->default('vue');
            $table->string('module_name')->nullable(); // For Laravel modules
            $table->string('version')->default('1.0.0');
            
            // Developer information
            $table->foreignId('developer_id')->constrained('users')->onDelete('cascade');
            $table->string('developer_name');
            $table->string('developer_website')->nullable();
            $table->string('developer_email')->nullable();
            
            // Pricing
            $table->enum('pricing_type', ['free', 'one_time', 'monthly', 'freemium'])->default('free');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('monthly_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            
            // Publishing and approval
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->enum('approval_status', [
                'pending', 
                'approved', 
                'rejected', 
                'needs_changes', 
                'under_review'
            ])->default('pending');
            $table->text('approval_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('published_at')->nullable();
            
            // Statistics
            $table->bigInteger('download_count')->default(0);
            $table->bigInteger('active_installs')->default(0);
            $table->decimal('rating_average', 3, 2)->default(0);
            $table->bigInteger('rating_count')->default(0);
            
            // Technical requirements
            $table->string('minimum_php_version')->nullable();
            $table->string('minimum_laravel_version')->nullable();
            
            // Links and documentation
            $table->string('repository_url')->nullable();
            $table->string('documentation_url')->nullable();
            $table->string('support_url')->nullable();
            $table->string('license')->nullable();
            
            // Tags and metadata
            $table->json('tags')->nullable();
            $table->json('iframe_config')->nullable(); // For iframe apps
            
            // AI and external integrations
            $table->boolean('requires_ai_tokens')->default(false);
            $table->integer('ai_token_cost_per_use')->default(0);
            $table->boolean('requires_external_api')->default(false);
            $table->json('external_api_config')->nullable();
            
            // Additional metadata
            $table->text('security_notes')->nullable();
            $table->json('changelog')->nullable();
            $table->text('installation_notes')->nullable();
            $table->json('configuration_schema')->nullable();
            $table->json('permissions_required')->nullable();
            $table->boolean('requires_admin_approval')->default(false);
            $table->boolean('auto_update_enabled')->default(true);
            $table->timestamp('last_updated_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['is_published', 'is_approved']);
            $table->index(['developer_id']);
            $table->index(['pricing_type']);
            $table->index(['app_type']);
            $table->index(['is_featured']);
            $table->index(['rating_average']);
            $table->index(['download_count']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_store_apps');
    }
}; 