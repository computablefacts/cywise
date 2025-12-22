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
        Schema::table('ynh_backups', function (Blueprint $table) {
            // Requires doctrine/dbal for renameColumn in some Laravel versions
            if (Schema::hasColumn('ynh_backups', 'user_id')) {
                $table->renameColumn('user_id', 'created_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ynh_backups', function (Blueprint $table) {
            if (Schema::hasColumn('ynh_backups', 'created_by')) {
                $table->renameColumn('created_by', 'user_id');
            }
        });
    }
};
