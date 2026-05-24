<?php

namespace App\Services\Arsip;

use App\Models\ArsipFolder;

class BreadcrumbService
{
    /**
     * Build breadcrumb untuk halaman arsip. Selalu diawali node "Arsip" (root).
     *
     * @return array<int, array{id:int|null, name:string, slug:string|null}>
     */
    public function for(?ArsipFolder $folder): array
    {
        $crumbs = [[
            'id'   => null,
            'name' => 'Arsip',
            'slug' => null,
        ]];

        if (!$folder) return $crumbs;

        foreach ($folder->ancestors() as $a) {
            $crumbs[] = [
                'id'   => (int) $a->id,
                'name' => (string) $a->name,
                'slug' => (string) $a->slug,
            ];
        }

        $crumbs[] = [
            'id'   => (int) $folder->id,
            'name' => (string) $folder->name,
            'slug' => (string) $folder->slug,
        ];

        return $crumbs;
    }
}
