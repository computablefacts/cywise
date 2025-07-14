<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;

class IsValidFileType implements ValidationRule
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
                ['application/x-ndjason'], // jsonl
                $mimeTypes->getMimeTypes('pdf'),
                $mimeTypes->getMimeTypes('doc'),
                $mimeTypes->getMimeTypes('docx'),
                $mimeTypes->getMimeTypes('txt'),
                $mimeTypes->getMimeTypes('mp3'),
                $mimeTypes->getMimeTypes('wav'),
                $mimeTypes->getMimeTypes('webm'),
                $mimeTypes->getMimeTypes('json'),
                $mimeTypes->getMimeTypes('gz'),
            );
            if (Str::contains($value->getMimeType(), $mimeTypesAllowed)) {
                return;
            }
        }
        $fail('The :attribute is not an allowed file type.');
    }
}
