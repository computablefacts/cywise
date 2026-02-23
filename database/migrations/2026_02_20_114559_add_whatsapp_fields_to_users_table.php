<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'whatsapp_access_token')) {
                $table->string('whatsapp_access_token', 255)->nullable()->after('telegram_webhook_secret');
            }
            if (!Schema::hasColumn('users', 'whatsapp_phone_number_id')) {
                $table->string('whatsapp_phone_number_id', 191)->nullable()->after('whatsapp_access_token');
            }
            if (!Schema::hasColumn('users', 'whatsapp_webhook_secret')) {
                $table->string('whatsapp_webhook_secret', 191)->nullable()->unique()->after('whatsapp_phone_number_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'whatsapp_webhook_secret')) {
                $table->dropUnique(['whatsapp_webhook_secret']);
                $table->dropColumn('whatsapp_webhook_secret');
            }
            if (Schema::hasColumn('users', 'whatsapp_phone_number_id')) {
                $table->dropColumn('whatsapp_phone_number_id');
            }
            if (Schema::hasColumn('users', 'whatsapp_access_token')) {
                $table->dropColumn('whatsapp_access_token');
            }
        });
    }
};
