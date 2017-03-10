<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroRedirectBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_2_1';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroRedirectSlugTable($schema);
        $this->createOroRedirectTable($schema);
        $this->createOroSlugScopeTable($schema);

        /** Foreign keys generation **/
        $this->addOroRedirectForeignKeys($schema);
        $this->addOroSlugScopeForeignKeys($schema);
        $this->addOroRedirectSlugForeignKeys($schema);
    }

    /**
     * Create oro_redirect_slug table
     *
     * @param Schema $schema
     */
    protected function createOroRedirectSlugTable(Schema $schema)
    {
        $table = $schema->createTable('oro_redirect_slug');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('url', 'string', ['length' => 1024]);
        $table->addColumn('url_hash', 'string', ['length' => 32]);
        $table->addColumn('route_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('route_parameters', 'array', ['comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['url_hash'], 'oro_redirect_slug_url_hash', []);
    }

    /**
     * Create oro_slug_scope table
     *
     * @param Schema $schema
     */
    protected function createOroSlugScopeTable(Schema $schema)
    {
        $table = $schema->createTable('oro_slug_scope');
        $table->addColumn('slug_id', 'integer', []);
        $table->addColumn('scope_id', 'integer', []);
        $table->setPrimaryKey(['slug_id', 'scope_id']);
    }

    /**
     * Add oro_slug_scope foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroSlugScopeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_slug_scope');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_redirect_slug'),
            ['slug_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_scope'),
            ['scope_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create orob2b_redirect table
     *
     * @param Schema $schema
     */
    protected function createOroRedirectTable(Schema $schema)
    {
        $table = $schema->createTable('oro_redirect');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('slug_id', 'integer', ['notnull' => false]);
        $table->addColumn('redirect_from', 'string', ['notnull' => true, 'length' => 1024]);
        $table->addColumn('redirect_to', 'string', ['notnull' => true, 'length' => 1024]);
        $table->addColumn('redirect_type', 'integer', ['notnull' => true]);
        $table->addColumn('from_hash', 'string', ['length' => 32]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['from_hash'], 'idx_oro_redirect_from_hash', []);
    }

    /**
     * Create oro_slug_redirect table
     *
     * @param Schema $schema
     */
    protected function createOroSlugRedirectTable(Schema $schema)
    {
        $table = $schema->createTable('oro_slug_redirect');
        $table->addColumn('slug_id', 'integer', []);
        $table->addColumn('redirect_id', 'integer', []);
        $table->setPrimaryKey(['slug_id', 'redirect_id']);
        $table->addIndex(['slug_id'], 'IDX_DE8AE597311966CE', []);
        $table->addIndex(['redirect_id'], 'IDX_DE8AE597B42D874D', []);
    }

    /**
     * Add oro_slug_redirect foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroSlugRedirectForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_slug_redirect');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_redirect_slug'),
            ['slug_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_redirect'),
            ['redirect_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_redirect foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroRedirectForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_redirect');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_redirect_slug'),
            ['slug_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_redirect_slug foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroRedirectSlugForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_redirect_slug');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
