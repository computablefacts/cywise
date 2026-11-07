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
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('from_lang', 10);
            $table->string('to_lang', 10);
            $table->string('source_text_hash', 32)->index();
            $table->text('source_text');
            $table->text('translated_text');
            $table->timestamps();

            $table->unique(['from_lang', 'to_lang', 'source_text_hash']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
