<?php

namespace Oro\Bundle\OrderBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\ImportExportBundle\Serializer\Serializer;
use Oro\Bundle\LocaleBundle\Formatter\AddressFormatter;
use Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Entity\AbstractDefaultTypedAddress;
use Oro\Bundle\OrderBundle\Manager\OrderAddressManager;
use Oro\Bundle\OrderBundle\Provider\OrderAddressSecurityProvider;

abstract class AbstractOrderAddressType extends AbstractType
{

    /** @var string */
    protected $dataClass;

    /** @var AddressFormatter */
    protected $addressFormatter;

    /** @var OrderAddressManager */
    protected $orderAddressManager;

    /** @var OrderAddressSecurityProvider */
    protected $orderAddressSecurityProvider;

    /** @var Serializer */
    protected $serializer;

    /**
     * @param AddressFormatter $addressFormatter
     * @param OrderAddressManager $orderAddressManager
     * @param OrderAddressSecurityProvider $orderAddressSecurityProvider
     * @param Serializer $serializer
     */
    public function __construct(
        AddressFormatter $addressFormatter,
        OrderAddressManager $orderAddressManager,
        OrderAddressSecurityProvider $orderAddressSecurityProvider,
        Serializer $serializer
    ) {
        $this->addressFormatter = $addressFormatter;
        $this->orderAddressManager = $orderAddressManager;
        $this->orderAddressSecurityProvider = $orderAddressSecurityProvider;
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $type = $options['addressType'];
        $order = $options['object'];
        $isEditEnabled = $options['isEditEnabled'];

        $isManualEditGranted = $this->orderAddressSecurityProvider->isManualEditGranted($type);
        $this->initCustomerAddressField($builder, $type, $order, $isManualEditGranted, $isEditEnabled);

        $builder->add('phone', 'text', ['required' => false]);

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) use ($isManualEditGranted) {
                if (!$isManualEditGranted) {
                    $event->setData(null);
                }

                $form = $event->getForm();
                if (!$form->has('customerAddress')) {
                    return;
                }

                $identifier = $form->get('customerAddress')->getData();
                if ($identifier === null) {
                    return;
                }

                //Enter manually or Customer/CustomerUser address
                $orderAddress = $event->getData();

                $address = null;
                if ($identifier) {
                    $address = $this->orderAddressManager->getEntityByIdentifier($identifier);
                }

                if ($orderAddress || $address) {
                    $event->setData($this->orderAddressManager->updateFromAbstract($address, $orderAddress));
                }
            },
            -10
        );
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $isManualEditGranted = $this->orderAddressSecurityProvider->isManualEditGranted($options['addressType']);

        foreach ($view->children as $child) {
            $child->vars['disabled'] = !$isManualEditGranted || $options['disabled'];
        }

        if ($view->offsetExists('customerAddress')) {
            $view->offsetGet('customerAddress')->vars['disabled'] = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired(['object', 'addressType'])
            ->setDefaults([
                'data_class' => $this->dataClass,
                'isEditEnabled' => true,
            ])
            ->setAllowedValues('addressType', [AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING])
            ->setAllowedTypes('object', 'Oro\Bundle\CustomerBundle\Entity\CustomerOwnerAwareInterface');
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param array $addresses
     *
     * @return array
     */
    protected function getChoices(array $addresses = [])
    {
        array_walk_recursive(
            $addresses,
            function (&$item) {
                if ($item instanceof AbstractAddress) {
                    $item = $this->addressFormatter->format($item, null, ', ');
                }

                return $item;
            }
        );

        return $addresses;
    }

    /**
     * @param CustomerOwnerAwareInterface $entity
     * @param string $type
     * @param array $addresses
     *
     * @return null|string
     */
    protected function getDefaultAddressKey(CustomerOwnerAwareInterface $entity, $type, array $addresses)
    {
        if (!$addresses) {
            return null;
        }

        $addresses = call_user_func_array('array_merge', array_values($addresses));
        $customerUser = $entity->getCustomerUser();
        $addressKey = null;

        /** @var AbstractDefaultTypedAddress $address */
        foreach ($addresses as $key => $address) {
            if ($address->hasDefault($type)) {
                $addressKey = $key;
                if ($address instanceof CustomerUserAddress &&
                    $address->getFrontendOwner()->getId() === $customerUser->getId()
                ) {
                    break;
                }
            }
        }

        return $addressKey;
    }

    /**
     * @param array $addresses
     *
     * @return array
     */
    protected function getPlainData(array $addresses = [])
    {
        $data = [];

        array_walk_recursive(
            $addresses,
            function ($item, $key) use (&$data) {
                if ($item instanceof AbstractAddress) {
                    $data[$key] = $this->serializer->normalize($item);
                }
            }
        );

        return $data;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param string $type - address type
     * @param CustomerOwnerAwareInterface $entity
     * @param bool $isManualEditGranted
     * @param bool $isEditEnabled
     *
     * @return bool
     */
    abstract protected function initCustomerAddressField(
        FormBuilderInterface $builder,
        $type,
        CustomerOwnerAwareInterface $entity,
        $isManualEditGranted,
        $isEditEnabled
    );
}
