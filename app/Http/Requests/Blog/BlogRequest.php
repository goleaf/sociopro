<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

abstract class BlogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'integer', 'exists:blogcategories,id'],
            'description' => ['nullable', 'string'],
            'tag' => ['nullable'],
            'tags' => ['sometimes', 'array'],
            'tags.*.value' => ['required', 'string', 'max:100'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
        ];
    }

    /**
     * @return list<string>
     */
    public function tagValues(): array
    {
        $tags = $this->validated('tags', []);

        if (! is_array($tags)) {
            return [];
        }

        $values = [];
        foreach ($tags as $tag) {
            if (! is_array($tag) || ! array_key_exists('value', $tag)) {
                continue;
            }

            $value = trim((string) $tag['value']);
            if ($value !== '') {
                $values[] = $value;
            }
        }

        return $values;
    }

    public function imageFile(): ?UploadedFile
    {
        $file = $this->file('image');

        return $file instanceof UploadedFile ? $file : null;
    }

    protected function prepareForValidation(): void
    {
        $tagInput = $this->input('tag');

        if (is_array($tagInput)) {
            $this->merge(['tags' => $tagInput]);

            return;
        }

        if (! is_string($tagInput) || trim($tagInput) === '') {
            return;
        }

        $decodedTags = json_decode($tagInput, true);
        if (is_array($decodedTags)) {
            $this->merge(['tags' => $decodedTags]);
        }
    }
}
