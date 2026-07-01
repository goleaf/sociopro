<?php

namespace App\Jobs;

use App\Actions\Addons\ImportAddonPackage;
use App\Actions\Install\ImportInstallSqlDump;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportAddonPackageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $packagePath,
        public readonly int $batchSize = ImportInstallSqlDump::DEFAULT_BATCH_SIZE
    ) {}

    public function handle(ImportAddonPackage $importAddonPackage): void
    {
        $importAddonPackage->handle($this->packagePath, $this->batchSize);
    }
}
