<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BackfillLegacyJsonColumnsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_reports_cleanable_and_blocking_json_values_without_mutating_rows(): void
    {
        DB::table('payment_gateways')->where('identifier', 'paypal')->update(['keys' => '']);
        DB::table('payment_gateways')->where('identifier', 'stripe')->update(['keys' => '{invalid-json']);
        DB::table('payment_gateways')->where('identifier', 'razorpay')->update(['keys' => '{\"public_key\":\"demo\"}']);

        $historyId = DB::table('payment_histories')->insertGetId([
            'transaction_keys' => '',
        ]);

        $exitCode = Artisan::call('legacy:backfill-json-columns', [
            '--dry-run' => true,
            '--chunk' => 1,
        ]);

        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('DRY RUN', $output);
        $this->assertStringContainsString('payment_gateways.keys', $output);
        $this->assertStringContainsString('payment_histories.transaction_keys', $output);
        $this->assertStringContainsString('would_update=3', $output);
        $this->assertStringContainsString('blockers=1', $output);

        $this->assertSame('', DB::table('payment_gateways')->where('identifier', 'paypal')->value('keys'));
        $this->assertSame('{invalid-json', DB::table('payment_gateways')->where('identifier', 'stripe')->value('keys'));
        $this->assertSame('{\"public_key\":\"demo\"}', DB::table('payment_gateways')->where('identifier', 'razorpay')->value('keys'));
        $this->assertSame('', DB::table('payment_histories')->where('id', $historyId)->value('transaction_keys'));
    }

    public function test_command_backfills_blank_json_payloads_idempotently_and_reports_blockers(): void
    {
        DB::table('payment_gateways')->where('identifier', 'paypal')->update(['keys' => '']);
        DB::table('payment_gateways')->where('identifier', 'stripe')->update(['keys' => '{invalid-json']);
        DB::table('payment_gateways')->where('identifier', 'razorpay')->update(['keys' => '{\"public_key\":\"demo\"}']);

        $historyId = DB::table('payment_histories')->insertGetId([
            'transaction_keys' => '',
        ]);

        $firstExitCode = Artisan::call('legacy:backfill-json-columns', [
            '--chunk' => 1,
        ]);

        $firstOutput = Artisan::output();

        $this->assertSame(1, $firstExitCode);
        $this->assertStringContainsString('updated=3', $firstOutput);
        $this->assertStringContainsString('blockers=1', $firstOutput);
        $this->assertSame('{}', DB::table('payment_gateways')->where('identifier', 'paypal')->value('keys'));
        $this->assertSame('{invalid-json', DB::table('payment_gateways')->where('identifier', 'stripe')->value('keys'));
        $this->assertSame('{"public_key":"demo"}', DB::table('payment_gateways')->where('identifier', 'razorpay')->value('keys'));
        $this->assertSame('{}', DB::table('payment_histories')->where('id', $historyId)->value('transaction_keys'));

        $secondExitCode = Artisan::call('legacy:backfill-json-columns', [
            '--chunk' => 1,
        ]);

        $secondOutput = Artisan::output();

        $this->assertSame(1, $secondExitCode);
        $this->assertStringContainsString('updated=0', $secondOutput);
        $this->assertStringContainsString('blockers=1', $secondOutput);
        $this->assertSame('{}', DB::table('payment_gateways')->where('identifier', 'paypal')->value('keys'));
        $this->assertSame('{}', DB::table('payment_histories')->where('id', $historyId)->value('transaction_keys'));
    }

    public function test_command_succeeds_when_all_cleanable_json_values_are_backfilled(): void
    {
        DB::table('payment_gateways')->where('identifier', 'paypal')->update(['keys' => '']);

        $historyId = DB::table('payment_histories')->insertGetId([
            'transaction_keys' => '',
        ]);

        $firstExitCode = Artisan::call('legacy:backfill-json-columns', [
            '--chunk' => 2,
        ]);

        $this->assertSame(0, $firstExitCode);
        $this->assertSame('{}', DB::table('payment_gateways')->where('identifier', 'paypal')->value('keys'));
        $this->assertSame('{}', DB::table('payment_histories')->where('id', $historyId)->value('transaction_keys'));

        $secondExitCode = Artisan::call('legacy:backfill-json-columns', [
            '--chunk' => 2,
        ]);

        $secondOutput = Artisan::output();

        $this->assertSame(0, $secondExitCode);
        $this->assertStringContainsString('updated=0', $secondOutput);
        $this->assertStringContainsString('blockers=0', $secondOutput);
    }

    public function test_backfill_audit_documents_candidates_and_command_usage(): void
    {
        $path = base_path('docs/backfill-audit.md');

        $this->assertFileExists($path);

        $contents = file_get_contents($path) ?: '';

        foreach ([
            'legacy:backfill-json-columns',
            '--dry-run',
            '--chunk',
            'payment_gateways.keys',
            'payment_histories.transaction_keys',
            'languages.name + languages.phrase',
            'settings.type',
            'marketplaces.price',
            'personal_access_tokens.expires_at',
            'idempotent',
        ] as $requiredText) {
            $this->assertStringContainsString($requiredText, $contents);
        }
    }
}
