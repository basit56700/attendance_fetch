<?php

namespace App\Http\Controllers;

use App\Http\Resources\AttendanceLogResource;
use App\Models\AttendanceLog;
use App\Models\AttendanceMachine;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $todayStr = now()->format('Y-m-d');

        $stats = [
            'punches_today' => AttendanceLog::whereDate('attendance_time', $todayStr)->count(),
            'total_punches' => AttendanceLog::count(),
            'active_machines' => AttendanceMachine::where('status', 'active')->count(),
            'total_machines' => AttendanceMachine::count(),
            'last_fetch' => AttendanceLog::latest('created_at')->value('created_at')?->diffForHumans() ?? 'Never',
        ];

        $recentLogs = AttendanceLog::with('machine')
            ->orderBy('attendance_time', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        $recentAudits = AuditLog::orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit(6)
            ->get();

        if ($request->boolean('live')) {
            return response()->json([
                'stats' => $stats,
                'latest_id' => $recentLogs->max('id') ?? 0,
                'recent_logs' => AttendanceLogResource::collection($recentLogs)->resolve($request),
                'recent_audits' => $recentAudits->map(fn ($audit) => [
                    'id' => $audit->id,
                    'username' => $audit->username,
                    'action' => $audit->action,
                    'status' => $audit->status,
                    'details' => $audit->details,
                    'created_at_human' => $audit->created_at?->diffForHumans() ?? '',
                ])->values(),
                'server_time' => now()->format('H:i:s'),
            ]);
        }

        $machines = AttendanceMachine::all();

        return view('dashboard.index', compact('stats', 'recentLogs', 'recentAudits', 'machines'));
    }
}
