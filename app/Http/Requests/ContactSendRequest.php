<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactSendRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'subject' => ['required', 'string', 'max:150'],
            'details' => ['required', 'string', 'max:5000'],
        ];
    }
}
