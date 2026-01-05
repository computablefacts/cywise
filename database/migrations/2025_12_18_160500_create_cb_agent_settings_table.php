<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('cb_action_settings', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type'); // 'tenant' or 'user'
            $table->unsignedBigInteger('scope_id');
            $table->string('action'); // action function name from schema
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['scope_type', 'scope_id', 'action'], 'cb_action_settings_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cb_action_settings');
    }
};
