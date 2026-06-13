<?php

namespace App\Http\Controllers\Iframes;

class NotesAndMemosController extends AbstractTimelineController
{
    protected function objects(): string
    {
        return 'notes-and-memos';
    }

    protected function viewname(): string
    {
        return 'theme::pages.notes-and-memos';
    }
}
