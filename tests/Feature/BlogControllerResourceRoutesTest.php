<?php

namespace Tests\Feature;

use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class BlogControllerResourceRoutesTest extends TestCase
{
    public function test_blog_routes_preserve_public_contract_with_resource_actions(): void
    {
        $routes = [
            'blogs' => [['GET', 'HEAD'], 'blogs', 'index'],
            'create.blog' => [['GET', 'HEAD'], 'create/blog', 'create'],
            'myblog' => [['GET', 'HEAD'], 'my/blog', 'myblog'],
            'blog.store' => [['POST'], 'blog/store', 'store'],
            'blog.edit' => [['GET', 'HEAD'], 'edit/blog/{id}', 'edit'],
            'blog.update' => [['POST'], 'update/blog/{id}', 'update'],
            'blog.delete' => [['GET', 'HEAD'], 'blog/delete', 'destroy'],
            'load_blog_by_scrolling' => [['GET', 'HEAD'], 'load_blog_by_scrolling', 'load_blog_by_scrolling'],
            'single.blog' => [['GET', 'HEAD'], 'blog/view/{id}', 'show'],
            'category.blog' => [['GET', 'HEAD'], 'blog/category/{category}', 'category_blog'],
            'search.blog' => [['GET', 'HEAD'], 'blog/search', 'search'],
        ];

        foreach ($routes as $name => [$methods, $uri, $method]) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route, "Route [{$name}] is missing.");
            $this->assertSame($methods, $route->methods(), "Route [{$name}] HTTP methods changed.");
            $this->assertSame($uri, $route->uri(), "Route [{$name}] URI changed.");
            $this->assertSame(BlogController::class.'@'.$method, $route->getActionName(), "Route [{$name}] action changed.");
        }
    }
}
