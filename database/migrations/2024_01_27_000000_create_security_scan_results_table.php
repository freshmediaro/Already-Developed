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
        Schema::create('security_scan_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_store_app_id')->constrained()->onDelete('cascade');
            
            // Scan results
            $table->enum('scan_status', ['pending', 'passed', 'blocked', 'failed'])->default('pending');
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical', 'unknown'])->default('unknown');
            $table->integer('security_score')->default(0);
            
            // Vulnerability details
            $table->json('vulnerabilities_found')->nullable();
            $table->longText('ai_analysis')->nullable();
            $table->json('recommendations')->nullable();
            $table->json('blocked_reasons')->nullable();
            
            // Timestamps
            $table->timestamp('scanned_at')->nullable();
            
            // Admin review fields
            $table->timestamp('admin_reviewed_at')->nullable();
            $table->foreignId('admin_reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('admin_decision', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('admin_override_reason')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['app_store_app_id', 'scan_status']);
            $table->index(['risk_level', 'scan_status']);
            $table->index(['admin_decision', 'admin_reviewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_scan_results');
    }
};