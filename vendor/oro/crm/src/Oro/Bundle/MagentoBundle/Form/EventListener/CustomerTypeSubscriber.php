<?php

namespace Oro\Bundle\MagentoBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\MagentoBundle\Entity\Customer;

class CustomerTypeSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT  => 'preSubmit'
        ];
    }

    /**
     * @param FormEvent $formEvent
     */
    public function preSubmit(FormEvent $formEvent)
    {
        /** @var Customer $entity */
        $entity = $formEvent->getForm()->getData();

        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        if (!$entity->getCreatedAt()) {
            $entity->setCreatedAt($date);
        }

        $entity->setUpdatedAt($date);
    }
}
