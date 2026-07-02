<?php

namespace App\Http\Requests\Page;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePageInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'job' => ['nullable', 'string', 'max:255'],
            'lifestyle' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ];
    }
}
