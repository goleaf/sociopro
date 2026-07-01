<?php

namespace App\Actions\Addons;

use App\Actions\Install\ImportInstallSqlDump;
use App\Models\Addon;
use App\Support\Addons\AddonPackageImportResult;
use App\Support\Addons\AddonPackageManifest;
use App\Support\Addons\AddonPackageManifestParser;
use Generator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

class ImportAddonPackage
{
    public function __construct(
        private readonly AddonPackageManifestParser $manifestParser,
        private readonly ImportInstallSqlDump $importInstallSqlDump
    ) {}

    public function handle(
        UploadedFile|string $package,
        int $batchSize = ImportInstallSqlDump::DEFAULT_BATCH_SIZE
    ): AddonPackageImportResult {
        if ($batchSize < 1) {
            throw new RuntimeException('Import batch size must be greater than zero.');
        }

        $packagePath = $this->packagePath($package);
        $workspace = storage_path('app/addon-imports/'.Str::uuid()->toString());

        try {
            $this->extractPackage($packagePath, $workspace);
            $packageRoot = $this->packageRoot($workspace);
            $manifest = $this->manifest($packageRoot);
            $this->assertCompatibleVersion($manifest);

            $filesDistributed = $this->distributeSources($packageRoot, $batchSize);
            $addonRowsUpserted = 0;
            $sqlResult = null;

            DB::transaction(function () use (
                $batchSize,
                $manifest,
                $packageRoot,
                &$addonRowsUpserted,
                &$sqlResult
            ): void {
                if ($manifest->isAddon) {
                    $addonRowsUpserted = $this->upsertAddons($manifest);
                }

                $sqlPath = $packageRoot.'/step3_database.sql';

                if (is_file($sqlPath)) {
                    $sqlResult = $this->importInstallSqlDump->handle($sqlPath, $batchSize);
                }

                if (! $manifest->isAddon) {
                    DB::table('settings')
                        ->where('type', 'version')
                        ->update(['description' => $manifest->updateVersion]);
                }
            });

            return new AddonPackageImportResult(
                $this->successMessage($manifest),
                $addonRowsUpserted,
                $filesDistributed,
                $sqlResult
            );
        } finally {
            File::deleteDirectory($workspace);
        }
    }

    private function packagePath(UploadedFile|string $package): string
    {
        $path = $package instanceof UploadedFile ? $package->getRealPath() : $package;

        if (! is_string($path) || ! is_file($path)) {
            throw new RuntimeException('Addon package was not found.');
        }

        if (! is_readable($path)) {
            throw new RuntimeException('Addon package is not readable.');
        }

        return $path;
    }

    private function extractPackage(string $packagePath, string $workspace): void
    {
        File::ensureDirectoryExists($workspace, 0755, true);

        $zip = new ZipArchive;

        if ($zip->open($packagePath) !== true) {
            throw new RuntimeException('Addon package could not be opened.');
        }

        try {
            $this->validateZipEntries($zip);

            if (! $zip->extractTo($workspace)) {
                throw new RuntimeException('Addon package could not be extracted.');
            }
        } finally {
            $zip->close();
        }
    }

    private function validateZipEntries(ZipArchive $zip): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = $zip->getNameIndex($index);

            if (! is_string($name) || $this->isUnsafeZipPath($name)) {
                throw new RuntimeException('Addon package contains an unsafe path.');
            }
        }
    }

    private function isUnsafeZipPath(string $path): bool
    {
        $path = str_replace('\\', '/', $path);

        return $path === ''
            || str_contains($path, "\0")
            || str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:/', $path) === 1
            || preg_match('#(^|/)\.\.(/|$)#', $path) === 1;
    }

    private function packageRoot(string $workspace): string
    {
        if (is_file($workspace.'/step2_config.json')) {
            return $workspace;
        }

        $roots = [];

        foreach (File::directories($workspace) as $directory) {
            if (is_file($directory.'/step2_config.json')) {
                $roots[] = $directory;
            }
        }

        if (count($roots) !== 1) {
            throw new RuntimeException('Addon package must contain one step2_config.json manifest.');
        }

        return $roots[0];
    }

    private function manifest(string $packageRoot): AddonPackageManifest
    {
        $manifestPath = $packageRoot.'/step2_config.json';
        $manifest = file_get_contents($manifestPath);

        if ($manifest === false) {
            throw new RuntimeException('Addon manifest could not be read.');
        }

        return $this->manifestParser->parse($manifest);
    }

    private function assertCompatibleVersion(AddonPackageManifest $manifest): void
    {
        $currentVersion = (string) DB::table('settings')
            ->where('type', 'version')
            ->value('description');

        if ($manifest->isAddon && version_compare($manifest->minimumProductVersion, $currentVersion, '>')) {
            throw new RuntimeException("You have to update your main application's version.");
        }

        if (! $manifest->isAddon && $manifest->minimumProductVersion !== $currentVersion) {
            throw new RuntimeException('It looks like you are skipping a version.');
        }
    }

    private function distributeSources(string $packageRoot, int $batchSize): int
    {
        $sourcesPath = $packageRoot.'/sources';

        if (! is_dir($sourcesPath)) {
            return 0;
        }

        $distributed = 0;
        $batch = [];

        foreach ($this->sourceFiles($sourcesPath) as $sourceFile => $relativePath) {
            $batch[$sourceFile] = $relativePath;

            if (count($batch) >= $batchSize) {
                $distributed += $this->copyBatch($batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $distributed += $this->copyBatch($batch);
        }

        return $distributed;
    }

    /**
     * @return Generator<string, string>
     */
    private function sourceFiles(string $sourcesPath): Generator
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcesPath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if (! $file instanceof \SplFileInfo || ! $file->isFile()) {
                continue;
            }

            $sourceFile = $file->getPathname();
            $relativePath = str_replace('\\', '/', substr($sourceFile, strlen($sourcesPath) + 1));

            if ($this->isUnsafeZipPath($relativePath)) {
                throw new RuntimeException('Addon package contains an unsafe source path.');
            }

            yield $sourceFile => $relativePath;
        }
    }

    /**
     * @param  array<string, string>  $batch
     */
    private function copyBatch(array $batch): int
    {
        $copied = 0;

        foreach ($batch as $sourceFile => $relativePath) {
            $targetPath = base_path($relativePath);

            File::ensureDirectoryExists(dirname($targetPath), 0755, true);

            if (! copy($sourceFile, $targetPath)) {
                throw new RuntimeException('Addon source file could not be copied.');
            }

            $copied++;

            if (strtolower(pathinfo($targetPath, PATHINFO_EXTENSION)) === 'zip') {
                $this->extractNestedZip($targetPath, dirname($targetPath));
                File::delete($targetPath);
            }
        }

        return $copied;
    }

    private function extractNestedZip(string $zipPath, string $destination): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('Addon nested zip could not be opened.');
        }

        try {
            $this->validateZipEntries($zip);

            if (! $zip->extractTo($destination)) {
                throw new RuntimeException('Addon nested zip could not be extracted.');
            }
        } finally {
            $zip->close();
        }
    }

    private function upsertAddons(AddonPackageManifest $manifest): int
    {
        $parentId = null;
        $upserted = 0;

        foreach ($manifest->addons as $addon) {
            $record = Addon::query()->updateOrCreate(
                ['unique_identifier' => $addon['unique_identifier']],
                [
                    'title' => $addon['title'],
                    'version' => $manifest->updateVersion,
                    'features' => $addon['features'],
                    'status' => 1,
                    'parent_id' => $parentId,
                ]
            );

            $parentId ??= $record->id;
            $upserted++;
        }

        return $upserted;
    }

    private function successMessage(AddonPackageManifest $manifest): string
    {
        if (! $manifest->isAddon) {
            return 'Version updated successfully';
        }

        return $manifest->minimumAddonVersion === '0'
            ? 'Addon installed successfully'
            : 'Addon updated successfully';
    }
}
