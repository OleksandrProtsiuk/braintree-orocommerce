<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Converter\ShippingContextToRuleValuesConverter;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;

class ShippingMethodsConfigsRulesProvider
{
    /** @var RuleFiltrationServiceInterface */
    private $filtrationService;

    /** @var ShippingContextToRuleValuesConverter */
    private $converter;

    /** @var ShippingMethodsConfigsRuleRepository */
    private $repository;

    /**
     * @param RuleFiltrationServiceInterface $filtrationService
     * @param ShippingContextToRuleValuesConverter $converter
     * @param ShippingMethodsConfigsRuleRepository $repository
     */
    public function __construct(
        RuleFiltrationServiceInterface $filtrationService,
        ShippingContextToRuleValuesConverter $converter,
        ShippingMethodsConfigsRuleRepository $repository
    ) {
        $this->filtrationService = $filtrationService;
        $this->converter = $converter;
        $this->repository = $repository;
    }

    /**
     * @param ShippingContextInterface $context
     * @return array|ShippingMethodsConfigsRule[]
     */
    public function getAllFilteredShippingMethodsConfigs(ShippingContextInterface $context)
    {
        if ($context->getShippingAddress()) {
            $methodsConfigsRules = $this->repository->getByDestinationAndCurrency(
                $context->getShippingAddress(),
                $context->getCurrency()
            );
        } else {
            $methodsConfigsRules = $this->repository->getByCurrencyWithoutDestination(
                $context->getCurrency()
            );
        }

        $arrayContext = $this->converter->convert($context);

        return $this->filtrationService->getFilteredRuleOwners(
            $methodsConfigsRules,
            $arrayContext
        );
    }
}
