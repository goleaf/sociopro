<?php

namespace App\Http\Requests\Api\Posts;

use App\Rules\PostMediaFile;

class StorePostRequest extends PostPayloadRequest
{
    protected function postMediaFileRule(): PostMediaFile
    {
        return PostMediaFile::forCreate();
    }
}
