<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\AttendanceMachine;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AdmsPushController extends Controller
{
    /**
     * Handles /iclock/cdata for ZKTeco ADMS push protocol.
     * GET: Device handshake, options exchange, and heartbeat.
     * POST: Pushed data tables (ATTLOG, OPERLOG, etc.).
     */
    public function cdata(Request $request): Response
    {
        $sn = $request->query('SN') ?: $request->input('SN');
        $ip = $request->header('X-Forwarded-For')
            ? trim(explode(',', $request->header('X-Forwarded-For'))[0])
            : $request->ip();

        // 1. Locate or automatically register/update the machine
        $machine = $this->resolveMachine($sn, $ip);

        if ($request->isMethod('GET')) {
            return $this->handleHandshake($request, $machine, $sn);
        }

        if ($request->isMethod('POST')) {
            return $this->handleDataPush($request, $machine, $sn);
        }

        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Handles /iclock/push endpoint (used by Push 3.x / Access Control firmware).
     * POST /iclock/push?SN=...
     */
    public function push(Request $request): Response
    {
        $sn = $request->query('SN') ?: $request->input('SN');
        $ip = $request->header('X-Forwarded-For')
            ? trim(explode(',', $request->header('X-Forwarded-For'))[0])
            : $request->ip();

        $machine = $this->resolveMachine($sn, $ip);
        $content = $request->getContent();

        Log::info("ADMS /iclock/push received from {$sn} ({$ip})", [
            'query' => $request->all(),
            'body' => $content,
        ]);

        if (! empty($content)) {
            $count = $this->parseAndStoreAttendanceLogs($content, $machine);
            if ($count > 0) {
                AuditLog::log(
                    'ADMS_PUSH',
                    "Received {$count} punch(es) via /iclock/push from [{$machine?->name} / SN: {$sn}].",
                    'success'
                );
            }
        }

        if ($machine) {
            $machine->update([
                'last_connected_at' => now(),
                'status' => 'active',
                'last_error' => null,
            ]);
        }

        return response("ServerVersion=3.1.2\r\nServerName=LaravelERP\r\nOK\r\n", 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Handles device polling for queued server commands.
     * GET /iclock/getrequest?SN=...
     */
    public function getRequest(Request $request): Response
    {
        $sn = $request->query('SN') ?: $request->input('SN');
        $this->resolveMachine($sn, $request->ip());

        // Return 'OK' (no pending commands). If remote commands are needed later, format here.
        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Handles command execution confirmation from the device.
     * POST /iclock/devicecmd?SN=...
     */
    public function deviceCmd(Request $request): Response
    {
        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Handles biometric template synchronization requests.
     * GET/POST /iclock/fdata?SN=...
     */
    public function fdata(Request $request): Response
    {
        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Handles new device self-registration.
     * POST /iclock/registry?SN=...
     */
    public function registry(Request $request): Response
    {
        $sn = $request->query('SN') ?: $request->input('SN');
        $machine = $this->resolveMachine($sn, $request->ip());

        return response('RegistryCode=1', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Handles the GET handshake / option negotiation.
     */
    protected function handleHandshake(Request $request, ?AttendanceMachine $machine, ?string $sn): Response
    {
        $snStr = $sn ?: ($machine?->serial_number ?: 'UNKNOWN');

        // Configure ZKTeco ADMS device parameters
        $options = [
            "GET OPTION FROM: {$snStr}",
            'Stamp=0',
            'OpStamp=0',
            'ErrorDelay=30',
            'Delay=10',
            'TransTimes=00:00;14:05',
            'TransInterval=1',
            'TransFlag=1111000000',
            'Realtime=1',
            'Encrypt=0',
            'ServerVersion=3.4.1',
        ];

        if ($machine) {
            $machine->update([
                'last_connected_at' => now(),
                'status' => 'active',
                'last_error' => null,
            ]);
        }

        return response(implode("\r\n", $options)."\r\n", 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Handles incoming POST data from the device.
     */
    protected function handleDataPush(Request $request, ?AttendanceMachine $machine, ?string $sn): Response
    {
        $table = strtoupper((string) ($request->query('table') ?: $request->input('table', '')));
        $content = $request->getContent();

        Log::info("ADMS POST /iclock/cdata [table={$table}] from {$sn}", [
            'query' => $request->all(),
            'body' => $content,
        ]);

        if (empty($content)) {
            return response('OK', 200)->header('Content-Type', 'text/plain');
        }

        if ($table === 'ATTLOG' || $table === 'RTLOG' || str_contains($content, "\t") || empty($table)) {
            $count = $this->parseAndStoreAttendanceLogs($content, $machine);

            if ($machine) {
                $machine->update([
                    'last_connected_at' => now(),
                    'last_fetch_count' => $count,
                    'last_error' => null,
                    'status' => 'active',
                ]);
            }

            AuditLog::log(
                'ADMS_PUSH',
                "Received {$count} real-time punch(es) from machine [{$machine?->name} / SN: {$sn}].",
                'success'
            );

            return response("OK: {$count}", 200)->header('Content-Type', 'text/plain');
        }

        // For OPERLOG, RTSTATE, BIODATA, or other system tables
        return response('OK', 200)->header('Content-Type', 'text/plain');
    }

    /**
     * Parses the raw ATTLOG push payload and stores punches safely in attendance_logs.
     */
    protected function parseAndStoreAttendanceLogs(string $content, ?AttendanceMachine $machine): int
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        $count = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $erpId = null;
            $rawTime = null;
            $status = 0;
            $verifyType = 1;
            $workCode = '0';

            if (str_contains($line, '=')) {
                $kvParts = explode("\t", $line);
                $kv = [];
                foreach ($kvParts as $pair) {
                    if (str_contains($pair, '=')) {
                        [$k, $v] = explode('=', trim($pair), 2);
                        $kv[strtolower(trim($k))] = trim($v);
                    }
                }

                $event = isset($kv['event']) ? (int) $kv['event'] : 0;
                $pin = $kv['pin'] ?? ($kv['cardno'] ?? null);

                if (($pin === null || $pin === '0' || $pin === '') && $event !== 0) {
                    continue;
                }

                $erpId = (string) ($pin ?: '0');
                $rawTime = $kv['time'] ?? null;
                $status = isset($kv['inoutstatus']) ? (int) $kv['inoutstatus'] : 0;
                $verifyType = isset($kv['verifytype']) ? (int) $kv['verifytype'] : 1;
            } else {
                $parts = preg_split('/[\t,]+/', $line);

                if (count($parts) < 2) {
                    if (preg_match('/^(\S+)\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\s*(\d*)\s*(\d*)/', $line, $matches)) {
                        $parts = array_slice($matches, 1);
                    } else {
                        continue;
                    }
                }

                $erpId = trim($parts[0]);
                $rawTime = trim($parts[1]);
                $status = isset($parts[2]) ? (int) $parts[2] : 0;
                $verifyType = isset($parts[3]) ? (int) $parts[3] : 1;
                $workCode = isset($parts[4]) ? trim($parts[4]) : '0';
            }

            // Validate timestamp format (YYYY-MM-DD HH:MM:SS)
            $ts = strtotime((string) $rawTime);
            if ($ts === false || empty($erpId)) {
                continue;
            }
            $attTime = date('Y-m-d H:i:s', $ts);

            $typeRemarks = match ($status) {
                0 => 'IN',
                1 => 'OUT',
                2 => 'Break OUT',
                3 => 'Break IN',
                4 => 'Overtime IN',
                5 => 'Overtime OUT',
                default => "Status {$status}",
            };

            $verifyMode = match ($verifyType) {
                1 => 'Fingerprint',
                2 => 'PIN/Password',
                3 => 'Card',
                4 => 'Card',
                15 => 'Face Recognition',
                default => "Mode {$verifyType}",
            };

            $workCode = isset($parts[4]) ? trim($parts[4]) : '0';

            // Insert or deduplicate
            $created = AttendanceLog::firstOrCreate(
                [
                    'attendance_machine_id' => $machine?->id,
                    'erp_id' => $erpId,
                    'attendance_time' => $attTime,
                ],
                [
                    'punch_type' => $status,
                    'type_remarks' => $typeRemarks,
                    'verification_mode' => $verifyMode,
                    'work_code' => $workCode ?: '0',
                    'raw_data' => json_encode([
                        'source' => 'ADMS_PUSH',
                        'raw_line' => $line,
                        'sn' => $machine?->serial_number,
                    ]),
                    'sync_status' => 'synced',
                ]
            );

            if ($created->wasRecentlyCreated) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Resolves an AttendanceMachine instance by Serial Number or IP address.
     * Auto-creates the machine if not yet added so punches are never dropped.
     */
    protected function resolveMachine(?string $sn, string $ip): ?AttendanceMachine
    {
        $machine = null;

        if ($sn) {
            $machine = AttendanceMachine::where('serial_number', $sn)->first();
        }

        if (! $machine && $ip) {
            $machine = AttendanceMachine::where('ip_address', $ip)->first();
        }

        if (! $machine && $sn) {
            // Auto-register newly discovered ADMS machine
            $machine = AttendanceMachine::create([
                'name' => "ADMS Machine ({$sn})",
                'ip_address' => $ip,
                'port' => 4370,
                'protocol' => 'adms',
                'comm_key' => 0,
                'serial_number' => $sn,
                'firmware_version' => 'ADMS / Push Terminal',
                'location' => 'Auto-Registered via ADMS',
                'status' => 'active',
                'last_connected_at' => now(),
            ]);

            AuditLog::log(
                'MACHINE_AUTO_REGISTER',
                "New biometric machine automatically registered via ADMS Push: [{$machine->name}] (IP: {$ip}, SN: {$sn})",
                'success'
            );
        } elseif ($machine) {
            if ($sn && ! $machine->serial_number) {
                $machine->update(['serial_number' => $sn]);
            }
            if ($machine->protocol !== 'adms' && $machine->protocol !== 'auto') {
                $machine->update(['protocol' => 'adms']);
            }
        }

        return $machine;
    }
}
