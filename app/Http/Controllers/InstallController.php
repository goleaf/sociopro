<?php

namespace App\Http\Controllers;

use App\Actions\Install\CheckInstallRequirements;
use App\Actions\Install\FinalizeInstallation;
use App\Actions\Install\ImportInstallSqlDump;
use App\Actions\Install\PrepareDatabaseConnection;
use App\Actions\Install\UpdateEnvironmentFile;
use App\Http\Requests\Install\FinalizeInstallationRequest;
use App\Http\Requests\Install\ValidatePurchaseCodeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class InstallController extends Controller
{

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public  function index()
    {
        if (DB::connection()->getDatabaseName() != 'db_name') {
           return redirect('/login');
        } else {
            return redirect()->route('install.step0');
        }
    }

    public function step0() {
        return view('install.step0');
    }

    public function step1(Request $request, CheckInstallRequirements $checkInstallRequirements) {
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

    function step2($param1 = '') {
        if ($param1 == 'error') {
            $error = 'Purchase Code Verification Failed';
        } else {
            $error = "";
        }
        return view('install.step2', ['error' => $error]);
    }

    public function validatePurchaseCode(ValidatePurchaseCodeRequest $request)
    {
        $purchase_code = $request->validated('purchase_code');
		session(['purchase_code' => $purchase_code]);
		session(['purchase_code_verified' => 1]);
		//move to step 3
		return redirect()->route('install.step3');
    }

    public function api_request($code = '')
    {
        $product_code = $code;
        $personal_token = config('services.envato.personal_token');

        if (! $personal_token) {
            return false;
        }

        //setting the header for the rest of the api
        $bearer   = 'bearer ' . $personal_token;
        $header   = array();
        $header[] = 'Content-length: 0';
        $header[] = 'Content-type: application/json; charset=utf-8';
        $header[] = 'Authorization: ' . $bearer;

        $verify_url = 'https://api.envato.com/v1/market/private/user/verify-purchase:' . $product_code . '.json';
        $ch_verify = curl_init($verify_url . '?code=' . $product_code);

        curl_setopt($ch_verify, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch_verify, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch_verify, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch_verify, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch_verify, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] ?? 'Sociopro Installer');

        $cinit_verify_data = curl_exec($ch_verify);
        curl_close($ch_verify);

        $response = json_decode($cinit_verify_data, true) ?: [];

        return count($response['verify-purchase'] ?? []) > 0;
    }

    public function step3(Request $request, PrepareDatabaseConnection $prepareDatabaseConnection) {
        $db_connection = "";
        $data = $request->all();

        $purchaseCodeRedirect = $this->check_purchase_code_verification($request);

        if ($purchaseCodeRedirect) {
            return $purchaseCodeRedirect;
        }

        if ($request->isMethod('post')) {
            $result = $prepareDatabaseConnection->handle($data);

            if ($result['status'] === 'success') {
                foreach ($result['session'] as $key => $value) {
                    session([$key => $value]);
                }

                return redirect()->route('install.step4');
            }

            $db_connection = $result['message'];
        }

        return view('install.step3', [
            'db_connection' => $db_connection,
            'selectedConnection' => $this->isLocalInstall($request) ? 'sqlite' : 'mysql',
            'sqlitePath' => database_path('database.sqlite'),
        ]);
    }

    public function check_purchase_code_verification(Request $request) {
        if ($this->isLocalInstall($request)) {
            return null;
        }

        if (! session('purchase_code_verified')) {
            return redirect()->route('install.step2');
        }

        return null;
    }

    public function step4(Request $request) {

        return view('install.step4');
    }


    public function confirmImport($param1='')
    {
        if (in_array($param1, ['confirm_import', 'confirm_install'], true)) {
            $this->configure_database();

            // redirect to admin creation page
            return view('install.install');
        }
    }

    public function confirmInstall(ImportInstallSqlDump $importInstallSqlDump)
    {
        // run sql
        $this->run_blank_sql($importInstallSqlDump);

        // redirect to admin creation page
        return redirect()->route('install.finalizing');
    }

    public function configure_database() {
        $connection = session('db_connection', 'mysql');
        $environment = app(UpdateEnvironmentFile::class);

        if ($connection === 'sqlite') {
            $database = session('dbname', database_path('database.sqlite'));

            $environment->handle([
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => $database,
                'DB_HOST' => '',
                'DB_PORT' => '',
                'DB_USERNAME' => '',
                'DB_PASSWORD' => '',
            ]);

            config([
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => $database,
            ]);

            DB::setDefaultConnection('sqlite');
            DB::purge('sqlite');

            return;
        }

        $environment->handle([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => session('hostname'),
            'DB_PORT' => config('database.connections.mysql.port', '3306'),
            'DB_DATABASE' => session('dbname'),
            'DB_USERNAME' => session('username'),
            'DB_PASSWORD' => session('password'),
        ]);

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => session('hostname'),
            'database.connections.mysql.database' => session('dbname'),
            'database.connections.mysql.username' => session('username'),
            'database.connections.mysql.password' => session('password'),
        ]);

        DB::setDefaultConnection('mysql');
        DB::purge('mysql');
    }

    public function run_blank_sql(ImportInstallSqlDump $importInstallSqlDump) {
        $this->configure_database();

        $importInstallSqlDump->handle(base_path('public/assets/install.sql'));
    }

    public function finalizingSetup(
        FinalizeInstallationRequest $request,
        FinalizeInstallation $finalizeInstallation
    ) {

        if ($request->isMethod('post')) {
            $finalizeInstallation->handle($request->validated(), session('purchase_code'));

            return redirect()->route('install.success');
        }

        return view('install.finalizing_setup');
    }

    public function success($param1 = '') {
        $this->configure_routes();

        if ($param1 == 'login') {
            return view('auth.login');
        }

        $admin_email = User::find('1')->email;

        return view('install.success', ['admin_email' => $admin_email]);
    }

    public function configure_routes() {
        return true;
    }

    private function isLocalInstall(Request $request): bool
    {
        return app()->environment(['local', 'testing'])
            || in_array($request->getHost(), ['localhost', '127.0.0.1', '::1'], true);
    }
}
