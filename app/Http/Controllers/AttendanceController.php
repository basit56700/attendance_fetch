<?php

namespace App\Http\Controllers;

use App\Http\Resources\AttendanceLogResource;
use App\Models\AttendanceLog;
use App\Models\AttendanceMachine;
use App\Models\AuditLog;
use App\Services\PunchSequenceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(protected PunchSequenceService $punchSequence) {}

    public function index(Request $request)
    {
        $logs = $this->getFilteredQuery($request)->paginate(25)->withQueryString();

        if ($request->boolean('live')) {
            return response()->json([
                'total' => $logs->total(),
                'from' => $logs->firstItem() ?? 0,
                'to' => $logs->lastItem() ?? 0,
                'latest_id' => $logs->max('id') ?? 0,
                'logs' => AttendanceLogResource::collection($logs->getCollection())->resolve($request),
                'server_time' => now()->format('H:i:s'),
            ]);
        }

        $machines = AttendanceMachine::all();

        return view('attendance.index', compact('logs', 'machines'));
    }

    /**
     * Streams filtered records as a downloadable JSON file.
     */
    public function exportJson(Request $request)
    {
        $records = $this->getFilteredQuery($request)->get()->map(function ($log) {
            return [
                'id' => $log->id,
                'erp_id' => $log->erp_id,
                'attendance_time' => $log->attendance_time->format('Y-m-d H:i:s'),
                'punch_type' => $log->punch_type,
                'type_remarks' => $log->type_remarks,
                'punch_label' => $this->punchSequence->labelForType($log->punch_type, $log->type_remarks),
                'verification_mode' => $log->verification_mode,
                'machine_id' => $log->attendance_machine_id,
                'machine_name' => $log->machine?->name ?? 'Unknown',
                'machine_ip' => $log->machine?->ip_address ?? '',
                'sync_status' => $log->sync_status,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
            ];
        });

        AuditLog::log('ATTENDANCE_EXPORT_JSON', "Exported {$records->count()} attendance records in JSON format", 'success');

        $fileName = 'attendance_export_'.now()->format('Ymd_His').'.json';
        $jsonContent = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return response($jsonContent, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Streams filtered records as a downloadable CSV file.
     */
    public function exportCsv(Request $request)
    {
        $query = $this->getFilteredQuery($request);

        $fileName = 'attendance_export_'.now()->format('Ymd_His').'.csv';

        AuditLog::log('ATTENDANCE_EXPORT_CSV', 'Exported attendance records in CSV format', 'success');

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Employee ERP ID', 'Attendance Time', 'Type Code', 'State Remarks', 'Verify Mode', 'Machine Name', 'Machine IP', 'Created At']);

            $query->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $l) {
                    fputcsv($handle, [
                        $l->id,
                        $l->erp_id,
                        $l->attendance_time->format('Y-m-d H:i:s'),
                        $l->punch_type,
                        $l->type_remarks,
                        $l->verification_mode,
                        $l->machine?->name ?? 'Unknown',
                        $l->machine?->ip_address ?? '',
                        $l->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Clean JSON REST API endpoint (/api/v1/attendance or /api/attendance).
     *
     * Returns all attendance logs if no machine ID is provided,
     * or logs for a specific machine if machine_id / id / machine is passed (via query string or route param).
     */
    public function apiIndex(Request $request, string|int|null $machine_id = null)
    {
        $identifier = $machine_id ?? $request->input('machine_id') ?? $request->input('id') ?? $request->input('machine');

        $machine = null;
        if ($identifier !== null && $identifier !== '') {
            $machine = AttendanceMachine::where('id', $identifier)
                ->orWhere('ip_address', $identifier)
                ->orWhere('serial_number', $identifier)
                ->first();

            if (! $machine && is_numeric($identifier)) {
                $hasLogs = AttendanceLog::where('attendance_machine_id', $identifier)->exists();
                if (! $hasLogs) {
                    return response()->json([
                        'success' => false,
                        'message' => "Machine with identifier '{$identifier}' not found.",
                        'machine' => null,
                        'count' => 0,
                        'data' => [],
                    ], 404);
                }
            } elseif (! $machine) {
                return response()->json([
                    'success' => false,
                    'message' => "Machine with identifier '{$identifier}' not found.",
                    'machine' => null,
                    'count' => 0,
                    'data' => [],
                ], 404);
            }
        }

        $query = $this->getFilteredQuery($request, $machine?->id ?? (is_numeric($identifier) ? (int) $identifier : null));

        if ($request->filled('limit')) {
            $limit = max(1, (int) $request->input('limit'));
            $query->limit($limit);
        }

        $logs = $query->get();

        return response()->json([
            'success' => true,
            'machine' => $machine ? [
                'id' => $machine->id,
                'name' => $machine->name,
                'ip_address' => $machine->ip_address,
                'serial_number' => $machine->serial_number,
                'protocol' => $machine->protocol,
                'location' => $machine->location,
                'status' => $machine->status,
            ] : null,
            'count' => $logs->count(),
            'data' => AttendanceLogResource::collection($logs),
        ]);
    }

    protected function getFilteredQuery(Request $request, ?int $machineId = null)
    {
        $query = AttendanceLog::with('machine')
            ->orderBy('attendance_time', 'desc')
            ->orderBy('id', 'desc');

        if ($machineId !== null) {
            $query->where('attendance_machine_id', $machineId);
        } elseif ($request->filled('machine_id')) {
            $query->where('attendance_machine_id', $request->input('machine_id'));
        } elseif ($request->filled('id')) {
            $query->where('attendance_machine_id', $request->input('id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('attendance_time', $request->input('date'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('attendance_time', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('attendance_time', '<=', $request->input('date_to'));
        }

        if ($request->filled('erp_id')) {
            $query->where('erp_id', 'like', '%'.trim($request->input('erp_id')).'%');
        }

        if ($request->filled('punch_type')) {
            $query->where('punch_type', $request->input('punch_type'));
        }

        return $query;
    }
}
