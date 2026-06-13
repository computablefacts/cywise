<?php

namespace App\Http\Controllers\Iframes;

class LeaksController extends AbstractTimelineController
{
    protected function objects(): string
    {
        return 'leaks';
    }

    protected function viewname(): string
    {
        return 'theme::pages.leaks';
    }
}
