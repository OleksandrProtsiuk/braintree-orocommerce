<?php

namespace Oro\Bundle\PricingBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\PricingBundle\Entity\PriceList;

class LoadPriceListData extends AbstractFixture implements ContainerAwareInterface
{
    /** @var string */
    const DEFAULT_PRICE_LIST_NAME = 'Default Price List';

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $priceList = new PriceList();
        $priceList
            ->setDefault(true)
            ->setCurrencies($this->container->get('oro_currency.config.currency')->getCurrencyList())
            ->setName(self::DEFAULT_PRICE_LIST_NAME);
        $manager->persist($priceList);
        $manager->flush();

        $this->addReference('default_price_list', $priceList);
    }
}
