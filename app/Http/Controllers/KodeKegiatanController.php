<?php

namespace App\Http\Controllers;

use App\Models\Kegiatan;
use App\Models\MenuKegiatan;
use App\Models\RincianMenu;
use Illuminate\Http\Request;

class KodeKegiatanController extends Controller
{
    public function index()
    {
        $menuKegiatan = MenuKegiatan::with(['rincianMenu.kegiatan'])
            ->orderBy('urutan')
            ->orderBy('nama')
            ->get();

        return view('kode-kegiatan.index', compact('menuKegiatan'));
    }

    // ===== MENU (Level 1) =====
    public function storeMenu(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'warna' => 'required|string|max:7',
        ]);

        $menu = MenuKegiatan::create($validated);

        return response()->json(['success' => true, 'message' => 'Menu berhasil ditambahkan.', 'data' => $menu]);
    }

    public function updateMenu(Request $request, $id)
    {
        $menu = MenuKegiatan::findOrFail($id);
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'warna' => 'required|string|max:7',
        ]);
        $menu->update($validated);

        return response()->json(['success' => true, 'message' => 'Menu berhasil diperbarui.', 'data' => $menu]);
    }

    public function destroyMenu($id)
    {
        MenuKegiatan::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Menu berhasil dihapus.']);
    }

    // ===== RINCIAN MENU (Level 2) =====
    public function storeRincian(Request $request)
    {
        $validated = $request->validate([
            'menu_kegiatan_id' => 'required|exists:menu_kegiatan,id',
            'nama' => 'required|string|max:255',
        ]);

        $rincian = RincianMenu::create($validated);

        return response()->json(['success' => true, 'message' => 'Rincian menu berhasil ditambahkan.', 'data' => $rincian]);
    }

    public function updateRincian(Request $request, $id)
    {
        $rincian = RincianMenu::findOrFail($id);
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
        ]);
        $rincian->update($validated);

        return response()->json(['success' => true, 'message' => 'Rincian menu berhasil diperbarui.', 'data' => $rincian]);
    }

    public function destroyRincian($id)
    {
        RincianMenu::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Rincian menu berhasil dihapus.']);
    }

    // ===== KEGIATAN (Level 3) =====
    public function storeKegiatan(Request $request)
    {
        $validated = $request->validate([
            'rincian_menu_id' => 'required|exists:rincian_menu,id',
            'nama' => 'required|string|max:500',
            'kode' => 'nullable|string|max:30',
            'pemegang_program' => 'nullable|string|max:255',
            'anggaran' => 'nullable|numeric|min:0',
        ]);

        $kegiatan = Kegiatan::create($validated);

        return response()->json(['success' => true, 'message' => 'Kegiatan berhasil ditambahkan.', 'data' => $kegiatan]);
    }

    public function updateKegiatan(Request $request, $id)
    {
        $kegiatan = Kegiatan::findOrFail($id);
        $validated = $request->validate([
            'nama' => 'required|string|max:500',
            'kode' => 'nullable|string|max:30',
            'pemegang_program' => 'nullable|string|max:255',
            'anggaran' => 'nullable|numeric|min:0',
        ]);
        $kegiatan->update($validated);

        return response()->json(['success' => true, 'message' => 'Kegiatan berhasil diperbarui.', 'data' => $kegiatan]);
    }

    public function destroyKegiatan($id)
    {
        Kegiatan::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Kegiatan berhasil dihapus.']);
    }
}
