<?php

namespace Entrepids\Bundle\BraintreeBundle\Method\Operation\Validate;

use Entrepids\Bundle\BraintreeBundle\Method\Operation\AbstractBraintreeOperation;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class OperationValidate extends AbstractBraintreeOperation
{

    const ZERO_AMOUNT = 0;

    /**
     * (non-PHPdoc)
     *
     * @see \Entrepids\Bundle\BraintreeBundle\Method\Operation\AbstractBraintreeOperation::preProcessOperation()
     */
    protected function preProcessOperation()
    {
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Entrepids\Bundle\BraintreeBundle\Method\Operation\AbstractBraintreeOperation::postProcessOperation()
     */
    protected function postProcessOperation()
    {
        $paymentTransaction = $this->paymentTransaction;
        $paymentTransaction->setAmount(self::ZERO_AMOUNT)->setCurrency('USD');

        // ORO REVIEW:
        // All data transfers should be done via PaymentTransaction.
        // It is not safe to work with global variables.
        // The reference to global variables also available in other places.
        $transactionOptions = $paymentTransaction->getTransactionOptions();
        if (array_key_exists('credit_card_value', $_POST)) {
            $credit_card_value = $_POST['credit_card_value'];
        } else {
            $paymentTransaction->setSuccessful(false)->setActive(false);
            return [];
        }

        if (array_key_exists('payment_method_nonce', $_POST)) {
            $nonce = $_POST["payment_method_nonce"];
        } else {
            $nonce = null;
        }

        $transactionOptions['nonce'] = $nonce;
        $transactionOptions['credit_card_value'] = $credit_card_value;
        $paymentTransaction->setTransactionOptions($transactionOptions);

        $paymentTransaction->setSuccessful(true)
            ->setAction(PaymentMethodInterface::VALIDATE)
            ->setActive(true);

        return [];
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Entrepids\Bundle\BraintreeBundle\Method\Operation\AbstractBraintreeOperation::preprocessDataToSend()
     */
    protected function preprocessDataToSend()
    {
    }
}
