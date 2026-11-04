<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLog;
use App\Models\AttendanceMachine;
use App\Models\AuditLog;

class DashboardController extends Controller
{
    public function index()
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
            ->limit(10)
            ->get();

        $recentAudits = AuditLog::orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        $machines = AttendanceMachine::all();

        return view('dashboard.index', compact('stats', 'recentLogs', 'recentAudits', 'machines'));
    }
}
