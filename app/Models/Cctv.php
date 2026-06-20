<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cctv extends Model
{
    use HasFactory;

    protected $fillable = [
        'camera_name',
        'path',
        'stream_url',
        'type',
    ];

}