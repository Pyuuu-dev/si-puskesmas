<?php

namespace App\Http\Requests\Arsip;

use App\Models\ArsipFolder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveFolderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && $this->user()->can('create', ArsipFolder::class);
    }

    public function rules(): array
    {
        return [
            'parent_id' => ['nullable', 'integer', Rule::exists('arsip_folders', 'id')],
            'order'     => ['required', 'array', 'min:1'],
            'order.*'   => ['integer', Rule::exists('arsip_folders', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'order.required' => 'Urutan folder kosong.',
            'order.array'    => 'Format urutan tidak valid.',
        ];
    }
}
