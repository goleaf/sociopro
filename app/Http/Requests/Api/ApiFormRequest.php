<?php

namespace App\Http\Requests\Api;

use App\Enums\ApiErrorCode;
use App\Enums\ApiTokenAbility;
use App\Models\User;
use App\Support\Api\ApiErrorResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            ApiErrorResponse::make(
                code: ApiErrorCode::Validation,
                message: 'Validation failed',
                details: $validator->errors()->toArray(),
                transportStatus: Response::HTTP_OK
            )
        );
    }

    protected function skipValidationForLegacyGuestFlow(): bool
    {
        return ! $this->bearerToken();
    }

    protected function bearerTokenUser(): ?User
    {
        if (! $this->bearerToken()) {
            return null;
        }

        $user = auth('sanctum')->user();
        if (! $user instanceof User) {
            return null;
        }

        $token = $user->currentAccessToken();
        if (! $token instanceof PersonalAccessToken) {
            return null;
        }

        if ($token->getAttribute('tokenable_type') !== $user->getMorphClass() || (int) $token->getAttribute('tokenable_id') !== (int) $user->getKey()) {
            return null;
        }

        return $user;
    }

    protected function bearerTokenCan(ApiTokenAbility $ability): bool
    {
        return $this->bearerTokenUser()?->tokenCan($ability->value) === true;
    }
}
