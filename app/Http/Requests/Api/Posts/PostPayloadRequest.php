<?php

namespace App\Http\Requests\Api\Posts;

use App\Http\Requests\Api\ApiFormRequest;
use App\Rules\PostMediaFile;
use App\Support\Validation\NestedFileValidationErrors;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class PostPayloadRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->skipValidationForLegacyGuestFlow()) {
            return [];
        }

        return [
            'multiple_files' => ['nullable', 'array'],
            'multiple_files.*' => [$this->postMediaFileRule()],
        ];
    }

    abstract protected function postMediaFileRule(): PostMediaFile;

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'validationError' => NestedFileValidationErrors::collapse(
                $validator->getMessageBag()->toArray(),
                'multiple_files'
            ),
        ]));
    }
}
