<?php

namespace App\Http\Requests\Api\Posts;

use App\Models\Posts;
use App\Rules\PostMediaFile;
use Illuminate\Support\Facades\Gate;

class UpdatePostRequest extends PostPayloadRequest
{
    public function authorize(): bool
    {
        $user = $this->bearerTokenUser();

        if ($this->skipValidationForLegacyGuestFlow() || ! $user) {
            return true;
        }

        $post = Posts::query()->find($this->route('id'));

        return $post === null || Gate::forUser($user)->allows('update', $post);
    }

    protected function postMediaFileRule(): PostMediaFile
    {
        return PostMediaFile::forUpdate();
    }
}
