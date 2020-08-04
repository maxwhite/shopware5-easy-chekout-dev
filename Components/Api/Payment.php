<?php


namespace NetsCheckoutPayment\Components\Api;


class Payment
{
    private $paymentObj;

    public function __construct(string $paymentJson) {
        $this->paymentObj = json_decode($paymentJson);
    }

    public function getPaymentId() {
        return $this->paymentObj->payment->paymentId;
    }

    public function getPaymentType() {
        return isset($this->paymentObj->payment->paymentDetails->paymentType) ?
            $this->paymentObj->payment->paymentDetails->paymentType : null;
    }

    public function getCardDetails() {
        if( $this->getPaymentType() == 'CARD' ) {
            return ['maskedPan'  =>   $this->paymentObj->payment->paymentDetails->cardDetails->maskedPan,
                'expiryDate' =>  $this->paymentObj->payment->paymentDetails->cardDetails->expiryDate];
        }
    }

    public function getPaymentMethod() {
        return isset($this->paymentObj->payment->paymentDetails->paymentMethod) ?
            $this->paymentObj->payment->paymentDetails->paymentMethod : null;
    }

    public function getReservedAmount() {
        return isset($this->paymentObj->payment->summary->reservedAmount) ?
            $this->paymentObj->payment->summary->reservedAmount : 0;
    }

    public function getChargedAmount() {
        return isset($this->paymentObj->payment->summary->chargedAmount) ?
            $this->paymentObj->payment->summary->chargedAmount : 0;
    }

    public function getCheckoutUrl() {
        return $this->paymentObj->payment->checkout->url;
    }

    public function getFirstChargeId()
    {
        if (isset($this->paymentObj->payment->charges)) {
            $charges = current($this->paymentObj->payment->charges);
            return $charges->chargeId;
        }
    }
}