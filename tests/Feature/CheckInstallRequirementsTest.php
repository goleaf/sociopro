<?php

namespace Tests\Feature;

use App\Actions\Install\CheckInstallRequirements;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionMethod;
use Tests\TestCase;

class CheckInstallRequirementsTest extends TestCase
{
    public function test_handle_returns_local_requirement_rows_with_file_checks_skipped(): void
    {
        $requirements = app(CheckInstallRequirements::class)->handle(isLocalInstall: true);

        $this->assertCount(3, $requirements);
        $this->assertSame([
            'config/database.php',
            'routes/web.php',
            'Curl Enabled',
        ], array_column($requirements, 'label'));
        $this->assertSame([
            true,
            true,
            function_exists('curl_version'),
        ], array_column($requirements, 'passed'));
        $this->assertSame([
            'Skipped on local installation',
            'Skipped on local installation',
            function_exists('curl_version') ? 'Available' : 'Required PHP extension is missing',
        ], array_column($requirements, 'message'));
    }

    public function test_file_requirement_reports_real_file_writability(): void
    {
        $relativePath = 'storage/framework/install-requirements-'.Str::random(12).'.txt';
        File::ensureDirectoryExists(dirname(base_path($relativePath)));
        File::put(base_path($relativePath), 'installer check');

        try {
            $requirement = $this->fileRequirement($relativePath, isLocalInstall: false);
        } finally {
            File::delete(base_path($relativePath));
        }

        $this->assertSame([
            'label' => $relativePath,
            'passed' => true,
            'message' => 'Writable by installer',
        ], $requirement);
    }

    public function test_file_requirement_reports_missing_file_as_failed_for_server_install(): void
    {
        $relativePath = 'storage/framework/missing-install-requirements-'.Str::random(12).'.txt';
        File::delete(base_path($relativePath));

        $this->assertSame([
            'label' => $relativePath,
            'passed' => false,
            'message' => 'Needs write permission',
        ], $this->fileRequirement($relativePath, isLocalInstall: false));
    }

    public function test_file_requirement_skips_file_checks_for_local_install(): void
    {
        $relativePath = 'storage/framework/missing-local-install-requirements-'.Str::random(12).'.txt';
        File::delete(base_path($relativePath));

        $this->assertSame([
            'label' => $relativePath,
            'passed' => true,
            'message' => 'Skipped on local installation',
        ], $this->fileRequirement($relativePath, isLocalInstall: true));
    }

    /**
     * @return array{label: string, passed: bool, message: string}
     */
    private function fileRequirement(string $relativePath, bool $isLocalInstall): array
    {
        $method = new ReflectionMethod(CheckInstallRequirements::class, 'fileRequirement');

        return $method->invoke(app(CheckInstallRequirements::class), $relativePath, $isLocalInstall);
    }
}
