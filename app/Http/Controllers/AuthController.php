<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginInput = trim($credentials['email']);

        // Support login by email, username, or 'admin'
        $user = User::where('email', $loginInput)
            ->orWhere('name', $loginInput)
            ->orWhereRaw('LOWER(email) = ?', [strtolower($loginInput)])
            ->orWhereRaw('LOWER(name) = ?', [strtolower($loginInput)])
            ->first();

        if (! $user && (strtolower($loginInput) === 'admin' || strtolower($loginInput) === 'admin@local.com')) {
            $user = User::where('role', 'admin')->first();
        }

        if ($user && $user->status !== 'active') {
            AuditLog::log('LOGIN_BLOCKED', "Disabled user attempted login: {$loginInput}", 'failed');

            return back()->withErrors(['email' => 'This account is deactivated. Please contact an administrator.'])->withInput();
        }

        $authCredentials = [
            'email' => $user ? $user->email : $loginInput,
            'password' => $credentials['password'],
        ];

        if (Auth::attempt($authCredentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            /** @var User $authUser */
            $authUser = Auth::user();
            $authUser->update(['last_login_at' => now()]);

            AuditLog::log('USER_LOGIN', "User {$authUser->name} logged in successfully.", 'success');

            return redirect()->route('dashboard');
        }

        AuditLog::log('LOGIN_FAILED', "Failed login attempt for: {$loginInput}", 'failed');

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->withInput();
    }

    public function logout(Request $request)
    {
        $userName = Auth::user()?->name ?? 'User';
        AuditLog::log('USER_LOGOUT', "User {$userName} logged out.", 'info');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }
}
