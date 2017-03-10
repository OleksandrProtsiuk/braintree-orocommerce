<?php

namespace Oro\Bundle\TaxBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Migrations\TaxEntitiesFactory;

class LoadTaxTableRatesDemoData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var TaxEntitiesFactory
     */
    private $entitiesFactory;

    public function __construct()
    {
        $this->entitiesFactory = new TaxEntitiesFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData',
            'Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerDemoData',
            'Oro\Bundle\CustomerBundle\Migrations\Data\Demo\ORM\LoadCustomerGroupDemoData',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $data = require __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'tax_table_rates.php';

        $this->loadCustomerTaxCodes($manager, $data['customer_tax_codes']);
        $this->loadProductTaxCodes($manager, $data['product_tax_codes']);
        $this->loadTaxes($manager, $data['taxes']);
        $this->loadTaxJurisdictions($manager, $data['tax_jurisdictions']);
        $this->loadTaxRules($manager, $data['tax_rules']);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param array $customerTaxCodes
     *
     * @return $this
     */
    private function loadCustomerTaxCodes(ObjectManager $manager, $customerTaxCodes)
    {
        foreach ($customerTaxCodes as $code => $data) {
            $taxCode = $this->entitiesFactory->createCustomerTaxCode($code, $data['description'], $manager, $this);
            if (isset($data['customers'])) {
                foreach ($data['customers'] as $customerName) {
                    $customer = $manager->getRepository('OroCustomerBundle:Customer')->findOneByName($customerName);
                    if (null !== $customer) {
                        $taxCode->addCustomer($customer);
                    }
                }
            }
            if (isset($data['customer_groups'])) {
                foreach ($data['customer_groups'] as $groupName) {
                    $group = $manager->getRepository('OroCustomerBundle:CustomerGroup')->findOneByName($groupName);
                    if (null !== $group) {
                        $taxCode->addCustomerGroup($group);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param ObjectManager $manager
     * @param array $productTaxCodes
     *
     * @return $this
     */
    private function loadProductTaxCodes(ObjectManager $manager, $productTaxCodes)
    {
        foreach ($productTaxCodes as $code => $data) {
            $taxCode = $this->entitiesFactory->createProductTaxCode($code, $data['description'], $manager, $this);
            foreach ($data['products'] as $sku) {
                $product = $manager->getRepository('OroProductBundle:Product')->findOneBySku($sku);
                if ($product) {
                    $taxCode->addProduct($product);
                }
            }
        }

        return $this;
    }

    /**
     * @param ObjectManager $manager
     * @param array $taxes
     *
     * @return $this
     */
    private function loadTaxes(ObjectManager $manager, $taxes)
    {
        foreach ($taxes as $code => $data) {
            $this->entitiesFactory->createTax($code, $data['rate'], $data['description'], $manager, $this);
        }

        return $this;
    }

    /**
     * @param ObjectManager $manager
     * @param array $taxJurisdictions
     *
     * @return $this
     */
    private function loadTaxJurisdictions(ObjectManager $manager, $taxJurisdictions)
    {
        foreach ($taxJurisdictions as $code => $data) {
            $country = $this->getCountryByIso2Code($manager, $data['country']);
            $region = $this->getRegionByCountryAndCode($manager, $country, $data['state']);

            $this->entitiesFactory->createTaxJurisdiction(
                $code,
                $data['description'],
                $country,
                $region,
                $data['zip_codes'],
                $manager,
                $this
            );
        }

        return $this;
    }

    /**
     * @param ObjectManager $manager
     * @param array $taxRules
     *
     * @return $this
     */
    private function loadTaxRules(ObjectManager $manager, $taxRules)
    {
        foreach ($taxRules as $rule) {
            /** @var \Oro\Bundle\TaxBundle\Entity\CustomerTaxCode $customerTaxCode */
            $customerTaxCode = $this->getReference($rule['customer_tax_code']);

            /** @var \Oro\Bundle\TaxBundle\Entity\ProductTaxCode $productTaxCode */
            $productTaxCode = $this->getReference($rule['product_tax_code']);

            /** @var \Oro\Bundle\TaxBundle\Entity\TaxJurisdiction $taxJurisdiction */
            $taxJurisdiction = $this->getReference($rule['tax_jurisdiction']);

            /** @var \Oro\Bundle\TaxBundle\Entity\Tax $tax */
            $tax = $this->getReference($rule['tax']);

            $this->entitiesFactory->createTaxRule(
                $customerTaxCode,
                $productTaxCode,
                $taxJurisdiction,
                $tax,
                isset($rule['description']) ? $rule['description'] : '',
                $manager
            );
        }

        return $this;
    }

    //region Helper methods for the methods that the corresponding repositories do not have
    /**
     * @param ObjectManager $manager
     * @param string $iso2Code
     *
     * @return Country|null
     */
    private function getCountryByIso2Code(ObjectManager $manager, $iso2Code)
    {
        return $manager->getRepository('OroAddressBundle:Country')->findOneBy(['iso2Code' => $iso2Code]);
    }

    /**
     * @param ObjectManager $manager
     * @param Country $country
     * @param string $code
     *
     * @return Region|null
     */
    private function getRegionByCountryAndCode(ObjectManager $manager, Country $country, $code)
    {
        return $manager->getRepository('OroAddressBundle:Region')->findOneBy(['country' => $country, 'code' => $code]);
    }
    //endregion
}
