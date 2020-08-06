<?php

use NetsCheckoutPayment\Models\NetsCheckoutPayment;
use function Shopware;

class Shopware_Controllers_Backend_NetsCheckout  extends Shopware_Controllers_Backend_Application {


    protected $model = NetsCheckoutPayment::class;

    public function testAction() {

        $orderId = $this->Request()->get('id');

        /** @var  $payment \NetsCheckoutPayment\Models\NetsCheckoutPayment */
        $payment = Shopware()->Models()->getRepository(NetsCheckoutPayment::class)->findOneBy(['orderId' => $orderId]);
        $params = ['data' => ['id' => $payment->getOrderId(),
                              'orderId' => $payment->getOrderId(),
                              'amountAuthorized' => $payment->getAmountAuthorized() - $payment->getAmountCaptured(),
                              'amountCaptured' => $payment->getAmountCaptured(),
                              'amountRefunded' => $payment->getAmountRefunded()]];
        $this->View()->assign($params);
    }


    public function captureAction() {

      $orderId = $this->Request()->get('id');;

      /** @var $service \NetsCheckoutPayment\Components\NetsCheckoutService */
      $service = $this->get('nets_checkout.checkout_service');

      $amountToCharge = (int) $this->Request()->get('amountAuthorized');

      try {

          error_log('start capturing');

          $service->chargePayment($orderId, $amountToCharge);

          $params = ["success" => true,
              "msg" => "Consignment updated"];

      }catch (\Exception $ex ) {

          error_log('excetption');

          error_log( $ex->getMessage() );

          $params = ["success" => false,
              "msg" => "Consignment updated"];
      }

          $this->View()->assign($params);
    }
}
