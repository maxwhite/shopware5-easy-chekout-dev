<?php


use Shopware\Components\CSRFWhitelistAware;

class Shopware_Controllers_Frontend_NetsCheckout extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    public function indexAction() {
        echo 'redirect to Nets Checkout Paymetn';
        exit;
    }


    /**
     * @inheritDoc
     */
    public function getWhitelistedCSRFActions()
    {
        // TODO: Implement getWhitelistedCSRFActions() method.
    }
}