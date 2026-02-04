<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE ynh_osquery ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // There is no going back!
    }
};
