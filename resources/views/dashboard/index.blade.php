@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'System Overview & Diagnostics')

@section('content')
<div class="space-y-6">

  <!-- Hero Welcome Banner -->
  <div class="hero-banner bg-white dark:bg-gradient-to-r dark:from-blue-950/60 dark:via-slate-900 dark:to-indigo-950/60 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 shadow-sm dark:shadow-xl flex flex-col md:flex-row items-center justify-between gap-4">
    <div>
      <h2 class="text-xl font-extrabold text-white">GCS Attendance ERP</h2>
      <p class="text-xs text-slate-400 mt-1">Multi-device management, real-time connectivity diagnostics, duplicate suppression, and JSON extraction.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2.5">
      <form action="{{ route('machines.fetch-all') }}" method="POST">
        @csrf
        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-medium text-xs px-4 py-2.5 rounded-xl transition shadow-lg shadow-blue-600/30 flex items-center space-x-2">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
          <span>Fetch All Machines</span>
        </button>
      </form>

      <a href="{{ route('machines.create') }}" class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-xs px-4 py-2.5 rounded-xl transition shadow-lg shadow-emerald-600/30 flex items-center space-x-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        <span>Add & Diagnose</span>
      </a>

      <a href="{{ route('attendance.export.json') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-medium text-xs px-3.5 py-2.5 rounded-xl transition flex items-center space-x-1.5">
        <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
        <span>Export JSON</span>
      </a>
    </div>
  </div>

  <!-- Metric Stat Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between text-slate-400 text-[11px] font-semibold uppercase tracking-wider">
        <span>Punches Today</span>
        <div class="w-7 h-7 rounded-lg bg-blue-500/10 text-blue-400 flex items-center justify-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
      </div>
      <div class="text-3xl font-extrabold text-white mt-2">{{ number_format($stats['punches_today']) }}</div>
      <div class="text-slate-500 text-[11px] mt-1">Recorded today</div>
    </div>

    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between text-slate-400 text-[11px] font-semibold uppercase tracking-wider">
        <span>Total Records</span>
        <div class="w-7 h-7 rounded-lg bg-purple-500/10 text-purple-400 flex items-center justify-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7M4 7c0-2 1.5-3 3.5-3h9c2 0 3.5 1 3.5 3M4 7h16"></path></svg>
        </div>
      </div>
      <div class="text-3xl font-extrabold text-white mt-2">{{ number_format($stats['total_punches']) }}</div>
      <div class="text-slate-500 text-[11px] mt-1">Stored in database</div>
    </div>

    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between text-slate-400 text-[11px] font-semibold uppercase tracking-wider">
        <span>Active Machines</span>
        <div class="w-7 h-7 rounded-lg bg-emerald-500/10 text-emerald-400 flex items-center justify-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
        </div>
      </div>
      <div class="text-3xl font-extrabold text-white mt-2">{{ $stats['active_machines'] }} <span class="text-sm font-normal text-slate-500">/ {{ $stats['total_machines'] }}</span></div>
      <div class="text-slate-500 text-[11px] mt-1">Configured devices</div>
    </div>

    <div class="bg-slate-900 border border-slate-800/80 rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between text-slate-400 text-[11px] font-semibold uppercase tracking-wider">
        <span>Last Polled</span>
        <div class="w-7 h-7 rounded-lg bg-amber-500/10 text-amber-400 flex items-center justify-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
        </div>
      </div>
      <div class="text-xl font-bold text-amber-400 mt-2 truncate">{{ $stats['last_fetch'] }}</div>
      <div class="text-slate-500 text-[11px] mt-1">Machine synchronization</div>
    </div>
  </div>

  <!-- Connected Machines Summary Bar -->
  <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5">
    <div class="flex items-center justify-between mb-3">
      <h3 class="text-sm font-bold text-white flex items-center space-x-2">
        <span>Connected Biometric Machines</span>
        <span class="text-xs bg-slate-800 text-slate-400 px-2 py-0.5 rounded-full font-mono">{{ $machines->count() }}</span>
      </h3>
      <a href="{{ route('machines.index') }}" class="text-xs text-blue-400 hover:text-blue-300 font-medium">Manage All &rarr;</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
      @forelse($machines as $m)
        <div class="bg-slate-950 border border-slate-800/80 p-3.5 rounded-xl flex items-center justify-between text-xs">
          <div>
            <div class="font-semibold text-white">{{ $m->name }}</div>
            <div class="text-slate-500 font-mono text-[11px]">{{ $m->ip_address }}:{{ $m->port }} ({{ strtoupper($m->protocol) }})</div>
          </div>
          <div class="flex items-center space-x-2">
            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $m->status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-800 text-slate-400' }}">
              {{ strtoupper($m->status) }}
            </span>
            <form action="{{ route('machines.fetch', $m) }}" method="POST">
              @csrf
              <button type="submit" class="p-1 bg-slate-800 hover:bg-blue-600 text-slate-300 hover:text-white rounded-lg transition" title="Fetch Logs Now">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
              </button>
            </form>
          </div>
        </div>
      @empty
        <div class="col-span-3 text-center py-6 text-slate-500 text-xs">No machines added yet. Click "Add & Diagnose" above to get started.</div>
      @endforelse
    </div>
  </div>

  <!-- Two Column Preview: Recent Attendance Logs & Audit Trail -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Recent Logs Table (2 columns) -->
    <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold text-white">Recent Attendance Logs</h3>
        <a href="{{ route('attendance.index') }}" class="text-xs text-blue-400 hover:text-blue-300 font-medium">View Full Table &rarr;</a>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs text-slate-300">
          <thead class="uppercase bg-slate-950/70 text-slate-500 border-b border-slate-800">
            <tr>
              <th class="py-2.5 px-3">ERP ID</th>
              <th class="py-2.5 px-3">Punch Timestamp</th>
              <th class="py-2.5 px-3">State</th>
              <th class="py-2.5 px-3">Mode</th>
              <th class="py-2.5 px-3">Machine</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-800/60 font-mono">
            @forelse($recentLogs as $log)
              <tr class="hover:bg-slate-800/40 transition">
                <td class="py-2 px-3 font-bold text-white">{{ $log->erp_id }}</td>
                <td class="py-2 px-3 text-slate-300">{{ $log->attendance_time->format('Y-m-d H:i:s') }}</td>
                <td class="py-2 px-3 font-sans">
                  @php
                    $badgeCls = match($log->punch_type) {
                      0 => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                      1 => 'bg-red-500/10 text-red-400 border-red-500/20',
                      default => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                    };
                  @endphp
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $badgeCls }}">{{ $log->type_remarks }}</span>
                </td>
                <td class="py-2 px-3 text-slate-400 font-sans">{{ $log->verification_mode }}</td>
                <td class="py-2 px-3 text-slate-400 font-sans">{{ $log->machine?->name ?? 'Unknown' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="py-6 text-center text-slate-500 font-sans">No attendance records found. Click "Fetch All Machines" to download punches.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <!-- Recent Audit Activity (1 column) -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold text-white">System Audit Trail</h3>
        <a href="{{ route('audit-logs.index') }}" class="text-xs text-blue-400 hover:text-blue-300 font-medium">All Logs &rarr;</a>
      </div>

      <div class="space-y-3">
        @forelse($recentAudits as $audit)
          <div class="text-xs border-b border-slate-800/60 pb-2.5 last:border-none">
            <div class="flex items-center justify-between">
              <span class="font-semibold text-white">{{ $audit->username }}</span>
              <span class="text-[10px] text-slate-500 font-mono">{{ $audit->created_at->diffForHumans() }}</span>
            </div>
            <div class="flex items-center space-x-1.5 mt-1">
              <span class="text-[9px] px-1.5 py-0.5 rounded font-mono font-bold uppercase {{ $audit->status === 'success' ? 'bg-blue-500/10 text-blue-400' : 'bg-red-500/10 text-red-400' }}">
                {{ $audit->action }}
              </span>
              <span class="text-slate-400 truncate">{{ $audit->details }}</span>
            </div>
          </div>
        @empty
          <div class="text-center py-6 text-slate-500 text-xs">No audit logs yet.</div>
        @endforelse
      </div>
    </div>

  </div>

</div>
@endsection
