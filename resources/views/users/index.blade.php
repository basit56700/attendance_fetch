@extends('layouts.app')

@section('title', 'User Management')
@section('page_title', 'System User Management & Access Control')

@section('content')
<div class="space-y-6">

  <!-- Header -->
  <div class="flex items-center justify-between">
    <div>
      <h2 class="text-lg font-bold text-white">System Users & Roles</h2>
      <p class="text-xs text-slate-400 mt-0.5">Manage staff access permissions, role tiers and account status.</p>
    </div>
    <a href="{{ route('users.create') }}" class="bg-red-600 hover:bg-red-500 text-white font-semibold text-xs px-4 py-2.5 rounded-xl transition shadow-lg shadow-red-600/20 flex items-center space-x-1.5 cursor-pointer">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
      <span>Create New User</span>
    </a>
  </div>

  <!-- Users Table -->
  <div class="bg-slate-900 border border-slate-800 rounded-3xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs text-slate-300">
        <thead class="uppercase bg-slate-950/70 text-slate-400 border-b border-slate-800">
          <tr>
            <th class="py-3 px-4">User</th>
            <th class="py-3 px-4">Email Address</th>
            <th class="py-3 px-4">Role Tier</th>
            <th class="py-3 px-4">Status</th>
            <th class="py-3 px-4">Last Login</th>
            <th class="py-3 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800/60">
          @foreach($users as $user)
            <tr class="hover:bg-slate-800/40 transition">
              <td class="py-3 px-4">
                <div class="flex items-center space-x-3">
                  <div class="w-8 h-8 rounded-xl bg-red-600/20 text-red-400 border border-red-500/30 flex items-center justify-center font-bold text-xs">
                    {{ substr($user->name, 0, 1) }}
                  </div>
                  <div>
                    <div class="font-bold text-white">{{ $user->name }}</div>
                    <div class="text-[10px] text-slate-500">Created {{ $user->created_at->format('M d, Y') }}</div>
                  </div>
                </div>
              </td>
              <td class="py-3 px-4 font-mono text-slate-300">{{ $user->email }}</td>
              <td class="py-3 px-4">
                @php
                  $roleBadge = match($user->role) {
                    'admin' => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
                    'hr' => 'bg-red-500/10 text-red-400 border-red-500/20',
                    'operator' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                    default => 'bg-slate-800 text-slate-400 border-slate-700',
                  };
                @endphp
                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border uppercase {{ $roleBadge }}">
                  {{ $user->role }}
                </span>
              </td>
              <td class="py-3 px-4">
                <form action="{{ route('users.toggle-status', $user) }}" method="POST">
                  @csrf
                  <button type="submit" class="px-2 py-0.5 rounded-full text-[10px] font-semibold border transition {{ $user->status === 'active' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20 hover:bg-emerald-500/20' : 'bg-red-500/10 text-red-400 border-red-500/20 hover:bg-red-500/20' }}">
                    {{ strtoupper($user->status) }}
                  </button>
                </form>
              </td>
              <td class="py-3 px-4 font-mono text-slate-400">
                {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Never' }}
              </td>
              <td class="py-3 px-4 text-right">
                <div class="flex items-center justify-end space-x-2">
                  <a href="{{ route('users.edit', $user) }}" class="text-slate-400 hover:text-red-400 p-1.5 hover:bg-slate-800 rounded-lg transition" title="Edit User">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                  </a>
                  @if($user->id !== auth()->id())
                    <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Permanently delete {{ $user->name }}?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="text-slate-400 hover:text-red-400 p-1.5 hover:bg-slate-800 rounded-lg transition" title="Delete User">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    @if($users->hasPages())
      <div class="px-6 py-4 border-t border-slate-800 bg-slate-950/40">
        {{ $users->links() }}
      </div>
    @endif
  </div>

</div>
@endsection
