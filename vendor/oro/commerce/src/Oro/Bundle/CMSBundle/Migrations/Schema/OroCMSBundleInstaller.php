<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
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

class OroCMSBundleInstaller implements
    Installation,
    AttachmentExtensionAwareInterface,
    ExtendExtensionAwareInterface,
    SlugExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;

    const CMS_LOGIN_PAGE_TABLE = 'oro_cms_login_page';
    const MAX_LOGO_IMAGE_SIZE_IN_MB = 10;
    const MAX_BACKGROUND_IMAGE_SIZE_IN_MB = 10;

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
        return 'v1_3';
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
        $this->createOroCmsPageTable($schema);
        $this->createOroCmsPageSlugTable($schema);
        $this->createOroCmsPageSlugPrototypeTable($schema);
        $this->createOroCmsPageTitleTable($schema);
        $this->createOroCmsLoginPageTable($schema);

        /** Foreign keys generation **/
        $this->addOroCmsPageForeignKeys($schema);
        $this->addOroCmsPageTitleForeignKeys($schema);

        $this->addImageAssociations($schema);
        $this->addContentVariantTypes($schema);
    }

    /**
     * Create oro_cms_page table
     *
     * @param Schema $schema
     */
    protected function createOroCmsPageTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cms_page');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('content', 'text', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_cms_page foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCmsPageForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_page');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_cms_login_page table
     *
     * @param Schema $schema
     */
    protected function createOroCmsLoginPageTable(Schema $schema)
    {
        $table = $schema->createTable(self::CMS_LOGIN_PAGE_TABLE);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('top_content', 'text', ['notnull' => false]);
        $table->addColumn('bottom_content', 'text', ['notnull' => false]);
        $table->addColumn('css', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addImageAssociations(Schema $schema)
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            self::CMS_LOGIN_PAGE_TABLE,
            'logoImage',
            [],
            self::MAX_LOGO_IMAGE_SIZE_IN_MB
        );

        $this->attachmentExtension->addImageRelation(
            $schema,
            self::CMS_LOGIN_PAGE_TABLE,
            'backgroundImage',
            [],
            self::MAX_BACKGROUND_IMAGE_SIZE_IN_MB
        );
    }

    /**
     * Create oro_cms_page_slug table
     *
     * @param Schema $schema
     */
    protected function createOroCmsPageSlugTable(Schema $schema)
    {
        $this->slugExtension->addSlugs(
            $schema,
            'oro_cms_page_to_slug',
            'oro_cms_page',
            'page_id'
        );
    }

    /**
     * Create oro_cms_page_slug_prototype table
     *
     * @param Schema $schema
     */
    protected function createOroCmsPageSlugPrototypeTable(Schema $schema)
    {
        $this->slugExtension->addLocalizedSlugPrototypes(
            $schema,
            'oro_cms_page_slug_prototype',
            'oro_cms_page',
            'page_id'
        );
    }

    /**
     * Create oro_cms_page_title table
     *
     * @param Schema $schema
     */
    protected function createOroCmsPageTitleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_cms_page_title');
        $table->addColumn('page_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['page_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Add oro_cms_page_title foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCmsPageTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_cms_page_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_cms_page'),
            ['page_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
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
                'cms_page',
                'oro_cms_page',
                'id',
                [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                    'entity' => ['label' => 'oro.cms.page.entity_label'],
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
