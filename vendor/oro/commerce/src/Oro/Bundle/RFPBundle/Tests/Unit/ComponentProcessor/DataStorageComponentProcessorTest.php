<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\ComponentProcessor;

use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\ComponentProcessor\DataStorageComponentProcessor;
use Oro\Bundle\RFPBundle\Form\Extension\RequestDataStorageExtension;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DataStorageComponentProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    /**
     * @var ProductDataStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storage;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $session;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * @var RequestDataStorageExtension|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestDataStorageExtension;

    /**
     * @var DataStorageComponentProcessor
     */
    protected $processor;

    protected function setUp()
    {
        $this->router = $this->createMock(UrlGeneratorInterface::class);

        $this->storage = $this->getMockBuilder(ProductDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->requestDataStorageExtension = $this->getMockBuilder(RequestDataStorageExtension::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new DataStorageComponentProcessor(
            $this->router,
            $this->storage,
            $this->securityFacade,
            $this->session,
            $this->translator,
            $this->requestDataStorageExtension
        );
    }

    protected function tearDown()
    {
        unset(
            $this->router,
            $this->storage,
            $this->securityFacade,
            $this->session,
            $this->translator,
            $this->requestDataStorageExtension,
            $this->processor
        );
    }

    public function testProcessNotAllowedRFP()
    {
        $data = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [['productSku' => 'sku01']]];

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request **/
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var FlashBag|\PHPUnit_Framework_MockObject_MockObject $flashBag **/
        $flashBag = $this->getMockBuilder(FlashBag::class)
            ->disableOriginalConstructor()
            ->getMock();

        $flashBag->expects($this->once())->method('add');

        $this->requestDataStorageExtension->expects($this->once())->method('isAllowedRFP')->willReturn(false);
        $this->session->expects($this->once())->method('getFlashBag')->willReturn($flashBag);

        $this->assertNull($this->processor->process($data, $request));
    }

    public function testProcessAllowedRFP()
    {
        $data = [ProductDataStorage::ENTITY_ITEMS_DATA_KEY => [['productSku' => 'sku01']]];

        /** @var Request|\PHPUnit_Framework_MockObject_MockObject $request **/
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestDataStorageExtension->expects($this->once())->method('isAllowedRFP')->willReturn(true);

        $this->assertNull($this->processor->process($data, $request));
    }
}
