<?php

namespace NetsCheckout;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\UninstallContext;

class NetsCheckout extends Plugin
{
        public function install(InstallContext $context)
        {
            /** @var \Shopware\Components\Plugin\PaymentInstaller $installer */
            $installer = $this->container->get('shopware.plugin_payment_installer');

            $options = [
                'name' => 'nets_checkout',
                'description' => 'Nets Checkout',
                'action' => 'NetsCheckout',
                'active' => 0,
                'position' => 0,
                'additionalDescription' =>
                    '<img src="http://your-image-url"/>'
                    . '<div id="payment_desc">'
                    . '  Nets checkout payment method'
                    . '</div>'
            ];
            $installer->createOrUpdate($context->getPlugin(), $options);

            $installer->createOrUpdate($context->getPlugin(), $options);
        }

        /**
         * @param UninstallContext $context
         */
        public function uninstall(UninstallContext $context)
        {
            $this->setActiveFlag($context->getPlugin()->getPayments(), false);
        }

        /**
         * @param DeactivateContext $context
         */
        public function deactivate(DeactivateContext $context)
        {
            $this->setActiveFlag($context->getPlugin()->getPayments(), false);
        }

        /**
         * @param ActivateContext $context
         */
        public function activate(ActivateContext $context)
        {
            $this->setActiveFlag($context->getPlugin()->getPayments(), true);
        }

        /**
         * @param Payment[] $payments
         * @param $active bool
         */
        private function setActiveFlag($payments, $active)
        {
            $em = $this->container->get('models');

            foreach ($payments as $payment) {
                $payment->setActive($active);
            }
            $em->flush();
        }

}