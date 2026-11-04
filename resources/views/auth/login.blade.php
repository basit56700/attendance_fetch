<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="color-scheme" content="light dark">
  <title>Sign In - GCS Attendance ERP</title>

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
  <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
  <script src="{{ asset('js/theme.js') }}" defer></script>
  <style>
    body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
  </style>
</head>
<body class="bg-slate-950 text-slate-100 flex items-center justify-center min-h-screen p-4 antialiased relative">
  <!-- Top Right Theme Switcher -->
  <div class="absolute top-6 right-6">
    <button type="button" onclick="toggleTheme()" class="theme-toggle-btn flex items-center space-x-2 text-xs bg-slate-900 hover:bg-slate-800 text-slate-300 px-3.5 py-2 rounded-xl border border-slate-800 shadow-lg transition cursor-pointer select-none" aria-label="Toggle light or dark theme" title="Toggle Light/Dark Theme">
      <span class="theme-sun-icon">
        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
      </span>
      <span class="theme-moon-icon hidden">
        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
      </span>
      <span class="theme-label font-medium">Light Mode</span>
    </button>
  </div>
  <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl relative">
    <div class="text-center mb-8">
      <div class="w-16 h-16 bg-blue-600/20 text-blue-500 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-blue-500/30 shadow-lg shadow-blue-600/20">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0v.5"></path>
        </svg>
      </div>
      <h2 class="text-2xl font-extrabold text-white tracking-tight">ZK Attendance <span class="text-blue-500">ERP</span></h2>
      <p class="text-xs text-slate-400 mt-1">Enterprise Biometric Device & Attendance System</p>
    </div>

    @if($errors->any())
      <div class="mb-5 bg-red-950/60 border border-red-800 text-red-300 p-3 rounded-xl text-xs space-y-1">
        @foreach($errors->all() as $error)
          <div>• {{ $error }}</div>
        @endforeach
      </div>
    @endif

    @if(session('success'))
      <div class="mb-5 bg-emerald-950/60 border border-emerald-800 text-emerald-300 p-3 rounded-xl text-xs">
        {{ session('success') }}
      </div>
    @endif

    <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
      @csrf
      <div>
        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Email / Username</label>
        <input type="text" name="email" value="{{ old('email', 'admin@local.com') }}" required autofocus
               class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
      </div>

      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label class="block text-xs font-semibold uppercase text-slate-400">Password</label>
        </div>
        <input type="password" name="password" value="admin123" required
               class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
      </div>

      <div class="flex items-center justify-between text-xs pt-1">
        <label class="flex items-center space-x-2 cursor-pointer text-slate-400 hover:text-slate-300">
          <input type="checkbox" name="remember" class="w-3.5 h-3.5 rounded text-blue-600 bg-slate-950 border-slate-700">
          <span>Remember me</span>
        </label>
      </div>

      <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-semibold py-2.5 px-4 rounded-xl text-xs transition duration-200 shadow-lg shadow-blue-600/30 flex items-center justify-center space-x-2">
        <span>Sign In to ERP Portal</span>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
      </button>

      <div class="pt-4 border-t border-slate-800 text-center">
        <div class="text-[11px] text-slate-500">
          Default Admin: <span class="text-slate-300 font-mono">admin@local.com</span> | Password: <span class="text-slate-300 font-mono">admin123</span>
        </div>
      </div>
    </form>
  </div>
</body>
</html>
