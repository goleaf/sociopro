<?php

namespace Tests\Feature;

use App\Models\Friendships;
use App\Models\User;
use App\Queries\FriendshipsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendshipsQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_accepted_friendships_for_user_ordered_by_importance(): void
    {
        $user = User::factory()->create();
        $firstFriend = User::factory()->create();
        $secondFriend = User::factory()->create();
        $pendingFriend = User::factory()->create();
        $unrelatedUser = User::factory()->create();

        $lowImportance = Friendships::create([
            'requester' => $user->id,
            'accepter' => $firstFriend->id,
            'importance' => 10,
            'is_accepted' => 1,
        ]);
        $highImportance = Friendships::create([
            'requester' => $secondFriend->id,
            'accepter' => $user->id,
            'importance' => 30,
            'is_accepted' => 1,
        ]);
        Friendships::create([
            'requester' => $pendingFriend->id,
            'accepter' => $user->id,
            'importance' => 50,
            'is_accepted' => 0,
        ]);
        Friendships::create([
            'requester' => $unrelatedUser->id,
            'accepter' => $firstFriend->id,
            'importance' => 100,
            'is_accepted' => 1,
        ]);

        $friendshipIds = FriendshipsQuery::importantForUser($user)
            ->pluck('id')
            ->all();

        $this->assertSame([
            $highImportance->id,
            $lowImportance->id,
        ], $friendshipIds);
    }
}
