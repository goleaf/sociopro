<?php

namespace App\Http\Requests\Page;

use App\Models\Page;
use Illuminate\Support\Facades\Gate;

class StorePageRequest extends PageWriteRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && Gate::allows('create', Page::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,gif', 'extensions:jpeg,jpg,png,gif', 'max:5120'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'integer', 'exists:pagecategories,id'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
