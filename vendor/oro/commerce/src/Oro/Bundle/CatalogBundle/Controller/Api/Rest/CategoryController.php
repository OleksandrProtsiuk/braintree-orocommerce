<?php

namespace Oro\Bundle\CatalogBundle\Controller\Api\Rest;

use Symfony\Component\HttpFoundation\Response;

use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\NamePrefix;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;

/**
 * @NamePrefix("oro_api_")
 */
class CategoryController extends RestController implements ClassResourceInterface
{
    /**
     * @ApiDoc(
     *      description="Delete catalog category",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_catalog_category_delete",
     *      type="entity",
     *      class="OroCatalogBundle:Category",
     *      permission="DELETE"
     * )
     *
     * @param int $id
     * @return Response
     */
    public function deleteAction($id)
    {
        $manager = $this->getManager();
        /** @var CategoryRepository $repository */
        $repository = $manager->getRepository('OroCatalogBundle:Category');

        /** @var Category $category */
        $category = $manager->find($id);
        if (!$category->getParentCategory() && $category == $repository->getMasterCatalogRoot()) {
            throw new \LogicException('Master catalog root can not be removed');
        }

        return $this->handleDeleteRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_catalog.category.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \LogicException('This method should not be called');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \LogicException('This method should not be called');
    }
}
