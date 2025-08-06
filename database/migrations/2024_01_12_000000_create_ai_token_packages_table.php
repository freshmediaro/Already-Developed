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
        Schema::create('ai_token_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Starter Pack", "Pro Pack", "Enterprise Pack"
            $table->text('description')->nullable();
            $table->integer('token_amount'); // Number of tokens in package
            $table->decimal('price', 10, 2); // Price in USD
            $table->decimal('discount_percentage', 5, 2)->default(0); // Bulk discount
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('features')->nullable(); // Additional features/benefits
            $table->integer('validity_days')->nullable(); // Token expiry (null = no expiry)
            $table->boolean('is_recurring')->default(false); // Monthly subscription
            $table->enum('package_type', ['one_time', 'subscription', 'bulk'])->default('one_time');
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['is_active', 'sort_order']);
            $table->index(['package_type', 'is_active']);
            $table->index(['is_featured', 'is_active']);
            $table->index(['price', 'token_amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_token_packages');
    }
}; 