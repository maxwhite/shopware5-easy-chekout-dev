<?php

namespace NetsCheckoutPayment\Components;

use NetsCheckoutPayment\Models\NetsCheckoutPayment;
use NetsCheckoutPayment\Models\NetsCheckoutPaymentApiOperations;
use Shopware\Models\Order\Order;
use Shopware\Models\Customer\Customer;

class NetsCheckoutService
{
    /** @var Api\NetsCheckoutService  */
    private $apiService;

    /**
     * regexp for filtering strings
     */
    const ALLOWED_CHARACTERS_PATTERN = '/[^\x{00A1}-\x{00AC}\x{00AE}-\x{00FF}\x{0100}-\x{017F}\x{0180}-\x{024F}'
    . '\x{0250}-\x{02AF}\x{02B0}-\x{02FF}\x{0300}-\x{036F}'
    . 'A-Za-z0-9\!\#\$\%\(\)*\+\,\-\.\/\:\;\\=\?\@\[\]\\^\_\`\{\}\~ ]+/u';


    public function __construct(Api\NetsCheckoutService $checkoutService)
    {
        $this->apiService = $checkoutService;
    }

    public function createPayment($userId, $basket, $temporaryOrderId) {
        $result = $this->collectRequestParams($userId, $basket, $temporaryOrderId);
        $this->apiService->setAuthorizationKey($this->getAuthorizationKey());
        return $this->apiService->createPayment( json_encode($result) );
    }

    private function collectRequestParams($userId, array $basket, $temporaryOrderId) : array {

        /** @var  $customer \Shopware\Models\Customer\Customer */
        $customer = Shopware()->Models()->find(Customer::class, $userId);

        $returUrl = Shopware()->Front()->Router()->assemble([
            'controller' => 'NetsCheckout',
            'action' => 'return',
            'forceSecure' => true
        ]);

        $data =  [
            'order' => [
                'items' => $this->getOrderItems($basket),
                'amount' => $this->prepareAmount($basket['sAmount']),
                'currency' => $basket['sCurrencyName'],
                'reference' =>  $temporaryOrderId,
            ]];

        $data['checkout']['returnUrl'] = $returUrl;
        $data['checkout']['termsUrl'] =Shopware()->Config()->getByNamespace('NetsCheckoutPayment', 'terms_and_conditions_url');

        $data['checkout']['integrationType'] = 'HostedPaymentPage';
        $data['checkout']['merchantHandlesConsumerData'] = true;

        if(Shopware()->Config()->getByNamespace('NetsCheckoutPayment', 'chargenow')) {
            $data['checkout']['charge'] = true;
        }

        $data['checkout']['consumer'] =
            ['email' =>  $customer->getEmail(),
                'privatePerson' => [
                    'firstName' => $this->stringFilter( $customer->getFirstname()),
                    'lastName' => $this->stringFilter(  $customer->getLastname())]
            ];

        $data['notifications'] =
            ['webhooks' =>
                [
                    ['eventName' => 'payment.checkout.completed',
                        'url' => 'https://some-url.com',
                        'authorization' => substr(str_shuffle(MD5(microtime())), 0, 10)]
        ]];

        $session = Shopware()->Container()->get('session');

        $session->offsetSet('nets_items_json', json_encode($data));

        return $data;
    }

    private function getOrderItems(array $basket) : array {
        $items = [];
        // Products
        $content = $basket['content'];

        foreach ($content as $item) {
            $taxAmount = (float) str_replace(',', '.', $item['tax']);

            $items[] = [
                'reference' => $item['itemInfoArray']['reference'],
                'name' => $this->stringFilter($item['articlename']),
                'quantity' => $item['quantity'],
                'unit' =>  $item['itemInfoArray']['unit']['unit'],
                'unitPrice' => $this->prepareAmount( $item['amountnetNumeric']),
                'taxRate' => $this->prepareAmount( $item['tax_rate']),
                'taxAmount' => $this->prepareAmount($taxAmount),
                'grossTotalAmount' => $this->prepareAmount( $item['amountNumeric']),
                'netTotalAmount' => $this->prepareAmount( $item['itemInfoArray']['unit']['unit'])];
        }

        if( $basket['sShippingcosts'] > 0 ) {
            $items[] = $this->shippingCostLine();
        }

        return $items;
    }

    public function getOrderItemsFromPayment($requestJson) {
         $requestArray = json_decode( $requestJson , true);

//        echo "<pre>";
//
//        var_dump( $requestArray['order']['items'] );
//
//        var_dump( $requestArray['order']['amount'] );


        $result = ['amount' => $requestArray['order']['amount'],
            'orderItems' => $requestArray['order']['items']
        ];

//
//        var_dump( $result );
//
//        echo "</pre>";

         return $result;
    }



    private function prepareAmount($amount = 0) {
        return (int)round($amount * 100);
    }

    public function stringFilter($string = '') {
        $string = substr($string, 0, 128);
        return preg_replace(self::ALLOWED_CHARACTERS_PATTERN, '', $string);
    }

    private function getTaxAmount(array $basket) :float {
        $totalTaxValue = 0;

        foreach($basket['sTaxRates'] as $taxName => $taxValue) {
            $totalTaxValue += $taxValue;
        }
        return $totalTaxValue;
    }

    private function shippingCostLine(array $basket) {
        return [
            'reference' => 'shipping',
            'name' => 'Shipping',
            'quantity' => 1,
            'unit' => 'pcs',
            'unitPrice' => $this->prepareAmount($basket['sShippingcosts']),
            'taxRate' => 0,
            'taxAmount' => 0,
            'grossTotalAmount' => $this->prepareAmount($basket['sShippingcosts']),
            'netTotalAmount' => $this->prepareAmount( $basket['sShippingcosts'] )
        ];
    }

    private function cancelAction() {

    }

    /**
     * @param $orderId
     * @param $amount
     */
    public function chargePayment($orderId, $amount) {

            // update captured amount in Payments models
            /** @var  $payment \NetsCheckoutPayment\Models\NetsCheckoutPayment */

            $payment = Shopware()->Models()->getRepository(NetsCheckoutPayment::class)->findOneBy(['orderId' => $orderId]);

            $payOperation = Shopware()->Models()->getRepository(NetsCheckoutPaymentApiOperations::class)->findAll(['orderId' => $orderId]);

            if ($amount > $payment->getAmountAuthorized() - $payment->getAmountCaptured()) {
                throw new \Exception('amount to capture must be less or equal to ');
            }

            $rep = Shopware()->Models()->getRepository(Order::class);

            $result = $rep->findOneBy(['number' => $orderId]);

            $paymentId = $result->getTransactionId();


            if($amount == $payment->getAmountAuthorized()) {
                $itemsJson = $payment->getItemsJson();
                $res = $this->getOrderItemsFromPayment($itemsJson);
                $data = json_encode($res);

            } else {
                $data = json_encode($this->orderRowsOperation($amount, 'item1'));
            }

            $this->apiService->setAuthorizationKey($this->getAuthorizationKey());

            $result = $this->apiService->chargePayment($paymentId, $data);

            $payment->setAmountCaptured( $payment->getAmountCaptured() + $amount);

            Shopware()->Models()->persist($payment);
            Shopware()->Models()->flush($payment);

            // update Operations model
            /** @var  $paymentOperation \NetsCheckoutPayment\Models\NetsCheckoutPaymentApiOperations */
            $paymentOperation = new NetsCheckoutPaymentApiOperations();
            $paymentOperation->setOperationType('capture');
            $paymentOperation->setOperationAmount($amount);
            $paymentOperation->setAmountAvailable($amount);
            $paymentOperation->setOrderId($orderId);
            $result = json_decode($result, true);
            $paymentOperation->setOperationId($result['chargeId']);

            Shopware()->Models()->persist($paymentOperation);
            Shopware()->Models()->flush($paymentOperation);

    }

    public function refundPayment($orderId, $amount) {
        try {

            $amountInitial = $amount;

            // update captured amount in Payments models
            /** @var  $payment \NetsCheckoutPayment\Models\NetsCheckoutPayment */

            $payment = Shopware()->Models()->getRepository(NetsCheckoutPayment::class)->findOneBy(['orderId' => $orderId]);

            if ($amount > $payment->getAmountCaptured()) {

                throw new \Exception('wrong amount');
            }


            $criteria = new \Doctrine\Common\Collections\Criteria();
            $criteria->where($criteria->expr()->eq('orderId', $orderId)); //->andWhere( $criteria->expr()->gt('amountAvailable', 0));


            $payOperation = Shopware()->Models()
                ->getRepository(NetsCheckoutPaymentApiOperations::class)
                ->findBy(['orderId' => $orderId]);

            $this->apiService->setAuthorizationKey($this->getAuthorizationKey());

            foreach ($payOperation as $operation) {

                /** @var  $operation \NetsCheckoutPayment\Models\NetsCheckoutPaymentApiOperations */
                $operation;

                $amountToRefund = 0;
                $amountAvailableToRefund = $operation->getAmountAvailable();
                if ($amountAvailableToRefund > 0 && $operation->getOperationType() == 'capture' && $amount > 0) {

                    $amountToRefund = $amountAvailableToRefund - $amount <= 0 ? $amountAvailableToRefund : $amount;

                    echo $amountToRefund . '---' . $operation->getId() . "<br>";


                    $data = json_encode($this->orderRowsOperation($amountToRefund, 'item1'));

                    $result = $this->apiService->refundPayment($operation->getOperationId() , $data);


                    // update Operations model
                    /** @var  $paymentOperation \NetsCheckoutPayment\Models\NetsCheckoutPaymentApiOperations */
                    $paymentOperation = new NetsCheckoutPaymentApiOperations();
                    $paymentOperation->setOperationType('refund');
                    $paymentOperation->setOperationAmount($amountToRefund);
                    $paymentOperation->setAmountAvailable(0);
                    $paymentOperation->setOrderId($orderId);
                    $result = json_decode($result, true);
                    $paymentOperation->setOperationId($result['refundId']);

                    Shopware()->Models()->persist($paymentOperation);
                    Shopware()->Models()->flush($paymentOperation);

                    $amount = $amount - $amountToRefund;

                    $operation->setAmountAvailable($operation->getAmountAvailable() - $amountToRefund);
                    Shopware()->Models()->persist($operation);
                    Shopware()->Models()->flush($operation);



                    $payment->setAmountRefunded($payment->getAmountRefunded() + $amountToRefund);
                    Shopware()->Models()->persist($payment);
                    Shopware()->Models()->flush($payment);
                }



            }




        }catch (\Exception $ex) {
            echo  $ex->getMessage();
        }
    }

    private function orderRowsOperation($amount, $name) {

        $result = ['amount' => $amount,
                   'orderItems' => [
                        ['reference' => 'ref1',
                         'name'  => $name,
                         'quantity' =>1,
                         'unit' => "psc",
                         'unitPrice' => $amount,
                         'taxRate'=> 0,
                         'taxAmount'=> 0,
                         'grossTotalAmount' => $amount,
                         'netTotalAmount'=> $amount]
                    ]
            ];

        return $result;

    }

    private function getAuthorizationKey() {
        $key_type = 'live_secret_key';
        if(Shopware()->Config()->getByNamespace('NetsCheckoutPayment', 'testmode')) {
            $this->apiService->setEnv('test');
            $key_type = 'test_secret_key';
        }
        return Shopware()->Config()->getByNamespace('NetsCheckoutPayment', $key_type);
    }

}