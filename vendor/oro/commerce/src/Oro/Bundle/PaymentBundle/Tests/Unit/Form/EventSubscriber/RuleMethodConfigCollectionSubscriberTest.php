<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\EventSubscriber;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;
use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\AddressBundle\Form\Type\RegionType;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\CurrencyBundle\Utils\CurrencyNameHelper;
use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodConfigCollectionType;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodConfigType;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleDestinationType;
use Oro\Bundle\PaymentBundle\Form\Type\PaymentMethodsConfigsRuleType;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Component\Testing\Unit\Form\EventListener\Stub\AddressCountryAndRegionSubscriberStub;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Translation\TranslatorInterface;

class RuleMethodConfigCollectionSubscriberTest extends FormIntegrationTestCase
{
    const PAYMENT_TYPE = 'payment_type_mock';
    /**
     * @var RuleMethodConfigCollectionSubscriberProxy
     */
    protected $subscriber;

    /**
     * @var PaymentMethodRegistry
     */
    protected $methodRegistry;

    public function setUp()
    {
        $this->methodRegistry = new PaymentMethodRegistry();
        $this->subscriber = new RuleMethodConfigCollectionSubscriberProxy();
        parent::setUp();
        $this->subscriber->setFactory($this->factory)->setMethodRegistry($this->methodRegistry);
    }

    public function test()
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SET_DATA => 'preSet',
                FormEvents::PRE_SUBMIT => 'preSubmit'
            ],
            RuleMethodConfigCollectionSubscriberProxy::getSubscribedEvents()
        );
    }

    public function testPreSet()
    {
        $form = $this->factory->create(PaymentMethodsConfigsRuleType::class);
        $paymentRule = new PaymentMethodsConfigsRule();
        $methodConfig = new PaymentMethodConfig();
        $methodConfig->setType(self::PAYMENT_TYPE);
        $paymentRule->addMethodConfig($methodConfig);
        $form->setData($paymentRule);
        $this->assertCount(0, $form->get('methodConfigs'));
    }

    public function testPreSubmitWithData()
    {
        $form = $this->factory->create(PaymentMethodsConfigsRuleType::class);
        $paymentRule = new PaymentMethodsConfigsRule();

        $form->submit([
            'methodConfigs' => [
                [
                    'type' => self::PAYMENT_TYPE
                ]
            ]
        ]);

        $this->assertCount(0, $paymentRule->getMethodConfigs());
        $this->assertCount(0, $form->get('methodConfigs'));
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        $roundingService = $this->getMockBuilder(RoundingServiceInterface::class)->getMock();
        $roundingService->expects($this->any())
            ->method('getPrecision')
            ->willReturn(4);
        $roundingService->expects($this->any())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        /** @var CurrencyProviderInterface|\PHPUnit_Framework_MockObject_MockObject $currencyProvider */
        $currencyProvider = $this->getMockBuilder(CurrencyProviderInterface::class)
            ->disableOriginalConstructor()->getMockForAbstractClass();
        $currencyProvider->expects($this->any())
            ->method('getCurrencyList')
            ->willReturn(['USD']);

        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatableEntityType $registry */
        $translatableEntity = $this->getMockBuilder(TranslatableEntityType::class)
            ->setMethods(['setDefaultOptions', 'buildForm'])
            ->disableOriginalConstructor()
            ->getMock();

        /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject $translator */
        $translator = $this->getMockBuilder(TranslatorInterface::class)->getMock();
        $translator->expects(static::any())
            ->method('trans')
            ->will(static::returnCallback(function ($message) {
                return $message.'_translated';
            }));

        return [
            new PreloadedExtension(
                [
                    PaymentMethodsConfigsRuleType::class
                    => new PaymentMethodsConfigsRuleType($this->methodRegistry, $translator),
                    PaymentMethodConfigCollectionType::class
                    => new PaymentMethodConfigCollectionType($this->subscriber),
                    PaymentMethodConfigType::class
                    => new PaymentMethodConfigType($this->methodRegistry, $translator),
                    CurrencySelectionType::NAME => new CurrencySelectionType(
                        $currencyProvider,
                        $this->getMockBuilder(LocaleSettings::class)->disableOriginalConstructor()->getMock(),
                        $this->getMockBuilder(CurrencyNameHelper::class)->disableOriginalConstructor()->getMock()
                    ),
                    CollectionType::NAME => new CollectionType(),
                    PaymentMethodsConfigsRuleDestinationType::NAME => new PaymentMethodsConfigsRuleDestinationType(
                        new AddressCountryAndRegionSubscriberStub()
                    ),
                    'oro_country' => new CountryType(),
                    'genemu_jqueryselect2_translatable_entity' => new Select2Type('translatable_entity'),
                    'translatable_entity' => $translatableEntity,
                    'oro_region' => new RegionType(),
                ],
                ['form' => [new AdditionalAttrExtension()]]
            ),
            $this->getValidatorExtension(true)
        ];
    }
}
