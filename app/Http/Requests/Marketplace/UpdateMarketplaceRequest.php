<?php

namespace App\Http\Requests\Marketplace;

class UpdateMarketplaceRequest extends MarketplaceRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|max:255',
            'price' => 'required',
            'location' => 'required',
            'condition' => 'required',
            'status' => 'required',
        ];
    }
}
