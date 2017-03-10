<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductPageVariantType;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant\Stub\ContentVariantStub;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;
use Symfony\Component\Form\PreloadedExtension;

class ProductPageVariantTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var ProductPageVariantType
     */
    protected $type;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->type = new ProductPageVariantType($this->registry);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->type);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    ScopeCollectionType::NAME => new ScopeCollectionTypeStub(),
                    ProductSelectType::NAME => new EntityType(
                        [
                            1 => $this->getEntity(Product::class, ['id' => 1]),
                            2 => $this->getEntity(Product::class, ['id' => 2]),
                        ],
                        ProductSelectType::NAME
                    )
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    public function testBuildForm()
    {
        $this->assertMetadataCall();
        $form = $this->factory->create($this->type, null, ['web_catalog' => null]);

        $this->assertTrue($form->has('productPageProduct'));
        $this->assertTrue($form->has('scopes'));
        $this->assertTrue($form->has('type'));
    }

    public function testGetName()
    {
        $this->assertEquals(ProductPageVariantType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ProductPageVariantType::NAME, $this->type->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param ContentVariantInterface $existingData
     * @param array $submittedData
     * @param int $expectedProductId
     * @param bool $isDefault
     */
    public function testSubmit(ContentVariantInterface $existingData, $submittedData, $expectedProductId, $isDefault)
    {
        $this->assertMetadataCall();
        $form = $this->factory->create($this->type, $existingData, ['web_catalog' => null]);

        $this->assertEquals($existingData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        /** @var ContentVariantStub $actualData */
        $actualData = $form->getData();

        $this->assertEquals('product_page', $actualData->getType());
        $this->assertEquals($expectedProductId, $actualData->getProductPageProduct()->getId());

        $this->assertEquals($isDefault, $actualData->isDefault());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        /** @var Product $product1 */
        $product1 = $this->getEntity(Product::class, ['id' => 1]);

        return [
            'new entity' => [
                'existingData' => new ContentVariantStub(),
                'submittedData' => [
                    'productPageProduct' => 1
                ],
                'expectedProductId' => 1,
                'isDefault' => false
            ],
            'existing entity' => [
                'existingData' => (new ContentVariantStub())
                    ->setProductPageProduct($product1)
                    ->setType(ProductPageContentVariantType::TYPE),
                'submittedData' => [
                    'productPageProduct' => 2,
                    'type' => 'fakeType',
                    'default' => true
                ],
                'expectedProductId' => 2,
                'isDefault' => true
            ],
        ];
    }

    protected function assertMetadataCall()
    {
        /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $catalogMetadata */
        $catalogMetadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $catalogMetadata->expects($this->once())
            ->method('getName')
            ->willReturn(ContentVariantStub::class);

        /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $variantMetadata */
        $variantMetadata = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $variantMetadata->expects($this->once())
            ->method('getName')
            ->willReturn(ContentVariantStub::class);

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->any())
            ->method('getClassMetadata')
            ->withConsecutive(
                [WebCatalogInterface::class],
                [ContentVariantInterface::class]
            )
            ->willReturnOnConsecutiveCalls(
                $catalogMetadata,
                $variantMetadata
            );
        $this->registry->expects($this->any())
            ->method('getManager')
            ->willReturn($em);
    }
}
