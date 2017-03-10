<?php

namespace Oro\Bundle\MultiWebsiteBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;

class WebsiteProvider implements WebsiteProviderInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @return Website[]
     */
    public function getWebsites()
    {
        return $this->registry->getManagerForClass('OroWebsiteBundle:Website')
            ->getRepository('OroWebsiteBundle:Website')
            ->getAllWebsites();
    }
}
