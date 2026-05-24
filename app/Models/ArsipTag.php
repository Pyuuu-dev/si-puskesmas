<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $color
 * @property int|null $created_by
 */
class ArsipTag extends Model
{
    protected $table = 'arsip_tags';

    protected $fillable = ['name', 'slug', 'color', 'created_by'];

    public function links(): BelongsToMany
    {
        return $this->belongsToMany(
            ArsipLink::class,
            'arsip_link_tag',
            'tag_id',
            'link_id'
        )->withTimestamps();
    }

    protected static function booted(): void
    {
        static::saving(function (self $t) {
            if (blank($t->slug)) {
                $t->slug = Str::slug($t->name) ?: 'tag-' . Str::random(6);
            }
        });
    }

    /**
     * Konversi array nama tag → array id (firstOrCreate).
     *
     * @param  array<int,string>  $names
     * @return array<int,int>
     */
    public static function syncFromInput(array $names): array
    {
        return collect($names)
            ->map(fn ($n) => trim((string) $n))
            ->filter(fn ($n) => $n !== '')
            ->unique()
            ->take(15)
            ->map(function (string $name) {
                $slug = Str::slug($name) ?: Str::random(6);
                return self::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'created_by' => Auth::id()]
                )->id;
            })
            ->values()
            ->all();
    }
}
