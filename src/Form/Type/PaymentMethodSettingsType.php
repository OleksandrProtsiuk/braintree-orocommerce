<?php
/**
 * Created by PhpStorm.
 * User: adamhall
 * Date: 3/9/19
 * Time: 7:46 PM
 */

namespace Aligent\BraintreeBundle\Form\Type;


use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PaymentMethodSettingsType extends AbstractType
{
    /**
     * @var FeatureChecker
     */
    protected $featureChecker;

    /**
     * PaymentMethodSettingsType constructor.
     * @param FeatureChecker $featureChecker
     */
    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'card',
            CreditCardSettingsType::class,
            [
                'label'    => 'aligent.braintree.settings.credit_card.label',
                'required' => false
            ]
        )->add(
            'paypal',
            PayPalSettingsType::class,
            [
                'label' => 'aligent.braintree.settings.paypal.label',
                'required' => false
            ]
        );

        if ($this->featureChecker->isFeatureEnabled('experimental_payment_methods')) {
            $builder->add(
                'paypalCredit',
                PayPalCreditSettingsType::class,
                [
                    'label' => 'aligent.braintree.settings.paypal_credit.label',
                    'required' => false
                ]
            )->add(
                'venmo',
                VenmoSettingsType::class,
                [
                    'label' => 'aligent.braintree.settings.venmo.label',
                    'required' => false
                ]
            )->add(
                'googlePay',
                GooglePaySettingsType::class,
                [
                    'label' => 'aligent.braintree.settings.google_pay.label',
                    'required' => false
                ]
            )->add(
                'applePay',
                ApplePaySettingsType::class,
                [
                    'label' => 'aligent.braintree.settings.apple_pay.label',
                    'required' => false
                ]
            );
        }
    }
}