<?php

namespace Oro\Bundle\EwsBundle\Ews\EwsType;

// @codingStandardsIgnoreStart
/**
 * SyncFolderItemsResponseMessageType
 *
 * @ignore This code was generated by a tool.
 *         Changes to this file may cause incorrect behaviour and will be lost if
 *         the code is regenerated.
 * @SuppressWarnings(PHPMD)
 */
class SyncFolderItemsResponseMessageType extends ResponseMessageType
{
    /**
     * @var string
     * @access public
     */
    public $SyncState;

    /**
     * @var boolean
     * @access public
     */
    public $IncludesLastItemInRange;

    /**
     * @var SyncFolderItemsChangesType
     * @access public
     */
    public $Changes;
}
// @codingStandardsIgnoreEnd
