<?php

namespace Oro\Bundle\PricingBundle\Builder;

use Oro\Bundle\PricingBundle\Entity\PriceListWebsiteFallback;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToWebsiteRepository;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @method PriceListToWebsiteRepository getPriceListToEntityRepository()
 */
class WebsiteCombinedPriceListsBuilder extends AbstractCombinedPriceListBuilder
{
    /**
     * @var CustomerGroupCombinedPriceListsBuilder
     */
    protected $customerGroupCombinedPriceListsBuilder;

    /**
     * @param CustomerGroupCombinedPriceListsBuilder $customerGroupCombinedPriceListsBuilder
     * @return $this
     */
    public function setCustomerGroupCombinedPriceListsBuilder($customerGroupCombinedPriceListsBuilder)
    {
        $this->customerGroupCombinedPriceListsBuilder = $customerGroupCombinedPriceListsBuilder;

        return $this;
    }

    /**
     * @param Website|null $currentWebsite
     * @param bool $force
     */
    public function build(Website $currentWebsite = null, $force = false)
    {
        if (!$this->isBuiltForWebsite($currentWebsite)) {
            $websites = [$currentWebsite];
            if (!$currentWebsite) {
                $fallback = $force ? null : PriceListWebsiteFallback::CONFIG;
                $websites = $this->getPriceListToEntityRepository()
                    ->getWebsiteIteratorByDefaultFallback($fallback);
            }

            foreach ($websites as $website) {
                $this->updatePriceListsOnCurrentLevel($website, $force);
                $this->customerGroupCombinedPriceListsBuilder->build($website, null, $force);
            }

            if ($currentWebsite) {
                $this->garbageCollector->cleanCombinedPriceLists();
            }
            $this->setBuiltForWebsite($currentWebsite);
        }
    }

    /**
     * @param Website $website
     * @param bool $force
     */
    protected function updatePriceListsOnCurrentLevel(Website $website, $force)
    {
        $priceListsToWebsite = $this->getPriceListToEntityRepository()
            ->findOneBy(['website' => $website]);
        if (!$priceListsToWebsite) {
            /** @var PriceListToWebsiteRepository $repo */
            $repo = $this->getCombinedPriceListToEntityRepository();
            $repo->delete($website);

            if ($this->hasFallbackOnNextLevel($website)) {
                //is this case price list would be fetched from next level, and there is no need to store the own
                return;
            }
        }
        $collection = $this->priceListCollectionProvider->getPriceListsByWebsite($website);
        $combinedPriceList = $this->combinedPriceListProvider->getCombinedPriceList($collection);
        $this->updateRelationsAndPrices($combinedPriceList, $website, null, $force);
    }

    /**
     * @param Website|null $website
     * @return bool
     */
    protected function isBuiltForWebsite(Website $website = null)
    {
        $websiteId = 0;
        if ($website) {
            $websiteId = $website->getId();
        }
        return !empty($this->builtList[$websiteId]);
    }

    /**
     * @param Website|null $website
     */
    protected function setBuiltForWebsite(Website $website = null)
    {
        $websiteId = 0;
        if ($website) {
            $websiteId = $website->getId();
        }

        $this->builtList[$websiteId] = true;
    }

    /**
     * @param Website $website
     * @return bool
     */
    public function hasFallbackOnNextLevel(Website $website)
    {
        $fallback = $this->getFallbackRepository()->findOneBy(
            ['website' => $website, 'fallback' => PriceListWebsiteFallback::CURRENT_WEBSITE_ONLY]
        );

        return $fallback === null;
    }
}
