<?php

namespace App\Services\Arsip;

use App\Models\ArsipFolder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FolderTreeService
{
    private const CACHE_KEY = 'arsip:folder-tree:v1';
    private const TTL       = 3600; // 1 jam, di-bust manual saat ada perubahan

    /**
     * Tree penuh sebagai nested Collection.
     * Tiap node: id, parent_id, name, slug, icon, color, depth, links_count, children
     */
    public function tree(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::TTL, function () {
            $rows = ArsipFolder::query()
                ->ordered()
                ->withCount('links')
                ->get();
            return $this->buildNested($rows, null, 0);
        });
    }

    private function buildNested(EloquentCollection $rows, ?int $parentId, int $depth): Collection
    {
        return $rows
            ->where('parent_id', $parentId)
            ->values()
            ->map(fn (ArsipFolder $f) => [
                'id'          => (int) $f->id,
                'parent_id'   => $f->parent_id ? (int) $f->parent_id : null,
                'name'        => (string) $f->name,
                'slug'        => (string) $f->slug,
                'icon'        => $f->icon,
                'color'       => $f->color ?: 'indigo',
                'description' => $f->description,
                'depth'       => $depth,
                'links_count' => (int) ($f->links_count ?? 0),
                'children'    => $this->buildNested($rows, (int) $f->id, $depth + 1),
            ])
            ->collect();
    }

    /**
     * Flatten tree → list ordered untuk dropdown "pilih folder".
     * Item: ['id','label','depth'] dengan label "Parent / Child / Grandchild".
     */
    public function flatList(): Collection
    {
        return $this->flatten($this->tree());
    }

    private function flatten(Collection $nodes, string $prefix = ''): Collection
    {
        return $nodes->flatMap(function (array $node) use ($prefix) {
            $label = $prefix . $node['name'];
            $self  = collect([[
                'id'    => $node['id'],
                'label' => $label,
                'depth' => $node['depth'],
            ]]);
            return $self->merge($this->flatten(collect($node['children']), $label . ' / '));
        });
    }

    public function bust(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Reorder & re-parent batch — dipakai endpoint drag-drop.
     *
     * @param  int[]      $orderedIds  ID folder dalam urutan baru di parent tujuan
     * @param  int|null   $newParentId Parent tujuan (null = root)
     */
    public function reorder(array $orderedIds, ?int $newParentId): void
    {
        DB::transaction(function () use ($orderedIds, $newParentId) {
            foreach ($orderedIds as $i => $id) {
                $id = (int) $id;
                if ($newParentId !== null && ArsipFolder::wouldCreateCycle($id, (int) $newParentId)) {
                    throw new \DomainException("Folder #{$id} tidak bisa dipindah ke turunannya.");
                }
                ArsipFolder::whereKey($id)->update([
                    'parent_id'  => $newParentId,
                    'sort_order' => $i,
                    'updated_at' => now(),
                ]);
            }
        });
        $this->bust();
    }
}
