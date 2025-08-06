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
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->decimal('requested_amount', 12, 2); // Amount requested by tenant
            $table->decimal('withdrawal_fee', 10, 2)->default(0); // Platform withdrawal fee
            $table->decimal('final_amount', 12, 2); // Amount after fees
            $table->string('withdrawal_method')->default('bank_transfer'); // bank_transfer, paypal, etc.
            $table->json('withdrawal_details'); // Bank account info, PayPal email, etc. (encrypted)
            $table->enum('status', ['pending', 'approved', 'processing', 'completed', 'rejected', 'cancelled'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->string('reference_number')->unique(); // Unique reference for tracking
            $table->timestamp('requested_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable(); // Admin user who approved
            $table->unsignedBigInteger('processed_by')->nullable(); // Admin user who processed
            $table->json('processing_metadata')->nullable(); // External transaction IDs, etc.
            $table->text('admin_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'team_id']);
            $table->index(['team_id', 'status']);
            $table->index(['status', 'requested_at']);
            $table->index(['reference_number']);
            $table->index(['requested_at', 'status']);
            $table->index(['withdrawal_method', 'status']);

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
}; 