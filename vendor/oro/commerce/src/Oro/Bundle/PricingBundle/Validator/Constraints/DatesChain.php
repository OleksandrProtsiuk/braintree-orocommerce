<?php

namespace Oro\Bundle\PricingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class DatesChain extends Constraint implements \JsonSerializable
{
    const ALIAS = 'oro_pricing_dates_chain_validator';

    /**
     * @var string
     */
    public $message = 'oro.pricing.validators.price_list.dates_chain.message';

    /**
     * @var array
     */
    public $chain = [];

    /**
     * @return string
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return self::ALIAS;
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'message' => $this->message,
            'chain' => $this->chain
        ];
    }
}
