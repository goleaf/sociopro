<?php

namespace App\Http\Requests\Page;

class UpdatePageCoverPhotoRequest extends PageWriteRequest
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
            'cover_photo' => ['nullable', 'file', 'image', 'mimes:jpeg,jpg,png,gif', 'extensions:jpeg,jpg,png,gif', 'max:5120'],
        ];
    }
}
