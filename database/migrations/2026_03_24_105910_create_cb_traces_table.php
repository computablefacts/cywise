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
        Schema::create('cb_traces', function (Blueprint $table) {
            $table->id();
            $table->text('input');
            $table->text('output')->nullable();
            $table->float('elapsed_time_in_seconds')->default(0.0);
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cb_traces');
    }
};
