<?php

namespace App\Http\Requests\Install;

use Illuminate\Foundation\Http\FormRequest;

class PrepareDatabaseConnectionRequest extends FormRequest
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
            'db_connection' => ['required', 'string', 'in:sqlite,mysql'],
            'sqlite_path' => ['nullable', 'string', 'max:1024'],
            'hostname' => ['nullable', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'dbname' => ['nullable', 'string', 'max:255'],
        ];
    }
}
