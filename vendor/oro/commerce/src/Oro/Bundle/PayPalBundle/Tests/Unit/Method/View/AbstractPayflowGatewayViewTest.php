<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\View;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PayPalBundle\Form\Type\CreditCardType;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfigInterface;
use Oro\Bundle\PayPalBundle\Method\View\PayflowGatewayView;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormFactoryInterface;

abstract class AbstractPayflowGatewayViewTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var PaymentMethodViewInterface|PayflowGatewayView */
    protected $methodView;

    /** @var  PaymentTransactionProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTransactionProvider;

    /** @var PayflowGatewayConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentConfig;

    protected function setUp()
    {
        $this->formFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactoryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentTransactionProvider = $this
            ->getMockBuilder('Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentConfig =
            $this->createMock('Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfigInterface');

        $this->methodView = $this->getMethodView();
    }

    /**
     * @return PaymentMethodViewInterface
     */
    abstract protected function getMethodView();

    /**
     * @return string
     */
    abstract protected function getZeroAmountKey();

    /**
     * @return string
     */
    abstract protected function getRequireCvvEntryKey();

    /**
     * @return string
     */
    abstract protected function getAuthForRequiredAmountKey();

    /**
     * @return string
     */
    abstract protected function getAllowedCCTypesKey();

    public function testGetOptionsWithoutZeroAmount()
    {
        $formView = $this->createMock('Symfony\Component\Form\FormView');
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->once())->method('createView')->willReturn($formView);

        $zeroAmountAuthEnabled = false;
        $requireCvvEntryEnabled = true;
        $allowedCCTypes = ['visa', 'mastercard'];

        $formOptions = [
            'zeroAmountAuthorizationEnabled' => $zeroAmountAuthEnabled,
            'requireCvvEntryEnabled' => $requireCvvEntryEnabled,
        ];

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME, null, $formOptions)
            ->willReturn($form);

        $this->paymentConfig->expects($this->once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn($zeroAmountAuthEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('isRequireCvvEntryEnabled')
            ->willReturn($requireCvvEntryEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('getAllowedCreditCards')
            ->willReturn($allowedCCTypes);

        $this->paymentTransactionProvider->expects($this->never())->method('getActiveValidatePaymentTransaction');

        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);

        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponentOptions' => [
                    'allowedCreditCards' => $allowedCCTypes,
                ]
            ],
            $this->methodView->getOptions($context)
        );
    }

    public function testGetOptionsWithZeroAmountWithoutTransaction()
    {
        $formView = $this->createMock('Symfony\Component\Form\FormView');
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->once())->method('createView')->willReturn($formView);

        $zeroAmountAuthEnabled = true;
        $requireCvvEntryEnabled = true;
        $allowedCCTypes = ['visa', 'mastercard'];

        $formOptions = [
            'zeroAmountAuthorizationEnabled' => $zeroAmountAuthEnabled,
            'requireCvvEntryEnabled' => $requireCvvEntryEnabled,
        ];

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME, null, $formOptions)
            ->willReturn($form);

        $this->paymentConfig->expects($this->once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn($zeroAmountAuthEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('isRequireCvvEntryEnabled')
            ->willReturn($requireCvvEntryEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('getAllowedCreditCards')
            ->willReturn($allowedCCTypes);

        $this->paymentTransactionProvider->expects($this->once())->method('getActiveValidatePaymentTransaction')
            ->willReturn(null);

        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);

        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponentOptions' => [
                    'allowedCreditCards' => $allowedCCTypes,
                ]
            ],
            $this->methodView->getOptions($context)
        );
    }

    public function testGetOptions()
    {
        $formView = $this->createMock('Symfony\Component\Form\FormView');
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->once())->method('createView')->willReturn($formView);

        $zeroAmountAuthEnabled = true;
        $requireCvvEntryEnabled = true;
        $allowedCCTypes = ['visa', 'mastercard'];

        $formOptions = [
            'zeroAmountAuthorizationEnabled' => $zeroAmountAuthEnabled,
            'requireCvvEntryEnabled' => $requireCvvEntryEnabled,
        ];

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME, null, $formOptions)
            ->willReturn($form);

        $this->paymentConfig->expects($this->once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn($zeroAmountAuthEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('isRequireCvvEntryEnabled')
            ->willReturn($requireCvvEntryEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('getAllowedCreditCards')
            ->willReturn($allowedCCTypes);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setResponse(['ACCT' => '1111']);

        $this->paymentTransactionProvider->expects($this->once())->method('getActiveValidatePaymentTransaction')
            ->willReturn($paymentTransaction);

        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponent' => 'oropaypal/js/app/components/authorized-credit-card-component',
                'creditCardComponentOptions' => [
                    'acct' => '1111',
                    'saveForLaterUse' => false,
                    'allowedCreditCards' => $allowedCCTypes,
                ],
            ],
            $this->methodView->getOptions($context)
        );
    }

    public function testGetOptionsWithLaterUse()
    {
        $formView = $this->createMock('Symfony\Component\Form\FormView');
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->once())->method('createView')->willReturn($formView);

        $zeroAmountAuthEnabled = true;
        $requireCvvEntryEnabled = true;
        $allowedCCTypes = ['visa', 'mastercard'];

        $formOptions = [
            'zeroAmountAuthorizationEnabled' => $zeroAmountAuthEnabled,
            'requireCvvEntryEnabled' => $requireCvvEntryEnabled,
        ];

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME, null, $formOptions)
            ->willReturn($form);

        $this->paymentConfig->expects($this->once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn($zeroAmountAuthEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('isRequireCvvEntryEnabled')
            ->willReturn($requireCvvEntryEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('getAllowedCreditCards')
            ->willReturn($allowedCCTypes);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setResponse(['ACCT' => '1111'])
            ->setTransactionOptions(['saveForLaterUse' => true]);

        $this->paymentTransactionProvider->expects($this->once())->method('getActiveValidatePaymentTransaction')
            ->willReturn($paymentTransaction);

        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponent' => 'oropaypal/js/app/components/authorized-credit-card-component',
                'creditCardComponentOptions' => [
                    'acct' => '1111',
                    'saveForLaterUse' => true,
                    'allowedCreditCards' => $allowedCCTypes,
                ],
            ],
            $this->methodView->getOptions($context)
        );
    }

    public function testGetOptionsWithAuthForRequiredAmount()
    {
        $formView = $this->createMock('Symfony\Component\Form\FormView');
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->once())->method('createView')->willReturn($formView);

        $zeroAmountAuthEnabled = true;
        $requireCvvEntryEnabled = false;
        $allowedCCTypes = ['visa', 'mastercard'];

        $formOptions = [
            'zeroAmountAuthorizationEnabled' => $zeroAmountAuthEnabled,
            'requireCvvEntryEnabled' => $requireCvvEntryEnabled,
        ];

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME, null, $formOptions)
            ->willReturn($form);

        $this->paymentConfig->expects($this->once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn($zeroAmountAuthEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('isRequireCvvEntryEnabled')
            ->willReturn($requireCvvEntryEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('getAllowedCreditCards')
            ->willReturn($allowedCCTypes);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setResponse(['ACCT' => '1111']);

        $this->paymentTransactionProvider->expects($this->once())->method('getActiveValidatePaymentTransaction')
            ->willReturn($paymentTransaction);

        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponent' => 'oropaypal/js/app/components/authorized-credit-card-component',
                'creditCardComponentOptions' => [
                    'acct' => '1111',
                    'saveForLaterUse' => false,
                    'allowedCreditCards' => $allowedCCTypes,
                ],
            ],
            $this->methodView->getOptions($context)
        );
    }

    public function testGetBlock()
    {
        $this->assertEquals('_payment_methods_credit_card_widget', $this->methodView->getBlock());
    }

    public function testGetAllowedCreditCards()
    {
        $allowedCards = ['visa', 'mastercard'];

        $this->paymentConfig->expects($this->once())
            ->method('getAllowedCreditCards')
            ->willReturn($allowedCards);

        $this->assertEquals($allowedCards, $this->methodView->getAllowedCreditCards());
    }
}
