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
        Schema::create('app_store_app_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_store_app_id')->constrained()->onDelete('cascade');
            $table->foreignId('app_store_category_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // Unique constraint
            $table->unique(['app_store_app_id', 'app_store_category_id'], 'app_category_unique');
            
            // Indexes
            $table->index('app_store_app_id');
            $table->index('app_store_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_store_app_categories');
    }
}; 