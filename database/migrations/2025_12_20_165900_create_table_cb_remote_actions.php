<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('cb_remote_actions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description');
            $table->string('url');
            $table->json('schema'); // Contient le formalisme schema(): array
            $table->json('payload_template')->default('[]'); // Template JSON-RPC
            $table->json('response_template')->nullable(); // Transformation de la réponse
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cb_remote_actions');
    }
};