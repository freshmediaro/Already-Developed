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
        Schema::create('app_store_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained('app_store_apps')->onDelete('cascade');
            $table->foreignId('dependency_id')->constrained('app_store_apps')->onDelete('cascade');
            $table->string('minimum_version')->nullable();
            $table->boolean('is_required')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate dependencies
            $table->unique(['app_id', 'dependency_id']);
            
            // Indexes
            $table->index('app_id');
            $table->index('dependency_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_store_dependencies');
    }
}; 