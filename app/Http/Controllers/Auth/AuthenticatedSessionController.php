<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Authenticated',
                    'user' => Auth::user(),
                ], 200);
            }

            return redirect()->intended(route('dashboard'));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'errors' => [
                    'email' => __('auth.failed'),
                ],
            ], 422);
        }

        return back()->withErrors([
            'email' => __('auth.failed'),
        ])->onlyInput('email');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Logged out',
            ], 200);
        }

        return redirect()->route('login');
    }
}
