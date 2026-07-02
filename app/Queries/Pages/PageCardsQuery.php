<?php

namespace App\Queries\Pages;

use App\Models\Page;
use App\Models\PageLike;
use Illuminate\Database\Eloquent\Builder;

class PageCardsQuery
{
    /**
     * @return Builder<Page>
     */
    public function forViewer(int $userId): Builder
    {
        return Page::query()
            ->select([
                'id',
                'user_id',
                'title',
                'category_id',
                'logo',
                'coverphoto',
                'description',
                'job',
                'lifestyle',
                'location',
                'status',
                'created_at',
                'updated_at',
            ])
            ->withCount('likedByUsers')
            ->withExists([
                'likedByUsers as liked_by_current_user' => fn (Builder $query): Builder => $query
                    ->where('users.id', $userId),
            ]);
    }

    /**
     * @return Builder<Page>
     */
    public function profileForViewer(int $userId): Builder
    {
        return $this->forViewer($userId)
            ->with(['getCategory:id,name'])
            ->withCount('posts');
    }

    /**
     * @param  list<int>  $friendIds
     * @return Builder<Page>
     */
    public function suggestedForViewer(int $userId, array $friendIds): Builder
    {
        return $this->forViewer($userId)
            ->whereIn('id', PageLike::query()
                ->select('page_id')
                ->whereIn('user_id', $friendIds)
                ->where('user_id', '!=', $userId))
            ->whereNotIn('id', PageLike::query()
                ->select('page_id')
                ->where('user_id', $userId))
            ->orderByDesc('id');
    }
}
