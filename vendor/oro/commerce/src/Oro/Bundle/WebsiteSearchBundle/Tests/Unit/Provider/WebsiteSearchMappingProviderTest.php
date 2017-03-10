<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Oro\Bundle\SearchBundle\Tests\Unit\Provider\AbstractSearchMappingProviderTest;
use Oro\Bundle\WebsiteSearchBundle\Loader\ConfigurationLoaderInterface;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;

class WebsiteSearchMappingProviderTest extends AbstractSearchMappingProviderTest
{
    /** @var ConfigurationLoaderInterface|\PHPUnit_Framework_MockObject_MockObject $mappingConfigurationLoader */
    protected $mappingConfigurationLoader;

    protected function tearDown()
    {
        unset($this->mappingConfigurationLoader);
    }

    public function testGetMappingConfig()
    {
        $this->assertEquals($this->testMapping, $this->getProvider()->getMappingConfig());

        // Check that cache was used
        $this->getProvider()->getMappingConfig();
    }

    /**
     * {@inheritdoc}
     */
    protected function getProvider()
    {
        $this->mappingConfigurationLoader = $this->createMock(ConfigurationLoaderInterface::class);
        $this->mappingConfigurationLoader
            ->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($this->testMapping);

        return new WebsiteSearchMappingProvider($this->mappingConfigurationLoader);
    }
}
