<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    const CREATED_AT = null;
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'setting_key',
        'setting_value',
        'updated_at',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];
}
