<?php

namespace App\Http\Controllers;

use App\Models\ArsipFolder;
use App\Models\ArsipTag;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ArsipTagController extends Controller
{
    use AuthorizesRequests;

    public function destroy(ArsipTag $tag)
    {
        // Admin-only — reuse ArsipFolder create policy (sama-sama isAdmin check)
        $this->authorize('create', ArsipFolder::class);

        $name = $tag->name;
        $id   = $tag->id;
        $tag->delete();

        ActivityLogger::log(
            event: 'delete',
            module: 'arsip',
            description: "Menghapus tag arsip: {$name}",
            properties: ['id' => $id, 'name' => $name],
        );

        return back()->with('success', "Tag \"{$name}\" dihapus.");
    }
}
