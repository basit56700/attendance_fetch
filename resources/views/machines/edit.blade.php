@extends('layouts.app')

@section('title', 'Edit Machine: ' . $machine->name)
@section('page_title', 'Edit Biometric Device Settings')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

  <!-- Breadcrumbs & Title -->
  <div class="flex items-center justify-between">
    <div>
      <a href="{{ route('machines.index') }}" class="text-xs text-red-400 hover:text-red-300 font-medium">&larr; Back to Machines Hub</a>
      <h2 class="text-xl font-bold text-white mt-1">Edit Machine: {{ $machine->name }}</h2>
      <p class="text-xs text-slate-400 mt-0.5">Modify connection parameters, location and status.</p>
    </div>
  </div>

  <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">

    @if($errors->any())
      <div class="bg-red-950/60 border border-red-800 text-red-300 p-4 rounded-xl text-xs space-y-1">
        <div class="font-bold text-red-200">Unable to save machine settings:</div>
        @foreach($errors->all() as $error)
          <div>&bull; {{ $error }}</div>
        @endforeach
      </div>
    @endif

    <form action="{{ route('machines.update', $machine) }}" method="POST" class="space-y-6">
      @csrf
      @method('PUT')

      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Machine Label / Name *</label>
          <input type="text" name="name" id="machName" value="{{ old('name', $machine->name) }}" required
                 class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Location / Department</label>
          <input type="text" name="location" id="machLocation" value="{{ old('location', $machine->location) }}"
                 class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">IP Address *</label>
          <input type="text" name="ip_address" id="machIp" value="{{ old('ip_address', $machine->ip_address) }}" required
                 class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:outline-none focus:border-blue-500">
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Port *</label>
          <input type="number" name="port" id="machPort" value="{{ old('port', $machine->port) }}" required
                 class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:outline-none focus:border-blue-500">
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Communication Protocol</label>
          @php $selectedProtocol = old('protocol', $machine->protocol); @endphp
          <select name="protocol" id="machProtocol" onchange="toggleAdmsBox()" class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
            <option value="auto" {{ $selectedProtocol === 'auto' ? 'selected' : '' }}>Auto (UDP & TCP Auto-detect)</option>
            <option value="udp" {{ $selectedProtocol === 'udp' ? 'selected' : '' }}>UDP Only</option>
            <option value="tcp" {{ $selectedProtocol === 'tcp' ? 'selected' : '' }}>TCP Only</option>
            <option value="adms" {{ $selectedProtocol === 'adms' ? 'selected' : '' }}>ADMS Push (Real-time Cloud for F22 / TimeCloud)</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Communication Key (0 if none)</label>
          <input type="number" name="comm_key" id="machCommKey" value="{{ old('comm_key', $machine->comm_key) }}"
                 class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:outline-none focus:border-blue-500">
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Serial Number (Optional / Auto-detected by ADMS)</label>
          <input type="text" name="serial_number" id="machSerial" value="{{ old('serial_number', $machine->serial_number) }}" placeholder="e.g. JJA1261301086"
                 class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white font-mono placeholder-slate-600 focus:outline-none focus:border-blue-500">
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Status</label>
          @php $selectedStatus = old('status', $machine->status); @endphp
          <select name="status" class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
            <option value="active" {{ $selectedStatus === 'active' ? 'selected' : '' }}>Active (Available for polling)</option>
            <option value="inactive" {{ $selectedStatus === 'inactive' ? 'selected' : '' }}>Inactive / Standby</option>
          </select>
        </div>
      </div>

      @php
        $reqHost = request()->getHost();
        $admsHost = ($reqHost && ! in_array($reqHost, ['127.0.0.1', 'localhost', '::1'], true)) ? $reqHost : (request()->server('SERVER_ADDR') ?: $reqHost);
        $admsPort = request()->getPort() ?: (request()->server('SERVER_PORT') ?: '80');
      @endphp
      <!-- ADMS Device Configuration Helper Box -->
      <div id="admsHelperBox" class="{{ $selectedProtocol === 'adms' ? '' : 'hidden' }} mt-4 p-4 bg-blue-950/30 border border-blue-500/40 rounded-xl text-xs space-y-2 text-blue-200">
        <div class="font-bold flex items-center gap-1.5 text-blue-300">
          <svg class="w-4 h-4 text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>ZKTeco F22 / ADMS Cloud Setup Instructions</span>
        </div>
        <p class="text-slate-300 leading-relaxed">On your biometric device screen, open <strong class="text-white">Menu &rarr; Comm. &rarr; Cloud Server Setting</strong> and enter:</p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 font-mono text-[11px] bg-slate-950/80 p-3 rounded-lg border border-slate-800">
          <div><span class="text-slate-500 block text-[10px]">Server Address:</span><span class="text-emerald-400 font-bold select-all">{{ $admsHost }}</span></div>
          <div><span class="text-slate-500 block text-[10px]">Server Port:</span><span class="text-emerald-400 font-bold select-all">{{ $admsPort }}</span></div>
          <div><span class="text-slate-500 block text-[10px]">Server Mode:</span><span class="text-white font-bold">ADMS</span></div>
          <div><span class="text-slate-500 block text-[10px]">HTTPS / Proxy:</span><span class="text-white font-bold">OFF</span></div>
        </div>
        <p class="text-[11px] text-slate-400">Once saved, the device pushes punches in real-time to <code>/iclock/cdata</code> and links automatically by Serial Number or IP.</p>
      </div>

      <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800">
        <a href="{{ route('machines.index') }}" class="px-5 py-2.5 text-xs text-slate-400 hover:text-white transition">Cancel</a>
        <button type="submit" class="bg-red-600 hover:bg-red-500 text-white font-semibold text-xs px-6 py-2.5 rounded-xl transition shadow-lg shadow-red-600/30 cursor-pointer">
          Save Changes
        </button>
      </div>

    </form>

  </div>

</div>
@endsection

@push('scripts')
<script>
function toggleAdmsBox() {
  const proto = document.getElementById('machProtocol').value;
  const box = document.getElementById('admsHelperBox');
  if (box) {
    box.classList.toggle('hidden', proto !== 'adms');
  }
}
document.addEventListener('DOMContentLoaded', toggleAdmsBox);
</script>
@endpush
