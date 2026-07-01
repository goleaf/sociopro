<?php

namespace App\Jobs;

use App\Actions\Install\ImportInstallSqlDump;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportInstallSqlDumpJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $dumpPath,
        public readonly int $batchSize = ImportInstallSqlDump::DEFAULT_BATCH_SIZE
    ) {}

    public function handle(ImportInstallSqlDump $importInstallSqlDump): void
    {
        $importInstallSqlDump->handle($this->dumpPath, $this->batchSize);
    }
}
