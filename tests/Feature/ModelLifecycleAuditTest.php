<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ModelLifecycleAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_documents_model_lifecycle_policy(): void
    {
        $path = base_path('docs/model-lifecycle-audit.md');

        $this->assertFileExists($path);

        $contents = file_get_contents($path) ?: '';

        foreach ([
            'No model observers are currently registered',
            'No custom Eloquent lifecycle hooks are currently defined',
            'create/update/delete',
            'queued work',
        ] as $requiredText) {
            $this->assertStringContainsString($requiredText, $contents);
        }
    }

    public function test_application_does_not_register_model_observers_or_eloquent_event_listeners(): void
    {
        $this->assertDirectoryDoesNotExist(app_path('Observers'));

        foreach ($this->phpFiles(app_path()) as $path => $contents) {
            foreach ($this->observerRegistrationPatterns() as $description => $pattern) {
                $this->assertDoesNotMatchRegularExpression(
                    $pattern,
                    $contents,
                    "{$path} {$description}; model lifecycle side effects must be explicit, tested, and queued when slow."
                );
            }
        }
    }

    public function test_models_do_not_define_hidden_lifecycle_hooks_or_dispatches_events(): void
    {
        foreach ($this->phpFiles(app_path('Models')) as $path => $contents) {
            foreach ($this->modelLifecyclePatterns() as $description => $pattern) {
                $this->assertDoesNotMatchRegularExpression(
                    $pattern,
                    $contents,
                    "{$path} {$description}; move business side effects to actions, services, jobs, or explicit event listeners with tests."
                );
            }
        }
    }

    public function test_models_do_not_dispatch_background_side_effects_directly(): void
    {
        foreach ($this->phpFiles(app_path('Models')) as $path => $contents) {
            foreach ($this->modelSideEffectPatterns() as $description => $pattern) {
                $this->assertDoesNotMatchRegularExpression(
                    $pattern,
                    $contents,
                    "{$path} {$description}; model persistence must not hide queued, mail, notification, HTTP, or broadcast side effects."
                );
            }
        }
    }

    public function test_basic_model_create_update_delete_lifecycle_events_fire_once_without_side_effects(): void
    {
        Event::fake();
        Mail::fake();
        Notification::fake();
        Queue::fake();

        $category = new Category;
        $category->forceFill(['name' => 'Lifecycle Audit']);
        $category->save();

        $category->forceFill(['name' => 'Lifecycle Audit Updated']);
        $category->save();

        $categoryId = $category->getKey();
        $category->delete();

        Event::assertDispatched('eloquent.created: '.Category::class, 1);
        Event::assertDispatched('eloquent.updated: '.Category::class, 1);
        Event::assertDispatched('eloquent.deleted: '.Category::class, 1);
        Mail::assertNothingOutgoing();
        Notification::assertNothingSent();
        Queue::assertNothingPushed();

        $this->assertDatabaseMissing('categories', [
            'id' => $categoryId,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function phpFiles(string $path): array
    {
        if (! File::isDirectory($path)) {
            return [];
        }

        $files = [];

        foreach (File::allFiles($path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $files[$file->getRelativePathname()] = (string) file_get_contents($file->getPathname());
        }

        return $files;
    }

    /**
     * @return array<string, non-empty-string>
     */
    private function observerRegistrationPatterns(): array
    {
        return [
            'registers an observer' => '/::observe\s*\(/',
            'listens to raw Eloquent lifecycle events' => '/Event::listen\s*\(\s*[\'"]eloquent\./',
        ];
    }

    /**
     * @return array<string, non-empty-string>
     */
    private function modelLifecyclePatterns(): array
    {
        return [
            'declares dispatchesEvents' => '/\$dispatchesEvents\b/',
            'defines a boot method' => '/(?:public|protected)\s+static\s+function\s+boot\s*\(/',
            'defines a booted method' => '/(?:public|protected)\s+static\s+function\s+booted\s*\(/',
            'registers an Eloquent lifecycle callback' => '/static::(?:creating|created|updating|updated|saving|saved|deleting|deleted|restoring|restored|replicating)\s*\(/',
        ];
    }

    /**
     * @return array<string, non-empty-string>
     */
    private function modelSideEffectPatterns(): array
    {
        return [
            'dispatches work directly from a model' => '/\b(?:dispatch|dispatch_sync|dispatchSync|dispatchAfterResponse)\s*\(/',
            'uses a queue facade from a model' => '/\b(?:Queue|Bus)::/',
            'sends mail from a model' => '/\bMail::/',
            'sends notifications from a model' => '/\bNotification::/',
            'performs HTTP calls from a model' => '/\bHttp::/',
            'broadcasts from a model' => '/\bbroadcast\s*\(/',
        ];
    }
}
