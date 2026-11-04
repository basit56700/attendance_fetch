<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_machine_id',
        'erp_id',
        'attendance_time',
        'punch_type',
        'type_remarks',
        'verification_mode',
        'work_code',
        'raw_data',
        'sync_status',
    ];

    protected $casts = [
        'punch_type' => 'integer',
        'attendance_time' => 'datetime',
        'raw_data' => 'array',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(AttendanceMachine::class, 'attendance_machine_id');
    }
}
