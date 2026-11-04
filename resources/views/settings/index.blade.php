@extends('layouts.app')

@section('title', 'ERP Settings')
@section('page_title', 'ERP System Configuration')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

  <div>
    <h2 class="text-xl font-bold text-white">System Settings & Automation</h2>
    <p class="text-xs text-slate-400 mt-0.5">Configure attendance rules, auto-polling intervals, and device memory safeguards.</p>
  </div>

  <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl">
    <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
      @csrf

      <div>
        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">ERP Brand / Application Title</label>
        <input type="text" name="app_title" value="{{ old('app_title', $settings['app_title'] ?? 'ZK Attendance & HRMS ERP') }}" required
               class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
      </div>

      <div class="p-4 bg-slate-950/70 border border-slate-800 rounded-2xl space-y-4">
        <h3 class="text-xs font-bold text-blue-400 uppercase tracking-wider">Automated Background Poller</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <label class="flex items-center space-x-3 cursor-pointer">
            <input type="hidden" name="auto_fetch_enabled" value="0">
            <input type="checkbox" name="auto_fetch_enabled" value="1" {{ ($settings['auto_fetch_enabled'] ?? '0') === '1' ? 'checked' : '' }}
                   class="w-4 h-4 rounded text-blue-600 bg-slate-950 border-slate-700">
            <span class="text-xs text-slate-300 font-medium">Enable Scheduled Auto-Polling</span>
          </label>

          <div class="flex items-center space-x-2">
            <label class="text-xs text-slate-400">Polling Interval:</label>
            <input type="number" name="auto_fetch_interval" value="{{ old('auto_fetch_interval', $settings['auto_fetch_interval'] ?? '15') }}" min="1"
                   class="w-20 bg-slate-950 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white font-mono">
            <span class="text-xs text-slate-500">Minutes</span>
          </div>
        </div>
      </div>

      <div class="p-5 bg-slate-950/70 border border-slate-800 rounded-2xl space-y-4">
        <div>
          <h3 class="text-xs font-bold text-amber-400 uppercase tracking-wider">Device Memory Policy & Log Retention</h3>
          <p class="text-[11px] text-slate-400 mt-1">Control what happens to attendance records stored on physical ZKTeco machines after the ERP successfully downloads and saves them.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
          <label class="relative flex flex-col p-4 rounded-2xl border cursor-pointer transition select-none {{ ($settings['clear_device_logs'] ?? '0') === '0' ? 'bg-blue-950/30 border-blue-500/50 ring-1 ring-blue-500/30' : 'bg-slate-900/50 border-slate-800 hover:border-slate-700' }}">
            <div class="flex items-start justify-between">
              <div class="flex items-center space-x-2.5">
                <input type="radio" name="clear_device_logs" value="0" {{ ($settings['clear_device_logs'] ?? '0') === '0' ? 'checked' : '' }}
                       class="text-blue-600 bg-slate-950 border-slate-700 focus:ring-blue-500 h-4 w-4">
                <span class="text-xs font-semibold text-white">Keep Logs on Device</span>
              </div>
              <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded-md bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">Recommended</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-2.5 pl-6.5 leading-relaxed">
              Attendance records remain safely saved in the device's internal memory. The ERP's smart duplicate suppression guarantees identical punches are never recorded twice.
            </p>
          </label>

          <label class="relative flex flex-col p-4 rounded-2xl border cursor-pointer transition select-none {{ ($settings['clear_device_logs'] ?? '1') === '1' && ($settings['clear_device_logs'] ?? '0') === '1' ? 'bg-amber-950/30 border-amber-500/50 ring-1 ring-amber-500/30' : 'bg-slate-900/50 border-slate-800 hover:border-slate-700' }}">
            <div class="flex items-start justify-between">
              <div class="flex items-center space-x-2.5">
                <input type="radio" name="clear_device_logs" value="1" {{ ($settings['clear_device_logs'] ?? '0') === '1' ? 'checked' : '' }}
                       class="text-amber-500 bg-slate-950 border-slate-700 focus:ring-amber-500 h-4 w-4">
                <span class="text-xs font-semibold text-white">Wipe Device Memory After Fetch</span>
              </div>
              <span class="px-2 py-0.5 text-[9px] font-bold uppercase rounded-md bg-amber-500/10 text-amber-400 border border-amber-500/30">Auto-Purge</span>
            </div>
            <p class="text-[11px] text-slate-400 mt-2.5 pl-6.5 leading-relaxed">
              Sends an automated purge command to the ZKTeco hardware once punches are securely stored in the ERP database. Prevents "Memory Full" lockups on high-traffic or legacy machines.
            </p>
          </label>
        </div>
      </div>

      <div class="p-5 bg-slate-950/70 border border-slate-800 rounded-2xl space-y-4">
        <div>
          <h3 class="text-xs font-bold text-sky-400 uppercase tracking-wider">Interface & Visual Theme</h3>
          <p class="text-[11px] text-slate-400 mt-1">Select your preferred portal color theme. You can also switch themes instantly anytime via the toggle button in the top navigation bar.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
          <button type="button" onclick="localStorage.setItem('theme', 'dark'); applyTheme('dark');"
                  class="flex items-center justify-between p-4 rounded-2xl border border-slate-800 bg-slate-900/60 hover:border-slate-700 transition text-left cursor-pointer">
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 rounded-xl bg-slate-800 flex items-center justify-center text-amber-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
              </div>
              <div>
                <div class="text-xs font-semibold text-white">Dark Theme</div>
                <div class="text-[10px] text-slate-400">High-contrast slate dark appearance</div>
              </div>
            </div>
            <span class="text-xs text-blue-400 font-medium">Switch →</span>
          </button>

          <button type="button" onclick="localStorage.setItem('theme', 'light'); applyTheme('light');"
                  class="flex items-center justify-between p-4 rounded-2xl border border-slate-800 bg-slate-900/60 hover:border-slate-700 transition text-left cursor-pointer">
            <div class="flex items-center space-x-3">
              <div class="w-8 h-8 rounded-xl bg-amber-500/10 flex items-center justify-center text-amber-500 border border-amber-500/20">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
              </div>
              <div>
                <div class="text-xs font-semibold text-white">Light Theme</div>
                <div class="text-[10px] text-slate-400">Clean, crisp modern enterprise styling</div>
              </div>
            </div>
            <span class="text-xs text-blue-400 font-medium">Switch →</span>
          </button>
        </div>
      </div>

      <div class="p-4 bg-slate-950/70 border border-slate-800 rounded-2xl space-y-4">
        <h3 class="text-xs font-bold text-purple-400 uppercase tracking-wider">Attendance Rules</h3>
        <div>
          <label class="block text-xs text-slate-400 mb-1">Late Mark Grace Period (Minutes)</label>
          <input type="number" name="grace_period_minutes" value="{{ old('grace_period_minutes', $settings['grace_period_minutes'] ?? '15') }}"
                 class="w-full md:w-48 bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white font-mono">
          <span class="text-[10px] text-slate-500 mt-1 block">Punches within this grace period are recorded as standard Check-In.</span>
        </div>
      </div>

      <div class="flex justify-end pt-2">
        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs px-6 py-2.5 rounded-xl transition shadow-lg shadow-blue-600/30">
          Save Settings
        </button>
      </div>
    </form>
  </div>

</div>
@endsection
