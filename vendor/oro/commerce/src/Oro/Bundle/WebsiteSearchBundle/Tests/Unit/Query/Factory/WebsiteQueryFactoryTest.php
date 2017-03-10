<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Query\Factory;

use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebsiteSearchBundle\Query\Factory\WebsiteQueryFactory;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchQuery;

class WebsiteQueryFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var QueryFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $queryFactory;

    /** @var EngineV2Interface|\PHPUnit_Framework_MockObject_MockBuilder */
    protected $engine;


    public function setUp()
    {
        $this->queryFactory    = $this->createMock(QueryFactoryInterface::class);
        $this->engine          = $this->createMock(EngineV2Interface::class);
    }

    public function testCreate()
    {
        $configForWebsiteSearch = [
            'search_index' => 'website',
            'query' => [
                'select' => [
                    'text.sku'
                ],
                'from' => [
                    'product'
                ]
            ]
        ];

        $configForBackendSearch = [
            'search_index' => null
        ];

        $factory = new WebsiteQueryFactory($this->engine);

        $result = $factory->create($configForBackendSearch);

        $this->assertInstanceOf(SearchQueryInterface::class, $result);

        $result = $factory->create($configForWebsiteSearch);

        $this->assertInstanceOf(WebsiteSearchQuery::class, $result);
    }
}
