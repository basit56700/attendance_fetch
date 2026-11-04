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

        // Support login by either email or username
        $user = User::where('email', $credentials['email'])->first();

        if ($user && $user->status !== 'active') {
            AuditLog::log('LOGIN_BLOCKED', "Disabled user attempted login: {$credentials['email']}", 'failed');

            return back()->withErrors(['email' => 'This account is deactivated. Please contact an administrator.'])->withInput();
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            /** @var User $authUser */
            $authUser = Auth::user();
            $authUser->update(['last_login_at' => now()]);

            AuditLog::log('USER_LOGIN', "User {$authUser->name} logged in successfully.", 'success');

            return redirect()->intended(route('dashboard'));
        }

        AuditLog::log('LOGIN_FAILED', "Failed login attempt for: {$credentials['email']}", 'failed');

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
