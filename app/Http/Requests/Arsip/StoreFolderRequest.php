<?php

namespace App\Http\Requests\Arsip;

use App\Models\ArsipFolder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->can('create', ArsipFolder::class);
    }

    /**
     * Normalisasi parent_id: terima "", "null", "0", 0 sebagai null.
     * Form HTML kirim string, jadi perlu di-cast supaya rule integer lulus.
     */
    protected function prepareForValidation(): void
    {
        $parent = $this->input('parent_id');
        if ($parent === '' || $parent === 'null' || $parent === '0' || $parent === 0) {
            $this->merge(['parent_id' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'parent_id'   => ['nullable', 'integer', Rule::exists('arsip_folders', 'id')],
            'name'        => ['required', 'string', 'max:150'],
            'icon'        => ['nullable', 'string', 'max:50'],
            'color'       => ['nullable', Rule::in(ArsipFolder::COLOR_OPTIONS)],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Nama folder wajib diisi.',
            'name.max'         => 'Nama folder maksimal 150 karakter.',
            'parent_id.exists' => 'Folder induk tidak ditemukan.',
            'color.in'         => 'Warna folder tidak valid.',
        ];
    }
}
