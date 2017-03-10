<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\View;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\View\PayflowExpressCheckoutView;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

class PayflowExpressCheckoutViewTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var PaymentMethodViewInterface */
    protected $methodView;

    /** @var PayflowExpressCheckoutConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentConfig;

    protected function setUp()
    {
        $this->paymentConfig =
            $this->createMock('Oro\Bundle\PayPalBundle\Method\Config\PayflowExpressCheckoutConfigInterface');

        $this->methodView = $this->createMethodView();
    }

    protected function tearDown()
    {
        unset($this->paymentConfig, $this->methodView);
    }

    public function testGetOptions()
    {
        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $this->assertEmpty($this->methodView->getOptions($context));
    }

    public function testGetBlock()
    {
        $this->assertEquals('_payment_methods_payflow_express_checkout_widget', $this->methodView->getBlock());
    }

    public function testGetPaymentMethodType()
    {
        $this->assertEquals('payflow_express_checkout', $this->methodView->getPaymentMethodType());
    }

    public function testGetLabel()
    {
        $label = 'Label';

        $this->paymentConfig->expects($this->once())
            ->method('getLabel')
            ->willReturn($label);

        $this->assertSame($label, $this->methodView->getLabel());
    }

    public function testGetShortLabel()
    {
        $shortLAbel = 'Short Label';

        $this->paymentConfig->expects($this->once())
            ->method('getShortLabel')
            ->willReturn($shortLAbel);

        $this->assertSame($shortLAbel, $this->methodView->getShortLabel());
    }

    /**
     * @return PaymentMethodViewInterface
     */
    protected function createMethodView()
    {
        return new PayflowExpressCheckoutView($this->paymentConfig);
    }
}
