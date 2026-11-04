<?php

namespace App\Console\Commands;

use App\Models\AttendanceLog;
use App\Models\AttendanceMachine;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Services\ZKTecoService;
use Exception;
use Illuminate\Console\Command;

class PollZkMachines extends Command
{
    protected $signature = 'zk:poll {machine_id? : Optional single machine ID to fetch}';
    protected $description = 'Polls configured ZKTeco biometric attendance machines and stores records';

    public function handle(ZKTecoService $zkService)
    {
        $machineId = $this->argument('machine_id');

        if ($machineId) {
            $machines = AttendanceMachine::where('id', $machineId)->get();
        } else {
            $machines = AttendanceMachine::where('status', 'active')->get();
        }

        if ($machines->isEmpty()) {
            $this->warn('No active machines found to poll.');
            return 0;
        }

        $this->info("Polling " . $machines->count() . " machine(s)...");

        $totalNew = 0;

        foreach ($machines as $machine) {
            $this->line("Connecting to {$machine->name} ({$machine->ip_address}:{$machine->port})...");

            try {
                $records = $zkService->getAttendance(
                    $machine->ip_address,
                    $machine->port,
                    $machine->protocol,
                    $machine->comm_key
                );

                $newCount = 0;
                foreach ($records as $r) {
                    $created = AttendanceLog::firstOrCreate(
                        [
                            'attendance_machine_id' => $machine->id,
                            'erp_id' => trim($r['erp_id']),
                            'attendance_time' => $r['attendance_time'],
                        ],
                        [
                            'punch_type' => $r['punch_type'],
                            'type_remarks' => $r['type_remarks'],
                            'verification_mode' => $r['verification_mode'],
                            'work_code' => $r['work_code'] ?? '0',
                            'raw_data' => $r,
                            'sync_status' => 'synced',
                        ]
                    );

                    if ($created->wasRecentlyCreated) {
                        $newCount++;
                    }
                }

                $machine->update([
                    'last_connected_at' => now(),
                    'last_fetch_count' => $newCount,
                    'last_error' => null,
                ]);

                // Auto-clear device memory if policy enabled
                if (SystemSetting::get('clear_device_logs', '0') === '1' && count($records) > 0) {
                    try {
                        $zkService->clearAttendance(
                            $machine->ip_address,
                            $machine->port,
                            $machine->protocol,
                            $machine->comm_key
                        );
                        AuditLog::log('AUTO_CLEAR_DEVICE_LOGS', "Cleared memory on machine '{$machine->name}' per background policy.", 'warning');
                        $this->line("  ↳ Cleared device memory per system policy.");
                    } catch (Exception $ce) {
                        $this->warn("  ↳ Could not clear machine logs: " . $ce->getMessage());
                    }
                }

                $totalNew += $newCount;
                $this->info("✓ Fetched " . count($records) . " records from {$machine->name} ({$newCount} new saved).");
            } catch (Exception $e) {
                $machine->update(['last_error' => $e->getMessage()]);
                $this->error("✕ Error on {$machine->name}: " . $e->getMessage());
                AuditLog::log('MACHINE_POLL_FAILED', "Polling error on '{$machine->name}': " . $e->getMessage(), 'failed');
            }
        }

        $this->info("Polling complete. Total {$totalNew} new attendance records inserted.");
        return 0;
    }
}
