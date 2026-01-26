<?php

use App\Models\TimelineItem;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $notes = TimelineItem::where('type', 'note')->get();

        /** @var TimelineItem $note */
        foreach ($notes as $note) {
            $note->addAttribute('scopes', json_encode(['CyberBuddy']), $note->owned_by);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $notes = TimelineItem::where('type', 'note')->get();

        /** @var TimelineItem $note */
        foreach ($notes as $note) {
            $note->removeAttribute('scopes', $note->owned_by);
        }
    }
};
