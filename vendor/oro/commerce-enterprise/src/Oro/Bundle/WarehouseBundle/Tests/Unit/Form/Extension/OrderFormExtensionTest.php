<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\WarehouseBundle\Entity\Helper\WarehouseCounter;
use Oro\Bundle\WarehouseBundle\Form\Extension\OrderFormExtension;

class OrderFormExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WarehouseCounter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $warehouseCounter;

    /**
     * @var OrderFormExtension
     */
    protected $orderFormExtension;

    protected function setUp()
    {
        $this->warehouseCounter = $this->getMockBuilder(WarehouseCounter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderFormExtension = new OrderFormExtension($this->warehouseCounter);
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder * */
        $builder = $this->getMockBuilder(FormBuilderInterface::class)->getMock();
        $this->warehouseCounter->expects($this->once())
            ->method('areMoreWarehouses')
            ->willReturn(true);
        $builder->expects($this->never())
            ->method('remove')
            ->willReturnCallback(function ($name) {
                $this->assertEquals('warehouse', $name);
            });

        $this->orderFormExtension->buildForm($builder, []);
    }

    public function testBuildFormDoesNotAddWarehouseField()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder * */
        $builder = $this->getMockBuilder(FormBuilderInterface::class)->getMock();
        $this->warehouseCounter->expects($this->once())
            ->method('areMoreWarehouses')
            ->willReturn(false);
        $builder->expects($this->once())
            ->method('remove');

        $this->orderFormExtension->buildForm($builder, []);
    }
}
