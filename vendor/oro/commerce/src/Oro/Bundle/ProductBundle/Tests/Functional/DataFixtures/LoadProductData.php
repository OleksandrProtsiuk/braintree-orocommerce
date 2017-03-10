<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;

class LoadProductData extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    const PRODUCT_1 = 'product-1';
    const PRODUCT_2 = 'product-2';
    const PRODUCT_3 = 'product-3';
    const PRODUCT_4 = 'product-4';
    const PRODUCT_5 = 'product-5';
    const PRODUCT_6 = 'product-6';
    const PRODUCT_7 = 'product-7';
    const PRODUCT_8 = 'product-8';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData',
            'Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityManager $manager */
        $user = $this->getFirstUser($manager);
        $businessUnit = $user->getOwner();
        $organization = $user->getOrganization();

        $inventoryStatusClassName = ExtendHelper::buildEnumValueClassName('prod_inventory_status');
        /** @var AbstractEnumValue[] $enumInventoryStatuses */
        $enumInventoryStatuses = $manager->getRepository($inventoryStatusClassName)->findAll();

        $inventoryStatuses = [];
        foreach ($enumInventoryStatuses as $inventoryStatus) {
            $inventoryStatuses[$inventoryStatus->getId()] = $inventoryStatus;
        }

        $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'product_fixture.yml';

        $data = Yaml::parse(file_get_contents($filePath));
        $defaultAttributeFamily = $this->getDefaultAttributeFamily($manager);

        foreach ($data as $item) {
            $unit = $this->getReference('product_unit.milliliter');

            $unitPrecision = new ProductUnitPrecision();
            $unitPrecision->setUnit($unit)
                ->setPrecision((int)$item['primaryUnitPrecision']['precision'])
                ->setConversionRate(1)
                ->setSell(true);

            $product = new Product();
            $product
                ->setSku($item['productCode'])
                ->setOwner($businessUnit)
                ->setOrganization($organization)
                ->setAttributeFamily($defaultAttributeFamily)
                ->setInventoryStatus($inventoryStatuses[$item['inventoryStatus']])
                ->setStatus($item['status'])
                ->setPrimaryUnitPrecision($unitPrecision)
                ->setType($item['type']);

            if (!empty($item['names'])) {
                foreach ($item['names'] as $name) {
                    $product->addName($this->createValue($name));
                }
            }

            if (!empty($item['descriptions'])) {
                foreach ($item['descriptions'] as $name) {
                    $product->addDescription($this->createValue($name));
                }
            }

            if (!empty($item['shortDescriptions'])) {
                foreach ($item['shortDescriptions'] as $name) {
                    $product->addShortDescription($this->createValue($name));
                }
            }

            $manager->persist($product);
            $this->addReference($product->getSku(), $product);
            $this->addReference(
                sprintf('product_unit_precision.%s', implode('.', [$product->getSku(), $unit->getCode()])),
                $unitPrecision
            );
        }

        $manager->flush();
    }

    /**
     * @param array $name
     * @return LocalizedFallbackValue
     */
    protected function createValue(array $name)
    {
        $value = new LocalizedFallbackValue();
        if (array_key_exists('localization', $name)) {
            /** @var Localization $localization */
            $localization = $this->getReference($name['localization']);
            $value->setLocalization($localization);
        }
        if (array_key_exists('fallback', $name)) {
            $value->setFallback($name['fallback']);
        }
        if (array_key_exists('string', $name)) {
            $value->setString($name['string']);
        }
        if (array_key_exists('text', $name)) {
            $value->setText($name['text']);
        }
        $this->setReference($name['reference'], $value);

        return $value;
    }

    /**
     * @param EntityManager $manager
     * @return User
     * @throws \LogicException
     */
    protected function getUser(EntityManager $manager)
    {
        $user = $manager->getRepository('OroUserBundle:User')
            ->createQueryBuilder('user')
            ->orderBy('user.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();

        if (!$user) {
            throw new \LogicException('There are no users in system');
        }

        return $user;
    }

    /**
     * @param ObjectManager $manager
     * @return AttributeFamily|null
     */
    protected function getDefaultAttributeFamily(ObjectManager $manager)
    {
        $familyRepository = $manager->getRepository(AttributeFamily::class);

        return $familyRepository->findOneBy(['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE]);
    }
}
