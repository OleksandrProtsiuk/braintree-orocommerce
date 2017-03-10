<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type\Stub;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\CustomerBundle\Form\DataTransformer\AddressTypeDefaultTransformer;

class CustomerTypedAddressWithDefaultTypeStub extends AbstractType
{
    const NAME = 'oro_customer_typed_address_with_default';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var array
     */
    protected $types;

    /**
     * @param array $types
     * @param EntityManager $em
     */
    public function __construct(array $types, EntityManager $em)
    {
        $this->types = $types;
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = [];
        /** @var AddressType $type */
        foreach ($this->types as $type) {
            $choices[$type->getName()] = 'Default' . $type->getName();
        }

        $builder->add('default', 'choice', [
            'choices'  => $choices,
            'multiple' => true,
            'expanded' => true,
            'label'    => false,
        ])
        ->addViewTransformer(new AddressTypeDefaultTransformer($this->em));
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'em'       => null,
            'property' => null
        ]);

        $resolver->setRequired([
            'class'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
