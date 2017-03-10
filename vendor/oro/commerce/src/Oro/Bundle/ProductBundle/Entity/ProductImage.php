<?php

namespace Oro\Bundle\ProductBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\ProductBundle\Model\ExtendProductImage;

/**
 * @ORM\Entity()
 * @ORM\Table(name="oro_product_image")
 * @ORM\HasLifecycleCallbacks
 * @Config
 */
class ProductImage extends ExtendProductImage
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $id;

    /**
     * @var Product
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\ProductBundle\Entity\Product", inversedBy="images")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $product;

    /**
     * @var Collection|ProductImageType[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\ProductBundle\Entity\ProductImageType",
     *     mappedBy="productImage",
     *     indexBy="type",
     *     cascade={"ALL"},
     *     orphanRemoval=true,
     *     fetch="EAGER"
     * )
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $types;

    /**
     * @var \DateTime $updatedAt
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "entity"={
     *              "label"="oro.ui.updated_at"
     *          },
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $updatedAt;

    public function __construct()
    {
        parent::__construct();

        $this->types = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Product $product
     */
    public function setProduct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types->getKeys();
    }

    /**
     * @param string $type
     */
    public function addType($type)
    {
        if (!$this->types->containsKey($type)) {
            $productImageType = new ProductImageType($type);
            $productImageType->setProductImage($this);
            $this->types->set($type, $productImageType);

            $this->setUpdatedAtToNow();
        }
    }

    /**
     * @param string $type
     */
    public function removeType($type)
    {
        if ($this->types->containsKey($type)) {
            $this->types->remove($type);

            $this->setUpdatedAtToNow();
        }
    }

    /**
     * @param string $type
     * @return bool
     */
    public function hasType($type)
    {
        return $this->types->containsKey($type);
    }

    /**
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function setUpdatedAtToNow()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->setUpdatedAtToNow();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->setUpdatedAtToNow();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getImage() ? $this->getImage()->getFilename() : sprintf('ProductImage #%d', $this->getId());
    }

    public function __clone()
    {
        if ($this->id) {
            $this->id = null;
            $types = $this->getTypes();
            $this->types = new ArrayCollection();
            foreach ($types as $type) {
                $this->addType($type);
            }
        }
    }
}
