<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int|null $folder_id
 * @property string $title
 * @property string $url
 * @property string $url_hash
 * @property string|null $domain
 * @property string|null $favicon
 * @property string|null $thumbnail
 * @property string|null $notes
 * @property bool $is_favorite
 * @property bool $is_pinned
 * @property int $open_count
 * @property \Illuminate\Support\Carbon|null $last_opened_at
 * @property \Illuminate\Support\Carbon|null $meta_fetched_at
 * @property string|null $meta_status
 * @property int $sort_order
 * @property int|null $created_by
 */
class ArsipLink extends Model
{
    protected $table = 'arsip_links';

    protected $fillable = [
        'folder_id', 'title', 'url', 'url_hash', 'domain',
        'favicon', 'thumbnail', 'icon_preset', 'notes',
        'is_favorite', 'is_pinned', 'open_count',
        'last_opened_at', 'meta_fetched_at', 'meta_status',
        'sort_order', 'created_by',
    ];

    protected $casts = [
        'is_favorite'     => 'boolean',
        'is_pinned'       => 'boolean',
        'open_count'      => 'integer',
        'sort_order'      => 'integer',
        'last_opened_at'  => 'datetime',
        'meta_fetched_at' => 'datetime',
    ];

    protected $appends = ['favicon_url', 'thumbnail_url', 'host'];

    // ── Relations ────────────────────────────────────────────────

    public function folder(): BelongsTo
    {
        return $this->belongsTo(ArsipFolder::class, 'folder_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            ArsipTag::class,
            'arsip_link_tag',
            'link_id',
            'tag_id'
        )->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeFavorites(Builder $q): Builder
    {
        return $q->where('is_favorite', true);
    }

    public function scopePinned(Builder $q): Builder
    {
        return $q->where('is_pinned', true);
    }

    public function scopeRecent(Builder $q, int $days = 30): Builder
    {
        return $q->whereNotNull('last_opened_at')
            ->where('last_opened_at', '>=', now()->subDays($days))
            ->orderByDesc('last_opened_at');
    }

    public function scopeInFolder(Builder $q, ?int $folderId): Builder
    {
        return is_null($folderId)
            ? $q->whereNull('folder_id')
            : $q->where('folder_id', $folderId);
    }

    /**
     * Search lintas title/notes/domain/url. Pakai FULLTEXT bila tersedia,
     * fallback ke LIKE.
     */
    public function scopeSearch(Builder $q, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') return $q;

        $driver = DB::connection()->getDriverName();
        $supportsFt = false;

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            try {
                $idx = DB::select(
                    "SHOW INDEX FROM arsip_links WHERE Index_type = 'FULLTEXT'"
                );
                $supportsFt = !empty($idx);
            } catch (\Throwable $e) {
                $supportsFt = false;
            }
        }

        if ($supportsFt) {
            return $q->whereRaw(
                'MATCH(title, notes, domain) AGAINST (? IN BOOLEAN MODE)',
                [self::booleanQuery($term)]
            );
        }

        $like = '%' . $term . '%';
        return $q->where(function ($w) use ($like) {
            $w->where('title', 'like', $like)
                ->orWhere('notes', 'like', $like)
                ->orWhere('domain', 'like', $like)
                ->orWhere('url', 'like', $like);
        });
    }

    private static function booleanQuery(string $term): string
    {
        $words = preg_split('/\s+/', $term) ?: [];
        $clean = collect($words)
            ->map(fn ($w) => preg_replace('/[+\-><\(\)~*"@]/', '', (string) $w))
            ->filter(fn ($w) => mb_strlen($w) >= 2)
            ->map(fn ($w) => '+' . $w . '*')
            ->implode(' ');
        return $clean !== '' ? $clean : '"' . preg_replace('/[+\-><\(\)~*"@]/', '', $term) . '"';
    }

    // ── Mutators ────────────────────────────────────────────────

    public function setUrlAttribute(string $url): void
    {
        $url = trim($url);
        $this->attributes['url']      = $url;
        $this->attributes['url_hash'] = sha1($url);
        $this->attributes['domain']   = parse_url($url, PHP_URL_HOST) ?: null;
    }

    // ── Accessors ───────────────────────────────────────────────

    public function getFaviconUrlAttribute(): ?string
    {
        if (!$this->favicon) return null;
        return Str::startsWith($this->favicon, ['http://', 'https://', 'data:'])
            ? $this->favicon
            : Storage::disk('public')->url($this->favicon);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail) return null;
        return Str::startsWith($this->thumbnail, ['http://', 'https://', 'data:'])
            ? $this->thumbnail
            : Storage::disk('public')->url($this->thumbnail);
    }

    public function getHostAttribute(): ?string
    {
        if ($this->domain) return $this->domain;
        return parse_url((string) $this->url, PHP_URL_HOST) ?: null;
    }
}
