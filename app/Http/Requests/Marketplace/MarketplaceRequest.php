<?php

namespace App\Http\Requests\Marketplace;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class MarketplaceRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'validationError' => $validator->errors()->toArray(),
        ]));
    }
}
