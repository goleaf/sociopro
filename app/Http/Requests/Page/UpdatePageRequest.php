<?php

namespace App\Http\Requests\Page;

class UpdatePageRequest extends StorePageRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }
}
