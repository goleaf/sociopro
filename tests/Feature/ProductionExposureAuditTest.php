<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class ProductionExposureAuditTest extends TestCase
{
    public function test_production_debug_defaults_and_debug_tool_packages_are_not_exposed(): void
    {
        $envExample = File::get(base_path('.env.example'));
        $appConfig = File::get(config_path('app.php'));

        $this->assertMatchesRegularExpression('/^APP_DEBUG=false$/m', $envExample);
        $this->assertStringContainsString("'debug' => (bool) env('APP_DEBUG', false)", $appConfig);

        $installedPackages = $this->composerPackageNames();
        $forbiddenPackages = [
            'barryvdh/laravel-debugbar',
            'facade/ignition',
            'laravel/horizon',
            'laravel/pulse',
            'laravel/telescope',
            'spatie/laravel-ignition',
        ];

        foreach ($forbiddenPackages as $package) {
            $this->assertNotContains($package, $installedPackages, "{$package} must not be installed in this production baseline.");
        }
    }

    public function test_debug_test_and_observability_routes_are_not_registered(): void
    {
        $forbiddenRoutePatterns = [
            'debug' => '/(?:^|\/|\.|_)debug(?:$|\/|\.|_)/i',
            'ignition' => '/(?:^|\/|\.|_)ignition(?:$|\/|\.|_)/i',
            'horizon' => '/(?:^|\/|\.|_)horizon(?:$|\/|\.|_)/i',
            'phpinfo' => '/(?:^|\/|\.|_)phpinfo(?:$|\/|\.|_)/i',
            'pulse' => '/(?:^|\/|\.|_)pulse(?:$|\/|\.|_)/i',
            'telescope' => '/(?:^|\/|\.|_)telescope(?:$|\/|\.|_)/i',
            'test endpoint' => '/(?:^|\/|\.|_)test(?:$|\/|\.|_)/i',
        ];

        foreach (Route::getRoutes() as $route) {
            $subject = implode(' ', [
                $route->uri(),
                (string) $route->getName(),
                (string) $route->getActionName(),
            ]);

            foreach ($forbiddenRoutePatterns as $label => $pattern) {
                $this->assertDoesNotMatchRegularExpression($pattern, $subject, "{$label} route exposure: {$subject}");
            }

            if (str_starts_with($route->uri(), 'admin/')) {
                $this->assertContains('admin', $route->gatherMiddleware(), "{$route->uri()} must require admin middleware.");
            }
        }
    }

    public function test_public_web_root_does_not_contain_sensitive_artifacts(): void
    {
        $offenders = [];
        $forbiddenFilePatterns = [
            'dot env file' => '/(^|\/)\.env(?:\.|$)/i',
            'mac metadata file' => '/(^|\/)\.DS_Store$/',
            'phpinfo endpoint' => '/(^|\/)phpinfo\.php$/i',
            'public backup archive or dump' => '/\.(?:sql|sqlite|db|log|bak|backup|old|orig|zip|tar|tgz|gz|7z|rar)$/i',
        ];

        foreach ($this->publicFiles() as $file) {
            $relativePath = str_replace(public_path().DIRECTORY_SEPARATOR, '', $file->getPathname());

            foreach ($forbiddenFilePatterns as $label => $pattern) {
                if (preg_match($pattern, $relativePath) === 1) {
                    $offenders[] = "{$relativePath}: {$label}";
                }
            }
        }

        $this->assertSame([], $offenders, 'Remove public .env files, backups, dumps, logs, phpinfo endpoints, and OS metadata.');
    }

    public function test_legacy_install_dump_is_configured_outside_public_web_root(): void
    {
        $configuredPath = (string) config('install.schema_dump_path');
        $realDumpPath = realpath($configuredPath);
        $realPublicPath = realpath(public_path());

        $this->assertFileExists($configuredPath);
        $this->assertIsString($realDumpPath);
        $this->assertIsString($realPublicPath);
        $this->assertStringStartsWith(realpath(database_path()).DIRECTORY_SEPARATOR, $realDumpPath);
        $this->assertFalse(str_starts_with($realDumpPath, $realPublicPath.DIRECTORY_SEPARATOR));
    }

    public function test_apache_rules_block_sensitive_public_files_and_executable_uploads(): void
    {
        $publicHtaccess = File::get(public_path('.htaccess'));
        $storageHtaccess = File::get(storage_path('app/public/.htaccess'));

        foreach (['\\.env', 'sql', 'sqlite', 'log', 'bak', 'backup', 'zip', 'phpinfo', 'debug', 'test'] as $needle) {
            $this->assertStringContainsString($needle, $publicHtaccess);
        }

        $this->assertStringContainsString('^(?!index\\.php$).+\\.php$', $publicHtaccess);
        $this->assertStringContainsString('Require all denied', $publicHtaccess);

        foreach (['php', 'phtml', 'phar', 'sql', 'zip', 'Require all denied'] as $needle) {
            $this->assertStringContainsString($needle, $storageHtaccess);
        }
    }

    /**
     * @return list<string>
     */
    private function composerPackageNames(): array
    {
        $lock = json_decode(File::get(base_path('composer.lock')), true, flags: JSON_THROW_ON_ERROR);
        $packages = [
            ...($lock['packages'] ?? []),
            ...($lock['packages-dev'] ?? []),
        ];

        return collect($packages)
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function publicFiles(): iterable
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(public_path()));

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile()) {
                yield $file;
            }
        }
    }
}
