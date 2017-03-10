<?php

namespace Oro\Bundle\WebsiteBundle\Tests\Unit\Twig;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\WebsiteBundle\Resolver\WebsiteUrlResolver;
use Oro\Bundle\WebsiteBundle\Twig\WebsitePathExtension;

class WebsitePathExtensionTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var WebsiteUrlResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteUrlResolver;

    /**
     * @var WebsitePathExtension
     */
    protected $websitePathExtension;

    protected function setUp()
    {
        $this->websiteUrlResolver = $this->getMockBuilder(WebsiteUrlResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->websitePathExtension = new WebsitePathExtension($this->websiteUrlResolver);
    }

    public function testGetName()
    {
        $this->assertEquals(WebsitePathExtension::NAME, $this->websitePathExtension->getName());
    }

    public function testGetFunctions()
    {
        /** @var \Twig_SimpleFunction[] $functions */
        $functions = $this->websitePathExtension->getFunctions();

        $this->assertCount(2, $functions);

        $availableFunctions = [
            'website_path',
            'website_secure_path'
        ];

        foreach ($functions as $function) {
            $this->assertInstanceOf(\Twig_SimpleFunction::class, $function);
            $this->assertTrue(in_array($function->getName(), $availableFunctions, true));
        }
    }
}
