<?php

namespace Oro\Bundle\InventoryBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\CatalogBundle\Form\Type\CategoryType;
use Oro\Bundle\EntityBundle\Form\Type\EntityFieldFallbackValueType;

class CategoryInventoryDecrementFormExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return CategoryType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'decrementQuantity',
            EntityFieldFallbackValueType::NAME,
            [
                'label' => 'oro.inventory.decrement_inventory.label',
            ]
        );
    }
}
