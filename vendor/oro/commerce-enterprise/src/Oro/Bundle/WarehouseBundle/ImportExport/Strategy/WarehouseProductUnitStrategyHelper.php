<?php

namespace Oro\Bundle\WarehouseBundle\ImportExport\Strategy;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\ImportExport\Strategy\AbstractInventoryLevelStrategyHelper;
use Oro\Bundle\InventoryBundle\Model\Data\ProductUnitTransformer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\WarehouseBundle\Entity\Warehouse;

class WarehouseProductUnitStrategyHelper extends AbstractInventoryLevelStrategyHelper
{
    /** @var array $requiredUnitCache */
    protected $requiredUnitCache = [];

    /** @var ProductUnitTransformer $productUnitTransformer */
    protected $productUnitTransformer;

    /**
     * @param DatabaseHelper $databaseHelper
     * @param TranslatorInterface $translator
     * @param ProductUnitTransformer $productUnitTransformer
     */
    public function __construct(
        DatabaseHelper $databaseHelper,
        TranslatorInterface $translator,
        ProductUnitTransformer $productUnitTransformer
    ) {
        $this->productUnitTransformer = $productUnitTransformer;
        parent::__construct($databaseHelper, $translator);
    }

    /**
     * {@inheritdoc}
     */
    public function process(
        InventoryLevel $importedEntity,
        array $importData = [],
        array $newEntities = [],
        array $errors = []
    ) {
        $this->errors = $errors;

        $product = $this->getProcessedEntity($newEntities, 'product');

        $productUnitPrecision = $importedEntity->getProductUnitPrecision();
        $productUnit = $productUnitPrecision->getUnit();
        $productUnit = $this->getProductUnit($productUnit);

        $productUnitPrecision = $this->getProductUnitPrecision($product, $productUnit);
        $newEntities['productUnitPrecision'] = $productUnitPrecision;

        $existingWarehouse = $this->getProcessedEntity($newEntities, 'warehouse');
        if ($this->isUnitRequired($product, $existingWarehouse) && !$productUnit) {
            $this->addError('oro.warehouse.import.error.unit_required');

            return null;
        }

        if ($this->successor) {
            return $this->successor->process($importedEntity, $importData, $newEntities, $this->errors);
        }

        return $importedEntity;
    }

    /**
     * Extract the existing product unit based on its code
     *
     * @param null|ProductUnit $productUnit
     * @return null|object|ProductUnit
     */
    protected function getProductUnit(ProductUnit $productUnit = null)
    {
        if ($productUnit && !empty(trim($productUnit->getCode()))) {
            $code = $this->productUnitTransformer->transformToProductUnit($productUnit->getCode());
            $productUnit = $this->checkAndRetrieveEntity(
                ProductUnit::class,
                ['code' => $code]
            );
        }

        return $productUnit;
    }

    /**
     * Return product precision unit corresponding to current product and unit or
     * extract primary product unit precision if no unit is specified
     *
     * @param Product $product
     * @param ProductUnit|null $productUnit
     * @return null|ProductUnitPrecision
     */
    protected function getProductUnitPrecision(Product $product, ProductUnit $productUnit = null)
    {
        if ($productUnit && !empty(trim($productUnit->getCode()))) {
            return $this->checkAndRetrieveEntity(
                ProductUnitPrecision::class,
                [
                    'product' => $product,
                    'unit' => $productUnit
                ]
            );
        }

        return $this->databaseHelper->findOneByIdentity($product->getPrimaryUnitPrecision());
    }
    
    /**
     * Update the cache which will be used to determine if unit is required. This cache
     * contains keys formed from product sku and warehouse name.
     *
     * @param string $productSku
     * @param string $warehouseName
     */
    protected function updateUnitRequiredCache($productSku, $warehouseName)
    {
        $key = $this->getUnitRequiredCacheKey($productSku, $warehouseName);
        if (!array_key_exists($key, $this->requiredUnitCache)) {
            $this->requiredUnitCache[$key] = 0;
        }

        $this->requiredUnitCache[$key]++;
    }

    /**
     * Generate a key for a product and warehouse combination
     *
     * @param string $productSku
     * @param string $warehouseName
     * @return string
     */
    protected function getUnitRequiredCacheKey($productSku, $warehouseName)
    {
        return $productSku . '-' . $warehouseName;
    }

    /**
     * Verify if the unit is required by searching in the cache for the combination of
     * product and warehouse and if the combination is found more then once then the unit
     * is required
     *
     * @param Product $product
     * @param Warehouse $warehouse
     * @return bool
     */
    protected function isUnitRequired(Product $product, Warehouse $warehouse)
    {
        $this->updateUnitRequiredCache($product->getSku(), $warehouse->getName());

        return $this->requiredUnitCache[$this->getUnitRequiredCacheKey($product->getSku(), $warehouse->getName())] > 1;
    }

    /**
     * {@inheritdoc}
     */
    public function clearCache($deep = false)
    {
        $this->requiredUnitCache = [];

        parent::clearCache($deep);
    }
}
