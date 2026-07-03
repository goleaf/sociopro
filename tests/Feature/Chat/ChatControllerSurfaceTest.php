<?php

declare(strict_types=1);

namespace Tests\Feature\Chat;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\ChatController;
use App\Models\Marketplace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class ChatControllerSurfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_controller_routes_are_bound_to_expected_methods(): void
    {
        $routes = [
            'chat' => ['GET', 'HEAD', 'chat/inbox/{receiver}/{product?}', 'chat'],
            'chat.save' => ['POST', 'chat/save', 'chat_save'],
            'remove.chat' => ['GET', 'HEAD', 'chat/own/remove/{id}', 'remove_chat'],
            'react.chat' => ['POST', 'my_message_react', 'react_chat'],
            'search.chat' => ['GET', 'HEAD', 'chat/profile/search', 'search_chat'],
            'chat.load' => ['GET', 'HEAD', 'chat/inbox/load/data/ajax', 'chat_load'],
            'chat.read' => ['GET', 'HEAD', 'chat/inbox/read/message/ajax', 'chat_read_option'],
        ];

        foreach ($routes as $name => $contract) {
            $method = array_pop($contract);
            $uri = array_pop($contract);
            $httpMethods = $contract;
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Route [{$name}] is missing.");
            $actualMethods = $route->methods();
            sort($actualMethods);
            sort($httpMethods);

            $this->assertSame($uri, $route->uri());
            $this->assertSame(ChatController::class.'@'.$method, $route->getActionName());
            $this->assertSame($httpMethods, $actualMethods);
        }
    }

    public function test_chat_controller_method_surface_tracks_public_private_and_absent_legacy_methods(): void
    {
        $reflection = new ReflectionClass(ChatController::class);

        foreach ([
            'chat',
            'chat_save',
            'remove_chat',
            'react_chat',
            'search_chat',
            'chat_load',
            'chat_read_option',
        ] as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Missing public method [{$method}].");
            $this->assertTrue($reflection->getMethod($method)->isPublic(), "Method [{$method}] must stay public.");
        }

        foreach (['receiverIdFromRequest', 'authorizeMarketplaceProductChat'] as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Missing private helper [{$method}].");
            $this->assertTrue($reflection->getMethod($method)->isPrivate(), "Helper [{$method}] must stay private.");
        }

        $this->assertFalse(
            $reflection->hasMethod('chat_read_optionN'),
            'chat_read_optionN is not registered in the current web chat surface.'
        );
    }

    public function test_receiver_id_resolver_prefers_canonical_input_and_keeps_legacy_typo_compatibility(): void
    {
        $method = (new ReflectionClass(ChatController::class))->getMethod('receiverIdFromRequest');
        $method->setAccessible(true);
        $controller = new ChatController;

        $this->assertSame(42, $method->invoke(
            $controller,
            Request::create('/chat/save', 'POST', ['receiver_id' => 42, 'reciver_id' => 99])
        ));
        $this->assertSame(99, $method->invoke(
            $controller,
            Request::create('/chat/save', 'POST', ['reciver_id' => 99])
        ));
    }

    public function test_marketplace_product_chat_authorization_allows_buyer_to_seller_and_denies_invalid_pairs(): void
    {
        $buyer = $this->activeUser();
        $seller = $this->activeUser();
        $otherUser = $this->activeUser();
        $product = Marketplace::factory()->forOwner($seller)->create();

        $this->actingAs($buyer)
            ->get(route('chat', ['receiver' => $seller->id, 'product' => $product->id]))
            ->assertOk();

        $this->actingAs($buyer)
            ->get(route('chat', ['receiver' => $otherUser->id, 'product' => $product->id]))
            ->assertForbidden();

        $this->actingAs($seller)
            ->get(route('chat', ['receiver' => $seller->id, 'product' => $product->id]))
            ->assertForbidden();
    }

    private function activeUser(): User
    {
        return User::factory()->create([
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::General->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
        ]);
    }
}
