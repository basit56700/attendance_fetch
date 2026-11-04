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
          <select name="protocol" id="machProtocol" class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
            <option value="auto" {{ $machine->protocol === 'auto' ? 'selected' : '' }}>Auto (UDP & TCP Auto-detect)</option>
            <option value="udp" {{ $machine->protocol === 'udp' ? 'selected' : '' }}>UDP Only</option>
            <option value="tcp" {{ $machine->protocol === 'tcp' ? 'selected' : '' }}>TCP Only</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Communication Key (0 if none)</label>
          <input type="number" name="comm_key" id="machCommKey" value="{{ old('comm_key', $machine->comm_key) }}"
                 class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:outline-none focus:border-blue-500">
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Status</label>
          <select name="status" class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
            <option value="active" {{ $machine->status === 'active' ? 'selected' : '' }}>Active (Available for polling)</option>
            <option value="inactive" {{ $machine->status === 'inactive' ? 'selected' : '' }}>Inactive / Standby</option>
          </select>
        </div>
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
