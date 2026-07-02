<?php

namespace Tests;

use App\Actions\Install\ImportInstallSqlDump;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;
use RuntimeException;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    private static bool $duskDatabasePrepared = false;

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDuskDatabase();
    }

    private function prepareDuskDatabase(): void
    {
        if (self::$duskDatabasePrepared || config('database.default') !== 'sqlite') {
            return;
        }

        $database = (string) config('database.connections.sqlite.database');

        if ($database === ':memory:') {
            throw new RuntimeException('Dusk requires a file-backed SQLite database shared with the browser app.');
        }

        File::ensureDirectoryExists(dirname($database));

        if (! File::exists($database)) {
            touch($database);
        }

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        if (! Schema::hasTable('settings')) {
            DB::disconnect('sqlite');
            File::delete($database);
            touch($database);
            DB::purge('sqlite');
            DB::reconnect('sqlite');

            app(ImportInstallSqlDump::class)->handle(
                (string) config('install.schema_dump_path'),
                batchSize: 100
            );
        }

        Artisan::call('migrate', ['--force' => true]);

        self::$duskDatabasePrepared = true;
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
            '--allow-insecure-localhost',
            '--ignore-certificate-errors',
            '--no-sandbox',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }
}
