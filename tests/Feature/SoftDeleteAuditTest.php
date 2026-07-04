<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SoftDeleteAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_documents_soft_delete_decisions_and_required_restore_guardrails(): void
    {
        $path = base_path('docs/soft-delete-audit.md');

        $this->assertFileExists($path);

        $contents = file_get_contents($path) ?: '';

        foreach ([
            'No active soft-delete usage',
            'deleted_at columns',
            'SoftDeletes',
            'indexes',
            'restore',
            'cascade behavior',
            'unique constraints',
            'query expectations',
            'hard deletes',
        ] as $requiredText) {
            $this->assertStringContainsString($requiredText, $contents);
        }
    }

    public function test_models_and_schema_do_not_have_partial_soft_delete_adoption(): void
    {
        $partialAdoptions = [];
        $missingDeletedAtIndexes = [];

        foreach ($this->modelClasses() as $class) {
            $model = new $class;
            $table = $model->getTable();

            if (! Schema::hasTable($table)) {
                continue;
            }

            $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($class), true);
            $hasDeletedAt = Schema::hasColumn($table, 'deleted_at');

            if ($usesSoftDeletes !== $hasDeletedAt) {
                $partialAdoptions[] = sprintf(
                    '%s: table=%s usesSoftDeletes=%s hasDeletedAt=%s',
                    $class,
                    $table,
                    $usesSoftDeletes ? 'yes' : 'no',
                    $hasDeletedAt ? 'yes' : 'no'
                );
            }

            if ($usesSoftDeletes && ! Schema::hasIndex($table, ['deleted_at'])) {
                $missingDeletedAtIndexes[] = "{$class}: {$table}.deleted_at";
            }
        }

        $this->assertSame([], $partialAdoptions);
        $this->assertSame([], $missingDeletedAtIndexes);
    }

    public function test_install_schema_and_project_migrations_do_not_define_unowned_deleted_at_columns(): void
    {
        $matches = [];

        foreach ($this->schemaSourceFiles() as $path) {
            $contents = file_get_contents($path) ?: '';

            if (preg_match('/\b(?:deleted_at|softDeletes|softDeletesTz)\b/i', $contents) === 1) {
                $matches[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
            }
        }

        $this->assertSame([], $matches);
    }

    public function test_current_delete_and_restore_contract_uses_hard_deletes_until_soft_deletes_are_introduced(): void
    {
        $category = new Category;
        $category->forceFill(['name' => 'Soft Delete Audit']);
        $category->save();

        $categoryId = $category->getKey();

        $this->assertFalse(in_array(SoftDeletes::class, class_uses_recursive(Category::class), true));
        $this->assertFalse(Schema::hasColumn('categories', 'deleted_at'));
        $this->assertFalse(method_exists($category, 'restore'));

        $category->delete();

        $this->assertDatabaseMissing('categories', [
            'id' => $categoryId,
        ]);
        $this->assertNull(Category::query()->find($categoryId));
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
     * @return list<string>
     */
    private function schemaSourceFiles(): array
    {
        $files = [];

        foreach (File::files(database_path('migrations')) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file->getPathname();
        }

        return $files;
    }
}
