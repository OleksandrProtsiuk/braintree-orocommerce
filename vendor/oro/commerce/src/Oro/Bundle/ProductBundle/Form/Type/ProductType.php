<?php

namespace Oro\Bundle\ProductBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints\NotBlank;

use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\DefaultProductUnitProviderInterface;

class ProductType extends AbstractType
{
    const NAME = 'oro_product';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var DefaultProductUnitProviderInterface
     */
    private $provider;

    /**
     * @param DefaultProductUnitProviderInterface $provider
     */
    public function __construct(DefaultProductUnitProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('sku', 'text', ['required' => true, 'label' => 'oro.product.sku.label'])
            ->add('status', ProductStatusType::NAME, ['label' => 'oro.product.status.label'])
            ->add(
                'inventory_status',
                'oro_enum_select',
                [
                    'label'     => 'oro.product.inventory_status.label',
                    'enum_code' => 'prod_inventory_status',
                    'configs'   => ['allowClear' => false]
                ]
            )
            ->add(
                'names',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'oro.product.names.label',
                    'required' => true,
                    'options' => ['constraints' => [new NotBlank(['message' => 'oro.product.names.blank'])]],
                ]
            )
            ->add(
                'descriptions',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'oro.product.descriptions.label',
                    'required' => false,
                    'field' => 'text',
                    'type' => OroRichTextType::NAME,
                    'options' => [
                        'wysiwyg_options' => [
                            'statusbar' => true,
                            'resize' => true,
                            'width' => 500,
                            'height' => 300,
                        ],
                    ],
                ]
            )
            ->add(
                'shortDescriptions',
                LocalizedFallbackValueCollectionType::NAME,
                [
                    'label' => 'oro.product.short_descriptions.label',
                    'required' => false,
                    'field' => 'text',
                    'type' => OroRichTextType::NAME,
                    'options' => [
                        'wysiwyg_options' => [
                            'statusbar' => true,
                            'resize' => true,
                            'width' => 500,
                            'height' => 300,
                        ]
                    ]
                ]
            )
            ->add(
                'primaryUnitPrecision',
                ProductPrimaryUnitPrecisionType::NAME,
                [
                    'label'          => 'oro.product.primary_unit_precision.label',
                    'tooltip'        => 'oro.product.form.tooltip.unit_precision',
                    'error_bubbling' => false,
                    'required'       => true,
                    'mapped'         => false,
                ]
            )
            ->add(
                'additionalUnitPrecisions',
                ProductUnitPrecisionCollectionType::NAME,
                [
                    'label'          => 'oro.product.additional_unit_precisions.label',
                    'tooltip'        => 'oro.product.form.tooltip.unit_precision',
                    'error_bubbling' => false,
                    'required'       => false,
                    'mapped'         => false,
                ]
            )
            ->add(
                'variantFields',
                ProductCustomVariantFieldsCollectionType::NAME,
                [
                    'label' => 'oro.product.variant_fields.label',
                    'tooltip' => 'oro.product.form.tooltip.variant_fields',
                ]
            )
            ->add(
                'images',
                ProductImageCollectionType::NAME,
                ['required' => false]
            )
            ->add('type', HiddenType::class)
            ->add(
                'slugPrototypes',
                LocalizedSlugType::NAME,
                [
                    'label'    => 'oro.product.slug_prototypes.label',
                    'required' => false,
                    'options'  => ['constraints' => [new UrlSafe()]],
                    'source_field' => 'names',
                ]
            )
            ->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetDataListener'])
            ->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSetDataListener'])
            ->addEventListener(FormEvents::SUBMIT, [$this, 'submitListener']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSetDataListener(FormEvent $event)
    {
        /** @var Product $product */
        $product = $event->getData();
        $form = $event->getForm();

        if ($product->getId() == null) {
            $form->remove('primaryUnitPrecision');
            $form->add(
                'primaryUnitPrecision',
                ProductPrimaryUnitPrecisionType::NAME,
                [
                    'label'          => 'oro.product.primary_unit_precision.label',
                    'tooltip'        => 'oro.product.form.tooltip.unit_precision',
                    'error_bubbling' => false,
                    'required'       => true,
                    'data'           => $this->provider->getDefaultProductUnitPrecision()
                ]
            );
        }

        if ($product instanceof Product && $product->getId() && $product->isConfigurable()) {
            $form
                ->add(
                    'variantLinks',
                    ProductVariantLinksType::NAME,
                    ['product_class' => $this->dataClass, 'by_reference' => false]
                );
        }
    }

    /**
     * @param FormEvent $event
     */
    public function postSetDataListener(FormEvent $event)
    {
        /** @var Product $product */
        $product = $event->getData();
        $form = $event->getForm();

        // manual mapping
        $precisionForm = $form->get('primaryUnitPrecision');
        if (empty($precisionForm->getData())) {
            // clone is required to prevent data modification by reference
            $precisionForm->setData(clone $product->getPrimaryUnitPrecision());
        }
        $form->get('additionalUnitPrecisions')->setData($product->getAdditionalUnitPrecisions());
    }

    /**
     * @param FormEvent $event
     */
    public function submitListener(FormEvent $event)
    {
        /** @var Product $product */
        $product = $event->getData();
        $form = $event->getForm();

        $primaryPrecision = $form->get('primaryUnitPrecision')->getData();
        if ($primaryPrecision) {
            $product->setPrimaryUnitPrecision($primaryPrecision);
        }

        /** @var ProductUnitPrecision[] $additionalPrecisions */
        $additionalPrecisions = $form->get('additionalUnitPrecisions')->getData();
        foreach ($additionalPrecisions as $key => $precision) {
            $existingPrecision = $product->getUnitPrecision($precision->getProductUnitCode());
            if ($existingPrecision) {
                // refresh precision object data to prevent problems with property accessor
                $product->addAdditionalUnitPrecision($precision);
                $additionalPrecisions[$key] = $existingPrecision;
            }
        }
        PropertyAccess::createPropertyAccessor()->setValue($product, 'additionalUnitPrecisions', $additionalPrecisions);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass,
            'intention' => 'product',
            'extra_fields_message' => 'This form should not contain extra fields: "{{ extra_fields }}"',
            'enable_attributes' => true,
            'enable_attribute_family' => true,
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
