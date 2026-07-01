<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Comments;
use App\Models\User;
use App\ViewModels\BladeViewData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class AggregateQueryOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_application_code_does_not_load_collections_only_to_aggregate(): void
    {
        $offenders = [];

        foreach ($this->aggregateCandidateFiles() as $file) {
            $contents = file_get_contents($file->getPathname());

            if (! is_string($contents)) {
                continue;
            }

            if (preg_match('/(?:->|::)\s*(?:get|all)\s*\([^)]*\)\s*->\s*(?:count|sum|avg|average|min|max|isEmpty|isNotEmpty|contains|groupBy)\s*\(/', $contents) === 1) {
                $offenders[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Use database-level aggregates, exists(), withCount(), withSum(), or a precomputed view-model value instead of loading a collection only to aggregate it.'
        );
    }

    public function test_root_comment_count_excludes_replies_for_comment_previews(): void
    {
        $author = User::factory()->create();
        $blog = new Blog;
        $blog->forceFill([
            'user_id' => $author->id,
            'title' => 'Aggregate-safe blog',
            'description' => 'Fixture blog.',
            'tag' => json_encode([]),
            'view' => json_encode([]),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ])->save();

        $rootComment = $this->comment($author, [
            'is_type' => 'blog',
            'id_of_type' => $blog->id,
            'parent_id' => 0,
        ]);
        $this->comment($author, [
            'is_type' => 'blog',
            'id_of_type' => $blog->id,
            'parent_id' => $rootComment->comment_id,
        ]);
        $this->comment($author, [
            'is_type' => 'post',
            'id_of_type' => $blog->id,
            'parent_id' => 0,
        ]);

        $this->assertSame(1, app(BladeViewData::class)->rootCommentCount($blog, 'blog'));
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function aggregateCandidateFiles(): iterable
    {
        foreach ([app_path(), base_path('routes'), resource_path('views')] as $root) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

            foreach ($iterator as $file) {
                if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                    continue;
                }

                if ($file->getExtension() === 'php') {
                    yield $file;
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function comment(User $user, array $overrides = []): Comments
    {
        $comment = new Comments;
        $comment->forceFill(array_merge([
            'user_id' => $user->id,
            'parent_id' => 0,
            'is_type' => 'blog',
            'id_of_type' => 1,
            'description' => 'Aggregate fixture comment.',
            'user_reacts' => json_encode([]),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ], $overrides))->save();

        return $comment;
    }
}
