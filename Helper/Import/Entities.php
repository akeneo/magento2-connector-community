<?php

namespace Akeneo\Connector\Helper\Import;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Zend_Db_Expr as Expr;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Magento\Catalog\Model\Product as BaseProductModel;

/**
 * Class Entities
 *
 * @category  Class
 * @package   Akeneo\Connector\Helper\Import
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Entities extends AbstractHelper
{
    /**
     * @var string TABLE_PREFIX
     */
    const TABLE_PREFIX = 'tmp';
    /**
     * @var string TABLE_NAME
     */
    const TABLE_NAME = 'akeneo_connector_entities';
    /**
     * @var array EXCLUDED_COLUMNS
     */
    const EXCLUDED_COLUMNS = ['_links'];
    /**
     * Akeneo Connector product import code
     *
     * @var string IMPORT_CODE_PRODUCT
     */
    const IMPORT_CODE_PRODUCT = 'product';

    /**
     * This variable contains a ResourceConnection
     *
     * @var ResourceConnection $connection
     */
    protected $connection;
    /**
     * This variable contains a ProductModel
     *
     * @var BaseProductModel $product
     */
    protected $product;
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;
    /**
     * @var DeploymentConfig $deploymentConfig
     */
    private $deploymentConfig;
    /**
     * @var string
     */
    protected $tablePrefix;
    /**
     * Product attributes to pass if empty value
     *
     * @var string[] $passIfEmpty
     */
    protected $passIfEmpty = [
        'price',
    ];
    /**
     * Mapped catalog attributes with relative scope
     *
     * @var string[] $attributeScopeMapping
     */
    protected $attributeScopeMapping = [];

    /**
     * Entities constructor
     *
     * @param Context            $context
     * @param ResourceConnection $connection
     * @param DeploymentConfig   $deploymentConfig
     * @param ConfigHelper       $configHelper
     * @param BaseProductModel   $product
     */
    public function __construct(
        Context $context,
        ResourceConnection $connection,
        DeploymentConfig $deploymentConfig,
        BaseProductModel $product,
        ConfigHelper $configHelper
    ) {
        parent::__construct($context);

        $this->connection       = $connection->getConnection();
        $this->deploymentConfig = $deploymentConfig;
        $this->configHelper     = $configHelper;
        $this->product          = $product;
    }

    /**
     * Retrieve Connection object
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get temporary table name
     *
     * @param null $tableSuffix
     *
     * @return string
     */
    public function getTableName($tableSuffix = null)
    {
        /** @var array $fragments */
        $fragments = [
            self::TABLE_PREFIX,
            self::TABLE_NAME,
        ];

        if ($tableSuffix) {
            $fragments[] = $tableSuffix;
        }

        return $this->getTable(join('_', $fragments));
    }

    /**
     * Retrieve table name with prefix
     *
     * @param string $tableName
     *
     * @return string
     */
    public function getTable($tableName)
    {
        return $this->getTablePrefix() . $this->connection->getTableName($tableName);
    }

    /**
     * Get table prefix
     *
     * @return string
     */
    private function getTablePrefix()
    {
        if (null === $this->tablePrefix) {
            $this->tablePrefix = (string)$this->deploymentConfig->get(
                ConfigOptionsListConstants::CONFIG_PATH_DB_PREFIX
            );
        }
        return $this->tablePrefix;
    }

    /**
     * Create temporary table from api result
     *
     * @param array $result
     * @param string $tableSuffix
     *
     * @return $this
     */
    public function createTmpTableFromApi($result, $tableSuffix)
    {
        /** @var array $columns */
        $columns = $this->getColumnsFromResult($result);
        $this->createTmpTable(array_keys($columns), $tableSuffix);

        return $this;
    }

    /**
     * Drop table if exist then create it
     *
     * @param array $fields
     * @param string $tableSuffix
     *
     * @return $this
     * @throws \Zend_Db_Exception
     */
    private function createTmpTable($fields, $tableSuffix)
    {
        /* Delete table if exists */
        $this->dropTable($tableSuffix);
        /** @var string $tableName */
        $tableName = $this->getTableName($tableSuffix);

        /* Create new table */
        /** @var Table $table */
        $table = $this->connection->newTable($tableName);

        $fields = array_diff($fields, ['identifier']);

        $table->addColumn(
            'identifier',
            Table::TYPE_TEXT,
            255,
            [],
            'identifier'
        );

        $table->addIndex(
            'UNIQUE_IDENTIFIER',
            'identifier',
            ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
        );

        /** @var string $field */
        foreach ($fields as $field) {
            if ($field) {
                /** @var string $column */
                $column = $this->formatColumn($field);
                $table->addColumn(
                    $column,
                    Table::TYPE_TEXT,
                    null,
                    [],
                    $column
                );
            }
        }

        $table->addColumn(
            '_entity_id',
            Table::TYPE_INTEGER,
            11,
            [],
            'Entity Id'
        );

        $table->addIndex(
            'UNIQUE_ENTITY_ID',
            '_entity_id',
            ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
        );

        $table->addColumn(
            '_is_new',
            Table::TYPE_SMALLINT,
            1,
            ['default' => 0],
            'Is New'
        );

        $table->setOption('type', 'MYISAM');

        $this->connection->createTable($table);

        return $this;
    }

    /**
     * Get columns from the api result
     *
     * @param array $result
     *
     * @return array
     */
    protected function getColumnsFromResult(array $result)
    {
        /** @var array $columns */
        $columns = [];
        /**
         * @var string $key
         * @var mixed $value
         */
        foreach ($result as $key => $value) {
            if (in_array($key, static::EXCLUDED_COLUMNS)) {
                continue;
            }

            $columns[$key] = $value;

            if (is_array($value)) {
                if (empty($value)) {
                    $columns[$key] = null;
                    continue;
                }
                unset($columns[$key]);
                foreach ($value as $local => $v) {
                    if (!is_numeric($local)) {
                        $data = $v;
                        if (is_array($data)) {
                            $data = join(',', $data);
                        }
                        $columns[$key . '-' . $local] = $data;
                    } else {
                        $columns[$key] = join(',', $value);
                    }
                }
            }
        }

        return $columns;
    }

    /**
     * Drop temporary table
     *
     * @param string $tableSuffix
     *
     * @return $this
     */
    public function dropTable($tableSuffix)
    {
        /** @var string $tableName */
        $tableName = $this->getTableName($tableSuffix);

        $this->connection->resetDdlCache($tableName);
        $this->connection->dropTable($tableName);

        return $this;
    }

    /**
     * Format column name
     *
     * @param string $column
     *
     * @return string
     */
    private function formatColumn($column)
    {
        return trim(str_replace(PHP_EOL, '', preg_replace('/\s+/', ' ', trim($column))), '""');
    }

    /**
     * Insert data in the temporary table
     *
     * @param array       $result
     * @param null|string $tableSuffix
     *
     * @return bool
     */
    public function insertDataFromApi(array $result, $tableSuffix = null)
    {
        if (empty($result)) {
            return false;
        }

        /** @var string $tableName */
        $tableName = $this->getTableName($tableSuffix);

        /** @var string[] $result */
        $result = $this->getColumnsFromResult($result);

        /** @var string[] $fields */
        $fields = array_diff_key($result, ['identifier' => null]);
        $fields = array_keys($fields);

        /**
         * @var string $key
         * @var        $string $value
         */
        foreach ($result as $key => $value) {
            if (!$this->connection->tableColumnExists($tableName, $key)) {
                $this->connection->addColumn($tableName, $key, 'text');
            }
        }

        $this->connection->insertOnDuplicate($tableName, $result, $fields);

        return true;
    }

    /**
     * Match Magento Id with code
     *
     * @param string $pimKey
     * @param string $entityTable
     * @param string $entityKey
     * @param string $import
     * @param string $prefix
     *
     * @return \Akeneo\Connector\Helper\Import\Entities
     */
    public function matchEntity($pimKey, $entityTable, $entityKey, $import, $prefix = null)
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
        $connection = $this->connection;
        /** @var string $tableName */
        $tableName = $this->getTableName($import);

        $connection->delete($tableName, [$pimKey . ' = ?' => '']);
        /** @var string $akeneoConnectorTable */
        $akeneoConnectorTable = $this->getTable('akeneo_connector_entities');
        /** @var string $entityTable */
        $entityTable = $this->getTable($entityTable);

        if ($entityKey == 'entity_id') {
            $entityKey = $this->getColumnIdentifier($entityTable);
        }

        /* Update entity_id column from akeneo_connector_entities table */
        $connection->query('
            UPDATE `' . $tableName . '` t
            SET `_entity_id` = (
                SELECT `entity_id` FROM `' . $akeneoConnectorTable . '` c
                WHERE ' . ($prefix ? 'CONCAT(t.`' . $prefix . '`, "_", t.`' . $pimKey . '`)' : 't.`' . $pimKey . '`') . ' = c.`code`
                    AND c.`import` = "' . $import . '"
            )
        ');

        /* Set entity_id for new entities */
        /** @var string $query */
        $query = $connection->query('SHOW TABLE STATUS LIKE "' . $entityTable . '"');
        /** @var mixed $row */
        $row = $query->fetch();

        $connection->query('SET @id = ' . (int)$row['Auto_increment']);
        /** @var array $values */
        $values = [
            '_entity_id' => new Expr('@id := @id + 1'),
            '_is_new' => new Expr('1'),
        ];
        $connection->update($tableName, $values, '_entity_id IS NULL');

        /* Update akeneo_connector_entities table with code and new entity_id */
        /** @var Select $select */
        $select = $connection->select()
            ->from(
                $tableName,
                [
                    'import' => new Expr("'" . $import . "'"),
                    'code' => $prefix ? new Expr('CONCAT(`' . $prefix . '`, "_", `' . $pimKey . '`)') : $pimKey,
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
                $connection->select()
                    ->from($akeneoConnectorTable, new Expr('MAX(`entity_id`)'))
                    ->where('import = ?', $import)
            );
            /** @var string $maxEntity */
            $maxEntity = $connection->fetchOne(
                $connection->select()
                    ->from($entityTable, new Expr('MAX(`' . $entityKey . '`)'))
            );

            $connection->query(
                'ALTER TABLE `' . $entityTable . '` AUTO_INCREMENT = ' . (max((int)$maxCode, (int)$maxEntity) + 1)
            );
        }

        return $this;
    }

    /**
     * Set values to attributes
     *
     * @param string $import
     * @param string $entityTable
     * @param array  $values
     * @param int    $entityTypeId
     * @param int    $storeId
     * @param int    $mode
     *
     * @return \Akeneo\Connector\Helper\Import\Entities
     */
    public function setValues($import, $entityTable, $values, $entityTypeId, $storeId, $mode = AdapterInterface::INSERT_ON_DUPLICATE)
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
        $connection = $this->getConnection();
        /** @var string $tableName */
        $tableName = $this->getTableName($import);

        /**
         * @var string $code
         * @var string $value
         */
        foreach ($values as $code => $value) {
            /** @var array|bool $attribute */
            $attribute = $this->getAttribute($code, $entityTypeId);

            if (empty($attribute)) {
                continue;
            }

            if (!isset($attribute[AttributeInterface::BACKEND_TYPE])) {
                continue;
            }

            if ($attribute[AttributeInterface::BACKEND_TYPE] === 'static') {
                continue;
            }

            /** @var string $backendType */
            $backendType = $attribute[AttributeInterface::BACKEND_TYPE];
            /** @var string $identifier */
            $identifier = $this->getColumnIdentifier($this->getTable($entityTable . '_' . $backendType));

            /** @var \Magento\Framework\DB\Select $select */
            $select = $connection->select()->from(
                $tableName,
                [
                    'attribute_id' => new Expr($attribute[AttributeInterface::ATTRIBUTE_ID]),
                    'store_id'     => new Expr($storeId),
                    $identifier    => '_entity_id',
                    'value'        => $value,
                ]
            );

            /** @var bool $columnExists */
            $columnExists = $connection->tableColumnExists($tableName, $value);
            if ($columnExists && ($import !== self::IMPORT_CODE_PRODUCT || in_array($code, $this->passIfEmpty))) {
                $select->where(sprintf('TRIM(`%s`) > ?', $value), new Expr('""'));
            }

            /** @var string $insert */
            $insert = $connection->insertFromSelect(
                $select,
                $this->getTable($entityTable . '_' . $backendType),
                ['attribute_id', 'store_id', $identifier, 'value'],
                $mode
            );
            $connection->query($insert);

            if ($backendType === 'datetime') {
                $values = [
                    'value' => new Expr('NULL'),
                ];
                $where = [
                    'value = ?' => '0000-00-00 00:00:00',
                ];
                $connection->update(
                    $this->getTable($entityTable . '_' . $backendType),
                    $values,
                    $where
                );
            }
        }

        return $this;
    }

    /**
     * Retrieve attribute
     *
     * @param string $code
     * @param int    $entityTypeId
     *
     * @return bool|array
     */
    public function getAttribute($code, $entityTypeId)
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
        $connection = $this->connection;

        /** @var array $attribute */
        $attribute = $connection->fetchRow(
            $connection->select()
                ->from(
                    $this->getTable('eav_attribute'),
                    [
                        AttributeInterface::ATTRIBUTE_ID,
                        AttributeInterface::BACKEND_TYPE
                    ]
                )
                ->where(AttributeInterface::ENTITY_TYPE_ID . ' = ?', $entityTypeId)
                ->where(AttributeInterface::ATTRIBUTE_CODE . ' = ?', $code)
                ->limit(1)
        );

        if (empty($attribute)) {
            return false;
        }

        return $attribute;
    }

    /**
     * Retrieve catalog attributes mapped with relative scope
     *
     * @return string[]
     */
    public function getAttributeScopeMapping()
    {
        if (!empty($this->attributeScopeMapping)) {
            return $this->attributeScopeMapping;
        }

        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
        $connection = $this->connection;
        /** @var string $catalogAttribute */
        $catalogAttribute = $this->getTable('catalog_eav_attribute');
        /** @var string $eavAttribute */
        $eavAttribute = $this->getTable('eav_attribute');
        /** @var Select $select */
        $select = $connection->select()->from(['a' => $eavAttribute], ['attribute_code'])->joinInner(['c' => $catalogAttribute], 'c.attribute_id = a.attribute_id', ['is_global']);

        /** @var string[] $attributeScopes */
        $attributeScopes = $connection->fetchPairs($select);
        if (!empty($attributeScopes)) {
            $this->attributeScopeMapping = $attributeScopes;
        }

        return $this->attributeScopeMapping;
    }

    /**
     * Retrieve if row id column exists
     *
     * @param string $table
     * @param string $identifier
     *
     * @return string
     */
    public function getColumnIdentifier($table, $identifier = 'entity_id')
    {
        if ($this->connection->tableColumnExists($table, 'row_id')) {
            $identifier = 'row_id';
        }

        return $identifier;
    }

    /**
     * Copy column to an other
     *
     * @param string $tableName
     * @param string $source
     * @param string $target
     *
     * @return \Akeneo\Connector\Helper\Import\Entities
     */
    public function copyColumn($tableName, $source, $target)
    {
        /** @var AdapterInterface $connection */
        $connection = $this->getConnection();

        if ($connection->tableColumnExists($tableName, $source)) {
            $connection->addColumn($tableName, $target, 'text');
            $connection->update(
                $tableName, array($target => new Expr('`' . $source . '`'))
            );
        }

        return $this;
    }

    /**
     * Delete entity relation
     *
     * @param string $import
     * @param int $entityId
     *
     * @return int The number of affected rows.
     */
    public function delete($import, $entityId)
    {
        /** @var AdapterInterface $connection */
        $connection = $this->getConnection();

        /** @var string $pimTable */
        $pimTable = $this->getTable('akeneo_connector_entities');

        /** @var array $data */
        $data = [
            'import = ?'    => $import,
            'entity_id = ?' => $entityId
        ];

        return $connection->delete($pimTable, $data);
    }

    /**
     * Set prefix to lower case
     * to avoid problems with values import
     *
     * @param string[] $values
     *
     * @return string[]
     */
    public function prefixToLowerCase($values)
    {
        /** @var string[] $newValues */
        $newValues = [];
        foreach ($values as $key => $data) {
            /** @var string[] $keyParts */
            $keyParts    = explode('-', $key, 2);
            $keyParts[0] = strtolower($keyParts[0]);
            if (count($keyParts) > 1) {
                $newValues[$keyParts[0].'-'.$keyParts[1]] = $data;
            } else {
                $newValues[$keyParts[0]] = $data;
            }
        }
        return $newValues;
    }

    /**
     * Format the url_key column of a given table, suffix is optional
     *
     * @param string      $tmpTable
     * @param null|string $local
     *
     * @return void
     */
    public function formatUrlKeyColumn($tmpTable, $local = null) {
        /** @var bool $isUrlKeyMapped */
        $isUrlKeyMapped = $this->configHelper->isUrlKeyMapped();
        /** @var string $columnKey */
        $columnKey = 'url_key';
        if ($local !== null) {
            $columnKey = 'url_key-' . $local;
        }
        if ($isUrlKeyMapped && $this->connection->tableColumnExists($tmpTable, $columnKey)) {
            /** @var \Magento\Framework\DB\Select $select */
            $select = $this->connection->select()->from(
                $tmpTable,
                [
                    'identifier' => 'identifier',
                    'url_key'    => $columnKey,
                ]
            );
            /** @var \Magento\Framework\DB\Statement\Pdo\Mysql $query */
            $query = $this->connection->query($select);

            /** @var array $row */
            while (($row = $query->fetch())) {
                if (isset($row['url_key'])) {
                    $row['url_key'] = $this->product->formatUrlKey($row['url_key']);
                    $this->connection->update(
                        $tmpTable,
                        [
                            $columnKey => $row['url_key'],
                        ],
                        ['identifier = ?' => $row['identifier']]
                    );
                }
            }
        }
    }

    /**
     * Format media filename, removing hash and stoppig at 90 characters
     *
     * @param string $filename
     *
     * @return string
     */
    public function formatMediaName($filename)
    {
        /** @var string[] $filenameParts */
        $filenameParts = explode('.', $filename);
        // Get the extention
        /** @var string $extension */
        $extension = array_pop($filenameParts);
        // Get the hash
        $filename = implode('.', $filenameParts);
        $filename = explode('_', $filename);
        /** @var string $shortHash */
        $shortHash = array_shift($filename);
        $shortHash = substr($shortHash, 0, 4);
        $filename  = implode('_', $filename);
        // Form the final file name
        /** @var string $shortName */
        $shortName = substr($filename, 0, 79);
        /** @var string $finalName */
        $finalName = $shortName . '_' . $shortHash . '.' . $extension;

        return $finalName;
    }
}
