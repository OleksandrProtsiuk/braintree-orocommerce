<?php

namespace Oro\Bundle\OrganizationProBundle\Tests\Unit\Event;

use Oro\Bundle\OrganizationBundle\Entity\Organization;

use Oro\Bundle\OrganizationProBundle\Event\OrganizationUpdateEvent;

class OrganizationUpdateEventTest extends \PHPUnit_Framework_TestCase
{
    /** @var OrganizationUpdateEvent */
    protected $event;
    
    public function testGetOrganization()
    {
        $organization = new Organization();
        $this->event = new OrganizationUpdateEvent($organization);

        $this->assertEquals($organization, $this->event->getOrganization());
    }
}
