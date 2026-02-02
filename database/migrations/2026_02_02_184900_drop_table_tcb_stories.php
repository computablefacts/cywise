<?php

use App\Models\TimelineItem;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('tcb_stories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // There is no going back!
    }
};
