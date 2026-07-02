<?php

namespace App\Queries\Marketplace;

use App\Models\Marketplace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use LogicException;

final class MarketplaceProductsQuery
{
    /**
     * @var list<string>
     */
    private const ALLOWED_SORT_FIELDS = ['id', 'created_at', 'price', 'title'];

    /**
     * @var list<string>
     */
    private const ALLOWED_DIRECTIONS = ['asc', 'desc'];

    /**
     * @var list<string>
     */
    private const LIKE_SEARCH_COLUMNS = ['title', 'description', 'location'];

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
        return $this->query($filters)
            ->forPage($filters['page'], $filters['per_page'])
            ->get();
    }

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
     * @return Paginator<int, Marketplace>
     */
    public function paginate(array $filters): Paginator
    {
        return $this->query($filters)
            ->simplePaginate($filters['per_page'], ['*'], 'page', $filters['page']);
    }

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
     * @return Builder<Marketplace>
     */
    private function query(array $filters): Builder
    {
        $query = Marketplace::query()
            ->with(['getUser', 'getCategory', 'getBrand', 'getCurrency'])
            ->where('status', 1);

        $this->applySearchAndLocation($query, $filters['search'], $filters['location']);
        $this->applyPriceRange($query, $filters['min'], $filters['max']);
        $this->applyExactFilter($query, 'condition', $filters['condition']);
        $this->applyExactFilter($query, 'category', $filters['category']);
        $this->applyExactFilter($query, 'brand', $filters['brand']);
        $this->applyDateRange($query, $filters['date_from'], $filters['date_to']);
        $this->applySorting($query, $filters['sort'], $filters['direction']);

        return $query;
    }

    private function applySearchAndLocation(Builder $query, mixed $search, mixed $location): void
    {
        if (empty($search) && empty($location)) {
            return;
        }

        $query->where(function (Builder $query) use ($search, $location): void {
            if (! empty($search)) {
                $query->where(function (Builder $query) use ($search): void {
                    $searchPattern = $this->containsLikePattern($search);

                    $this->whereLikeEscaped($query, 'title', $searchPattern);
                    $this->whereLikeEscaped($query, 'description', $searchPattern, 'or');
                });
            }

            if (! empty($location)) {
                $this->whereLikeEscaped($query, 'location', $this->containsLikePattern($location), 'or');
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

    private function applySorting(Builder $query, mixed $sort, mixed $direction): void
    {
        $sort = is_string($sort) && in_array($sort, self::ALLOWED_SORT_FIELDS, true)
            ? $sort
            : 'id';
        $direction = is_string($direction) && in_array(strtolower($direction), self::ALLOWED_DIRECTIONS, true)
            ? strtolower($direction)
            : 'desc';

        $query->orderBy($sort, $direction);

        if ($sort !== 'id') {
            $query->orderBy('id', $direction);
        }
    }

    private function containsLikePattern(mixed $value): string
    {
        return '%'.strtr((string) $value, [
            '\\' => '\\\\',
            '%' => '\%',
            '_' => '\_',
        ]).'%';
    }

    private function whereLikeEscaped(Builder $query, string $column, string $pattern, string $boolean = 'and'): void
    {
        if (! in_array($column, self::LIKE_SEARCH_COLUMNS, true)) {
            throw new LogicException('Unexpected marketplace search column.');
        }

        $wrappedColumn = $query->getQuery()->getGrammar()->wrap($column);

        // Eloquent has no portable ESCAPE-clause helper; bindings still carry all user input.
        $query->whereRaw("{$wrappedColumn} LIKE ? ESCAPE '\\'", [$pattern], $boolean);
    }
}
