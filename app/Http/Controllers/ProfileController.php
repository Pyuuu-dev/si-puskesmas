<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function index()
    {
        return view('profile.index', ['user' => auth()->user()]);
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'email'               => 'required|email|unique:users,email,' . $user->id,
            'password'            => 'nullable|string|min:6|confirmed',
            'foto'                => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'foto_cropped'        => 'nullable|string', // base64 dari cropper
        ]);

        $user->name  = $validated['name'];
        $user->email = $validated['email'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        // Prioritas: foto_cropped (base64 dari cropper) > foto (file upload biasa)
        $fotoCropped = $request->input('foto_cropped');

        if ($fotoCropped === 'HAPUS') {
            // Hapus foto
            if ($user->foto && Storage::disk('public')->exists($user->foto)) {
                Storage::disk('public')->delete($user->foto);
            }
            $user->foto = null;
        } elseif (!empty($fotoCropped) && preg_match('/^data:image\/(jpeg|jpg|png);base64,/', $fotoCropped, $matches)) {
            $imageData = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $fotoCropped));

            if ($imageData !== false) {
                if ($user->foto && Storage::disk('public')->exists($user->foto)) {
                    Storage::disk('public')->delete($user->foto);
                }
                $ext      = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
                $filename = 'profile-photos/' . uniqid('foto_', true) . '.' . $ext;
                Storage::disk('public')->put($filename, $imageData);
                $user->foto = $filename;
            }
        } elseif ($request->hasFile('foto')) {
            if ($user->foto && Storage::disk('public')->exists($user->foto)) {
                Storage::disk('public')->delete($user->foto);
            }
            $path       = $request->file('foto')->store('profile-photos', 'public');
            $user->foto = $path;
        }

        $user->save();

        return redirect()->route('profile')->with('success', 'Profil berhasil diperbarui.');
    }
}
