<?php

namespace App\Actions\Pages;

use App\Enums\MediaFileType;
use App\Models\Albums;
use App\Models\Comments;
use App\Models\MediaFile;
use App\Models\Page;
use App\Models\Posts;
use App\Models\User;
use App\Queries\FriendshipsQuery;
use App\Queries\Pages\PageCardsQuery;
use Illuminate\Support\Collection;

class BuildPageProfileViewDataAction
{
    public function __construct(private readonly PageCardsQuery $pageCards) {}

    /**
     * @return array<string, mixed>
     */
    public function timeline(User $viewer, int|string $pageId): array
    {
        $page = $this->pageForViewer($viewer, $pageId);
        $friendIds = FriendshipsQuery::acceptedFriendIdsForUser($viewer);

        return [
            'all_videos' => $this->mediaForPage($page, MediaFileType::Video, 20),
            'all_photos' => $this->mixedMediaForPage($page, 30),
            'posts' => $this->timelinePosts($page),
            'comments' => $this->rootComments($page),
            'suggestedpages' => $this->suggestedPages($viewer, $friendIds),
            'page' => $page,
            'pageIntro' => ellipsis($page->description ?? '', 500),
            'friendships' => FriendshipsQuery::importantForUser($viewer)
                ->take(15)
                ->get(),
            'view_path' => 'frontend.pages.page-timeline',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function photos(User $viewer, int|string $pageId): array
    {
        $page = $this->pageForViewer($viewer, $pageId);
        $friendIds = FriendshipsQuery::acceptedFriendIdsForUser($viewer);

        return [
            'all_videos' => $this->mediaForPage($page, MediaFileType::Video, 20),
            'all_photos' => $this->mediaForPage($page, MediaFileType::Image, 20),
            'all_albums' => Albums::where('page_id', $page->id)
                ->take(6)
                ->orderBy('id', 'DESC')
                ->get(),
            'page_identifire' => 'page',
            'page' => $page,
            'pageIntro' => ellipsis($page->description ?? '', 500),
            'suggestedpages' => $this->suggestedPages($viewer, $friendIds),
            'view_path' => 'frontend.pages.photos',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function videos(User $viewer, int|string $pageId): array
    {
        $page = $this->pageForViewer($viewer, $pageId);
        $friendIds = FriendshipsQuery::acceptedFriendIdsForUser($viewer);

        return [
            'all_videos' => $this->mediaForPage($page, MediaFileType::Video, 20),
            'page' => $page,
            'pageIntro' => ellipsis($page->description ?? '', 500),
            'all_photos' => $this->mediaForPage($page, MediaFileType::Image, 20),
            'suggestedpages' => $this->suggestedPages($viewer, $friendIds),
            'view_path' => 'frontend.pages.video',
        ];
    }

    private function pageForViewer(User $viewer, int|string $pageId): Page
    {
        return $this->pageCards
            ->profileForViewer((int) $viewer->id)
            ->findOrFail($pageId);
    }

    /**
     * @return Collection<int, MediaFile>
     */
    private function mediaForPage(Page $page, MediaFileType $type, int $limit): Collection
    {
        return MediaFile::where('page_id', $page->id)
            ->ofType($type)
            ->take($limit)
            ->orderBy('id', 'DESC')
            ->get();
    }

    /**
     * @return Collection<int, MediaFile>
     */
    private function mixedMediaForPage(Page $page, int $limit): Collection
    {
        return MediaFile::where('page_id', $page->id)
            ->take($limit)
            ->orderBy('id', 'DESC')
            ->get();
    }

    /**
     * @return Collection<int, Posts>
     */
    private function timelinePosts(Page $page): Collection
    {
        return Posts::notPrivate()
            ->forPublisher('page', $page->id)
            ->active()
            ->join('pages', 'posts.publisher_id', '=', 'pages.id')
            ->select('posts.*', 'pages.id', 'pages.title', 'pages.logo', 'posts.created_at as created_at')
            ->orderBy('posts.post_id', 'DESC')
            ->get();
    }

    /**
     * @return Collection<int, Comments>
     */
    private function rootComments(Page $page): Collection
    {
        return Comments::query()
            ->select(['comment_id', 'user_id', 'parent_id', 'is_type', 'id_of_type', 'description', 'user_reacts', 'created_at', 'updated_at'])
            ->with(['user:id,name,photo'])
            ->where('is_type', 'page')
            ->where('id_of_type', $page->id)
            ->where('parent_id', 0)
            ->orderByDesc('comment_id')
            ->take(1)
            ->get()
            ->each(function (Comments $comment): void {
                $comment->setAttribute('name', $comment->user?->name);
                $comment->setAttribute('photo', $comment->user?->photo);
            });
    }

    /**
     * @param  list<int>  $friendIds
     * @return Collection<int, Page>
     */
    private function suggestedPages(User $viewer, array $friendIds): Collection
    {
        return $this->pageCards
            ->suggestedForViewer((int) $viewer->id, $friendIds)
            ->limit(1)
            ->get();
    }
}
