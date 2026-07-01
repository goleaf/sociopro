<?php

namespace App\Http\Requests\Api\Marketplace;

use App\Enums\ApiTokenAbility;
use App\Http\Requests\Api\ApiFormRequest;
use App\Http\Requests\Api\Marketplace\Concerns\ValidatesMarketplacePayload;
use App\Models\Marketplace;
use Illuminate\Support\Facades\Gate;

class StoreMarketplaceRequest extends ApiFormRequest
{
    use ValidatesMarketplacePayload;

    public function authorize(): bool
    {
        $user = $this->bearerTokenUser();

        if ($this->skipValidationForLegacyGuestFlow() || ! $user) {
            return true;
        }

        return $this->bearerTokenCan(ApiTokenAbility::MarketplaceCreate)
            && Gate::forUser($user)->allows('create', Marketplace::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->skipValidationForLegacyGuestFlow()) {
            return [];
        }

        return $this->marketplacePayloadRules();
    }
}
