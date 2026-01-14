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
        Schema::table('am_alerts', function (Blueprint $table) {
            $table->text('ai_remediation')->nullable()->after('remediation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('am_alerts', function (Blueprint $table) {
            $table->dropColumn('ai_remediation');
        });
    }
};
