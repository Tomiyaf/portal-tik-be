<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntercomCall extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'gate_id',
        'call_status',
        'answered_by',
        'started_at',
        'answered_at',
        'ended_at',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function gate(): BelongsTo
    {
        return $this->belongsTo(Gate::class);
    }

    public function answeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }

    public function visitorSnapshots(): HasMany
    {
        return $this->hasMany(VisitorSnapshot::class);
    }
}
