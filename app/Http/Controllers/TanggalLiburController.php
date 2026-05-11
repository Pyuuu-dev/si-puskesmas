<?php

namespace App\Http\Controllers;

use App\Models\InfoTanggal;
use App\Models\TanggalLibur;
use Illuminate\Http\Request;

class TanggalLiburController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'is_libur' => 'required|boolean',
            'keterangan' => 'nullable|string|max:255',
            'catatan' => 'nullable|string|max:255',
        ]);

        $tanggal = date('Y-m-d', strtotime($validated['tanggal']));

        // Check if exists first, then update or create
        $record = TanggalLibur::whereDate('tanggal', $tanggal)->first();

        if ($record) {
            $record->update([
                'is_libur' => $validated['is_libur'],
                'keterangan' => $validated['keterangan'] ?? null,
                'catatan' => $validated['catatan'] ?? null,
            ]);
        } else {
            $record = TanggalLibur::create([
                'tanggal' => $tanggal,
                'is_libur' => $validated['is_libur'],
                'keterangan' => $validated['keterangan'] ?? null,
                'catatan' => $validated['catatan'] ?? null,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Data tanggal berhasil disimpan.', 'data' => $record]);
    }

    public function destroyByTanggal(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
        ]);

        $deleted = TanggalLibur::whereDate('tanggal', $validated['tanggal'])->delete();

        if (!$deleted) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
        }

        return response()->json(['success' => true, 'message' => 'Data tanggal berhasil dihapus.']);
    }

    public function destroy(Request $request, $id = null)
    {
        if ($id) {
            $record = TanggalLibur::findOrFail($id);
            $record->delete();
        } else {
            $validated = $request->validate([
                'tanggal' => 'required|date',
            ]);
            $deleted = TanggalLibur::whereDate('tanggal', $validated['tanggal'])->delete();
            if (!$deleted) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }
        }

        return response()->json(['success' => true, 'message' => 'Data tanggal berhasil dihapus.']);
    }

    public function storeInfo(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'lokasi' => 'nullable|string|max:255',
            'catatan' => 'nullable|string|max:255',
        ]);

        $tanggal = date('Y-m-d', strtotime($validated['tanggal']));

        $record = InfoTanggal::create([
            'tanggal' => $tanggal,
            'lokasi' => $validated['lokasi'] ?? null,
            'catatan' => $validated['catatan'] ?? null,
        ]);

        return response()->json(['success' => true, 'message' => 'Info lokasi berhasil ditambahkan.', 'data' => $record]);
    }

    public function destroyInfo(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:info_tanggal,id',
        ]);

        $info = InfoTanggal::findOrFail($validated['id']);
        $info->delete();

        return response()->json(['success' => true, 'message' => 'Info lokasi berhasil dihapus.']);
    }
}
