<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Twig;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;
use Oro\Bundle\DataGridBundle\Twig\DataGridExtension;
use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DataGridExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerInterface */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|NameStrategyInterface */
    protected $nameStrategy;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RouterInterface */
    protected $router;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    /** @var DataGridExtension */
    protected $twigExtension;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridRouteHelper */
    protected $datagridRouteHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|RequestStack */
    protected $requestStack;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    private $logger;

    protected function setUp()
    {
        $this->manager = $this->createMock(ManagerInterface::class);
        $this->nameStrategy = $this->createMock(NameStrategyInterface::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->datagridRouteHelper = $this->getMockBuilder(DatagridRouteHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->logger = $this->getMockBuilder('Psr\Log\LoggerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->twigExtension = new DataGridExtension(
            $this->manager,
            $this->nameStrategy,
            $this->router,
            $this->securityFacade,
            $this->datagridRouteHelper,
            $this->requestStack,
            $this->logger
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_datagrid', $this->twigExtension->getName());
    }

    public function testGetFunctions()
    {
        $expectedFunctions = [
            ['oro_datagrid_build', [$this->twigExtension, 'getGrid']],
            ['oro_datagrid_data', [$this->twigExtension, 'getGridData']],
            ['oro_datagrid_metadata', [$this->twigExtension, 'getGridMetadata']],
            ['oro_datagrid_generate_element_id', [$this->twigExtension, 'generateGridElementId']],
            ['oro_datagrid_build_fullname', [$this->twigExtension, 'buildGridFullName']],
            ['oro_datagrid_build_inputname', [$this->twigExtension, 'buildGridInputName']],
            ['oro_datagrid_link', [$this->datagridRouteHelper, 'generate']],
            ['oro_datagrid_column_attributes', [$this->twigExtension, 'getColumnAttributes']],
            ['oro_datagrid_get_page_url', [$this->twigExtension, 'getPageUrl']],
        ];
        /** @var \Twig_SimpleFunction[] $actualFunctions */
        $actualFunctions = $this->twigExtension->getFunctions();
        $this->assertSameSize($expectedFunctions, $actualFunctions);

        foreach ($actualFunctions as $twigFunction) {
            $expectedFunction = current($expectedFunctions);

            $this->assertInstanceOf('\Twig_SimpleFunction', $twigFunction);
            $this->assertEquals($expectedFunction[0], $twigFunction->getName());
            $this->assertEquals($expectedFunction[1], $twigFunction->getCallable());

            next($expectedFunctions);
        }
    }

    public function testGetGridWorks()
    {
        $gridName = 'test-grid';
        $params = ['foo' => 'bar'];

        $grid = $this->createMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\DatagridInterface');

        $configuration = $this->getMockBuilder('Oro\\Bundle\\DataGridBundle\\Datagrid\\Common\\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->setMethods(['getAclResource'])
            ->getMock();

        $configuration->expects($this->once())
            ->method('getAclResource')
            ->will($this->returnValue(null));

        $this->manager->expects($this->once())
            ->method('getConfigurationForGrid')
            ->with($gridName)
            ->will($this->returnValue($configuration));

        $this->manager->expects($this->once())
            ->method('getDatagridByRequestParams')
            ->with($gridName, $params)
            ->will($this->returnValue($grid));

        $this->assertSame($grid, $this->twigExtension->getGrid($gridName, $params));
    }

    public function testGetGridReturnsNullWhenConfigurationNotFound()
    {
        $gridName = 'test-grid';

        $this->manager->expects($this->once())
            ->method('getConfigurationForGrid')
            ->with($gridName)
            ->will($this->returnValue(null));

        $this->assertNull($this->twigExtension->getGrid($gridName));
    }

    public function testGetGridReturnsNullWhenDontHavePermissions()
    {
        $gridName = 'test-grid';

        $acl = 'test-acl';

        $configuration = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();

        $configuration->expects($this->once())
            ->method('getAclResource')
            ->will($this->returnValue($acl));

        $this->manager->expects($this->once())
            ->method('getConfigurationForGrid')
            ->with($gridName)
            ->will($this->returnValue($configuration));

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with($acl)
            ->will($this->returnValue(false));

        $this->assertNull($this->twigExtension->getGrid($gridName));
    }

    /**
     * @param mixed $configRoute
     * @param string $expectedRoute
     *
     * @dataProvider routeProvider
     */
    public function testGetGridMetadataWorks($configRoute, $expectedRoute)
    {
        $gridName = 'test-grid';
        $gridScope = 'test-scope';
        $gridFullName = 'test-grid:test-scope';
        $params = ['foo' => 'bar'];
        $url = '/datagrid/test-grid?test-grid-test-scope=foo=bar';

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $grid */
        $grid = $this->createMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\DatagridInterface');
        $metadata = $this->getMockBuilder('Oro\\Bundle\\DataGridBundle\\Datagrid\\Common\\MetadataObject')
            ->disableOriginalConstructor()
            ->getMock();

        $grid->expects($this->once())
            ->method('getMetadata')
            ->will($this->returnValue($metadata));

        $grid->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($gridName));

        $grid->expects($this->once())
            ->method('getScope')
            ->will($this->returnValue($gridScope));

        $this->nameStrategy->expects($this->once())
            ->method('buildGridFullName')
            ->with($gridName, $gridScope)
            ->will($this->returnValue($gridFullName));

        $this->nameStrategy->expects($this->once())
            ->method('getGridUniqueName')
            ->with($gridFullName)
            ->will($this->returnValue($gridFullName));

        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                $expectedRoute,
                ['gridName' => $gridFullName, $gridFullName => $params]
            )
            ->will($this->returnValue($url));

        $metadata->expects($this->once())
            ->method('offsetAddToArray')
            ->with('options', ['url' => $url, 'urlParams' => $params]);

        $metadata->expects($this->any())
            ->method('offsetGetByPath')
            ->with()
            ->will(
                $this->returnValueMap(
                    [
                        ['[options][route]', $configRoute],
                    ]
                )
            );

        $metadataArray = ['metadata-array'];
        $metadata->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($metadataArray));

        $this->assertSame($metadataArray, $this->twigExtension->getGridMetadata($grid, $params));
    }

    /**
     * @return array
     */
    public function routeProvider()
    {
        return [
            [null, DataGridExtension::ROUTE],
        ];
    }

    public function testGetGridDataWorks()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $grid */
        $grid = $this->createMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\DatagridInterface');
        $gridData = $this->getMockBuilder('Oro\\Bundle\\DataGridBundle\\Datagrid\\Common\\ResultsObject')
            ->disableOriginalConstructor()
            ->getMock();

        $grid->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($gridData));

        $gridDataArray = ['grid-data'];

        $gridData->expects($this->once())
            ->method('toArray')
            ->will($this->returnValue($gridDataArray));

        $this->assertSame($gridDataArray, $this->twigExtension->getGridData($grid));
    }

    public function testGetGridDataException()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $grid */
        $grid = $this->createMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\DatagridInterface');
        $gridData = $this->getMockBuilder('Oro\\Bundle\\DataGridBundle\\Datagrid\\Common\\ResultsObject')
            ->disableOriginalConstructor()
            ->getMock();

        $grid->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($gridData));

        $exception = new \Exception('Page not found');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Getting grid data failed.',
                ['exception' => $exception]
            );

        $errorArray = [
            "data" => [],
            "metadata" => [],
            "options" => []
        ];

        $gridData->expects($this->once())
            ->method('toArray')
            ->willThrowException($exception);

        $this->assertSame($errorArray, $this->twigExtension->getGridData($grid));
    }

    /**
     * @dataProvider generateGridElementIdDataProvider
     * @param string $gridName
     * @param string $gridScope
     * @param string $expectedPattern
     */
    public function testGenerateGridElementIdWorks($gridName, $gridScope, $expectedPattern)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $grid */
        $grid = $this->createMock('Oro\\Bundle\\DataGridBundle\\Datagrid\\DatagridInterface');

        $grid->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($gridName));

        $grid->expects($this->atLeastOnce())
            ->method('getScope')
            ->will($this->returnValue($gridScope));

        $this->assertRegExp($expectedPattern, $this->twigExtension->generateGridElementId($grid));
    }

    /**
     * @return array
     */
    public function generateGridElementIdDataProvider()
    {
        return [
            [
                'test-grid',
                'test-scope',
                '/grid-test-grid-test-scope-[\d]+/',
            ],
            [
                'test-grid',
                '',
                '/grid-test-grid-[\d]+/',
            ],
        ];
    }

    public function testBuildGridFullNameWorks()
    {
        $expectedFullName = 'test-grid:test-scope';
        $gridName = 'test-grid';
        $gridScope = 'test-scope';

        $this->nameStrategy->expects($this->once())
            ->method('buildGridFullName')
            ->will($this->returnValue($expectedFullName));

        $this->assertEquals($expectedFullName, $this->twigExtension->buildGridFullName($gridName, $gridScope));
    }

    public function testGetColumnAttributes()
    {
        $columnAttributes = [
            'name' => 'column1',
            'label' => 'Column 1',
            'type' => 'string'
        ];

        $metadata = $this->getMockBuilder(MetadataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->exactly(2))
            ->method('toArray')
            ->willReturn([
                'columns' => [$columnAttributes]
            ]);

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $grid */
        $grid = $this->createMock(DatagridInterface::class);
        $grid->expects($this->exactly(2))
            ->method('getMetadata')
            ->willReturn($metadata);

        $this->assertEquals($columnAttributes, $this->twigExtension->getColumnAttributes($grid, 'column1'));
        $this->assertEquals([], $this->twigExtension->getColumnAttributes($grid, 'column3'));
    }

    /**
     * @dataProvider getPageUrlProvider
     *
     * @param string $queryString
     * @param integer $page
     * @param array $expectedParameters
     */
    public function testGetPageUrl($queryString, $page, array $expectedParameters)
    {
        $gridName = 'test';

        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('getQueryString')->willReturn($queryString);
        $request->expects($this->once())->method('get')->with('_route')->willReturn('test_route');

        $this->requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $grid = $this->createMock(DatagridInterface::class);
        $grid->expects($this->any())->method('getName')->willReturn($gridName);

        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('test_route', $expectedParameters)
            ->willReturn('test_url');

        $this->assertEquals('test_url', $this->twigExtension->getPageUrl($grid, $page));
    }

    /**
     * @return array
     */
    public function getPageUrlProvider()
    {
        return [
            'with empty query string' => [
                'queryString' => '',
                'page' => 5,
                'expectedParameters' => [
                    'grid' => [
                        'test' => 'i=5'
                    ]
                ]
            ],
            'with not empty query string but without grid params' => [
                'queryString' => 'foo=bar&bar=baz',
                'page' => 5,
                'expectedParameters' => [
                    'foo' => 'bar',
                    'bar' => 'baz',
                    'grid' => [
                        'test' => 'i=5'
                    ]
                ]
            ],
            'with grid params in query sting' => [
                'queryString' => 'grid%5Btest%5D=i%3D4',
                'page' => 5,
                'expectedParameters' => [
                    'grid' => [
                        'test' => 'i=5'
                    ]
                ]
            ],
        ];
    }

    protected function tearDown()
    {
        unset(
            $this->datagridRouteHelper,
            $this->manager,
            $this->nameStrategy,
            $this->router,
            $this->securityFacade,
            $this->twigExtension
        );
    }
}
