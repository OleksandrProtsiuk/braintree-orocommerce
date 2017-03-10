<?php
/**
 * Handles Soap communication with the Exchnage server using NTLM
 * authentication
 *
 * @link https://github.com/jamesiarmes/php-ews/blob/master/NTLMSoapClient/Exchange.php
 * @author James I. Armes <jamesiarmes@gmail.com>
 *
 */

namespace Oro\Bundle\EwsBundle\Ews;

use SoapHeader;

/**
 * Handles Soap communication with the Exchnage server using NTLM
 * authentication
 *
 * @author James I. Armes <jamesiarmes@gmail.com>
 */
class ExchangeSoapClient extends NTLMSoapClient
{
    /**
     * Username for authentication on the Exchange Server
     *
     * @var string
     */
    protected $user;

    /**
     * Password for authentication on the Exchange Server
     *
     * @var string
     */
    protected $password;

    /**
     * Constructor
     *
     * @param string $wsdl URI of the WSDL file or NULL if working in non-WSDL mode.
     * @param array $options An array of options. If working in WSDL mode, this parameter is optional.
     * @throws EwsException
     */
    public function __construct($wsdl, $options)
    {
        // Verify that a user name and password were entered.
        if (empty($options['user']) || empty($options['password'])) {
            throw new EwsException('A username and password is required.', EwsException::buildSenderFaultCode(
                "Validation.Failed"
            ));
        }

        // Set the username and password properties.
        $this->user = $options['user'];
        $this->password = $options['password'];

        // If a version was set then add it to the headers.
        if (!empty($options['version'])) {
            $this->__default_headers[] = new SoapHeader(
                'http://schemas.microsoft.com/exchange/services/2006/types',
                'RequestServerVersion Version="' . $options['version'] . '"'
            );
        }

        // If impersonation was set then add it to the headers.
        if (!empty($options['impersonation'])) {
            $this->__default_headers[] = new SoapHeader(
                'http://schemas.microsoft.com/exchange/services/2006/types',
                'ExchangeImpersonation',
                $options['impersonation']
            );
        }

        parent::__construct($wsdl, $options);
    }

    /**
     * Returns the response code from the last request
     *
     * @return integer
     */
    public function getResponseCode()
    {
        return curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
    }
}
