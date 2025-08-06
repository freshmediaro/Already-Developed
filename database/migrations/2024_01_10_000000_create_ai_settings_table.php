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
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->boolean('use_defaults')->default(true);
            $table->string('custom_model')->nullable();
            $table->text('api_key')->nullable(); // Encrypted
            $table->enum('privacy_level', ['public', 'private', 'agent'])->default('public');
            $table->json('preferences')->nullable(); // Additional user preferences
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'team_id']);
            $table->index(['team_id']);
            $table->index(['privacy_level']);
            $table->unique(['user_id', 'team_id'], 'ai_settings_user_team_unique');

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
        Schema::dropIfExists('ai_settings');
    }
}; 