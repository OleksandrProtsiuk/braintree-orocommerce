<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class AjaxProductPriceControllerTest extends AbstractAjaxProductPriceControllerTest
{
    /**
     * @var string
     */
    protected $pricesByCustomerActionUrl = 'oro_pricing_price_by_customer';

    /**
     * @var string
     */
    protected $matchingPriceActionUrl = 'oro_pricing_matching_price';

    protected function setUp()
    {
        $this->initClient(
            [],
            array_merge(
                $this->generateBasicAuthHeader(),
                [
                    'HTTP_X-CSRF-Header' => 1,
                    'X-Requested-With' => 'XMLHttpRequest'
                ]
            )
        );

        $this->loadFixtures(
            [
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices',
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceListRelations',
                'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices',
            ]
        );
    }

    public function testUpdate()
    {
        $this->loadFixtures([
            'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
        ]);
        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReference('product_price.3');
        /** @var ProductUnit $unit */
        $unit = $this->getReference('product_unit.bottle');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_product_price_update_widget',
                [
                    'id' => $productPrice->getId(),
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form(
            [
                'oro_pricing_price_list_product_price[quantity]' => 10,
                'oro_pricing_price_list_product_price[unit]' => $unit->getCode(),
                'oro_pricing_price_list_product_price[price][value]' => 20,
                'oro_pricing_price_list_product_price[price][currency]' => 'USD'
            ]
        );

        $this->assertSaved($form);
    }

    public function testUpdateDuplicateEntry()
    {
        $this->loadFixtures([
            'Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
        ]);
        /** @var ProductPrice $productPrice */
        $productPrice = $this->getReference('product_price.3');
        $productPriceEUR = $this->getReference('product_price.11');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_product_price_update_widget',
                [
                    'id' => $productPriceEUR->getId(),
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test-uuid'
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $form = $crawler->selectButton('Save')->form(
            [
                'oro_pricing_price_list_product_price[quantity]' => $productPrice->getQuantity(),
                'oro_pricing_price_list_product_price[unit]' => $productPrice->getUnit()->getCode(),
                'oro_pricing_price_list_product_price[price][value]' => $productPrice->getPrice()->getValue(),
                'oro_pricing_price_list_product_price[price][currency]' => $productPrice->getPrice()->getCurrency(),
            ]
        );

        $this->assertSubmitError($form, 'oro.pricing.validators.product_price.unique_entity.message');
    }

    /**
     * @param Form $form
     * @param string $message
     */
    protected function assertSubmitError(Form $form, $message)
    {
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertRegExp('/"savedId":\s*null/i', $html);
        $error = $this->getContainer()->get('translator')
            ->trans($message, [], 'validators');
        $this->assertContains($error, $html);
    }

    /**
     * @param Form $form
     */
    protected function assertSaved(Form $form)
    {
        $this->client->followRedirects(true);
        $crawler = $this->client->submit($form);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $html = $crawler->html();

        $this->assertRegExp('/"savedId":\s*\d+/i', $html);
    }

    /**
     * @return array
     */
    public function getProductPricesByCustomerActionDataProvider()
    {
        return [
            'with customer and website' => [
                'product' => 'product-1',
                'expected' => [
                    ['price' => '1.1000', 'currency' => 'USD', 'quantity' => 1, 'unit' => 'bottle'],
                    ['price' => '1.2000', 'currency' => 'USD', 'quantity' => 10, 'unit' => 'liter'],
                ],
                'currency' => null,
                'customer' => 'customer.level_1.1',
                'website' => LoadWebsiteData::WEBSITE1
            ],
            'default, without customer and website' => [
                'product' => 'product-1',
                'expected' => [
                    ['price' => '12.2000', 'currency' => 'EUR', 'quantity' => 1, 'unit' => 'bottle'],
                    ['price' => '13.1000', 'currency' => 'USD', 'quantity' => 1, 'unit' => 'bottle'],
                    ['price' => '12.2000', 'currency' => 'EUR', 'quantity' => 11, 'unit' => 'bottle'],
                    ['price' => '10.0000', 'currency' => 'USD', 'quantity' => 1, 'unit' => 'liter'],
                    ['price' => '12.2000', 'currency' => 'USD', 'quantity' => 10, 'unit' => 'liter'],
                ]
            ],

        ];
    }
}
