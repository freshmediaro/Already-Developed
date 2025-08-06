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
        Schema::table('teams', function (Blueprint $table) {
            $table->string('subscription_plan')->default('free')->after('personal_team');
            $table->bigInteger('storage_quota')->nullable()->after('subscription_plan'); // bytes
            $table->integer('app_quota')->nullable()->after('storage_quota'); // number of apps
            $table->json('settings')->nullable()->after('app_quota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['subscription_plan', 'storage_quota', 'app_quota', 'settings']);
        });
    }
}; 