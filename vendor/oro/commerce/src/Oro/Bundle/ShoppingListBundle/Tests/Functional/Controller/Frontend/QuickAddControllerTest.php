<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend\QuickAddControllerTest as BaseControllerTest;

/**
 * @dbIsolation
 */
class QuickAddControllerTest extends BaseControllerTest
{
    /**
     * @return array
     */
    public function validationResultProvider()
    {
        return [
            'add to shopping list' => [
                'processorName' => 'oro_shopping_list_quick_add_processor',
                'routerName' => 'oro_product_frontend_quick_add',
                'routerParams' => [],
                'expectedMessage' => '3 products were added'
            ],
        ];
    }
}
