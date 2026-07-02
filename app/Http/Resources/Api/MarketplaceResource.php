<?php

namespace App\Http\Resources\Api;

use App\Models\Marketplace;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use LogicException;

/**
 * @mixin Marketplace
 */
class MarketplaceResource extends JsonResource
{
    /**
     * @param  Collection<int, true>  $savedProductIds
     */
    public function __construct(
        $resource,
        private readonly int $userId,
        private readonly Collection $savedProductIds,
        private readonly int $messageThreadId
    ) {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $marketplace = $this->marketplace();
        $user = $marketplace->getUser;
        $category = $marketplace->getCategory;
        $brand = $marketplace->getBrand;
        $currency = $marketplace->getCurrency;

        return [
            'id' => $marketplace->id,
            'thrade' => $this->messageThreadId,
            'user_id' => $marketplace->user_id,
            'user' => $user->name,
            'photo' => $this->userImageUrl($user->photo),
            'title' => $marketplace->title,
            'price' => $marketplace->price,
            'category_id' => $marketplace->category,
            'status_id' => $marketplace->status,
            'brand_id' => $marketplace->brand,
            'currency_id' => $marketplace->currency_id,
            'condition' => $marketplace->condition,
            'status' => $marketplace->status,
            'category' => $category->name,
            'brand' => $brand->name,
            'currency' => $currency->name,
            'is_Saved' => $this->savedProductIds->has((int) $marketplace->id) ? 'saved' : 'not_saved',
            'my_product' => $marketplace->user_id == $this->userId ? 'my_product' : 'not_my_product',
            'description' => $marketplace->description,
            'location' => $marketplace->location != null ? $marketplace->location : '',
            'coverphoto' => get_group_event_photos($marketplace->image, 'coverphoto', 'marketplace'),
            'created_at' => date('d-m-Y', strtotime($marketplace->created_at)),
        ];
    }

    private function marketplace(): Marketplace
    {
        if (! $this->resource instanceof Marketplace) {
            throw new LogicException('MarketplaceResource must wrap a Marketplace model.');
        }

        return $this->resource;
    }

    private function userImageUrl(?string $fileName, string $optimized = ''): string
    {
        $optimized = trim($optimized, '/');
        $optimizedPath = $optimized === '' ? '' : $optimized.'/';
        $fileName = $fileName ?: 'default.png';

        if (str_contains($fileName, 'https://')) {
            return $fileName;
        }

        $path = 'public/storage/userimage/'.$optimizedPath.$fileName;

        if (File::exists($path) && is_file($path)) {
            return url($path);
        }

        return url('public/storage/userimage/default.png');
    }
}
