<?php

namespace App\Http\Controllers;

use App\Models\Kegiatan;
use App\Models\MenuKegiatan;
use App\Models\RincianMenu;
use App\Services\ActivityLogger;
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

        ActivityLogger::log(
            event: 'create',
            module: 'kode_kegiatan',
            description: "Menambah menu kegiatan: {$menu->nama}",
            subject: $menu,
            properties: ['data' => $validated],
        );

        return response()->json(['success' => true, 'message' => 'Menu berhasil ditambahkan.', 'data' => $menu]);
    }

    public function updateMenu(Request $request, $id)
    {
        $menu = MenuKegiatan::findOrFail($id);
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'warna' => 'required|string|max:7',
        ]);
        $before = $menu->replicate()->setRawAttributes($menu->getOriginal());
        $menu->update($validated);

        ActivityLogger::log(
            event: 'update',
            module: 'kode_kegiatan',
            description: "Mengubah menu kegiatan: {$menu->nama}",
            subject: $menu,
            properties: ['changes' => ActivityLogger::diff($before, $menu)],
        );

        return response()->json(['success' => true, 'message' => 'Menu berhasil diperbarui.', 'data' => $menu]);
    }

    public function destroyMenu($id)
    {
        $menu = MenuKegiatan::findOrFail($id);
        $snapshot = ['id' => $menu->id, 'nama' => $menu->nama];
        $menu->delete();

        ActivityLogger::log(
            event: 'delete',
            module: 'kode_kegiatan',
            description: "Menghapus menu kegiatan: {$snapshot['nama']}",
            properties: ['data' => $snapshot],
        );

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

        ActivityLogger::log(
            event: 'create',
            module: 'kode_kegiatan',
            description: "Menambah rincian menu: {$rincian->nama}",
            subject: $rincian,
            properties: ['data' => $validated],
        );

        return response()->json(['success' => true, 'message' => 'Rincian menu berhasil ditambahkan.', 'data' => $rincian]);
    }

    public function updateRincian(Request $request, $id)
    {
        $rincian = RincianMenu::findOrFail($id);
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
        ]);
        $before = $rincian->replicate()->setRawAttributes($rincian->getOriginal());
        $rincian->update($validated);

        ActivityLogger::log(
            event: 'update',
            module: 'kode_kegiatan',
            description: "Mengubah rincian menu: {$rincian->nama}",
            subject: $rincian,
            properties: ['changes' => ActivityLogger::diff($before, $rincian)],
        );

        return response()->json(['success' => true, 'message' => 'Rincian menu berhasil diperbarui.', 'data' => $rincian]);
    }

    public function destroyRincian($id)
    {
        $rincian = RincianMenu::findOrFail($id);
        $snapshot = ['id' => $rincian->id, 'nama' => $rincian->nama];
        $rincian->delete();

        ActivityLogger::log(
            event: 'delete',
            module: 'kode_kegiatan',
            description: "Menghapus rincian menu: {$snapshot['nama']}",
            properties: ['data' => $snapshot],
        );

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

        ActivityLogger::log(
            event: 'create',
            module: 'kode_kegiatan',
            description: "Menambah kegiatan: " . ($kegiatan->kode ? "[{$kegiatan->kode}] " : "") . $kegiatan->nama,
            subject: $kegiatan,
            properties: ['data' => $validated],
        );

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
        $before = $kegiatan->replicate()->setRawAttributes($kegiatan->getOriginal());
        $kegiatan->update($validated);

        ActivityLogger::log(
            event: 'update',
            module: 'kode_kegiatan',
            description: "Mengubah kegiatan: " . ($kegiatan->kode ? "[{$kegiatan->kode}] " : "") . $kegiatan->nama,
            subject: $kegiatan,
            properties: ['changes' => ActivityLogger::diff($before, $kegiatan)],
        );

        return response()->json(['success' => true, 'message' => 'Kegiatan berhasil diperbarui.', 'data' => $kegiatan]);
    }

    public function destroyKegiatan($id)
    {
        $kegiatan = Kegiatan::findOrFail($id);
        $snapshot = ['id' => $kegiatan->id, 'kode' => $kegiatan->kode, 'nama' => $kegiatan->nama];
        $kegiatan->delete();

        ActivityLogger::log(
            event: 'delete',
            module: 'kode_kegiatan',
            description: "Menghapus kegiatan: " . ($snapshot['kode'] ? "[{$snapshot['kode']}] " : "") . $snapshot['nama'],
            properties: ['data' => $snapshot],
        );

        return response()->json(['success' => true, 'message' => 'Kegiatan berhasil dihapus.']);
    }

    /**
     * GET pemakai kode: list pegawai + tanggal yang pakai kegiatan ini.
     * Filter via query string ?bulan=5&tahun=2026
     */
    public function pemakai(Request $request, $id)
    {
        $kegiatan = Kegiatan::with('rincianMenu.menuKegiatan')->findOrFail($id);

        $bulan = (int) $request->query('bulan', now()->month);
        $tahun = (int) $request->query('tahun', now()->year);

        $namaHariMap = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $namaBulanMap = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        $records = \App\Models\PerjalananDinas::where('kegiatan_id', $id)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->with('user')
            ->orderBy('tanggal')
            ->get();

        // Group per pegawai
        $grouped = [];
        foreach ($records as $rec) {
            $userId = $rec->user_id;
            if (!isset($grouped[$userId])) {
                $grouped[$userId] = [
                    'user_id'    => $userId,
                    'nama'       => $rec->user->name ?? '-',
                    'jabatan'    => $rec->user->jabatan ?? '-',
                    'penempatan' => ucfirst($rec->user->penempatan ?? 'induk'),
                    'tanggal'    => [],
                ];
            }
            $tgl = $rec->tanggal;
            $grouped[$userId]['tanggal'][] = [
                'iso'      => $tgl->format('Y-m-d'),
                'display'  => $namaHariMap[$tgl->dayOfWeek] . ', ' . $tgl->day . ' ' . $namaBulanMap[$tgl->month] . ' ' . $tgl->year,
                'short'    => $tgl->format('d/m'),
                'hari'     => substr($namaHariMap[$tgl->dayOfWeek], 0, 3),
            ];
        }

        // Sort: pegawai dengan jumlah terbanyak di atas
        $list = collect(array_values($grouped))
            ->sortByDesc(fn($p) => count($p['tanggal']))
            ->values()
            ->map(function ($p) {
                $p['jumlah'] = count($p['tanggal']);
                return $p;
            })
            ->all();

        return response()->json([
            'success' => true,
            'kegiatan' => [
                'id'    => $kegiatan->id,
                'kode'  => $kegiatan->kode,
                'nama'  => $kegiatan->nama,
                'warna' => $kegiatan->rincianMenu->menuKegiatan->warna ?? '#6B7280',
            ],
            'bulan' => $bulan,
            'tahun' => $tahun,
            'total_pegawai'  => count($list),
            'total_tanggal'  => $records->count(),
            'pemakai'        => $list,
        ]);
    }
}
