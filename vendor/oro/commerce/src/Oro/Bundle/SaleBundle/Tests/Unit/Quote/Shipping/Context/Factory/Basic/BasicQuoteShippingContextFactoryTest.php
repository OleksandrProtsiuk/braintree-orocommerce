<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Quote\Shipping\Context\Factory\Basic;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Quote\Calculable\CalculableQuoteInterface;
use Oro\Bundle\SaleBundle\Quote\Calculable\Factory\CalculableQuoteFactoryInterface;
use Oro\Bundle\SaleBundle\Quote\Shipping\Context\Factory\Basic\BasicQuoteShippingContextFactory;
use Oro\Bundle\SaleBundle\Quote\Shipping\LineItem\Converter\QuoteToShippingLineItemConverterInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\Factory\ShippingContextBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;

class BasicQuoteShippingContextFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BasicQuoteShippingContextFactory
     */
    private $basicQuoteShippingContextFactory;

    /**
     * @var ShippingContextBuilderFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingContextBuilderFactoryMock;

    /**
     * @var QuoteToShippingLineItemConverterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quoteToShippingLineItemConverterMock;

    /**
     * @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $totalProcessorProviderMock;

    /**
     * @var CalculableQuoteFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $calculableQuoteFactoryMock;

    public function setUp()
    {
        $this->shippingContextBuilderFactoryMock = $this
            ->getMockBuilder(ShippingContextBuilderFactoryInterface::class)
            ->getMock();

        $this->quoteToShippingLineItemConverterMock = $this
            ->getMockBuilder(QuoteToShippingLineItemConverterInterface::class)
            ->getMock();

        $this->totalProcessorProviderMock = $this
            ->getMockBuilder(TotalProcessorProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->calculableQuoteFactoryMock = $this
            ->getMockBuilder(CalculableQuoteFactoryInterface::class)
            ->getMock();

        $this->basicQuoteShippingContextFactory = new BasicQuoteShippingContextFactory(
            $this->shippingContextBuilderFactoryMock,
            $this->quoteToShippingLineItemConverterMock,
            $this->totalProcessorProviderMock,
            $this->calculableQuoteFactoryMock
        );
    }

    public function testCreate()
    {
        $quoteId = 5;
        $currency = 'USD';
        $amount = 20;
        $subTotal = Price::create($amount, $currency);

        $totalMock = $this->getTotalMock();
        $calculableQuoteMock = $this->getCalculableQuoteMock();
        $shippingAddressMock = $this->getShippingAddressMock();
        $shippingLineItems = new DoctrineShippingLineItemCollection(
            [
                $this->getShippingLineItemMock(),
            ]
        );
        $quoteMock = $this->getQuoteMock();
        $shippingContextMock = $this->getShippingContextMock();
        $builder = $this->getShippingContextBuilderMock();

        $this->calculableQuoteFactoryMock
            ->expects($this->once())
            ->method('createCalculableQuote')
            ->with($shippingLineItems)
            ->willReturn($calculableQuoteMock);

        $totalMock
            ->expects($this->once())
            ->method('getAmount')
            ->willReturn($amount);

        $totalMock
            ->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency);

        $this->totalProcessorProviderMock
            ->expects($this->once())
            ->method('getTotal')
            ->with($calculableQuoteMock)
            ->willReturn($totalMock);

        $this->quoteToShippingLineItemConverterMock
            ->expects($this->once())
            ->method('convertLineItems')
            ->with($quoteMock)
            ->willReturn($shippingLineItems);

        $quoteMock
            ->expects($this->once())
            ->method('getId')
            ->willReturn($quoteId);

        $quoteMock
            ->expects($this->exactly(2))
            ->method('getShippingAddress')
            ->willReturn($shippingAddressMock);

        $quoteMock
            ->expects($this->once())
            ->method('getCurrency')
            ->willReturn($currency);

        $builder
            ->expects($this->once())
            ->method('setShippingAddress')
            ->with($shippingAddressMock);

        $builder
            ->expects($this->once())
            ->method('getResult')
            ->willReturn($shippingContextMock);

        $builder
            ->expects($this->once())
            ->method('setLineItems')
            ->with($shippingLineItems);

        $this->shippingContextBuilderFactoryMock
            ->expects($this->once())
            ->method('createShippingContextBuilder')
            ->with($currency, $subTotal, $quoteMock, $quoteId)
            ->willReturn($builder);

        $actualContext = $this->basicQuoteShippingContextFactory->create($quoteMock);

        $this->assertEquals($shippingContextMock, $actualContext);
    }

    /**
     * @return Subtotal|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getTotalMock()
    {
        return $this
            ->getMockBuilder(Subtotal::class)
            ->getMock();
    }

    /**
     * @return CalculableQuoteInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getCalculableQuoteMock()
    {
        return $this
            ->getMockBuilder(CalculableQuoteInterface::class)
            ->getMock();
    }

    /**
     * @return ShippingContextBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getShippingContextBuilderMock()
    {
        return $this
            ->getMockBuilder(ShippingContextBuilderInterface::class)
            ->getMock();
    }

    /**
     * @return ShippingLineItemInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getShippingLineItemMock()
    {
        return $this
            ->getMockBuilder(ShippingLineItemInterface::class)
            ->getMock();
    }

    /**
     * @return QuoteAddress|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getShippingAddressMock()
    {
        return $this
            ->getMockBuilder(QuoteAddress::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return Quote|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getQuoteMock()
    {
        return $this
            ->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return ShippingContext|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getShippingContextMock()
    {
        return $this
            ->getMockBuilder(ShippingContext::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
