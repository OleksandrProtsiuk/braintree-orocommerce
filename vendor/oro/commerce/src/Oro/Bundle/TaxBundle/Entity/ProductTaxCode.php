<?php

namespace Oro\Bundle\TaxBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\TaxBundle\Entity\Repository\ProductTaxCodeRepository")
 * @ORM\Table(name="oro_tax_product_tax_code")
 * @ORM\HasLifecycleCallbacks
 * @Config(
 *      routeName="oro_tax_product_tax_code_index",
 *      routeView="oro_tax_product_tax_code_view",
 *      routeUpdate="oro_tax_product_tax_code_update",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-list-alt"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 */
class ProductTaxCode extends AbstractTaxCode
{
    /**
     * @ORM\ManyToMany(targetEntity="Oro\Bundle\ProductBundle\Entity\Product")
     * @ORM\JoinTable(
     *      name="oro_tax_prod_tax_code_prod",
     *      joinColumns={
     *          @ORM\JoinColumn(name="product_tax_code_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     *
     * @var Product[]|Collection
     */
    protected $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    /**
     * Add product
     *
     * @param Product $product
     * @return $this
     */
    public function addProduct(Product $product)
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
        }

        return $this;
    }

    /**
     * Remove product
     *
     * @param Product $product
     * @return $this
     */
    public function removeProduct(Product $product)
    {
        if ($this->products->contains($product)) {
            $this->products->removeElement($product);
        }

        return $this;
    }

    /**
     * Get products
     *
     * @return Product[]|Collection
     */
    public function getProducts()
    {
        return $this->products;
    }

    /** {@inheritdoc} */
    public function getType()
    {
        return TaxCodeInterface::TYPE_PRODUCT;
    }
}
