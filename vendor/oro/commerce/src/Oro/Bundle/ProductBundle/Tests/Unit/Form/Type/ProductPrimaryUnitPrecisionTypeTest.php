<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Extension\IntegerExtension;
use Oro\Bundle\ProductBundle\Form\Type\ProductPrimaryUnitPrecisionType;
use Oro\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use Oro\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use Oro\Bundle\ProductBundle\Provider\SystemDefaultProductUnitProvider;
use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Validation;

class ProductPrimaryUnitPrecisionTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision';

    /**
     * @var ProductPrimaryUnitPrecisionType
     */
    protected $formType;

    /**
     * @var array
     */
    protected $units = ['item', 'kg'];

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SingleUnitModeService
     */
    protected $singleUnitModeService;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SystemDefaultProductUnitProvider
     */
    protected $defaultProductUnitProvider;

    /**
     * @var ProductUnitLabelFormatter
     */
    protected $productUnitLabelFormatter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $this->singleUnitModeService = $this->getMockBuilder(SingleUnitModeService::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->defaultProductUnitProvider = $this->getMockBuilder(SystemDefaultProductUnitProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translator
            ->expects(static::any())
            ->method('trans')
            ->willReturnCallback(
                function ($id, array $params) {
                    return isset($params['{title}']) ? $id . ':' . $params['{title}'] : $id;
                }
            );
        $this->productUnitLabelFormatter = new ProductUnitLabelFormatter($this->translator);
        $this->formType = new ProductPrimaryUnitPrecisionType();
        $this->formType->setDataClass(self::DATA_CLASS);
        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $entityType = new EntityType($this->prepareChoices());
        return [
            new PreloadedExtension(
                [
                    ProductUnitSelectionType::NAME => new ProductUnitSelectionType(
                        $this->productUnitLabelFormatter,
                        $this->translator,
                        $this->singleUnitModeService,
                        $this->defaultProductUnitProvider
                    ),
                    'entity' => $entityType
                ],
                [
                    'form' => [new IntegerExtension()]
                ]
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @param ProductUnitPrecision $defaultData
     * @param array $expectedOptions
     * @param array|ProductUnitPrecision $submittedData
     * @param ProductUnitPrecision $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit(
        ProductUnitPrecision $defaultData,
        array $expectedOptions,
        $submittedData,
        ProductUnitPrecision $expectedData
    ) {
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $this->assertFormConfig($expectedOptions['unit'], $form->get('unit')->getConfig());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @param array $expectedConfig
     * @param FormConfigInterface $actualConfig
     */
    protected function assertFormConfig(array $expectedConfig, FormConfigInterface $actualConfig)
    {
        foreach ($expectedConfig as $key => $value) {
            $this->assertTrue($actualConfig->hasOption($key));
            $this->assertEquals($value, $actualConfig->getOption($key));
        }
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'existing unit precision' => [
                'defaultData'   => (new ProductUnitPrecision())
                    ->setUnit((new ProductUnit())->setCode('kg'))
                    ->setPrecision(0),
                'expectedOptions' => [
                    'unit' => [
                        'attr' => [
                            'class' => 'unit'
                        ]
                    ]
                ],
                'submittedData' => [],
                'expectedData'  => (new ProductUnitPrecision())
                    ->setSell(false)
            ],
            'unit precision without value' => [
                'defaultData'   => new ProductUnitPrecision(),
                'expectedOptions' => [
                    'unit' => [],
                ],
                'submittedData' => [],
                'expectedData'  => (new ProductUnitPrecision())
                    ->setSell(false)
            ],
            'unit precision with value' => [
                'defaultData'   => new ProductUnitPrecision(),
                'expectedOptions' => [
                    'unit' => [],
                ],
                'submittedData' => [
                    'unit' => 'kg',
                    'precision' => 5,
                    'sell' => true
                ],
                'expectedData'  => (new ProductUnitPrecision())
                    ->setUnit((new ProductUnit())->setCode('kg'))
                    ->setPrecision(5)
                    ->setSell(true)
            ]
        ];
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(ProductPrimaryUnitPrecisionType::NAME, $this->formType->getName());
    }

    /**
     * @return array
     */
    protected function prepareChoices()
    {
        $choices = [];
        foreach ($this->units as $unitCode) {
            $unit = new ProductUnit();
            $unit->setCode($unitCode);
            $choices[$unitCode] = $unit;
        }

        return $choices;
    }
}
