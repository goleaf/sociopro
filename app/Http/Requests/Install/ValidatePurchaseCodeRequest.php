<?php

namespace App\Http\Requests\Install;

use Illuminate\Foundation\Http\FormRequest;

class ValidatePurchaseCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchase_code' => ['required', 'string', 'max:255'],
        ];
    }
}
