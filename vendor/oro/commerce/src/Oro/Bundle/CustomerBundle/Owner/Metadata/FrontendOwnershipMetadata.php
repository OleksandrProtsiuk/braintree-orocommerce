<?php

namespace Oro\Bundle\CustomerBundle\Owner\Metadata;

use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;

/**
 * This class represents the entity ownership metadata for CustomerUser
 */
class FrontendOwnershipMetadata extends OwnershipMetadata
{
    const OWNER_TYPE_FRONTEND_USER = 4;
    const OWNER_TYPE_FRONTEND_CUSTOMER = 5;

    /**
     * {@inheritdoc}
     */
    public function isLocalLevelOwned($deep = false)
    {
        return $this->ownerType === self::OWNER_TYPE_FRONTEND_CUSTOMER;
    }

    /**
     * {@inheritdoc}
     */
    public function isBasicLevelOwned()
    {
        return $this->ownerType === self::OWNER_TYPE_FRONTEND_USER;
    }

    /**
     * {@inheritdoc}
     */
    public function isGlobalLevelOwned()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessLevelNames()
    {
        if (!$this->hasOwner()) {
            return [
                AccessLevel::NONE_LEVEL => AccessLevel::NONE_LEVEL_NAME,
                AccessLevel::SYSTEM_LEVEL => AccessLevel::getAccessLevelName(AccessLevel::SYSTEM_LEVEL),
            ];
        }

        if ($this->isBasicLevelOwned()) {
            $maxLevel = AccessLevel::DEEP_LEVEL;
            $minLevel = AccessLevel::BASIC_LEVEL;
        } elseif ($this->isLocalLevelOwned()) {
            $maxLevel = AccessLevel::DEEP_LEVEL;
            $minLevel = AccessLevel::LOCAL_LEVEL;
        } else {
            throw new \BadMethodCallException(sprintf('Owner type %s is not supported', $this->ownerType));
        }

        return AccessLevel::getAccessLevelNames($minLevel, $maxLevel);
    }
}
