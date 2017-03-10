<?php

namespace Oro\Bundle\EwsBundle\Ews\EwsType;

// @codingStandardsIgnoreStart
/**
 * PostItemType
 *
 * @ignore This code was generated by a tool.
 *         Changes to this file may cause incorrect behaviour and will be lost if
 *         the code is regenerated.
 * @SuppressWarnings(PHPMD)
 */
class PostItemType extends ItemType
{
    /**
     * @var string WSDL type is base64Binary
     * @access public
     */
    public $ConversationIndex;

    /**
     * @var string
     * @access public
     */
    public $ConversationTopic;

    /**
     * @var SingleRecipientType
     * @access public
     */
    public $From;

    /**
     * @var string
     * @access public
     */
    public $InternetMessageId;

    /**
     * @var boolean
     * @access public
     */
    public $IsRead;

    /**
     * @var string WSDL type is dateTime
     * @access public
     */
    public $PostedTime;

    /**
     * @var string
     * @access public
     */
    public $References;

    /**
     * @var SingleRecipientType
     * @access public
     */
    public $Sender;
}
// @codingStandardsIgnoreEnd
