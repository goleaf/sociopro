<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RawSqlSafetyAuditTest extends TestCase
{
    /**
     * @var array<string, string>
     */
    private const ALLOWED_RAW_QUERY_FILES = [
        'app/Actions/Install/ImportInstallSqlDump.php' => 'legacy installer SQL dump import; replace only with a tested migration-baseline plan',
        'app/Queries/Marketplace/MarketplaceProductsQuery.php' => 'portable LIKE ESCAPE clause with allowlisted columns and bound search text',
    ];

    public function test_application_code_does_not_introduce_unreviewed_raw_query_helpers(): void
    {
        $offenders = [];
        $patterns = [
            'DB raw helper' => '/\bDB::(?:select|statement|raw|unprepared)\s*\(/',
            'query raw helper' => '/->(?:whereRaw|orWhereRaw|havingRaw|orderByRaw|selectRaw|fromRaw|joinRaw|groupByRaw)\s*\(/',
            'PDO raw execution' => '/->getPdo\(\)->exec\s*\(/',
        ];

        foreach ($this->sourceFiles() as $path) {
            $relativePath = str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
            $contents = $this->withoutComments(File::get($path));

            foreach ($patterns as $label => $pattern) {
                if (preg_match($pattern, $contents) !== 1) {
                    continue;
                }

                if (array_key_exists($relativePath, self::ALLOWED_RAW_QUERY_FILES)) {
                    continue;
                }

                $offenders[] = "{$relativePath}: {$label}";
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Use Eloquent/query builder methods or bound parameters instead of raw SQL helpers.\n".implode("\n", $offenders)
        );
    }

    public function test_marketplace_raw_like_clause_keeps_user_input_in_bindings(): void
    {
        $contents = $this->withoutComments(File::get(app_path('Queries/Marketplace/MarketplaceProductsQuery.php')));

        $this->assertStringContainsString('LIKE_SEARCH_COLUMNS', $contents);
        $this->assertStringContainsString('$query->getQuery()->getGrammar()->wrap($column)', $contents);
        $this->assertStringContainsString('LIKE ? ESCAPE', $contents);
        $this->assertStringContainsString('[$pattern]', $contents);
        $this->assertStringNotContainsString("LIKE '%", $contents);
    }

    /**
     * @return list<string>
     */
    private function sourceFiles(): array
    {
        return collect([
            'app',
            'routes',
            'config',
            'database',
        ])
            ->map(fn (string $directory): string => base_path($directory))
            ->filter(fn (string $path): bool => File::isDirectory($path))
            ->flatMap(fn (string $path) => File::allFiles($path))
            ->filter(fn ($file): bool => $file->getExtension() === 'php')
            ->map(fn ($file): string => $file->getRealPath())
            ->values()
            ->all();
    }

    private function withoutComments(string $contents): string
    {
        $tokens = token_get_all($contents);

        return collect($tokens)
            ->reject(fn ($token): bool => is_array($token) && in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true))
            ->map(fn ($token): string => is_array($token) ? $token[1] : $token)
            ->implode('');
    }
}
