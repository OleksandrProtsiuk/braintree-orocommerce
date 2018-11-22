<?php

namespace Entrepids\Bundle\BraintreeBundle\Method\Operation\Complete;

use Entrepids\Bundle\BraintreeBundle\Method\Operation\AbstractBraintreeOperation;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class OperationComplete extends AbstractBraintreeOperation
{

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
        if ($paymentTransaction->getAction() === PaymentMethodInterface::CHARGE) {
            $paymentTransaction->setActive(false);
        }
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
