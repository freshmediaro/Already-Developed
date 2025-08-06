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
        Schema::table('app_store_apps', function (Blueprint $table) {
            // Security scanning fields
            $table->enum('security_scan_status', ['pending', 'passed', 'failed', 'admin_override'])->default('pending');
            $table->text('security_scan_notes')->nullable();
            
            // Package and submission tracking
            $table->string('package_path')->nullable();
            $table->foreignId('submitted_by_team_id')->nullable()->constrained('teams')->onDelete('set null');
            
            // Additional approval statuses for security
            $table->enum('approval_status', [
                'pending', 
                'under_review', 
                'approved', 
                'rejected', 
                'security_blocked',
                'security_rejected', 
                'approved_with_conditions',
                'auto_approved'
            ])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_store_apps', function (Blueprint $table) {
            $table->dropColumn([
                'security_scan_status',
                'security_scan_notes',
                'package_path',
                'submitted_by_team_id'
            ]);
            
            // Restore original approval_status enum
            $table->enum('approval_status', ['pending', 'under_review', 'approved', 'rejected'])->default('pending')->change();
        });
    }
};