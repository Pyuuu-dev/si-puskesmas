<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /**
     * List semua role.
     */
    public function index()
    {
        $roles = Role::withCount(['permissions', 'users'])
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();

        return view('roles.index', compact('roles'));
    }

    /**
     * Simpan role baru.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'display_name' => 'required|string|max:100',
            'description'  => 'nullable|string|max:255',
        ]);

        $name = Str::slug($data['display_name'], '_');

        // Pastikan slug unik
        $base = $name;
        $i = 2;
        while (Role::where('name', $name)->exists()) {
            $name = $base . '_' . $i++;
        }

        Role::create([
            'name'         => $name,
            'display_name' => $data['display_name'],
            'description'  => $data['description'] ?? null,
            'is_system'    => false,
        ]);

        return redirect()->route('roles.index')->with('success', 'Role berhasil ditambahkan.');
    }

    /**
     * Update info role (display_name & description).
     */
    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'display_name' => 'required|string|max:100',
            'description'  => 'nullable|string|max:255',
        ]);

        $role->update([
            'display_name' => $data['display_name'],
            'description'  => $data['description'] ?? null,
        ]);

        return redirect()->route('roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    /**
     * Hapus role (tidak boleh role bawaan / yang masih dipakai).
     */
    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', 'Role bawaan sistem tidak dapat dihapus.');
        }

        $userCount = User::where('role', $role->name)->count();
        if ($userCount > 0) {
            return back()->with('error', "Role tidak dapat dihapus karena masih dipakai oleh {$userCount} pengguna.");
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role berhasil dihapus.');
    }

    /**
     * Halaman pengaturan permission per role (matriks).
     */
    public function permissions(Role $role)
    {
        $grouped = Permission::orderBy('sort_order')->get()->groupBy('group');
        $owned   = $role->permissions()->pluck('permissions.id')->all();

        // Susun daftar action unik per menu untuk header kolom
        $menuActions = [];
        foreach ($grouped as $group => $perms) {
            foreach ($perms as $p) {
                $menuActions[$p->menu][$p->action] = $p; // map action -> permission
            }
        }

        return view('roles.permissions', [
            'role'        => $role,
            'grouped'     => $grouped,
            'owned'       => $owned,
            'menuActions' => $menuActions,
        ]);
    }

    /**
     * Sinkronisasi permission untuk role tertentu.
     */
    public function syncPermissions(Request $request, Role $role)
    {
        // Super admin tidak boleh diubah permission-nya (selalu full access via Gate::before)
        if ($role->name === 'super_admin') {
            return back()->with('error', 'Permission Super Admin tidak dapat diubah (akses penuh dijamin sistem).');
        }

        $ids = (array) $request->input('permissions', []);
        // Filter ID yang valid
        $valid = Permission::whereIn('id', $ids)->pluck('id')->all();

        $role->permissions()->sync($valid);

        return redirect()->route('roles.permissions', $role)
            ->with('success', 'Permission untuk role "' . $role->display_name . '" berhasil disimpan.');
    }
}
