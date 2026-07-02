<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CodeCleanupStandardsDocumentationTest extends TestCase
{
    public function test_code_cleanup_standards_cover_senior_laravel_micro_rules(): void
    {
        $contents = File::get(base_path('docs/code-cleanup-standards.md'));

        $requiredPhrases = [
            'Rules 41-100 Coverage',
            'Eloquent model classes represent one entity',
            'Use one action method convention',
            'Every HTTP request to an external service must define a timeout',
            'Boolean columns should read as questions',
            'Date/time columns should normally end in `_at`',
            'Count columns should end in `_count`',
            'Money columns must communicate amount and currency or minor units',
            'Status fields require an enum or constants',
            'Do not run queries inside loops',
            'Scope ownership in the query when possible',
            'Factory default states must create valid records',
            'Uploaded files belong on Laravel disks',
            'Retryable jobs must be idempotent',
            'Events should be facts that already happened',
        ];

        foreach ($requiredPhrases as $phrase) {
            $this->assertStringContainsString($phrase, $contents);
        }
    }

    public function test_risky_legacy_model_renames_are_documented_before_renaming(): void
    {
        $contents = File::get(base_path('docs/code-cleanup-standards.md'));

        foreach (['Posts', 'Comments', 'Albums', 'Stories', 'Users', 'Friendships', 'PaidContentPackages'] as $legacyModel) {
            $this->assertStringContainsString($legacyModel, $contents);
        }

        $this->assertStringContainsString('dedicated compatibility slice', $contents);
        $this->assertStringContainsString('Do not rename database tables', $contents);
    }
}
