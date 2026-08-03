<?php

namespace App\Http\Controllers\Iframes;

class ConversationsController extends AbstractTimelineController
{
    protected function objects(): string
    {
        return 'conversations';
    }

    protected function viewname(): string
    {
        return 'theme::pages.conversations';
    }
}
