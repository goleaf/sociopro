<?php

namespace App\Queries\Marketplace;

use App\Models\Marketplace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class MarketplaceProductsQuery
{
    /**
     * @param  array{
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
     * }  $filters
     * @return Collection<int, Marketplace>
     */
    public function handle(array $filters): Collection
    {
        $query = Marketplace::query()
            ->with(['getUser', 'getCategory', 'getBrand', 'getCurrency'])
            ->where('status', 1)
            ->orderBy($filters['sort'], $filters['direction']);

        $this->applySearchAndLocation($query, $filters['search'], $filters['location']);
        $this->applyPriceRange($query, $filters['min'], $filters['max']);
        $this->applyExactFilter($query, 'condition', $filters['condition']);
        $this->applyExactFilter($query, 'category', $filters['category']);
        $this->applyExactFilter($query, 'brand', $filters['brand']);
        $this->applyDateRange($query, $filters['date_from'], $filters['date_to']);

        return $query
            ->forPage($filters['page'], $filters['per_page'])
            ->get();
    }

    private function applySearchAndLocation(Builder $query, mixed $search, mixed $location): void
    {
        if (empty($search) && empty($location)) {
            return;
        }

        $query->where(function (Builder $query) use ($search, $location): void {
            if (! empty($search)) {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%');
                });
            }

            if (! empty($location)) {
                $query->orWhere('location', 'like', '%'.$location.'%');
            }
        });
    }

    private function applyPriceRange(Builder $query, mixed $min, mixed $max): void
    {
        if (empty($min) && empty($max)) {
            return;
        }

        $query->where(function (Builder $query) use ($min, $max): void {
            if (! empty($min)) {
                $query->where('price', '>=', $min);
            }

            if (! empty($max)) {
                $query->where('price', '<=', $max);
            }
        });
    }

    private function applyExactFilter(Builder $query, string $column, mixed $value): void
    {
        if (! empty($value)) {
            $query->where($column, $value);
        }
    }

    private function applyDateRange(Builder $query, mixed $dateFrom, mixed $dateTo): void
    {
        if (! empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if (! empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
    }
}
