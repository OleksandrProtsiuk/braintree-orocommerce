<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;

class LoadInventoryLevels extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductUnitPrecisions::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createInventoryLevel($manager, 'product_unit_precision.product-1.liter', 10);
        $this->createInventoryLevel($manager, 'product_unit_precision.product-1.bottle', 99);
        $this->createInventoryLevel($manager, 'product_unit_precision.product-2.liter', 12.345);
        $this->createInventoryLevel($manager, 'product_unit_precision.product-2.bottle', 98);
        $this->createInventoryLevel($manager, 'product_unit_precision.product-2.box', 42);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $precisionReference
     * @param int|float $quantity
     */
    protected function createInventoryLevel(ObjectManager $manager, $precisionReference, $quantity)
    {
        /** @var ProductUnitPrecision $precision */
        $precision = $this->getReference($precisionReference);

        $level = new InventoryLevel();
        $level
            ->setProductUnitPrecision($precision)
            ->setQuantity($quantity);

        $manager->persist($level);
        $this->addReference(
            sprintf('inventory_level.%s', $precisionReference),
            $level
        );
    }
}
