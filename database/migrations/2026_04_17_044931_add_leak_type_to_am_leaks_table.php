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
        Schema::table('am_leaks', function (Blueprint $table) {
            $table->string('leak_type')->nullable()->after('leak_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('am_leaks', function (Blueprint $table) {
            $table->dropColumn('leak_type');
        });
    }
};
