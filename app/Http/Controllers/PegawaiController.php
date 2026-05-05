<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PegawaiController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        
        $pegawai = User::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('nip', 'like', "%{$search}%")
                      ->orWhere('jabatan', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('urutan')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
        
        return view('pegawai.index', compact('pegawai', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:50|unique:users,nip',
            'pangkat_golongan' => 'nullable|string|max:255',
            'status_pegawai' => 'nullable|string|max:255',
            'status_kepegawaian' => 'nullable|string|in:PNS,PPPK,PPPK Paruh Waktu,PTT,Lainnya',
            'jabatan' => 'nullable|string|max:255',
            'unit_kerja' => 'nullable|string|max:255',
            'penempatan' => 'required|in:induk,desa',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:super_admin,kepala,pegawai',
            'is_user' => 'required|boolean',
            'password' => 'required_if:is_user,true|nullable|string|min:6',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user = User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Pegawai berhasil ditambahkan.',
            'data' => $user,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nip')->ignore($user->id)],
            'pangkat_golongan' => 'nullable|string|max:255',
            'status_pegawai' => 'nullable|string|max:255',
            'status_kepegawaian' => 'nullable|string|in:PNS,PPPK,PPPK Paruh Waktu,PTT,Lainnya',
            'jabatan' => 'nullable|string|max:255',
            'unit_kerja' => 'nullable|string|max:255',
            'penempatan' => 'required|in:induk,desa',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => 'required|in:super_admin,kepala,pegawai',
            'is_user' => 'required|boolean',
            'password' => 'nullable|string|min:6',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Pegawai berhasil diperbarui.',
            'data' => $user->fresh(),
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus akun sendiri.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pegawai berhasil dihapus.',
        ]);
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|integer|exists:users,id',
            'order.*.urutan' => 'required|integer|min:0',
        ]);

        foreach ($validated['order'] as $item) {
            User::where('id', $item['id'])->update(['urutan' => $item['urutan']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Urutan pegawai berhasil diperbarui.',
        ]);
    }

    public function export()
    {
        $pegawai = User::orderBy('urutan')->orderBy('name')->get();

        $csv = "Nama,NIP,Pangkat/Golongan,ST/S/F,Jabatan,Penempatan,Akses Login,Email,Password\n";
        foreach ($pegawai as $p) {
            $csv .= '"' . str_replace('"', '""', $p->name) . '",';
            $csv .= '"' . ($p->nip ?? '') . '",';
            $csv .= '"' . ($p->pangkat_golongan ?? '') . '",';
            $csv .= '"' . ($p->status_pegawai ?? '') . '",';
            $csv .= '"' . ($p->jabatan ?? '') . '",';
            $csv .= '"' . ($p->penempatan ?? 'induk') . '",';
            $csv .= '"' . ($p->is_user ? 'Ya' : 'Tidak') . '",';
            $csv .= '"' . $p->email . '",';
            $csv .= "\"\"\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="data_pegawai.csv"');
    }

    public function downloadTemplate()
    {
        $csv = "Nama,NIP,Pangkat/Golongan,ST/S/F,Jabatan,Penempatan,Akses Login,Email,Password,Role\n";
        $csv .= "\"Contoh Nama\",\"199001012020011001\",\"III/a\",\"ST\",\"Bidan\",\"induk\",\"Ya\",\"contoh@email.com\",\"password123\",\"pegawai\"\n";

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="template_import_pegawai.csv"');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('file');
        $content = file_get_contents($file->getRealPath());
        $lines = array_filter(explode("\n", $content));

        // Skip header
        array_shift($lines);

        $imported = 0;
        $errors = [];

        foreach ($lines as $i => $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $data = str_getcsv($line);
            if (count($data) < 6) {
                $errors[] = "Baris " . ($i + 2) . ": Data tidak lengkap";
                continue;
            }

            $name = trim($data[0] ?? '');
            $nip = trim($data[1] ?? '') ?: null;
            $pangkat = trim($data[2] ?? '') ?: null;
            $statusPeg = trim($data[3] ?? '') ?: null;
            $jabatan = trim($data[4] ?? '') ?: null;
            $penempatan = strtolower(trim($data[5] ?? 'induk'));
            $isUser = strtolower(trim($data[6] ?? 'tidak')) === 'ya';
            $email = trim($data[7] ?? '');
            $password = trim($data[8] ?? 'password');
            $role = strtolower(trim($data[9] ?? 'pegawai'));

            if (!in_array($role, ['super_admin', 'kepala', 'pegawai'])) {
                $role = 'pegawai';
            }

            if (empty($name)) {
                $errors[] = "Baris " . ($i + 2) . ": Nama wajib diisi";
                continue;
            }

            if (!in_array($penempatan, ['induk', 'desa'])) {
                $penempatan = 'induk';
            }

            // Generate email if empty
            if (empty($email)) {
                $email = strtolower(str_replace(' ', '.', $name)) . '@puskesmas.id';
            }

            // Check duplicate email
            if (User::where('email', $email)->exists()) {
                $errors[] = "Baris " . ($i + 2) . ": Email {$email} sudah terdaftar";
                continue;
            }

            try {
                User::create([
                    'name' => $name,
                    'nip' => $nip,
                    'pangkat_golongan' => $pangkat,
                    'status_pegawai' => $statusPeg,
                    'jabatan' => $jabatan,
                    'penempatan' => $penempatan,
                    'unit_kerja' => null,
                    'email' => $email,
                    'role' => $role,
                    'is_user' => $isUser,
                    'password' => Hash::make($password ?: 'password'),
                ]);
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Baris " . ($i + 2) . ": " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Berhasil import {$imported} pegawai." . (count($errors) > 0 ? " " . count($errors) . " error." : ""),
            'imported' => $imported,
            'errors' => $errors,
        ]);
    }
}
