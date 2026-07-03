<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\FollowController;
use App\Models\Follower;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class FollowControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_follow_controller_routes_are_bound_to_expected_methods(): void
    {
        $routes = [
            'user.follow' => ['GET', 'HEAD', 'user/account/follow/{id}', 'follow'],
            'user.unfollow' => ['GET', 'HEAD', 'user/account/unfollow/{id}', 'unfollow'],
        ];

        foreach ($routes as $name => $contract) {
            $method = array_pop($contract);
            $uri = array_pop($contract);
            $expectedMethods = $contract;
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Route [{$name}] is missing.");

            $actualMethods = $route->methods();
            sort($expectedMethods);
            sort($actualMethods);

            $this->assertSame($uri, $route->uri(), "Route [{$name}] URI changed.");
            $this->assertSame($expectedMethods, $actualMethods, "Route [{$name}] HTTP methods changed.");
            $this->assertSame(FollowController::class.'@'.$method, $route->getActionName(), "Route [{$name}] action changed.");
        }
    }

    public function test_follow_controller_method_surface_tracks_expected_public_actions(): void
    {
        $reflection = new ReflectionClass(FollowController::class);

        foreach (['follow', 'unfollow'] as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Missing public method [{$method}].");
            $this->assertTrue($reflection->getMethod($method)->isPublic(), "Method [{$method}] must stay public.");
        }
    }

    public function test_follow_is_idempotent_for_current_user_pair(): void
    {
        $viewer = $this->activeUser();
        $target = $this->activeUser();

        $this
            ->actingAs($viewer)
            ->get(route('user.follow', $target->id))
            ->assertOk()
            ->assertJson(['reload' => 1])
            ->assertSessionHas('success_message', get_phrase('You are now following'));

        $this
            ->actingAs($viewer)
            ->get(route('user.follow', $target->id))
            ->assertOk()
            ->assertJson(['reload' => 1]);

        $this->assertSame(1, Follower::query()
            ->where('user_id', $viewer->id)
            ->where('follow_id', $target->id)
            ->count());
    }

    public function test_unfollow_deletes_only_current_authenticated_users_follow_record(): void
    {
        $viewer = $this->activeUser();
        $otherFollower = $this->activeUser();
        $target = $this->activeUser();

        $this->createFollower($viewer, $target);
        $this->createFollower($otherFollower, $target);

        $this
            ->actingAs($viewer)
            ->get(route('user.unfollow', $target->id))
            ->assertOk()
            ->assertJson(['reload' => 1])
            ->assertSessionHas('success_message', get_phrase('Removed from followed list'));

        $this->assertDatabaseMissing('followers', [
            'user_id' => $viewer->id,
            'follow_id' => $target->id,
        ]);
        $this->assertDatabaseHas('followers', [
            'user_id' => $otherFollower->id,
            'follow_id' => $target->id,
        ]);
    }

    private function activeUser(UserRole $role = UserRole::General): User
    {
        return User::factory()->create([
            'status' => UserAccountStatus::Active->value,
            'user_role' => $role->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
        ]);
    }

    private function createFollower(User $user, User $target): Follower
    {
        $follower = new Follower;
        $follower->user_id = $user->id;
        $follower->follow_id = $target->id;
        $follower->save();

        return $follower;
    }
}
