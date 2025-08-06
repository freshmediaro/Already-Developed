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
        Schema::create('ai_chat_usage', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_id')->nullable();
            $table->uuid('message_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('provider')->default('openai'); // openai, anthropic, groq, etc.
            $table->string('model');
            $table->integer('tokens_used')->default(0);
            $table->decimal('cost', 10, 6)->default(0.000000);
            $table->enum('operation_type', ['chat', 'image_analysis', 'document_analysis', 'command_execution'])->default('chat');
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes for tenant isolation and analytics
            $table->index(['user_id', 'team_id']);
            $table->index(['team_id', 'created_at']);
            $table->index(['provider', 'model']);
            $table->index(['operation_type', 'created_at']);
            $table->index(['created_at']);

            // Foreign key constraints
            $table->foreign('session_id')->references('id')->on('ai_chat_sessions')->onDelete('set null');
            $table->foreign('message_id')->references('id')->on('ai_chat_messages')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_chat_usage');
    }
}; 