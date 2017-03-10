<?php

namespace Oro\Bundle\WarehouseBundle\Tests\Unit\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\WarehouseBundle\DependencyInjection\Compiler\OverrideInventoryTemplateFixtureCompilerPass;
use Oro\Bundle\WarehouseBundle\ImportExport\TemplateFixture\WarehouseInventoryLevelFixture;

class OverrideInventoryTemplateFixtureCompilerPassTest extends \PHPUnit_Framework_TestCase
{
    public function testProcessSkip()
    {
        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerMock */
        $containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $containerMock->expects($this->exactly(1))
            ->method('hasDefinition')
            ->with(
                $this->equalTo('oro_inventory.importexport.template_fixture.inventory_level')
            )
            ->will($this->returnValue(false));

        $containerMock
            ->expects($this->never())
            ->method('getDefinition');

        $compilerPass = new OverrideInventoryTemplateFixtureCompilerPass();
        $compilerPass->process($containerMock);
    }

    public function testProcess()
    {
        $definition = $this->getMockBuilder('Symfony\Component\DependencyInjection\Definition')
            ->setMethods([])
            ->getMock();

        $definition
            ->expects($this->exactly(1))
            ->method('setClass')
            ->with(
                $this->equalTo(WarehouseInventoryLevelFixture::class)
            )
            ->will($this->returnSelf());

        /** @var ContainerBuilder|\PHPUnit_Framework_MockObject_MockObject $containerMock */
        $containerMock = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->getMock();

        $containerMock->expects($this->exactly(1))
            ->method('hasDefinition')
            ->with(
                $this->equalTo('oro_inventory.importexport.template_fixture.inventory_level')
            )
            ->will($this->returnValue(true));

        $containerMock->expects($this->exactly(1))
            ->method('getDefinition')
            ->with(
                $this->equalTo('oro_inventory.importexport.template_fixture.inventory_level')
            )
            ->will($this->returnValue($definition));

        $compilerPass = new OverrideInventoryTemplateFixtureCompilerPass();
        $compilerPass->process($containerMock);
    }
}
