<?php

namespace App\Http\Controllers\Iframes;

class IoCsController extends AbstractTimelineController
{
    protected function objects(): string
    {
        return 'ioc';
    }

    protected function viewname(): string
    {
        return 'theme::pages.ioc';
    }
}
