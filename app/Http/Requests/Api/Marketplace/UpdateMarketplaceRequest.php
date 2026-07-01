<?php

namespace App\Http\Requests\Api\Marketplace;

use App\Http\Requests\Api\ApiFormRequest;
use App\Http\Requests\Api\Marketplace\Concerns\ValidatesMarketplacePayload;
use App\Models\Marketplace;
use Illuminate\Support\Facades\Gate;

class UpdateMarketplaceRequest extends ApiFormRequest
{
    use ValidatesMarketplacePayload;

    public function authorize(): bool
    {
        if ($this->skipValidationForLegacyGuestFlow() || ! $this->user()) {
            return true;
        }

        $marketplace = Marketplace::find($this->route('id'));

        return $marketplace === null || Gate::allows('update', $marketplace);
    }

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
