<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'erp_id' => (string) $this->erp_id,
            'attendance_time' => $this->attendance_time?->format('Y-m-d H:i:s'),
            'punch_type' => $this->punch_type,
            'type_remarks' => $this->type_remarks ?? ($this->punch_type === 0 ? 'IN' : 'OUT'),
            'verification_mode' => $this->verification_mode ?? 'Fingerprint',
            'work_code' => (string) ($this->work_code ?? '0'),
            'machine_id' => $this->attendance_machine_id,
            'machine_name' => $this->machine?->name ?? 'Unknown',
            'machine_ip' => $this->machine?->ip_address ?? '',
            'machine_serial' => $this->machine?->serial_number ?? '',
            'sync_status' => $this->sync_status ?? 'synced',
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
