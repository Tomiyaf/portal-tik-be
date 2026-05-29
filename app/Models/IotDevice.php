<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_name',
        'device_uid',
        'gate_id',
        // 'ip_address',
        'firmware_version',
        'status',
        'last_online_at',
    ];

    protected $casts = [
        'last_online_at' => 'datetime',
    ];

    public function gate(): BelongsTo
    {
        return $this->belongsTo(Gate::class);
    }
}
