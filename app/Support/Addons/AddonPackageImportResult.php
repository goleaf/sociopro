<?php

namespace App\Support\Addons;

use App\Support\Install\InstallSqlImportResult;

final class AddonPackageImportResult
{
    public function __construct(
        public readonly string $message,
        public readonly int $addonRowsUpserted,
        public readonly int $filesDistributed,
        public readonly ?InstallSqlImportResult $sqlResult,
        public readonly bool $skippedPhpSteps = true
    ) {}
}
