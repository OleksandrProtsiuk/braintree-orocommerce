<?php

namespace Oro\Bundle\ProductBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\ProductBundle\Entity\ProductImage;

class ProductImageResizeEvent extends Event
{
    const NAME = 'oro_product.product_image.resize';

    /**
     * @var ProductImage
     */
    protected $productImage;

    /**
     * @var bool
     */
    protected $forceOption;

    /**
     * @param ProductImage $productImage
     * @param bool $forceOption
     */
    public function __construct(ProductImage $productImage, $forceOption = false)
    {
        $this->productImage = $productImage;
        $this->forceOption = $forceOption;
    }

    /**
     * @return ProductImage
     */
    public function getProductImage()
    {
        return $this->productImage;
    }

    /**
     * @return bool
     */
    public function getForceOption()
    {
        return $this->forceOption;
    }
}
