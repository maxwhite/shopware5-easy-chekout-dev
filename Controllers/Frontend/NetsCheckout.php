<?php

use Shopware\Components\CSRFWhitelistAware;
use NetsCheckout\Components\Api\Exception\EasyApiException;

use function Shopware;

class Shopware_Controllers_Frontend_NetsCheckout extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{


    private $service;

    private $session;

    public function preDispatch()
    {
        parent::preDispatch();
        $this->service = $this->get('nets_checkout.checkout_service');

        $this->session = $this->get('session');

    }

    public function indexAction() {



        /** @var  $service NetsCheckoutPayment\Components\NetsCheckoutService **/
        $service = $this->service;

        try {
            $payment = $service->createPayment($this->session->offsetGet('sUserId'), $this->getBasket());
            $result = json_decode( $payment, true );

            $language = Shopware()->Config()->getByNamespace('NetsCheckoutPayment', 'language');

            echo $language;

            exit;

            return $this->redirect( $result['hostedPaymentPageUrl'] . '&language=' . $language );

        }  catch (EasyApiException $e) {

            echo $e->getMessage();
            echo 'error occured';
        }
        exit;
    }


    public function cancelAction() {

        echo $this->getCancelUrl();
        exit;

    }


    /**
     * @inheritDoc
     */
    public function getWhitelistedCSRFActions()
    {
        // TODO: Implement getWhitelistedCSRFActions() method.
    }


    /**
     * Get cancel url
     *
     * @return mixed|string
     */
    private function getCancelUrl()
    {
        return $this->Front()->Router()->assemble([
            'controller' => 'NetsCheckout',
            'action' => 'cancel',
            'forceSecure' => true
        ]);
    }
}