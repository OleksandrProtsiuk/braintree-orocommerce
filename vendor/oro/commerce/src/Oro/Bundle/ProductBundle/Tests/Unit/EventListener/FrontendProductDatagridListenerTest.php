<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\LocaleBundle\Datagrid\Formatter\Property\LocalizedValueProperty;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper;
use Oro\Bundle\ProductBundle\EventListener\FrontendProductDatagridListener;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;

class FrontendProductDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var FrontendProductDatagridListener
     */
    protected $listener;

    /**
     * @var DataGridThemeHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $themeHelper;

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrine;

    /**
     * @var AttachmentManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $attachmentManager;

    /**
     * @var CacheManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $imagineCacheManager;

    /**
     * @var ProductUnitLabelFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $unitFormatter;

    public function setUp()
    {
        $this->themeHelper = $this->getMockBuilder('Oro\Bundle\ProductBundle\DataGrid\DataGridThemeHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine = $this->createMock('Symfony\Bridge\Doctrine\RegistryInterface');

        $this->attachmentManager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()->getMock();

        $this->imagineCacheManager = $this->getMockBuilder('Liip\ImagineBundle\Imagine\Cache\CacheManager')
            ->disableOriginalConstructor()->getMock();

        $this->unitFormatter = $this->getMockBuilder('Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new FrontendProductDatagridListener(
            $this->themeHelper,
            $this->doctrine,
            $this->attachmentManager,
            $this->imagineCacheManager,
            $this->unitFormatter
        );
    }

    /**
     * @dataProvider onPreBuildDataProvider
     *
     * @param string $themeName
     * @param array $expectedConfig
     */
    public function testOnPreBuild($themeName, array $expectedConfig)
    {
        $gridName = 'grid-name';
        $this->themeHelper->expects($this->any())
            ->method('getTheme')
            ->willReturn($themeName);

        $config = DatagridConfiguration::createNamed($gridName, []);
        $params = new ParameterBag();
        $event = new PreBuild($config, $params);
        $this->listener->onPreBuild($event);
        $this->assertEquals($expectedConfig, $config->toArray());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function onPreBuildDataProvider()
    {
        return [
            'list' => [
                DataGridThemeHelper::VIEW_LIST,
                [
                    'name' => 'grid-name',
                    'properties' => [
                        'product_units' => [
                            'type' => 'field',
                            'frontend_type' => 'row_array',
                        ]
                    ],
                ],
            ],
            'grid' => [
                DataGridThemeHelper::VIEW_GRID,
                [
                    'name' => 'grid-name',
                    'properties' => [
                        'product_units' => [
                            'type' => 'field',
                            'frontend_type' => 'row_array',
                        ],
                        'shortDescription' => [
                            'type' => LocalizedValueProperty::NAME,
                            'data_name' => 'shortDescriptions',
                        ],
                    ],
                    'columns' => [
                        'image' => ['label' => 'oro.product.image.label'],
                        'shortDescription' => ['label' => 'oro.product.short_descriptions.label'],
                    ]
                ]
            ],
            'tiles' => [
                DataGridThemeHelper::VIEW_TILES,
                [
                    'name' => 'grid-name',
                    'properties' => [
                        'product_units' => [
                            'type' => 'field',
                            'frontend_type' => 'row_array',
                        ]
                    ],
                    'columns' => [
                        'image' => ['label' => 'oro.product.image.label'],
                    ]
                ]
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @dataProvider onResultAfterDataProvider
     *
     * @param string $themeName
     * @param array $data
     * @param array $productWithImages
     * @param array $expectedData
     * @param array $units
     */
    public function testOnResultAfter(
        $themeName,
        array $data,
        array $productWithImages,
        array $expectedData,
        array $units
    ) {
        $ids = [];
        $records = [];
        foreach ($data as $record) {
            $ids[] = $record['id'];
            $records[] = new ResultRecord($record);
        }

        /**
         * @var SearchResultAfter|\PHPUnit_Framework_MockObject_MockObject $event
         */
        $event = $this->getMockBuilder(SearchResultAfter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getRecords')
            ->willReturn($records);

        /**
         * @var Datagrid|\PHPUnit_Framework_MockObject_MockObject $datagrid
         */
        $datagrid = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Datagrid')
            ->disableOriginalConstructor()->getMock();

        $gridName = 'grid-name';
        $datagrid->expects($this->once())
            ->method('getName')
            ->willReturn($gridName);

        $this->themeHelper->expects($this->any())
            ->method('getTheme')
            ->willReturn($themeName);

        $event->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        $productRepository = $this->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $productUnitRepository = $this
            ->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    ['OroProductBundle:Product', $productRepository],
                    ['OroProductBundle:ProductUnit', $productUnitRepository]
                ]
            );

        $images = [];
        foreach ($productWithImages as $index => $productId) {
            $product = $this->createMock('Oro\Bundle\ProductBundle\Entity\Product');
            $product->expects($this->any())
                ->method('getId')
                ->willReturn($productId);

            $image = $this->createMock('Oro\Bundle\AttachmentBundle\Entity\File');
            $images[$productId] = $image;

            $this->attachmentManager->expects($this->at($index))
                ->method('getFilteredImageUrl')
                ->with(
                    $image,
                    FrontendProductDatagridListener::PRODUCT_IMAGE_FILTER_MEDIUM
                )
                ->willReturn($productId);
        }

        $productRepository->expects($this->once())
            ->method('getListingImagesFilesByProductIds')
            ->with($ids)
            ->willReturn($images);

        $productUnitRepository->expects($this->once())
            ->method('getProductsUnits')
            ->with($ids)
            ->willReturn($units);
        $this->unitFormatter->expects($this->any())
            ->method('format')
            ->willReturnCallback(
                function ($string) {
                    return $string . 'Formatted';
                }
            );

        $this->listener->onResultAfter($event);
        foreach ($expectedData as $expectedRecord) {
            $record = current($records);
            $this->assertEquals($expectedRecord['id'], $record->getValue('id'));
            $this->assertEquals($expectedRecord['image'], $record->getValue('image'));
            $this->assertEquals($expectedRecord['expectedUnits'], $record->getValue('product_units'));
            next($records);
        }
    }

    /**
     * @return array
     */
    public function onResultAfterDataProvider()
    {
        return [
            [
                'themeName' => DataGridThemeHelper::VIEW_TILES,
                'records' => [
                    ['id' => 1],
                    ['id' => 2],
                    ['id' => 3],
                ],
                'productWithImages' => [1, 3],
                'expectedData' => [
                    [
                        'id' => 1,
                        'image' => 1,
                        'expectedUnits' => [
                            'item',
                            'pack'
                        ]
                    ],
                    [
                        'id' => 2,
                        'image' => null,
                        'expectedUnits' => [
                            'bottle'
                        ]
                    ],
                    [
                        'id' => 3,
                        'image' => 3,
                        'expectedUnits' => []
                    ],
                ],
                'units' => [
                    1 => ['item', 'pack'],
                    2 => ['bottle']
                ]
            ],
            [
                'themeName' => DataGridThemeHelper::VIEW_TILES,
                'records' => [
                    ['id' => 1],
                    ['id' => 2],
                    ['id' => 3],
                ],
                'productWithImages' => [1, 2, 3],
                'expectedData' => [
                    [
                        'id' => 1,
                        'image' => 1,
                        'expectedUnits' => []
                    ],
                    [
                        'id' => 2,
                        'image' => 2,
                        'expectedUnits' => []
                    ],
                    [
                        'id' => 3,
                        'image' => 3,
                        'expectedUnits' => []
                    ],
                ],
                'units' => []
            ],
        ];
    }

    /**
     * @dataProvider themesWithoutImageDataProvider
     *
     * @param string $themeName
     */
    public function testOnResultAfterViewWithoutImage($themeName)
    {
        /** @var SearchResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(SearchResultAfter::class)
            ->disableOriginalConstructor()->getMock();
        $event->expects($this->once())
            ->method('getRecords')
            ->willReturn([]);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroProductBundle:ProductUnit')
            ->willReturn($em);

        $repository = $this->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitRepository')
            ->disableOriginalConstructor()->getMock();

        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroProductBundle:ProductUnit')
            ->willReturn($repository);

        /** @var Datagrid|\PHPUnit_Framework_MockObject_MockObject $datagrid */
        $datagrid = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Datagrid')
            ->disableOriginalConstructor()->getMock();

        $gridName = 'grid-name';
        $datagrid->expects($this->once())
            ->method('getName')
            ->willReturn($gridName);

        $this->themeHelper->expects($this->any())
            ->method('getTheme')
            ->willReturn($themeName);

        $event->expects($this->once())
            ->method('getDatagrid')
            ->willReturn($datagrid);

        $this->doctrine->expects($this->never())
            ->method('getEntityManagerForClass');

        $this->listener->onResultAfter($event);
    }

    /**
     * @return array
     */
    public function themesWithoutImageDataProvider()
    {
        return [
            ['themeName' => DataGridThemeHelper::VIEW_LIST],
        ];
    }
}
