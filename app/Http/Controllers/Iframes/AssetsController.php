<?php

namespace App\Http\Controllers\Iframes;

class AssetsController extends AbstractTimelineController
{
    protected function objects(): string
    {
        return 'assets';
    }

    protected function viewname(): string
    {
        return 'theme::pages.assets';
    }
}
