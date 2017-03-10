<?php

namespace Oro\Bundle\WarehouseBundle\Entity\Helper;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WarehouseBundle\Entity\Warehouse;

class WarehouseCounter
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @return bool
     */
    public function areMoreWarehouses()
    {
        return $this->doctrineHelper->getEntityRepository(Warehouse::class)->countAll() > 1;
    }
}
