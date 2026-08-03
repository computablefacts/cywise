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
        Schema::table('ynh_permissions', function (Blueprint $table) {
            $table->dropForeign(['ynh_application_id']);
            $table->dropForeign(['ynh_user_id']);
        });

        Schema::table('ynh_applications', function (Blueprint $table) {
            $table->dropForeign(['ynh_server_id']);
        });

        Schema::table('ynh_domains', function (Blueprint $table) {
            $table->dropForeign(['ynh_server_id']);
        });

        Schema::dropIfExists('ynh_permissions');
        Schema::dropIfExists('ynh_domains');
        Schema::dropIfExists('ynh_applications');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
