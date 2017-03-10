<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Processor;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ShoppingListBundle\Processor\QuickAddProcessor;

class QuickAddProcessorTest extends AbstractQuickAddProcessorTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->processor = new QuickAddProcessor($this->handler, $this->registry, $this->messageGenerator);
        $this->processor->setProductClass('Oro\Bundle\ProductBundle\Entity\Product');
    }

    public function getProcessorName()
    {
        return QuickAddProcessor::NAME;
    }

    /**
     * @param array $data
     * @param Request $request
     * @param array $productIds
     * @param array $productQuantities
     * @param bool $failed
     * @dataProvider processDataProvider
     */
    public function testProcess(
        array $data,
        Request $request,
        array $productIds = [],
        array $productQuantities = [],
        $failed = false
    ) {
        $entitiesCount = count($data);

        $this->handler->expects($this->any())->method('getShoppingList')->will(
            $this->returnCallback(
                function ($shoppingListId) {
                    if (!$shoppingListId) {
                        return $this->getEntity('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList');
                    }

                    return $this->getEntity('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList', $shoppingListId);
                }
            )
        );

        $this->productRepository->expects($this->any())->method('getProductsIdsBySku')->willReturn($productIds);

        if ($failed) {
            $this->handler->expects($this->once())
                ->method('createForShoppingList')
                ->willThrowException(new AccessDeniedException());
        } else {
            $this->handler->expects($data ? $this->once() : $this->never())
                ->method('createForShoppingList')
                ->with(
                    $this->isInstanceOf('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList'),
                    array_values($productIds),
                    $productQuantities
                )
                ->willReturn($entitiesCount);
        }

        if ($failed) {
            $this->assertFlashMessage($request, true);
        } elseif ($entitiesCount) {
            $this->assertFlashMessage($request);
        }

        $this->processor->process($data, $request);
    }

    /**
     * @param Request $request
     * @param bool $isFailedMessage
     */
    protected function assertFlashMessage(Request $request, $isFailedMessage = false)
    {
        $message = 'test message';

        $this->messageGenerator->expects($this->once())
            ->method($isFailedMessage ? 'getFailedMessage' : 'getSuccessMessage')
            ->willReturn($message);

        $flashBag = $this->createMock('Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface');
        $flashBag->expects($this->once())
            ->method('add')
            ->with($isFailedMessage ? 'error' : 'success', $message)
            ->willReturn($flashBag);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Session $session */
        $session = $this->getMockBuilder('Symfony\Component\HttpFoundation\Session\Session')
            ->disableOriginalConstructor()
            ->getMock();
        $session->expects($this->once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $request->setSession($session);
    }

    /** @return array */
    public function processDataProvider()
    {
        return [
            'empty' => [[], new Request()],
            'new shopping list' => [
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku1', 'productQuantity' => 2],
                        ['productSku' => 'sku2', 'productQuantity' => 3],
                    ]
                ],
                new Request(),
                ['sku1' => 1, 'sku2' => 2],
                [1 => 2, 2 => 3],
            ],
            'shopping list with same products couple of times' => [
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku1', 'productQuantity' => 2],
                        ['productSku' => 'sku1', 'productQuantity' => 3],
                        ['productSku' => 'sku1', 'productQuantity' => 4],
                        ['productSku' => 'sku2', 'productQuantity' => 5],
                        ['productSku' => 'sku2', 'productQuantity' => 6],
                    ]
                ],
                new Request(),
                ['sku1' => 1, 'sku2' => 2],
                [1 => 9, 2 => 11],
            ],
            'shopping list with products skus in descending order' => [
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku1', 'productQuantity' => 11],
                        ['productSku' => 'sku2', 'productQuantity' => 12],
                    ]
                ],
                new Request(),
                ['sku2' => 2, 'sku1' => 1],
                [1 => 11, 2 => 12],
            ],
            'existing shopping list' => [
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku1', 'productQuantity' => 2],
                        ['productSku' => 'sku2', 'productQuantity' => 3],
                    ],
                ],
                new Request(['oro_product_quick_add' => ['additional' => 1]]),
                ['sku1' => 1, 'sku2' => 2],
                [1 => 2, 2 => 3],
            ],
            'ids sorting' => [
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku2', 'productQuantity' => 3],
                        ['productSku' => 'sku1', 'productQuantity' => 2],
                    ],
                ],
                new Request(['oro_product_quick_add' => ['additional' => 1]]),
                ['sku2' => 2, 'sku1' => 1],
                [1 => 2, 2 => 3],
            ],
            'process failed' => [
                [
                    ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [
                        ['productSku' => 'sku1', 'productQuantity' => 2],
                        ['productSku' => 'sku2', 'productQuantity' => 3],
                    ],
                ],
                new Request(),
                ['sku1' => 1, 'sku2' => 2],
                [1 => 2, 2 => 3],
                true
            ],
        ];
    }
}
