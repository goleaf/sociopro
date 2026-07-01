<?php

namespace App\Http\Requests\Api;

use App\Enums\ApiTokenAbility;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Laravel\Sanctum\PersonalAccessToken;

abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'validationError' => $validator->errors()->toArray(),
        ]));
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
