<?php

namespace App\Console\Commands;

use App\Models\Payment_gateway;
use App\Models\PaymentHistoryEntry;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class BackfillLegacyJsonColumns extends Command
{
    private const EMPTY_JSON_OBJECT = '{}';

    protected $signature = 'legacy:backfill-json-columns
        {--dry-run : Report cleanable rows without updating them}
        {--chunk=500 : Number of rows to scan per chunk}';

    protected $description = 'Backfill safe blank legacy JSON payloads before JSON column migrations.';

    public function handle(): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN: no rows will be updated.');
        }

        $summary = $this->emptyStats();

        foreach ($this->targets() as $target) {
            $stats = $this->backfillTarget(
                new $target['model'],
                $target['column'],
                $target['label'],
                $chunkSize,
                $dryRun
            );

            $summary = $this->mergeStats($summary, $stats);
        }

        $this->line(sprintf(
            'Backfill summary: processed=%d valid=%d updated=%d would_update=%d blockers=%d',
            $summary['processed'],
            $summary['valid'],
            $summary['updated'],
            $summary['would_update'],
            $summary['blockers']
        ));

        if ($summary['blockers'] > 0) {
            $this->error('Malformed non-blank JSON values remain. Review and fix them manually before rerunning the JSON column migration.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @return list<array{label: string, model: class-string<Model>, column: string}>
     */
    private function targets(): array
    {
        return [
            [
                'label' => 'payment_gateways.keys',
                'model' => Payment_gateway::class,
                'column' => 'keys',
            ],
            [
                'label' => 'payment_histories.transaction_keys',
                'model' => PaymentHistoryEntry::class,
                'column' => 'transaction_keys',
            ],
        ];
    }

    /**
     * @return array{processed: int, valid: int, updated: int, would_update: int, blockers: int}
     */
    private function backfillTarget(Model $prototype, string $column, string $label, int $chunkSize, bool $dryRun): array
    {
        $stats = $this->emptyStats();

        $this->line("Scanning {$label} in chunks of {$chunkSize}.");

        $prototype->newQuery()
            ->whereNotNull($column)
            ->chunkById($chunkSize, function (Collection $models) use ($column, $label, $dryRun, &$stats): void {
                $chunkStats = $this->emptyStats();

                foreach ($models as $model) {
                    if (! $model instanceof Model) {
                        continue;
                    }

                    $rowStats = $this->processModel($model, $column, $dryRun);
                    $chunkStats = $this->mergeStats($chunkStats, $rowStats);
                    $stats = $this->mergeStats($stats, $rowStats);
                }

                $this->line(sprintf(
                    '%s chunk: processed=%d updated=%d would_update=%d blockers=%d',
                    $label,
                    $chunkStats['processed'],
                    $chunkStats['updated'],
                    $chunkStats['would_update'],
                    $chunkStats['blockers']
                ));
            }, $prototype->getKeyName());

        $this->line(sprintf(
            '%s summary: processed=%d valid=%d updated=%d would_update=%d blockers=%d',
            $label,
            $stats['processed'],
            $stats['valid'],
            $stats['updated'],
            $stats['would_update'],
            $stats['blockers']
        ));

        return $stats;
    }

    /**
     * @return array{processed: int, valid: int, updated: int, would_update: int, blockers: int}
     */
    private function processModel(Model $model, string $column, bool $dryRun): array
    {
        $stats = $this->emptyStats();
        $stats['processed']++;

        $value = trim((string) $model->getRawOriginal($column));

        $replacement = $this->replacementJson($value);

        if ($replacement !== null) {
            if ($dryRun) {
                $stats['would_update']++;

                return $stats;
            }

            $model::withoutTimestamps(function () use ($model, $column, $replacement): void {
                $model->newQuery()
                    ->whereKey($model->getKey())
                    ->update([$column => $replacement]);
            });

            $stats['updated']++;

            return $stats;
        }

        if ($this->isSafeJsonArray($value)) {
            $stats['valid']++;

            return $stats;
        }

        $stats['blockers']++;

        return $stats;
    }

    private function replacementJson(string $value): ?string
    {
        if ($value === '') {
            return self::EMPTY_JSON_OBJECT;
        }

        $unescaped = stripcslashes($value);

        if ($unescaped === $value) {
            return null;
        }

        $decoded = json_decode($unescaped, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return null;
        }

        $encoded = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($encoded) ? $encoded : null;
    }

    private function isSafeJsonArray(string $value): bool
    {
        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded);
    }

    /**
     * @return array{processed: int, valid: int, updated: int, would_update: int, blockers: int}
     */
    private function emptyStats(): array
    {
        return [
            'processed' => 0,
            'valid' => 0,
            'updated' => 0,
            'would_update' => 0,
            'blockers' => 0,
        ];
    }

    /**
     * @param  array{processed: int, valid: int, updated: int, would_update: int, blockers: int}  $left
     * @param  array{processed: int, valid: int, updated: int, would_update: int, blockers: int}  $right
     * @return array{processed: int, valid: int, updated: int, would_update: int, blockers: int}
     */
    private function mergeStats(array $left, array $right): array
    {
        return [
            'processed' => $left['processed'] + $right['processed'],
            'valid' => $left['valid'] + $right['valid'],
            'updated' => $left['updated'] + $right['updated'],
            'would_update' => $left['would_update'] + $right['would_update'],
            'blockers' => $left['blockers'] + $right['blockers'],
        ];
    }
}
