<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CacheEntry extends Model
{
    public $timestamps = false;

    protected $table = 'cache';
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'key',
        'value',
        'expiration',
    ];

    protected $casts = [
        'expiration' => 'integer',
    ];
}
