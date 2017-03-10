<?php

namespace Oro\Bundle\CatalogBundle\Entity\Repository;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\Tree\Entity\Repository\NestedTreeRepository;

class CategoryRepository extends NestedTreeRepository
{
    /**
     * @var Category
     */
    private $masterCatalog;

    /**
     * @return Category
     */
    public function getMasterCatalogRoot()
    {
        if (!$this->masterCatalog) {
            $this->masterCatalog = $this->createQueryBuilder('category')
                ->andWhere('category.parentCategory IS NULL')
                ->orderBy('category.id', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getSingleResult();
        }
        return $this->masterCatalog;
    }

    /**
     * @param object|null $node
     * @param bool $direct
     * @param string|null $sortByField
     * @param string $direction
     * @param bool $includeNode
     * @return QueryBuilder
     */
    public function getChildrenQueryBuilderPartial(
        $node = null,
        $direct = false,
        $sortByField = null,
        $direction = 'ASC',
        $includeNode = false
    ) {
        return $this->getChildrenQueryBuilder($node, $direct, $sortByField, $direction, $includeNode)
            ->select('partial node.{id, parentCategory, materializedPath, left, level, right, root}');
    }

    /**
     * @param object|null $node
     * @param bool $direct
     * @param string|null $sortByField
     * @param string $direction
     * @param bool $includeNode
     * @return Category[]
     */
    public function getChildrenWithTitles(
        $node = null,
        $direct = false,
        $sortByField = null,
        $direction = 'ASC',
        $includeNode = false
    ) {
        return $this->getChildrenQueryBuilder($node, $direct, $sortByField, $direction, $includeNode)
            ->addSelect('title, children')
            ->leftJoin('node.titles', 'title')
            ->leftJoin('node.childCategories', 'children')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Category $category
     * @return array
     */
    public function getChildrenIds(Category $category)
    {
        $result = $this->childrenQueryBuilder($category)
            ->select('node.id')
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $result);
    }

    /**
     * @param string $title
     * @return Category|null
     */
    public function findOneByDefaultTitle($title)
    {
        $qb = $this->createQueryBuilder('category');

        return $qb
            ->select('partial category.{id}')
            ->innerJoin('category.titles', 'title', Join::WITH, $qb->expr()->isNull('title.localization'))
            ->andWhere('title.string = :title')
            ->setParameter('title', $title)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param Product $product
     * @return Category|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByProduct(Product $product)
    {
        return $this->createQueryBuilder('category')
            ->where(':product MEMBER OF category.products')
            ->setParameter('product', $product)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $productSku
     * @param bool $includeTitles
     * @return null|Category
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findOneByProductSku($productSku, $includeTitles = false)
    {
        $qb = $this->createQueryBuilder('category');

        if ($includeTitles) {
            $qb->addSelect('title.string');
            $qb->leftJoin('category.titles', 'title', Join::WITH, $qb->expr()->isNull('title.localization'));
        }

        return $qb
            ->select('partial category.{id}')
            ->innerJoin('category.products', 'p', Join::WITH, $qb->expr()->eq('p.sku', ':sku'))
            ->setParameter('sku', $productSku)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param array $categories
     * @return QueryBuilder
     */
    public function getCategoriesProductsCountQueryBuilder($categories)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('category.id, COUNT(product.id) as products_count')
            ->from('OroProductBundle:Product', 'product')
            ->innerJoin(
                'OroCatalogBundle:Category',
                'category',
                Expr\Join::WITH,
                'product MEMBER OF category.products'
            )
            ->where($qb->expr()->in('category.id', ':categories'))
            ->setParameter('categories', $categories)
            ->groupBy('category.id');

        return $qb;
    }

    /**
     * @param Category $category
     * @return Category[]
     */
    public function getAllChildCategories(Category $category)
    {
        return $this->getChildrenQueryBuilderPartial($category)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Category[] $categories
     * @return array
     */
    public function getProductIdsByCategories(array $categories)
    {
        $qb = $this->createQueryBuilder('category');
        $productIds = $qb->select('product.id')
            ->innerJoin('category.products', 'product')
            ->where($qb->expr()->in('category.id', ':categories'))
            ->setParameter('categories', $categories)
            ->groupBy('product.id')
            ->orderBy($qb->expr()->asc('product.id'))
            ->getQuery()
            ->getScalarResult();

        return array_column($productIds, 'id');
    }

    /**
     * Creates product to category map, [product_id => Category, ...]
     * @param Product[] $products
     * @param Localization[] $localizations
     * @return array
     */
    public function getCategoryMapByProducts(array $products, array $localizations = [])
    {
        $builder = $this->createQueryBuilder('category');
        $builder
            ->join(Product::class, 'product', 'WITH', $builder->expr()->isMemberOf('product', 'category.products'))
            ->andWhere($builder->expr()->in('product', ':products'))
            ->setParameter('products', $products);

        // Join localization fields to avoid lazy-loading
        $localizationFields = ['titles', 'shortDescriptions', 'longDescriptions'];
        foreach ($localizationFields as $field) {
            $builder
                ->addSelect($field)
                ->leftJoin(
                    sprintf('category.%s', $field),
                    $field,
                    'WITH',
                    $builder->expr()->orX(
                        $builder->expr()->in(sprintf('%s.localization', $field), ':localizations'),
                        $builder->expr()->isNull(sprintf('%s.localization', $field))
                    )
                );
        }
        $builder->setParameter('localizations', $localizations);

        $relationBuilder = clone $builder;
        $relationBuilder->select('product.id as productId, category.id as categoryId');

        $categories = $builder->getQuery()->getResult();
        $relations = $relationBuilder->getQuery()->getArrayResult();

        $categoryMap = [];
        /** @var Category $category */
        foreach ($categories as $category) {
            $categoryMap[$category->getId()] = $category;
        }

        $productCategoryMap = [];
        foreach ($relations as $relation) {
            $productCategoryMap[$relation['productId']] = $categoryMap[$relation['categoryId']];
        }

        return $productCategoryMap;
    }
}
