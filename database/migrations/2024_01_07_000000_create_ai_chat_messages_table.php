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
        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->enum('type', ['user', 'ai'])->default('user');
            $table->longText('content');
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes for tenant isolation and performance
            $table->index(['session_id', 'created_at']);
            $table->index(['user_id', 'team_id']);
            $table->index(['team_id', 'created_at']);
            $table->index(['type', 'created_at']);

            // Foreign key constraints
            $table->foreign('session_id')->references('id')->on('ai_chat_sessions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_messages');
    }
}; 