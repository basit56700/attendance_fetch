@extends('layouts.app')

@section('title', 'Create User')
@section('page_title', 'Add System User')

@section('content')
<div class="max-w-xl mx-auto space-y-6">

  <div>
    <a href="{{ route('users.index') }}" class="text-xs text-blue-400 hover:text-blue-300 font-medium">&larr; Back to Users</a>
    <h2 class="text-xl font-bold text-white mt-1">Create System User</h2>
    <p class="text-xs text-slate-400 mt-0.5">Assign login credentials and permission tier.</p>
  </div>

  <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-xl">
    <form action="{{ route('users.store') }}" method="POST" class="space-y-4">
      @csrf

      <div>
        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Full Name *</label>
        <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. John Doe"
               class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
      </div>

      <div>
        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Email / Login ID *</label>
        <input type="email" name="email" value="{{ old('email') }}" required placeholder="e.g. john@example.com"
               class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
      </div>

      <div>
        <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Password *</label>
        <input type="password" name="password" required placeholder="Minimum 6 characters"
               class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Role Tier *</label>
          <select name="role" class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
            <option value="operator">Operator (Fetch & View)</option>
            <option value="admin">Administrator (Full Control)</option>
            <option value="hr">HR Manager</option>
            <option value="viewer">Viewer (Read-Only)</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-semibold uppercase text-slate-400 mb-1.5">Status *</label>
          <select name="status" class="w-full bg-slate-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-blue-500">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>

      <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-800">
        <a href="{{ route('users.index') }}" class="px-5 py-2.5 text-xs text-slate-400 hover:text-white transition">Cancel</a>
        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs px-6 py-2.5 rounded-xl transition shadow-lg shadow-blue-600/30">
          Create User
        </button>
      </div>
    </form>
  </div>

</div>
@endsection
