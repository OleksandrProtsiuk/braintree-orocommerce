<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\PricingBundle\Compiler\ProductAssignmentRuleCompiler;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToProductRepository;
use Oro\Bundle\PricingBundle\Event\AssignmentBuilderBuildEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PriceListProductAssignmentBuilder
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertFromSelectQueryExecutor;

    /**
     * @var ProductAssignmentRuleCompiler
     */
    protected $ruleCompiler;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param ManagerRegistry $registry
     * @param InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor
     * @param ProductAssignmentRuleCompiler $ruleCompiler
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        ManagerRegistry $registry,
        InsertFromSelectQueryExecutor $insertFromSelectQueryExecutor,
        ProductAssignmentRuleCompiler $ruleCompiler,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->registry = $registry;
        $this->insertFromSelectQueryExecutor = $insertFromSelectQueryExecutor;
        $this->ruleCompiler = $ruleCompiler;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param PriceList $priceList
     * @param Product|null $product
     */
    public function buildByPriceList(PriceList $priceList, Product $product = null)
    {
        $this->clearGenerated($priceList, $product);
        if ($priceList->getProductAssignmentRule()) {
            $this->insertFromSelectQueryExecutor->execute(
                PriceListToProduct::class,
                $this->ruleCompiler->getOrderedFields(),
                $this->ruleCompiler->compile($priceList, $product)
            );
        }
        $this->registry->getManagerForClass(ProductPrice::class)
            ->getRepository(ProductPrice::class)
            ->deleteInvalidPrices($priceList);

        $event = new AssignmentBuilderBuildEvent($priceList, $product);
        $this->eventDispatcher->dispatch(AssignmentBuilderBuildEvent::NAME, $event);
    }

    /**
     * @param PriceList $priceList
     * @param Product $product
     */
    protected function clearGenerated(PriceList $priceList, Product $product = null)
    {
        /** @var PriceListToProductRepository $repo */
        $repo = $this->registry->getManagerForClass(PriceListToProduct::class)
            ->getRepository(PriceListToProduct::class);
        $repo->deleteGeneratedRelations($priceList, $product);
    }
}
