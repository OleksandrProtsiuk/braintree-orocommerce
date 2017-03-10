<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Condition;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Model\Condition\OrderLineItemsHasCount;

class OrderLineItemsHasCountTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OrderLineItemsHasCount
     */
    protected $condition;

    /**
     * @var CheckoutLineItemsManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $manager;

    public function setUp()
    {
        $this->manager = $this
            ->getMockBuilder('Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager')
            ->disableOriginalConstructor()->getMock();

        $this->condition = new OrderLineItemsHasCount($this->manager);
    }

    public function testGetName()
    {
        $this->assertEquals(OrderLineItemsHasCount::NAME, $this->condition->getName());
    }

    /**
     * @dataProvider initializeDataProvider
     * @param array $options
     * @param $message
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     */
    public function testInitializeExceptions(array $options, $message)
    {
        $this->expectExceptionMessage($message);
        $this->condition->initialize($options);
    }

    /**
     * @return array
     */
    public function initializeDataProvider()
    {
        return [
            [
                'options' => [1, 2, 3],
                'exceptionMessage' => 'Options must have 1 elements, but 3 given.',
            ],
            [
                'options' => [],
                'exceptionMessage' => 'Options must have 1 elements, but 0 given.',
            ],
            [
                'options' => [1 => 1],
                'exceptionMessage' => 'Option "entity" must be set.',
            ]
        ];
    }

    /**
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
     * @expectedExceptionMessage Entity must implement Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface
     */
    public function testEvaluateException()
    {
        $context = [];
        $this->condition->initialize(['entity' => []]);
        $this->condition->evaluate($context);
    }

    /**
     * @dataProvider evaluateDataProvider
     * @param array $lineItems
     * @param $expectedResult
     */
    public function testEvaluate(array $lineItems, $expectedResult)
    {
        /** @var CheckoutInterface|\PHPUnit_Framework_MockObject_MockObject $checkout */
        $checkout = $this->createMock('Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface');
        $context = [];
        $this->condition->initialize(['entity' => $checkout]);
        $this->manager->expects($this->once())
            ->method('getData')
            ->willReturn($lineItems);
        $this->assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    /**
     * @return array
     */
    public function evaluateDataProvider()
    {
        return [
            [
                'lineItems' => [
                    $this->createMock('Oro\Bundle\OrderBundle\Entity\OrderLineItem'),
                ],
                'expectedResult' => true,
            ],
            [
                'lineItems' => [],
                'expectedResult' => false,
            ]
        ];
    }
}
