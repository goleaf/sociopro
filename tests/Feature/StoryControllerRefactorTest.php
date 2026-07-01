<?php

namespace Tests\Feature;

use App\Models\Stories;
use App\Models\User;
use App\Queries\StoriesQuery;
use Tests\TestCase;

class StoryControllerRefactorTest extends TestCase
{
    public function test_story_controller_does_not_use_raw_table_queries(): void
    {
        $contents = file_get_contents(app_path('Http/Controllers/StoryController.php'));

        $this->assertStringNotContainsString('DB::table', $contents);
        $this->assertStringNotContainsString('use DB;', $contents);
    }

    public function test_story_visibility_query_preserves_friend_owner_status_and_age_rules(): void
    {
        $viewer = User::factory()->create(['friends' => json_encode([])]);
        $friend = User::factory()->create(['friends' => json_encode([(int) $viewer->id])]);
        $stranger = User::factory()->create(['friends' => json_encode([])]);

        $oldFriendStory = $this->storyFor($friend, [
            'privacy' => 'public',
            'created_at' => time() - 90000,
        ]);
        $inactiveFriendStory = $this->storyFor($friend, [
            'privacy' => 'public',
            'status' => 'inactive',
        ]);
        $privateFriendStory = $this->storyFor($friend, ['privacy' => 'private']);
        $strangerStory = $this->storyFor($stranger, ['privacy' => 'public']);
        $friendStory = $this->storyFor($friend, ['privacy' => 'friends']);
        $ownPrivateStory = $this->storyFor($viewer, ['privacy' => 'private']);

        $storyIds = StoriesQuery::visibleFor($viewer)
            ->pluck('story_id')
            ->all();

        $this->assertSame([
            $ownPrivateStory->story_id,
            $friendStory->story_id,
        ], $storyIds);
        $this->assertNotContains($privateFriendStory->story_id, $storyIds);
        $this->assertNotContains($strangerStory->story_id, $storyIds);
        $this->assertNotContains($inactiveFriendStory->story_id, $storyIds);
        $this->assertNotContains($oldFriendStory->story_id, $storyIds);
    }

    public function test_story_details_returns_not_found_for_missing_story(): void
    {
        $viewer = User::factory()->create([
            'friends' => json_encode([]),
            'status' => '1',
            'user_role' => 'general',
        ]);

        $this->actingAs($viewer)
            ->get(route('story_details', ['story_id' => 999999]))
            ->assertNotFound();
    }

    public function test_single_story_details_returns_not_found_for_missing_story(): void
    {
        $viewer = User::factory()->create([
            'friends' => json_encode([]),
            'status' => '1',
            'user_role' => 'general',
        ]);

        $this->actingAs($viewer)
            ->get(route('single_story_details', ['story_id' => 999999]))
            ->assertNotFound();
    }

    private function storyFor(User $user, array $overrides = []): Stories
    {
        return Stories::create($overrides + [
            'user_id' => $user->id,
            'publisher' => 'user',
            'publisher_id' => $user->id,
            'privacy' => 'public',
            'content_type' => 'text',
            'description' => json_encode(['color' => '000000', 'bg-color' => 'ffffff', 'text' => 'Story text']),
            'status' => 'active',
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }
}
