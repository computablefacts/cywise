<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TablesList extends Component
{
    public function render(): View|Closure|string
    {
        return view('theme::components.elements.tables-list');
    }
}
