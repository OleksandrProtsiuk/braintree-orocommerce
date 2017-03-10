<?php

namespace Oro\Bundle\UPSBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Method\UPSShippingMethod;
use Oro\Bundle\UPSBundle\Provider\ChannelType;

class UPSTransportEntityListener
{
    /**
     * @param UPSTransport $transport
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(UPSTransport $transport, LifecycleEventArgs $args)
    {
        /** @var PersistentCollection $services */
        $services = $transport->getApplicableShippingServices();
        $deletedServices = $services->getDeleteDiff();
        if (0 !== count($deletedServices)) {
            $deleted = [];
            /** @var ShippingService $deletedService */
            foreach ($deletedServices as $deletedService) {
                $deleted[] = $deletedService->getCode();
            }
            $entityManager = $args->getEntityManager();
            $channel = $entityManager
                ->getRepository('OroIntegrationBundle:Channel')
                ->findOneBy(['type' => ChannelType::TYPE, 'transport' => $transport->getId()]);

            if (null !== $channel) {
                $shippingMethodIdentifier = UPSShippingMethod::IDENTIFIER . '_' . $channel->getId();
                $configuredMethods = $entityManager
                    ->getRepository('OroShippingBundle:ShippingMethodConfig')
                    ->findBy(['method' => $shippingMethodIdentifier ]);
                if (0 < count($configuredMethods)) {
                    $types = $entityManager
                        ->getRepository('OroShippingBundle:ShippingMethodTypeConfig')
                        ->findBy(['methodConfig' => $configuredMethods, 'type' => $deleted]);

                    foreach ($types as $type) {
                        $entityManager->getRepository('OroShippingBundle:ShippingMethodTypeConfig')
                            ->deleteByMethodAndType($type->getMethodConfig(), $type->getType());
                    }
                }
            }
        }
    }
}
