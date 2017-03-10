<?php

namespace Oro\Bundle\PaymentTermBundle\Method;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

class PaymentTerm implements PaymentMethodInterface, LoggerAwareInterface
{
    const TYPE = 'payment_term';

    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

    /** @var PaymentTermAssociationProvider */
    protected $paymentTermAssociationProvider;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    use LoggerAwareTrait;

    /**
     * @param PaymentTermProvider $paymentTermProvider
     * @param PaymentTermAssociationProvider $paymentTermAssociationProvider
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        PaymentTermProvider $paymentTermProvider,
        PaymentTermAssociationProvider $paymentTermAssociationProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->paymentTermAssociationProvider = $paymentTermAssociationProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /** {@inheritdoc} */
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        $entity = $this->doctrineHelper->getEntityReference(
            $paymentTransaction->getEntityClass(),
            $paymentTransaction->getEntityIdentifier()
        );

        if (!$entity) {
            return [];
        }

        $paymentTerm = $this->paymentTermProvider->getCurrentPaymentTerm();

        if (!$paymentTerm) {
            return [];
        }

        try {
            $this->paymentTermAssociationProvider->setPaymentTerm($entity, $paymentTerm);
            $this->doctrineHelper->getEntityManager($entity)->flush($entity);
        } catch (NoSuchPropertyException $e) {
            if (null !== $this->logger) {
                $this->logger->error(
                    'Property association {paymentTermClass} not found for entity {entityClass}',
                    [
                        'exception' => $e,
                        'paymentTermClass' => get_class($paymentTerm),
                        'entityClass' => get_class($entity),
                    ]
                );
            }

            return [];
        }

        $paymentTransaction
            ->setSuccessful(true)
            ->setActive(false);

        return [];
    }

    /** {@inheritdoc} */
    public function getType()
    {
        return self::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(PaymentContextInterface $context)
    {
        if ($context->getCustomer()) {
            return (bool)$this->paymentTermProvider->getPaymentTerm($context->getCustomer());
        }
        return false;
    }

    /** {@inheritdoc} */
    public function supports($actionName)
    {
        return $actionName === self::PURCHASE;
    }
}
