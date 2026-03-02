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
        Schema::table('cb_scheduled_tasks', function (Blueprint $table) {
            $table->boolean('run_once')->default(false)->after('enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cb_scheduled_tasks', function (Blueprint $table) {
            $table->dropColumn('run_once');
        });
    }
};
