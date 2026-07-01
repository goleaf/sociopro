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
        if (!$payment_details || !is_array($payment_details) || count($payment_details) <= 0) {
            flash()->addError('Payment not configured yet');
            return redirect()->back();
        }
        if ($payment_details['payable_amount'] <= 0) {
            flash()->addError("Payable amount cannot be less than 1");
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
        return view('payment.' . $identifier . '.index', $page_data);
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

            $model_full_path = 'App\Models\\' . str_replace(' ', '', $success_model);

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
        return view('payment.razorpay.payment', compact('data'));
    }

    public function payment_paytm(Request $request)
    {
        $user = Users::where('id', $request->user)->first();
        $payment = PaytmWallet::with('receive');
        $payment->prepare([
            'order' => $user->phone . "_" . rand(1, 1000),
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
            return redirect(route('initiate.payment'))->with('message', "Your payment is successfull.");

        } else if ($transaction->isFailed()) {
            Paytm::where('order_id', $order_id)->update(['status' => 0, 'transaction_id' => $transaction->getTransactionId()]);
            return redirect(route('initiate.payment'))->with('message', "Your payment is failed.");

        } else if ($transaction->isOpen()) {
            Paytm::where('order_id', $order_id)->update(['status' => 2, 'transaction_id' => $transaction->getTransactionId()]);
            return redirect(route('initiate.payment'))->with('message', "Your payment is processing.");
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
        return 'App\Models\payment_gateway\\' . str_replace(' ', '', $paymentGateway->model_name);
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
}
