<?php

namespace App\Enums;

// Public website served by the app: prod keeps the former one until the
// redesign ships. Set with WEBSITE_UI.
enum WebsiteUiEnum: string
{
    case LEGACY = 'legacy';
    case REDESIGN = 'redesign';

    public static function current(): self
    {
        return self::tryFrom(config('towerify.website_ui')) ?? self::LEGACY;
    }
}
