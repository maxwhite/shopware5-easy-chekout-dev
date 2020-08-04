<?php

use NetsCheckoutPayment\Models\NetsCheckoutPayment;
use Shopware\Components\CSRFWhitelistAware;
use NetsCheckoutPayment\Components\Api\Exception\EasyApiException;

use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;
use function Shopware;

class Shopware_Controllers_Frontend_NetsCheckout extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{

    /** @var \NetsCheckoutPayment\Components\NetsCheckoutService */
    private $service;

    private $session;

    public function preDispatch()
    {
        parent::preDispatch();
        $this->service = $this->get('nets_checkout.checkout_service');
        $this->session = $this->get('session');
    }

    public function indexAction() {

        $test = 1;

        if($test) {
            $orderId = 20040;
            $amount = 30000;

            //$this->service->chargePayment($orderId, $amount);

            $this->service->refundPayment($orderId, $amount);

            exit;
        }
        try {
            $payment = $this->service->createPayment($this->session->offsetGet('sUserId'), $this->getBasket(), $this->session->offsetGet('sessionId'));
            $result = json_decode( $payment, true );
            $language = Shopware()->Config()->getByNamespace('NetsCheckoutPayment', 'language');
            return $this->redirect( $result['hostedPaymentPageUrl'] . '&language=' . $language );
        }  catch (EasyApiException $e) {

            echo $e->getMessage();
            exit;
        }
    }

    public function returnAction() {
        /** @var  $checkoutApiService \NetsCheckoutPayment\Components\Api\NetsCheckoutService */
        $checkoutApiService = $this->get('nets_checkout.checkout_api_service');

        $key_type = 'live_secret_key';
        if(Shopware()->Config()->getByNamespace('NetsCheckoutPayment', 'testmode')) {
            $checkoutApiService->setEnv('test');
            $key_type = 'test_secret_key';
        }
        $key = Shopware()->Config()->getByNamespace('NetsCheckoutPayment', $key_type);
        $checkoutApiService->setAuthorizationKey($key);

        /** @var  $payment  \NetsCheckoutPayment\Components\Api\Payment */
        $payment = $checkoutApiService->getPayment($this->request->get('paymentid'));


        if($payment->getReservedAmount() || $payment->getPaymentMethod()) {
            $paymentId = $this->request->get('paymentid');
            $orderNumber = $this->saveOrder(($this->request->get('paymentid')), $paymentId, Status::PAYMENT_STATE_OPEN);

            if($orderNumber) {
                    // update reference from temporary to real orderid through Nets api
                    $payload = json_encode(['reference' => $orderNumber,
                                            'checkoutUrl' => $payment->getCheckoutUrl()]);
                    $checkoutApiService->updateReference($paymentId, $payload);

                    // persist payment to database
                    $paymentModel = new NetsCheckoutPayment();
                    $paymentModel->setOrderId($orderNumber);
                    $paymentModel->setNetsPaymentId( $payment->getPaymentId() );
                    $paymentModel->setPaytype( $payment->getPaymentType() );
                    $paymentModel->setAmountAuthorized($payment->getReservedAmount());
                    $paymentModel->setAmountCaptured($payment->getChargedAmount());

                    Shopware()->Models()->persist($paymentModel);
                    Shopware()->Models()->flush($paymentModel);

                $this->redirect(['controller' => 'checkout', 'action' => 'finish', 'sUniqueID' => $this->request->get('paymentid')]);
                return;
            } else {
                $this->redirect(['controller' => 'checkout', 'action' => 'confirm']);
                return;
            }
        } else {
            $this->redirect(['controller' => 'checkout', 'action' => 'confirm']);
            return;
        }
    }


    public function testAction() {



    }

    /**
     * @inheritDoc
     */
    public function getWhitelistedCSRFActions()
    {
        // TODO: Implement getWhitelistedCSRFActions() method.
    }

}
