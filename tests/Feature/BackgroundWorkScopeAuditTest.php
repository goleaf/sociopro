<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BackgroundWorkScopeAuditTest extends TestCase
{
    public function test_legacy_placeholder_console_commands_are_not_registered(): void
    {
        $commandFiles = $this->phpFiles(app_path('Console/Commands'));

        $this->assertIsArray($commandFiles);

        foreach ($commandFiles as $path => $contents) {
            $this->assertStringNotContainsString(
                "protected \$signature = 'command:name'",
                $contents,
                "{$path} still registers Laravel's placeholder command:name signature."
            );
        }
    }

    public function test_queueable_application_handlers_do_not_depend_on_request_authentication(): void
    {
        $queueableFiles = $this->queueableApplicationFiles();

        $this->assertIsArray($queueableFiles);

        foreach ($queueableFiles as $path => $contents) {
            $this->assertDoesNotMatchRegularExpression(
                '/\b(auth\s*\(|Auth::(?:user|id|check)\s*\()/',
                $contents,
                "{$path} uses request authentication inside queueable work; delayed or retried jobs need explicit serialized scope context."
            );
        }
    }

    public function test_queueable_application_handlers_guard_scoped_records_before_processing(): void
    {
        $queueableFiles = $this->queueableApplicationFiles();

        $this->assertIsArray($queueableFiles);

        foreach ($queueableFiles as $path => $contents) {
            if (! $this->touchesScopedRecords($contents)) {
                continue;
            }

            $this->assertMatchesRegularExpression(
                '/Gate::|authorize\s*\(|->can\s*\(|forUser\s*\(|where(?:In)?\s*\(\s*[\'"](?:user_id|sender_user_id|reciver_user_id|tenant_id|team_id|account_id|organization_id)[\'"]|Policy|scope[A-Z]/',
                $contents,
                "{$path} touches user or tenant scoped records without an obvious authorization or ownership filter."
            );
        }
    }

    public function test_background_dispatches_do_not_use_anonymous_closure_jobs(): void
    {
        foreach ($this->applicationAndRouteFiles() as $path => $contents) {
            $this->assertDoesNotMatchRegularExpression(
                '/\b(?:dispatch|dispatch_sync)\s*\(\s*(?:static\s+)?(?:function|\fn)\b|(?:Bus::dispatch|Queue::(?:push|later))\s*\(\s*(?:static\s+)?(?:function|\fn)\b/s',
                $contents,
                "{$path} dispatches anonymous background work; use a named job with serialized user or tenant scope."
            );
        }
    }

    public function test_no_unreviewed_scheduled_background_work_is_registered(): void
    {
        $kernel = file_get_contents(app_path('Console/Kernel.php'));

        preg_match_all('/\$schedule->(?:command|job|call)\s*\(/', (string) $kernel, $scheduledWork);

        $this->assertCount(
            0,
            $scheduledWork[0],
            'Scheduled background work must be added with focused cross-scope authorization tests.'
        );
    }

    /**
     * @return array<string, string>
     */
    private function queueableApplicationFiles(): array
    {
        return collect($this->phpFiles(app_path()))
            ->filter(fn (string $contents): bool => str_contains($contents, 'ShouldQueue'))
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function applicationAndRouteFiles(): array
    {
        return [
            ...$this->phpFiles(app_path()),
            ...$this->phpFiles(base_path('routes')),
        ];
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

            $files[$file->getPathname()] = (string) file_get_contents($file->getPathname());
        }

        return $files;
    }

    private function touchesScopedRecords(string $contents): bool
    {
        return preg_match(
            '/\b(?:user_id|sender_user_id|reciver_user_id|tenant_id|team_id|account_id|organization_id)\b|::(?:find|findOrFail)\s*\(/',
            $contents
        ) === 1;
    }
}
