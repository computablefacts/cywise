<?php

use App\Models\Vector;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cb_vectors', function (Blueprint $table) {
            if (Vector::isSupportedByMariaDb()) {
                DB::statement('alter table `cb_vectors` add vector index `cb_vectors_embedding_index`(`embedding`)');
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
        Schema::table('cb_vectors', function (Blueprint $table) {
            if (Vector::isSupportedByMariaDb()) {
                $table->dropIndex('cb_vectors_embedding_index');
            }
        });
    }
};
