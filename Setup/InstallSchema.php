<?php

namespace Akeneo\Connector\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class InstallSchema
 *
 * @category  Class
 * @package   Akeneo\Connector\Setup
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class InstallSchema implements InstallSchemaInterface
{

    /**
     * {@inheritdoc}
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var SchemaSetupInterface $installer */
        $installer = $setup;

        $installer->startSetup();

        /**
         * Create table 'akeneo_connector_entities'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('akeneo_connector_entities'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'ID'
            )
            ->addColumn(
                'import',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Import type'
            )
            ->addColumn(
                'code',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Akeneo Code'
            )
            ->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                11,
                ['nullable' => true],
                'Magento Entity Id'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Creation Time'
            )
            ->addIndex(
                $installer->getIdxName(
                    'akeneo_connector_entities',
                    ['import', 'code', 'entity_id'],
                    \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['import', 'code', 'entity_id'],
                ['type' => \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]
            )
            ->setComment('Akeneo Connector Entities Relation');

        $installer->getConnection()->createTable($table);

        /**
         * Create table 'akeneo_connector_family_attribute_relations'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('akeneo_connector_family_attribute_relations'))
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'ID'
            )
            ->addColumn(
                'family_entity_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Family Entity ID'
            )
            ->addColumn(
                'attribute_code',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Attribute Code'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Creation Time'
            )
            ->setComment('Akeneo Connector Family Attribute Relations');

        $installer->getConnection()->createTable($table);

        /**
         * Create table 'akeneo_connector_import_log'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('akeneo_connector_import_log'))
            ->addColumn(
                'log_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Import ID'
            )
            ->addColumn(
                'identifier',
                Table::TYPE_TEXT,
                13,
                ['nullable' => false],
                'Identifier ID'
            )
            ->addColumn(
                'code',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Code'
            )
            ->addColumn(
                'name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Name'
            )
            ->addColumn(
                'status',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '1'],
                'Status'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Creation Time'
            )
            ->setComment('Akeneo Connector Import Log');

        $installer->getConnection()->createTable($table);

        /**
         * Create table 'akeneo_connector_import_log_step'
         */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('akeneo_connector_import_log_step'))
            ->addColumn(
                'step_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Import ID'
            )
            ->addColumn(
                'log_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Log ID'
            )
            ->addColumn(
                'identifier',
                Table::TYPE_TEXT,
                13,
                ['nullable' => false],
                'Identifier ID'
            )
            ->addColumn(
                'number',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true, 'default' => '0'],
                'Number'
            )
            ->addColumn(
                'method',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Method'
            )
            ->addColumn(
                'message',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => ''],
                'Message'
            )
            ->addColumn(
                'continue',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '1'],
                'Continue'
            )
            ->addColumn(
                'status',
                Table::TYPE_SMALLINT,
                null,
                ['nullable' => false, 'default' => '1'],
                'Status'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Creation Time'
            )
            ->addForeignKey(
                $installer->getFkName('akeneo_connector_import_log_step', 'log_id', 'akeneo_connector_import_log', 'log_id'),
                'log_id',
                $installer->getTable('akeneo_connector_import_log'),
                'log_id',
                Table::ACTION_CASCADE
            )
            ->setComment('Akeneo Connector Import Log Step');

        $installer->getConnection()->createTable($table);

        /** @var Table $table */
        $table = $installer->getConnection()
            ->newTable($installer->getTable('akeneo_connector_product_model'))
            ->addColumn(                'code',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'primary' => true],
                'Akeneo code'
            )
            ->addColumn(
                'axis',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Product axis')
            ->setComment('Akeneo Connector Product Model');

        $installer->getConnection()->createTable($table);
    }
}
