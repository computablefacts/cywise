<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cb_scheduled_tasks', function (Blueprint $table) {
            $table->boolean('enabled')->default(true)->after('task');
            $table->index('enabled');
        });
    }

    public function down(): void
    {
        Schema::table('cb_scheduled_tasks', function (Blueprint $table) {
            $table->dropIndex(['enabled']);
            $table->dropColumn('enabled');
        });
    }
};
