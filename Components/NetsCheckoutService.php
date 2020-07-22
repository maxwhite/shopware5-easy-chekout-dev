<?php

namespace NetsCheckoutPayment\Components;


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

    public function createPayment($userId, $basket) {
        $result = $this->collectRequestParams($userId, $basket);
        $key_type = 'live_secret_key';
        if(Shopware()->Config()->getByNamespace('NetsCheckoutPayment', 'testmode')) {
            $this->apiService->setEnv('test');
            $key_type = 'test_secret_key';
        }
        $key = Shopware()->Config()->getByNamespace('NetsCheckoutPayment', $key_type);
        $this->apiService->setAuthorizationKey($key);

        error_log(  $key  );

        return $this->apiService->createPayment( json_encode($result) );
    }

    private function collectRequestParams($userId, array $basket ) : array {

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
                'reference' =>  1002003,
            ]];

        $data['checkout']['returnUrl'] = $returUrl;
        $data['checkout']['termsUrl'] ='https://return-url.com';

        $data['checkout']['integrationType'] = 'HostedPaymentPage';
        $data['checkout']['merchantHandlesConsumerData'] = true;

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

}