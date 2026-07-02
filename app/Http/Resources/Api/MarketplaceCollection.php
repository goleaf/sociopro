<?php

namespace App\Http\Resources\Api;

use App\Models\Marketplace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class MarketplaceCollection extends ResourceCollection
{
    public static $wrap = null;

    /**
     * @param  Paginator<int, Marketplace>  $resource
     * @param  Collection<int, true>  $savedProductIds
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        $resource,
        private readonly int $userId,
        private readonly Collection $savedProductIds,
        private readonly int $messageThreadId,
        private readonly array $filters,
        private readonly bool $includePagination
    ) {
        parent::__construct($resource);
    }

    /**
     * @return list<array<string, mixed>>|array{
     *     marketplaces: list<array<string, mixed>>,
     *     links: array<string, string|null>,
     *     meta: array<string, mixed>
     * }
     */
    public function toArray(Request $request): array
    {
        $marketplaces = $this->collection
            ->map(fn (Marketplace $marketplace): array => (new MarketplaceResource(
                $marketplace,
                $this->userId,
                $this->savedProductIds,
                $this->messageThreadId
            ))->resolve($request))
            ->values()
            ->all();

        if (! $this->includePagination) {
            return $marketplaces;
        }

        return [
            'marketplaces' => $marketplaces,
            'links' => $this->links(),
            'meta' => $this->meta(),
        ];
    }

    public function toResponse($request): JsonResponse
    {
        $response = new JsonResponse(
            data: $this->resolve($request),
            json: false
        );

        $this->withResponse($request, $response);

        return $response;
    }

    public function withResponse(Request $request, JsonResponse $response): void
    {
        $paginator = $this->paginator();

        $response->headers->set('X-Pagination-Current-Page', (string) $paginator->currentPage());
        $response->headers->set('X-Pagination-Per-Page', (string) $paginator->perPage());
        $response->headers->set('X-Pagination-Has-More-Pages', $paginator->hasMorePages() ? 'true' : 'false');

        if ($paginator->nextPageUrl() !== null) {
            $response->headers->set('X-Pagination-Next-Page-Url', $paginator->nextPageUrl());
        }

        if ($paginator->previousPageUrl() !== null) {
            $response->headers->set('X-Pagination-Prev-Page-Url', $paginator->previousPageUrl());
        }

        $linkHeader = $this->linkHeader();

        if ($linkHeader !== '') {
            $response->headers->set('Link', $linkHeader);
        }
    }

    /**
     * @return array<string, string|null>
     */
    private function links(): array
    {
        $paginator = $this->paginator();

        return [
            'first' => $paginator->url(1),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function meta(): array
    {
        $paginator = $this->paginator();

        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'has_more_pages' => $paginator->hasMorePages(),
            'sort' => $this->filters['sort'] ?? 'id',
            'direction' => $this->filters['direction'] ?? 'desc',
        ];
    }

    private function linkHeader(): string
    {
        $links = [];

        foreach ($this->links() as $rel => $url) {
            if ($url === null) {
                continue;
            }

            $links[] = '<'.$url.'>; rel="'.$rel.'"';
        }

        return implode(', ', $links);
    }

    protected function collects(): ?string
    {
        return null;
    }

    /**
     * @return Paginator<int, Marketplace>
     */
    private function paginator(): Paginator
    {
        /** @var Paginator<int, Marketplace> $resource */
        $resource = $this->resource;

        return $resource;
    }
}
