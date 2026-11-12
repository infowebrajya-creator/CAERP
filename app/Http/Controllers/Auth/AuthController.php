<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private readonly ActivityLogger $logger) {}

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended($this->redirectTarget(Auth::user()->role));
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Invalid email address or password.']);
        }

        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'Your account is currently deactivated. Contact your administrator.']);
        }

        $request->session()->regenerate();
        $this->logger->log('auth', 'login', "{$user->name} ({$user->roleLabel()}) signed in.");

        return redirect()->intended($this->redirectTarget($user->role));
    }

    public function logout(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            $this->logger->log('auth', 'logout', Auth::user()->name.' signed out.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been securely logged out.');
    }

    private function redirectTarget(string $role): string
    {
        return $role === 'client' ? route('portal.dashboard') : route('dashboard');
    }
}

