<?php

namespace Oro\Bundle\EwsBundle\Ews\EwsType;

// @codingStandardsIgnoreStart
/**
 * GetSharingFolderType
 *
 * @ignore This code was generated by a tool.
 *         Changes to this file may cause incorrect behaviour and will be lost if
 *         the code is regenerated.
 * @SuppressWarnings(PHPMD)
 */
class GetSharingFolderType extends BaseRequestType
{
    /**
     * @var string WSDL type is NonEmptyStringType
     * @access public
     */
    public $SmtpAddress;

    /**
     * @var string
     * @see SharingDataType
     * @access public
     */
    public $DataType;

    /**
     * @var string WSDL type is NonEmptyStringType
     * @access public
     */
    public $SharedFolderId;
}
// @codingStandardsIgnoreEnd
