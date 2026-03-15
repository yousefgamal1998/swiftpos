<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'color' => ['required', 'string', Rule::in(['emerald', 'sky', 'amber', 'violet', 'slate'])],
            'route_name' => ['required', 'string', 'max:255'],
            'permission' => ['nullable', 'string', Rule::exists('permissions', 'name')],
            'role' => ['nullable', 'string', Rule::exists('roles', 'name')],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }
}
