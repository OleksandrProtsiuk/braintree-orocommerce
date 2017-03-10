<?php

namespace Oro\Bundle\ShoppingListBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class LineItemRepository extends EntityRepository
{
    /**
     * Find line item with the same product and unit
     *
     * @param LineItem $lineItem
     *
     * @return LineItem
     */
    public function findDuplicate(LineItem $lineItem)
    {
        $qb = $this->createQueryBuilder('li')
            ->where('li.product = :product')
            ->andWhere('li.unit = :unit')
            ->andWhere('li.shoppingList = :shoppingList')
            ->setParameter('product', $lineItem->getProduct())
            ->setParameter('unit', $lineItem->getUnit())
            ->setParameter('shoppingList', $lineItem->getShoppingList());

        if ($lineItem->getId()) {
            $qb
                ->andWhere('li.id != :currentId')
                ->setParameter('currentId', $lineItem->getId());
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array|Product $products
     * @param CustomerUser $customerUser
     * @return array|LineItem[]
     */
    public function getProductItemsWithShoppingListNames($products, $customerUser)
    {
        $qb = $this->createQueryBuilder('li')
            ->select('li, shoppingList')
            ->join('li.shoppingList', 'shoppingList')
            ->andWhere('li.customerUser = :customerUser')
            ->andWhere('li.product IN (:products)')
            ->setParameter('products', $products)
            ->setParameter('customerUser', $customerUser);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param ShoppingList $shoppingList
     * @return array|LineItem[]
     */
    public function getItemsWithProductByShoppingList(ShoppingList $shoppingList)
    {
        $qb = $this->createQueryBuilder('li')
            ->select('li, product, names')
            ->join('li.product', 'product')
            ->join('product.names', 'names')
            ->where('li.shoppingList = :shoppingList')
            ->setParameter('shoppingList', $shoppingList);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param ShoppingList $shoppingList
     * @param Product[] $products
     * @return array|LineItem[]
     */
    public function getItemsByShoppingListAndProducts(ShoppingList $shoppingList, $products)
    {
        $qb = $this->createQueryBuilder('li');
        $qb->select('li')
            ->where('li.shoppingList = :shoppingList', $qb->expr()->in('li.product', ':product'))
            ->setParameter('shoppingList', $shoppingList)
            ->setParameter('product', $products);

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Product $product
     * @param CustomerUser $customerUser
     * @return array|LineItem[]
     */
    public function getOneProductLineItemsWithShoppingListNames(Product $product, CustomerUser $customerUser)
    {
        $qb = $this->createQueryBuilder('li')
            ->select('li, shoppingList')
            ->join('li.shoppingList', 'shoppingList')
            ->andWhere('li.product = :product')
            ->andWhere('li.customerUser = :customerUser')
            ->setParameter('product', $product)
            ->setParameter('customerUser', $customerUser);

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns array where Shopping List id is a key and array of last added products is a value
     *
     * Example:
     * [
     *   74 => [
     *     ['name' => '220 Lumen Rechargeable Headlamp'],
     *     ['name' => 'Credit Card Pin Pad Reader']
     *   ]
     * ]
     *
     * @param ShoppingList[] $shoppingLists
     * @param int $productCount
     *
     * @return array
     */
    public function getLastProductsGroupedByShoppingList(array $shoppingLists, $productCount)
    {
        $dql = <<<DQL
SELECT li, list, product, names
FROM OroShoppingListBundle:LineItem AS li
INNER JOIN li.shoppingList list
INNER JOIN li.product product
INNER JOIN product.names names
WHERE li.shoppingList IN (:shoppingLists) AND (
    SELECT COUNT(li2.id) FROM OroShoppingListBundle:LineItem AS li2
    WHERE li2.shoppingList = li.shoppingList AND li2.id >= li.id
) <= :productCount
ORDER BY li.shoppingList DESC, li.id DESC
DQL;

        $shoppingListIds = array_map(
            function (ShoppingList $shoppingList) {
                return $shoppingList->getId();
            },
            $shoppingLists
        );

        /** @var LineItem[] $lineItems */
        $lineItems = $this->getEntityManager()
            ->createQuery($dql)
            ->setParameter('shoppingLists', $shoppingListIds)
            ->setParameter('productCount', $productCount)
            ->getResult();

        $result = [];
        foreach ($lineItems as $lineItem) {
            $shoppingListId = $lineItem->getShoppingList()->getId();
            $productName = $lineItem->getProduct()->getName()->getString();

            $result[$shoppingListId][] = [
                'name' => $productName
            ];
        }

        return $result;
    }
}
