<?php

namespace Oro\Bundle\WebCatalogBundle\JsTree;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\Repository\ContentNodeRepository;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Component\Tree\Handler\AbstractTreeHandler;

/**
 * @method ContentNodeRepository getEntityRepository()
 */
class ContentNodeTreeHandler extends AbstractTreeHandler
{
    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @param string $entityClass
     * @param ManagerRegistry $managerRegistry
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct($entityClass, ManagerRegistry $managerRegistry, LocalizationHelper $localizationHelper)
    {
        parent::__construct($entityClass, $managerRegistry);

        $this->localizationHelper = $localizationHelper;
    }

    /**
     * @param ContentNode $entity
     *
     * {@inheritdoc}
     */
    protected function formatEntity($entity)
    {
        $titleValue = $this->localizationHelper->getFirstNonEmptyLocalizedValue($entity->getTitles());
        return [
            'id'     => $entity->getId(),
            'parent' => $entity->getParentNode() ? $entity->getParentNode()->getId() : null,
            'text'   => $titleValue ? $titleValue->getString() : '',
            'state'  => [
                'opened' => $entity->getParentNode() === null
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function moveProcessing($entityId, $parentId, $position)
    {
        /** @var ContentNodeRepository $entityRepository */
        $entityRepository = $this->getEntityRepository();

        /** @var ContentNode $node */
        $node = $entityRepository->find($entityId);
        /** @var ContentNode $parentNode */
        $parentNode = $entityRepository->find($parentId);

        if (null === $parentNode) {
            $node->setParentNode(null);
        } else {
            if ($parentNode->getChildNodes()->contains($node)) {
                $parentNode->removeChildNode($node);
            }

            $parentNode->addChildNode($node);

            if ($position) {
                $children = array_values($parentNode->getChildNodes()->toArray());
                $entityRepository->persistAsNextSiblingOf($node, $children[$position - 1]);
            } else {
                $entityRepository->persistAsFirstChildOf($node, $parentNode);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createTree($root = null, $includeRoot = true)
    {
        if (!$root) {
            return [];
        }

        return parent::createTree($root, $includeRoot);
    }

    /**
     * @param WebCatalog $webCatalog
     * @return ContentNode
     */
    public function getTreeRootByWebCatalog(WebCatalog $webCatalog)
    {
        return $this->getEntityRepository()->getRootNodeByWebCatalog($webCatalog);
    }
}
