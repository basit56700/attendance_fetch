@extends('layouts.app')

@section('title', 'Audit Trail Logs')
@section('page_title', 'System Audit Trail & Security Logs')

@section('content')
<div class="space-y-6">

  <!-- Filter Card -->
  <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
    <form action="{{ route('audit-logs.index') }}" method="GET">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
        <div>
          <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Action Type</label>
          <select name="action" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            <option value="">All Actions</option>
            @foreach($actions as $act)
              <option value="{{ $act }}" {{ request('action') == $act ? 'selected' : '' }}>{{ $act }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Username</label>
          <input type="text" name="username" value="{{ request('username') }}" placeholder="Search username"
                 class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
        </div>

        <div>
          <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Status</label>
          <select name="status" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            <option value="">All Statuses</option>
            <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>Success</option>
            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed / Error</option>
            <option value="warning" {{ request('status') === 'warning' ? 'selected' : '' }}>Warning</option>
            <option value="info" {{ request('status') === 'info' ? 'selected' : '' }}>Info</option>
          </select>
        </div>

        <div>
          <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Date</label>
          <input type="date" name="date" value="{{ request('date') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
        </div>
      </div>

      <div class="flex items-center justify-between pt-4 mt-3 border-t border-slate-800 gap-3">
        <div class="flex items-center space-x-2">
          <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold px-4 py-2 rounded-xl transition">
            Filter Logs
          </button>
          <a href="{{ route('audit-logs.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium px-3.5 py-2 rounded-xl transition">
            Reset
          </a>
        </div>

        @if(auth()->user()->isAdmin())
          <button type="button" onclick="document.getElementById('pruneModal').classList.remove('hidden')" class="text-xs text-slate-400 hover:text-red-400">
            Prune Old Logs &rarr;
          </button>
        @endif
      </div>
    </form>
  </div>

  <!-- Audit Table -->
  <div class="bg-slate-900 border border-slate-800 rounded-3xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs text-slate-300">
        <thead class="uppercase bg-slate-950/70 text-slate-400 border-b border-slate-800">
          <tr>
            <th class="py-3 px-4">Timestamp</th>
            <th class="py-3 px-4">User</th>
            <th class="py-3 px-4">Action</th>
            <th class="py-3 px-4">Details / Description</th>
            <th class="py-3 px-4">IP Address</th>
            <th class="py-3 px-4">Result</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60 font-mono">
          @forelse($logs as $log)
            <tr class="hover:bg-slate-800/40 transition">
              <td class="py-3 px-4 text-slate-400">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
              <td class="py-3 px-4 font-bold text-white font-sans">{{ $log->username }}</td>
              <td class="py-3 px-4">
                <span class="px-2 py-0.5 rounded font-bold uppercase text-[10px] bg-slate-800 text-blue-400 border border-slate-700">
                  {{ $log->action }}
                </span>
              </td>
              <td class="py-3 px-4 font-sans text-slate-300 max-w-md truncate" title="{{ $log->details }}">{{ $log->details }}</td>
              <td class="py-3 px-4 text-slate-500">{{ $log->ip_address }}</td>
              <td class="py-3 px-4 font-sans">
                @php
                  $stCls = match($log->status) {
                    'success' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                    'failed' => 'bg-red-500/10 text-red-400 border-red-500/20',
                    'warning' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                    default => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                  };
                @endphp
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border uppercase {{ $stCls }}">{{ $log->status }}</span>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="6" class="py-12 text-center text-slate-500 font-sans">No audit records found matching your query.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($logs->hasPages())
      <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/40">
        {{ $logs->links() }}
      </div>
    @endif
  </div>

  <!-- Prune Modal -->
  <div id="pruneModal" class="fixed inset-0 bg-slate-950/85 backdrop-blur-sm flex items-center justify-center z-50 p-4 hidden">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-md p-6 rounded-3xl shadow-2xl space-y-4">
      <h3 class="text-sm font-bold text-white">Prune Historic Audit Logs</h3>
      <p class="text-xs text-slate-400">Permanently delete older audit records to optimize database size.</p>

      <form action="{{ route('audit-logs.clear') }}" method="POST" class="space-y-4">
        @csrf
        <div>
          <label class="block text-xs text-slate-400 mb-1">Delete logs older than:</label>
          <select name="older_than_days" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white">
            <option value="30">Older than 30 days</option>
            <option value="60">Older than 60 days</option>
            <option value="90">Older than 90 days</option>
          </select>
        </div>
        <div class="flex justify-end space-x-2 pt-2">
          <button type="button" onclick="document.getElementById('pruneModal').classList.add('hidden')" class="px-4 py-2 text-xs text-slate-400 hover:text-white">Cancel</button>
          <button type="submit" class="bg-red-600 hover:bg-red-500 text-white text-xs font-semibold px-4 py-2 rounded-xl transition">Prune Logs</button>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection
