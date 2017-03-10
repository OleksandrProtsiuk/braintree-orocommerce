<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\EventListener\Config;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Event\ConfigSettingsUpdateEvent;
use Oro\Bundle\ConfigBundle\Event\ConfigGetEvent;
use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use Oro\Bundle\PayPalBundle\EventListener\Config\PayPalConfigurationEncryptListener;
use Oro\Bundle\PayPalBundle\DependencyInjection\Configuration;
use Oro\Bundle\PayPalBundle\DependencyInjection\OroPayPalExtension;

class PayPalConfigurationEncryptListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var Mcrypt|\PHPUnit_Framework_MockObject_MockObject */
    protected $encoder;

    /** @var PayPalConfigurationEncryptListener */
    protected $listener;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->encoder = $this->getMockBuilder('Oro\Bundle\SecurityBundle\Encoder\Mcrypt')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new PayPalConfigurationEncryptListener($this->encoder);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->encoder);
    }

    public function testBeforeSave()
    {
        $encryptedPasswordData = 'encrypted_password_data';

        $fullPasswordKey = $this->getFullConfigKey(Configuration::PAYFLOW_GATEWAY_PASSWORD_KEY);

        $settings = [
            $fullPasswordKey => ['value' => 'password'],
        ];

        $this->encoder->expects($this->once())
            ->method('encryptData')
            ->with($settings[$fullPasswordKey]['value'])
            ->willReturn($encryptedPasswordData);

        $event = new ConfigSettingsUpdateEvent($this->configManager, $settings);
        $this->listener->beforeSave($event);

        $actualSettings = $event->getSettings();

        $this->assertArrayHasKey($fullPasswordKey, $actualSettings);
        $this->assertEquals(['value' => $encryptedPasswordData], $actualSettings[$fullPasswordKey]);
    }

    /**
     * @dataProvider loadConfigProvider
     * @param string $key
     * @param string $value
     * @param bool $full
     * @param string $expectedValue
     */
    public function testLoadConfig($key, $value, $full, $expectedValue)
    {
        $plainValue = $full ? $value['value'] : $value;

        $this->encoder
            ->expects($value === $expectedValue ? $this->never() : $this->once())
            ->method('decryptData')
            ->with($plainValue)
            ->willReturnCallback(
                function ($value) {
                    return 'encrypted_' . $value;
                }
            );

        $event = new ConfigGetEvent($this->configManager, $key, $value, $full, null);

        $this->listener->loadConfig($event);
        $this->assertEquals($expectedValue, $event->getValue());
    }

    /**
     * @return array
     */
    public function loadConfigProvider()
    {
        return [
            'full with encryptable value' => [
                'key' => $this->getFullConfigKey(Configuration::PAYFLOW_GATEWAY_PARTNER_KEY),
                'value' => ['value' => 'email@example.com'],
                'full' => true,
                'expectedValue' => ['value' => 'encrypted_email@example.com']
            ],
            'non-full with encryptable value' => [
                'key' => $this->getFullConfigKey(Configuration::PAYFLOW_GATEWAY_PARTNER_KEY),
                'value' => 'email@example.com',
                'full' => false,
                'expectedValue' => 'encrypted_email@example.com'
            ],
            'unhandled key' => [
                'key' => 'somekey.key',
                'value' => true,
                'full' => false,
                'expectedValue' => true
            ],
            'empty value' => [
                'key' => $this->getFullConfigKey(Configuration::PAYFLOW_GATEWAY_PARTNER_KEY),
                'value' => null,
                'full' => false,
                'expectedValue' => null
            ],
        ];
    }

    /**
     * @param string $configKey
     * @return string
     */
    protected function getFullConfigKey($configKey)
    {
        return OroPayPalExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $configKey;
    }
}
