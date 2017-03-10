<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Method\PricesAwareShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewFactory;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;

class ShippingPriceProvider implements ShippingPriceProviderInterface
{
    /** @var ShippingMethodsConfigsRulesProvider */
    protected $shippingRulesProvider;

    /** @var ShippingMethodRegistry */
    protected $registry;

    /** @var ShippingPriceCache */
    protected $priceCache;

    /** @var ShippingMethodViewFactory */
    protected $shippingMethodViewFactory;

    /**
     * @param ShippingMethodsConfigsRulesProvider $shippingRulesProvider
     * @param ShippingMethodRegistry $registry
     * @param ShippingPriceCache $priceCache
     * @param ShippingMethodViewFactory $shippingMethodViewFactory
     */
    public function __construct(
        ShippingMethodsConfigsRulesProvider $shippingRulesProvider,
        ShippingMethodRegistry $registry,
        ShippingPriceCache $priceCache,
        ShippingMethodViewFactory $shippingMethodViewFactory
    ) {
        $this->shippingRulesProvider = $shippingRulesProvider;
        $this->registry = $registry;
        $this->priceCache = $priceCache;
        $this->shippingMethodViewFactory = $shippingMethodViewFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getApplicableMethodsViews(ShippingContextInterface $context)
    {
        $methodCollection = new ShippingMethodViewCollection();

        $rules = $this->shippingRulesProvider->getAllFilteredShippingMethodsConfigs($context);
        foreach ($rules as $rule) {
            foreach ($rule->getMethodConfigs() as $methodConfig) {
                $methodId = $methodConfig->getMethod();
                $method = $this->registry->getShippingMethod($methodId);

                if (!$method) {
                    continue;
                }

                $methodView = $this->shippingMethodViewFactory->createMethodView(
                    $methodId,
                    $method->getLabel(),
                    $method->isGrouped(),
                    $method->getSortOrder()
                );

                $methodCollection->addMethodView($methodId, $methodView);

                $types = $this->getApplicableMethodTypesViews($context, $methodConfig);

                if (count($types) === 0) {
                    continue;
                }

                $methodCollection->addMethodTypesViews($methodId, $types);
            }
        }

        return $methodCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrice(ShippingContextInterface $context, $methodId, $typeId)
    {
        $method = $this->registry->getShippingMethod($methodId);
        if (!$method) {
            return null;
        }

        $type = $method->getType($typeId);
        if (!$type) {
            return null;
        }

        $rules = $this->shippingRulesProvider->getAllFilteredShippingMethodsConfigs($context);
        foreach ($rules as $rule) {
            foreach ($rule->getMethodConfigs() as $methodConfig) {
                if ($methodConfig->getMethod() !== $methodId) {
                    continue;
                }

                $typesOptions = $this->getEnabledTypesOptions($methodConfig->getTypeConfigs()->toArray());
                if (array_key_exists($typeId, $typesOptions)) {
                    if ($this->priceCache->hasPrice($context, $methodId, $typeId)) {
                        return $this->priceCache->getPrice($context, $methodId, $typeId);
                    }
                    $price = $type->calculatePrice(
                        $context,
                        $methodConfig->getOptions(),
                        $typesOptions[$typeId]
                    );
                    $this->priceCache->savePrice($context, $methodId, $typeId, $price);

                    return $price;
                }
            }
        }

        return null;
    }

    /**
     * @param ShippingContextInterface $context
     * @param ShippingMethodConfig $methodConfig
     * @return array
     */
    protected function getApplicableMethodTypesViews(
        ShippingContextInterface $context,
        ShippingMethodConfig $methodConfig
    ) {
        $method = $this->registry->getShippingMethod($methodConfig->getMethod());
        $methodId = $method->getIdentifier();
        $methodOptions = $methodConfig->getOptions();
        $typesOptions = $this->getEnabledTypesOptions($methodConfig->getTypeConfigs()->toArray());

        $prices = [];
        foreach ($typesOptions as $typeId => $typeOptions) {
            if ($this->priceCache->hasPrice($context, $methodId, $typeId)) {
                $prices[$typeId] = $this->priceCache->getPrice($context, $methodId, $typeId);
            }
        }
        $requestedTypesOptions = array_diff_key($typesOptions, $prices);

        if ($method instanceof PricesAwareShippingMethodInterface && count($requestedTypesOptions) > 0) {
            $prices = array_replace(
                $prices,
                $method->calculatePrices($context, $methodOptions, $requestedTypesOptions)
            );
        } else {
            foreach ($requestedTypesOptions as $typeId => $typeOptions) {
                $type = $method->getType($typeId);
                if ($type) {
                    $prices[$typeId] = $type->calculatePrice($context, $methodOptions, $typeOptions);
                }
            }
        }

        $types = [];
        foreach (array_filter($prices) as $typeId => $price) {
            if (array_key_exists($typeId, $requestedTypesOptions)) {
                $this->priceCache->savePrice($context, $methodId, $typeId, $price);
            }
            $type = $method->getType($typeId);
            $types[$typeId] = $this->shippingMethodViewFactory
                ->createMethodTypeView(
                    $type->getIdentifier(),
                    $type->getLabel(),
                    $type->getSortOrder(),
                    $price
                );
        }

        return $types;
    }

    /**
     * @param array $typeConfigs
     *
     * @return array
     */
    protected function getEnabledTypesOptions(array $typeConfigs)
    {
        return array_reduce(
            $typeConfigs,
            function (array $result, ShippingMethodTypeConfig $config) {
                if ($config->isEnabled()) {
                    $result[$config->getType()] = $config->getOptions();
                }

                return $result;
            },
            []
        );
    }
}
