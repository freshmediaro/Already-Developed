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
        Schema::create('platform_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('transaction_type'); // 'payment', 'withdrawal', 'ai_tokens', 'app_purchase'
            $table->string('transaction_id'); // Reference to original transaction
            $table->decimal('original_amount', 12, 2); // Original transaction amount
            $table->decimal('platform_commission', 10, 2); // Our commission
            $table->decimal('stripe_fee', 10, 2)->nullable(); // Stripe's fee (if applicable)
            $table->decimal('total_fees', 10, 2); // Total fees deducted
            $table->decimal('tenant_amount', 12, 2); // Amount credited to tenant
            $table->decimal('commission_rate', 5, 4); // Commission rate applied (e.g., 0.0500 for 5%)
            $table->string('payment_provider')->default('stripe'); // stripe, paypal, etc.
            $table->string('currency')->default('USD');
            $table->enum('status', ['pending', 'processed', 'failed', 'refunded'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->json('metadata')->nullable(); // Additional transaction details
            $table->timestamps();

            // Indexes for analytics and reporting
            $table->index(['user_id', 'team_id']);
            $table->index(['team_id', 'created_at']);
            $table->index(['transaction_type', 'status']);
            $table->index(['payment_provider', 'created_at']);
            $table->index(['status', 'processed_at']);
            $table->index(['created_at', 'platform_commission']);

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_commissions');
    }
}; 