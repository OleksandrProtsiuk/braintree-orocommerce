<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Grid\Frontend;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\DataGridBundle\Extension\Sorter\AbstractSorterExtension;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadFrontendCategoryProductData;

/**
 * @dbIsolation
 */
class ProductSearchGridTest extends FrontendWebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW),
            true
        );
        $this->getContainer()->get('request_stack')->push(Request::create(''));
        $this->setCurrentWebsite('default');

        $this->loadFixtures([LoadFrontendCategoryProductData::class]);

        // load image filters for grid images
        $this->getContainer()->get('oro_layout.loader.image_filter')->load();
    }

    public function testSorters()
    {
        $products = $this->getDatagridData(
            'frontend-product-search-grid',
            [],
            ['[sku]' => AbstractSorterExtension::DIRECTION_ASC]
        );
        $this->checkSorting($products, 'sku', AbstractSorterExtension::DIRECTION_ASC);

        $products = $this->getDatagridData(
            'frontend-product-search-grid',
            [],
            ['[sku]' => AbstractSorterExtension::DIRECTION_DESC,]
        );
        $this->checkSorting($products, 'sku', AbstractSorterExtension::DIRECTION_DESC);

        $products = $this->getDatagridData(
            'frontend-product-search-grid',
            [],
            ['[name]' => AbstractSorterExtension::DIRECTION_ASC,]
        );
        $this->checkSorting($products, 'name', AbstractSorterExtension::DIRECTION_ASC);

        $products = $this->getDatagridData(
            'frontend-product-search-grid',
            [],
            ['[name]' => AbstractSorterExtension::DIRECTION_DESC,]
        );
        $this->checkSorting($products, 'name', AbstractSorterExtension::DIRECTION_DESC);
    }

    /**
     * @param array  $data
     * @param string $column
     * @param string $orderDirection
     */
    protected function checkSorting(array $data, $column, $orderDirection)
    {
        $this->assertNotEmpty($data);

        foreach ($data as $row) {
            $actualValue = $row[$column];

            if (isset($lastValue)) {
                if ($orderDirection === AbstractSorterExtension::DIRECTION_DESC) {
                    $this->assertGreaterThanOrEqual($actualValue, $lastValue);
                } elseif ($orderDirection === AbstractSorterExtension::DIRECTION_ASC) {
                    $this->assertLessThanOrEqual($actualValue, $lastValue);
                }
            }
            $lastValue = $actualValue;
        }
    }

    public function testFilters()
    {
        $data = $this->getDatagridData(
            'frontend-product-search-grid'
        );

        $indexes = array_keys($data);
        $lastRow = $data[end($indexes)];

        $filteredData = $this->getDatagridData(
            'frontend-product-search-grid',
            [
                '[sku][value]' => $lastRow['sku'],
                '[sku][type]' => '1',
                '[name][value]' => $lastRow['name'],
                '[name][type]' => '1',
            ]
        );

        // can't use strict comparing because of different search engines
        $this->assertGreaterThanOrEqual(1, count($filteredData));

        $filteredRow = $this->getRowBySku($filteredData, $lastRow['sku']);

        $this->assertNotNull($filteredRow);
        $this->assertEquals($lastRow['sku'], $filteredRow['sku']);
        $this->assertEquals($lastRow['name'], $filteredRow['name']);
    }

    public function testAllTextFilter()
    {
        $data = $this->getDatagridData(
            'frontend-product-search-grid'
        );

        $indexes = array_keys($data);
        $lastRow = $data[end($indexes)];
        $allTextValue = substr($lastRow['shortDescription'], 0, 12);

        $filteredData = $this->getDatagridData(
            'frontend-product-search-grid',
            [
                '[all_text][value]' => $allTextValue,
                '[all_text][type]' => '1'
            ]
        );

        // can't use strict comparing because of different search engines
        $this->assertGreaterThanOrEqual(1, count($filteredData));

        $filteredRow = $this->getRowBySku($filteredData, $lastRow['sku']);
        $this->assertStringStartsWith($allTextValue, $filteredRow['shortDescription']);
    }

    public function testPagination()
    {
        $first2Rows = $this->getDatagridData('frontend-product-search-grid', [], [], [
            '[_page]'     => 1,
            '[_per_page]' => 2,
        ]);
        $first2Skus = array_column($first2Rows, 'sku');

        $second2Rows = $this->getDatagridData('frontend-product-search-grid', [], [], [
            '[_page]'     => 2,
            '[_per_page]' => 2,
        ]);
        $second2Skus = array_column($second2Rows, 'sku');

        $first4Rows = $this->getDatagridData('frontend-product-search-grid', [], [], [
            '[_page]'     => 1,
            '[_per_page]' => 4,
        ]);
        $first4Skus = array_column($first4Rows, 'sku');

        $this->assertEquals($first4Skus, array_merge($first2Skus, $second2Skus));
    }

    /**
     * @param string $gridName
     * @param array  $filters
     * @param array  $sorters
     * @param array  $pager
     * @return array
     */
    protected function getDatagridData($gridName, array $filters = [], array $sorters = [], array $pager = [])
    {
        $gridParameters = ['gridName' => $gridName];

        $result = [];
        foreach ($filters as $filter => $value) {
            $result[$gridName . '[_filter]' . $filter] = $value;
        }
        foreach ($sorters as $sorter => $value) {
            $result[$gridName . '[_sort_by]' . $sorter] = $value;
        }
        foreach ($pager as $param => $value) {
            $result[$gridName . '[_pager]' . $param] = $value;
        }

        $response = $this->client->requestFrontendGrid($gridParameters, $result);
        $this->assertJsonResponseStatusCodeEquals($response, 200);

        return json_decode($response->getContent(), true)['data'];
    }

    /**
     * @param array $rows
     * @param string $sku
     * @return array|null
     */
    protected function getRowBySku(array $rows, $sku)
    {
        foreach ($rows as $row) {
            if ($row['sku'] === $sku) {
                return $row;
            }
        }

        return null;
    }
}
