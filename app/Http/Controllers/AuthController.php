<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

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
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = $credentials['login'];
        $password = $credentials['password'];
        $remember = $request->boolean('remember');

        if (
            Auth::attempt(['username' => $login, 'password' => $password], $remember)
            || Auth::attempt(['email' => $login, 'password' => $password], $remember)
        ) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['login' => 'Username, email, atau kata sandi salah.'])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function forgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $data = $request->validate(['login' => ['required', 'string', 'max:255']]);
        $user = User::query()
            ->where('username', $data['login'])
            ->orWhere('email', $data['login'])
            ->first();

        if ($user && blank($user->email)) {
            return back()
                ->withErrors(['login' => 'Akun ini belum memiliki alamat email. Segera hubungi admin untuk melengkapinya.'])
                ->onlyInput('login');
        }

        if ($user) {
            Password::sendResetLink(['email' => $user->email]);
        }

        return back()->with('success', 'Jika email terdaftar, tautan reset kata sandi telah dikirim.');
    }

    public function resetPasswordForm(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate(['token' => ['required'], 'email' => ['required', 'email'], 'password' => ['required', 'confirmed', 'min:8']]);
        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
        });

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', 'Kata sandi berhasil direset. Silakan masuk.')
            : back()->withErrors(['email' => __($status)]);
    }
}
