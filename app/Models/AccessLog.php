<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'gate_id',
        'access_status',
        'access_method',
        'triggered_by',
        'notes',
        'accessed_at',
        'created_at',
    ];

    protected $casts = [
        'accessed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gate(): BelongsTo
    {
        return $this->belongsTo(Gate::class);
    }
}
