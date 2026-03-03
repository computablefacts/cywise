<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class ScriptProvider
{
    /**
     * Load a script template from resources/scripts/ and replace {key} placeholders with the given values.
     */
    public static function provide(string $name, array $variables = []): string
    {
        $script = file_get_contents(resource_path('scripts/' . $name));

        foreach ($variables as $key => $value) {
            $script = Str::replace('{' . $key . '}', $value, $script);
        }

        return $script;
    }
}
