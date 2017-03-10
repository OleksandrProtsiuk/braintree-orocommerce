<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtension;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroProductBundleInstaller implements
    Installation,
    ExtendExtensionAwareInterface,
    ActivityExtensionAwareInterface,
    AttachmentExtensionAwareInterface,
    SlugExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;

    const PRODUCT_TABLE_NAME = 'oro_product';
    const PRODUCT_UNIT_TABLE_NAME = 'oro_product_unit';
    const PRODUCT_UNIT_PRECISION_TABLE_NAME = 'oro_product_unit_precision';
    const PRODUCT_VARIANT_LINK_TABLE_NAME = 'oro_product_variant_link';
    const PRODUCT_SHORT_DESCRIPTION_TABLE_NAME = 'oro_product_short_desc';
    const FALLBACK_LOCALE_VALUE_TABLE_NAME = 'oro_fallback_localization_val';

    const MAX_PRODUCT_IMAGE_SIZE_IN_MB = 10;
    const MAX_PRODUCT_ATTACHMENT_SIZE_IN_MB = 5;

    const PRODUCT_IMAGE_TABLE_NAME = 'oro_product_image';
    const PRODUCT_IMAGE_TYPE_TABLE_NAME = 'oro_product_image_type';

    /** @var ExtendExtension */
    protected $extendExtension;

    /** @var  ActivityExtension */
    protected $activityExtension;

    /**
     * @var SlugExtension
     */
    protected $slugExtension;

    /**
     * {@inheritdoc}
     */
    public function setSlugExtension(SlugExtension $extension)
    {
        $this->slugExtension = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_9';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroProductTable($schema);
        $this->createOroProductUnitTable($schema);
        $this->createOroProductUnitPrecisionTable($schema);
        $this->createOroProductNameTable($schema);
        $this->createOroProductDescriptionTable($schema);
        $this->createOroProductVariantLinkTable($schema);
        $this->createOroProductShortDescriptionTable($schema);
        $this->createOroProductImageTable($schema);
        $this->createOroProductImageTypeTable($schema);
        $this->createOroProductSlugTable($schema);
        $this->createOroProductSlugPrototypeTable($schema);

        $this->addOroProductForeignKeys($schema);
        $this->addOroProductUnitPrecisionForeignKeys($schema);
        $this->addOroProductNameForeignKeys($schema);
        $this->addOroProductDescriptionForeignKeys($schema);
        $this->addOroProductVariantLinkForeignKeys($schema);
        $this->addOroProductShortDescriptionForeignKeys($schema);
        $this->addOroProductImageForeignKeys($schema);
        $this->addOroProductImageTypeForeignKeys($schema);

        $this->updateProductTable($schema);
        $this->addNoteAssociations($schema);
        $this->addAttachmentAssociations($schema);
        $this->addProductContentVariants($schema);
        $this->addAttributeFamilyField($schema);
    }

    /**
     * Create oro_product table
     *
     * @param Schema $schema
     */
    protected function createOroProductTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('sku', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('variant_fields', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('status', 'string', ['length' => 16]);
        $table->addColumn('primary_unit_precision_id', 'integer', ['notnull' => false]);
        $table->addColumn('type', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['sku']);
        $table->addIndex(['created_at'], 'idx_oro_product_created_at', []);
        $table->addIndex(['updated_at'], 'idx_oro_product_updated_at', []);
        $table->addIndex(['sku'], 'idx_oro_product_sku', []);
        $table->addUniqueIndex(['primary_unit_precision_id'], 'idx_oro_product_primary_unit_precision_id');
    }

    /**
     * Create oro_product_unit table
     *
     * @param Schema $schema
     */
    protected function createOroProductUnitTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_UNIT_TABLE_NAME);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('default_precision', 'integer');
        $table->setPrimaryKey(['code']);
    }

    /**
     * Create oro_product_unit_precision table
     *
     * @param Schema $schema
     */
    protected function createOroProductUnitPrecisionTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_UNIT_PRECISION_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('unit_precision', 'integer', []);
        $table->addColumn('conversion_rate', 'float', ['notnull' => false]);
        $table->addColumn('sell', 'boolean', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_id', 'unit_code'], 'uidx_oro_product_unit_precision');
    }

    /**
     * @param Schema $schema
     */
    protected function createOroProductNameTable(Schema $schema)
    {
        $table = $schema->createTable('oro_product_name');
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['product_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'uniq_ba57d521eb576e89');
    }

    /**
     * @param Schema $schema
     */
    protected function createOroProductDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_product_description');
        $table->addColumn('description_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['description_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'uniq_416a3679eb576e89');
    }

    /**
     * Create oro_product_slug table
     *
     * @param Schema $schema
     */
    protected function createOroProductSlugTable(Schema $schema)
    {
        $this->slugExtension->addSlugs(
            $schema,
            'oro_product_slug',
            'oro_product',
            'product_id'
        );
    }

    /**
     * Create oro_product_slug_prototype table
     *
     * @param Schema $schema
     */
    protected function createOroProductSlugPrototypeTable(Schema $schema)
    {
        $this->slugExtension->addLocalizedSlugPrototypes(
            $schema,
            'oro_product_slug_prototype',
            'oro_product',
            'product_id'
        );
    }

    /**
     * Add oro_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_UNIT_PRECISION_TABLE_NAME),
            ['primary_unit_precision_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_product_unit_precision foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroProductUnitPrecisionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_UNIT_PRECISION_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_UNIT_TABLE_NAME),
            ['unit_code'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOroProductNameForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_product_name');
        $table->addForeignKeyConstraint(
            $schema->getTable(self::FALLBACK_LOCALE_VALUE_TABLE_NAME),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOroProductDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_product_description');
        $table->addForeignKeyConstraint(
            $schema->getTable(self::FALLBACK_LOCALE_VALUE_TABLE_NAME),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['description_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function updateProductTable(Schema $schema)
    {
        $this->extendExtension->addEnumField(
            $schema,
            self::PRODUCT_TABLE_NAME,
            'inventory_status',
            'prod_inventory_status',
            false,
            false,
            [
                'importexport' => ['order' => '25'],
                'dataaudit' => ['auditable' => true],
            ]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addNoteAssociations(Schema $schema)
    {
        $this->activityExtension->addActivityAssociation($schema, 'oro_note', self::PRODUCT_TABLE_NAME);
    }

    /**
     * @param Schema $schema
     */
    protected function addAttachmentAssociations(Schema $schema)
    {
        $this->attachmentExtension->addAttachmentAssociation(
            $schema,
            self::PRODUCT_TABLE_NAME,
            [],
            self::MAX_PRODUCT_ATTACHMENT_SIZE_IN_MB
        );

        $this->attachmentExtension->addImageRelation(
            $schema,
            self::PRODUCT_IMAGE_TABLE_NAME,
            'image',
            [
                'importexport' => ['excluded' => true]
            ],
            self::MAX_PRODUCT_IMAGE_SIZE_IN_MB
        );
    }

    /**
     * @param Schema $schema
     */
    protected function createOroProductVariantLinkTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_VARIANT_LINK_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->addColumn('parent_product_id', 'integer', ['notnull' => true]);
        $table->addColumn('visible', 'boolean', ['default' => true]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroProductVariantLinkForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_VARIANT_LINK_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_TABLE_NAME),
            ['parent_product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function createOroProductShortDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_SHORT_DESCRIPTION_TABLE_NAME);
        $table->addColumn('short_description_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['short_description_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroProductShortDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_SHORT_DESCRIPTION_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::FALLBACK_LOCALE_VALUE_TABLE_NAME),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_TABLE_NAME),
            ['short_description_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function createOroProductImageTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_IMAGE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroProductImageTypeTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_IMAGE_TYPE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_image_id', 'integer');
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroProductImageForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_IMAGE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOroProductImageTypeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_IMAGE_TYPE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_IMAGE_TABLE_NAME),
            ['product_image_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    public function addProductContentVariants(Schema $schema)
    {
        if ($schema->hasTable('oro_web_catalog_variant')) {
            $table = $schema->getTable('oro_web_catalog_variant');

            $this->extendExtension->addManyToOneRelation(
                $schema,
                $table,
                'product_page_product',
                'oro_product',
                'id',
                [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                    'entity' => ['label' => 'oro.product.entity_label'],
                    'extend' => [
                        'is_extend' => true,
                        'owner' => ExtendScope::OWNER_CUSTOM,
                        'cascade' => ['persist'],
                        'on_delete' => 'CASCADE',
                    ],
                    'datagrid' => [
                        'is_visible' => false
                    ],
                    'form' => [
                        'is_enabled' => false
                    ],
                    'view' => ['is_displayable' => false],
                    'merge' => ['display' => false],
                    'dataaudit' => ['auditable' => true]
                ]
            );
        }
    }

    /**
     * @param Schema $schema
     */
    public function addAttributeFamilyField(Schema $schema)
    {
        $table = $schema->getTable('oro_product');
        $table->addColumn('attribute_family_id', 'integer', ['notnull' => false]);
        $table->addIndex(['attribute_family_id']);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attribute_family'),
            ['attribute_family_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'RESTRICT']
        );
    }
}
