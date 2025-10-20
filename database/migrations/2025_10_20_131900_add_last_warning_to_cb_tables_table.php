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
        Schema::table('cb_tables', function (Blueprint $table) {
            $table->text('last_warning')->nullable()->after('last_error');
            $table->boolean('bypass_missing_columns_warning')->default(false);
            $table->boolean('bypass_rowcount_warning')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cb_tables', function (Blueprint $table) {
            $table->dropColumn('bypass_rowcount_warning');
            $table->dropColumn('bypass_missing_columns_warning');
            $table->dropColumn('last_warning');
        });
    }
};
