<?php

namespace App\Http\Controllers\Iframes;

class VulnerabilitiesController extends AbstractTimelineController
{
    protected function objects(): string
    {
        return 'vulnerabilities';
    }

    protected function viewname(): string
    {
        return 'theme::pages.vulnerabilities';
    }
}
