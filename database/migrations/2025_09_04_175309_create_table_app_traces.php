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
        Schema::create('app_traces', function (Blueprint $table) {

            $table->bigIncrements('id');
            $table->timestamps();

            // If a user is deleted, all traces linked to him must be deleted
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');

            // The endpoint, procedure and method called
            $table->string('verb');
            $table->string('endpoint');
            $table->string('procedure')->nullable();
            $table->string('method')->nullable();

            // The call duration
            $table->unsignedBigInteger('duration_in_ms');

            // The call return status
            $table->boolean('failed');

            // Indexes
            $table->timestamp('created_at', 0)->index()->change();
            $table->timestamp('updated_at', 0)->index()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('app_traces');
    }
};
