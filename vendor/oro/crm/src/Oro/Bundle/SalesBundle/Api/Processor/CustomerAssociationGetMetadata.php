<?php

namespace Oro\Bundle\SalesBundle\Api\Processor;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

use Oro\Bundle\ApiBundle\Processor\GetMetadata\MetadataContext;
use Oro\Bundle\SalesBundle\Provider\Customer\ConfigProvider;

class CustomerAssociationGetMetadata implements ProcessorInterface
{
    /** @var ConfigProvider */
    protected $configProvider;

    /** @var string */
    protected $customerAssociationField;

    /**
     * @param ConfigProvider $provider
     * @param string         $customerAssociationField
     */
    public function __construct(ConfigProvider $provider, $customerAssociationField)
    {
        $this->configProvider           = $provider;
        $this->customerAssociationField = $customerAssociationField;
    }

    /**
     * Sets proper acceptable customer target classes for customer association
     *
     * Sets inherited type true to customer association target metadata
     * to determine target customer type from target customer object
     *
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var MetadataContext $context */

        $entityMetadata = $context->getResult();
        if (null === $entityMetadata) {
            // metadata is not loaded
            return;
        }

        $targetAssociationMetadata = $entityMetadata->getAssociation($this->customerAssociationField);
        if (!$targetAssociationMetadata) {
            return;
        }
        $targetAssociationMetadata->setAcceptableTargetClassNames(
            $this->configProvider->getCustomerClasses()
        );
        $targetEntityMetadata = $targetAssociationMetadata->getTargetMetadata();
        if (!$targetEntityMetadata) {
            return;
        }

        $targetEntityMetadata->setInheritedType(true);
    }
}
