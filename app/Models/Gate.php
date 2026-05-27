<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gate extends Model
{
    use HasFactory;

    protected $fillable = [
        'gate_name',
        'latitude',
        'longitude',
        'allowed_radius_meter',
        // 'current_status',
        // 'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    public function iotDevices(): HasMany
    {
        return $this->hasMany(IotDevice::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    public function intercomCalls(): HasMany
    {
        return $this->hasMany(IntercomCall::class);
    }
}
