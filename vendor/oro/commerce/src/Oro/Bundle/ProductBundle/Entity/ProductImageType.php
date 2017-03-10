<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="oro_product_image_type")
 */
class ProductImageType
{
    const TYPE_LISTING = 'listing';
    const TYPE_MAIN = 'main';
    const TYPE_ADDITIONAL = 'additional';

    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var ProductImage
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\ProductImage", inversedBy="types")
     * @ORM\JoinColumn(name="product_image_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $productImage;

    /**
     * @var string
     * @ORM\Column(name="type", type="string", length=255)
     */
    protected $type;

    /**
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param ProductImage $productImage
     * @return $this
     */
    public function setProductImage(ProductImage $productImage)
    {
        $this->productImage = $productImage;

        return $this;
    }

    /**
     * @return ProductImage
     */
    public function getProductImage()
    {
        return $this->productImage;
    }
}
