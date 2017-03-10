<?php

namespace Oro\Bundle\EwsBundle\Ews\EwsType;

// @codingStandardsIgnoreStart
/**
 * FindMessageTrackingReportRequestType
 *
 * @ignore This code was generated by a tool.
 *         Changes to this file may cause incorrect behaviour and will be lost if
 *         the code is regenerated.
 * @SuppressWarnings(PHPMD)
 */
class FindMessageTrackingReportRequestType extends BaseRequestType
{
    /**
     * @var string WSDL type is NonEmptyStringType
     * @access public
     */
    public $Scope;

    /**
     * @var string WSDL type is NonEmptyStringType
     * @access public
     */
    public $Domain;

    /**
     * @var EmailAddressType
     * @access public
     */
    public $Sender;

    /**
     * @var EmailAddressType
     * @access public
     */
    public $PurportedSender;

    /**
     * @var EmailAddressType
     * @access public
     */
    public $Recipient;

    /**
     * @var string
     * @access public
     */
    public $Subject;

    /**
     * @var string WSDL type is dateTime
     * @access public
     */
    public $StartDateTime;

    /**
     * @var string WSDL type is dateTime
     * @access public
     */
    public $EndDateTime;

    /**
     * @var string WSDL type is NonEmptyStringType
     * @access public
     */
    public $MessageId;

    /**
     * @var EmailAddressType
     * @access public
     */
    public $FederatedDeliveryMailbox;

    /**
     * @var string
     * @access public
     */
    public $DiagnosticsLevel;

    /**
     * @var string
     * @access public
     */
    public $ServerHint;

    /**
     * @var ArrayOfTrackingPropertiesType
     * @access public
     */
    public $Properties;
}
// @codingStandardsIgnoreEnd
