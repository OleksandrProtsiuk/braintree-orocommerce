<?php

namespace Oro\Bundle\CommerceMenuBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCommerceMenuBundleInstaller implements
    Installation,
    AttachmentExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;

    const ORO_COMMERCE_MENU_UPDATE_TABLE_NAME = 'oro_commerce_menu_upd';
    const ORO_COMMERCE_MENU_UPDATE_TITLE_TABLE_NAME = 'oro_commerce_menu_upd_title';
    const ORO_COMMERCE_MENU_UPDATE_DESCRIPTION_TABLE_NAME = 'oro_commerce_menu_upd_descr';

    const MAX_MENU_UPDATE_IMAGE_SIZE_IN_MB = 10;
    const THUMBNAIL_WIDTH_SIZE_IN_PX = 100;
    const THUMBNAIL_HEIGHT_SIZE_IN_PX = 100;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_2';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroCommerceMenuUpdateTable($schema);
        $this->createOroCommerceMenuUpdateTitleTable($schema);
        $this->createOroCommerceMenuUpdateDescriptionTable($schema);

        /** Foreign keys generation **/
        $this->addOroCommerceMenuUpdateForeignKeys($schema);
        $this->addOroCommerceMenuUpdateTitleForeignKeys($schema);
        $this->addOroCommerceMenuUpdateDescriptionForeignKeys($schema);

        /** Associations */
        $this->addOroCommerceMenuUpdateImageAssociation($schema);

        /** Cleaning up MenuBundle */
        $this->removeMenuBundleTables($schema, $queries);
    }

    /**
     * Create oro_commerce_menu_upd table.
     *
     * @param Schema $schema
     */
    protected function createOroCommerceMenuUpdateTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_COMMERCE_MENU_UPDATE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('key', 'string', ['length' => 100]);
        $table->addColumn('parent_key', 'string', ['length' => 100, 'notnull' => false]);
        $table->addColumn('uri', 'string', ['length' => 1023, 'notnull' => false]);
        $table->addColumn('menu', 'string', ['length' => 100]);
        $table->addColumn('icon', 'string', ['length' => 150, 'notnull' => false]);
        $table->addColumn('is_active', 'boolean', []);
        $table->addColumn('is_divider', 'boolean', []);
        $table->addColumn('is_custom', 'boolean', []);
        $table->addColumn('priority', 'integer', ['notnull' => false]);
        $table->addColumn('scope_id', 'integer', ['notnull' => true]);
        $table->addColumn('condition', 'string', ['length' => 512, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['key', 'scope_id', 'menu'], 'oro_commerce_menu_upd_uidx');
    }

    /**
     * Create oro_commerce_menu_upd_title table
     *
     * @param Schema $schema
     */
    protected function createOroCommerceMenuUpdateTitleTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_COMMERCE_MENU_UPDATE_TITLE_TABLE_NAME);
        $table->addColumn('menu_update_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['menu_update_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Add oro_commerce_menu_upd_title foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCommerceMenuUpdateTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_COMMERCE_MENU_UPDATE_TITLE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_COMMERCE_MENU_UPDATE_TABLE_NAME),
            ['menu_update_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }


    /**
     * Create `oro_navigation_menu_upd_descr` table
     *
     * @param Schema $schema
     */
    protected function createOroCommerceMenuUpdateDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_COMMERCE_MENU_UPDATE_DESCRIPTION_TABLE_NAME);
        $table->addColumn('menu_update_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['menu_update_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Add `oro_navigation_menu_upd_descr` foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCommerceMenuUpdateDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_COMMERCE_MENU_UPDATE_DESCRIPTION_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_COMMERCE_MENU_UPDATE_TABLE_NAME),
            ['menu_update_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    public function addOroCommerceMenuUpdateImageAssociation(Schema $schema)
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            self::ORO_COMMERCE_MENU_UPDATE_TABLE_NAME,
            'image',
            [],
            self::MAX_MENU_UPDATE_IMAGE_SIZE_IN_MB,
            self::THUMBNAIL_WIDTH_SIZE_IN_PX,
            self::THUMBNAIL_HEIGHT_SIZE_IN_PX
        );
    }

    /**
     * Add `oro_commerce_menu_upd` foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCommerceMenuUpdateForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_COMMERCE_MENU_UPDATE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id']
        );
    }

    /**
     * Remove `MenuBundle` tables and entity configs
     *
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function removeMenuBundleTables(Schema $schema, QueryBag $queries)
    {
        $this->safeDropTable($schema, 'orob2b_menu_item');
        $this->safeDropTable($schema, 'orob2b_menu_item_title');
        $this->safeDropTable($schema, 'oro_menu_item');
        $this->safeDropTable($schema, 'oro_menu_item_title');

        $this->dropEntityConfig($queries, 'OroB2B\Bundle\MenuBundle\Entity\MenuItem');
        $this->dropEntityConfig($queries, 'Oro\Bundle\MenuBundle\Entity\MenuItem');
    }

    /**
     * @param Schema $schema
     * @param string $tableName
     */
    protected function safeDropTable(Schema $schema, $tableName)
    {
        if ($schema->hasTable($tableName)) {
            $schema->dropTable($tableName);
        }
    }

    /**
     * @param QueryBag $queries
     * @param string $className
     */
    protected function dropEntityConfig(QueryBag $queries, $className)
    {
        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config_field WHERE entity_id IN ('
                . 'SELECT id FROM oro_entity_config WHERE class_name = :class)',
                ['class' => $className],
                ['class' => 'string']
            )
        );

        $queries->addPostQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config WHERE class_name = :class',
                ['class' => $className],
                ['class' => 'string']
            )
        );
    }
}
