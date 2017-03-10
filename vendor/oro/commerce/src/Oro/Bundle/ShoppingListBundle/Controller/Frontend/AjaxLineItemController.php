<?php

namespace Oro\Bundle\ShoppingListBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionDispatcher;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\ShoppingListBundle\Form\Type\ShoppingListType;

class AjaxLineItemController extends Controller
{
    /**
     * Add Product to Shopping List (product view form)
     *
     * @Route(
     *      "/add-product-from-view/{productId}",
     *      name="oro_shopping_list_frontend_add_product",
     *      requirements={"productId"="\d+"}
     * )
     * @AclAncestor("oro_shopping_list_frontend_update")
     * @ParamConverter("product", class="OroProductBundle:Product", options={"id" = "productId"})
     *
     * @param Request $request
     * @param Product $product
     *
     * @return JsonResponse
     */
    public function addProductFromViewAction(Request $request, Product $product)
    {
        $shoppingListManager = $this->get('oro_shopping_list.shopping_list.manager');
        $shoppingList = $shoppingListManager->getForCurrentUser($request->get('shoppingListId'));

        $parentProduct = $this->getParentProduct($request);

        $lineItem = (new LineItem())
            ->setProduct($product)
            ->setShoppingList($shoppingList)
            ->setCustomerUser($shoppingList->getCustomerUser())
            ->setOrganization($shoppingList->getOrganization());

        if ($parentProduct && $parentProduct->isConfigurable()) {
            $lineItem->setParentProduct($parentProduct);
        }

        $form = $this->createForm(FrontendLineItemType::NAME, $lineItem);

        $handler = new LineItemHandler(
            $form,
            $request,
            $this->getDoctrine(),
            $shoppingListManager
        );
        $isFormHandled = $handler->process($lineItem);

        if (!$isFormHandled) {
            return new JsonResponse(['successful' => false, 'message' => (string)$form->getErrors(true, false)]);
        }

        return new JsonResponse(
            $this->getSuccessResponse($shoppingList, $product, 'oro.shoppinglist.product.added.label')
        );
    }

    /**
     * Remove Product from Shopping List (product view form)
     *
     * @Route(
     *      "/remove-product-from-view/{productId}",
     *      name="oro_shopping_list_frontend_remove_product",
     *      requirements={"productId"="\d+"}
     * )
     * @AclAncestor("oro_shopping_list_frontend_update")
     * @ParamConverter("product", class="OroProductBundle:Product", options={"id" = "productId"})
     * @Method("POST")
     *
     * @param Request $request
     * @param Product $product
     *
     * @return JsonResponse
     */
    public function removeProductFromViewAction(Request $request, Product $product)
    {
        $shoppingListManager = $this->get('oro_shopping_list.shopping_list.manager');

        $shoppingList = $shoppingListManager->getForCurrentUser($request->get('shoppingListId'));

        $result = [
            'successful' => false,
            'message' => $this->get('translator')
                ->trans('oro.frontend.shoppinglist.lineitem.product.cant_remove.label')
        ];

        if ($shoppingList) {
            $count = $shoppingListManager->removeProduct($shoppingList, $product);

            if ($count) {
                $result = $this->getSuccessResponse(
                    $shoppingList,
                    $product,
                    'oro.frontend.shoppinglist.lineitem.product.removed.label'
                );
            }
        }

        return new JsonResponse($result);
    }

    /**
     * @Route("/{gridName}/massAction/{actionName}", name="oro_shopping_list_add_products_massaction")
     * @AclAncestor("oro_shopping_list_frontend_update")
     *
     * @param Request $request
     * @param string $gridName
     * @param string $actionName
     *
     * @return JsonResponse
     */
    public function addProductsMassAction(Request $request, $gridName, $actionName)
    {
        /** @var MassActionDispatcher $massActionDispatcher */
        $massActionDispatcher = $this->get('oro_datagrid.mass_action.dispatcher');

        $response = $massActionDispatcher->dispatchByRequest($gridName, $actionName, $request);

        $data = [
            'successful' => $response->isSuccessful(),
            'message' => $response->getMessage()
        ];

        return new JsonResponse(array_merge($data, $response->getOptions()));
    }

    /**
     * @Route("/{gridName}/massAction/{actionName}/create", name="oro_shopping_list_add_products_to_new_massaction")
     * @Template("OroShoppingListBundle:ShoppingList/Frontend:update.html.twig")
     * @AclAncestor("oro_shopping_list_frontend_update")
     *
     * @param Request $request
     * @param string $gridName
     * @param string $actionName
     *
     * @return array
     */
    public function addProductsToNewMassAction(Request $request, $gridName, $actionName)
    {
        $form = $this->createForm(ShoppingListType::NAME);
        $manager = $this->get('oro_shopping_list.shopping_list.manager');
        $response = $this->get('oro_form.model.update_handler')->handleUpdate($manager->create(), $form, [], [], null);

        if ($form->isValid()) {
            $manager->setCurrent($this->getUser(), $form->getData());

            $result = $this->get('oro_datagrid.mass_action.dispatcher')
                ->dispatchByRequest($gridName, $actionName, $request);

            $response['message'] = $result->getMessage();
        }

        return $response;
    }

    /**
     * @param ShoppingList $shoppingList
     * @param string $translationKey
     * @return string
     */
    protected function getSuccessMessage(ShoppingList $shoppingList, $translationKey)
    {
        $link = $this->get('router')->generate('oro_shopping_list_frontend_view', ['id' => $shoppingList->getId()]);

        $translator = $this->get('translator');
        $label = htmlspecialchars($shoppingList->getLabel());

        return $translator->trans(
            $translationKey,
            ['%shoppinglist%' => sprintf('<a href="%s">%s</a>', $link, $label)]
        );
    }

    /**
     * @param ShoppingList $shoppingList
     * @param Product $product
     * @param string $message
     * @return array
     */
    protected function getSuccessResponse(ShoppingList $shoppingList, Product $product, $message)
    {
        $productShoppingLists = $this->get('oro_shopping_list.data_provider.product_shopping_lists')
            ->getProductUnitsQuantity($product);

        return [
            'successful' => true,
            'message' => $this->getSuccessMessage($shoppingList, $message),
            'product' => [
                'id' => $product->getId(),
                'shopping_lists' => $productShoppingLists
            ],
            'shoppingList' => [
                'id' => $shoppingList->getId(),
                'label' => $shoppingList->getLabel()
            ]
        ];
    }

    /**
     * @param Request $request
     * @return null|Product
     */
    protected function getParentProduct(Request $request)
    {
        if ($parentProductId = $request->get('parentProductId')) {
            $doctrineHelper = $this->get('oro_entity.doctrine_helper');
            $productRepository = $doctrineHelper->getEntityRepositoryForClass(Product::class);
            return $productRepository->find($parentProductId);
        }

        return null;
    }
}
