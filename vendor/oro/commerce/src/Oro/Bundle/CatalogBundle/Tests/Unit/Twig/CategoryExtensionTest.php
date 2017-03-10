<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\CatalogBundle\JsTree\CategoryTreeHandler;
use Oro\Bundle\CatalogBundle\Twig\CategoryExtension;

class CategoryExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CategoryTreeHandler
     */
    protected $categoryTreeHandler;

    /**
     * @var CategoryExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    protected $container;

    public function setUp()
    {
        $this->container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');

        $this->categoryTreeHandler = $this->getMockBuilder('Oro\Bundle\CatalogBundle\JsTree\CategoryTreeHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new CategoryExtension();
        $this->extension->setContainer($this->container);
    }

    public function testGetName()
    {
        $this->assertEquals(CategoryExtension::NAME, $this->extension->getName());
    }

    /**
     * Test related method
     */
    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        $this->assertCount(1, $functions);

        /** @var \Twig_SimpleFunction $function */
        $function = $functions[0];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('oro_category_list', $function->getName());
        $this->assertInstanceOf(\Closure::class, $function->getCallable());
    }

    public function testGetCategoryList()
    {
        $tree = [
            [
                'id' => 1,
                'parent' => '#',
                'text' => 'Master catalog',
                'state' => [
                    'opened' => true,
                ],
            ],
        ];

        $this->categoryTreeHandler->expects($this->once())
            ->method('createTree')
            ->will($this->returnValue($tree));

        $functions = $this->extension->getFunctions();
        $function = reset($functions);
        $this->container->expects($this->once())->method('get')->willReturn($this->categoryTreeHandler);
        $callable = $function->getCallable();
        $callable->bindTo($this->extension);
        $result = call_user_func_array($callable, []);

        $this->assertEquals($tree, $result);
    }

    public function testGetCategoryListWithRootLabel()
    {
        $tree = [
            [
                'id' => 1,
                'parent' => '#',
                'text' => 'oro.catalog.frontend.category.master_category.label',
                'state' => [
                    'opened' => true,
                ],
            ],
        ];

        $this->categoryTreeHandler->expects($this->once())
            ->method('createTree')
            ->will($this->returnValue($tree));

        $functions = $this->extension->getFunctions();
        $function = reset($functions);
        $this->container->expects($this->once())->method('get')->willReturn($this->categoryTreeHandler);

        $callable = $function->getCallable();
        $callable->bindTo($this->extension);
        $result = call_user_func_array($callable, ['oro.catalog.frontend.category.master_category.label']);

        $this->assertEquals($tree, $result);
    }
}
