<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatMessageRequest extends FormRequest
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
            // Legacy clients still send the misspelled receiver field.
            'reciver_id' => ['nullable', 'integer'],
            'receiver_id' => ['nullable', 'integer'],
            'messagecenter' => ['nullable', 'string'],
            'message' => ['nullable', 'string'],
            'thumbsup' => ['nullable'],
            'product_id' => ['nullable', 'integer'],
            // File extension handling remains in the legacy controller path for now.
            'multiple_files' => ['nullable'],
            'multiple_files.*' => ['nullable'],
        ];
    }
}
