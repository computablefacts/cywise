<?php

use App\Models\Vector;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cb_vectors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->index('cb_vectors_created_by_foreign');
            $table->unsignedBigInteger('collection_id')->index('cb_vectors_collection_id_foreign');
            $table->unsignedBigInteger('file_id')->index('cb_vectors_file_id_foreign');
            $table->unsignedBigInteger('chunk_id')->index('cb_vectors_chunk_id_foreign');
            $table->string('locale');
            $table->string('hypothetical_question', 1000);
            if (Vector::isSupportedByMariaDb()) {
                $table->vector('embedding')->default('[]');
                $table->index('embedding');
            } else {
                $table->json('embedding')->default('[]');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cb_vectors');
    }
};
