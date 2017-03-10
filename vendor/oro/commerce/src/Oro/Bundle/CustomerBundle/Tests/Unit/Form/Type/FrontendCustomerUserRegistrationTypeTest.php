<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Form\Type\FrontendCustomerUserRegistrationType;
use Oro\Bundle\CustomerBundle\Entity\Customer;

class FrontendCustomerUserRegistrationTypeTest extends FormIntegrationTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ConfigManager
     */
    protected $configManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|UserManager
     */
    protected $userManager;

    /**
     * @var FrontendCustomerUserRegistrationType
     */
    protected $formType;

    /**
     * @var Customer[]
     */
    protected static $customers = [];

    protected function setUp()
    {
        parent::setUp();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->userManager = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\UserManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new FrontendCustomerUserRegistrationType($this->configManager, $this->userManager);
        $this->formType->setDataClass('Oro\Bundle\CustomerBundle\Entity\CustomerUser');
    }

    protected function tearDown()
    {
        unset($this->configManager, $this->userManager, $this->formType);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider submitProvider
     *
     * @param CustomerUser $defaultData
     * @param array $submittedData
     * @param CustomerUser $expectedData
     * @param User $owner
     * @param boolean $isValid
     * @param array $options
     */
    public function testSubmit(
        $defaultData,
        array $submittedData,
        $expectedData,
        User $owner,
        $isValid,
        array $options = []
    ) {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with('oro_customer.default_customer_owner')
            ->willReturn(42);

        $repository = $this->assertUserRepositoryCall();
        $repository->expects($this->any())
            ->method('find')
            ->with(42)
            ->willReturn($owner);

        $form = $this->factory->create($this->formType, $defaultData, $options);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertEquals($isValid, $form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $entity = new CustomerUser();
        $owner = new User();

        $expectedEntity = new CustomerUser();
        $expectedEntity
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('johndoe@example.com')
            ->setOwner($owner)
            ->setPlainPassword('123456')
            ->createCustomer();

        $entity->setSalt($expectedEntity->getSalt());

        return [
            'new user' => [
                'defaultData' => $entity,
                'submittedData' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'johndoe@example.com',
                    'plainPassword' => [
                        'first' => '123456',
                        'second' => '123456'
                    ]
                ],
                'expectedData' => $expectedEntity,
                'owner' => $owner,
                'isValid' => false
            ],
            'new user with company name' => [
                'defaultData' => $entity,
                'submittedData' => [
                    'companyName' => 'Test Company',
                    'firstName' => 'John',
                    'lastName' => 'Doe',
                    'email' => 'johndoe@example.com',
                    'plainPassword' => [
                        'first' => '123456',
                        'second' => '123456'
                    ]
                ],
                'expectedData' => $expectedEntity,
                'owner' => $owner,
                'isValid' => true
            ]
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ObjectRepository
     */
    protected function assertUserRepositoryCall()
    {
        $repository = $this->getMockBuilder('Oro\Bundle\UserBundle\Entity\Repository\UserRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->userManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        return $repository;
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(FrontendCustomerUserRegistrationType::NAME, $this->formType->getName());
    }

    /**
     * @param CustomerUser $existingCustomerUser
     * @param string $property
     * @param mixed $value
     */
    protected function setPropertyValue(CustomerUser $existingCustomerUser, $property, $value)
    {
        $class = new \ReflectionClass($existingCustomerUser);
        $prop = $class->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($existingCustomerUser, $value);
    }
}
