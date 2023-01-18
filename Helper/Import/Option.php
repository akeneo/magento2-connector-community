<?php

declare(strict_types=1);

namespace Akeneo\Connector\Helper\Import;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Zend_Db_Expr as Expr;
use Zend_Db_Statement_Exception;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Option extends Entities
{
    /**
     * Match Magento Id with code
     *
     * @param string $pimKey
     * @param string $entityTable
     * @param string $entityKey
     * @param string $import
     * @param string $prefix
     *
     * @return Entities
     * @throws LocalizedException
     * @throws Zend_Db_Statement_Exception
     */
    public function matchEntity($pimKey, $entityTable, $entityKey, $import, $prefix = null)
    {
        /** @var string $localeCode */
        $localeCode = $this->configHelper->getDefaultLocale();
        /** @var AdapterInterface $connection */
        $connection = $this->connection;
        /** @var string $tableName */
        $tableName = $this->getTableName($import);

        // Delete empty
        $connection->delete($tableName, [$pimKey . ' = ?' => '']);
        /** @var string $akeneoConnectorTable */
        $akeneoConnectorTable = $this->getTable('akeneo_connector_entities');
        /** @var string $entityTable */
        $entityTable = $this->getTable($entityTable);

        if ($entityKey == 'entity_id') {
            $entityKey = $this->getColumnIdentifier($entityTable);
        }

        /* Connect existing Magento options to new Akeneo items */ // Get existing entities from Akeneo table
        /** @var Select $select */
        $select = $connection->select()->from($akeneoConnectorTable, ['entity_id' => 'entity_id'])->where(
            'import = ?',
            'option'
        );
        /** @var string[] $existingEntities */
        $existingEntities = $connection->query($select)->fetchAll();
        $existingEntities = array_column($existingEntities, 'entity_id');
        /** @var int $entityTypeId */
        $entityTypeId = $this->configHelper->getEntityTypeId(ProductAttributeInterface::ENTITY_TYPE_CODE);

        /** @var string[] $columnToSelect */
        $columnToSelect = ['label' => 't.labels-' . $localeCode, 'code' => 't.code', 'attribute' => 't.attribute'];
        /** @var string $adminColumnValue */
        $adminColumnValue = 'labels-' . $localeCode;
        /** @var string $condition */
        $condition = '`labels-' . $localeCode . '` = e.value';
        if ($this->configHelper->getOptionCodeAsAdminLabel()) {
            $condition        = '`code` = e.value';
            $adminColumnValue = 'code';
            $columnToSelect   = ['code' => 't.code', 'attribute' => 't.attribute'];
        }

        if ($connection->tableColumnExists($tableName, $adminColumnValue)) {
            // Add index to admin label column
            /** @var string $labelIndexName */
            $labelIndexName = $connection->getIndexName($tableName, $adminColumnValue);
            $connection->query('CREATE INDEX `' . $labelIndexName . '` ON ' . $tableName . ' (`' . $adminColumnValue . '`(255));');

            // Get all entities that are being imported and have a corresponding label in Magento
            $select = $connection->select()->from(
                ['t' => $tableName],
                $columnToSelect
            )->joinInner(
                ['e' => 'eav_attribute_option_value'],
                $condition,
                []
            )->joinInner(
                ['o' => 'eav_attribute_option'],
                'o.`option_id` = e.`option_id`',
                ['option_id']
            )->joinInner(
                ['a' => 'eav_attribute'],
                'o.`attribute_id` = a.`attribute_id` AND t.`attribute` = a.`attribute_code`',
                []
            )->where('e.store_id = ?', 0)->where('a.entity_type_id', $entityTypeId);
            /** @var string[] $existingMagentoOptions */
            $existingMagentoOptions = $connection->query($select)->fetchAll();
            /** @var string[] $existingMagentoOptionIds */
            $existingMagentoOptionIds = array_column($existingMagentoOptions, 'option_id');
            /** @var string[] $entitiesToCreate */
            $entitiesToCreate = array_diff($existingMagentoOptionIds, $existingEntities);

            /**
             * @var string $entityToCreateKey
             * @var string $entityOptionId
             */
            foreach ($entitiesToCreate as $entityToCreateKey => $entityOptionId) {
                /** @var string[] $currentEntity */
                $currentEntity = $existingMagentoOptions[$entityToCreateKey];
                /** @var string[] $values */
                $values = [
                    'import'    => 'option',
                    'code'      => $currentEntity['attribute'] . '-' . $currentEntity['code'],
                    'entity_id' => $entityOptionId,
                ];
                $connection->insertOnDuplicate($akeneoConnectorTable, $values);
            }
        }

        /* Use new error-free separator */
        $entityCodeColumnName = ($prefix ? 'CONCAT(t.`' . $prefix . '`, "-", t.`' . $pimKey . '`)' : 't.`' . $pimKey . '`');

        /* Legacy: update columns still using former "_" separator */
        /** @var string $oldEntityCodeColumnName */
        $oldEntityCodeColumnName = ($prefix ? 'CONCAT(t.`' . $prefix . '`, "_", t.`' . $pimKey . '`)' : 't.`' . $pimKey . '`');

        /** @var string $update */
        $update = 'UPDATE `' . $akeneoConnectorTable . '` AS `e`, `' . $tableName . '` AS `t` SET e.code = ' . $entityCodeColumnName . ' WHERE e.code = ' . $oldEntityCodeColumnName . ' AND e.`import` = "' . $import . '"';
        $connection->query($update);

        /* Continue with original matchEntities */

        /*
         * Update entity_id column from akeneo_connector_entities table
         *
         * Beware of "Subquery returns more than 1 row" MySQL error.
         * The problem is not that the subquery "SELECT `entity_id` FROM ..." return more than one row.
         * The problem is that for a single attribute option and option value combination, you have 2 row in akeneo_connector_entity.
         * To find them you can query "SELECT `code`, import FROM akeneo_connector_entities GROUP BY CODE, import HAVING count(*) > 1";
         */
        $connection->query(
            '
            UPDATE `' . $tableName . '` t
            SET `_entity_id` = (
                SELECT `entity_id` FROM `' . $akeneoConnectorTable . '` c
                WHERE ' . ($prefix ? 'CONCAT(t.`' . $prefix . '`, "-", t.`' . $pimKey . '`)' : 't.`' . $pimKey . '`') . ' = c.`code`
                    AND c.`import` = "' . $import . '"
            )
        '
        );

        /** @var string $mysqlVersion */
        $mysqlVersion = $this->getMysqlVersion();
        if (substr($mysqlVersion, 0, 1) == '8') {
            $connection->query('SET @@SESSION.information_schema_stats_expiry = 0;');
        }

        /* Set entity_id for new entities */
        /** @var string $query */
        $query = $connection->query('SHOW TABLE STATUS LIKE "' . $entityTable . '"');
        /** @var mixed $row */
        $row = $query->fetch();

        $connection->query('SET @id = ' . (int)$row['Auto_increment']);
        /** @var array $values */
        $values = [
            '_entity_id' => new Expr('@id := @id + 1'),
            '_is_new'    => new Expr('1'),
        ];
        $connection->update($tableName, $values, '_entity_id IS NULL');

        /* Update akeneo_connector_entities table with code and new entity_id */
        /** @var Select $select */
        $select = $connection->select()->from(
            $tableName,
            [
                'import'    => new Expr("'" . $import . "'"),
                'code'      => $prefix ? new Expr('CONCAT(`' . $prefix . '`, "-", `' . $pimKey . '`)') : $pimKey,
                'entity_id' => '_entity_id',
            ]
        )->where('_is_new = ?', 1);

        $connection->query(
            $connection->insertFromSelect($select, $akeneoConnectorTable, ['import', 'code', 'entity_id'], 2)
        );

        /* Update entity table auto increment */
        /** @var string $count */
        $count = $connection->fetchOne(
            $connection->select()->from($tableName, [new Expr('COUNT(*)')])->where('_is_new = ?', 1)
        );
        if ($count) {
            /** @var string $maxCode */
            $maxCode = $connection->fetchOne(
                $connection->select()->from($akeneoConnectorTable, new Expr('MAX(`entity_id`)'))->where(
                    'import = ?',
                    $import
                )
            );
            /** @var string $maxEntity */
            $maxEntity = $connection->fetchOne(
                $connection->select()->from($entityTable, new Expr('MAX(`' . $entityKey . '`)'))
            );

            $connection->query(
                'ALTER TABLE `' . $entityTable . '` AUTO_INCREMENT = ' . (max((int)$maxCode, (int)$maxEntity) + 1)
            );
        }

        return $this;
    }
}
