<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;

class CategoryProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var RequestProductHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestProductHandler;

    /** @var CategoryRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryRepository;

    /** @var CategoryTreeProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $categoryTreeProvider;

    /**
     * @var CategoryProvider
     */
    protected $categoryProvider;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->requestProductHandler = $this->createMock(RequestProductHandler::class);
        $this->categoryRepository = $this->createMock(CategoryRepository::class);
        $this->categoryTreeProvider = $this->createMock(CategoryTreeProvider::class);

        $this->categoryProvider = new CategoryProvider(
            $this->requestProductHandler,
            $this->categoryRepository,
            $this->categoryTreeProvider
        );
    }

    public function testGetCurrentCategoryUsingMasterCatalogRoot()
    {
        $category = new Category();

        $this->requestProductHandler
            ->expects($this->once())
            ->method('getCategoryId')
            ->willReturn(null);

        $this->categoryRepository
            ->expects($this->once())
            ->method('getMasterCatalogRoot')
            ->willReturn($category);

        $result = $this->categoryProvider->getCurrentCategory();
        $this->assertSame($category, $result);
    }

    public function testGetCurrentCategoryUsingFind()
    {
        $category = new Category();
        $categoryId = 1;

        $this->requestProductHandler
            ->expects($this->once())
            ->method('getCategoryId')
            ->willReturn($categoryId);

        $this->categoryRepository
            ->expects($this->once())
            ->method('find')
            ->with($categoryId)
            ->willReturn($category);

        $result = $this->categoryProvider->getCurrentCategory();
        $this->assertSame($category, $result);
    }

    public function testGetRootCategory()
    {
        $category = new Category();

        $this->categoryRepository
            ->expects($this->once())
            ->method('getMasterCatalogRoot')
            ->willReturn($category);

        $result = $this->categoryProvider->getRootCategory();
        $this->assertSame($category, $result);
    }

    public function testGetCategoryTree()
    {
        $childCategory = new Category();
        $childCategory->setLevel(2);

        $mainCategory = new Category();
        $mainCategory->setLevel(1);
        $mainCategory->addChildCategory($childCategory);

        $rootCategory = new Category();
        $rootCategory->setLevel(0);
        $rootCategory->addChildCategory($mainCategory);

        $user = new CustomerUser();

        $this->categoryRepository
            ->expects($this->once())
            ->method('getMasterCatalogRoot')
            ->willReturn($rootCategory);

        $this->categoryTreeProvider->expects($this->once())
            ->method('getCategories')
            ->with($user, $rootCategory, null)
            ->willReturn([$mainCategory]);

        $actual = $this->categoryProvider->getCategoryTree($user);

        $this->assertEquals([$mainCategory], $actual);
    }
}
