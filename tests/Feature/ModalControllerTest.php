<?php

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\ModalController;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Event;
use App\Models\Marketplace;
use App\Models\MediaFile;
use App\Models\Page;
use App\Models\PageCategory;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class ModalControllerTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLIC_METHODS = [
        '__construct',
        'common_view_function',
        'common_view_function2',
    ];

    /**
     * @var list<string>
     */
    private const ROUTE_METHODS = [
        'GET',
        'HEAD',
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
        'OPTIONS',
    ];

    public function test_requested_modal_controller_methods_keep_expected_visibility(): void
    {
        $controller = new ReflectionClass(ModalController::class);

        foreach (self::PUBLIC_METHODS as $method) {
            $this->assertTrue($controller->hasMethod($method), "ModalController::{$method} is missing.");
            $this->assertTrue($controller->getMethod($method)->isPublic(), "ModalController::{$method} should stay public.");
        }

        $this->assertTrue($controller->hasMethod('modalViewData'), 'ModalController::modalViewData is missing.');
        $this->assertTrue($controller->getMethod('modalViewData')->isPrivate(), 'ModalController::modalViewData should stay internal.');
    }

    public function test_modal_route_keeps_expected_action_methods_uri_and_middleware(): void
    {
        $route = Route::getRoutes()->getByName('load_modal_content');

        $this->assertNotNull($route, 'Route [load_modal_content] is missing.');
        $this->assertSame(ModalController::class.'@common_view_function', $route->getActionName());
        $this->assertSame(self::ROUTE_METHODS, $route->methods());
        $this->assertSame('load_modal_content/{view_path}', $route->uri());

        foreach (['auth', 'user', 'verified', 'activity'] as $middleware) {
            $this->assertContains($middleware, $route->middleware(), "Route [load_modal_content] lost [{$middleware}] middleware.");
        }
    }

    public function test_common_view_function_passes_only_whitelisted_modal_request_data(): void
    {
        $viewer = $this->activeUser();

        $response = $this
            ->actingAs($viewer)
            ->get(route('load_modal_content', [
                'view_path' => 'frontend.main_content.create_report',
                'post_id' => 123,
                'unsafe' => 'must-not-leak',
            ]));

        $response
            ->assertOk()
            ->assertSee('value="123" name="post_id"', false)
            ->assertDontSee('must-not-leak', false);
    }

    public function test_common_view_function2_parses_legacy_payload_into_view_data(): void
    {
        $viewer = $this->activeUser();

        $view = $this
            ->actingAs($viewer)
            ->app
            ->make(ModalController::class)
            ->common_view_function2('frontend.main_content.create_report', 'post_id->456,ignored->legacy');

        $html = $view->render();

        $this->assertStringContainsString('value="456" name="post_id"', $html);
        $this->assertStringNotContainsString('legacy', $html);
    }

    public function test_modal_view_data_supplies_page_categories_for_create_page_modal(): void
    {
        $viewer = $this->activeUser();
        PageCategory::factory()->create(['name' => 'Zeta modal category']);
        PageCategory::factory()->create(['name' => 'Alpha modal category']);

        $response = $this
            ->actingAs($viewer)
            ->get(route('load_modal_content', [
                'view_path' => 'frontend.pages.create_page',
            ]));

        $response
            ->assertOk()
            ->assertSeeInOrder(['Alpha modal category', 'Zeta modal category']);
    }

    public function test_modal_view_data_authorizes_page_owner_modals(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $category = PageCategory::factory()->create(['name' => 'Owner modal category']);
        $page = Page::factory()
            ->forOwner($owner)
            ->forCategory($category)
            ->create(['title' => 'Owner modal page']);

        $this
            ->actingAs($owner)
            ->get(route('load_modal_content', [
                'view_path' => 'frontend.pages.edit-modal',
                'page_id' => $page->id,
            ]))
            ->assertOk()
            ->assertSee('Owner modal page');

        $this
            ->actingAs($otherUser)
            ->get(route('load_modal_content', [
                'view_path' => 'frontend.pages.edit-modal',
                'page_id' => $page->id,
            ]))
            ->assertForbidden();
    }

    public function test_modal_view_data_loads_marketplace_product_images_and_authorizes_updates(): void
    {
        $owner = $this->activeUser();
        $otherUser = $this->activeUser();
        $product = $this->marketplace($owner, ['title' => 'Modal product owner item']);
        $image = MediaFile::factory()->image()->create([
            'user_id' => $owner->id,
            'product_id' => $product->id,
            'file_name' => 'modal-product-image.jpg',
        ]);
        MediaFile::factory()->video()->create([
            'user_id' => $owner->id,
            'product_id' => $product->id,
            'file_name' => 'modal-product-video.mp4',
        ]);

        $data = $this->actingAs($owner)->invokeModalViewData('frontend.marketplace.edit_product', [
            'product_id' => $product->id,
        ]);

        $this->assertSame($product->id, $data['product']->id);
        $this->assertSame([$image->id], $data['productImages']->pluck('id')->all());

        $this->expectException(AuthorizationException::class);

        $this->actingAs($otherUser)->invokeModalViewData('frontend.marketplace.edit_product', [
            'product_id' => $product->id,
        ]);
    }

    public function test_event_modal_uses_modal_view_data_guest_rows(): void
    {
        $owner = $this->activeUser();
        $goingUser = $this->activeUser();
        $interestedUser = $this->activeUser();
        $event = Event::factory()
            ->forOwner($owner)
            ->create([
                'title' => 'Modal guest event',
                'going_users_id' => json_encode([$goingUser->id]),
                'interested_users_id' => json_encode([$interestedUser->id]),
            ]);

        $data = $this->actingAs($owner)->invokeModalViewData('frontend.events.view-all', [
            'event_id' => $event->id,
        ]);

        $this->assertSame($event->id, $data['event']->id);
        $this->assertSame(
            [
                [$goingUser->id, 'Going'],
                [$interestedUser->id, 'Interested'],
            ],
            $data['eventGuestRows']
                ->map(fn (array $row): array => [$row['user']->id, $row['status']])
                ->all()
        );

        $this
            ->actingAs($owner)
            ->get(route('load_modal_content', [
                'view_path' => 'frontend.events.view-all',
                'event_id' => $event->id,
            ]))
            ->assertOk()
            ->assertSee($goingUser->name)
            ->assertSee('Going')
            ->assertSee($interestedUser->name)
            ->assertSee('Interested');
    }

    public function test_event_modal_blade_uses_preloaded_guest_rows(): void
    {
        $blade = File::get(resource_path('views/frontend/events/view-all.blade.php'));

        $this->assertStringContainsString('$eventGuestRows', $blade);
        $this->assertStringNotContainsString('Event::where', $blade);
        $this->assertStringNotContainsString('User::find', $blade);
        $this->assertStringNotContainsString('@php', $blade);
    }

    private function activeUser(UserRole $role = UserRole::General): User
    {
        return User::factory()->create([
            'user_role' => $role->value,
            'status' => UserAccountStatus::Active->value,
            'timezone' => 'UTC',
            'friends' => json_encode([]),
            'followers' => json_encode([]),
            'save_post' => json_encode([]),
            'profile_status' => 'unlock',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function marketplace(User $owner, array $attributes = []): Marketplace
    {
        return Marketplace::factory()
            ->forOwner($owner)
            ->forCategory(Category::factory()->create())
            ->forBrand(Brand::factory()->create())
            ->forCurrency(Currency::factory()->create())
            ->create([
                'title' => 'Feature modal product',
                'description' => 'Feature modal product description.',
                'status' => '1',
                ...$attributes,
            ]);
    }

    /**
     * @param  array<string, mixed>  $pageData
     * @return array<string, mixed>
     */
    private function invokeModalViewData(string $viewPath, array $pageData): array
    {
        $method = new ReflectionMethod(ModalController::class, 'modalViewData');
        $method->setAccessible(true);

        return $method->invoke(app(ModalController::class), $viewPath, $pageData);
    }
}
