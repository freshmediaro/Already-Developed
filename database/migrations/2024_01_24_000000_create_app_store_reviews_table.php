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
        Schema::create('app_store_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_store_app_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('rating')->unsigned(); // 1-5 stars
            $table->string('title')->nullable();
            $table->text('review')->nullable();
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->integer('helpful_count')->default(0);
            $table->string('version_reviewed')->nullable();
            $table->timestamps();

            // Unique constraint - one review per user per app
            $table->unique(['app_store_app_id', 'user_id']);
            
            // Indexes
            $table->index(['app_store_app_id', 'is_approved']);
            $table->index(['user_id']);
            $table->index(['team_id']);
            $table->index(['rating']);
            $table->index(['is_verified_purchase']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_store_reviews');
    }
}; 