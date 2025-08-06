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
        Schema::create('app_store_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_store_app_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('team_id')->constrained()->onDelete('cascade');
            
            // Purchase details
            $table->enum('purchase_type', ['one_time', 'subscription', 'in_app_purchase', 'upgrade']);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('payment_method')->nullable();
            $table->string('payment_provider')->nullable();
            $table->string('payment_intent_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            
            // Status and metadata
            $table->enum('status', [
                'pending', 
                'processing', 
                'completed', 
                'failed', 
                'cancelled', 
                'refunded', 
                'partially_refunded'
            ])->default('pending');
            $table->json('metadata')->nullable();
            
            // Refund information
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->text('refund_reason')->nullable();
            
            // Subscription information
            $table->string('subscription_id')->nullable();
            $table->timestamp('subscription_start_date')->nullable();
            $table->timestamp('subscription_end_date')->nullable();
            $table->boolean('is_trial')->default(false);
            $table->timestamp('trial_ends_at')->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index(['app_store_app_id', 'status']);
            $table->index(['user_id', 'status']);
            $table->index(['team_id', 'status']);
            $table->index(['purchase_type']);
            $table->index(['payment_intent_id']);
            $table->index(['subscription_id']);
            $table->index(['subscription_end_date']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_store_purchases');
    }
}; 