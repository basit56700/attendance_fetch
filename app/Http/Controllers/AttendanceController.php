<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\AttendanceMachine;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $query = AttendanceLog::with('machine')->orderBy('attendance_time', 'desc');

        if ($request->filled('date_from')) {
            $query->whereDate('attendance_time', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('attendance_time', '<=', $request->input('date_to'));
        }

        if ($request->filled('erp_id')) {
            $query->where('erp_id', 'like', '%' . trim($request->input('erp_id')) . '%');
        }

        if ($request->filled('machine_id')) {
            $query->where('attendance_machine_id', $request->input('machine_id'));
        }

        if ($request->filled('punch_type')) {
            $query->where('punch_type', $request->input('punch_type'));
        }

        $logs = $query->paginate(25)->withQueryString();
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
                'verification_mode' => $log->verification_mode,
                'machine_id' => $log->attendance_machine_id,
                'machine_name' => $log->machine?->name ?? 'Unknown',
                'machine_ip' => $log->machine?->ip_address ?? '',
                'sync_status' => $log->sync_status,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
            ];
        });

        AuditLog::log('ATTENDANCE_EXPORT_JSON', "Exported {$records->count()} attendance records in JSON format", 'success');

        $fileName = 'attendance_export_' . now()->format('Ymd_His') . '.json';
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

        $fileName = 'attendance_export_' . now()->format('Ymd_His') . '.csv';

        AuditLog::log('ATTENDANCE_EXPORT_CSV', "Exported attendance records in CSV format", 'success');

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
     * Clean JSON REST API endpoint (/api/v1/attendance).
     */
    public function apiIndex(Request $request)
    {
        $limit = min((int) $request->input('limit', 500), 5000);
        $records = $this->getFilteredQuery($request)->limit($limit)->get()->map(function ($log) {
            return [
                'id' => $log->id,
                'erp_id' => $log->erp_id,
                'attendance_time' => $log->attendance_time->format('Y-m-d H:i:s'),
                'punch_type' => $log->punch_type,
                'type_remarks' => $log->type_remarks,
                'verification_mode' => $log->verification_mode,
                'machine_id' => $log->attendance_machine_id,
                'machine_name' => $log->machine?->name ?? 'Unknown',
                'machine_ip' => $log->machine?->ip_address ?? '',
                'sync_status' => $log->sync_status,
            ];
        });

        return response()->json([
            'success' => true,
            'count' => $records->count(),
            'data' => $records,
        ]);
    }

    protected function getFilteredQuery(Request $request)
    {
        $query = AttendanceLog::with('machine')->orderBy('attendance_time', 'desc');

        if ($request->filled('date_from')) {
            $query->whereDate('attendance_time', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('attendance_time', '<=', $request->input('date_to'));
        }

        if ($request->filled('erp_id')) {
            $query->where('erp_id', 'like', '%' . trim($request->input('erp_id')) . '%');
        }

        if ($request->filled('machine_id')) {
            $query->where('attendance_machine_id', $request->input('machine_id'));
        }

        if ($request->filled('punch_type')) {
            $query->where('punch_type', $request->input('punch_type'));
        }

        return $query;
    }
}
