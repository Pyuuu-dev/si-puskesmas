<?php

namespace App\Http\Controllers;

use App\Models\Changelog;
use Illuminate\Http\Request;

class ChangelogController extends Controller
{
    public function index()
    {
        $changelogs = Changelog::orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn($c) => $c->tanggal->format('Y-m-d'));

        return view('changelog.index', compact('changelogs'));
    }

    public function store(Request $request)
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'tanggal'    => 'required|date',
            'versi'      => 'nullable|string|max:20',
            'tipe'       => 'required|in:tambah,update,fix,hapus,lainnya',
            'judul'      => 'required|string|max:255',
            'deskripsi'  => 'nullable|string|max:2000',
        ]);

        $changelog = Changelog::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Entry changelog berhasil ditambahkan.',
            'data'    => $changelog,
        ]);
    }

    public function update(Request $request, Changelog $changelog)
    {
        $this->authorizeSuperAdmin();

        $validated = $request->validate([
            'tanggal'    => 'required|date',
            'versi'      => 'nullable|string|max:20',
            'tipe'       => 'required|in:tambah,update,fix,hapus,lainnya',
            'judul'      => 'required|string|max:255',
            'deskripsi'  => 'nullable|string|max:2000',
        ]);

        $changelog->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Entry changelog berhasil diperbarui.',
            'data'    => $changelog,
        ]);
    }

    public function destroy(Changelog $changelog)
    {
        $this->authorizeSuperAdmin();

        $changelog->delete();

        return response()->json([
            'success' => true,
            'message' => 'Entry changelog berhasil dihapus.',
        ]);
    }

    private function authorizeSuperAdmin(): void
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Hanya super admin yang dapat mengelola changelog.');
        }
    }
}
