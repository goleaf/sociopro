<?php

namespace Tests\Unit;

use App\Support\Addons\AddonPackageManifestParser;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AddonPackageManifestParserTest extends TestCase
{
    public function test_it_parses_addon_manifest_metadata(): void
    {
        $manifest = (new AddonPackageManifestParser)->parse(<<<'JSON'
{
  "is_addon": "1",
  "product_version": {"minimum_required_version": "2.6.1"},
  "addon_version": {"minimum_required_version": "0", "update_version": "1.2.3"},
  "addons": [
    {"unique_identifier": "jobs", "title": "Jobs", "features": "job board"}
  ]
}
JSON);

        $this->assertTrue($manifest->isAddon);
        $this->assertSame('2.6.1', $manifest->minimumProductVersion);
        $this->assertSame('1.2.3', $manifest->updateVersion);
        $this->assertSame([
            [
                'unique_identifier' => 'jobs',
                'title' => 'Jobs',
                'features' => 'job board',
            ],
        ], $manifest->addons);
    }

    public function test_it_rejects_missing_addon_identifiers(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Addon manifest entry is missing a unique identifier.');

        (new AddonPackageManifestParser)->parse(<<<'JSON'
{
  "is_addon": "1",
  "product_version": {"minimum_required_version": "2.6.1"},
  "addon_version": {"minimum_required_version": "0", "update_version": "1.2.3"},
  "addons": [{"title": "Broken addon"}]
}
JSON);
    }
}
