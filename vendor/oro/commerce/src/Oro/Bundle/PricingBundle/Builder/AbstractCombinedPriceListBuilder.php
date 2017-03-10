<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Provider\CombinedPriceListProvider;
use Oro\Bundle\PricingBundle\Provider\PriceListCollectionProvider;
use Oro\Bundle\PricingBundle\Resolver\CombinedPriceListScheduleResolver;
use Oro\Bundle\PricingBundle\Resolver\CombinedProductPriceResolver;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
abstract class AbstractCombinedPriceListBuilder
{
    /**
     * @var CombinedPriceListTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var CombinedPriceListGarbageCollector
     */
    protected $garbageCollector;

    /**
     * @var PriceListCollectionProvider
     */
    protected $priceListCollectionProvider;

    /**
     * @var CombinedPriceListProvider
     */
    protected $combinedPriceListProvider;

    /**
     * @var EntityRepository
     */
    protected $priceListToEntityRepository;

    /**
     * @var string
     */
    protected $priceListToEntityClassName;

    /**
     * @var CombinedPriceListRepository
     */
    protected $combinedPriceListRepository;

    /**
     * @var CombinedPriceListRepository
     */
    protected $fallbackRepository;

    /**
     * @var EntityRepository
     */
    protected $combinedPriceListToEntityRepository;

    /**
     * @var string
     */
    protected $combinedPriceListClassName;

    /**
     * @var string
     */
    protected $combinedPriceListToEntityClassName;

    /**
     * @var string
     */
    protected $fallbackClassName;

    /**
     * @var array
     */
    protected $builtList = [];

    /**
     * @var CombinedPriceListScheduleResolver
     */
    protected $scheduleResolver;

    /**
     * @var CombinedProductPriceResolver
     */
    protected $priceResolver;

    /**
     * @param ManagerRegistry $registry
     * @param PriceListCollectionProvider $priceListCollectionProvider
     * @param CombinedPriceListProvider $combinedPriceListProvider
     * @param CombinedPriceListGarbageCollector $garbageCollector
     * @param CombinedPriceListScheduleResolver $scheduleResolver
     * @param CombinedProductPriceResolver $priceResolver
     * @param CombinedPriceListTriggerHandler $triggerHandler
     */
    public function __construct(
        ManagerRegistry $registry,
        PriceListCollectionProvider $priceListCollectionProvider,
        CombinedPriceListProvider $combinedPriceListProvider,
        CombinedPriceListGarbageCollector $garbageCollector,
        CombinedPriceListScheduleResolver $scheduleResolver,
        CombinedProductPriceResolver $priceResolver,
        CombinedPriceListTriggerHandler $triggerHandler
    ) {
        $this->registry = $registry;
        $this->priceListCollectionProvider = $priceListCollectionProvider;
        $this->combinedPriceListProvider = $combinedPriceListProvider;
        $this->garbageCollector = $garbageCollector;
        $this->scheduleResolver = $scheduleResolver;
        $this->priceResolver = $priceResolver;
        $this->triggerHandler = $triggerHandler;
    }

    /**
     * @param string $priceListToEntityClassName
     * @return $this
     */
    public function setPriceListToEntityClassName($priceListToEntityClassName)
    {
        $this->priceListToEntityClassName = $priceListToEntityClassName;

        return $this;
    }

    /**
     * @param string $combinedPriceListClassName
     * @return $this
     */
    public function setCombinedPriceListClassName($combinedPriceListClassName)
    {
        $this->combinedPriceListClassName = $combinedPriceListClassName;

        return $this;
    }
    
    /**
     * @param string $fallbackClassName
     * @return $this
     */
    public function setFallbackClassName($fallbackClassName)
    {
        $this->fallbackClassName = $fallbackClassName;

        return $this;
    }

    /**
     * @return EntityRepository
     */
    protected function getPriceListToEntityRepository()
    {
        if (!$this->priceListToEntityRepository) {
            $this->priceListToEntityRepository = $this->registry
                ->getManagerForClass($this->priceListToEntityClassName)
                ->getRepository($this->priceListToEntityClassName);
        }

        return $this->priceListToEntityRepository;
    }

    /**
     * @return EntityRepository
     */
    protected function getCombinedPriceListToEntityRepository()
    {
        if (!$this->combinedPriceListToEntityRepository) {
            $this->combinedPriceListToEntityRepository = $this->registry
                ->getManagerForClass($this->combinedPriceListToEntityClassName)
                ->getRepository($this->combinedPriceListToEntityClassName);
        }

        return $this->combinedPriceListToEntityRepository;
    }

    /**
     * @return CombinedPriceListRepository
     */
    protected function getCombinedPriceListRepository()
    {
        if (!$this->combinedPriceListRepository) {
            $this->combinedPriceListRepository = $this->registry
                ->getManagerForClass($this->combinedPriceListClassName)
                ->getRepository($this->combinedPriceListClassName);
        }

        return $this->combinedPriceListRepository;
    }

    /**
     * @return EntityRepository
     */
    protected function getFallbackRepository()
    {
        if (!$this->fallbackRepository) {
            $this->fallbackRepository = $this->registry
                ->getManagerForClass($this->fallbackClassName)
                ->getRepository($this->fallbackClassName);
        }

        return $this->fallbackRepository;
    }


    /**
     * @return string
     */
    public function getCombinedPriceListToEntityClassName()
    {
        return $this->combinedPriceListToEntityClassName;
    }

    /**
     * @param string $combinedPriceListToEntityClassName
     */
    public function setCombinedPriceListToEntityClassName($combinedPriceListToEntityClassName)
    {
        $this->combinedPriceListToEntityClassName = $combinedPriceListToEntityClassName;
    }

    /**
     * @return $this
     */
    public function resetCache()
    {
        $this->builtList = [];

        return $this;
    }

    /**
     * @param CombinedPriceList $cpl
     * @param Website $website
     * @param Customer|CustomerGroup $targetEntity
     * @param bool|false $force
     */
    protected function updateRelationsAndPrices(
        CombinedPriceList $cpl,
        Website $website,
        $targetEntity = null,
        $force = false
    ) {
        $activeCpl = $this->scheduleResolver->getActiveCplByFullCPL($cpl);
        if ($activeCpl === null) {
            $activeCpl = $cpl;
        }
        $relation = $this->getCombinedPriceListRepository()
            ->updateCombinedPriceListConnection($cpl, $activeCpl, $website, $targetEntity);
        if ($force || !$activeCpl->isPricesCalculated()) {
            $this->priceResolver->combinePrices($activeCpl);
        }
        $hasOtherRelations = $this->getCombinedPriceListRepository()->hasOtherRelations($relation);
        //when CPL used the first time at this website
        if ($force || !$hasOtherRelations) {
            $this->triggerHandler->process($relation->getPriceList(), $relation->getWebsite());
        }
    }

    /**
     * @return array
     */
    public function getBuiltList()
    {
        return $this->builtList;
    }
}
