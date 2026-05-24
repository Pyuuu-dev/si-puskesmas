<?php

namespace App\Http\Requests\Arsip;

use App\Models\ArsipLink;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->can('create', ArsipLink::class);
    }

    public function prepareForValidation(): void
    {
        $folder = $this->input('folder_id');
        if ($folder === '' || $folder === 'null' || $folder === '0' || $folder === 0) {
            $folder = null;
        }

        $iconPreset = $this->input('icon_preset');
        if ($iconPreset === '' || $iconPreset === 'null' || $iconPreset === 'auto') {
            $iconPreset = null;
        } elseif ($iconPreset && !\App\Services\Arsip\LinkIconService::isValid($iconPreset)) {
            $iconPreset = null; // Defensive: ignore unknown slug
        }

        $this->merge([
            'folder_id'   => $folder,
            'icon_preset' => $iconPreset,
            'is_favorite' => $this->boolean('is_favorite'),
            'is_pinned'   => $this->boolean('is_pinned'),
            'fetch_meta'  => $this->has('fetch_meta') ? $this->boolean('fetch_meta') : true,
            'tags'        => $this->normalizeTags($this->input('tags')),
        ]);
    }

    public function rules(): array
    {
        return [
            'folder_id'   => ['nullable', 'integer', Rule::exists('arsip_folders', 'id')],
            'url'         => ['required', 'url:http,https', 'max:2048'],
            'title'       => ['nullable', 'string', 'max:255'],
            'notes'       => ['nullable', 'string', 'max:2000'],
            'icon_preset' => ['nullable', 'string', 'max:30'],
            'is_favorite' => ['sometimes', 'boolean'],
            'is_pinned'   => ['sometimes', 'boolean'],
            'fetch_meta'  => ['sometimes', 'boolean'],
            'tags'        => ['nullable', 'array', 'max:15'],
            'tags.*'      => ['string', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'url.required' => 'URL wajib diisi.',
            'url.url'      => 'URL tidak valid (harus diawali http:// atau https://).',
            'url.max'      => 'URL terlalu panjang (maksimal 2048 karakter).',
            'tags.max'     => 'Maksimal 15 tag per link.',
        ];
    }

    /**
     * Normalisasi input tags: terima array atau string CSV / koma-separated.
     */
    private function normalizeTags(mixed $tags): array
    {
        if (is_array($tags)) {
            return array_values(array_filter(array_map('trim', $tags)));
        }
        if (is_string($tags) && $tags !== '') {
            return array_values(array_filter(array_map('trim', preg_split('/[,;\n]+/', $tags) ?: [])));
        }
        return [];
    }
}
