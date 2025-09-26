<?php

namespace App\Models;

class Permission extends \Spatie\Permission\Models\Permission
{
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
