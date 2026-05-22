<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParkingQuota extends Model
{
    use HasFactory;

    const CREATED_AT = null;
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'total_slots',
        'used_slots',
        'status',
        'auto_restrict_student',
        'updated_by',
        'updated_at',
    ];

    protected $casts = [
        'auto_restrict_student' => 'boolean',
        'updated_at' => 'datetime',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
