<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Layout\DataProvider;

use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Layout\DataProvider\AddressProvider;

class AddressProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var FragmentHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $fragmentHandler;

    /** @var AddressProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->router = $this->createMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
        $this->fragmentHandler = $this->getMockBuilder('Symfony\Component\HttpKernel\Fragment\FragmentHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new AddressProvider($this->router, $this->fragmentHandler);
    }

    public function testGetComponentOptions()
    {
        $this->provider->setEntityClass('Oro\Bundle\CustomerBundle\Entity\Customer');
        $this->provider->setListRouteName('oro_api_customer_frontend_get_customer_addresses');
        $this->provider->setCreateRouteName('oro_customer_frontend_customer_address_create');
        $this->provider->setUpdateRouteName('oro_customer_frontend_customer_address_update');

        /** @var Customer $entity */
        $entity = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Customer', ['id' => 40]);

        $this->router->expects($this->exactly(2))
            ->method('generate')
            ->willReturnMap([
                [
                    'oro_api_customer_frontend_get_customer_addresses',
                    ['entityId' => $entity->getId()],
                    UrlGeneratorInterface::ABSOLUTE_PATH,
                    '/address/list/test/url'
                ],
                [
                    'oro_customer_frontend_customer_address_create',
                    ['entityId' => $entity->getId()],
                    UrlGeneratorInterface::ABSOLUTE_PATH,
                    '/address/create/test/url'
                ]
            ]);

        $this->fragmentHandler->expects($this->once())
            ->method('render')
            ->with('/address/list/test/url')
            ->willReturn(['data']);

        $data = $this->provider->getComponentOptions($entity);

        $this->assertEquals(
            [
                'entityId' => 40,
                'addressListUrl' => '/address/list/test/url',
                'addressCreateUrl' => '/address/create/test/url',
                'addressUpdateRouteName' => 'oro_customer_frontend_customer_address_update',
                'currentAddresses' => ['data'],
            ],
            $data
        );
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testGetComponentOptionsWithoutRouteName()
    {
        /** @var Customer $entity */
        $entity = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Customer');

        $this->provider->setListRouteName('');
        $this->provider->getComponentOptions($entity);
    }

    /**
     * @expectedException \UnexpectedValueException
     */
    public function testGetComponentOptionsWithWrongEntityClass()
    {
        /** @var Customer $entity */
        $entity = $this->getEntity('Oro\Bundle\CustomerBundle\Entity\Customer');

        $this->provider->setEntityClass('Oro\Bundle\CustomerBundle\Entity\CustomerUser');
        $this->provider->getComponentOptions($entity);
    }
}
