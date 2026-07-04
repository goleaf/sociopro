<?php

namespace Tests\Feature;

use Closure;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RouteHandlerSmokeTest extends TestCase
{
    public function test_registered_routes_resolve_to_callable_handlers(): void
    {
        $missingHandlers = [];

        foreach (Route::getRoutes() as $route) {
            $uses = $route->getAction('uses');

            if ($uses instanceof Closure) {
                continue;
            }

            if (! is_string($uses)) {
                continue;
            }

            if (str_contains($uses, '@')) {
                [$class, $method] = explode('@', $uses, 2);

                if (! class_exists($class) || ! method_exists($class, $method)) {
                    $missingHandlers[] = sprintf('%s %s', $route->getName() ?? $route->uri(), $uses);
                }

                continue;
            }

            if (! class_exists($uses) || ! method_exists($uses, '__invoke')) {
                $missingHandlers[] = sprintf('%s %s::__invoke', $route->getName() ?? $route->uri(), $uses);
            }
        }

        $this->assertSame([], $missingHandlers);
    }
}
