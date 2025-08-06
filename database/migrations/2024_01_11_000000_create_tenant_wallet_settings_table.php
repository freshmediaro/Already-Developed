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
        Schema::create('tenant_wallet_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('wallet_type')->default('default'); // default, ai-tokens, revenue, expenses
            $table->boolean('auto_top_up')->default(false);
            $table->decimal('auto_top_up_threshold', 10, 2)->nullable();
            $table->decimal('auto_top_up_amount', 10, 2)->nullable();
            $table->string('preferred_payment_method')->nullable();
            $table->boolean('email_notifications')->default(true);
            $table->boolean('sms_notifications')->default(false);
            $table->json('notification_preferences')->nullable();
            $table->json('spending_limits')->nullable(); // Daily/monthly limits
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'team_id']);
            $table->index(['team_id', 'wallet_type']);
            $table->index(['user_id', 'wallet_type']);
            $table->index(['auto_top_up', 'auto_top_up_threshold']);

            // Unique constraint to prevent duplicate settings
            $table->unique(['user_id', 'team_id', 'wallet_type'], 'wallet_settings_unique');

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
        Schema::dropIfExists('tenant_wallet_settings');
    }
}; 