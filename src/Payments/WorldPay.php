<?php
declare(strict_types=1);

namespace WebtronicIE\WorldPay\Payments;

use Exception;
use Igniter\Cart\Models\Order;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\PayRegister\Classes\BasePaymentGateway;
use Igniter\PayRegister\Concerns\WithPaymentRefund;
use Igniter\PayRegister\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Override;

class WorldPay extends BasePaymentGateway
{

    use WithPaymentRefund;

    public static ?string $paymentFormView = 'webtronicie.worldpay::_partials.worldpay.payment_form';
    public $worldpayTestEndpoint = "https://try.access.worldpay.com";
    public $worldpayLiveEndpoint = "https://access.worldpay.com";


    #[Override]
    public function defineFieldsConfig(): string
    {
        return 'webtronicie.worldpay::/models/worldpay';
    }

    #[Override]
    public function registerEntryPoints(): array
    {
        return [
            'worldpay_return_url' => 'processReturnUrl',
            'worldpay_notify_url' => 'processNotifyUrl',
        ];
    }

    public function isTestMode(): bool
    {
        return $this->model->transaction_mode != 'live';
    }

    public function getUsername()
    {
        return $this->isTestMode() ? $this->model->test_useranme : $this->model->live_username;
    }

    public function getPassword()
    {
        return $this->isTestMode() ? $this->model->test_password : $this->model->live_password;
    }

    public function getAccount()
    {
        return $this->isTestMode() ? $this->model->test_account : $this->model->live_account;
    }

    public function getEndPoint()
    {
        return $this->isTestMode() ? $this->worldpayTestEndpoint : $this->worldpayLiveEndpoint;
    }

    public function getToken(){

        return base64_encode($this->getUsername().$this->getAccount());

    }

    /**
     * Processes payment using passed data.
     *
     * @param array $data
     * @param Payment $host
     * @param Order $order
     *
     * @return bool|RedirectResponse
     * @throws ApplicationException
     */
    #[Override]
    public function processPaymentForm($data, $host, $order)
    {
        $this->validateApplicableFee($order, $host);

        $fields = $this->getPaymentFormFields($order, $data);


        try {
            $payment = $this->createPayment($fields);


            if ($payment['status'] === 'success') {

                session()->put('worldpay.check_url', $payment['response']['_links']['_self']['href']);

                return Redirect::to($payment['response']['url']);
            }

            $order->logPaymentAttempt('Payment error -> Failed to create payment redirect link', 0, $fields, [
                'status' => 'error',
                'method' => 'worldpay',
                'amount' => $fields['amount'],
            ]);
        } catch (Exception $ex) {
            $order->logPaymentAttempt('Payment error -> ' . $ex->getMessage(), 0, $fields);
        }

        throw new ApplicationException('Sorry, there was an error processing your payment. Please try again later.');
    }

    public function processReturnUrl($params)
    {
        $hash = $params[0] ?? null;
        $redirectPage = input('redirect') ?: 'checkout.checkout';
        $cancelPage = input('cancel') ?: 'checkout.checkout';

        $order = $this->createOrderModel()->whereHash($hash)->first();

        $payment = $this->checkResult(session()->get('worldpay.check_url'));


        try {
            throw_unless($order, new ApplicationException('No order found'));

            throw_if(
                !($paymentMethod = $order->payment_method) || !$paymentMethod->getGatewayObject() instanceof WorldPay,
                new ApplicationException('No valid payment method found'),
            );

            throw_if($order->isPaymentProcessed(), new ApplicationException('Payment has already been processed'));

            if ($payment['status'] == 'success' && $payment['response']->lastEvent == 'saleSucceeded' && $payment['response']->transactionReference == $order->order_id) {
                $order->logPaymentAttempt('Payment successful', 1, [], [
                    'id' => $payment['response']->paymentId,
                    'status' => 'success',
                    'method' => 'worldpay',
                    'amount' => $payment['response']->value->amount
                ], true);
                $order->updateOrderStatus($paymentMethod->order_status, ['notify' => false]);
                $order->markAsPaymentProcessed();
            }

            return Redirect::to(page_url($redirectPage, [
                'id' => $order->getKey(),
                'hash' => $order->hash,
            ]));
        } catch (Exception $ex) {
            $order?->logPaymentAttempt('Payment error -> '.$ex->getMessage(), 0, [], request()->input());
            flash()->warning($ex->getMessage())->important();
        }

        return Redirect::to(page_url($cancelPage));
    }




    protected function checkResult($result_url){

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $result_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "GET",
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            return ['status' => 'error', 'response' => $error];
        } else {
            return ['status' => 'success', 'response' => json_decode($response)];
        }
    }


    protected function createPayment($fields)
    {

        $curl = curl_init();

        $payload = array(
            "transactionReference" => $fields['metadata']['order_id'],
            "merchant" => array(
                "entity" => $this->getAccount()
            ),
            "narrative" => array(
                "line1" => "line1"
            ),
            "value" => array(
                "currency" => $fields['amount']['currency'],
                "amount" => $fields['amount']['value'],
            ),
            "description" => $fields['description'],

            "resultURLs" => array(
                "successURL" => $fields['redirectUrl'],
                "pendingURL" => $fields['redirectUrl'],
                "failureURL" => $fields['redirectUrl'],
                "errorURL" => $fields['redirectUrl'],
                "cancelURL" => $fields['redirectUrl'],
                "expiryURL" => $fields['redirectUrl']
            ),

        );

        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => [
                "Authorization: BasicAuth ".$this->getToken(),
                "Content-Type: application/vnd.worldpay.payment_pages-v1.hal+json",
                "User-Agent: string",
                "WP-CorrelationId: string"
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_URL => $this->getEndPoint(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {

            return ['status' => 'error', 'response' => $error];
        } else {
            return ['status' => 'success', 'response' => json_decode($response)];

        }
    }



    protected function getPaymentFormFields($order, $data = []): array
    {
        $returnUrl = $this->makeEntryPointUrl('worldpay_return_url') . '/' . $order->hash;
        $returnUrl .= '?redirect=' . array_get($data, 'successPage') . '&cancel=' . array_get($data, 'cancelPage');

        $fields = [
            'amount' => [
                'currency' => currency()->getUserCurrency(),
                'value' => number_format($order->order_total, 2, '.', ''),
            ],
            'description' => 'Payment for Order ' . $order->order_id,
            'metadata' => [
                'order_id' => $order->order_id,
            ],
            'redirectUrl' => $returnUrl,
        ];

        $this->fireSystemEvent('webtronicie.worldpay.extendFields', [&$fields, $order, $data]);

        return $fields;
    }

}
