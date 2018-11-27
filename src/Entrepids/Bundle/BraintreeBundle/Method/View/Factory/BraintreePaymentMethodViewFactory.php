<?php

namespace Entrepids\Bundle\BraintreeBundle\Method\View\Factory;

use Entrepids\Bundle\BraintreeBundle\Method\Config\BraintreeConfig;
use Entrepids\Bundle\BraintreeBundle\Method\View\BraintreeView;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Symfony\Component\Form\FormFactoryInterface;

class BraintreePaymentMethodViewFactory implements BraintreePaymentMethodViewFactoryInterface
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var PaymentTransactionProvider
     */
    private $transactionProvider;

    /**
     * @param FormFactoryInterface $formFactory
     * @param PaymentTransactionProvider $transactionProvider
     */
    public function __construct(FormFactoryInterface $formFactory, PaymentTransactionProvider $transactionProvider)
    {
        $this->formFactory = $formFactory;
        $this->transactionProvider = $transactionProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function create(BraintreeConfig $config)
    {
        return new BraintreeView($this->formFactory, $config, $this->transactionProvider);
    }
}
