@extends('layouts.app')

@section('title', 'Edit User: ' . $user->name)
@section('page_title', 'Edit User Credentials & Roles')

@section('content')
<div class="max-w-xl mx-auto space-y-6">

  <div>
    <a href="{{ route('users.index') }}" class="text-xs text-blue-400 hover:text-blue-300 font-medium">&larr; Back to Users</a>
    <h2 class="text-xl font-bold text-white mt-1">Edit User: {{ $user->name }}</h2>
    <p class="text-xs text-slate-400 mt-0.5">Update credentials or permission tier.</p>
  </div>

  <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl">
    <form action="{{ route('users.update', $user) }}" method="POST" class="space-y-4">
      @csrf
      @method('PUT')

      <div>
        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Full Name *</label>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" required
               class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
      </div>

      <div>
        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Email / Login ID *</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required
               class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
      </div>

      <div>
        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Reset Password (leave blank to keep current)</label>
        <input type="password" name="password" placeholder="••••••••"
               class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Role Tier *</label>
          <select name="role" class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
            <option value="operator" {{ $user->role === 'operator' ? 'selected' : '' }}>Operator (Fetch & View)</option>
            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Administrator (Full Control)</option>
            <option value="hr" {{ $user->role === 'hr' ? 'selected' : '' }}>HR Manager</option>
            <option value="viewer" {{ $user->role === 'viewer' ? 'selected' : '' }}>Viewer (Read-Only)</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Status *</label>
          <select name="status" class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
            <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ $user->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
          </select>
        </div>
      </div>

      <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800">
        <a href="{{ route('users.index') }}" class="px-5 py-2.5 text-xs text-slate-400 hover:text-white transition">Cancel</a>
        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs px-6 py-2.5 rounded-xl transition shadow-lg shadow-blue-600/30">
          Save User Changes
        </button>
      </div>
    </form>
  </div>

</div>
@endsection
