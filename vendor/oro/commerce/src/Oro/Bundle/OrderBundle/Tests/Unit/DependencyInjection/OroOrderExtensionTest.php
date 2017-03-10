<?php
namespace Oro\Bundle\OrderBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\OrderBundle\DependencyInjection\OroOrderExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroOrderExtensionTest extends ExtensionTestCase
{
    /**
     * @var array
     */
    protected $extensionConfigs = [];

    public function testLoad()
    {
        $this->loadExtension(new OroOrderExtension());

        $expectedParameters = [
            'oro_order.entity.order.class',
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [
            'oro_order.form.type.order',
            'oro_order.form.type.order_shipping_tracking',
            'oro_order.form.type.order_shipping_tracking_collection',
            'oro_order.form.type.select_switch_input',
            'oro_order.handler.order_shipping_tracking',
            'oro_order.twig.order_shipping',
            'oro_order.formatter.shipping_tracking',
            'oro_order.factory.shipping_context',
            'oro_order.event_listener.order.possible_shipping_methods'
        ];
        $this->assertDefinitionsLoaded($expectedDefinitions);

        $this->assertExtensionConfigsLoaded([OroOrderExtension::ALIAS]);
    }

    /**
     * Test Get Alias
     */
    public function testGetAlias()
    {
        $extension = new OroOrderExtension();
        static::assertEquals(OroOrderExtension::ALIAS, $extension->getAlias());
    }
}
