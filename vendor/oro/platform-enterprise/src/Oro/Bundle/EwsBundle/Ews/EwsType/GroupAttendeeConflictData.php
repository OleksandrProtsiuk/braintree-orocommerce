<?php

namespace Oro\Bundle\EwsBundle\Ews\EwsType;

// @codingStandardsIgnoreStart
/**
 * GroupAttendeeConflictData
 *
 * @ignore This code was generated by a tool.
 *         Changes to this file may cause incorrect behaviour and will be lost if
 *         the code is regenerated.
 * @SuppressWarnings(PHPMD)
 */
class GroupAttendeeConflictData extends AttendeeConflictData
{
    /**
     * @var integer WSDL type is int
     * @access public
     */
    public $NumberOfMembers;

    /**
     * @var integer WSDL type is int
     * @access public
     */
    public $NumberOfMembersAvailable;

    /**
     * @var integer WSDL type is int
     * @access public
     */
    public $NumberOfMembersWithConflict;

    /**
     * @var integer WSDL type is int
     * @access public
     */
    public $NumberOfMembersWithNoData;
}
// @codingStandardsIgnoreEnd
