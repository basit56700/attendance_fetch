<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\AttendanceMachine;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Services\ZKTecoService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MachineController extends Controller
{
    protected ZKTecoService $zkService;

    public function __construct(ZKTecoService $zkService)
    {
        $this->zkService = $zkService;
    }

    public function index()
    {
        $machines = AttendanceMachine::withCount('attendanceLogs')->get();

        return view('machines.index', compact('machines'));
    }

    public function create()
    {
        return view('machines.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateMachine($request);
        if ($validated['protocol'] === 'adms') {
            $validated['firmware_version'] = 'ADMS / Push Terminal';
        }

        $machine = AttendanceMachine::create($validated);

        AuditLog::log('MACHINE_CREATE', "Added machine '{$machine->name}' ({$machine->ip_address}:{$machine->port}, {$machine->protocol})", 'success');

        return redirect()->route('machines.index')->with('success', "Machine '{$machine->name}' added successfully.");
    }

    public function edit(AttendanceMachine $machine)
    {
        return view('machines.edit', compact('machine'));
    }

    public function update(Request $request, AttendanceMachine $machine)
    {
        $validated = $this->validateMachine($request);

        if ($validated['protocol'] === 'adms') {
            // Clear stale Port 4370 socket errors when switching a device to ADMS Push mode
            $validated['last_error'] = null;
            if (! $machine->firmware_version) {
                $validated['firmware_version'] = 'ADMS / Push Terminal';
            }
        }

        $machine->update($validated);

        AuditLog::log('MACHINE_UPDATE', "Updated machine '{$machine->name}' ({$machine->ip_address}, {$machine->protocol})", 'info');

        return redirect()->route('machines.index')->with('success', "Machine '{$machine->name}' updated successfully.");
    }

    /**
     * Validates biometric machine input consistently for create and update actions.
     *
     * @return array<string, mixed>
     */
    protected function validateMachine(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'ip_address' => ['required', 'ip'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'protocol' => ['required', 'in:auto,udp,tcp,adms'],
            'comm_key' => ['required', 'integer', 'min:0'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:150'],
            'status' => ['required', 'in:active,inactive'],
        ]);
    }

    public function destroy(AttendanceMachine $machine)
    {
        $name = $machine->name;
        $ip = $machine->ip_address;
        $machine->delete();

        AuditLog::log('MACHINE_DELETE', "Deleted machine '{$name}' ({$ip})", 'info');

        return redirect()->route('machines.index')->with('success', "Machine '{$name}' deleted successfully.");
    }

    /**
     * Diagnostic API: Runs pre-add or live connectivity test on machine parameters.
     * Evaluates reachability, socket, port, protocol, comm key, firmware, and clock drift.
     * Returns structured error diagnosis and resolution instructions.
     */
    public function testConnectivity(Request $request)
    {
        $ip = $request->input('ip_address');
        $port = (int) $request->input('port', 4370);
        $protocol = $request->input('protocol', 'auto');
        $commKey = (int) $request->input('comm_key', 0);

        if (! $ip) {
            return response()->json([
                'success' => false,
                'error_code' => 'ERR_EMPTY_IP',
                'error_summary' => 'IP address is required.',
                'resolution_steps' => ['Please provide a valid IPv4 address.'],
            ], 422);
        }

        if ($protocol === 'adms') {
            $host = $request->getHost();
            $serverAddr = $request->server('SERVER_ADDR');
            $serverIp = ($host && ! in_array($host, ['127.0.0.1', 'localhost', '::1'], true))
                ? $host
                : ($serverAddr ?: $host);
            $serverPort = (string) ($request->getPort() ?: ($request->server('SERVER_PORT') ?: '80'));
            $diag = [
                'success' => true,
                'ip' => $ip,
                'port' => $port,
                'protocol' => 'ADMS (Cloud Push)',
                'latency_ms' => 0.1,
                'firmware' => 'ADMS Real-time Push Receiver',
                'device_time' => now()->toDateTimeString(),
                'time_drift_seconds' => 0,
                'error_code' => null,
                'error_summary' => 'ADMS real-time push receiver is active on this server.',
                'root_cause' => "Configure on device: Server Address = {$serverIp}, Server Port = {$serverPort}, Server Mode = ADMS.",
                'resolution_steps' => [
                    '1. On device screen: Menu -> Comm. -> Cloud Server Setting.',
                    "2. Set Server Address: {$serverIp}",
                    "3. Set Server Port: {$serverPort}",
                    '4. Set Server Mode: ADMS, HTTPS: OFF.',
                    '5. Every punch on the device will push directly into this ERP in real-time!',
                ],
            ];

            return response()->json($diag);
        }

        $diag = $this->zkService->runDiagnostics($ip, $port, $protocol, $commKey);

        AuditLog::log(
            'MACHINE_TEST',
            "Tested connection to {$ip}:{$port} ({$protocol}). Result: ".($diag['success'] ? "SUCCESS (Latency: {$diag['latency_ms']}ms)" : "FAILED: {$diag['error_code']}"),
            $diag['success'] ? 'success' : 'failed'
        );

        // If machine exists in DB, update last_error or last_connected_at
        if ($request->has('machine_id')) {
            $mach = AttendanceMachine::find($request->input('machine_id'));
            if ($mach) {
                if ($diag['success']) {
                    $mach->update([
                        'last_connected_at' => now(),
                        'firmware_version' => $diag['firmware'],
                        'last_error' => null,
                    ]);
                } else {
                    $mach->update([
                        'last_error' => $diag['error_summary'],
                    ]);
                }
            }
        }

        return response()->json($diag);
    }

    /**
     * Fetches attendance logs from a specific machine and saves to database with deduplication.
     */
    public function fetchLogs(AttendanceMachine $machine)
    {
        if ($machine->protocol === 'adms') {
            return back()->with('info', "Machine '{$machine->name}' operates in ADMS Real-time Push Mode. Punches are delivered automatically whenever an employee punches.");
        }

        try {
            $rawRecords = $this->zkService->getAttendance(
                $machine->ip_address,
                $machine->port,
                $machine->protocol,
                $machine->comm_key
            );

            $totalFetched = count($rawRecords);
            $newlyInserted = 0;
            $duplicatesSkipped = 0;

            foreach ($rawRecords as $record) {
                $erpId = trim($record['erp_id']);
                $attTime = $record['attendance_time'];

                if (empty($erpId) || empty($attTime)) {
                    continue;
                }

                // Insert with duplicate prevention via UNIQUE constraint
                $created = AttendanceLog::firstOrCreate(
                    [
                        'attendance_machine_id' => $machine->id,
                        'erp_id' => $erpId,
                        'attendance_time' => $attTime,
                    ],
                    [
                        'punch_type' => $record['punch_type'],
                        'type_remarks' => $record['type_remarks'],
                        'verification_mode' => $record['verification_mode'],
                        'work_code' => $record['work_code'] ?? '0',
                        'raw_data' => $record,
                        'sync_status' => 'synced',
                    ]
                );

                if ($created->wasRecentlyCreated) {
                    $newlyInserted++;
                } else {
                    $duplicatesSkipped++;
                }
            }

            $machine->update([
                'last_connected_at' => now(),
                'last_fetch_count' => $newlyInserted,
                'last_error' => null,
            ]);

            // Auto-clear device memory if policy is enabled
            $shouldClear = SystemSetting::get('clear_device_logs', '0') === '1';
            $clearedMsg = '';
            if ($shouldClear && $totalFetched > 0) {
                try {
                    $cleared = $this->zkService->clearAttendance(
                        $machine->ip_address,
                        $machine->port,
                        $machine->protocol,
                        $machine->comm_key
                    );
                    if ($cleared) {
                        $clearedMsg = ' Device memory was cleared following your setting policy.';
                        AuditLog::log('AUTO_CLEAR_DEVICE_LOGS', "Cleared memory on machine '{$machine->name}' ({$machine->ip_address}) per policy.", 'warning');
                    }
                } catch (Exception $ce) {
                    $clearedMsg = ' Note: Could not clear device logs: '.$ce->getMessage();
                }
            }

            AuditLog::log(
                'MACHINE_FETCH',
                "Fetched from '{$machine->name}' ({$machine->ip_address}). Total: {$totalFetched}, New: {$newlyInserted}, Duplicates: {$duplicatesSkipped}",
                'success'
            );

            return back()->with('success', "Fetched {$totalFetched} punches from '{$machine->name}'. ({$newlyInserted} new recorded, {$duplicatesSkipped} duplicates skipped).{$clearedMsg}");
        } catch (Exception $e) {
            $machine->update(['last_error' => $e->getMessage()]);

            AuditLog::log('MACHINE_FETCH_FAILED', "Failed to fetch from '{$machine->name}': ".$e->getMessage(), 'failed');

            return back()->with('error', "Could not fetch attendance from '{$machine->name}': ".$e->getMessage());
        }
    }

    /**
     * Fetches attendance logs from all active machines in sequence.
     */
    public function fetchAll()
    {
        $activeMachines = AttendanceMachine::where('status', 'active')->get();

        if ($activeMachines->isEmpty()) {
            return back()->with('warning', 'No active machines configured.');
        }

        $totalNew = 0;
        $failedCount = 0;
        $polledCount = 0;

        foreach ($activeMachines as $machine) {
            if ($machine->protocol === 'adms') {
                continue;
            }
            $polledCount++;

            try {
                $records = $this->zkService->getAttendance($machine->ip_address, $machine->port, $machine->protocol, $machine->comm_key);
                $newInMach = 0;

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
                        $newInMach++;
                    }
                }

                $machine->update([
                    'last_connected_at' => now(),
                    'last_fetch_count' => $newInMach,
                    'last_error' => null,
                ]);

                // Auto-clear device memory if policy enabled
                if (SystemSetting::get('clear_device_logs', '0') === '1' && count($records) > 0) {
                    try {
                        $this->zkService->clearAttendance($machine->ip_address, $machine->port, $machine->protocol, $machine->comm_key);
                        AuditLog::log('AUTO_CLEAR_DEVICE_LOGS', "Cleared memory on machine '{$machine->name}' per policy.", 'warning');
                    } catch (Exception $ce) {
                    }
                }

                $totalNew += $newInMach;
            } catch (Exception $e) {
                $failedCount++;
                $machine->update(['last_error' => $e->getMessage()]);
            }
        }

        if ($polledCount === 0) {
            return back()->with('info', 'All active machines use ADMS Real-time Push Mode and deliver punches automatically without polling.');
        }

        AuditLog::log('MACHINE_FETCH_ALL', "Batch fetch finished. New records: {$totalNew}, Machines failed: {$failedCount}", $failedCount > 0 ? 'warning' : 'success');

        return back()->with('success', "Batch fetch completed. Recorded {$totalNew} new punches across ".($polledCount - $failedCount).' machine(s).');
    }

    /**
     * Synchronizes machine RTC clock with server time.
     */
    public function syncTime(AttendanceMachine $machine)
    {
        if ($machine->protocol === 'adms') {
            return back()->with('info', "Machine '{$machine->name}' uses ADMS Push Mode where port 4370 pull commands are disabled by firmware.");
        }

        try {
            $ok = $this->zkService->connect($machine->ip_address, $machine->port, $machine->protocol, $machine->comm_key);
            if ($ok) {
                $synced = $this->zkService->syncDeviceTime();
                $this->zkService->disconnect();
                if ($synced) {
                    AuditLog::log('SYNC_TIME', "Clock synchronized on '{$machine->name}'", 'success');

                    return back()->with('success', "Device clock on '{$machine->name}' synchronized with computer time.");
                }
            }

            return back()->with('error', "Could not synchronize time on '{$machine->name}'.");
        } catch (Exception $e) {
            return back()->with('error', 'Clock sync error: '.$e->getMessage());
        }
    }

    /**
     * Clears device logs.
     */
    public function clearLogs(AttendanceMachine $machine)
    {
        if ($machine->protocol === 'adms') {
            return back()->with('info', "Machine '{$machine->name}' uses ADMS Push Mode where port 4370 pull/clear commands are not used.");
        }

        try {
            $ok = $this->zkService->clearAttendance($machine->ip_address, $machine->port, $machine->protocol, $machine->comm_key);
            if ($ok) {
                AuditLog::log('CLEAR_DEVICE_LOGS', "Cleared attendance logs on '{$machine->name}' ({$machine->ip_address})", 'warning');

                return back()->with('success', "Attendance logs cleared from device '{$machine->name}'.");
            }

            return back()->with('error', 'Failed to clear attendance logs on device.');
        } catch (Exception $e) {
            return back()->with('error', 'Error clearing logs: '.$e->getMessage());
        }
    }
}
