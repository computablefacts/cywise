<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE INDEX idx_osquery_events_lookup ON ynh_osquery (calendar_time, ynh_server_id, ynh_osquery_rule_id)');
        DB::statement('CREATE INDEX idx_osquery_dismissed_lookup ON ynh_osquery (dismissed, ynh_server_id)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX idx_osquery_dismissed_lookup ON ynh_osquery');
        DB::statement('DROP INDEX idx_osquery_events_lookup ON ynh_osquery');
    }
};
