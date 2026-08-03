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
        Schema::dropIfExists('cb_traces');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('cb_traces', function (Blueprint $table) {
            $table->id();
            $table->string('thread_id')->nullable()->index();
            $table->text('input');
            $table->text('output')->nullable();
            $table->float('elapsed_time_in_seconds');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();
        });
    }
};
