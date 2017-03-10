<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Unit\Api\Processor;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Get\GetProcessorOrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfigExtra;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Util\CriteriaConnector;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\WarehouseBundle\Api\Processor\BuildSingleInventoryLevelWithWarehouseQuery;
use Oro\Bundle\WarehouseBundle\Entity\Helper\WarehouseCounter;

class BuildSingleInventoryLevelWithWarehouseQueryTest extends GetProcessorOrmRelatedTestCase
{
    /** @var UpdateContext */
    protected $context;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $criteriaConnector;

    /** @var WarehouseCounter|\PHPUnit_Framework_MockObject_MockObject */
    protected $warehouseCounter;

    /** @var BuildSingleInventoryLevelWithWarehouseQuery */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->context = new UpdateContext($this->configProvider, $this->metadataProvider);
        $this->context->setVersion(self::TEST_VERSION);
        $this->context->getRequestType()->add(self::TEST_REQUEST_TYPE);
        $this->context->setConfigExtras(
            [
                new EntityDefinitionConfigExtra($this->context->getAction()),
                new FiltersConfigExtra()
            ]
        );

        $this->criteriaConnector = $this->getMockBuilder(CriteriaConnector::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->warehouseCounter = $this->getMockBuilder(WarehouseCounter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new BuildSingleInventoryLevelWithWarehouseQuery(
            $this->doctrineHelper,
            $this->criteriaConnector,
            $this->warehouseCounter
        );
    }

    public function testProcessWhenCriteriaObjectDoesNotExist()
    {
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasQuery());
    }

    public function testProcessForNotManageableEntity()
    {
        $className = 'Test\Class';

        $this->notManageableClassNames = [$className];

        $this->context->setClassName($className);
        $this->processor->process($this->context);

        $this->assertNull($this->context->getQuery());
    }

    public function testProductNotExistsInRequest()
    {
        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasQuery());
    }

    public function testMoreWarehousesAndNoProduct()
    {
        $this->context->setRequestData([]);

        $resolver = $this->getMockBuilder(EntityClassResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $criteria = new Criteria($resolver);

        $this->context->setCriteria($criteria);

        $this->processor->process($this->context);

        $this->assertFalse($this->context->hasQuery());
    }

    public function testProcessBuildQueryWithMultipleWarehouses()
    {
        $this->warehouseCounter->expects($this->once())
            ->method('areMoreWarehouses')
            ->willReturn(true);

        $requestData = [
            'sku' => 'product-1',
            'warehouse' => 1,
            'unit' => 'liter'
        ];
        $this->context->setRequestData($requestData);

        $resolver = $this->getMockBuilder(EntityClassResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $criteria = new Criteria($resolver);

        $this->criteriaConnector->expects($this->once())
            ->method('applyCriteria');

        $this->context->setCriteria($criteria);
        $this->context->setClassName(InventoryLevel::class);

        $this->processor->process($this->context);

        $this->assertTrue($this->context->hasQuery());

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->context->getQuery();

        foreach ($requestData as $parameter => $value) {
            $this->assertEquals($queryBuilder->getParameter($parameter)->getValue(), $value);
        }
    }

    public function testProcessBuildQueryWithOneWarehouses()
    {
        $this->warehouseCounter->expects($this->once())
            ->method('areMoreWarehouses')
            ->willReturn(false);

        $requestData = [
            'sku' => 'product-1',
            'unit' => 'liter'
        ];
        $this->context->setRequestData($requestData);

        $resolver = $this->getMockBuilder(EntityClassResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $criteria = new Criteria($resolver);

        $this->criteriaConnector->expects($this->once())
            ->method('applyCriteria');

        $this->context->setCriteria($criteria);
        $this->context->setClassName(InventoryLevel::class);

        $this->processor->process($this->context);

        $this->assertTrue($this->context->hasQuery());

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->context->getQuery();
        foreach ($requestData as $parameter => $value) {
            $this->assertEquals($queryBuilder->getParameter($parameter)->getValue(), $value);
        }
    }
}
