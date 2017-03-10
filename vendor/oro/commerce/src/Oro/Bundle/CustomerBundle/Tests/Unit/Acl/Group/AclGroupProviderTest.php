<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Acl\Group;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CustomerBundle\Acl\Group\AclGroupProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

class AclGroupProviderTest extends \PHPUnit_Framework_TestCase
{
    const LOCAL_LEVEL = 'Oro\Bundle\CustomerBundle\Entity\Customer';
    const BASIC_LEVEL = 'Oro\Bundle\CustomerBundle\Entity\CustomerUser';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ContainerInterface
     */
    protected $container;

    /**
     * @var AclGroupProvider
     */
    protected $provider;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->expects($this->any())
            ->method('get')
            ->with('oro_security.security_facade')
            ->willReturn($this->securityFacade);

        $this->provider = new AclGroupProvider();
        $this->provider->setContainer($this->container);
    }

    protected function tearDown()
    {
        unset($this->securityFacade, $this->container, $this->provider);
    }

    /**
     * @dataProvider supportsDataProvider
     *
     * @param object|null $user
     * @param bool $expectedResult
     */
    public function testSupports($user, $expectedResult)
    {
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->assertEquals($expectedResult, $this->provider->supports());
    }

    /**
     * @return array
     */
    public function supportsDataProvider()
    {
        return [
            'incorrect user object' => [
                'securityFacadeUser' => new \stdClass(),
                'expectedResult' => false
            ],
            'customer user' => [
                'securityFacadeUser' => new CustomerUser(),
                'expectedResult' => true
            ],
            'user is not logged in' => [
                'securityFacadeUser' => null,
                'expectedResult' => true
            ],
        ];
    }

    public function testGetGroup()
    {
        $this->assertEquals(CustomerUser::SECURITY_GROUP, $this->provider->getGroup());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage ContainerInterface not injected
     */
    public function testWithoutContainer()
    {
        (new AclGroupProvider())->supports();
    }
}
