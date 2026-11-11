<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="color-scheme" content="light dark">
  <title>@yield('title', 'Dashboard') - {{ config('app.name', 'GCS Attendance ERP') }}</title>
  <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
  
  <!-- Early FOUC Prevention Script -->
  <script>
    (function() {
      try {
        const saved = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const isDark = saved ? (saved === 'dark') : prefersDark;
        document.documentElement.classList.toggle('dark', isDark);
        document.documentElement.classList.toggle('light', !isDark);
        const meta = document.querySelector('meta[name="color-scheme"]');
        if (meta) meta.content = isDark ? 'dark' : 'light';
      } catch (e) {}
    })();
  </script>

  <script src="{{ asset('js/tailwind.js') }}"></script>
  <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ file_exists(public_path('css/theme.css')) ? filemtime(public_path('css/theme.css')) : time() }}">
  <script src="{{ asset('js/theme.js') }}?v={{ file_exists(public_path('js/theme.js')) ? filemtime(public_path('js/theme.js')) : time() }}" defer></script>
  <style>
    body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
  </style>
</head>
<body class="bg-slate-950 text-slate-100 antialiased min-h-screen flex">

  <!-- ========================================== -->
  <!-- SIDEBAR NAVIGATION -->
  <!-- ========================================== -->
  <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col shrink-0 hidden md:flex">
    <!-- Brand Header -->
    <div class="p-4 border-b border-slate-800/80 bg-slate-900/40">
      <a href="{{ route('dashboard') }}" class="flex items-center justify-center group py-1.5">
        <img id="sidebar-brand-logo"
             src="{{ asset('images/gcs-logo.png') }}" 
             alt="Global Connect Synergy" 
             class="h-20 w-auto max-w-[190px] object-contain brightness-0 invert opacity-95 group-hover:opacity-100 transition duration-200">
      </a>
      <div class="mt-2.5 flex items-center justify-between px-1">
        <div class="text-xs font-bold text-white tracking-tight flex items-center space-x-1.5">
          <span class="w-2 h-2 rounded-full bg-red-500 inline-block animate-pulse"></span>
          <span>Attendance <span class="text-red-500 font-extrabold">ERP</span></span>
        </div>
        <div class="text-[10px] text-slate-400 font-mono font-medium bg-slate-800 px-2 py-0.5 rounded-md border border-slate-700/60">Enterprise v2.0</div>
      </div>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 px-4 py-5 space-y-1.5 overflow-y-auto">
      <div class="text-[10px] uppercase font-semibold text-slate-500 px-3 pb-1 tracking-wider">Main</div>

      <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition {{ request()->routeIs('dashboard') ? 'nav-active-pill font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
        <span>Dashboard</span>
      </a>

      <div class="text-[10px] uppercase font-semibold text-slate-500 px-3 pt-4 pb-1 tracking-wider">Biometrics</div>

      <a href="{{ route('machines.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition {{ request()->routeIs('machines.index') ? 'nav-active-pill font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
        <span>Machines Hub</span>
      </a>

      <a href="{{ route('machines.create') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition {{ request()->routeIs('machines.create') ? 'nav-active-pill font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span>Add & Diagnose</span>
      </a>

      <a href="{{ route('attendance.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition {{ request()->routeIs('attendance.*') ? 'nav-active-pill font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
        <span>Attendance Logs</span>
      </a>

      <div class="text-[10px] uppercase font-semibold text-slate-500 px-3 pt-4 pb-1 tracking-wider">Administration</div>

      @if(auth()->user()->isHr())
      <a href="{{ route('users.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition {{ request()->routeIs('users.*') ? 'nav-active-pill font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
        <span>User Management</span>
      </a>
      @endif

      <a href="{{ route('audit-logs.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition {{ request()->routeIs('audit-logs.*') ? 'nav-active-pill font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
        <span>Audit Trail Logs</span>
      </a>

      <a href="{{ route('settings.index') }}" class="flex items-center space-x-3 px-3.5 py-2.5 rounded-xl text-xs font-medium transition {{ request()->routeIs('settings.*') ? 'nav-active-pill font-semibold' : 'text-slate-400 hover:text-white hover:bg-slate-800/60' }}">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
        <span>ERP Settings</span>
      </a>
    </nav>

    <!-- User Profile Footer in Sidebar -->
    <div class="p-4 border-t border-slate-800 flex items-center justify-between">
      <a href="{{ route('profile.show') }}" class="flex items-center space-x-3 overflow-hidden group">
        <div class="w-8 h-8 rounded-xl bg-red-600/20 text-red-400 border border-red-500/30 flex items-center justify-center font-bold text-xs shrink-0 group-hover:border-red-400 transition">
          {{ substr(auth()->user()->name, 0, 1) }}
        </div>
        <div class="truncate">
          <div class="text-xs font-semibold text-white group-hover:text-red-400 transition truncate">{{ auth()->user()->name }}</div>
          <div class="text-[10px] text-slate-500 uppercase font-mono">{{ auth()->user()->role }}</div>
        </div>
      </a>
      <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="p-1.5 text-slate-400 hover:text-red-400 hover:bg-slate-800 rounded-lg transition" title="Log Out">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
        </button>
      </form>
    </div>
  </aside>

  <!-- ========================================== -->
  <!-- MAIN CONTENT WRAPPER -->
  <!-- ========================================== -->
  <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
    <!-- Top Header -->
    <header class="h-16 bg-slate-900/60 border-b border-slate-800/80 sticky top-0 z-20 backdrop-blur-md px-6 flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <!-- Mobile Logo Icon -->
        <div class="md:hidden flex items-center shrink-0">
          <a href="{{ route('dashboard') }}" class="group flex items-center">
            <img id="mobile-brand-logo"
                 src="{{ asset('images/gcs-icon.png') }}" 
                 alt="GCS" 
                 class="w-7 h-7 object-contain brightness-0 invert opacity-95 group-hover:opacity-100 transition duration-200">
          </a>
        </div>
        <h1 class="text-base font-bold text-white tracking-tight">@yield('page_title', 'Attendance Management')</h1>
        <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-medium hidden sm:inline-flex items-center space-x-1">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
          <span>System Online</span>
        </span>
      </div>

      <div class="flex items-center space-x-3">
        <!-- Theme Toggle Button -->
        <button type="button" onclick="toggleTheme()" class="theme-toggle-btn flex items-center space-x-1.5 text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-3 py-1.5 rounded-lg border border-slate-700 transition cursor-pointer select-none" aria-label="Toggle light or dark theme" title="Toggle Light/Dark Theme">
          <span class="theme-sun-icon">
            <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
          </span>
          <span class="theme-moon-icon hidden">
            <svg class="w-3.5 h-3.5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
          </span>
          <span class="theme-label font-medium hidden sm:inline">Light Mode</span>
        </button>

        <a href="{{ route('api.attendance') }}" target="_blank" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 font-mono px-3 py-1.5 rounded-lg border border-slate-700 transition flex items-center space-x-1.5">
          <span class="w-2 h-2 rounded-full bg-red-500"></span>
          <span>REST API / JSON</span>
        </a>
        <div class="text-xs text-slate-400 font-mono hidden sm:block" id="systemClock">
          {{ now()->format('Y-m-d H:i:s') }}
        </div>
      </div>
    </header>

    <!-- Flash Notifications -->
    <div class="px-6 pt-4">
      @if(session('success'))
        <div class="bg-emerald-950/60 border border-emerald-800 text-emerald-300 px-4 py-3 rounded-xl text-xs flex items-center justify-between mb-2">
          <div class="flex items-center space-x-2">
            <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('success') }}</span>
          </div>
          <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-white">✕</button>
        </div>
      @endif

      @if(session('error'))
        <div class="bg-red-950/60 border border-red-800 text-red-300 px-4 py-3 rounded-xl text-xs flex items-center justify-between mb-2">
          <div class="flex items-center space-x-2">
            <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            <span>{{ session('error') }}</span>
          </div>
          <button onclick="this.parentElement.remove()" class="text-red-400 hover:text-white">✕</button>
        </div>
      @endif

      @if(session('warning'))
        <div class="bg-amber-950/60 border border-amber-800 text-amber-300 px-4 py-3 rounded-xl text-xs flex items-center justify-between mb-2">
          <div class="flex items-center space-x-2">
            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <span>{{ session('warning') }}</span>
          </div>
          <button onclick="this.parentElement.remove()" class="text-amber-400 hover:text-white">✕</button>
        </div>
      @endif

      @if(session('info'))
        <div class="bg-blue-950/60 border border-blue-800 text-blue-200 px-4 py-3 rounded-xl text-xs flex items-center justify-between mb-2">
          <div class="flex items-center space-x-2">
            <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>{{ session('info') }}</span>
          </div>
          <button onclick="this.parentElement.remove()" class="text-blue-400 hover:text-white">✕</button>
        </div>
      @endif
    </div>

    <!-- Page Content Container -->
    <main class="flex-1 px-6 py-4">
      @yield('content')
    </main>
  </div>

  <script>
    // Live Clock Update
    setInterval(() => {
      const now = new Date();
      const el = document.getElementById('systemClock');
      if (el) el.textContent = now.getFullYear() + '-' +
        String(now.getMonth()+1).padStart(2,'0') + '-' +
        String(now.getDate()).padStart(2,'0') + ' ' +
        String(now.getHours()).padStart(2,'0') + ':' +
        String(now.getMinutes()).padStart(2,'0') + ':' +
        String(now.getSeconds()).padStart(2,'0');
    }, 1000);
  </script>

  @stack('scripts')
</body>
</html>
