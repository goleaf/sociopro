<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array{title: list<string>, category: list<string>, description: list<string>, tag: list<string>, image: list<string>}
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'max:255'],
            'category' => ['required'],
            'description' => ['nullable'],
            'tag' => ['nullable'],
            'image' => ['nullable'],
        ];
    }
}
