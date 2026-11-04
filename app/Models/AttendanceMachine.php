<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceMachine extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'ip_address',
        'port',
        'protocol',
        'comm_key',
        'serial_number',
        'firmware_version',
        'location',
        'status',
        'last_connected_at',
        'last_fetch_count',
        'last_error',
    ];

    protected $casts = [
        'port' => 'integer',
        'comm_key' => 'integer',
        'last_fetch_count' => 'integer',
        'last_connected_at' => 'datetime',
    ];

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }
}
