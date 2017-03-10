<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolation
 */
class AjaxProductControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([
            LoadFrontendProductData::class
        ]);
    }

    /**
     * @dataProvider productNamesBySkusDataProvider
     * @param array $skus
     * @param array $expectedData
     */
    public function testProductNamesBySkus(array $skus, array $expectedData)
    {
        $this->client->request(
            'POST',
            $this->getUrl('oro_product_frontend_ajax_names_by_skus'),
            ['skus' => $skus]
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);
        $this->assertEquals($expectedData, $data);
    }

    public function productNamesBySkusDataProvider()
    {
        return [
            'restricted' => [
                'skus'         => [
                    'not a sku',
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                ],
                'expectedData' => [
                    LoadProductData::PRODUCT_1 => ['name' => 'product-1.names.default'],
                    LoadProductData::PRODUCT_2 => ['name' => 'product-2.names.default'],
                    LoadProductData::PRODUCT_3 => ['name' => 'product-3.names.default'],
                ],
            ],
            'allowed'    => [
                'skus'         => [
                    'not a sku',
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
                'expectedData' => [
                    LoadProductData::PRODUCT_1 => ['name' => 'product-1.names.default'],
                    LoadProductData::PRODUCT_2 => ['name' => 'product-2.names.default'],
                ],
            ],
        ];
    }
}
