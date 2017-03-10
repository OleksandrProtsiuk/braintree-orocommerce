<?php

namespace Oro\Bundle\ElasticSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\ElasticSearchBundle\RequestBuilder\OrderRequestBuilder;
use Oro\Bundle\SearchBundle\Query\Query;

class OrderRequestBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param string $field
     * @param string $direction
     * @param array $request
     * @dataProvider buildDataProvider
     */
    public function testBuild($field, $direction, array $request)
    {
        $query = new Query();
        if ($field) {
            if ($direction) {
                $query->getCriteria()->orderBy([$field => $direction]);
            } else {
                $query->getCriteria()->orderBy([$field]);
            }
        }

        $builder = new OrderRequestBuilder();

        $this->assertEquals($request, $builder->build($query, []));
    }

    /**
     * @return array
     */
    public function buildDataProvider()
    {
        return [
            'asc' => [
                'field' => 'name',
                'direction' => Query::ORDER_ASC,
                'request' => [
                    'body' => ['sort' => ['name' => ['order' => Query::ORDER_ASC]]]
                ],
            ],
            'desc' => [
                'field' => 'name',
                'direction' => Query::ORDER_DESC,
                'request' => [
                    'body' => ['sort' => ['name' => ['order' => Query::ORDER_DESC]]]
                ],
            ],
            'empty' => [
                'field' => null,
                'direction' => null,
                'request' => [],
            ],
        ];
    }
}
