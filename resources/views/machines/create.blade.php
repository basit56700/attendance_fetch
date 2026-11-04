@extends('layouts.app')

@section('title', 'Add Biometric Machine')
@section('page_title', 'Register & Diagnose Biometric Device')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

  <!-- Breadcrumbs & Title -->
  <div class="flex items-center justify-between">
    <div>
      <a href="{{ route('machines.index') }}" class="text-xs text-red-400 hover:text-red-300 font-medium">&larr; Back to Machines Hub</a>
      <h2 class="text-xl font-bold text-white mt-1">Add Biometric Machine</h2>
      <p class="text-xs text-slate-400 mt-0.5">Configure device network parameters and run pre-add diagnostics before saving.</p>
    </div>
  </div>

  <!-- Main Card with Form & Diagnostic Sandbox -->
  <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">

    <form id="createMachineForm" action="{{ route('machines.store') }}" method="POST" class="space-y-6">
      @csrf

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Machine Label / Name *</label>
          <input type="text" name="name" id="machName" value="{{ old('name') }}" required placeholder="e.g. Ground Floor Entrance"
                 class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-blue-500">
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Location / Department</label>
          <input type="text" name="location" id="machLocation" value="{{ old('location') }}" placeholder="e.g. Head Office Reception"
                 class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-blue-500">
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">IP Address *</label>
          <input type="text" name="ip_address" id="machIp" value="{{ old('ip_address', '192.168.1.201') }}" required placeholder="192.168.1.201"
                 class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white font-mono placeholder-slate-600 focus:outline-none focus:border-blue-500">
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Port (Default 4370) *</label>
          <input type="number" name="port" id="machPort" value="{{ old('port', 4370) }}" required
                 class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:outline-none focus:border-blue-500">
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Communication Protocol</label>
          <select name="protocol" id="machProtocol" class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
            <option value="auto">Auto (UDP & TCP Auto-detect)</option>
            <option value="udp">UDP Only (Most B&W / TFT models)</option>
            <option value="tcp">TCP Only (SilkID / SpeedFace / Linux)</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Communication Key (0 if none)</label>
          <input type="number" name="comm_key" id="machCommKey" value="{{ old('comm_key', 0) }}"
                 class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:outline-none focus:border-blue-500">
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Status</label>
          <select name="status" id="machStatus" class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
            <option value="active">Active (Available for polling)</option>
            <option value="inactive">Inactive / Standby</option>
          </select>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- PRE-ADD DIAGNOSTIC SANDBOX -->
      <!-- ========================================== -->
      <div class="border-t border-slate-800 pt-5 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-950 p-4 rounded-2xl border border-slate-800">
          <div>
            <div class="text-xs font-bold text-white flex items-center space-x-2">
              <span class="w-2 h-2 rounded-full bg-red-500"></span>
              <span>Pre-Add Connectivity Sandbox</span>
            </div>
            <div class="text-[11px] text-slate-400 mt-0.5">Test socket ping, port 4370 & ZK protocol handshake before saving to database.</div>
          </div>
          <button type="button" onclick="runPreAddDiagnostic()" id="btnRunDiag" class="bg-slate-800 hover:bg-red-600 text-slate-200 hover:text-white font-semibold text-xs px-4 py-2 rounded-xl transition flex items-center space-x-2 border border-slate-700 shadow-sm shrink-0 cursor-pointer">
            <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            <span>Test Connectivity & Diagnostics</span>
          </button>
        </div>

        <!-- Live Diagnostic Report Container -->
        <div id="preAddDiagOutput" class="hidden">
          <!-- Dynamically populated -->
        </div>
      </div>

      <!-- Form Submission Footer -->
      <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800">
        <a href="{{ route('machines.index') }}" class="px-5 py-2.5 text-xs text-slate-400 hover:text-white transition">Cancel</a>
        <button type="submit" class="bg-red-600 hover:bg-red-500 text-white font-semibold text-xs px-6 py-2.5 rounded-xl transition shadow-lg shadow-red-600/30 flex items-center space-x-1.5 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
          <span>Save Machine</span>
        </button>
      </div>

    </form>

  </div>

</div>
@endsection

@push('scripts')
<script>
function runPreAddDiagnostic() {
  const ip = document.getElementById('machIp').value;
  const port = document.getElementById('machPort').value;
  const protocol = document.getElementById('machProtocol').value;
  const commKey = document.getElementById('machCommKey').value;
  const btn = document.getElementById('btnRunDiag');
  const container = document.getElementById('preAddDiagOutput');

  if (!ip) {
    alert('Please enter an IP address before running diagnostics.');
    return;
  }

  btn.disabled = true;
  btn.innerHTML = `<span class="animate-spin mr-1">⏳</span> Probing Device...`;

  container.classList.remove('hidden');
  container.innerHTML = `
    <div class="bg-slate-950 border border-slate-800 p-4 rounded-xl text-center text-xs text-slate-400 space-y-1">
      <div class="inline-block animate-spin text-lg">⏳</div>
      <div>Attempting handshake with ${ip}:${port} (${protocol})...</div>
    </div>
  `;

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
      comm_key: commKey
    })
  })
  .then(res => res.json())
  .then(data => {
    btn.disabled = false;
    btn.innerHTML = `
      <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
      <span>Retest Connectivity</span>
    `;

    if (data.success) {
      container.innerHTML = `
        <div class="bg-emerald-950/40 border border-emerald-800 p-5 rounded-2xl space-y-3 text-xs">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
              <span class="font-bold text-emerald-400 text-sm">Diagnostic Succeeded: Device is Online & Responsive</span>
            </div>
            <span class="bg-emerald-500/20 text-emerald-300 font-mono px-2 py-0.5 rounded text-[11px]">${data.latency_ms} ms</span>
          </div>

          <div class="text-slate-300">${data.error_summary}</div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2 border-t border-emerald-900/40 font-mono text-[11px]">
            <div class="bg-slate-950/80 p-2 rounded-lg"><span class="text-slate-500 block">Protocol:</span><span class="text-white font-bold">${data.protocol}</span></div>
            <div class="bg-slate-950/80 p-2 rounded-lg"><span class="text-slate-500 block">Firmware:</span><span class="text-white truncate block">${data.firmware || 'N/A'}</span></div>
            <div class="bg-slate-950/80 p-2 rounded-lg"><span class="text-slate-500 block">Device RTC:</span><span class="text-white truncate block">${data.device_time || 'N/A'}</span></div>
            <div class="bg-slate-950/80 p-2 rounded-lg"><span class="text-slate-500 block">Clock Drift:</span><span class="${Math.abs(data.time_drift_seconds) > 60 ? 'text-amber-400 font-bold' : 'text-slate-300'}">${data.time_drift_seconds ? data.time_drift_seconds + 's' : '0s'}</span></div>
          </div>
        </div>
      `;
    } else {
      let resolutionItems = data.resolution_steps ? data.resolution_steps.map(s => `<li>${s}</li>`).join('') : '';

      container.innerHTML = `
        <div class="bg-red-950/40 border border-red-800 p-5 rounded-2xl space-y-4 text-xs">
          <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2">
              <span class="w-3 h-3 rounded-full bg-red-400"></span>
              <span class="font-bold text-red-400 text-sm">Diagnostic Failed: Unable to Connect</span>
            </div>
            <span class="bg-red-500/20 text-red-300 font-mono px-2 py-0.5 rounded text-[10px]">${data.error_code || 'ERROR'}</span>
          </div>

          <div class="text-slate-200 font-medium">${data.error_summary}</div>

          <div class="bg-slate-950/90 border border-slate-800 p-4 rounded-xl space-y-3">
            <div>
              <div class="text-[10px] uppercase font-bold text-slate-500 tracking-wider">Root Cause Analysis</div>
              <div class="text-slate-300 mt-1">${data.root_cause || 'The network host did not reply or port is closed.'}</div>
            </div>

            <div>
              <div class="text-[10px] uppercase font-bold text-blue-400 tracking-wider">Step-by-Step Resolution Guide</div>
              <ul class="list-disc list-inside space-y-1.5 text-slate-300 mt-1.5 text-[11px] leading-relaxed">
                ${resolutionItems}
              </ul>
            </div>
          </div>
        </div>
      `;
    }
  })
  .catch(err => {
    btn.disabled = false;
    btn.textContent = 'Test Connectivity & Diagnostics';
    container.innerHTML = `<div class="text-xs text-red-400 p-3 bg-red-950/30 rounded-xl">Diagnostic request error: ${err.message}</div>`;
  });
}
</script>
@endpush
