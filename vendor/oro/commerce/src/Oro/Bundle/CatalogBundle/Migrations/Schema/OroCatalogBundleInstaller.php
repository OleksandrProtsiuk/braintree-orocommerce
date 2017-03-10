<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtension;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroCatalogBundleInstaller implements
    Installation,
    ActivityExtensionAwareInterface,
    AttachmentExtensionAwareInterface,
    ExtendExtensionAwareInterface,
    SlugExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;

    const ORO_CATALOG_CATEGORY_SHORT_DESCRIPTION_TABLE_NAME = 'oro_catalog_cat_short_desc';
    const ORO_CATALOG_CATEGORY_LONG_DESCRIPTION_TABLE_NAME = 'oro_catalog_cat_long_desc';
    const ORO_CATALOG_CATEGORY_TABLE_NAME = 'oro_catalog_category';
    const ORO_FALLBACK_LOCALIZE_TABLE_NAME ='oro_fallback_localization_val';
    const ORO_CATEGORY_DEFAULT_PRODUCT_OPTIONS_TABLE_NAME = 'oro_category_def_prod_opts';
    const ORO_PRODUCT_UNIT_TABLE_NAME = 'oro_product_unit';
    const MAX_CATEGORY_IMAGE_SIZE_IN_MB = 10;
    const THUMBNAIL_WIDTH_SIZE_IN_PX = 100;
    const THUMBNAIL_HEIGHT_SIZE_IN_PX = 100;

    /** @var ActivityExtension */
    protected $activityExtension;

    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * @var SlugExtension
     */
    protected $slugExtension;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_7';
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
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

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
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroCatalogCategoryTable($schema);
        $this->createOroCatalogCategoryTitleTable($schema);
        $this->createOroCategoryToProductTable($schema);
        $this->createOroCatalogCategoryShortDescriptionTable($schema);
        $this->createOroCatalogCategoryLongDescriptionTable($schema);
        $this->createOroCategoryDefaultProductOptionsTable($schema);
        $this->createOroCategorySlugTable($schema);
        $this->createOroCategorySlugPrototypeTable($schema);

        /** Foreign keys generation **/
        $this->addOroCatalogCategoryForeignKeys($schema);
        $this->addOroCatalogCategoryTitleForeignKeys($schema);
        $this->addOroCategoryToProductForeignKeys($schema);
        $this->addOroCatalogCategoryShortDescriptionForeignKeys($schema);
        $this->addOroCatalogCategoryLongDescriptionForeignKeys($schema);
        $this->addOroCategoryDefaultProductOptionsForeignKeys($schema);
        $this->addCategoryImageAssociation($schema, 'largeImage');
        $this->addCategoryImageAssociation($schema, 'smallImage');

        $this->addContentVariantTypes($schema);
    }

    /**
     * Create oro_catalog_category table
     *
     * @param Schema $schema
     */
    protected function createOroCatalogCategoryTable(Schema $schema)
    {
        $table = $schema->createTable('oro_catalog_category');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('tree_left', 'integer', []);
        $table->addColumn('tree_level', 'integer', []);
        $table->addColumn('tree_right', 'integer', []);
        $table->addColumn('tree_root', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('default_product_options_id', 'integer', ['notnull' => false]);
        $table->addColumn('materialized_path', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['default_product_options_id']);

        $this->activityExtension->addActivityAssociation($schema, 'oro_note', self::ORO_CATALOG_CATEGORY_TABLE_NAME);
    }

    /**
     * Create oro_catalog_category_title table
     *
     * @param Schema $schema
     */
    protected function createOroCatalogCategoryTitleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_catalog_category_title');
        $table->addColumn('category_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['category_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Create oro_category_to_product table
     *
     * @param Schema $schema
     */
    protected function createOroCategoryToProductTable(Schema $schema)
    {
        $table = $schema->createTable('oro_category_to_product');
        $table->addColumn('category_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->setPrimaryKey(['category_id', 'product_id']);
        $table->addUniqueIndex(['product_id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroCategoryDefaultProductOptionsTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_CATEGORY_DEFAULT_PRODUCT_OPTIONS_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_unit_precision', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_catalog_cat_slug table
     *
     * @param Schema $schema
     */
    protected function createOroCategorySlugTable(Schema $schema)
    {
        $this->slugExtension->addSlugs(
            $schema,
            'oro_catalog_cat_slug',
            'oro_catalog_category',
            'category_id'
        );
    }

    /**
     * Create oro_catalog_cat_slug_prototype table
     *
     * @param Schema $schema
     */
    protected function createOroCategorySlugPrototypeTable(Schema $schema)
    {
        $this->slugExtension->addLocalizedSlugPrototypes(
            $schema,
            'oro_catalog_cat_slug_prototype',
            'oro_catalog_category',
            'category_id'
        );
    }

    /**
     * Add oro_catalog_category foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCatalogCategoryForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_catalog_category');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['parent_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_CATEGORY_DEFAULT_PRODUCT_OPTIONS_TABLE_NAME),
            ['default_product_options_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_catalog_category_title foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCatalogCategoryTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_catalog_category_title');
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_FALLBACK_LOCALIZE_TABLE_NAME),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_category_to_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCategoryToProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_category_to_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_catalog_category_short_description table
     *
     * @param Schema $schema
     */
    protected function createOroCatalogCategoryShortDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_CATALOG_CATEGORY_SHORT_DESCRIPTION_TABLE_NAME);
        $table->addColumn('category_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->addUniqueIndex(['localized_value_id'], 'uniq_a2b14ef5eb576e89');
        $table->setPrimaryKey(['category_id', 'localized_value_id']);
    }

    /**
     * Create oro_catalog_category_long_description table
     *
     * @param Schema $schema
     */
    protected function createOroCatalogCategoryLongDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_CATALOG_CATEGORY_LONG_DESCRIPTION_TABLE_NAME);
        $table->addColumn('category_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->addUniqueIndex(['localized_value_id'], 'uniq_4f7c279feb576e89');
        $table->setPrimaryKey(['category_id', 'localized_value_id']);
    }

    /**
     * Add oro_catalog_category_short_description foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCatalogCategoryShortDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_CATALOG_CATEGORY_SHORT_DESCRIPTION_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_FALLBACK_LOCALIZE_TABLE_NAME),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_CATALOG_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_catalog_category_long_description foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCatalogCategoryLongDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_CATALOG_CATEGORY_LONG_DESCRIPTION_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_FALLBACK_LOCALIZE_TABLE_NAME),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_CATALOG_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOroCategoryDefaultProductOptionsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_CATEGORY_DEFAULT_PRODUCT_OPTIONS_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_PRODUCT_UNIT_TABLE_NAME),
            ['product_unit_code'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     * @param $fieldName
     */
    public function addCategoryImageAssociation(Schema $schema, $fieldName)
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            self::ORO_CATALOG_CATEGORY_TABLE_NAME,
            $fieldName,
            [],
            self::MAX_CATEGORY_IMAGE_SIZE_IN_MB,
            self::THUMBNAIL_WIDTH_SIZE_IN_PX,
            self::THUMBNAIL_HEIGHT_SIZE_IN_PX
        );
    }

    /**
     * @param Schema $schema
     */
    public function addContentVariantTypes(Schema $schema)
    {
        if ($schema->hasTable('oro_web_catalog_variant')) {
            $table = $schema->getTable('oro_web_catalog_variant');

            $this->extendExtension->addManyToOneRelation(
                $schema,
                $table,
                'category_page_category',
                'oro_catalog_category',
                'id',
                [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                    'entity' => ['label' => 'oro.catalog.category.entity_label'],
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
}
