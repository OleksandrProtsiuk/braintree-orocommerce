<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Twig;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Twig\DeleteMessageTextExtension;
use Oro\Bundle\PaymentTermBundle\Twig\DeleteMessageTextGenerator;

class DeleteMessageTextExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var  DeleteMessageTextGenerator|\PHPUnit_Framework_MockObject_MockObject */
    protected $deleteMessageTextGenerator;

    /** @var  DeleteMessageTextExtension */
    protected $deleteMessageTextExtension;

    protected function setUp()
    {
        $this->deleteMessageTextGenerator =
            $this->getMockBuilder('\Oro\Bundle\PaymentTermBundle\Twig\DeleteMessageTextGenerator')
                ->disableOriginalConstructor()
                ->getMock();
        $this->deleteMessageTextExtension = new DeleteMessageTextExtension($this->deleteMessageTextGenerator);
    }

    protected function tearDown()
    {
        unset($this->deleteMessageTextGenerator);
    }

    public function testGetName()
    {
        $this->assertEquals(
            DeleteMessageTextExtension::DELETE_MESSAGE_TEXT_EXTENSION_NAME,
            $this->deleteMessageTextExtension->getName()
        );
    }

    public function testGetFunctions()
    {
        $functions = $this->deleteMessageTextExtension->getFunctions();
        $this->assertCount(2, $functions);

        /** @var \Twig_SimpleFunction $function */
        $function = $functions[0];
        $this->assertInstanceOf('\Twig_SimpleFunction', $function);
        $this->assertEquals('get_payment_term_delete_message', $function->getName());
        $this->assertEquals([$this->deleteMessageTextExtension, 'getDeleteMessageText'], $function->getCallable());

        /** @var \Twig_SimpleFunction $functionNext */
        $functionNext = $functions[1];
        $this->assertInstanceOf('\Twig_SimpleFunction', $functionNext);
        $this->assertEquals('get_payment_term_delete_message_datagrid', $functionNext->getName());
        $this->assertEquals(
            [
                $this->deleteMessageTextExtension,
                'getDeleteMessageDatagrid'
            ],
            $functionNext->getCallable()
        );
    }

    public function testGetDeleteMessageText()
    {
        $message = 'Delete message for payment term';
        $paymentTerm = new PaymentTerm();

        $this->deleteMessageTextGenerator->expects($this->once())
            ->method('getDeleteMessageText')
            ->with($paymentTerm)
            ->willReturn($message);

        $result = $this->deleteMessageTextExtension->getDeleteMessageText($paymentTerm);
        $this->assertEquals($message, $result);
    }

    public function testGetDeleteMessageDatagrid()
    {
        $message = 'Payment term delete message for datagrid';
        $paymentTermId = 1;

        $this->deleteMessageTextGenerator->expects($this->once())
            ->method('getDeleteMessageTextForDataGrid')
            ->with($paymentTermId)
            ->willReturn($message);

        $result = $this->deleteMessageTextExtension->getDeleteMessageDatagrid($paymentTermId);
        $this->assertEquals($message, $result);
    }
}
