<?php

namespace App\Http\Requests\Api\Marketplace;

use App\Http\Requests\Api\ApiFormRequest;
use App\Support\Validation\DateTimeRules;
use Illuminate\Validation\Rule;

class FilterMarketplaceRequest extends ApiFormRequest
{
    public const DEFAULT_PAGE = 1;

    public const DEFAULT_PER_PAGE = 20;

    public const MAX_PER_PAGE = 100;

    /**
     * @var list<string>
     */
    private const ALLOWED_SORT_FIELDS = ['id', 'created_at', 'price', 'title'];

    /**
     * @var list<string>
     */
    private const ALLOWED_DIRECTIONS = ['asc', 'desc'];

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'condition.in' => __('marketplace.validation.messages.in_values'),
            'filters.condition.in' => __('marketplace.validation.messages.in_values'),
            'sort.in' => __('marketplace.validation.messages.in_values'),
            'direction.in' => __('marketplace.validation.messages.in_values'),
            'per_page.max' => __('marketplace.validation.messages.per_page_max'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = trans('marketplace.validation.attributes');

        return is_array($attributes) ? $attributes : [];
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
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'condition' => ['nullable', 'string', Rule::in(['new', 'used'])],
            'min' => ['nullable', 'numeric', 'min:0'],
            'max' => ['nullable', 'numeric', 'min:0', 'gte:min'],
            'brand' => ['nullable', 'integer', 'exists:brands,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', Rule::in(self::ALLOWED_SORT_FIELDS)],
            'direction' => ['nullable', 'string', Rule::in(self::ALLOWED_DIRECTIONS)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.self::MAX_PER_PAGE],
            'date_from' => DateTimeRules::nullableBrowserDate(),
            'date_to' => ['nullable', 'date_format:'.DateTimeRules::BROWSER_DATE_FORMAT, 'after_or_equal:date_from'],
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
            'filters.created_between.from' => DateTimeRules::nullableBrowserDate(),
            'filters.created_between.to' => ['nullable', 'date_format:'.DateTimeRules::BROWSER_DATE_FORMAT, 'after_or_equal:filters.created_between.from'],
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
     *     page: int,
     *     per_page: int,
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
            'sort' => $this->sortField(),
            'direction' => $this->sortDirection(),
            'page' => $this->positiveInteger('page', self::DEFAULT_PAGE),
            'per_page' => $this->positiveInteger('per_page', self::DEFAULT_PER_PAGE, self::MAX_PER_PAGE),
            'date_from' => $this->input('date_from', $createdBetween['from'] ?? null),
            'date_to' => $this->input('date_to', $createdBetween['to'] ?? null),
        ];
    }

    protected function prepareForValidation(): void
    {
        $direction = $this->input('direction');

        if (is_string($direction)) {
            $this->merge([
                'direction' => strtolower($direction),
            ]);
        }
    }

    private function sortField(): string
    {
        $sort = $this->input('sort', 'id');

        return is_string($sort) && in_array($sort, self::ALLOWED_SORT_FIELDS, true)
            ? $sort
            : 'id';
    }

    private function sortDirection(): string
    {
        $direction = $this->input('direction', 'desc');
        $direction = is_string($direction) ? strtolower($direction) : 'desc';

        return in_array($direction, self::ALLOWED_DIRECTIONS, true)
            ? $direction
            : 'desc';
    }

    private function positiveInteger(string $key, int $default, ?int $maximum = null): int
    {
        if (! $this->filled($key)) {
            return $default;
        }

        $value = $this->integer($key);

        if ($value < 1) {
            return $default;
        }

        if ($maximum !== null && $value > $maximum) {
            return $maximum;
        }

        return $value;
    }
}
