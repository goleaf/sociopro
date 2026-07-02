<?php

namespace App\Http\Requests\Api\Marketplace\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesMarketplacePayload
{
    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'condition.in' => __('marketplace.validation.messages.in_values'),
            'status.in' => __('marketplace.validation.messages.in_values'),
            'multiple_files.*.image' => __('marketplace.validation.messages.image'),
            'multiple_files.*.mimes' => __('marketplace.validation.messages.mimes'),
            'multiple_files.*.extensions' => __('marketplace.validation.messages.extensions'),
            'multiple_files.*.max' => __('marketplace.validation.messages.max_file'),
            'multiple_files.*.dimensions' => __('marketplace.validation.messages.dimensions'),
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
    protected function marketplacePayloadRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:9999999999.99'],
            'location' => ['required', 'string', 'max:255'],
            'category' => ['required', 'integer', 'exists:categories,id'],
            'condition' => ['required', 'string', Rule::in(['new', 'used'])],
            'status' => ['required', 'integer', Rule::in([0, 1])],
            'brand' => ['required', 'integer', 'exists:brands,id'],
            'currency' => ['nullable', 'integer', 'exists:currencies,id'],
            'buy_link' => ['nullable', 'url', 'max:2048'],
            'description' => ['nullable', 'string', 'max:5000'],
            'multiple_files' => ['nullable', 'array', 'max:10'],
            'multiple_files.*' => [
                'file',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'extensions:jpeg,jpg,png,gif,webp',
                'max:5120',
                'dimensions:max_width=4096,max_height=4096',
            ],
        ];
    }
}
