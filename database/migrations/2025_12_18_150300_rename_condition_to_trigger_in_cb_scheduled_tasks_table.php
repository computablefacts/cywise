<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cb_scheduled_tasks', function (Blueprint $table) {
            // Requires doctrine/dbal for renameColumn in some Laravel versions
            if (Schema::hasColumn('cb_scheduled_tasks', 'condition')) {
                $table->renameColumn('condition', 'trigger');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cb_scheduled_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('cb_scheduled_tasks', 'trigger')) {
                $table->renameColumn('trigger', 'condition');
            }
        });
    }
};
