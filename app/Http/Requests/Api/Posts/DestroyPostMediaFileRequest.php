<?php

namespace App\Http\Requests\Api\Posts;

use App\Http\Requests\Api\ApiFormRequest;
use App\Models\MediaFile;

class DestroyPostMediaFileRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->bearerTokenUser();

        if ($this->skipValidationForLegacyGuestFlow() || ! $user) {
            return true;
        }

        $mediaFile = MediaFile::query()->find($this->route('id'));

        return $mediaFile === null || (int) $mediaFile->user_id === (int) $user->id;
    }

    /**
     * @return array<string, never>
     */
    public function rules(): array
    {
        return [];
    }
}
