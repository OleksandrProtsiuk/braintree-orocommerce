<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestStatus;

class RequestTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testConstruct()
    {
        $request = new Request();

        $now = new \DateTime();

        $this->assertInstanceOf('DateTime', $request->getCreatedAt());
        $this->assertLessThanOrEqual($now, $request->getCreatedAt());

        $this->assertInstanceOf('DateTime', $request->getUpdatedAt());
        $this->assertLessThanOrEqual($now, $request->getUpdatedAt());
    }

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $date = new \DateTime();

        $properties = [
            ['id', 42],
            ['firstName', 'Grzegorz'],
            ['lastName', 'Brzeczyszczykiewicz'],
            ['email', 'john.dow@example.com'],
            ['phone', '(555)5555-555-55'],
            ['company', 'JohnDow Inc.'],
            ['role', 'cto'],
            ['note', 'test_request_notes'],
            ['status', new RequestStatus(), false],
            ['createdAt', $date, false],
            ['updatedAt', $date, false],
        ];

        $propertyRequest = new Request();

        $this->assertPropertyAccessors($propertyRequest, $properties);

        $this->assertPropertyCollections(
            $propertyRequest,
            [
                ['requestProducts', new RequestProduct()],
                ['assignedUsers', new User()],
                ['assignedCustomerUsers', new CustomerUser()],
            ]
        );
    }

    public function testPreUpdate()
    {
        $request = new Request();
        $request->preUpdate();

        $this->assertInstanceOf('DateTime', $request->getUpdatedAt());
        $this->assertLessThanOrEqual(new \DateTime(), $request->getUpdatedAt());
    }

    /**
     * Test setters getters
     */
    public function testOwnershipAccessors()
    {
        $properties = [
            ['customer', new Customer()],
            ['customerUser', new CustomerUser()],
            ['organization', new Organization()],
            ['owner', new User()]
        ];

        $this->assertPropertyAccessors(new Request(), $properties);
    }

    /**
     * Test toString
     */
    public function testToString()
    {
        $id = 42;
        $firstName = 'Grzegorz';
        $lastName  = 'Brzeczyszczykiewicz';

        $request = new Request();
        $request->setFirstName($firstName)
            ->setLastName($lastName);

        $reflectionProperty = new \ReflectionProperty(get_class($request), 'id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($request, $id);

        $this->assertEquals(sprintf('%s: %s %s', $id, $firstName, $lastName), (string)$request);
    }

    public function testGetIdentifier()
    {
        $poNumber = 'testNumber';
        $request = new Request();
        $request->setPoNumber($poNumber);

        $this->assertEquals($poNumber, $request->getIdentifier());
    }
}
