<?php

namespace App\Http\Controllers\Payment_Methods;

use App\Models\Order;
use App\Models\OrderEditHistory;
use App\Models\PaymentRequest;
use App\Models\User;
use App\Services\PaystackBankService;
use App\Traits\Processor;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaystackController extends Controller
{
    use Processor;

    private PaymentRequest $payment;
    private $user;

    public function __construct(PaymentRequest $payment, User $user)
    {
        $config = $this->payment_config('paystack', 'payment_config');
        $values = false;
        if (!is_null($config) && $config->mode == 'live') {
            $values = json_decode($config->live_values);
        } elseif (!is_null($config) && $config->mode == 'test') {
            $values = json_decode($config->test_values);
        }

        if ($values) {
            $config = array(
                'publicKey' => env('PAYSTACK_PUBLIC_KEY', $values->public_key),
                'secretKey' => env('PAYSTACK_SECRET_KEY', $values->secret_key),
                'paymentUrl' => env('PAYSTACK_PAYMENT_URL', 'https://api.paystack.co'),
                'merchantEmail' => env('MERCHANT_EMAIL', $values->merchant_email),
            );
            Config::set('paystack', $config);
        }

        $this->payment = $payment;
        $this->user = $user;
    }

    public function index(Request $request): JsonResponse|Redirector|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|uuid'
        ]);

        if ($validator->fails()) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_400, null, $this->error_processor($validator)), 400);
        }

        $data = $this->payment::where(['id' => $request['payment_id']])->where(['is_paid' => 0])->first();
        if (!isset($data)) {
            return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
        }

        $payer = json_decode($data['payer_information'], true);

        $url = "https://api.paystack.co/transaction/initialize";

        $fields = [
            'email' => $payer['email'] ?? "customer@email.com",
            'amount' => ($data['payment_amount'] ?? 0) * 100,
            'currency' => $data['currency_code'] ?? 'XOF',
            'reference' => (string)('REF' . time() . 'RANDOM'),
            'callback_url' => route('paystack.callback', ['payment_id' => $data['id']]),
            'metadata' => [
                'payment_id' => $data['id'],
            ]
        ];

        $fields_string = http_build_query($fields);
        $ch = curl_init();

        //set the url, number of POST vars, POST data
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Authorization: Bearer " . Config::get('paystack.secretKey'),
            "Cache-Control: no-cache",
        ));

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), true);

        if ($response['status'] && isset($response['data']['authorization_url'])) {
            return redirect($response['data']['authorization_url']);
        }

        return response()->json($this->response_formatter(GATEWAYS_DEFAULT_204), 200);
    }

    public function handleGatewayCallback(Request $request): Redirector|RedirectResponse
    {
        $paymentDetails = self::getPayStackPaymentData(request: $request);
        $payment_data = $this->payment::where(['id' => $paymentDetails['data']['metadata']['payment_id']])->first();

        if ($paymentDetails['status'] == true && $paymentDetails['data']['status'] == 'success') {
            $expected_amount = round(($payment_data['payment_amount'] ?? 0) * 100);
            $paid_amount = $paymentDetails['data']['amount'];

            if ($paid_amount >= $expected_amount) {
                $this->payment::where(['id' => $paymentDetails['data']['metadata']['payment_id']])->update([
                    'payment_method' => 'paystack',
                    'is_paid' => 1,
                    'transaction_id' => $request['trxref'],
                ]);
                $data = $this->payment::where(['id' => $paymentDetails['data']['metadata']['payment_id']])->first();
                if (isset($data) && function_exists($data->success_hook)) {
                    call_user_func($data->success_hook, $data);
                }
                return $this->payment_response($data, 'success');
            }
        }


        $payment_data = $this->payment::where(['id' => $paymentDetails['data']['metadata']['payment_id']])->first();
        if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
            call_user_func($payment_data->failure_hook, $payment_data);
        }
        return $this->payment_response($payment_data, 'fail');
    }

    public function cancel(Request $request): Application|JsonResponse|Redirector|RedirectResponse
    {
        $payment_data = $this->payment::where(['id' => $request['payments_id']])->first();
        if (isset($payment_data) && function_exists($payment_data->failure_hook)) {
            call_user_func($payment_data->failure_hook, $payment_data);
        }
        return $this->payment_response($payment_data, 'fail');
    }

    protected function getPayStackPaymentData(object|array $request): array
    {
        $reference = $request->query('reference');
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/transaction/verify/$reference",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer " . Config::get('paystack.secretKey'),
                "Cache-Control: no-cache",
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
        return json_decode($response, true);
    }

    /**
     * Handle Paystack Asynchronous Webhooks with Strict HMAC-SHA512 Cryptographic Signature Verification
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function webhook(Request $request): JsonResponse
    {
        $secretKey = Config::get('paystack.secretKey');
        if (empty($secretKey)) {
            $paystackBankService = app(PaystackBankService::class);
            $secretKey = $paystackBankService->getSecretKey();
        }

        $paystackSignature = $request->header('x-paystack-signature');
        $payload = $request->getContent();

        // 1. Strict Cryptographic Verification
        if (empty($paystackSignature) || empty($secretKey) || !hash_equals(hash_hmac('sha512', $payload, $secretKey), $paystackSignature)) {
            Log::warning('Paystack Webhook: Signature verification failed or invalid signature header.', [
                'has_signature' => !empty($paystackSignature),
                'ip' => $request->ip(),
            ]);
            return response()->json(['status' => false, 'message' => 'Invalid signature'], 401);
        }

        $event = json_decode($payload, true);
        if (!$event || !isset($event['event'])) {
            return response()->json(['status' => false, 'message' => 'Invalid event payload'], 400);
        }

        Log::info('Paystack Webhook received event: ' . $event['event']);

        // 2. Process Successful Charge
        if ($event['event'] === 'charge.success') {
            $data = $event['data'] ?? [];
            $reference = $data['reference'] ?? null;
            $amountPaid = $data['amount'] ?? 0; // in kobo
            $metadata = $data['metadata'] ?? [];

            // A. Check standard e-commerce PaymentRequest
            $paymentId = $metadata['payment_id'] ?? null;
            if ($paymentId) {
                $paymentRequest = $this->payment::where('id', $paymentId)->first();
                if ($paymentRequest && $paymentRequest->is_paid == 0) {
                    $expectedAmount = round(($paymentRequest->payment_amount ?? 0) * 100);
                    if ($amountPaid >= $expectedAmount) {
                        $paymentRequest->update([
                            'payment_method' => 'paystack',
                            'is_paid' => 1,
                            'transaction_id' => $reference,
                        ]);

                        $updatedPayment = $this->payment::where('id', $paymentId)->first();
                        if (!empty($updatedPayment->success_hook) && function_exists($updatedPayment->success_hook)) {
                            call_user_func($updatedPayment->success_hook, $updatedPayment);
                        }
                        Log::info("Paystack Webhook: Successfully processed PaymentRequest #{$paymentId} with ref {$reference}.");
                    }
                }
            }

            // B. Check Delivery Rider Cash-on-Delivery Order Payment Link
            $orderId = $metadata['order_id'] ?? null;
            $type = $metadata['type'] ?? null;
            if ($orderId && $type === 'delivery_payment') {
                $order = Order::with(['customer', 'deliveryMan', 'latestEditHistory'])->find($orderId);
                if ($order && $order->order_status != 'delivered') {
                    $expectedAmount = round(($order['order_amount'] + $order['edit_due_amount']) * 100);
                    if ($amountPaid >= $expectedAmount) {
                        $order->update([
                            'order_status' => 'delivered',
                            'order_amount' => $order['order_amount'] + $order['edit_due_amount'],
                            'payment_status' => 'paid',
                            'edit_due_amount' => 0,
                            'payment_method' => 'paystack',
                            'transaction_ref' => $reference,
                        ]);

                        if ($order->latestEditHistory) {
                            OrderEditHistory::where('id', $order->latestEditHistory->id)->update([
                                'order_due_payment_status' => 'paid',
                                'order_due_payment_note' => 'Marked as paid by Paystack Webhook',
                            ]);
                        }

                        Log::info("Paystack Webhook: Successfully processed Delivery Order #{$orderId} with ref {$reference}.");
                    }
                }
            }
        }

        // Paystack requires a 200 OK HTTP response
        return response()->json(['status' => true, 'message' => 'Webhook received and processed'], 200);
    }
}
