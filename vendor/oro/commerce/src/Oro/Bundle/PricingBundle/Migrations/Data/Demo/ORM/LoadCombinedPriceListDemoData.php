<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadCombinedPriceListDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListToCustomerDemoData',
            'Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListToCustomerGroupDemoData',
            'Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadPriceListToWebsiteDemoData',
            'Oro\Bundle\PricingBundle\Migrations\Data\Demo\ORM\LoadProductPriceDemoData',
        ];
    }

    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $this->container->get('oro_pricing.builder.combined_price_list_builder')->build(true);
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }
}
