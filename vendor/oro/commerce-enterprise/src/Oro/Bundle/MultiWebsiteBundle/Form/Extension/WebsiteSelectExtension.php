<?php

namespace Oro\Bundle\MultiWebsiteBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class WebsiteSelectExtension extends AbstractTypeExtension
{
    /**
     * @var string
     */
    protected $extendedType;

    /**
     * @var string
     */
    protected $label;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'website',
            'entity',
            [
                'class' => 'Oro\Bundle\WebsiteBundle\Entity\Website',
                'label' => $this->label,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return $this->extendedType;
    }

    /**
     * @param string $extendedType
     * @return $this
     */
    public function setExtendedType($extendedType)
    {
        $this->extendedType = $extendedType;

        return $this;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }
}
