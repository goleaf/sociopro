<?php

namespace App\Http\Controllers;

use Anand\LaravelPaytmWallet\Facades\PaytmWallet;
use App\Models\Payment_gateway;
use App\Models\Setting;
use App\Models\Users;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    private const PAYMENT_GATEWAY_COLUMNS = [
        'id',
        'identifier',
        'currency',
        'title',
        'description',
        'keys',
        'model_name',
        'test_mode',
        'status',
        'is_addon',
    ];

    public function index()
    {
        $payment_details = session('payment_details');
        if (! $payment_details || ! is_array($payment_details) || count($payment_details) <= 0) {
            flash()->addError('Payment not configured yet');

            return redirect()->back();
        }
        if ($payment_details['payable_amount'] <= 0) {
            flash()->addError('Payable amount cannot be less than 1');

            return redirect()->to($payment_details['cancel_url']);
        }

        $page_data['payment_details'] = $payment_details;
        $page_data['payment_gateways'] = Payment_gateway::query()
            ->select(self::PAYMENT_GATEWAY_COLUMNS)
            ->get();
        $page_data += $this->paymentPageSettings();

        return view('payment.index', $page_data);
    }

    public function show_payment_gateway_by_ajax($identifier)
    {
        $page_data['payment_details'] = session('payment_details');
        $page_data['payment_gateway'] = $this->paymentGateway($identifier);
        $page_data += $this->paymentGatewayViewData($identifier, $page_data['payment_gateway'], $page_data['payment_details']);

        return view('payment.'.$identifier.'.index', $page_data);
    }

    public function payment_success($identifier, Request $request)
    {
        $payment_details = session('payment_details');
        $payment_gateway = $this->paymentGateway($identifier);
        $model_full_path = $this->gatewayModelClass($payment_gateway);

        $paymentGateway = new $model_full_path();
        $status = $paymentGateway->payment_status($identifier, $request->all());

        if ($status === true) {
            $success_model = $payment_details['success_method']['model_name'];

            $success_function = $payment_details['success_method']['function_name'];

            $model_full_path = 'App\Models\\'.str_replace(' ', '', $success_model);

            return $model_full_path::$success_function($identifier);
        } else {
            flash()->addError(get_phrase('Payment failed! Please try again.'));

            return redirect()->to($payment_details['cancel_url']);
        }
    }

    public function payment_create($identifier)
    {
        $payment_gateway = $this->paymentGateway($identifier);
        $model_full_path = $this->gatewayModelClass($payment_gateway);
        $created_payment_link = $model_full_path::payment_create($identifier);

        return redirect()->to($created_payment_link);
    }

    public function payment_razorpay($identifier)
    {
        $payment_gateway = $this->paymentGateway($identifier);
        $model_full_path = $this->gatewayModelClass($payment_gateway);
        $data = $model_full_path::payment_create($identifier);

        return view('payment.razorpay.payment', [
            'page_data' => $data['page_data'],
            'payment_details' => $data['payment_details'],
            'color' => $data['color'],
        ]);
    }

    public function payment_paytm(Request $request)
    {
        $user = Users::where('id', $request->user)->first();
        $payment = PaytmWallet::with('receive');
        $payment->prepare([
            'order' => $user->phone.'_'.rand(1, 1000),
            'user' => auth()->user()->id,
            'mobile_number' => $user->phone,
            'email' => $user->email,
            'amount' => $request->amount,
            'callback_url' => route('payment.status', ['identifier' => 'paytm']),
        ]);

        return $payment->receive();
    }

    public function paytm_paymentCallback()
    {
        $transaction = PaytmWallet::with('receive');
        $response = $transaction->response();
        $order_id = $transaction->getOrderId(); // return a order id
        $transaction->getTransactionId(); // return a transaction id

        // update the db data as per result from api call
        if ($transaction->isSuccessful()) {
            Paytm::where('order_id', $order_id)->update(['status' => 1, 'transaction_id' => $transaction->getTransactionId()]);

            return redirect()->route('initiate.payment')->with('message', 'Your payment is successfull.');
        } elseif ($transaction->isFailed()) {
            Paytm::where('order_id', $order_id)->update(['status' => 0, 'transaction_id' => $transaction->getTransactionId()]);

            return redirect()->route('initiate.payment')->with('message', 'Your payment is failed.');
        } elseif ($transaction->isOpen()) {
            Paytm::where('order_id', $order_id)->update(['status' => 2, 'transaction_id' => $transaction->getTransactionId()]);

            return redirect()->route('initiate.payment')->with('message', 'Your payment is processing.');
        }
        $transaction->getResponseMessage(); //Get Response Message If Available

        // $transaction->getOrderId(); // Get order id
    }

    private function paymentGateway(string $identifier): Payment_gateway
    {
        return Payment_gateway::query()
            ->select(self::PAYMENT_GATEWAY_COLUMNS)
            ->forIdentifier($identifier)
            ->firstOrFail();
    }

    private function gatewayModelClass(Payment_gateway $paymentGateway): string
    {
        return 'App\Models\payment_gateway\\'.str_replace(' ', '', $paymentGateway->model_name);
    }

    private function paymentPageSettings(): array
    {
        $settings = Setting::query()
            ->whereIn('type', ['system_name', 'system_fav_icon'])
            ->pluck('description', 'type');

        return [
            'system_name' => $settings->get('system_name', ''),
            'system_favicon' => $settings->get('system_fav_icon', ''),
        ];
    }

    private function paymentGatewayViewData(string $identifier, Payment_gateway $paymentGateway, array $paymentDetails): array
    {
        return match ($identifier) {
            'stripe' => $this->stripeViewData($paymentGateway, $paymentDetails),
            'razorpay' => $this->razorpayViewData($paymentGateway, $paymentDetails),
            'flutterwave' => $this->flutterwaveViewData($paymentGateway, $paymentDetails),
            'paypal' => $this->paypalViewData($paymentGateway),
            'paystack' => $this->paystackViewData($paymentGateway, $paymentDetails),
            default => [],
        };
    }

    private function stripeViewData(Payment_gateway $paymentGateway, array $paymentDetails): array
    {
        $model = $paymentDetails['success_method']['model_name'];
        $key = '';
        $msg = '';

        if (in_array($model, ['AuthorPayout', 'CampaignPayout'], true)) {
            $keys = $this->userPaymentSettings($paymentDetails);
            $key = ! empty($keys['stripe_live'])
                ? ($keys['stripe_secret_live_key'] ?? '')
                : ($keys['stripe_secret_key'] ?? '');
        } elseif (in_array($model, ['Sponsor', 'Subscription', 'Donation', 'Job'], true)) {
            $paymentKeys = json_decode($paymentGateway->keys, true) ?: [];

            if ($paymentGateway->status == 1) {
                $key = $paymentGateway->test_mode == 1
                    ? ($paymentKeys['secret_key'] ?? '')
                    : ($paymentKeys['secret_live_key'] ?? '');
            } else {
                $msg = 'Admin denied transaction through this gateway.';
            }
        }

        return [
            'key' => $key,
            'msg' => $msg ?: ($key === '' ? "This payment gateway isn't configured." : ''),
        ];
    }

    private function razorpayViewData(Payment_gateway $paymentGateway, array $paymentDetails): array
    {
        $model = $paymentDetails['success_method']['model_name'];
        $publicKey = '';
        $secretKey = '';
        $msg = '';

        if (in_array($model, ['AuthorPayout', 'CampaignPayout'], true)) {
            $keys = $this->userPaymentSettings($paymentDetails);
            $publicKey = $keys['raz_key_id'] ?? '';
            $secretKey = $keys['raz_secret_key'] ?? '';
        } elseif (in_array($model, ['Sponsor', 'Subscription', 'Donation', 'Job', 'Badge'], true)) {
            $keys = json_decode($paymentGateway->keys, true) ?: [];

            if ($paymentGateway->status == 1) {
                $publicKey = $keys['public_key'] ?? '';
                $secretKey = $keys['secret_key'] ?? '';
            } else {
                $msg = 'Admin denied transaction through this gateway.';
            }
        }

        return [
            'public_key' => $publicKey,
            'secret_key' => $secretKey,
            'msg' => $msg ?: ($publicKey === '' || $secretKey === '' ? "This payment gateway isn't configured." : ''),
        ];
    }

    private function flutterwaveViewData(Payment_gateway $paymentGateway, array $paymentDetails): array
    {
        $model = $paymentDetails['success_method']['model_name'];
        $key = '';
        $keyType = '';
        $msg = '';
        $title = '';
        $description = '';

        if (in_array($model, ['AuthorPayout', 'CampaignPayout'], true)) {
            $keys = $this->userPaymentSettings($paymentDetails);

            if (! empty($keys['flutterwave_live'])) {
                $keyType = 'secret_key';
                $key = $keys['flutterwave_secret_key'] ?? '';
            } else {
                $keyType = 'public_key';
                $key = $keys['flutterwave_public_key'] ?? '';
            }

            $title = $model === 'AuthorPayout' ? 'Authors payout.' : 'Campaign Payout.';
            $description = $title;
        } elseif (in_array($model, ['Sponsor', 'Subscription', 'Donation', 'Job'], true)) {
            $paymentKeys = json_decode($paymentGateway->keys, true) ?: [];

            if ($paymentGateway->status == 1) {
                if ($paymentGateway->test_mode == 1) {
                    $keyType = 'public_key';
                    $key = $paymentKeys['public_key'] ?? '';
                } else {
                    $keyType = 'secret_key';
                    $key = $paymentKeys['secret_key'] ?? '';
                }
            } else {
                $msg = 'Admin denied transaction through this gateway.';
            }

            [$title, $description] = match ($model) {
                'Sponsor' => ['Ads payment.', 'Payment for ads publish.'],
                'Subscription' => ['Subscription.', 'Payment for subscription.'],
                'Donation' => ['Donation.', 'Payment for donation.'],
                'Job' => ['Job.', 'Payment for Job.'],
                default => ['', ''],
            };
        }

        return [
            'key' => $key,
            'key_type' => $keyType,
            'msg' => $msg ?: ($key === '' ? "This payment gateway isn't configured." : ''),
            'title' => $title,
            'description' => $description,
            'user' => Users::query()
                ->select(['id', 'name', 'email', 'phone'])
                ->where('id', auth()->id())
                ->first(),
        ];
    }

    private function paypalViewData(Payment_gateway $paymentGateway): array
    {
        $paymentKeys = json_decode($paymentGateway->keys, true) ?: [];

        return [
            'client_id' => $paymentGateway->test_mode == 1
                ? ($paymentKeys['sandbox_client_id'] ?? '')
                : ($paymentKeys['production_client_id'] ?? ''),
            'paypalURL' => $paymentGateway->test_mode == 1
                ? 'https://api.sandbox.paypal.com/v1/'
                : 'https://api.paypal.com/v1/',
            'systemCurrency' => Setting::query()
                ->where('type', 'system_currency')
                ->value('description'),
        ];
    }

    private function paystackViewData(Payment_gateway $paymentGateway, array $paymentDetails): array
    {
        $keys = json_decode($paymentGateway->keys, true) ?: [];

        return [
            'key' => $paymentGateway->test_mode == 1
                ? ($keys['public_test_key'] ?? '')
                : ($keys['public_live_key'] ?? ''),
            'amount' => $paymentDetails['items'][0]['price'],
            'user_details' => auth()->user(),
        ];
    }

    private function userPaymentSettings(array $paymentDetails): array
    {
        $paymentSettings = Users::query()
            ->select(['id', 'payment_settings'])
            ->where('id', $paymentDetails['custom_field']['user_id'] ?? null)
            ->value('payment_settings');

        return json_decode($paymentSettings, true) ?: [];
    }
}
