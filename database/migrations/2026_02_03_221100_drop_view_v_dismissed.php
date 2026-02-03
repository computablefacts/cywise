<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_dismissed');
        DB::statement('DROP INDEX ynh_osquery_columns_uid_index ON ynh_osquery');
        DB::statement('ALTER TABLE ynh_osquery DROP COLUMN columns_uid');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // There is no going back!
    }
};
