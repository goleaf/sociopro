<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\PostType;
use App\Enums\Visibility;
use App\Models\Posts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class GlobalScopeAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_documents_current_global_scope_policy(): void
    {
        $path = base_path('docs/global-scopes-audit.md');

        $this->assertFileExists($path);

        $contents = file_get_contents($path) ?: '';

        $this->assertStringContainsString('No Eloquent global scopes are registered', $contents);
        $this->assertStringContainsString('withoutGlobalScopes', $contents);
        $this->assertStringContainsString('SoftDeletes', $contents);
    }

    public function test_models_do_not_register_global_scopes_or_soft_delete_scope(): void
    {
        foreach ($this->modelClasses() as $class) {
            $model = new $class;

            $this->assertSame(
                [],
                $model->getGlobalScopes(),
                "{$class} registers a global scope without this audit being updated."
            );

            $this->assertNotContains(
                SoftDeletes::class,
                class_uses_recursive($class),
                "{$class} uses SoftDeletes; add scoped/unscoped tests and update docs/global-scopes-audit.md."
            );
        }
    }

    public function test_application_code_does_not_bypass_global_scopes(): void
    {
        $matches = $this->sourceMatches(
            [app_path(), base_path('routes')],
            '/\b(?:withoutGlobalScope|withoutGlobalScopes|withTrashed|onlyTrashed|withoutTrashed)\s*\(/'
        );

        $this->assertSame([], $matches, 'Global scope bypasses must be documented and covered by tests.');
    }

    public function test_post_visibility_status_and_ownership_scopes_are_local_not_global(): void
    {
        $visibleOwnerPostId = $this->insertPost([
            'user_id' => 10,
            'privacy' => Visibility::Public->value,
            'status' => ContentStatus::Active->value,
            'report_status' => 0,
        ]);
        $privateOwnerPostId = $this->insertPost([
            'user_id' => 10,
            'privacy' => Visibility::Private->value,
            'status' => ContentStatus::Active->value,
            'report_status' => 0,
        ]);
        $inactiveOwnerPostId = $this->insertPost([
            'user_id' => 10,
            'privacy' => Visibility::Public->value,
            'status' => ContentStatus::Inactive->value,
            'report_status' => 0,
        ]);
        $reportedOwnerPostId = $this->insertPost([
            'user_id' => 10,
            'privacy' => Visibility::Public->value,
            'status' => ContentStatus::Active->value,
            'report_status' => 1,
        ]);
        $otherOwnerPostId = $this->insertPost([
            'user_id' => 11,
            'privacy' => Visibility::Public->value,
            'status' => ContentStatus::Active->value,
            'report_status' => 0,
        ]);

        $allPostIds = Posts::query()->pluck('post_id')->all();
        $withoutGlobalScopePostIds = Posts::withoutGlobalScopes()->pluck('post_id')->all();
        $scopedPostIds = Posts::query()
            ->forUser(10)
            ->publiclyVisible()
            ->active()
            ->notReported()
            ->pluck('post_id')
            ->all();

        $this->assertSame(
            [$visibleOwnerPostId, $privateOwnerPostId, $inactiveOwnerPostId, $reportedOwnerPostId, $otherOwnerPostId],
            $allPostIds
        );
        $this->assertSame($allPostIds, $withoutGlobalScopePostIds);
        $this->assertSame([$visibleOwnerPostId], $scopedPostIds);
    }

    /**
     * @return list<class-string<Model>>
     */
    private function modelClasses(): array
    {
        $classes = [];

        foreach (glob(app_path('Models').'/*.php') ?: [] as $file) {
            $class = 'App\\Models\\'.pathinfo($file, PATHINFO_FILENAME);

            if (is_subclass_of($class, Model::class)) {
                $classes[] = $class;
            }
        }

        sort($classes);

        return $classes;
    }

    /**
     * @param  list<string>  $directories
     * @return list<string>
     */
    private function sourceMatches(array $directories, string $pattern): array
    {
        $matches = [];

        foreach ($directories as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

            foreach ($files as $file) {
                if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $contents = file_get_contents($file->getPathname()) ?: '';

                if (preg_match($pattern, $contents) === 1) {
                    $matches[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
                }
            }
        }

        sort($matches);

        return $matches;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function insertPost(array $overrides): int
    {
        return (int) Posts::query()->insertGetId($overrides + [
            'user_id' => 1,
            'publisher' => 'post',
            'publisher_id' => 1,
            'post_type' => PostType::General->value,
            'privacy' => Visibility::Public->value,
            'status' => ContentStatus::Active->value,
            'report_status' => 0,
            'tagged_user_ids' => json_encode([]),
            'user_reacts' => json_encode([]),
            'shared_user' => json_encode([]),
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }
}
