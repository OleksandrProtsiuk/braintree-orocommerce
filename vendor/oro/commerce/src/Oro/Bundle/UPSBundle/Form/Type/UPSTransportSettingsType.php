<?php

namespace Oro\Bundle\UPSBundle\Form\Type;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class UPSTransportSettingsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_ups_transport_settings';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var TransportInterface
     */
    protected $transport;

    /**
     * @var ShippingOriginProvider
     */
    protected $shippingOriginProvider;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /** @var SymmetricCrypterInterface */
    protected $symmetricCrypter;

    /**
     * UPSTransportSettingsType constructor.
     *
     * @param TransportInterface        $transport
     * @param ShippingOriginProvider    $shippingOriginProvider
     * @param DoctrineHelper            $doctrineHelper
     * @param SymmetricCrypterInterface $symmetricCrypter
     */
    public function __construct(
        TransportInterface $transport,
        ShippingOriginProvider $shippingOriginProvider,
        DoctrineHelper $doctrineHelper,
        SymmetricCrypterInterface $symmetricCrypter
    ) {
        $this->transport = $transport;
        $this->shippingOriginProvider = $shippingOriginProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->symmetricCrypter = $symmetricCrypter;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws ConstraintDefinitionException
     * @throws InvalidOptionsException
     * @throws MissingOptionsException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'labels',
            LocalizedFallbackValueCollectionType::class,
            [
                'label' => 'oro.ups.transport.labels.label',
                'required' => true,
                'options' => ['constraints' => [new NotBlank()]],
            ]
        );
        $builder->add(
            'baseUrl',
            TextType::class,
            [
                'label' => 'oro.ups.transport.base_url.label',
                'required' => true
            ]
        );
        $builder->add(
            'apiUser',
            TextType::class,
            [
                'label' => 'oro.ups.transport.api_user.label',
                'required' => true
            ]
        );
        $builder->add(
            'apiPassword',
            PasswordType::class,
            [
                'label' => 'oro.ups.transport.api_password.label',
                'required' => true
            ]
        );
        $builder->get('apiPassword')
            ->addModelTransformer(new CallbackTransformer(
                function ($password) {
                    return $password;
                },
                function ($password) {
                    return $this->symmetricCrypter->encryptData($password);
                }
            ));
        $builder->add(
            'apiKey',
            TextType::class,
            [
                'label' => 'oro.ups.transport.api_key.label',
                'required' => true
            ]
        );
        $builder->add(
            'shippingAccountName',
            TextType::class,
            [
                'label' => 'oro.ups.transport.shipping_account_name.label',
                'required' => true
            ]
        );
        $builder->add(
            'shippingAccountNumber',
            TextType::class,
            [
                'label' => 'oro.ups.transport.shipping_account_number.label',
                'required' => true
            ]
        );
        $builder->add(
            'pickupType',
            ChoiceType::class,
            [
                'label' => 'oro.ups.transport.pickup_type.label',
                'required' => true,
                'choices' => [
                    UPSTransport::PICKUP_TYPE_REGULAR_DAILY => 'oro.ups.transport.pickup_type.regular_daily.label',
                    UPSTransport::PICKUP_TYPE_CUSTOMER_COUNTER =>
                        'oro.ups.transport.pickup_type.customer_counter.label',
                    UPSTransport::PICKUP_TYPE_ONE_TIME => 'oro.ups.transport.pickup_type.one_time.label',
                    UPSTransport::PICKUP_TYPE_ON_CALL_AIR => 'oro.ups.transport.pickup_type.on_call_air.label',
                    UPSTransport::PICKUP_TYPE_LETTER_CENTER => 'oro.ups.transport.pickup_type.letter_center.label',
                ]
            ]
        );
        $builder->add(
            'unitOfWeight',
            ChoiceType::class,
            [
                'label' => 'oro.ups.transport.unit_of_weight.label',
                'required' => true,
                'choices' => [
                    UPSTransport::UNIT_OF_WEIGHT_LBS => 'oro.ups.transport.unit_of_weight.lbs.label',
                    UPSTransport::UNIT_OF_WEIGHT_KGS => 'oro.ups.transport.unit_of_weight.kgs.label'
                ]
            ]
        );
        $builder->add(
            'country',
            CountryType::class,
            [
                'label' => 'oro.ups.transport.country.label',
                'required' => true,
            ]
        );
        $builder->add(
            'applicableShippingServices',
            'entity',
            [
                'label' => 'oro.ups.transport.shipping_service.plural_label',
                'required' => true,
                'multiple' => true,
                'class' => 'Oro\Bundle\UPSBundle\Entity\ShippingService',
            ]
        );
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    /**
     * @param FormEvent $event
     * @throws NotManageableEntityException
     */
    public function onPreSetData(FormEvent $event)
    {
        /** @var UPSTransport $transport */
        $transport = $event->getData();

        if ($transport && null === $transport->getCountry()) {
            $countryCode = $this
                ->shippingOriginProvider
                ->getSystemShippingOrigin()
                ->getCountry();

            $country = $this->getCountry($countryCode);
            if (null !== $country) {
                $transport->setCountry($country);
                $event->setData($transport);
            }
        }
    }

    /**
     * @param string $iso2Code
     * @throws NotManageableEntityException
     * @return Country|null
     */
    protected function getCountry($iso2Code)
    {
        $repo = $this->doctrineHelper
            ->getEntityManagerForClass('OroAddressBundle:Country')
            ->getRepository('OroAddressBundle:Country');

        return $repo->findOneBy(['iso2Code' => $iso2Code]);
    }

    /**
     * {@inheritdoc}
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass ?: $this->transport->getSettingsEntityFQCN()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }
}
