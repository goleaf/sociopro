<?php

namespace App\Http\Requests\Install;

use Illuminate\Foundation\Http\FormRequest;

class FinalizeInstallationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if (! $this->isMethod('post')) {
            return [];
        }

        return [
            'system_name' => ['required', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'string'],
            'admin_address' => ['required', 'string', 'max:300'],
            'admin_phone' => ['required', 'string', 'max:100'],
            'timezone' => ['required', 'timezone'],
        ];
    }
}
