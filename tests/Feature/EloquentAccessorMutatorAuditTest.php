<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EloquentAccessorMutatorAuditTest extends TestCase
{
    public function test_project_documents_accessor_and_mutator_policy(): void
    {
        $path = base_path('docs/accessors-mutators-audit.md');

        $this->assertFileExists($path);

        $contents = file_get_contents($path) ?: '';

        foreach ([
            'Only the chat naming compatibility adapter currently defines Eloquent accessors or mutators',
            'App\Models\Chat',
            'App\Models\MessageThread',
            'Serialization leaks',
            'app/ViewModels',
            'resources/view models',
        ] as $requiredText) {
            $this->assertStringContainsString($requiredText, $contents);
        }
    }

    public function test_models_do_not_define_unreviewed_eloquent_accessors_or_mutators(): void
    {
        foreach ($this->modelSourceFiles() as $file) {
            $contents = file_get_contents($file) ?: '';
            $relativePath = str_replace(base_path().DIRECTORY_SEPARATOR, '', $file);

            if (in_array($relativePath, $this->allowedAccessorAndMutatorFiles(), true)) {
                continue;
            }

            foreach ($this->accessorAndMutatorPatterns() as $description => $pattern) {
                $this->assertDoesNotMatchRegularExpression(
                    $pattern,
                    $contents,
                    "{$relativePath} defines {$description}; keep accessors and mutators cheap, pure, and explicitly tested before allowing them."
                );
            }
        }
    }

    public function test_models_do_not_append_computed_attributes_to_serialization(): void
    {
        foreach ($this->modelClasses() as $class) {
            $model = new $class;

            $this->assertSame(
                [],
                $model->getAppends(),
                "{$class} appends computed attributes to serialization; move presentation formatting to resources or view models unless explicitly tested."
            );
        }
    }

    public function test_empty_model_serialization_does_not_trigger_queries(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        foreach ($this->modelClasses() as $class) {
            (new $class)->toArray();
        }

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertSame(
            [],
            $queries,
            'Model serialization triggered database queries; move hidden query work to eager-loaded relationships, resources, or view models.'
        );
    }

    /**
     * @return list<class-string<Model>>
     */
    private function modelClasses(): array
    {
        return EloquentModelAuditTest::modelClasses();
    }

    /**
     * @return list<string>
     */
    private function modelSourceFiles(): array
    {
        $files = glob(app_path('Models').'/*.php') ?: [];

        sort($files);

        return array_values($files);
    }

    /**
     * @return list<string>
     */
    private function allowedAccessorAndMutatorFiles(): array
    {
        return [
            'app/Models/Chat.php',
            'app/Models/MessageThread.php',
        ];
    }

    /**
     * @return array<string, non-empty-string>
     */
    private function accessorAndMutatorPatterns(): array
    {
        return [
            'a legacy getFooAttribute accessor' => '/function\s+get[A-Z][A-Za-z0-9_]*Attribute\s*\(/',
            'a legacy setFooAttribute mutator' => '/function\s+set[A-Z][A-Za-z0-9_]*Attribute\s*\(/',
            'an Attribute::make accessor or mutator' => '/Attribute::make\s*\(/',
            'a typed Attribute return accessor or mutator' => '/(?:public|protected)\s+function\s+[A-Za-z_][A-Za-z0-9_]*\s*\([^)]*\)\s*:\s*Attribute\b/',
        ];
    }
}
