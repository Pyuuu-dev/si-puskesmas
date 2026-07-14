<?php

namespace App\Http\Controllers;

use App\Models\InfoTanggal;
use App\Models\TanggalLibur;
use App\Services\ActivityLogger;
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
            $eventType = 'update';
        } else {
            $record = TanggalLibur::create([
                'tanggal' => $tanggal,
                'is_libur' => $validated['is_libur'],
                'keterangan' => $validated['keterangan'] ?? null,
                'catatan' => $validated['catatan'] ?? null,
            ]);
            $eventType = 'create';
        }

        $statusLabel = $validated['is_libur'] ? 'libur' : 'kerja';
        ActivityLogger::log(
            event: $eventType,
            module: 'tanggal_libur',
            description: ($eventType === 'create' ? "Menambah" : "Mengubah") . " tanggal {$statusLabel} pada {$tanggal}" . ($validated['keterangan'] ? " ({$validated['keterangan']})" : ""),
            subject: $record,
            properties: $validated,
        );

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

        ActivityLogger::log(
            event: 'delete',
            module: 'tanggal_libur',
            description: "Menghapus tanggal libur pada {$validated['tanggal']}",
            properties: ['tanggal' => $validated['tanggal']],
        );

        return response()->json(['success' => true, 'message' => 'Data tanggal berhasil dihapus.']);
    }

    public function destroy(Request $request, $id = null)
    {
        if ($id) {
            $record = TanggalLibur::findOrFail($id);
            $tanggalStr = $record->tanggal?->format('Y-m-d');
            $record->delete();

            ActivityLogger::log(
                event: 'delete',
                module: 'tanggal_libur',
                description: "Menghapus tanggal libur pada {$tanggalStr}",
                properties: ['id' => $id, 'tanggal' => $tanggalStr],
            );
        } else {
            $validated = $request->validate([
                'tanggal' => 'required|date',
            ]);
            $deleted = TanggalLibur::whereDate('tanggal', $validated['tanggal'])->delete();
            if (!$deleted) {
                return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
            }

            ActivityLogger::log(
                event: 'delete',
                module: 'tanggal_libur',
                description: "Menghapus tanggal libur pada {$validated['tanggal']}",
                properties: ['tanggal' => $validated['tanggal']],
            );
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

        ActivityLogger::log(
            event: 'create',
            module: 'tanggal_libur',
            description: "Menambah info lokasi pada {$tanggal}" . (!empty($validated['lokasi']) ? " ({$validated['lokasi']})" : ""),
            subject: $record,
            properties: $validated,
        );

        return response()->json(['success' => true, 'message' => 'Info lokasi berhasil ditambahkan.', 'data' => $record]);
    }

    public function updateInfo(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:info_tanggal,id',
            'lokasi' => 'nullable|string|max:255',
            'catatan' => 'nullable|string|max:255',
        ]);

        $info = InfoTanggal::findOrFail($validated['id']);
        $oldData = [
            'lokasi' => $info->lokasi,
            'catatan' => $info->catatan,
        ];

        $info->update([
            'lokasi' => $validated['lokasi'] ?? null,
            'catatan' => $validated['catatan'] ?? null,
        ]);

        ActivityLogger::log(
            event: 'update',
            module: 'tanggal_libur',
            description: "Mengubah info lokasi pada {$info->tanggal->format('Y-m-d')}" . (!empty($validated['lokasi']) ? " ({$validated['lokasi']})" : ""),
            subject: $info,
            properties: ['old' => $oldData, 'new' => $validated],
        );

        return response()->json(['success' => true, 'message' => 'Info lokasi berhasil diubah.', 'data' => $info]);
    }

    public function destroyInfo(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:info_tanggal,id',
        ]);

        $info = InfoTanggal::findOrFail($validated['id']);
        $snapshot = [
            'id' => $info->id,
            'tanggal' => $info->tanggal?->format('Y-m-d'),
            'lokasi' => $info->lokasi,
        ];
        $info->delete();

        ActivityLogger::log(
            event: 'delete',
            module: 'tanggal_libur',
            description: "Menghapus info lokasi pada {$snapshot['tanggal']}" . ($snapshot['lokasi'] ? " ({$snapshot['lokasi']})" : ""),
            properties: $snapshot,
        );

        return response()->json(['success' => true, 'message' => 'Info lokasi berhasil dihapus.']);
    }
}
