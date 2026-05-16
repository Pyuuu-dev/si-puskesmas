<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        // Generate math captcha
        $a = rand(1, 9);
        $b = rand(1, 9);
        session(['captcha_answer' => $a + $b, 'captcha_q' => $a . ' + ' . $b]);

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        // Rate limiting: maks 5 percobaan per menit per IP
        $throttleKey = 'login.' . $request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ])->onlyInput('email');
        }

        // Validasi captcha
        $captchaAnswer = session('captcha_answer');
        $userAnswer    = (int) $request->input('captcha');

        if ($captchaAnswer === null || $userAnswer !== $captchaAnswer) {
            RateLimiter::hit($throttleKey, 60);
            // Regenerate captcha
            $a = rand(1, 9);
            $b = rand(1, 9);
            session(['captcha_answer' => $a + $b, 'captcha_q' => "{$a} + {$b}"]);

            return back()->withErrors([
                'captcha' => 'Jawaban captcha salah.',
            ])->onlyInput('email');
        }

        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            session()->forget(['captcha_answer', 'captcha_q']);
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        // Gagal login — tambah hit rate limiter
        RateLimiter::hit($throttleKey, 60);

        // Regenerate captcha setelah gagal
        $a = rand(1, 9);
        $b = rand(1, 9);
        session(['captcha_answer' => $a + $b, 'captcha_q' => "{$a} + {$b}"]);

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
