<?php

namespace App\Http\Requests\Api\Marketplace;

use App\Http\Requests\Api\ApiFormRequest;
use App\Http\Requests\Api\Marketplace\Concerns\ValidatesMarketplacePayload;

class UpdateMarketplaceRequest extends ApiFormRequest
{
    use ValidatesMarketplacePayload;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->skipValidationForLegacyGuestFlow()) {
            return [];
        }

        return [
            'id' => ['required', 'integer', 'exists:marketplaces,id'],
            ...$this->marketplacePayloadRules(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return [
            ...parent::validationData(),
            'id' => $this->route('id'),
        ];
    }
}
