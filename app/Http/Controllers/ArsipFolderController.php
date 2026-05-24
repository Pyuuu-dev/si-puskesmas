<?php

namespace App\Http\Controllers;

use App\Http\Requests\Arsip\MoveFolderRequest;
use App\Http\Requests\Arsip\StoreFolderRequest;
use App\Http\Requests\Arsip\UpdateFolderRequest;
use App\Models\ArsipFolder;
use App\Models\ArsipLink;
use App\Models\ArsipTag;
use App\Services\ActivityLogger;
use App\Services\Arsip\BreadcrumbService;
use App\Services\Arsip\FolderTreeService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ArsipFolderController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private FolderTreeService $tree,
        private BreadcrumbService $crumbs,
    ) {}

    /**
     * Halaman utama /arsip dan /arsip/folder/{folder}.
     */
    public function index(Request $request, ?ArsipFolder $folder = null)
    {
        $this->authorize('viewAny', ArsipFolder::class);

        $perPage = (int) $request->query('per_page', 24);
        if (!in_array($perPage, [12, 24, 48, 96], true)) {
            $perPage = 24;
        }

        $sort    = $request->query('sort', 'recent');     // recent|title|opened|created
        $filter  = $request->query('filter');             // null|favorite|pinned|recent
        $search  = trim((string) $request->query('q', ''));
        $tagSlug = $request->query('tag');

        $globalMode = ($filter !== null && $filter !== '')
            || $search !== ''
            || ($tagSlug !== null && $tagSlug !== '');

        $linksQuery = ArsipLink::query()->with(['tags:id,name,slug,color', 'folder:id,name,slug']);

        if (!$globalMode) {
            $linksQuery->inFolder($folder?->id);
        }

        if ($filter === 'favorite') $linksQuery->favorites();
        if ($filter === 'pinned')   $linksQuery->pinned();
        if ($filter === 'recent')   $linksQuery->recent();
        if ($search !== '')         $linksQuery->search($search);
        if ($tagSlug) {
            $linksQuery->whereHas('tags', fn ($q) => $q->where('slug', $tagSlug));
        }

        $linksQuery = match ($sort) {
            'title'   => $linksQuery->orderBy('title'),
            'opened'  => $linksQuery->orderByDesc('open_count')->orderByDesc('last_opened_at'),
            'created' => $linksQuery->orderByDesc('created_at'),
            default   => $linksQuery->orderByDesc('is_pinned')
                                    ->orderByDesc('last_opened_at')
                                    ->orderByDesc('created_at'),
        };

        $links = $linksQuery->paginate($perPage)->withQueryString();

        $showHomeSections = !$globalMode && !$folder;

        // Subfolder yang muncul sebagai folder cards di grid utama (file-explorer style).
        // Hanya saat browsing folder normal (bukan saat search / filter global).
        $subfolders = $globalMode
            ? collect()
            : ($folder
                ? $folder->children()->withCount('links')->ordered()->get()
                : \App\Models\ArsipFolder::roots()->withCount('links')->ordered()->get());

        return view('arsip.index', [
            'tree'             => $this->tree->tree(),
            'flatFolders'      => $this->tree->flatList(),
            'currentFolder'    => $folder,
            'breadcrumbs'      => $this->crumbs->for($folder),
            'links'            => $links,
            'subfolders'       => $subfolders,
            'allTags'          => ArsipTag::orderBy('name')->get(),
            'pinnedLinks'      => $showHomeSections
                ? ArsipLink::pinned()->with('folder:id,name')->latest('updated_at')->limit(8)->get()
                : collect(),
            'recentLinks'      => $showHomeSections
                ? ArsipLink::recent(30)->with('folder:id,name')->limit(8)->get()
                : collect(),
            'favoriteLinks'    => $showHomeSections
                ? ArsipLink::favorites()->with('folder:id,name')->latest()->limit(8)->get()
                : collect(),
            'filters' => [
                'sort'    => $sort,
                'filter'  => $filter,
                'search'  => $search,
                'tag'     => $tagSlug,
                'per_page'=> $perPage,
            ],
        ]);
    }

    public function store(StoreFolderRequest $request)
    {
        $folder = ArsipFolder::create(
            $request->validated() + ['created_by' => auth()->id()]
        );

        ActivityLogger::log(
            event: 'create',
            module: 'arsip',
            description: "Membuat folder arsip: {$folder->name}",
            subject: $folder,
            properties: [
                'parent_id' => $folder->parent_id,
                'name'      => $folder->name,
            ],
        );

        return redirect()
            ->route('arsip.folder', $folder->id)
            ->with('success', "Folder \"{$folder->name}\" berhasil dibuat.");
    }

    public function update(UpdateFolderRequest $request, ArsipFolder $folder)
    {
        $before = $folder->replicate();
        $before->setRawAttributes($folder->getOriginal());

        $folder->fill($request->validated())->save();

        ActivityLogger::log(
            event: 'update',
            module: 'arsip',
            description: "Mengubah folder arsip: {$folder->name}",
            subject: $folder,
            properties: ActivityLogger::diff($before, $folder),
        );

        return back()->with('success', "Folder \"{$folder->name}\" diperbarui.");
    }

    public function destroy(ArsipFolder $folder)
    {
        $this->authorize('delete', $folder);

        $name = $folder->name;
        $id   = $folder->id;

        // FK self → nullOnDelete: children naik jadi root
        // FK link → nullOnDelete: link naik ke "Tanpa Folder"
        $folder->delete();

        ActivityLogger::log(
            event: 'delete',
            module: 'arsip',
            description: "Menghapus folder arsip: {$name}",
            properties: ['id' => $id, 'name' => $name],
        );

        return redirect()
            ->route('arsip.index')
            ->with('success', "Folder \"{$name}\" dihapus. Sub-folder & link dipindah ke akar.");
    }

    /**
     * Endpoint drag-drop reorder.
     * Body: { parent_id: int|null, order: int[] }
     */
    public function move(MoveFolderRequest $request)
    {
        $this->authorize('create', ArsipFolder::class);

        $parentId = $request->input('parent_id');
        $parentId = ($parentId === null || $parentId === '') ? null : (int) $parentId;
        $order    = array_map('intval', $request->input('order', []));

        try {
            $this->tree->reorder($order, $parentId);
        } catch (\DomainException $e) {
            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        ActivityLogger::log(
            event: 'update',
            module: 'arsip',
            description: 'Menyusun ulang folder arsip',
            properties: ['parent_id' => $parentId, 'order' => $order],
        );

        return response()->json(['ok' => true]);
    }
}
