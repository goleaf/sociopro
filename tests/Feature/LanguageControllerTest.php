<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserAccountStatus;
use App\Enums\UserRole;
use App\Http\Controllers\LanguageController;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class LanguageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_language_controller_routes_are_bound_to_expected_methods(): void
    {
        $routes = [
            'admin.language.settings' => ['GET', 'HEAD', 'admin/all/language/settings', 'language'],
            'admin.language.create' => ['POST', 'admin/create/language', 'language_add'],
            'admin.languages.update' => ['POST', 'admin/languages/update/{language}', 'language_update'],
            'admin.languages.edit.phrase' => ['GET', 'HEAD', 'admin/languages/edit/phrase/{language}', 'edit_phrase'],
            'admin.languages.update.phrase' => ['POST', 'admin/languages/update/phrase/{id}', 'update_phrase'],
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
            $this->assertSame(LanguageController::class.'@'.$method, $route->getActionName(), "Route [{$name}] action changed.");
        }
    }

    public function test_language_controller_method_surface_tracks_expected_public_actions(): void
    {
        $reflection = new ReflectionClass(LanguageController::class);

        foreach ([
            'language',
            'language_add',
            'language_update',
            'edit_phrase',
            'update_phrase',
        ] as $method) {
            $this->assertTrue($reflection->hasMethod($method), "Missing public method [{$method}].");
            $this->assertTrue($reflection->getMethod($method)->isPublic(), "Method [{$method}] must stay public.");
        }
    }

    public function test_language_settings_and_phrase_pages_render_for_admin(): void
    {
        $admin = $this->activeAdmin();
        Language::factory()->create([
            'name' => 'dusk_feature_render_language',
            'phrase' => 'Dusk Feature Phrase One',
            'translated' => 'Dusk Feature Translation One',
        ]);
        Language::factory()->create([
            'name' => 'dusk_feature_render_language',
            'phrase' => 'Dusk Feature Phrase Two',
            'translated' => 'Dusk Feature Translation Two',
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.language.settings'))
            ->assertOk()
            ->assertSee('dusk_feature_render_language')
            ->assertSee('Add new language');

        $this
            ->actingAs($admin)
            ->get(route('admin.languages.edit.phrase', 'dusk_feature_render_language'))
            ->assertOk()
            ->assertSee('Dusk Feature Phrase One')
            ->assertSee('Dusk Feature Translation Two');
    }

    public function test_language_can_be_created_and_renamed(): void
    {
        $admin = $this->activeAdmin();

        $this
            ->actingAs($admin)
            ->post(route('admin.language.create'), ['language' => 'Dusk Feature Language'])
            ->assertRedirect();

        $this->assertDatabaseHas('languages', [
            'name' => 'Dusk Feature Language',
            'phrase' => 'Dusk Feature Language',
            'translated' => 'Dusk Feature Language',
        ]);

        Language::factory()->create([
            'name' => 'Dusk Feature Language',
            'phrase' => 'Dusk Feature Existing Phrase',
            'translated' => 'Dusk Feature Existing Translation',
        ]);

        $this
            ->actingAs($admin)
            ->post(route('admin.languages.update', 'Dusk Feature Language'), ['language' => 'Dusk Renamed Language'])
            ->assertRedirect(route('admin.language.settings'));

        $this->assertSame(0, Language::query()->where('name', 'Dusk Feature Language')->count());
        $this->assertSame(2, Language::query()->where('name', 'dusk renamed language')->count());
    }

    public function test_phrase_updates_keep_placeholder_contract(): void
    {
        $admin = $this->activeAdmin();
        $plain = Language::factory()->create([
            'name' => 'dusk_feature_phrase_language',
            'phrase' => 'Dusk Plain Phrase',
            'translated' => 'Old plain translation',
        ]);
        $placeholder = Language::factory()->create([
            'name' => 'dusk_feature_phrase_language',
            'phrase' => 'Dusk ____ Phrase',
            'translated' => 'Old ____ translation',
        ]);
        $leadingPlaceholder = Language::factory()->create([
            'name' => 'dusk_feature_phrase_language',
            'phrase' => '____ Dusk Leading Phrase',
            'translated' => 'Old ____ leading translation',
        ]);

        $this
            ->actingAs($admin)
            ->post(route('admin.languages.update.phrase', $plain->id), ['translated' => 'New plain translation'])
            ->assertOk()
            ->assertContent('1');

        $this
            ->actingAs($admin)
            ->post(route('admin.languages.update.phrase', $placeholder->id), ['translated' => 'New ____ translation'])
            ->assertOk()
            ->assertContent('1');

        $this
            ->actingAs($admin)
            ->post(route('admin.languages.update.phrase', $leadingPlaceholder->id), ['translated' => 'Missing leading placeholder'])
            ->assertOk()
            ->assertContent('');

        $this->assertSame('New plain translation', $plain->refresh()->translated);
        $this->assertSame('New ____ translation', $placeholder->refresh()->translated);
        $this->assertSame('Old ____ leading translation', $leadingPlaceholder->refresh()->translated);
    }

    public function test_language_controller_uses_eloquent_instead_of_database_facade_queries(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/LanguageController.php'));

        $this->assertIsString($source);
        $this->assertStringNotContainsString('use DB;', $source);
        $this->assertStringNotContainsString('DB::table', $source);
    }

    private function activeAdmin(): User
    {
        return User::factory()->create([
            'status' => UserAccountStatus::Active->value,
            'user_role' => UserRole::Admin->value,
            'timezone' => 'UTC',
            'lastActive' => now(),
            'friends' => json_encode([]),
            'followers' => json_encode([]),
        ]);
    }
}
