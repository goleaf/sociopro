<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\AuthCheckerController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\BlogController;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class WebControllerSurfaceTest extends TestCase
{
    /**
     * @var array<class-string, list<string>>
     */
    private const CONTROLLER_METHODS = [
        AuthenticatedSessionController::class => ['create', 'store', 'destroy'],
        ConfirmablePasswordController::class => ['show', 'store'],
        EmailVerificationNotificationController::class => ['__invoke'],
        EmailVerificationPromptController::class => ['__invoke'],
        NewPasswordController::class => ['create', 'store'],
        PasswordResetLinkController::class => ['create', 'store'],
        RegisteredUserController::class => ['create', 'store'],
        VerifyEmailController::class => ['__invoke'],
        AuthCheckerController::class => ['__invoke'],
        BadgeController::class => ['badge', 'badge_info', 'payment_configuration'],
        BlogController::class => [
            'index',
            'blogs',
            'myblog',
            'create',
            'store',
            'edit',
            'update',
            'destroy',
            'delete',
            'load_blog_by_scrolling',
            'show',
            'single_blog',
            'category_blog',
            'search',
            'blogCategoriesForSelect',
        ],
    ];

    /**
     * @var array<string, array{0: class-string, 1: string, 2: list<string>, 3: string}>
     */
    private const ROUTES = [
        'login' => [AuthenticatedSessionController::class, 'create', ['GET', 'HEAD'], 'login'],
        'login.store' => [AuthenticatedSessionController::class, 'store', ['POST'], 'login'],
        'logout' => [AuthenticatedSessionController::class, 'destroy', ['POST'], 'logout'],
        'password.confirm' => [ConfirmablePasswordController::class, 'show', ['GET', 'HEAD'], 'confirm-password'],
        'password.confirm.store' => [ConfirmablePasswordController::class, 'store', ['POST'], 'confirm-password'],
        'verification.send' => [EmailVerificationNotificationController::class, '__invoke', ['POST'], 'email/verification-notification'],
        'verification.notice' => [EmailVerificationPromptController::class, '__invoke', ['GET', 'HEAD'], 'verify-email'],
        'password.reset' => [NewPasswordController::class, 'create', ['GET', 'HEAD'], 'reset-password/{token}'],
        'password.update' => [NewPasswordController::class, 'store', ['POST'], 'reset-password'],
        'password.request' => [PasswordResetLinkController::class, 'create', ['GET', 'HEAD'], 'forgot-password'],
        'password.email' => [PasswordResetLinkController::class, 'store', ['POST'], 'forgot-password'],
        'register' => [RegisteredUserController::class, 'create', ['GET', 'HEAD'], 'register'],
        'register.store' => [RegisteredUserController::class, 'store', ['POST'], 'register'],
        'verification.verify' => [VerifyEmailController::class, '__invoke', ['GET', 'HEAD'], 'verify-email/{id}/{hash}'],
        'auth-checker' => [AuthCheckerController::class, '__invoke', ['GET', 'HEAD'], 'auth-checker'],
        'badge' => [BadgeController::class, 'badge', ['GET', 'HEAD'], 'badge'],
        'badge.info' => [BadgeController::class, 'badge_info', ['GET', 'HEAD'], 'badge/info'],
        'badge.payment_configuration' => [BadgeController::class, 'payment_configuration', ['POST'], 'badge/payment_configuration/{id}'],
        'blogs' => [BlogController::class, 'index', ['GET', 'HEAD'], 'blogs'],
        'create.blog' => [BlogController::class, 'create', ['GET', 'HEAD'], 'create/blog'],
        'myblog' => [BlogController::class, 'myblog', ['GET', 'HEAD'], 'my/blog'],
        'blog.store' => [BlogController::class, 'store', ['POST'], 'blog/store'],
        'blog.edit' => [BlogController::class, 'edit', ['GET', 'HEAD'], 'edit/blog/{id}'],
        'blog.update' => [BlogController::class, 'update', ['POST'], 'update/blog/{id}'],
        'blog.delete' => [BlogController::class, 'destroy', ['GET', 'HEAD'], 'blog/delete'],
        'load_blog_by_scrolling' => [BlogController::class, 'load_blog_by_scrolling', ['GET', 'HEAD'], 'load_blog_by_scrolling'],
        'single.blog' => [BlogController::class, 'show', ['GET', 'HEAD'], 'blog/view/{id}'],
        'category.blog' => [BlogController::class, 'category_blog', ['GET', 'HEAD'], 'blog/category/{category}'],
        'search.blog' => [BlogController::class, 'search', ['GET', 'HEAD'], 'blog/search'],
    ];

    public function test_requested_controller_methods_keep_expected_visibility(): void
    {
        foreach (self::CONTROLLER_METHODS as $controllerClass => $methods) {
            $controller = new ReflectionClass($controllerClass);

            foreach ($methods as $method) {
                $this->assertTrue($controller->hasMethod($method), "{$controllerClass}::{$method} is missing.");

                if ($method === 'blogCategoriesForSelect') {
                    $this->assertTrue($controller->getMethod($method)->isPrivate(), "{$controllerClass}::{$method} should stay internal.");

                    continue;
                }

                $this->assertTrue($controller->getMethod($method)->isPublic(), "{$controllerClass}::{$method} should be routable/public.");
            }
        }
    }

    public function test_requested_controller_routes_keep_expected_actions_methods_and_uris(): void
    {
        foreach (self::ROUTES as $routeName => [$controllerClass, $method, $verbs, $uri]) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, "Route [{$routeName}] is missing.");
            $this->assertSame($verbs, $route->methods(), "Route [{$routeName}] HTTP methods changed.");
            $this->assertSame($uri, $route->uri(), "Route [{$routeName}] URI changed.");
            if ($method === '__invoke') {
                $this->assertContains(
                    $route->getActionName(),
                    [$controllerClass, $controllerClass.'@__invoke'],
                    "Route [{$routeName}] action changed."
                );

                continue;
            }

            $this->assertSame($controllerClass.'@'.$method, $route->getActionName(), "Route [{$routeName}] action changed.");
        }
    }
}
