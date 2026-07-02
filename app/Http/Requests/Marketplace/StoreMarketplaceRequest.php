<?php

namespace App\Http\Requests\Marketplace;

use App\Models\Marketplace;
use Illuminate\Support\Facades\Gate;

class StoreMarketplaceRequest extends MarketplaceRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && Gate::allows('create', Marketplace::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|max:255',
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'location' => 'required',
            'condition' => 'required',
        ];
    }
}
