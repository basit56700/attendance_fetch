<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . trim($request->input('action')) . '%');
        }

        if ($request->filled('username')) {
            $query->where('username', 'like', '%' . trim($request->input('username')) . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        $logs = $query->paginate(30)->withQueryString();

        $actions = AuditLog::select('action')->distinct()->pluck('action');

        return view('audit-logs.index', compact('logs', 'actions'));
    }

    public function clear(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Unauthorized');
        }

        $days = (int) $request->input('older_than_days', 30);
        $deleted = AuditLog::where('created_at', '<', now()->subDays($days))->delete();

        AuditLog::log('AUDIT_LOG_CLEANUP', "Pruned {$deleted} audit entries older than {$days} days.", 'warning');

        return back()->with('success', "Pruned {$deleted} audit log entries older than {$days} days.");
    }
}
