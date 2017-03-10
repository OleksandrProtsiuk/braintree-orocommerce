<?php

namespace Oro\Bundle\DataGridBundle\Twig;

use Psr\Log\LoggerInterface;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ManagerInterface;
use Oro\Bundle\DataGridBundle\Datagrid\NameStrategyInterface;
use Oro\Bundle\DataGridBundle\Tools\DatagridRouteHelper;

use Oro\Bundle\SecurityBundle\SecurityFacade;

class DataGridExtension extends \Twig_Extension
{
    const ROUTE = 'oro_datagrid_index';

    /** @var ManagerInterface */
    protected $manager;

    /** @var NameStrategyInterface */
    protected $nameStrategy;

    /** @var RouterInterface */
    protected $router;

    /** @var SecurityFacade $securityFacade */
    protected $securityFacade;

    /** @var DatagridRouteHelper */
    protected $datagridRouteHelper;

    /** @var RequestStack */
    protected $requestStack;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param ManagerInterface $manager
     * @param NameStrategyInterface $nameStrategy
     * @param RouterInterface $router
     * @param SecurityFacade $securityFacade
     * @param DatagridRouteHelper $datagridRouteHelper
     * @param RequestStack $requestStack
     * @param LoggerInterface $logger
     */
    public function __construct(
        ManagerInterface $manager,
        NameStrategyInterface $nameStrategy,
        RouterInterface $router,
        SecurityFacade $securityFacade,
        DatagridRouteHelper $datagridRouteHelper,
        RequestStack $requestStack,
        LoggerInterface $logger = null
    ) {
        $this->manager = $manager;
        $this->nameStrategy = $nameStrategy;
        $this->router = $router;
        $this->securityFacade = $securityFacade;
        $this->datagridRouteHelper = $datagridRouteHelper;
        $this->requestStack = $requestStack;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'oro_datagrid';
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_datagrid_build', [$this, 'getGrid']),
            new \Twig_SimpleFunction('oro_datagrid_data', [$this, 'getGridData']),
            new \Twig_SimpleFunction('oro_datagrid_metadata', [$this, 'getGridMetadata']),
            new \Twig_SimpleFunction('oro_datagrid_generate_element_id', [$this, 'generateGridElementId']),
            new \Twig_SimpleFunction('oro_datagrid_build_fullname', [$this, 'buildGridFullName']),
            new \Twig_SimpleFunction('oro_datagrid_build_inputname', [$this, 'buildGridInputName']),
            new \Twig_SimpleFunction('oro_datagrid_link', [$this->datagridRouteHelper, 'generate']),
            new \Twig_SimpleFunction('oro_datagrid_column_attributes', [$this, 'getColumnAttributes']),
            new \Twig_SimpleFunction('oro_datagrid_get_page_url', [$this, 'getPageUrl']),
        ];
    }

    /**
     * @param string $name
     * @param array $params
     * @return DatagridInterface
     */
    public function getGrid($name, array $params = [])
    {
        if ($this->isAclGrantedForGridName($name)) {
            return $this->manager->getDatagridByRequestParams($name, $params);
        }

        return null;
    }

    /**
     * Returns grid metadata array
     *
     * @param DatagridInterface $grid
     * @param array $params
     * @return array
     */
    public function getGridMetadata(DatagridInterface $grid, array $params = [])
    {
        $metaData = $grid->getMetadata();
        $params   = array_merge(
            $metaData->offsetGetByPath('[options][urlParams]') ? : [],
            $params
        );

        $route = $metaData->offsetGetByPath('[options][route]');
        $metaData->offsetAddToArray(
            'options',
            [
                'url' => $this->generateUrl($grid, $route, $params),
                'urlParams' => $params,
            ]
        );

        return $metaData->toArray();
    }

    /**
     * Renders grid data
     *
     * @param DatagridInterface $grid
     * @return array
     */
    public function getGridData(DatagridInterface $grid)
    {
        try {
            return $grid->getData()->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Getting grid data failed.', ['exception' => $e]);

            return [
                "data" => [],
                "metadata" => [],
                "options" => []
            ];
        }
    }

    /**
     * Generate grid element id.
     *
     * @param DatagridInterface $grid
     * @return array
     */
    public function generateGridElementId(DatagridInterface $grid)
    {
        $result = 'grid-' . $grid->getName() . '-';
        if ($grid->getScope()) {
            $result .= $grid->getScope() . '-';
        }
        $result .= mt_rand();

        return $result;
    }

    /**
     * Generate grid full name.
     *
     * @param string $name
     * @param string $scope
     * @return array
     */
    public function buildGridFullName($name, $scope)
    {
        return $this->nameStrategy->buildGridFullName($name, $scope);
    }

    /**
     * Generate grid input name
     *
     * @param string $name
     *
     * @return string
     */
    public function buildGridInputName($name)
    {
        return $this->nameStrategy->getGridUniqueName($name);
    }

    /**
     * @param DatagridInterface $grid
     * @param string            $columnName
     *
     * @return array|null
     */
    public function getColumnAttributes(DatagridInterface $grid, $columnName)
    {
        $metadata = $grid->getMetadata()->toArray();

        if (array_key_exists('columns', $metadata)) {
            foreach ($metadata['columns'] as $column) {
                if ($columnName === $column['name']) {
                    return $column;
                }
            }
        }

        return [];
    }

    /**
     * Generates url based on current uri with replaced current page parameter
     *
     * @param DatagridInterface $grid
     * @param integer           $page
     *
     * @return string
     */
    public function getPageUrl(DatagridInterface $grid, $page)
    {
        $request = $this->requestStack->getCurrentRequest();

        $queryString = $request->getQueryString();
        parse_str($queryString, $queryStringComponents);

        $gridParams = [];
        if (isset($queryStringComponents['grid'][$grid->getName()])) {
            parse_str($queryStringComponents['grid'][$grid->getName()], $gridParams);
        }

        $gridParams['i'] = $page;
        $queryStringComponents['grid'][$grid->getName()] = http_build_query($gridParams);

        $route = $request->get('_route');

        return $this->router->generate($route, $queryStringComponents);
    }

    /**
     * @param DatagridInterface $grid
     * @param string $route
     * @param array $params
     * @return string
     */
    protected function generateUrl(DatagridInterface $grid, $route, $params)
    {
        $gridFullName = $this->nameStrategy->buildGridFullName($grid->getName(), $grid->getScope());
        $gridUniqueName = $this->nameStrategy->getGridUniqueName($gridFullName);

        return $this->router->generate(
            $route ?: self::ROUTE,
            ['gridName' => $gridFullName, $gridUniqueName => $params]
        );
    }

    /**
     * @param string $gridName
     *
     * @return bool
     */
    protected function isAclGrantedForGridName($gridName)
    {
        $gridConfig = $this->manager->getConfigurationForGrid($gridName);

        if ($gridConfig) {
            $aclResource = $gridConfig->getAclResource();
            if ($aclResource && !$this->securityFacade->isGranted($aclResource)) {
                return false;
            } else {
                return true;
            }
        }

        return false;
    }
}
