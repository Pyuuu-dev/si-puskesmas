<?php

namespace App\Http\Requests\Arsip;

use App\Models\ArsipFolder;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $folder = $this->route('folder');
        if (!$folder instanceof ArsipFolder) return false;
        return $this->user() !== null && $this->user()->can('update', $folder);
    }

    /**
     * Normalisasi parent_id: terima "", "null", "0", 0 sebagai null.
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $folder = $this->route('folder');
            $folderId = $folder instanceof ArsipFolder ? (int) $folder->id : 0;
            $newParent = $this->input('parent_id');
            if (!$folderId || $newParent === null || $newParent === '') return;

            $newParent = (int) $newParent;
            if ($newParent === $folderId) {
                $v->errors()->add('parent_id', 'Folder tidak bisa jadi induk untuk dirinya sendiri.');
                return;
            }
            if (ArsipFolder::wouldCreateCycle($folderId, $newParent)) {
                $v->errors()->add('parent_id', 'Pemindahan ini akan membuat loop folder.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required'    => 'Nama folder wajib diisi.',
            'parent_id.exists' => 'Folder induk tidak ditemukan.',
        ];
    }
}
