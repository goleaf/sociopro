<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\PageCategory;
use App\Models\SaveForLater;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class NamingStandardsAuditTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const CLASS_BACKED_ROOTS = [
        'app',
        'database/factories',
        'database/seeders',
        'tests',
    ];

    public function test_php_class_names_and_class_backed_files_do_not_use_underscores(): void
    {
        $offenders = [];

        foreach ($this->phpFiles(self::CLASS_BACKED_ROOTS) as $path) {
            $relativePath = $this->relativePath($path);
            $classNames = $this->classLikeNames(File::get($path));

            if (str_contains(pathinfo($path, PATHINFO_FILENAME), '_')) {
                $offenders[] = "{$relativePath}: filename contains underscore";
            }

            foreach ($classNames as $className) {
                if (str_contains($className, '_')) {
                    $offenders[] = "{$relativePath}: {$className} contains underscore";
                }
            }

            if (count($classNames) === 1 && pathinfo($path, PATHINFO_FILENAME) !== $classNames[0]) {
                $offenders[] = "{$relativePath}: filename does not match {$classNames[0]}";
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Use StudlyCase class names and matching class-backed PHP filenames without underscores.'
        );
    }

    public function test_legacy_underscored_model_class_names_are_absent_from_runtime_code(): void
    {
        $offenders = [];
        $legacyTokens = [
            'Account'.'_active_request',
            'Album'.'_image',
            'Feeling'.'_and_activities',
            'Fundraiser'.'_category',
            'Fundraiser'.'_donation',
            'Fundraiser'.'_payout',
            'Group'.'_member',
            'Live'.'_streamings',
            'Media'.'_files',
            'Message'.'_thrade',
            'Message'.'_thradeFactory',
            'Message'.'Thrade',
            'Message'.'ThradeFactory',
            'Page'.'_like',
            'Payment'.'_gateway',
            'Post'.'_share',
        ];

        foreach ($this->phpFiles(['app', 'database/factories', 'database/migrations', 'database/seeders', 'routes', 'config', 'resources/views', 'tests']) as $path) {
            $contents = File::get($path);

            foreach ($legacyTokens as $legacyToken) {
                if (str_contains($contents, $legacyToken)) {
                    $offenders[] = $this->relativePath($path).": {$legacyToken}";
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Use StudlyCase PHP model class references. Keep legacy database names documented separately.'
        );
    }

    public function test_legacy_non_studly_compound_class_names_are_absent_from_runtime_code(): void
    {
        $offenders = [];
        $legacyTokens = [
            'Blog'.'category',
            'Page'.'category',
            'Page'.'categoryFactory',
            'Save'.'forlater',
        ];

        foreach ($this->phpFiles(['app', 'database/factories', 'database/seeders', 'routes', 'config', 'tests']) as $path) {
            $contents = File::get($path);

            foreach ($legacyTokens as $legacyToken) {
                if (str_contains($contents, $legacyToken)) {
                    $offenders[] = $this->relativePath($path).": {$legacyToken}";
                }
            }

            foreach ($legacyTokens as $legacyToken) {
                if (str_contains(pathinfo($path, PATHINFO_FILENAME), $legacyToken)) {
                    $offenders[] = $this->relativePath($path).': filename uses '.$legacyToken;
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Use StudlyCase compound class names such as BlogCategory, PageCategory, and SaveForLater.'
        );
    }

    public function test_renamed_legacy_compound_models_keep_existing_table_contracts(): void
    {
        $this->assertSame('blogcategories', (new BlogCategory)->getTable());
        $this->assertSame('pagecategories', (new PageCategory)->getTable());
        $this->assertSame('saveforlaters', (new SaveForLater)->getTable());
    }

    /**
     * @param  list<string>  $roots
     * @return list<string>
     */
    private function phpFiles(array $roots): array
    {
        return collect($roots)
            ->map(fn (string $root): string => base_path($root))
            ->filter(fn (string $root): bool => File::isDirectory($root))
            ->flatMap(fn (string $root) => File::allFiles($root))
            ->filter(fn ($file): bool => $file->getExtension() === 'php')
            ->map(fn ($file): string => $file->getRealPath())
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function classLikeNames(string $contents): array
    {
        $tokens = token_get_all($contents);
        $classTokens = [T_CLASS, T_INTERFACE, T_TRAIT];

        if (defined('T_ENUM')) {
            $classTokens[] = T_ENUM;
        }

        $names = [];

        foreach ($tokens as $index => $token) {
            if (! is_array($token) || ! in_array($token[0], $classTokens, true)) {
                continue;
            }

            $previousToken = $this->previousMeaningfulToken($tokens, $index);

            if (is_array($previousToken) && $previousToken[0] === T_DOUBLE_COLON) {
                continue;
            }

            $name = $this->nextClassLikeName($tokens, $index);

            if ($name !== null) {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * @param  list<mixed>  $tokens
     */
    private function previousMeaningfulToken(array $tokens, int $index): mixed
    {
        for ($cursor = $index - 1; $cursor >= 0; $cursor--) {
            $token = $tokens[$cursor];

            if (is_array($token) && $token[0] === T_WHITESPACE) {
                continue;
            }

            return $token;
        }

        return null;
    }

    /**
     * @param  list<mixed>  $tokens
     */
    private function nextClassLikeName(array $tokens, int $index): ?string
    {
        for ($cursor = $index + 1, $count = count($tokens); $cursor < $count; $cursor++) {
            $token = $tokens[$cursor];

            if (is_array($token) && $token[0] === T_STRING) {
                return $token[1];
            }

            if (is_array($token) && in_array($token[0], [T_EXTENDS, T_IMPLEMENTS], true)) {
                return null;
            }

            if ($token === '(' || $token === '{' || $token === ';') {
                return null;
            }
        }

        return null;
    }

    private function relativePath(string $path): string
    {
        return str_replace(base_path().DIRECTORY_SEPARATOR, '', $path);
    }
}
