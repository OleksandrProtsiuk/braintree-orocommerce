<?php

namespace Entrepids\Bundle\BraintreeBundle\EventListener\Callback;

use Psr\Log\LoggerAwareTrait;

use Entrepids\Bundle\BraintreeBundle\Method\Braintree;
use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry;

class BraintreeCheckoutListener {
	use LoggerAwareTrait;
	
	/** @var PaymentMethodRegistry */
	protected $paymentMethodRegistry;
	
	/**
	 * @param PaymentMethodRegistry $paymentMethodRegistry
	 */
	public function __construct(PaymentMethodRegistry $paymentMethodRegistry)
	{
		$this->paymentMethodRegistry = $paymentMethodRegistry;
	}
	
	/**
	 * @param AbstractCallbackEvent $event
	 */
	public function onError(AbstractCallbackEvent $event)
	{
		$paymentTransaction = $event->getPaymentTransaction();
	
		if (!$paymentTransaction) {
			return;
		}
	
		$paymentTransaction
		->setSuccessful(false)
		->setActive(false);
	}
	
	/**
	 * @param AbstractCallbackEvent $event
	 */
	public function onReturn(AbstractCallbackEvent $event)
	{
		$paymentTransaction = $event->getPaymentTransaction();
		$data = $event->getData();
	
		// TODO: BB-3693 Will use typed Response
		if (!$paymentTransaction || !isset($data['PayerID']) ||
				!isset($data['token']) || $data['token'] !== $paymentTransaction->getReference()
		) {
			return;
		}
	
		$paymentTransaction
		->setResponse(array_replace($paymentTransaction->getResponse(), $data));
	
		try {
			$this->paymentMethodRegistry
			->getPaymentMethod($paymentTransaction->getPaymentMethod())
			->execute(Braintree::COMPLETE, $paymentTransaction);
			$event->markSuccessful();
		} catch (\InvalidArgumentException $e) {
			if ($this->logger) {
				// do not expose sensitive data in context
				$this->logger->error($e->getMessage(), []);
			}
		}
	}	
}