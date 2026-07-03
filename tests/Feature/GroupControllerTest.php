<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\MembershipRole;
use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Http\Controllers\GroupController;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class GroupControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_controller_routes_are_bound_to_expected_methods(): void
    {
        $routes = [
            'groups' => ['GET', 'HEAD', 'groups', 'groups'],
            'group.store' => ['POST', 'group/store', 'store'],
            'group.update' => ['POST', 'update/group/{id}', 'update'],
            'group.coverphoto' => ['POST', 'update/coverphoto/group/{id}', 'updatecoverphoto'],
            'group.people.info' => ['GET', 'HEAD', 'group/peopel/info/{id}', 'peopelinfo'],
            'single.group' => ['GET', 'HEAD', 'group/view/details/{id}', 'single_group'],
            'single.group.photos' => ['GET', 'HEAD', 'group/photo/view/{id}', 'group_photos'],
            'all.people.group.view' => ['GET', 'HEAD', 'all/peopel/group/view/{id}', 'all_people_group'],
            'group.event.view' => ['GET', 'HEAD', 'group/event/view/{id}', 'group_event'],
            'group.join' => ['GET', 'HEAD', 'group/join/{id}', 'join'],
            'group.rjoin' => ['GET', 'HEAD', 'group/rjoin/{id}', 'rjoin'],
            'search.group' => ['GET', 'HEAD', 'group/search/view', 'search_group'],
            'all.group.view' => ['GET', 'HEAD', 'group/all/view', 'group_all_view'],
            'group.user.created' => ['GET', 'HEAD', 'group/user/create', 'group_user_create'],
            'group.user.joined' => ['GET', 'HEAD', 'group/user/joined', 'group_user_joined'],
            'add.image.album' => ['POST', 'album/add/image', 'add_album_image'],
            'group.invition' => ['POST', 'group/invites/sent', 'sent_invition'],
            'search_friends_for_inviting' => ['GET', 'HEAD', 'search_friends_for_inviting', 'search_friends_for_inviting'],
            'load_groups_by_scrolling' => ['GET', 'HEAD', 'load_groups_by_scrolling', 'load_groups_by_scrolling'],
            'album.details.list' => ['GET', 'HEAD', 'album/details/list/{identifire}/{album_id}', 'album_details_list'],
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
            $this->assertSame(GroupController::class.'@'.$method, $route->getActionName(), "Route [{$name}] action changed.");
        }
    }

    public function test_group_controller_method_surface_tracks_expected_public_actions(): void
    {
        $reflection = new ReflectionClass(GroupController::class);

        foreach ([
            'groups',
            'single_group',
            'store',
            'update',
            'updatecoverphoto',
            'join',
            'rjoin',
            'peopelinfo',
            'group_photos',
            'all_people_group',
            'group_event',
            'add_album_image',
            'search_group',
            'group_all_view',
            'load_groups_by_scrolling',
            'group_user_create',
            'group_user_joined',
            'search_friends_for_inviting',
            'sent_invition',
            'album_details_list',
        ] as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Missing public method [{$method}].");
            $this->assertTrue($reflection->getMethod($method)->isPublic(), "Method [{$method}] must stay public.");
        }
    }

    public function test_join_is_idempotent_and_rjoin_deletes_only_current_authenticated_member(): void
    {
        $viewer = $this->activeUser();
        $otherMember = $this->activeUser();
        $group = Group::factory()->create([
            'privacy' => Visibility::Public->value,
            'status' => '1',
        ]);

        $this
            ->actingAs($viewer)
            ->get(route('group.join', $group->id))
            ->assertOk()
            ->assertJson(['reload' => 1])
            ->assertSessionHas('success_message', get_phrase('Group Joind  Successfully'));

        $this
            ->actingAs($viewer)
            ->get(route('group.join', $group->id))
            ->assertOk()
            ->assertJson(['reload' => 1]);

        $this->assertSame(1, GroupMember::query()
            ->where('user_id', $viewer->id)
            ->where('group_id', $group->id)
            ->count());

        GroupMember::factory()->create([
            'user_id' => $otherMember->id,
            'group_id' => $group->id,
            'role' => MembershipRole::General->value,
            'is_accepted' => '1',
        ]);

        $this
            ->actingAs($viewer)
            ->get(route('group.rjoin', $group->id))
            ->assertOk()
            ->assertJson(['reload' => 1])
            ->assertSessionHas('success_message', get_phrase('Group Joining Canceled'));

        $this->assertDatabaseMissing('group_members', [
            'user_id' => $viewer->id,
            'group_id' => $group->id,
        ]);
        $this->assertDatabaseHas('group_members', [
            'user_id' => $otherMember->id,
            'group_id' => $group->id,
        ]);
    }

    public function test_group_listing_pages_render_scalar_member_count_phrase_replacements(): void
    {
        $viewer = $this->activeUser();
        $group = Group::factory()->create([
            'title' => 'Scalar Group',
            'privacy' => Visibility::Public->value,
            'status' => '1',
        ]);
        GroupMember::factory()->create([
            'user_id' => $viewer->id,
            'group_id' => $group->id,
            'role' => MembershipRole::General->value,
            'is_accepted' => '1',
        ]);

        $this
            ->actingAs($viewer)
            ->get(route('all.group.view'))
            ->assertOk()
            ->assertSee('Scalar Group');

        $this
            ->actingAs($viewer)
            ->get(route('load_groups_by_scrolling', ['offset' => 0]))
            ->assertOk()
            ->assertSee('Scalar Group');
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
}
