<?php

namespace App\Models;

use App\Services\Arsip\FolderTreeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $icon
 * @property string|null $color
 * @property string|null $description
 * @property int $sort_order
 * @property int|null $created_by
 */
class ArsipFolder extends Model
{
    protected $table = 'arsip_folders';

    public const COLOR_OPTIONS = [
        'red', 'orange', 'amber', 'yellow', 'lime', 'green',
        'emerald', 'teal', 'cyan', 'sky', 'blue', 'indigo',
        'violet', 'purple', 'fuchsia', 'pink', 'rose', 'gray',
    ];

    protected $fillable = [
        'parent_id', 'name', 'slug', 'icon', 'color',
        'description', 'sort_order', 'created_by',
    ];

    protected $casts = [
        'parent_id'  => 'integer',
        'sort_order' => 'integer',
    ];

    protected $appends = ['depth'];

    // ── Relations ────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    /**
     * Recursive eager-load: ->with('descendants') memuat tree penuh.
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    public function links(): HasMany
    {
        return $this->hasMany(ArsipLink::class, 'folder_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Ambil ancestor chain (root → parent langsung), tidak termasuk self.
     */
    public function ancestors(): Collection
    {
        $chain = new Collection();
        $node = $this->parent;
        while ($node) {
            $chain->prepend($node);
            $node = $node->parent;
        }
        return $chain;
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeRoots(Builder $q): Builder
    {
        return $q->whereNull('parent_id');
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort_order')->orderBy('name');
    }

    // ── Hooks ────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (self $f) {
            // Auto slug bila kosong
            if (blank($f->slug)) {
                $base = Str::slug($f->name) ?: 'folder';
                $slug = $base;
                $i = 1;
                while (
                    self::where('parent_id', $f->parent_id)
                        ->where('slug', $slug)
                        ->where('id', '!=', $f->id ?? 0)
                        ->exists()
                ) {
                    $slug = $base . '-' . (++$i);
                }
                $f->slug = $slug;
            }

            // Cegah cycle saat ada perpindahan parent
            if ($f->exists && $f->parent_id && self::wouldCreateCycle((int) $f->id, (int) $f->parent_id)) {
                throw new \DomainException('Folder tidak bisa dipindah ke turunannya sendiri.');
            }
        });

        // Bust cache tree setiap perubahan
        static::saved(fn () => self::bustTreeCache());
        static::deleted(fn () => self::bustTreeCache());
    }

    /**
     * Cek apakah memindahkan $movingId ke bawah $newParentId akan membuat cycle.
     */
    public static function wouldCreateCycle(int $movingId, int $newParentId): bool
    {
        if ($movingId === $newParentId) return true;

        $cursor = self::find($newParentId);
        $guard = 0;
        while ($cursor && $guard++ < 1000) {
            if ((int) $cursor->id === $movingId) return true;
            $cursor = $cursor->parent;
        }
        return false;
    }

    private static function bustTreeCache(): void
    {
        try {
            app(FolderTreeService::class)->bust();
        } catch (\Throwable $e) {
            // Service belum tersedia di console boot dini — abaikan
        }
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getDepthAttribute(): int
    {
        if (!$this->parent_id) return 0;
        $d = 0;
        $node = $this->parent;
        $guard = 0;
        while ($node && $guard++ < 1000) {
            $d++;
            $node = $node->parent;
        }
        return $d;
    }
}
