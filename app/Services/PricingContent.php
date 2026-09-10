<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Wave\Plan;

class PricingContent
{
    // Keep plan selection out of the public pricing view.
    public function plans(): Collection
    {
        return Plan::getActivePlans();
    }
}
