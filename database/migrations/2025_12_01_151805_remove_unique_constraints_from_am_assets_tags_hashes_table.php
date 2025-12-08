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
        Schema::table('am_assets_tags_hashes', function (Blueprint $table) {
            // Supprimer la contrainte de clé étrangère d'abord
            $table->dropForeign('am_assets_tags_hashes_tag_foreign');
            
            // Ensuite supprimer les contraintes d'unicité
            $table->dropUnique(['hash']);
            $table->dropUnique(['tag']);
            
            // Recréer la clé étrangère sans contrainte d'unicité
            $table->foreign(['tag'])->references(['tag'])->on('am_assets_tags')->onUpdate('restrict')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('am_assets_tags_hashes', function (Blueprint $table) {
            // Supprimer la clé étrangère
            $table->dropForeign('am_assets_tags_hashes_tag_foreign');
            
            // Recréer les contraintes d'unicité
            $table->unique('hash');
            $table->unique('tag');
            
            // Recréer la clé étrangère
            $table->foreign(['tag'])->references(['tag'])->on('am_assets_tags')->onUpdate('restrict')->onDelete('cascade');
        });
    }
};
