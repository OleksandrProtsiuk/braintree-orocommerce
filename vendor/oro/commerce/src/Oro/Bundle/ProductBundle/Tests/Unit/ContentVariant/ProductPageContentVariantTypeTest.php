<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant;

use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductPageVariantType;
use Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant\Stub\ContentVariantStub;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductPageContentVariantTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    private $securityFacade;

    /**
     * @var ProductPageContentVariantType
     */
    private $type;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new ProductPageContentVariantType($this->securityFacade, $this->getPropertyAccessor());
    }

    public function testGetName()
    {
        $this->assertEquals('product_page', $this->type->getName());
    }

    public function testGetTitle()
    {
        $this->assertEquals('oro.product.content_variant.product_page.label', $this->type->getTitle());
    }

    public function testGetFormType()
    {
        $this->assertEquals(ProductPageVariantType::class, $this->type->getFormType());
    }

    public function testIsAllowed()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_product_view')
            ->willReturn(true);
        $this->assertTrue($this->type->isAllowed());
    }

    public function testGetRouteData()
    {
        /** @var ContentVariantStub **/
        $contentVariant = new ContentVariantStub();
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 42]);
        $contentVariant->setProductPageProduct($product);

        $this->assertEquals(
            new RouteData('oro_product_frontend_product_view', ['id' => 42]),
            $this->type->getRouteData($contentVariant)
        );
    }
}
