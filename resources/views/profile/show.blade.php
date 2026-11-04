@extends('layouts.app')

@section('title', 'My Profile')
@section('page_title', 'Account Settings & Security')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

  <div>
    <h2 class="text-xl font-bold text-white">My Profile & Security</h2>
    <p class="text-xs text-slate-400 mt-0.5">Manage your personal account credentials and security preferences.</p>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <!-- Profile Details Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-5">
      <div class="flex items-center space-x-3 pb-3 border-b border-slate-800">
        <div class="w-10 h-10 rounded-2xl bg-blue-600/20 text-blue-400 border border-blue-500/30 flex items-center justify-center font-bold text-sm">
          {{ substr($user->name, 0, 1) }}
        </div>
        <div>
          <h3 class="text-sm font-bold text-white">{{ $user->name }}</h3>
          <span class="text-[10px] uppercase font-mono font-bold text-blue-400">{{ $user->role }}</span>
        </div>
      </div>

      <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Full Name</label>
          <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                 class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Email Address</label>
          <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                 class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
        </div>

        <div class="pt-2">
          <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs px-5 py-2.5 rounded-xl transition shadow-sm">
            Save Profile
          </button>
        </div>
      </form>
    </div>

    <!-- Password Change Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-sm space-y-5">
      <div class="pb-3 border-b border-slate-800">
        <h3 class="text-sm font-bold text-white">Change Account Password</h3>
        <p class="text-xs text-slate-400 mt-0.5">Ensure your account is protected with a strong password.</p>
      </div>

      <form action="{{ route('profile.password') }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Current Password</label>
          <input type="password" name="current_password" required placeholder="••••••••"
                 class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">New Password (Min 6)</label>
          <input type="password" name="password" required placeholder="••••••••"
                 class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Confirm New Password</label>
          <input type="password" name="password_confirmation" required placeholder="••••••••"
                 class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
        </div>

        <div class="pt-2">
          <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs px-5 py-2.5 rounded-xl transition border border-slate-700">
            Update Password
          </button>
        </div>
      </form>
    </div>

  </div>

  <!-- Recent Personal Activity Timeline -->
  <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-sm">
    <h3 class="text-sm font-bold text-white mb-4">My Recent Activity Log</h3>
    <div class="space-y-3">
      @forelse($recentActivity as $act)
        <div class="flex items-center justify-between text-xs py-2 border-b border-slate-800/60 last:border-none">
          <div class="flex items-center space-x-2">
            <span class="px-2 py-0.5 rounded font-mono font-bold text-[10px] uppercase {{ $act->status === 'success' ? 'bg-blue-500/10 text-blue-400' : 'bg-red-500/10 text-red-400' }}">
              {{ $act->action }}
            </span>
            <span class="text-slate-300">{{ $act->details }}</span>
          </div>
          <span class="text-slate-500 font-mono text-[11px]">{{ $act->created_at->diffForHumans() }}</span>
        </div>
      @empty
        <div class="text-xs text-slate-500 text-center py-4">No recent activity found.</div>
      @endforelse
    </div>
  </div>

</div>
@endsection
