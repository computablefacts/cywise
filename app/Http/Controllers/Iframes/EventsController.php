<?php

namespace App\Http\Controllers\Iframes;

class EventsController extends AbstractTimelineController
{
    protected function objects(): string
    {
        return 'events';
    }

    protected function viewname(): string
    {
        return 'theme::pages.events';
    }
}
