<?php

namespace App\Http\Requests\Api\Marketplace;

use App\Http\Requests\Api\ApiFormRequest;
use Illuminate\Validation\Rule;

class FilterMarketplaceRequest extends ApiFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        if ($this->skipValidationForLegacyGuestFlow()) {
            return [];
        }

        return [
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'condition' => ['nullable', 'string', Rule::in(['new', 'used'])],
            'min' => ['nullable', 'numeric', 'min:0'],
            'max' => ['nullable', 'numeric', 'min:0', 'gte:min'],
            'brand' => ['nullable', 'integer', 'exists:brands,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', Rule::in(['id', 'created_at', 'price', 'title'])],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'filters' => ['nullable', 'array'],
            'filters.search' => ['nullable', 'string', 'max:255'],
            'filters.category' => ['nullable', 'integer', 'exists:categories,id'],
            'filters.condition' => ['nullable', 'string', Rule::in(['new', 'used'])],
            'filters.brand' => ['nullable', 'integer', 'exists:brands,id'],
            'filters.location' => ['nullable', 'string', 'max:255'],
            'filters.price' => ['nullable', 'array'],
            'filters.price.min' => ['nullable', 'numeric', 'min:0'],
            'filters.price.max' => ['nullable', 'numeric', 'min:0', 'gte:filters.price.min'],
            'filters.created_between' => ['nullable', 'array'],
            'filters.created_between.from' => ['nullable', 'date'],
            'filters.created_between.to' => ['nullable', 'date', 'after_or_equal:filters.created_between.from'],
        ];
    }

    /**
     * @return array{
     *     search: mixed,
     *     category: mixed,
     *     condition: mixed,
     *     min: mixed,
     *     max: mixed,
     *     brand: mixed,
     *     location: mixed,
     *     sort: string,
     *     direction: string,
     *     page: int|null,
     *     per_page: int|null,
     *     date_from: mixed,
     *     date_to: mixed
     * }
     */
    public function filters(): array
    {
        $nested = $this->input('filters', []);
        $nested = is_array($nested) ? $nested : [];

        $price = $nested['price'] ?? [];
        $price = is_array($price) ? $price : [];

        $createdBetween = $nested['created_between'] ?? [];
        $createdBetween = is_array($createdBetween) ? $createdBetween : [];

        return [
            'search' => $this->input('search', $nested['search'] ?? null),
            'category' => $this->input('category', $nested['category'] ?? null),
            'condition' => $this->input('condition', $nested['condition'] ?? null),
            'min' => $this->input('min', $price['min'] ?? null),
            'max' => $this->input('max', $price['max'] ?? null),
            'brand' => $this->input('brand', $nested['brand'] ?? null),
            'location' => $this->input('location', $nested['location'] ?? null),
            'sort' => (string) $this->input('sort', 'id'),
            'direction' => (string) $this->input('direction', 'desc'),
            'page' => $this->filled('page') ? $this->integer('page') : null,
            'per_page' => $this->filled('per_page') ? $this->integer('per_page') : null,
            'date_from' => $this->input('date_from', $createdBetween['from'] ?? null),
            'date_to' => $this->input('date_to', $createdBetween['to'] ?? null),
        ];
    }
}
