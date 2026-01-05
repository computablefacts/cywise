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
        Schema::table('am_assets_tags_hashes', function (Blueprint $table) {
            if (Schema::hasColumn('am_assets_tags_hashes', 'views')) {
                $table->dropColumn('views');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('am_assets_tags_hashes', function (Blueprint $table) {
            if (!Schema::hasColumn('am_assets_tags_hashes', 'views')) {
                $table->integer('views')->default(0);
            }
        });
    }
};
