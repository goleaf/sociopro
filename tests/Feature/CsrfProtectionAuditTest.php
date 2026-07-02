<?php

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class CsrfProtectionAuditTest extends TestCase
{
    public function test_web_group_uses_csrf_and_api_group_stays_stateless(): void
    {
        $groups = $this->kernelProperty('middlewareGroups');

        $this->assertContains(VerifyCsrfToken::class, $groups['web']);
        $this->assertNotContains(VerifyCsrfToken::class, $groups['api']);
    }

    public function test_state_changing_routes_use_the_correct_stateful_or_stateless_group(): void
    {
        foreach (Route::getRoutes() as $route) {
            if ($this->isReadOnlyRoute($route->methods())) {
                continue;
            }

            $middleware = $route->gatherMiddleware();
            $identifier = implode('|', $route->methods()).' '.$route->uri();

            if (str_starts_with($route->uri(), 'api/')) {
                $this->assertContains('api', $middleware, "{$identifier} must stay in the stateless API group.");
                $this->assertNotContains('web', $middleware, "{$identifier} must not use stateful web middleware.");

                continue;
            }

            $this->assertContains('web', $middleware, "{$identifier} must use the web group for CSRF protection.");
            $this->assertSame([], $route->excludedMiddleware(), "{$identifier} must not disable route middleware.");
        }
    }

    public function test_csrf_middleware_does_not_bypass_web_routes(): void
    {
        $middleware = app(VerifyCsrfToken::class);
        $reflection = new ReflectionClass($middleware);
        $except = $reflection->getProperty('except');
        $except->setAccessible(true);

        $this->assertSame([], $except->getValue($middleware));

        $source = File::get(app_path('Http/Middleware/VerifyCsrfToken.php'));
        $this->assertStringNotContainsString('$this->except[]', $source);
    }

    public function test_mutating_blade_forms_include_csrf_tokens(): void
    {
        $missingTokens = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $contents = $this->withoutBladeComments(File::get($file->getPathname()));

            preg_match_all('/<form\b(?:(?!<\/form>).)*<\/form>/is', $contents, $forms, PREG_OFFSET_CAPTURE);

            foreach ($forms[0] as [$form, $offset]) {
                if (! $this->formUsesMutatingMethod($form)) {
                    continue;
                }

                if ($this->formContainsCsrfToken($form)) {
                    continue;
                }

                $missingTokens[] = $file->getRelativePathname().':'.$this->lineNumberAtOffset($contents, $offset);
            }
        }

        $this->assertSame(
            [],
            $missingTokens,
            "Mutating Blade forms must include @csrf, csrf_field(), or a _token field:\n".implode("\n", $missingTokens)
        );
    }

    public function test_mutating_blade_ajax_requests_send_csrf_tokens(): void
    {
        $missingTokens = [];

        foreach (File::allFiles(resource_path('views')) as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            $lines = file($file->getPathname()) ?: [];

            foreach ($lines as $index => $line) {
                if (! preg_match('/(?:type|method)\s*:\s*[\'"](?:POST|PUT|PATCH|DELETE)[\'"]/i', $line)) {
                    continue;
                }

                $block = implode('', array_slice($lines, max(0, $index - 5), 18));

                if (preg_match('/X-CSRF-TOKEN|_token|csrf_token|csrf-token|csrfToken|csrf_token\(\)/i', $block)) {
                    continue;
                }

                $missingTokens[] = $file->getRelativePathname().':'.($index + 1);
            }
        }

        $this->assertSame(
            [],
            $missingTokens,
            "Mutating Blade AJAX requests must include a CSRF header or token payload:\n".implode("\n", $missingTokens)
        );
    }

    /**
     * @return array<mixed>
     */
    private function kernelProperty(string $property): array
    {
        $kernel = app(HttpKernelContract::class);
        $reflection = new ReflectionClass($kernel);
        $reflectedProperty = $reflection->getProperty($property);
        $reflectedProperty->setAccessible(true);

        return $reflectedProperty->getValue($kernel);
    }

    /**
     * @param  list<string>  $methods
     */
    private function isReadOnlyRoute(array $methods): bool
    {
        return collect($methods)
            ->every(fn (string $method): bool => in_array($method, ['GET', 'HEAD', 'OPTIONS'], true));
    }

    private function formUsesMutatingMethod(string $form): bool
    {
        return preg_match('/method\s*=\s*([\'"])\s*(?:post|put|patch|delete)\s*\1/i', $form) === 1;
    }

    private function formContainsCsrfToken(string $form): bool
    {
        return preg_match('/@csrf|csrf_field\s*\(|name\s*=\s*([\'"])_token\1/i', $form) === 1;
    }

    private function lineNumberAtOffset(string $contents, int $offset): int
    {
        return substr_count(substr($contents, 0, $offset), "\n") + 1;
    }

    private function withoutBladeComments(string $contents): string
    {
        return preg_replace_callback(
            '/{{--.*?--}}/s',
            fn (array $matches): string => str_repeat("\n", substr_count($matches[0], "\n")),
            $contents
        ) ?? $contents;
    }
}
