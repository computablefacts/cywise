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
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'auto_monitor_new_assets')) {
                $table->dropColumn('auto_monitor_new_assets');
            }
        });

        Schema::table('am_assets', function (Blueprint $table) {
            $table->boolean('auto_monitor_new_subdomains')->default(true)->after('is_monitored');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('am_assets', function (Blueprint $table) {
            $table->dropColumn('auto_monitor_new_subdomains');
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'auto_monitor_new_assets')) {
                $table->boolean('auto_monitor_new_assets')->default(true)->after('gets_audit_report');
            }
        });
    }
};
