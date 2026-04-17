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
            $table->renameColumn('user_id', 'created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('am_leaks', function (Blueprint $table) {
            $table->renameColumn('created_by', 'user_id');
        });
    }
};
