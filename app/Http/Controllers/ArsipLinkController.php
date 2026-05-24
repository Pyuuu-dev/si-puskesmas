<?php

namespace App\Http\Controllers;

use App\Http\Requests\Arsip\StoreLinkRequest;
use App\Http\Requests\Arsip\UpdateLinkRequest;
use App\Models\ArsipLink;
use App\Models\ArsipTag;
use App\Services\ActivityLogger;
use App\Services\Arsip\LinkIconService;
use App\Services\Arsip\LinkMetadataService;
use App\Services\Arsip\LinkOpenTrackerService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ArsipLinkController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private LinkMetadataService $meta,
        private LinkOpenTrackerService $tracker,
    ) {}

    public function store(StoreLinkRequest $request)
    {
        $data = $request->validated();

        $link = new ArsipLink([
            'folder_id'   => $data['folder_id'] ?? null,
            'title'       => $data['title'] ?? null,
            'notes'       => $data['notes'] ?? null,
            'icon_preset' => $data['icon_preset'] ?? null,
            'is_favorite' => (bool) ($data['is_favorite'] ?? false),
            'is_pinned'   => (bool) ($data['is_pinned']   ?? false),
            'created_by'  => auth()->id(),
        ]);
        $link->url = $data['url']; // mutator set url_hash + domain

        // Auto-detect icon preset dari URL kalau user tidak set manual
        if (empty($link->icon_preset)) {
            $link->icon_preset = LinkIconService::detect($data['url']);
        }

        if ($data['fetch_meta'] ?? true) {
            $m = $this->meta->fetch($data['url']);
            $link->title           = $link->title ?: ($m['title'] ?? null);
            $link->favicon         = $m['favicon']   ?? null;
            $link->thumbnail       = $m['thumbnail'] ?? null;
            $link->notes           = $link->notes ?: ($m['description'] ?? null);
            $link->meta_status     = $m['status'];
            $link->meta_fetched_at = now();
        }

        $link->title = $link->title ?: ($link->host ?: 'Tanpa Judul');
        $link->save();

        if (!empty($data['tags'])) {
            $link->tags()->sync(ArsipTag::syncFromInput($data['tags']));
        }

        ActivityLogger::log(
            event: 'create',
            module: 'arsip',
            description: "Menyimpan link: {$link->title}",
            subject: $link,
            properties: [
                'url'       => $link->url,
                'folder_id' => $link->folder_id,
            ],
        );

        return back()->with('success', "Link \"{$link->title}\" berhasil disimpan.");
    }

    public function update(UpdateLinkRequest $request, ArsipLink $link)
    {
        $data = $request->validated();

        $before = $link->replicate();
        $before->setRawAttributes($link->getOriginal());

        if (array_key_exists('url', $data) && $data['url'] !== $link->url) {
            $link->url = $data['url']; // mutator update hash & domain
            // Re-detect icon preset jika user tidak override
            if (!array_key_exists('icon_preset', $data) || $data['icon_preset'] === null) {
                $link->icon_preset = LinkIconService::detect($data['url']);
            }
        }

        $link->fill(array_intersect_key($data, array_flip([
            'folder_id', 'title', 'notes', 'icon_preset', 'is_favorite', 'is_pinned',
        ])));

        if ($link->title === null || $link->title === '') {
            $link->title = $link->host ?: 'Tanpa Judul';
        }

        $link->save();

        if (array_key_exists('tags', $data)) {
            $link->tags()->sync(ArsipTag::syncFromInput($data['tags'] ?? []));
        }

        ActivityLogger::log(
            event: 'update',
            module: 'arsip',
            description: "Mengubah link: {$link->title}",
            subject: $link,
            properties: ActivityLogger::diff($before, $link),
        );

        return back()->with('success', 'Link diperbarui.');
    }

    public function destroy(ArsipLink $link)
    {
        $this->authorize('delete', $link);

        $title = $link->title;
        $id    = $link->id;
        $link->delete();

        ActivityLogger::log(
            event: 'delete',
            module: 'arsip',
            description: "Menghapus link: {$title}",
            properties: ['id' => $id, 'title' => $title],
        );

        return back()->with('success', "Link \"{$title}\" dihapus.");
    }

    /** Toggle favorite — semua user login boleh */
    public function toggleFavorite(ArsipLink $link)
    {
        $this->authorize('favorite', $link);

        $link->is_favorite = ! $link->is_favorite;
        $link->save();

        return response()->json([
            'ok'          => true,
            'is_favorite' => $link->is_favorite,
        ]);
    }

    /** Toggle pin — admin only via policy update */
    public function togglePin(ArsipLink $link)
    {
        $this->authorize('update', $link);

        $link->is_pinned = ! $link->is_pinned;
        $link->save();

        return response()->json([
            'ok'        => true,
            'is_pinned' => $link->is_pinned,
        ]);
    }

    /** Re-fetch metadata manual */
    public function refetch(ArsipLink $link)
    {
        $this->authorize('update', $link);

        $m = $this->meta->fetch($link->url);

        $link->fill([
            'title'           => $m['title']     ?: $link->title,
            'favicon'         => $m['favicon']   ?: $link->favicon,
            'thumbnail'       => $m['thumbnail'] ?: $link->thumbnail,
            'meta_status'     => $m['status'],
            'meta_fetched_at' => now(),
        ])->save();

        return back()->with('success', 'Metadata diperbarui.');
    }

    /**
     * Redirect dengan tracking — tombol "Buka" arahkan ke endpoint ini.
     */
    public function go(ArsipLink $link)
    {
        $this->authorize('track', $link);
        $this->tracker->track($link);

        return redirect()->away($link->url);
    }

    /** Live search JSON untuk omnibar */
    public function search(Request $request)
    {
        $term = trim((string) $request->query('q', ''));
        if (mb_strlen($term) < 2) {
            return response()->json(['items' => []]);
        }

        $items = ArsipLink::query()
            ->search($term)
            ->with('folder:id,name,slug')
            ->limit(10)
            ->get(['id','title','url','domain','favicon','folder_id','is_favorite']);

        return response()->json([
            'items' => $items->map(fn (ArsipLink $l) => [
                'id'          => $l->id,
                'title'       => $l->title,
                'url'         => $l->url,
                'domain'      => $l->host,
                'favicon_url' => $l->favicon_url,
                'folder'      => $l->folder?->name,
                'is_favorite' => $l->is_favorite,
                'go_url'      => route('arsip.link.go', $l),
            ])->values(),
        ]);
    }
}
