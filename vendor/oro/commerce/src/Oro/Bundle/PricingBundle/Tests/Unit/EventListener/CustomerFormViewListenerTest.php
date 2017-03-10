<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Oro\Component\Testing\Unit\FormViewListenerTestCase;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\EventListener\CustomerFormViewListener;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;

class CustomerFormViewListenerTest extends FormViewListenerTestCase
{
    /**
     * @var WebsiteProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->websiteProvider = $this->getMockBuilder(WebsiteProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $website = new Website();
        $this->websiteProvider->method('getWebsites')->willReturn([$website]);
    }

    protected function tearDown()
    {
        unset($this->doctrineHelper, $this->translator);
    }

    public function testOnViewNoRequest()
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');

        $listener = $this->getListener($requestStack);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityReference');

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $env */
        $env = $this->createMock('\Twig_Environment');
        $event = $this->createEvent($env);
        $listener->onCustomerView($event);
    }

    public function testOnCustomerView()
    {
        $customerId = 1;
        $customer = new Customer();
        $websites = $this->websiteProvider->getWebsites();

        $priceListToCustomer1 = new PriceListToCustomer();
        $priceListToCustomer1->setCustomer($customer);
        $priceListToCustomer1->setPriority(3);

        $priceListToCustomer2 = clone $priceListToCustomer1;
        $priceListsToCustomer = [$priceListToCustomer1, $priceListToCustomer2];

        $templateHtml = 'template_html';

        $fallbackEntity = new PriceListCustomerFallback();
        $fallbackEntity->setCustomer($customer);
        $fallbackEntity->setFallback(PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY);
        $fallbackEntity->setWebsite(current($websites));

        $request = new Request(['id' => $customerId]);
        $requestStack = $this->getRequestStack($request);

        /** @var CustomerFormViewListener $listener */
        $listener = $this->getListener($requestStack);

        $this->setRepositoryExpectationsForCustomer($customer, $priceListsToCustomer, $fallbackEntity, $websites);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->createMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with(
                'OroPricingBundle:Customer:price_list_view.html.twig',
                [
                    'priceLists' => [
                        $priceListToCustomer1,
                        $priceListToCustomer2,
                    ],
                    'fallback' => 'oro.pricing.fallback.current_customer_only.label'
                ]
            )
            ->willReturn($templateHtml);
        $event = $this->createEvent($environment);
        $listener->onCustomerView($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertEquals(
            [$templateHtml],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    public function testOnEntityEdit()
    {
        $formView = new FormView();
        $templateHtml = 'template_html';

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');

        /** @var CustomerFormViewListener $listener */
        $listener = $this->getListener($requestStack);

        /** @var \PHPUnit_Framework_MockObject_MockObject|\Twig_Environment $environment */
        $environment = $this->createMock('\Twig_Environment');
        $environment->expects($this->once())
            ->method('render')
            ->with('OroPricingBundle:Customer:price_list_update.html.twig', ['form' => $formView])
            ->willReturn($templateHtml);
        $event = $this->createEvent($environment, $formView);
        $listener->onEntityEdit($event);
        $scrollData = $event->getScrollData()->getData();

        $this->assertEquals(
            [$templateHtml],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    /**
     * @param array $scrollData
     * @param string $html
     */
    protected function assertScrollDataPriceBlock(array $scrollData, $html)
    {
        $this->assertEquals(
            'oro.pricing.productprice.entity_plural_label.trans',
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::TITLE]
        );

        $this->assertEquals(
            [$html],
            $scrollData[ScrollData::DATA_BLOCKS][1][ScrollData::SUB_BLOCKS][0][ScrollData::DATA]
        );
    }

    /**
     * @param \Twig_Environment $environment
     * @param FormView $formView
     * @return BeforeListRenderEvent
     */
    protected function createEvent(\Twig_Environment $environment, FormView $formView = null)
    {
        $defaultData = [
            ScrollData::DATA_BLOCKS => [
                [
                    ScrollData::SUB_BLOCKS => [
                        [
                            ScrollData::DATA => [],
                        ]
                    ]
                ]
            ]
        ];
        return new BeforeListRenderEvent($environment, new ScrollData($defaultData), $formView);
    }

    /**
     * @param RequestStack $requestStack
     * @return CustomerFormViewListener
     */
    protected function getListener(RequestStack $requestStack)
    {
        return new CustomerFormViewListener(
            $requestStack,
            $this->translator,
            $this->doctrineHelper,
            $this->websiteProvider
        );
    }

    /**
     * @param Customer $customer
     * @param PriceListToCustomer[] $priceListsToCustomer
     * @param PriceListCustomerFallback $fallbackEntity
     * @param Website[] $websites
     */
    protected function setRepositoryExpectationsForCustomer(
        Customer $customer,
        $priceListsToCustomer,
        PriceListCustomerFallback $fallbackEntity,
        array $websites
    ) {
        $priceToCustomerRepository = $this
            ->getMockBuilder('Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $priceToCustomerRepository->expects($this->once())
            ->method('findBy')
            ->with(['customer' => $customer, 'website' => $websites])
            ->willReturn($priceListsToCustomer);

        $fallbackRepository = $this
            ->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $fallbackRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['customer' => $customer, 'website' => $websites])
            ->willReturn($fallbackEntity);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->willReturn($customer);

        $this->doctrineHelper->expects($this->exactly(2))
            ->method('getEntityRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['OroPricingBundle:PriceListToCustomer', $priceToCustomerRepository],
                        ['OroPricingBundle:PriceListCustomerFallback', $fallbackRepository],
                    ]
                )
            );
    }

    /**
     * @param $request
     * @return \PHPUnit_Framework_MockObject_MockObject|RequestStack
     */
    protected function getRequestStack($request)
    {
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->once())->method('getCurrentRequest')->willReturn($request);
        return $requestStack;
    }
}
