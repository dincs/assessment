<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($data, $request->boolean('remember'))) {
            $request->session()->regenerate();

            if ($request->wantsJson()) {
                return response()->noContent();
            }

            return redirect()->intended('/admin');
        }

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Invalid credentials'], 422);
        }

        return back()->withErrors(['email' => 'Invalid credentials'])->onlyInput('email');
    }


    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ✅ Return 204 for API/tests, redirect for normal web requests
        if ($request->wantsJson()) {
            return response()->noContent(); // 204
        }

        return redirect('/login'); // for browser use
    }
}
