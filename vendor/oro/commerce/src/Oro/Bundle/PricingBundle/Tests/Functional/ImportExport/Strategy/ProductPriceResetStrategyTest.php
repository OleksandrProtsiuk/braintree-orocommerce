<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\ImportExport\Strategy;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Oro\Bundle\ImportExportBundle\Context\StepExecutionProxyContext;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\ImportExport\Strategy\ProductPriceResetStrategy;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * @dbIsolation
 */
class ProductPriceResetStrategyTest extends WebTestCase
{
    /**
     * @var ProductPriceResetStrategy
     */
    protected $strategy;

    /**
     * @var StepExecutionProxyContext
     */
    protected $context;

    /**
     * @var StepExecution
     */
    protected $stepExecution;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
            ]
        );

        $container = $this->getContainer();

        $this->strategy = new ProductPriceResetStrategy(
            $container->get('event_dispatcher'),
            $container->get('oro_importexport.strategy.import.helper'),
            $container->get('oro_entity.helper.field_helper'),
            $container->get('oro_importexport.field.database_helper'),
            $container->get('oro_entity.entity_class_name_provider'),
            $container->get('translator'),
            $container->get('oro_importexport.strategy.new_entities_helper'),
            $container->get('oro_entity.doctrine_helper')
        );
        
        $this->stepExecution = new StepExecution('step', new JobExecution());
        $this->context = new StepExecutionProxyContext($this->stepExecution);
        $this->strategy->setImportExportContext($this->context);
        $this->strategy->setEntityName(
            $container->getParameter('oro_pricing.entity.product_price.class')
        );
    }

    protected function tearDown()
    {
        unset($this->strategy, $this->context, $this->stepExecution);
    }

    public function testProcessResetPriceListProductPrices()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        $productPrice = $this->getPriceByReference('product_price.1');

        $actualPrices = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroPricingBundle:ProductPrice')
            ->findBy(['priceList' => $priceList->getId()]);
        $this->assertCount(8, $actualPrices);

        $expectedPricesIds = [
            $productPrice->getId(),
            $this->getPriceByReference('product_price.2')->getId(),
            $this->getPriceByReference('product_price.3')->getId(),
        ];

        $actualPricesIds = array_map(
            function (ProductPrice $price) {
                return $price->getId();
            },
            $actualPrices
        );

        foreach ($expectedPricesIds as $price) {
            $this->assertContains($price, $actualPricesIds);
        }

        $this->strategy->process($productPrice);

        $this->assertEmpty(
            $this->getContainer()
                ->get('doctrine')
                ->getRepository('OroPricingBundle:ProductPrice')
                ->findBy(['priceList' => $priceList->getId()])
        );

        // do not clear twice
        $newProductPrice = $this->createProductPrice();
        $this->getContainer()->get('doctrine')->getManager()->persist($newProductPrice);
        $this->getContainer()->get('doctrine')->getManager()->flush();

        $this->strategy->process($productPrice);

        $this->assertEquals(
            [$newProductPrice],
            $this->getContainer()
                ->get('doctrine')
                ->getRepository('OroPricingBundle:ProductPrice')
                ->findBy(['priceList' => $priceList->getId()])
        );
    }

    /**
     * @param string $reference
     * @return ProductPrice
     */
    protected function getPriceByReference($reference)
    {
        return $this->getReference($reference);
    }

    /**
     * @return ProductPrice
     */
    protected function createProductPrice()
    {
        /** @var Product $product */
        $product = $this->getReference('product-1');

        /** @var PriceList $priceList */
        $priceList = $this->getReference('price_list_1');

        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.liter');

        $price = Price::create(1.2, 'USD');
        $newProductPrice = new ProductPrice();
        $newProductPrice
            ->setPriceList($priceList)
            ->setProduct($product)
            ->setQuantity(1)
            ->setPrice($price)
            ->setUnit($unit);

        return $newProductPrice;
    }
}
