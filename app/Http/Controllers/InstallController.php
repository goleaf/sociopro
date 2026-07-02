<?php

namespace App\Http\Controllers;

use App\Actions\Install\CheckInstallRequirements;
use App\Actions\Install\ConfigureDatabase;
use App\Actions\Install\FinalizeInstallation;
use App\Actions\Install\ImportInstallSqlDump;
use App\Actions\Install\PrepareDatabaseConnection;
use App\Http\Requests\Install\FinalizeInstallationRequest;
use App\Http\Requests\Install\PrepareDatabaseConnectionRequest;
use App\Http\Requests\Install\ValidatePurchaseCodeRequest;
use App\Models\User;
use DateTimeZone;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstallController extends Controller
{
    private const DEFAULT_INSTALL_TIMEZONE = 'Asia/Dhaka';

    public function __construct(
        private ConfigureDatabase $configureDatabase
    ) {}

    /**
     * Show the application dashboard.
     *
     * @return Renderable
     */
    public function index()
    {
        if (DB::connection()->getDatabaseName() != 'db_name') {
            return redirect()->route('login');
        } else {
            return redirect()->route('install.step0');
        }
    }

    public function step0()
    {
        return view('install.step0');
    }

    public function step1(Request $request, CheckInstallRequirements $checkInstallRequirements)
    {
        $isLocalInstall = $this->isLocalInstall($request);
        $requirements = $checkInstallRequirements->handle($isLocalInstall);
        $valid = collect($requirements)->every(fn ($requirement) => $requirement['passed']);
        $nextUrl = $isLocalInstall ? route('install.step3') : route('install.step2');

        return view('install.step1', [
            'isLocalInstall' => $isLocalInstall,
            'nextUrl' => $nextUrl,
            'requirements' => $requirements,
            'valid' => $valid,
        ]);
    }

    public function step2($param1 = '')
    {
        if ($param1 == 'error') {
            $error = 'Purchase Code Verification Failed';
        } else {
            $error = '';
        }

        return view('install.step2', ['error' => $error]);
    }

    public function validatePurchaseCode(ValidatePurchaseCodeRequest $request)
    {
        $purchase_code = $request->validated('purchase_code');
        session(['purchase_code' => $purchase_code]);
        session(['purchase_code_verified' => 1]);

        // move to step 3
        return redirect()->route('install.step3');
    }

    public function step3(
        PrepareDatabaseConnectionRequest $request,
        PrepareDatabaseConnection $prepareDatabaseConnection
    ) {
        $databaseError = '';

        $purchaseCodeRedirect = $this->redirectIfPurchaseCodeUnverified($request);

        if ($purchaseCodeRedirect) {
            return $purchaseCodeRedirect;
        }

        if ($request->isMethod('post')) {
            $result = $prepareDatabaseConnection->handle($request->validated());

            if ($result['status'] === 'success') {
                foreach ($result['session'] as $key => $value) {
                    session([$key => $value]);
                }

                return redirect()->route('install.step4');
            }

            $databaseError = $result['message'];
        }

        return view('install.step3', [
            'db_connection' => $databaseError,
            'selectedConnection' => $this->isLocalInstall($request) ? 'sqlite' : 'mysql',
            'sqlitePath' => database_path('database.sqlite'),
        ]);
    }

    private function redirectIfPurchaseCodeUnverified(Request $request): ?RedirectResponse
    {
        if ($this->isLocalInstall($request)) {
            return null;
        }

        if (! session('purchase_code_verified')) {
            return redirect()->route('install.step2');
        }

        return null;
    }

    public function step4()
    {
        return view('install.step4');
    }

    public function confirmImport($param1 = '')
    {
        if (! in_array($param1, ['confirm_import', 'confirm_install'], true)) {
            return redirect()->route('install.step4');
        }

        $this->configureDatabase();

        return view('install.install');
    }

    public function confirmInstall(ImportInstallSqlDump $importInstallSqlDump)
    {
        // run sql
        $this->importInstallSql($importInstallSqlDump);

        // redirect to admin creation page
        return redirect()->route('install.finalizing');
    }

    private function importInstallSql(ImportInstallSqlDump $importInstallSqlDump): void
    {
        $this->configureDatabase();

        $importInstallSqlDump->handle((string) config('install.schema_dump_path'));
    }

    private function configureDatabase(): void
    {
        $this->configureDatabase->handle();
    }

    public function finalizingSetup(
        FinalizeInstallationRequest $request,
        FinalizeInstallation $finalizeInstallation
    ) {
        if ($request->isMethod('post')) {
            $finalizeInstallation->handle($request->validated(), session('purchase_code'));

            return redirect()->route('install.success');
        }

        return view('install.finalizing_setup', [
            'defaultTimezone' => self::DEFAULT_INSTALL_TIMEZONE,
            'timezones' => DateTimeZone::listIdentifiers(DateTimeZone::ALL),
        ]);
    }

    public function success($param1 = '')
    {
        $this->configureRoutes();

        if ($param1 === 'login') {
            return view('auth.login');
        }

        $admin = User::query()
            ->select(['id', 'email'])
            ->admins()
            ->oldest('id')
            ->first();

        if (! $admin) {
            return redirect()->route('install.finalizing');
        }

        return view('install.success', ['admin_email' => $admin->email]);
    }

    private function configureRoutes(): void {}

    private function isLocalInstall(Request $request): bool
    {
        return app()->environment(['local', 'testing'])
            || in_array($request->getHost(), ['localhost', '127.0.0.1', '::1'], true);
    }
}
