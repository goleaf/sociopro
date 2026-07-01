<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RolePermissionAuditTest extends TestCase
{
    public function test_project_does_not_install_or_partially_configure_a_permission_package(): void
    {
        $permissionPackages = [
            'spatie/laravel-permission',
            'silber/bouncer',
            'zizaco/entrust',
            'santigarcor/laratrust',
            'romanbican/roles',
        ];

        foreach (['composer.json', 'composer.lock'] as $composerFile) {
            $contents = File::get(base_path($composerFile));

            foreach ($permissionPackages as $package) {
                $this->assertStringNotContainsString($package, $contents, "{$composerFile} installs {$package}.");
            }
        }

        $this->assertFileDoesNotExist(config_path('permission.php'), 'Permission package config exists without an installed package.');
        $this->assertSourceDoesNotContain(
            '/\b(model_has_roles|model_has_permissions|role_has_permissions)\b/',
            'Permission-package pivot tables should not exist in migrations without an installed package.',
            ['database/migrations']
        );
    }

    public function test_source_does_not_mix_permission_package_apis_with_first_party_roles(): void
    {
        $patterns = [
            '/@(role|hasrole|hasanyrole|hasallroles|permission|haspermission)\b/i',
            '/\bhas(Any|All)?Roles?\s*\(/',
            '/\bhas(Any|All)?Permission(To)?\s*\(/',
            '/->middleware\(\s*[\'"](?:role|permission):/i',
            '/Route::middleware\(\s*[\'"](?:role|permission):/i',
        ];

        foreach ($patterns as $pattern) {
            $this->assertSourceDoesNotContain(
                $pattern,
                "Permission package API found for {$pattern}; use the project's enums, middleware, policies, or gates instead."
            );
        }
    }

    public function test_access_role_literals_use_project_enums(): void
    {
        $patterns = [
            '/user_role\s*(?:={2,3}|!={1,2})\s*[\'"](?:admin|general|member)[\'"]/',
            '/[\'"]user_role[\'"]\s*=>\s*[\'"](?:admin|general|member)[\'"]/',
            '/->role\s*=\s*[\'"](?:admin|general)[\'"]/',
        ];

        foreach ($patterns as $pattern) {
            $this->assertSourceDoesNotContain(
                $pattern,
                "Role literal found for {$pattern}; use UserRole or MembershipRole enum values."
            );
        }
    }

    /**
     * @param  list<string>  $directories
     */
    private function assertSourceDoesNotContain(string $pattern, string $message, array $directories = ['app', 'routes', 'resources/views']): void
    {
        $matches = [];

        foreach ($this->sourceFiles($directories) as $file) {
            $contents = $this->withoutComments(File::get($file));

            if (preg_match($pattern, $contents)) {
                $matches[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file);
            }
        }

        $this->assertSame([], $matches, $message."\n".implode("\n", $matches));
    }

    /**
     * @param  list<string>  $directories
     * @return list<string>
     */
    private function sourceFiles(array $directories): array
    {
        return collect($directories)
            ->map(fn (string $directory): string => base_path($directory))
            ->filter(fn (string $path): bool => File::isDirectory($path))
            ->flatMap(fn (string $path) => File::allFiles($path))
            ->filter(fn ($file): bool => in_array($file->getExtension(), ['php'], true))
            ->map(fn ($file): string => $file->getRealPath())
            ->values()
            ->all();
    }

    private function withoutComments(string $contents): string
    {
        $contents = preg_replace('/\{\{--.*?--\}\}/s', '', $contents) ?? $contents;
        $tokens = token_get_all($contents);

        return collect($tokens)
            ->reject(fn ($token): bool => is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true))
            ->map(fn ($token): string => is_array($token) ? $token[1] : $token)
            ->implode('');
    }
}
