<?php

namespace Tests\Feature;

use App\Logging\SanitizeLogContext;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

class ProductionDebugInstrumentationTest extends TestCase
{
    public function test_production_code_has_no_temporary_query_logging_or_dump_debugging(): void
    {
        $offenders = [];
        $patterns = [
            'DB::listen' => '/\bDB::listen\s*\(/',
            'QueryExecuted listener' => '/\bQueryExecuted::class\b|\bQueryExecuted\s+\$/',
            'query log enablement' => '/\benableQueryLog\s*\(/',
            'query log reads' => '/\bgetQueryLog\s*\(/',
            'database pretend logging' => '/\bpretend\s*\(/',
            'dump statement' => '/(?<!function\s)\bdump\s*\(/',
            'dd statement' => '/\bdd\s*\(/',
            'ray statement' => '/\bray\s*\(/',
            'var_dump statement' => '/\bvar_dump\s*\(/',
            'print_r statement' => '/\bprint_r\s*\(/',
            'browser console statement' => '/\bconsole\.(?:debug|error|info|log|warn)\s*\(/',
            'verbose sql logging' => '/\bLog::(?:debug|info|notice|warning|error)\s*\([^;\n]*(?:sql|query)/i',
        ];

        foreach ($this->productionFiles() as $file) {
            $contents = file_get_contents($file->getPathname());

            if (! is_string($contents)) {
                continue;
            }

            $code = $this->withoutComments($contents);

            foreach ($patterns as $label => $pattern) {
                if (preg_match($pattern, $code) === 1) {
                    $offenders[] = $this->relativePath($file).": {$label}";
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Remove temporary DB query logging, debug listeners, dump statements, and verbose SQL logs from production code.'
        );
    }

    public function test_exception_logs_are_structured_without_raw_exception_messages(): void
    {
        $offenders = [];

        foreach ($this->productionFiles() as $file) {
            $contents = file_get_contents($file->getPathname());

            if (! is_string($contents)) {
                continue;
            }

            $code = $this->withoutComments($contents);

            if (preg_match('/\bLog::(?:debug|info|notice|warning|error|critical|alert|emergency)\s*\([^;]*->getMessage\s*\(/s', $code) === 1) {
                $offenders[] = $this->relativePath($file);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Log stable event names and allowlisted context instead of raw exception messages, which can expose sensitive input.'
        );
    }

    public function test_log_channels_use_sensitive_context_sanitizer(): void
    {
        $channels = config('logging.channels');
        $channels = is_array($channels) ? $channels : [];

        foreach (['single', 'daily', 'slack', 'papertrail', 'stderr', 'syslog', 'errorlog', 'emergency'] as $channel) {
            $this->assertContains(
                SanitizeLogContext::class,
                $channels[$channel]['tap'] ?? [],
                "Log channel [{$channel}] must sanitize passwords, tokens, cookies, headers, PII, payment data, raw payloads, secrets, and file contents."
            );
        }
    }

    public function test_production_logs_do_not_pass_raw_request_or_file_payloads(): void
    {
        $offenders = [];
        $patterns = [
            'raw request all logging' => '/\bLog::(?:debug|info|notice|warning|error|critical|alert|emergency)\s*\([^;]*(?:request\(\)|\$request)->all\s*\(/s',
            'raw request input logging' => '/\bLog::(?:debug|info|notice|warning|error|critical|alert|emergency)\s*\([^;]*(?:request\(\)|\$request)->input\s*\(\s*\)/s',
            'raw request headers logging' => '/\bLog::(?:debug|info|notice|warning|error|critical|alert|emergency)\s*\([^;]*(?:request\(\)|\$request)->headers->all\s*\(/s',
            'raw request body logging' => '/\bLog::(?:debug|info|notice|warning|error|critical|alert|emergency)\s*\([^;]*(?:request\(\)|\$request)->getContent\s*\(/s',
            'raw file contents logging' => '/\bLog::(?:debug|info|notice|warning|error|critical|alert|emergency)\s*\([^;]*file_get_contents\s*\(/s',
        ];

        foreach ($this->productionFiles() as $file) {
            $contents = file_get_contents($file->getPathname());

            if (! is_string($contents)) {
                continue;
            }

            $code = $this->withoutComments($contents);

            foreach ($patterns as $label => $pattern) {
                if (preg_match($pattern, $code) === 1) {
                    $offenders[] = $this->relativePath($file).": {$label}";
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Log sanitized summaries instead of raw request payloads, headers, bodies, or file contents.'
        );
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function productionFiles(): iterable
    {
        foreach ([app_path(), base_path('routes'), config_path(), resource_path('views'), resource_path('js')] as $root) {
            if (! is_dir($root)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

            foreach ($iterator as $file) {
                if (! $file instanceof SplFileInfo || ! $file->isFile()) {
                    continue;
                }

                if (in_array($file->getExtension(), ['php', 'js', 'ts'], true)) {
                    yield $file;
                }
            }
        }
    }

    private function withoutComments(string $contents): string
    {
        return (string) preg_replace([
            '/\/\*.*?\*\//s',
            '/^\s*\/\/.*$/m',
            '/^\s*#.*$/m',
            '/{{--.*?--}}/s',
        ], '', $contents);
    }

    private function relativePath(SplFileInfo $file): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $file->getPathname());
    }
}
