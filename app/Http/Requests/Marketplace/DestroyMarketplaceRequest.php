<?php

namespace App\Http\Requests\Marketplace;

class DestroyMarketplaceRequest extends MarketplaceRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, never>
     */
    public function rules(): array
    {
        return [];
    }
}
