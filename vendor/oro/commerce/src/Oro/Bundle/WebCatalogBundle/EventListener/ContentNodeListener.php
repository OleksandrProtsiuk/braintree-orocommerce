<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\WebCatalogBundle\Async\Topics;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Generator\SlugGenerator;
use Oro\Bundle\WebCatalogBundle\Model\ContentNodeMaterializedPathModifier;
use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class ContentNodeListener
{
    /**
     * @var ContentNodeMaterializedPathModifier
     */
    protected $modifier;

    /**
     * @var ExtraActionEntityStorageInterface
     */
    protected $storage;

    /**
     * @var ServiceLink
     */
    protected $slugGeneratorLink;

    /**
     * @var MessageProducerInterface
     */
    protected $messageProducer;

    /**
     * @param ContentNodeMaterializedPathModifier $modifier
     * @param ExtraActionEntityStorageInterface $storage
     * @param ServiceLink $slugGenerator
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(
        ContentNodeMaterializedPathModifier $modifier,
        ExtraActionEntityStorageInterface $storage,
        ServiceLink $slugGenerator,
        MessageProducerInterface $messageProducer
    ) {
        $this->modifier = $modifier;
        $this->storage = $storage;
        $this->slugGeneratorLink = $slugGenerator;
        $this->messageProducer = $messageProducer;
    }

    /**
     * @param ContentNode $contentNode
     */
    public function postPersist(ContentNode $contentNode)
    {
        $contentNode = $this->modifier->calculateMaterializedPath($contentNode);
        $this->storage->scheduleForExtraInsert($contentNode);
    }

    /**
     * @param ContentNode $contentNode
     * @param PreUpdateEventArgs $args
     */
    public function preUpdate(ContentNode $contentNode, PreUpdateEventArgs $args)
    {
        $changeSet = $args->getEntityChangeSet();

        if (!empty($changeSet[ContentNode::FIELD_PARENT_NODE])) {
            $this->getSlugGeneratorLink()->generate($contentNode);
            $this->modifier->calculateMaterializedPath($contentNode);
            $childNodes = $this->modifier->calculateChildrenMaterializedPath($contentNode);

            $this->storage->scheduleForExtraInsert($contentNode);
            foreach ($childNodes as $childNode) {
                $this->storage->scheduleForExtraInsert($childNode);
            }
        }
    }

    /**
     * @param ContentNode $contentNode
     */
    public function postRemove(ContentNode $contentNode)
    {
        $this->scheduleContentNodeRecalculation($contentNode);
    }

    /**
     * @param AfterFormProcessEvent $event
     */
    public function onFormAfterFlush(AfterFormProcessEvent $event)
    {
        $this->scheduleContentNodeRecalculation($event->getData());
    }

    /**
     * @return SlugGenerator
     */
    protected function getSlugGeneratorLink()
    {
        return $this->slugGeneratorLink->getService();
    }

    /**
     * @param ContentNode $contentNode
     */
    protected function scheduleContentNodeRecalculation(ContentNode $contentNode)
    {
        $this->messageProducer->send(Topics::RESOLVE_NODE_SLUGS, $contentNode->getId());
    }
}
