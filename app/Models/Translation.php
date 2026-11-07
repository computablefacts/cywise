<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    protected $fillable = [
        'from_lang',
        'to_lang',
        'source_text_hash',
        'source_text',
        'translated_text',
    ];
}
