<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ynh_osquery_rules')->updateOrInsert([
            'name' => 'cywise_ossec_rule_result',
            'created_by' => null,
        ], [
            'description' => 'Stores the result of a Cywise OSSEC security check executed by the server agent.',
            'query' => 'SELECT * FROM osquery_info WHERE 1 = 0;',
            'version' => '1.0.0',
            'interval' => 86400,
            'snapshot' => true,
            'platform' => 'all',
            'category' => 'security_check',
            'enabled' => true,
            'attck' => null,
            'is_ioc' => false,
            'score' => 0,
            'comments' => 'A Cywise OSSEC rule was executed on the monitored server.',
            'updated_at' => now(),
            'created_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('ynh_osquery_rules')
            ->where('name', 'cywise_ossec_rule_result')
            ->whereNull('created_by')
            ->delete();
    }
};
