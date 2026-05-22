<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitorSnapshot extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'intercom_call_id',
        'image_url',
        'captured_at',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
    ];

    public function intercomCall(): BelongsTo
    {
        return $this->belongsTo(IntercomCall::class);
    }
}
