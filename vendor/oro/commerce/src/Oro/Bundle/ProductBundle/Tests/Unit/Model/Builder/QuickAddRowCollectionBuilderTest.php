<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model\Builder;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Form\Type\QuickAddType;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowCollectionBuilder;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;

class QuickAddRowCollectionBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|QuickAddRowCollectionBuilder
     */
    private $builder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductRepository
     */
    private $productRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ProductManager
     */
    private $productManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array
     */
    private $expectedElements = [
        'HSSUC' => 1,
        'HSTUC' => 2.55,
        'HCCM' => 3,
        'SKU1' => 10.0112,
        'SKU2' => 0,
        'SKU3' => null
    ];

    /**
     * @var array
     */
    protected $completeElementSkus = ['HSSUC', 'HSTUC', 'HCCM', 'SKU1'];

    /**
     * @var array
     */
    protected $validElementSkus = ['HSSUC', 'HSTUC'];

    /**
     * @var int
     */
    protected $expectedCountAll = 6;

    /**
     * @var int
     */
    protected $expectedCountCompleted = 4;

    /**
     * @var int
     */
    protected $expectedCountValid = 2;

    public function setUp()
    {
        $this->productRepository = $this
            ->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->productManager = $this
            ->getMockBuilder(ProductManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new QuickAddRowCollectionBuilder(
            $this->productRepository,
            $this->productManager,
            $this->eventDispatcher
        );
    }

    public function testBuildFromRequest()
    {
        $data = [];
        foreach ($this->expectedElements as $sku => $quantity) {
            $data[] = [
                ProductDataStorage::PRODUCT_SKU_KEY => $sku,
                ProductDataStorage::PRODUCT_QUANTITY_KEY => $quantity,
            ];
        }

        $request = new Request();
        $request->request->set(QuickAddType::NAME, [
            QuickAddType::PRODUCTS_FIELD_NAME => $data
        ]);

        $this->prepareProductRepository();
        $this->prepareProductManager();
        $this->assertValidCollection($this->builder->buildFromRequest($request));
    }

    /**
     * @dataProvider fileDataProvider
     *
     * @param UploadedFile $file
     */
    public function testBuildFromFile(UploadedFile $file)
    {
        if (extension_loaded('xdebug')) {
            $this->markTestSkipped('Skipped due to xdebug enabled (nesting level can be reached)');
        }
        $this->prepareProductManager();
        $this->prepareProductRepository();
        $this->assertValidCollection($this->builder->buildFromFile($file));
    }

    /**
     * @expectedException \Box\Spout\Common\Exception\UnsupportedTypeException
     */
    public function testBuildFromFileWithInvalidFormat()
    {
        $this->builder->buildFromFile(new UploadedFile(__DIR__ . '/files/quick-order.txt', 'quick-order.txt'));
    }

    /**
     * @dataProvider textDataProvider
     *
     * @param string $text
     */
    public function testBuildFromCopyPasteText($text)
    {
        $this->prepareProductRepository();
        $this->prepareProductManager();
        $this->assertValidCollection($this->builder->buildFromCopyPasteText($text));
    }

    /**
     * @return array
     */
    public function textDataProvider()
    {
        $commaSeparated = ['HSSUC, 1', 'HSTUC, 2.55', 'HCCM, 3,', 'SKU1,10.0112', 'SKU2,asd', 'SKU3,'];
        $tabsSeparated = ["HSSUC\t1", "HSTUC\t2.55", "HCCM\t3\t", "SKU1\t10.0112", "SKU2\tasd", "SKU3\t"];
        $spaceSeparated = ['HSSUC 1', 'HSTUC 2.55', 'HCCM 3,', 'SKU1 10.0112', 'SKU2 asd', 'SKU3'];

        return [
            'comma separated' => [implode(PHP_EOL, $commaSeparated)],
            'tabs separated' => [implode(PHP_EOL, $tabsSeparated)],
            'space separated' => [implode(PHP_EOL, $spaceSeparated)],
        ];
    }

    /**
     * @return array
     */
    public function fileDataProvider()
    {
        return [
            'csv' => [new UploadedFile(__DIR__ . '/files/quick-order.csv', 'quick-order.csv')],
            'ods' => [new UploadedFile(__DIR__ . '/files/quick-order.ods', 'quick-order.ods')],
            'xlsx' => [new UploadedFile(__DIR__ . '/files/quick-order.xlsx', 'quick-order.xlsx')]
        ];
    }

    /**
     * @param QuickAddRowCollection $collection
     */
    protected function assertValidCollection(QuickAddRowCollection $collection)
    {
        $this->assertInstanceOf('Oro\Bundle\ProductBundle\Model\QuickAddRowCollection', $collection);
        $this->assertCount($this->expectedCountAll, $collection);
        $this->assertCount($this->expectedCountValid, $collection->getValidRows());

        foreach ($collection as $i => $element) {
            $this->assertEquals($this->expectedElements[$element->getSku()], $element->getQuantity());
        }

        foreach ($collection->getValidRows() as $i => $element) {
            $this->assertContains($element->getSku(), $this->validElementSkus);
        }
    }

    /**
     * @param string $sku
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareProduct($sku)
    {
        $product = $this->createMock('Oro\Bundle\ProductBundle\Entity\Product');
        $product->expects($this->once())
            ->method('getSku')
            ->willReturn($sku);

        return $product;
    }

    private function prepareProductRepository()
    {
        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $this->productRepository->method('getProductWithNamesBySkuQueryBuilder')->willReturn($qb);
    }

    private function prepareProductManager()
    {
        $query = $this->getMockBuilder('\Doctrine\ORM\AbstractQuery')
            ->setMethods(['setParameter', 'getResult', 'execute'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('execute')
            ->willReturn([
                'HSSUC' => $this->prepareProduct('HSSUC'),
                'HSTUC' => $this->prepareProduct('HSTUC'),
            ]);

        $expr = $this->createMock(Expr::class);
        $expr->expects($this->once())
            ->method('neq')
            ->with('product.type', ':configurable_type');

        $qb = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $qb->expects($this->once())
            ->method('expr')
            ->willReturn($expr);
        $qb->expects($this->once())
            ->method('andWhere')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('configurable_type', Product::TYPE_CONFIGURABLE);
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->productManager->method('restrictQueryBuilder')->withAnyParameters()->willReturn($qb);
    }
}
