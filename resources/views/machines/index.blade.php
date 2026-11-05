@extends('layouts.app')

@section('title', 'Biometric Machines')
@section('page_title', 'GCS Biometric Devices Hub')

@section('content')
<div class="space-y-6">

  <!-- Header Bar -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h2 class="text-lg font-bold text-white">Biometric Devices</h2>
      <p class="text-xs text-slate-400 mt-0.5">Manage machine IP addresses, ports (standard 4370), direct cable or LAN connections.</p>
    </div>
    <div class="flex items-center space-x-2.5">
      <form action="{{ route('machines.fetch-all') }}" method="POST">
        @csrf
        <button type="submit" class="bg-red-600 hover:bg-red-500 text-white font-semibold text-xs px-3.5 py-2 rounded-xl transition shadow-lg shadow-red-600/20 flex items-center space-x-1.5 cursor-pointer">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
          <span>Fetch All</span>
        </button>
      </form>
      <a href="{{ route('machines.create') }}" class="bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-xs px-3.5 py-2 rounded-xl transition shadow-lg shadow-emerald-600/20 flex items-center space-x-1.5">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
        <span>Add & Diagnose</span>
      </a>
    </div>
  </div>

  <!-- Machines Grid -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    @forelse($machines as $machine)
      <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm space-y-4 hover:border-slate-700 transition">
        <div class="flex items-start justify-between">
          <div>
            <h3 class="text-base font-bold text-white">{{ $machine->name }}</h3>
            <div class="text-xs text-slate-400 mt-0.5">{{ $machine->location ?: 'No location specified' }}</div>
          </div>
          <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $machine->status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' : 'bg-slate-800 text-slate-500 border-slate-700' }}">
            {{ strtoupper($machine->status) }}
          </span>
        </div>

        <!-- Technical Parameters Box -->
        <div class="bg-slate-950/70 border border-slate-800/80 rounded-xl p-3.5 text-xs font-mono space-y-1.5 text-slate-300">
          <div class="flex justify-between">
            <span class="text-slate-500">IP & Port:</span>
            <span class="text-white font-semibold">{{ $machine->ip_address }}:{{ $machine->port }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Protocol:</span>
            @if($machine->protocol === 'adms')
              <span class="text-blue-400 uppercase font-bold">ADMS Push (Real-time)</span>
            @else
              <span class="text-red-400 uppercase font-bold">{{ $machine->protocol }}</span>
            @endif
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Comm Key:</span>
            <span>{{ $machine->comm_key }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Firmware:</span>
            <span class="text-slate-300 truncate max-w-[150px]">{{ $machine->firmware_version ?: 'Not queried' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Total Logs in DB:</span>
            <span class="text-emerald-400 font-bold">{{ number_format($machine->attendance_logs_count) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">Last Synced:</span>
            <span class="text-slate-400">{{ $machine->last_connected_at ? $machine->last_connected_at->diffForHumans() : 'Never' }}</span>
          </div>
        </div>

        @if($machine->last_error)
          <div class="bg-red-950/40 border border-red-800/80 text-red-400 text-xs p-2.5 rounded-xl truncate" title="{{ $machine->last_error }}">
            ⚠️ {{ $machine->last_error }}
          </div>
        @endif

        <!-- Action Buttons -->
        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-800/80">
          <button onclick="testMachineDiagnostic('{{ $machine->ip_address }}', {{ $machine->port }}, '{{ $machine->protocol }}', {{ $machine->comm_key }}, {{ $machine->id }})" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs py-2 rounded-xl transition font-medium flex items-center justify-center space-x-1 cursor-pointer">
            <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            <span>Diagnostics</span>
          </button>

          <form action="{{ route('machines.fetch', $machine) }}" method="POST">
            @csrf
            <button type="submit" class="w-full bg-red-600 hover:bg-red-500 text-white text-xs py-2 rounded-xl transition font-medium flex items-center justify-center space-x-1 shadow-md shadow-red-600/20 cursor-pointer">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
              <span>Fetch Logs</span>
            </button>
          </form>

          <form action="{{ route('machines.sync-time', $machine) }}" method="POST">
            @csrf
            <button type="submit" class="w-full bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs py-2 rounded-xl transition flex items-center justify-center space-x-1">
              <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              <span>Sync Clock</span>
            </button>
          </form>

          <div class="flex items-center space-x-1">
            <a href="{{ route('machines.edit', $machine) }}" class="flex-1 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs py-2 rounded-xl transition text-center">Edit</a>
            <form action="{{ route('machines.clear', $machine) }}" method="POST" onsubmit="return confirm('WARNING: Are you sure you want to clear all punch logs from device \'{{ $machine->name }}\'? Ensure all logs are already fetched.');">
              @csrf
              <button type="submit" class="p-2 text-slate-500 hover:text-amber-400 hover:bg-slate-800 rounded-xl transition" title="Clear Machine Memory">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
              </button>
            </form>
            <form action="{{ route('machines.destroy', $machine) }}" method="POST" onsubmit="return confirm('Delete machine {{ $machine->name }}?');">
              @csrf
              @method('DELETE')
              <button type="submit" class="p-2 text-slate-500 hover:text-red-400 hover:bg-slate-800 rounded-xl transition" title="Delete Machine">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
              </button>
            </form>
          </div>
        </div>
      </div>
    @empty
      <div class="col-span-3 bg-slate-900 border border-slate-800 rounded-2xl p-12 text-center text-slate-400">
        <div class="w-12 h-12 bg-slate-800 text-slate-500 rounded-2xl flex items-center justify-center mx-auto mb-3">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
        </div>
        <div class="text-sm font-semibold text-white">No biometric machines registered</div>
        <div class="text-xs text-slate-500 mt-1">Connect your ZKTeco device via Ethernet cable or LAN and add it here.</div>
        <a href="{{ route('machines.create') }}" class="inline-flex items-center space-x-1.5 mt-4 bg-red-600 hover:bg-red-500 text-white font-medium text-xs px-4 py-2.5 rounded-xl transition shadow-lg shadow-red-600/20">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
          <span>Add & Diagnose First Machine</span>
        </a>
      </div>
    @endforelse
  </div>

  <!-- Live Diagnostic Modal -->
  <div id="diagnosticModal" class="fixed inset-0 bg-slate-950/85 backdrop-blur-sm flex items-center justify-center z-50 p-4 hidden">
    <div class="bg-slate-900 border border-slate-800 w-full max-w-xl p-6 rounded-2xl shadow-2xl flex flex-col space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-slate-800">
        <h3 class="text-base font-bold text-white flex items-center space-x-2">
          <span>Device Connectivity Diagnostics</span>
        </h3>
        <button onclick="closeDiagnosticModal()" class="text-slate-400 hover:text-white text-xs p-1">✕</button>
      </div>

      <div id="diagnosticLoading" class="py-8 text-center text-xs text-slate-400 space-y-2">
        <div class="inline-block animate-spin text-xl">⏳</div>
        <div>Probing network socket, port 4370 & ZKTeco handshake...</div>
      </div>

      <div id="diagnosticResults" class="space-y-4 hidden">
        <!-- Rendered by JS -->
      </div>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
function testMachineDiagnostic(ip, port, protocol, commKey, machineId) {
  const modal = document.getElementById('diagnosticModal');
  const loading = document.getElementById('diagnosticLoading');
  const results = document.getElementById('diagnosticResults');

  modal.classList.remove('hidden');
  loading.classList.remove('hidden');
  results.classList.add('hidden');

  fetch('{{ route("machines.test-connection") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}'
    },
    body: JSON.stringify({
      ip_address: ip,
      port: port,
      protocol: protocol,
      comm_key: commKey,
      machine_id: machineId
    })
  })
  .then(res => res.json())
  .then(data => {
    loading.classList.add('hidden');
    results.classList.remove('hidden');

    if (data.success) {
      results.innerHTML = `
        <div class="bg-emerald-950/40 border border-emerald-800 p-4 rounded-xl space-y-2 text-xs">
          <div class="flex items-center justify-between">
            <span class="font-bold text-emerald-400 text-sm">✓ Device Connected Successfully</span>
            <span class="bg-emerald-500/20 text-emerald-300 font-mono px-2 py-0.5 rounded text-[11px]">${data.latency_ms} ms</span>
          </div>
          <div class="text-slate-300">${data.error_summary}</div>
        </div>

        <div class="bg-slate-950 border border-slate-800 p-3.5 rounded-xl font-mono text-xs space-y-1.5 text-slate-300">
          <div class="flex justify-between"><span class="text-slate-500">Protocol:</span><span class="text-red-400 font-bold">${data.protocol}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Firmware:</span><span>${data.firmware || 'Unknown'}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Device Clock:</span><span>${data.device_time || 'Unknown'}</span></div>
          <div class="flex justify-between"><span class="text-slate-500">Clock Drift:</span><span class="${Math.abs(data.time_drift_seconds) > 60 ? 'text-amber-400 font-bold' : 'text-slate-400'}">${data.time_drift_seconds ? data.time_drift_seconds + ' seconds' : 'N/A'}</span></div>
        </div>
      `;
    } else {
      let resolutionHtml = data.resolution_steps ? data.resolution_steps.map(s => `<li>${s}</li>`).join('') : '';

      results.innerHTML = `
        <div class="bg-red-950/40 border border-red-800 p-4 rounded-xl space-y-2 text-xs">
          <div class="flex items-center justify-between">
            <span class="font-bold text-red-400 text-sm">✕ Connection Failed</span>
            <span class="bg-red-500/20 text-red-300 font-mono px-2 py-0.5 rounded text-[10px]">${data.error_code || 'ERROR'}</span>
          </div>
          <div class="text-slate-200 font-medium">${data.error_summary}</div>
        </div>

        <div class="bg-slate-950 border border-slate-800 p-4 rounded-xl space-y-3 text-xs">
          <div>
            <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Root Cause Analysis</div>
            <div class="text-slate-300 mt-1">${data.root_cause || 'Device unreachable or protocol packet unacknowledged.'}</div>
          </div>

          <div>
            <div class="text-[10px] uppercase font-bold text-red-400 tracking-wider">Step-by-Step Resolution Guide</div>
            <ul class="list-disc list-inside space-y-1 text-slate-300 mt-1.5 text-[11px] leading-relaxed">
              ${resolutionHtml}
            </ul>
          </div>
        </div>
      `;
    }
  })
  .catch(err => {
    loading.classList.add('hidden');
    results.classList.remove('hidden');
    results.innerHTML = `<div class="text-xs text-red-400 p-4">Diagnostic request failed: ${err.message}</div>`;
  });
}

function closeDiagnosticModal() {
  document.getElementById('diagnosticModal').classList.add('hidden');
}
</script>
@endpush
