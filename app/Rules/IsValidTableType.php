<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;

class IsValidTableType implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof UploadedFile) {
            $mimeTypes = new MimeTypes();
            $mimeTypesAllowed = array_merge(
                ['tsv', 'text/plain'],
                $mimeTypes->getMimeTypes('tsv'),
            );
            if (Str::contains($value->getMimeType(), $mimeTypesAllowed)) {
                return;
            }
        }
        $fail('The :attribute is not an allowed table type.');
    }
}
