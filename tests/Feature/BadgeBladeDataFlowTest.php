<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BadgeBladeDataFlowTest extends TestCase
{
    public function test_badge_blade_views_use_prepared_data_instead_of_runtime_queries(): void
    {
        $forbiddenTokens = [
            'App\\Models',
            '\\App\\Models',
            'DB::',
            'auth()->user()',
            'Carbon::now',
            'new DateTime',
        ];

        foreach ($this->badgeViews() as $view) {
            $contents = File::get(resource_path("views/{$view}"));

            foreach ($forbiddenTokens as $token) {
                $this->assertStringNotContainsString(
                    $token,
                    $contents,
                    "{$view} should receive prepared data from the controller/action instead of querying or resolving auth state in Blade."
                );
            }
        }
    }

    /**
     * @return list<string>
     */
    private function badgeViews(): array
    {
        return [
            'frontend/badge/badge.blade.php',
            'frontend/badge/badge_info.blade.php',
        ];
    }
}
