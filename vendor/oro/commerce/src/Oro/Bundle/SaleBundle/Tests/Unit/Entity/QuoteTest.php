<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuoteTest extends AbstractTest
{
    public function testProperties()
    {
        $now = new \DateTime('now');
        $properties = [
            ['id', '123'],
            ['qid', 'QID-123456'],
            ['owner', new User()],
            ['customerUser', new CustomerUser()],
            ['shippingAddress', new QuoteAddress()],
            ['customer', new Customer()],
            ['organization', new Organization()],
            ['poNumber', '1'],
            ['validUntil', $now, false],
            ['createdAt', $now, false],
            ['updatedAt', $now, false],
            ['shipUntil', $now, false],
            ['expired', true],
            ['locked', true],
            ['shippingMethodLocked', true],
            ['allowUnlistedShippingMethod', true],
            ['request', new Request()],
            ['website', new Website()],
            ['currency', 'UAH'],
            ['estimatedShippingCostAmount', 15],
            ['overriddenShippingCostAmount', 15],
        ];

        static::assertPropertyAccessors(new Quote(), $properties);

        static::assertPropertyCollections(new Quote(), [
            ['quoteProducts', new QuoteProduct()],
            ['assignedUsers', new User()],
            ['assignedCustomerUsers', new CustomerUser()],
        ]);
    }

    public function testToString()
    {
        $id = '123';
        $quote = new Quote();
        $class = new \ReflectionClass($quote);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($quote, $id);

        $this->assertEquals($id, (string)$quote);
    }

    public function testGetEmail()
    {
        $quote = new Quote();
        $this->assertEmpty($quote->getEmail());
        $customerUser = new CustomerUser();
        $customerUser->setEmail('test');
        $quote->setCustomerUser($customerUser);
        $this->assertEquals('test', $quote->getEmail());
    }

    public function testPrePersist()
    {
        $quote = new Quote();

        $this->assertNull($quote->getCreatedAt());
        $this->assertNull($quote->getUpdatedAt());

        $quote->prePersist();

        $this->assertInstanceOf('\DateTime', $quote->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $quote->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $quote = new Quote();

        $this->assertNull($quote->getUpdatedAt());

        $quote->preUpdate();

        $this->assertInstanceOf('\DateTime', $quote->getUpdatedAt());
    }

    public function testAddQuoteProduct()
    {
        $quote          = new Quote();
        $quoteProduct   = new QuoteProduct();

        $this->assertNull($quoteProduct->getQuote());

        $quote->addQuoteProduct($quoteProduct);

        $this->assertEquals($quote, $quoteProduct->getQuote());
    }

    /**
     * @dataProvider hasOfferVariantsDataProvider
     *
     * @param Quote $quote
     * @param bool $expected
     */
    public function testHasOfferVariants(Quote $quote, $expected)
    {
        $this->assertEquals($expected, $quote->hasOfferVariants());
    }

    /**
     * @return array
     */
    public function hasOfferVariantsDataProvider()
    {
        return [
            [$this->createQuote(0, 0), false],
            [$this->createQuote(1, 0), false],
            [$this->createQuote(1, 1), false],
            [$this->createQuote(2, 0), false],
            [$this->createQuote(2, 1), false],
            [$this->createQuote(1, 2), true],
            [$this->createQuote(1, 1, true), true],
        ];
    }

    /**
     * @dataProvider isAcceptableDataProvider
     *
     * @param bool $expired
     * @param \DateTime|null $validUntil
     * @param bool $expected
     */
    public function testIsAcceptable($expired, $validUntil, $expected)
    {
        $quote = new Quote();
        $quote
            ->setExpired($expired)
            ->setValidUntil($validUntil);
        $this->assertEquals($expected, $quote->isAcceptable());
    }

    /**
     * @return array
     */
    public function isAcceptableDataProvider()
    {
        return [
            [false, null, true],
            [false, new \DateTime('+1 day'), true],
            [false, new \DateTime('-1 day'), false],
            [true, null, false],
            [true, new \DateTime('+1 day'), false],
            [true, new \DateTime('-1 day'), false],
        ];
    }

    /**
     * @param int $quoteProductCount
     * @param int $quoteProductOfferCount
     * @param bool|false $allowIncrements
     * @return Quote
     */
    protected function createQuote($quoteProductCount, $quoteProductOfferCount, $allowIncrements = false)
    {
        $quote = new Quote();

        for ($i = 0; $i < $quoteProductCount; $i++) {
            $quoteProduct = new QuoteProduct();

            for ($j = 0; $j < $quoteProductOfferCount; $j++) {
                $quoteProductOffer = new QuoteProductOffer();
                $quoteProductOffer->setAllowIncrements($allowIncrements);

                $quoteProduct->addQuoteProductOffer($quoteProductOffer);
            }

            $quote->addQuoteProduct($quoteProduct);
        }

        return $quote;
    }
}
