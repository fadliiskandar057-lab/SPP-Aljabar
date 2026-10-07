<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'nip' => [$user->role === 'kepala_sekolah' || $user->role === 'wali_kelas' ? 'required' : 'nullable', 'string', 'max:50'],
        ]);
        if (! in_array($user->role, ['kepala_sekolah', 'wali_kelas'], true)) unset($data['nip']);
        $user->update($data);
        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function password(Request $request)
    {
        $data = $request->validate(['current_password' => ['required', 'current_password'], 'password' => ['required', 'confirmed', 'min:8']]);
        $request->user()->update(['password' => Hash::make($data['password'])]);
        $request->session()->regenerate();
        return back()->with('success', 'Kata sandi berhasil diganti.');
    }
}
