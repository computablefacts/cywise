<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('cb_remote_actions', function (Blueprint $table) {
            $table->json('examples')->default('[]');
        });
    }

    public function down(): void
    {
        Schema::table('cb_remote_actions', function (Blueprint $table) {
            $table->dropColumn('examples');
        });
    }
};