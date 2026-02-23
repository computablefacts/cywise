<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'telegram_bot_token')) {
                $table->string('telegram_bot_token', 255)->nullable()->after('gets_audit_report');
            }
            if (!Schema::hasColumn('users', 'telegram_webhook_secret')) {
                $table->string('telegram_webhook_secret', 191)->nullable()->unique()->after('telegram_bot_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'telegram_webhook_secret')) {
                $table->dropUnique(['telegram_webhook_secret']);
                $table->dropColumn('telegram_webhook_secret');
            }
            if (Schema::hasColumn('users', 'telegram_bot_token')) {
                $table->dropColumn('telegram_bot_token');
            }
        });
    }
};
