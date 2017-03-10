<?php

namespace Oro\Bundle\MoneyOrderBundle\Tests\Unit\Method\View;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\MoneyOrderBundle\DependencyInjection\Configuration;
use Oro\Bundle\MoneyOrderBundle\DependencyInjection\OroMoneyOrderExtension;
use Oro\Bundle\MoneyOrderBundle\Method\Config\MoneyOrderConfig;
use Oro\Bundle\MoneyOrderBundle\Method\MoneyOrder;
use Oro\Bundle\MoneyOrderBundle\Method\View\MoneyOrderView;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;

class MoneyOrderViewTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var MoneyOrderView */
    protected $methodView;

    /** @var MoneyOrderConfig */
    protected $config;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->config = new MoneyOrderConfig($this->configManager);
        $this->methodView = new MoneyOrderView($this->config);
    }

    public function testGetOptions()
    {
        $data = ['pay_to' => 'Pay To', 'send_to' => 'Send To'];

        $this->setConfig($this->at(0), Configuration::MONEY_ORDER_PAY_TO_KEY, $data['pay_to']);
        $this->setConfig($this->at(1), Configuration::MONEY_ORDER_SEND_TO_KEY, $data['send_to']);

        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $this->assertEquals($data, $this->methodView->getOptions($context));
    }

    public function testGetBlock()
    {
        $this->assertEquals('_payment_methods_money_order_widget', $this->methodView->getBlock());
    }

    public function testGetPaymentMethodType()
    {
        $this->assertEquals(MoneyOrder::TYPE, $this->methodView->getPaymentMethodType());
    }

    public function testGetLabel()
    {
        $this->setConfig($this->once(), Configuration::MONEY_ORDER_LABEL_KEY, 'testValue');
        $this->assertEquals('testValue', $this->methodView->getLabel());
    }

    public function testShortGetLabel()
    {
        $this->setConfig($this->once(), Configuration::MONEY_ORDER_SHORT_LABEL_KEY, 'testValue');
        $this->assertEquals('testValue', $this->methodView->getShortLabel());
    }

    /**
     * @param mixed $expects
     * @param string $key
     * @param mixed $value
     */
    protected function setConfig($expects, $key, $value)
    {
        $this->configManager->expects($expects)
            ->method('get')
            ->with($this->getConfigKey($key))
            ->willReturn($value);
    }

    /**
     * @param string $key
     * @return string
     */
    protected function getConfigKey($key)
    {
        return OroMoneyOrderExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $key;
    }
}
