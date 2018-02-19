<?php

namespace Entrepids\Bundle\BraintreeBundle\Settings\DataProvider;

class BasicPaymentActionsDataProvider implements PaymentActionsDataProviderInterface
{
    /**
     * @internal
     */
    const AUTHORIZE = 'authorize';

    /**
     * @internal
     */
    const CHARGE = 'charge';

    // ORO REVIEW:
    // Why we need next constants?
    /**
     * @internal
     */
    const INVOICE = 'invoice';
    
    /**
     * @internal
     */
    const SHIPMENT = 'shipment';
    /**
     * @internal
     */
    const AUTHORIZED = 'authorized';
    /**
     * @return string[]
     */
    public function getPaymentActions()
    {
        return [
            self::AUTHORIZE,
            self::CHARGE,
        ];
    }
}
