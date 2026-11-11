@extends('layouts.app')

@section('title', 'Attendance Records')
@section('page_title', 'Attendance Punches & JSON Extraction')

@section('content')
<div class="space-y-6">

  <!-- Filter & Action Bar -->
  <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-sm space-y-4">
    <form action="{{ route('attendance.index') }}" method="GET">
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
        <div>
          <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">From Date</label>
          <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
        </div>

        <div>
          <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">To Date</label>
          <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
        </div>

        <div>
          <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Employee ERP ID</label>
          <input type="text" name="erp_id" value="{{ request('erp_id') }}" placeholder="e.g. 10023" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
        </div>

        <div>
          <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Machine</label>
          <select name="machine_id" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            <option value="">All Machines</option>
            @foreach($machines as $m)
              <option value="{{ $m->id }}" {{ request('machine_id') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
            @endforeach
          </select>
        </div>

        <div>
          <label class="block text-[11px] font-semibold uppercase text-slate-400 mb-1">Punch State</label>
          <select name="punch_type" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white">
            <option value="">All States</option>
            <option value="0" {{ request('punch_type') === '0' ? 'selected' : '' }}>Check-In (IN)</option>
            <option value="1" {{ request('punch_type') === '1' ? 'selected' : '' }}>Check-Out (OUT)</option>
            <option value="2" {{ request('punch_type') === '2' ? 'selected' : '' }}>Break OUT</option>
            <option value="3" {{ request('punch_type') === '3' ? 'selected' : '' }}>Break IN</option>
          </select>
        </div>
      </div>

      <div class="flex flex-wrap items-center justify-between pt-4 mt-3 border-t border-slate-800/80 gap-3">
        <div class="flex items-center space-x-2">
          <button type="submit" class="bg-red-600 hover:bg-red-500 text-white text-xs font-semibold px-4 py-2 rounded-xl transition shadow-sm cursor-pointer">
            Apply Filters
          </button>
          <a href="{{ route('attendance.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-medium px-3.5 py-2 rounded-xl transition">
            Reset
          </a>
        </div>

        <!-- Export Actions -->
        <div class="flex items-center space-x-2">
          <button type="button" onclick="inspectCurrentJson()" class="bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold px-3.5 py-2 rounded-xl transition shadow-sm flex items-center space-x-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
            <span>View JSON Payload</span>
          </button>

          <a href="{{ route('attendance.export.json', request()->query()) }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-semibold px-3.5 py-2 rounded-xl transition flex items-center space-x-1.5">
            <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            <span>Export JSON</span>
          </a>

          <a href="{{ route('attendance.export.csv', request()->query()) }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-semibold px-3.5 py-2 rounded-xl transition flex items-center space-x-1.5">
            <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            <span>Export CSV</span>
          </a>
        </div>
      </div>
    </form>
  </div>

  <!-- Attendance Table Card -->
  <div class="bg-slate-900 border border-slate-800 rounded-3xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-800 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-400">
      <span id="attendanceSummary">Showing <strong class="text-white">{{ $logs->firstItem() ?? 0 }}-{{ $logs->lastItem() ?? 0 }}</strong> of <strong class="text-white">{{ $logs->total() }}</strong> attendance records</span>
      <div class="flex items-center space-x-3">
        <span class="text-slate-500 hidden lg:inline">1st Daily Entry: Check-In &bull; 2nd Daily Entry: Check-Out</span>
        <x-live-refresh-control select-id="attendanceLiveSelect" dot-id="attendanceLiveDot" status-id="attendanceLiveStatus" />
      </div>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs text-slate-300">
        <thead class="uppercase bg-slate-950/70 text-slate-400 border-b border-slate-800">
          <tr>
            <th class="py-3 px-4">Log ID</th>
            <th class="py-3 px-4">Employee ERP ID</th>
            <th class="py-3 px-4">Attendance Timestamp</th>
            <th class="py-3 px-4">Punch State</th>
            <th class="py-3 px-4">Verification</th>
            <th class="py-3 px-4">Machine</th>
            <th class="py-3 px-4">Database Created</th>
          </tr>
        </thead>
        <tbody id="attendanceTableBody" class="divide-y divide-slate-800/60 font-mono">
          @forelse($logs as $log)
            <tr class="hover:bg-slate-800/40 transition" data-log-id="{{ $log->id }}">
              <td class="py-3 px-4 text-slate-500">#{{ $log->id }}</td>
              <td class="py-3 px-4 font-bold text-white">{{ $log->erp_id }}</td>
              <td class="py-3 px-4 text-slate-200">{{ $log->attendance_time->format('Y-m-d H:i:s') }}</td>
              <td class="py-3 px-4 font-sans">
                @php
                  $badgeCls = match($log->punch_type) {
                    0 => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                    1 => 'bg-red-500/10 text-red-400 border-red-500/20',
                    default => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                  };
                  $punchText = match($log->punch_type) {
                    0 => 'Check-In (IN)',
                    1 => 'Check-Out (OUT)',
                    default => $log->type_remarks,
                  };
                @endphp
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold border {{ $badgeCls }}">
                  {{ $punchText }}
                </span>
              </td>
              <td class="py-3 px-4 text-slate-400 font-sans">{{ $log->verification_mode }}</td>
              <td class="py-3 px-4 font-sans text-slate-300">
                {{ $log->machine?->name ?? 'Unknown' }}
                <span class="text-slate-500 text-[10px] font-mono">({{ $log->machine?->ip_address }})</span>
              </td>
              <td class="py-3 px-4 text-slate-500">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="py-12 text-center text-slate-500 font-sans">
                No attendance logs found matching the selected filters.
              </td>
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

  <!-- JSON Payload Inspector Modal -->
  <div id="jsonInspectorModal" class="fixed inset-0 bg-slate-950/85 backdrop-blur-sm flex items-center justify-center z-50 p-4 hidden">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-3xl p-6 rounded-3xl shadow-2xl flex flex-col max-h-[85vh]">
      <div class="flex items-center justify-between pb-3 border-b border-slate-800">
        <div class="flex items-center space-x-2">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
          <h3 class="text-sm font-bold text-white">Live Attendance JSON Payload</h3>
        </div>
        <div class="flex items-center space-x-2">
          <button onclick="copyJsonPayload()" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs px-3 py-1.5 rounded-xl border border-slate-700 transition">Copy JSON</button>
          <button onclick="closeJsonInspectorModal()" class="text-slate-400 hover:text-white p-1 text-xs">✕</button>
        </div>
      </div>
      <div class="flex-1 overflow-y-auto mt-4 p-4 bg-slate-950 rounded-2xl font-mono text-xs text-emerald-400 border border-slate-800">
        <pre id="jsonPayloadArea">Loading...</pre>
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
function inspectCurrentJson() {
  const modal = document.getElementById('jsonInspectorModal');
  const area = document.getElementById('jsonPayloadArea');
  modal.classList.remove('hidden');
  area.textContent = 'Loading live JSON from REST API...';

  const currentParams = window.location.search;
  fetch('{{ route("api.attendance") }}' + currentParams)
    .then(r => r.json())
    .then(data => {
      area.textContent = JSON.stringify(data.data, null, 2);
    })
    .catch(err => {
      area.textContent = 'Error fetching JSON: ' + err.message;
    });
}

function closeJsonInspectorModal() {
  document.getElementById('jsonInspectorModal').classList.add('hidden');
}

function copyJsonPayload() {
  const text = document.getElementById('jsonPayloadArea').textContent;
  navigator.clipboard.writeText(text).then(() => {
    alert('JSON payload copied to clipboard!');
  });
}

document.addEventListener('DOMContentLoaded', function () {
  if (!window.GCSLiveRefresh) return;
  let lastSeenId = {{ (int) ($logs->max('id') ?? 0) }};

  window.GCSLiveRefresh.init({
    selectId: 'attendanceLiveSelect',
    dotId: 'attendanceLiveDot',
    statusId: 'attendanceLiveStatus',
    defaultSeconds: {{ (int) \App\Models\SystemSetting::get('ui_refresh_interval', '5') }},
    url: function () {
      const params = new URLSearchParams(window.location.search);
      params.set('live', '1');
      return '{{ route("attendance.index") }}?' + params.toString();
    },
    onData: function (data) {
      const esc = window.GCSLiveRefresh.escapeHtml;
      const summaryEl = document.getElementById('attendanceSummary');
      if (summaryEl && typeof data.total !== 'undefined') {
        summaryEl.innerHTML = `Showing <strong class="text-white">${esc(data.from)}-${esc(data.to)}</strong> of <strong class="text-white">${esc(data.total)}</strong> attendance records`;
      }

      const tbody = document.getElementById('attendanceTableBody');
      if (tbody && Array.isArray(data.logs)) {
        if (data.logs.length === 0) {
          tbody.innerHTML = '<tr><td colspan="7" class="py-12 text-center text-slate-500 font-sans">No attendance logs found matching the selected filters.</td></tr>';
        } else {
          tbody.innerHTML = data.logs.map(function (log) {
            const isNew = Number(log.id) > lastSeenId;
            const badge = window.GCSLiveRefresh.renderPunchBadge(log.punch_type, log.type_remarks, log.punch_label);
            return `<tr class="hover:bg-slate-800/40 transition ${isNew ? 'live-row-new' : ''}" data-log-id="${esc(log.id)}">
              <td class="py-3 px-4 text-slate-500">#${esc(log.id)}</td>
              <td class="py-3 px-4 font-bold text-white">${esc(log.erp_id)}</td>
              <td class="py-3 px-4 text-slate-200">${esc(log.attendance_time)}</td>
              <td class="py-3 px-4 font-sans">${badge}</td>
              <td class="py-3 px-4 text-slate-400 font-sans">${esc(log.verification_mode)}</td>
              <td class="py-3 px-4 font-sans text-slate-300">${esc(log.machine_name)} <span class="text-slate-500 text-[10px] font-mono">(${esc(log.machine_ip)})</span></td>
              <td class="py-3 px-4 text-slate-500">${esc(log.created_at)}</td>
            </tr>`;
          }).join('');
        }
        if (Number(data.latest_id || 0) > lastSeenId) {
          lastSeenId = Number(data.latest_id);
        }
      }
    },
  });
});
</script>
@endpush
