<?php

namespace Oro\Bundle\VisibilityBundle\Model;

interface MessageFactoryInterface
{
    /**
     * @param object $visibility
     */
    public function createMessage($visibility);

    /**
     * @param array $data
     * @return object
     */
    public function getEntityFromMessage($data);
}
