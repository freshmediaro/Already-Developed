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
        Schema::create('payment_provider_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('provider_name'); // stripe, paypal, square, authorize_net, etc.
            $table->boolean('is_enabled')->default(false);
            $table->boolean('is_default')->default(false);
            $table->text('api_key')->nullable(); // Encrypted API key
            $table->text('api_secret')->nullable(); // Encrypted API secret
            $table->text('webhook_secret')->nullable(); // Encrypted webhook secret
            $table->json('additional_config')->nullable(); // Provider-specific settings
            $table->boolean('test_mode')->default(true);
            $table->string('currency')->default('USD');
            $table->decimal('monthly_fee', 8, 2)->default(0); // Monthly subscription fee for this provider
            $table->date('subscription_started_at')->nullable();
            $table->date('subscription_expires_at')->nullable();
            $table->enum('subscription_status', ['active', 'inactive', 'expired', 'cancelled'])->default('inactive');
            $table->timestamp('last_used_at')->nullable();
            $table->json('supported_features')->nullable(); // payments, refunds, subscriptions, etc.
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'team_id']);
            $table->index(['team_id', 'provider_name']);
            $table->index(['provider_name', 'is_enabled']);
            $table->index(['is_default', 'is_enabled']);
            $table->index(['subscription_status', 'subscription_expires_at']);

            // Unique constraint - only one default provider per team
            $table->unique(['team_id', 'is_default'], 'team_default_provider_unique');

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
        Schema::dropIfExists('payment_provider_configs');
    }
}; 