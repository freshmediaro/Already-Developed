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
        Schema::create('ai_chat_feedback', function (Blueprint $table) {
            $table->id();
            $table->uuid('message_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->enum('type', ['like', 'dislike', 'report'])->default('like');
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes for tenant isolation and performance
            $table->index(['message_id', 'user_id']);
            $table->index(['user_id', 'team_id']);
            $table->index(['team_id', 'type']);
            $table->index(['created_at']);

            // Unique constraint to prevent duplicate feedback
            $table->unique(['message_id', 'user_id', 'type']);

            // Foreign key constraints
            $table->foreign('message_id')->references('id')->on('ai_chat_messages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_feedback');
    }
}; 