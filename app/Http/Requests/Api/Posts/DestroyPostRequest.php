<?php

namespace App\Http\Requests\Api\Posts;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Posts;
use Illuminate\Support\Facades\Gate;

class DestroyPostRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->bearerTokenUser();

        if ($this->skipValidationForLegacyGuestFlow() || ! $user) {
            return true;
        }

        $post = Posts::query()->find($this->route('id'));

        return $post === null || Gate::forUser($user)->allows('delete', $post);
    }

    /**
     * @return array<string, never>
     */
    public function rules(): array
    {
        return [];
    }
}
