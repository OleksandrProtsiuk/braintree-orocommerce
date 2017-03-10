<?php

namespace Oro\Bundle\EwsBundle\Ews\EwsType;

// @codingStandardsIgnoreStart
/**
 * ResolveNamesType
 *
 * @ignore This code was generated by a tool.
 *         Changes to this file may cause incorrect behaviour and will be lost if
 *         the code is regenerated.
 * @SuppressWarnings(PHPMD)
 */
class ResolveNamesType extends BaseRequestType
{
    /**
     * @var NonEmptyArrayOfBaseFolderIdsType
     * @access public
     */
    public $ParentFolderIds;

    /**
     * @var string WSDL type is NonEmptyStringType
     * @access public
     */
    public $UnresolvedEntry;

    /**
     * @var boolean
     * @access public
     */
    public $ReturnFullContactData;

    /**
     * @var string
     * @see ResolveNamesSearchScopeType
     * @access public
     */
    public $SearchScope;

    /**
     * @var string
     * @see DefaultShapeNamesType
     * @access public
     */
    public $ContactDataShape;
}
// @codingStandardsIgnoreEnd
