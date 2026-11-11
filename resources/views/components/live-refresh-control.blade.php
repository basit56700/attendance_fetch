@props([
    'selectId' => 'liveRefreshInterval',
    'dotId' => 'liveRefreshDot',
    'statusId' => 'liveRefreshStatus',
])

<div class="live-refresh-pill inline-flex items-center space-x-2 bg-slate-950/80 border border-slate-800 rounded-xl px-2.5 py-1.5 text-[11px]">
  <span id="{{ $dotId }}" class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse inline-block shrink-0"></span>
  <span class="text-slate-400 font-medium hidden sm:inline">Auto Update:</span>
  <select id="{{ $selectId }}" aria-label="Auto refresh duration" class="bg-slate-900 border border-slate-700/80 rounded-lg px-2 py-0.5 text-[11px] font-semibold text-white cursor-pointer focus:outline-none">
    <option value="0">Off</option>
    <option value="5">Every 5s</option>
    <option value="10">Every 10s</option>
    <option value="30">Every 30s</option>
    <option value="60">Every 60s</option>
    <option value="custom">Custom...</option>
  </select>
  <span id="{{ $statusId }}" class="text-[10px] font-mono text-slate-500 hidden md:inline">Live</span>
</div>
