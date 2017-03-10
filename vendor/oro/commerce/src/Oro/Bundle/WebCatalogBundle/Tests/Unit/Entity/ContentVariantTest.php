<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Entity;

use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ContentVariantTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new ContentVariant(), [
            ['type', 'productPage'],
            ['systemPageRoute', 'some_route'],
            ['node', new ContentNode()],
            ['default', true]
        ]);

        $this->assertPropertyCollections(new ContentVariant(), [
            ['scopes', new Scope()],
            ['slugs', new Slug()]
        ]);
    }
}
