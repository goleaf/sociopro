<?php

namespace App\Http\Requests\Api\Marketplace;

use App\Enums\ApiTokenAbility;
use App\Http\Requests\Api\ApiFormRequest;
use App\Models\Marketplace;
use Illuminate\Support\Facades\Gate;

class DestroyMarketplaceRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->bearerTokenUser();

        if ($this->skipValidationForLegacyGuestFlow() || ! $user) {
            return true;
        }

        $marketplace = Marketplace::find($this->route('product_id'));

        return $marketplace === null || (
            $this->bearerTokenCan(ApiTokenAbility::MarketplaceDelete)
            && Gate::forUser($user)->allows('delete', $marketplace)
        );
    }

    /**
     * @return array<string, never>
     */
    public function rules(): array
    {
        return [];
    }
}
