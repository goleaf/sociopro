<?php

namespace Tests\Feature;

use Tests\TestCase;

class JsonColumnAuditDocumentationTest extends TestCase
{
    public function test_json_column_audit_documents_shapes_indexes_and_deferred_casts(): void
    {
        $path = base_path('docs/json-column-audit.md');

        $this->assertFileExists($path);

        $contents = file_get_contents($path);

        $this->assertIsString($contents);

        foreach ($this->requiredAuditTerms() as $term) {
            $this->assertStringContainsString($term, $contents);
        }
    }

    /**
     * @return list<string>
     */
    private function requiredAuditTerms(): array
    {
        return [
            'payment_gateways.keys',
            'payment_histories.transaction_keys',
            'live_streamings.details',
            'users.friends',
            'posts.tagged_user_ids',
            'settings.description',
            'whereJsonContains',
            'generated columns',
            'unindexed JSON',
            'deferred casts',
        ];
    }
}
