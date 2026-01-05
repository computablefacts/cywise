<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cb_scheduled_tasks', function (Blueprint $table) {
            $table->dateTime('last_email_sent_at')->nullable()->after('next_run_date');
        });
    }

    public function down(): void
    {
        Schema::table('cb_scheduled_tasks', function (Blueprint $table) {
            $table->dropColumn('last_email_sent_at');
        });
    }
};
