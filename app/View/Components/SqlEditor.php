<?php

namespace App\View\Components;

use App\Models\YnhServer;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SqlEditor extends Component
{
    public function __construct(?YnhServer $server = null)
    {
        //
    }

    public function render(): View|Closure|string
    {
        return view('theme::components.elements.sql-editor');
    }
}
