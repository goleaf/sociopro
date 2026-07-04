<?php

namespace Tests\Feature;

use Illuminate\Support\Arr;
use Tests\TestCase;

class FrontendAssetPipelineTest extends TestCase
{
    public function test_it_uses_vite_with_an_scss_entrypoint_instead_of_laravel_mix(): void
    {
        $this->assertFileExists(base_path('vite.config.js'));
        $this->assertFileDoesNotExist(base_path('webpack.mix.js'));
        $this->assertFileExists(resource_path('scss/app.scss'));
        $this->assertFileDoesNotExist(resource_path('css/app.css'));
        $this->assertFileDoesNotExist(public_path('mix-manifest.json'));
        $this->assertFileDoesNotExist(public_path('css/app.css'));
        $this->assertFileDoesNotExist(public_path('js/app.js'));
        $this->assertFileDoesNotExist(public_path('js/app.js.LICENSE.txt'));

        $package = json_decode(file_get_contents(base_path('package.json')), true, flags: JSON_THROW_ON_ERROR);
        $scripts = Arr::get($package, 'scripts', []);
        $devDependencies = Arr::get($package, 'devDependencies', []);
        $dependencies = Arr::get($package, 'dependencies', []);
        $allDependencies = array_merge($dependencies, $devDependencies);

        $this->assertSame('vite', $scripts['dev']);
        $this->assertSame('vite build', $scripts['build']);
        $this->assertStringNotContainsString('mix', implode(' ', $scripts));

        foreach (['vite', 'laravel-vite-plugin', 'sass'] as $packageName) {
            $this->assertArrayHasKey($packageName, $allDependencies);
        }

        foreach (['laravel-mix', 'webpack'] as $packageName) {
            $this->assertArrayNotHasKey($packageName, $allDependencies);
        }
    }

    public function test_layouts_load_application_assets_through_the_vite_blade_directive(): void
    {
        foreach (['app', 'guest'] as $layout) {
            $contents = file_get_contents(resource_path("views/layouts/{$layout}.blade.php"));

            $this->assertStringContainsString("@vite(['resources/scss/app.scss', 'resources/js/app.js'])", $contents);
            $this->assertStringNotContainsString("asset('css/app.css')", $contents);
            $this->assertStringNotContainsString("asset('js/app.js')", $contents);
        }
    }
}
