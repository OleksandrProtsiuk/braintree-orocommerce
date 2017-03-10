<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Placeholder;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ShoppingListBundle\Placeholder\PlaceholderFilter;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testUserCanCreateLineItem()
    {
        /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject $securityFacade */
        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_shopping_list_frontend_update');

        $placeholderFilter = new PlaceholderFilter($securityFacade);
        $placeholderFilter->userCanCreateLineItem();
    }
}
