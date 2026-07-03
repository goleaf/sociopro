<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\LanguageSwitchController;
use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class LanguageSwitchControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_language_switch_route_is_bound_to_invokable_controller(): void
    {
        $route = Route::getRoutes()->getByName('language.switch');

        $this->assertNotNull($route, 'Route [language.switch] is missing.');
        $this->assertSame('language/switch/{language}', $route->uri());
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame(LanguageSwitchController::class, $route->getActionName());
    }

    public function test_language_switch_controller_exposes_invokable_method(): void
    {
        $reflection = new ReflectionClass(LanguageSwitchController::class);

        $this->assertTrue($reflection->hasMethod('__invoke'));
        $this->assertTrue($reflection->getMethod('__invoke')->isPublic());
    }

    public function test_invoke_stores_existing_language_in_session_and_redirects_back(): void
    {
        Language::factory()->create([
            'name' => 'Dusk Switch Feature',
            'phrase' => 'Dusk Switch Feature',
            'translated' => 'Dusk Switch Feature',
        ]);

        $this
            ->from('/admin/all/language/settings')
            ->get(route('language.switch', 'Dusk Switch Feature'))
            ->assertRedirect('/admin/all/language/settings')
            ->assertSessionHas('active_language', 'Dusk Switch Feature');
    }

    public function test_invoke_rejects_unknown_language_without_mutating_session(): void
    {
        $this->withSession(['active_language' => 'english'])
            ->from('/admin/all/language/settings')
            ->get(route('language.switch', 'missing-language'))
            ->assertNotFound()
            ->assertSessionHas('active_language', 'english');
    }
}
