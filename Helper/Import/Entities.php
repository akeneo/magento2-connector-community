<?php

declare(strict_types=1);

namespace Akeneo\Connector\Helper\Import;

use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Model\Source\Edition;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Api\ProductApiInterface;
use Akeneo\Pim\ApiClient\Api\ProductUuidApiInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursor;
use Exception;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Model\Product as BaseProductModel;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;
use Zend_Db_Expr as Expr;
use Zend_Db_Select_Exception;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Entities
{
    /**
     * @var string TABLE_PREFIX
     */
    public const TABLE_PREFIX = 'tmp';
    /**
     * @var string TABLE_NAME
     */
    public const TABLE_NAME = 'akeneo_connector_entities';
    /**
     * @var array EXCLUDED_COLUMNS
     */
    public const EXCLUDED_COLUMNS = ['_links'];
    /**
     * Akeneo Connector product import code
     *
     * @var string IMPORT_CODE_PRODUCT
     */
    public const IMPORT_CODE_PRODUCT = 'product';
    /** @var string DEFAULT_ATTRIBUTE_LENGTH */
    public const DEFAULT_ATTRIBUTE_LENGTH = 'default';
    /** @var string TEXTAREA_ATTRIBUTE_LENGTH */
    public const TEXTAREA_ATTRIBUTE_LENGTH = '65535';
    /** @var string LARGE_ATTRIBUTE_LENGTH */
    public const LARGE_ATTRIBUTE_LENGTH = '2M';
    /**
     * @var mixed[] ATTRIBUTE_TYPES_LENGTH
     */
    public const ATTRIBUTE_TYPES_LENGTH = [
        'pim_catalog_identifier' => self::DEFAULT_ATTRIBUTE_LENGTH,
        'pim_catalog_text' => self::DEFAULT_ATTRIBUTE_LENGTH,
        'pim_catalog_textarea' => self::TEXTAREA_ATTRIBUTE_LENGTH,
        'pim_catalog_simpleselect' => self::DEFAULT_ATTRIBUTE_LENGTH,
        'pim_catalog_multiselect' => self::DEFAULT_ATTRIBUTE_LENGTH,
        'pim_catalog_boolean' => self::DEFAULT_ATTRIBUTE_LENGTH,
        'pim_catalog_date' => self::DEFAULT_ATTRIBUTE_LENGTH,
        'pim_catalog_number' => self::DEFAULT_ATTRIBUTE_LENGTH,
        'pim_catalog_metric' => self::DEFAULT_ATTRIBUTE_LENGTH,
        'pim_catalog_price_collection' => self::DEFAULT_ATTRIBUTE_LENGTH,
        'pim_catalog_image' => self::DEFAULT_ATTRIBUTE_LENGTH,
        'pim_catalog_file' => self::DEFAULT_ATTRIBUTE_LENGTH,
        'pim_catalog_asset_collection' => self::LARGE_ATTRIBUTE_LENGTH,
        'akeneo_reference_entity' => self::DEFAULT_ATTRIBUTE_LENGTH,
        'akeneo_reference_entity_collection' => self::DEFAULT_ATTRIBUTE_LENGTH,
        'pim_reference_data_simpleselect' => self::DEFAULT_ATTRIBUTE_LENGTH,
        'pim_reference_data_multiselect' => self::DEFAULT_ATTRIBUTE_LENGTH,
        'pim_catalog_table' => self::LARGE_ATTRIBUTE_LENGTH,
    ];
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
    protected Authenticator $authenticator;
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;
    /**
     * @var DeploymentConfig $deploymentConfig
     */
    protected $deploymentConfig;
    /**
     * @var string
     */
    protected $tablePrefix;
    /**
     * Mapped catalog attributes with relative scope
     *
     * @var string[] $attributeScopeMapping
     */
    protected $attributeScopeMapping = [];
    /**
     * Description $rowIdExists field
     *
     * @var bool[] $rowIdExists
     */
    protected $rowIdExists = [];
    protected array $attributeLength = [];

    /**
     * Entities constructor
     *
     * @param ResourceConnection $connection
     * @param DeploymentConfig $deploymentConfig
     * @param BaseProductModel $product
     * @param ConfigHelper $configHelper
     * @param Authenticator $authenticator
     */
    public function __construct(
        ResourceConnection $connection,
        DeploymentConfig $deploymentConfig,
        BaseProductModel $product,
        ConfigHelper $configHelper,
        Authenticator $authenticator
    ) {
        $this->connection       = $connection->getConnection();
        $this->deploymentConfig = $deploymentConfig;
        $this->configHelper     = $configHelper;
        $this->product          = $product;
        $this->authenticator    = $authenticator;
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
     * @param string|null $tableSuffix
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
    public function getTablePrefix()
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
     * @param array  $result
     * @param string $tableSuffix
     * @param string|null $family
     *
     * @return $this
     */
    public function createTmpTableFromApi(array $result, string $tableSuffix, ?string $family = null)
    {
        /** @var array $columns */
        $columns = $this->getColumnsFromResult($result);
        $this->createTmpTable(array_keys($columns), $tableSuffix, $family);

        return $this;
    }

    /**
     * Drop table if exist then create it
     *
     * @param array  $fields
     * @param string $tableSuffix
     * @param string|null $family
     *
     * @return $this
     * @throws \Zend_Db_Exception
     */
    public function createTmpTable(array $fields, string $tableSuffix, ?string $family = null)
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
                    $this->getAttributeColumnLength($family, $column),
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
         * @var mixed  $value
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
                            $data = implode(',', $data);
                        }
                        $columns[$key . '-' . $local] = $data;
                    } else {
                        if (isset($value[0]['code'])) {
                            // Skip attribute of table attributes to manage table attribute
                            continue;
                        }

                        $columns[$key] = implode(',', $value);
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
    public function formatColumn($column)
    {
        return trim(str_replace(PHP_EOL, '', preg_replace('/\s+/', ' ', trim($column))), '""');
    }

    /**
     * Insert data in the temporary table
     *
     * @param array       $result
     * @param null|string $tableSuffix
     * @param null|string $family
     *
     * @return bool
     */
    public function insertDataFromApi(array $result, ?string $tableSuffix = null, ?string $family = null)
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
                $this->connection->addColumn(
                    $tableName,
                    $key,
                    [
                        'type'    => 'text',
                        'length'  => $this->getAttributeColumnLength($family, $key), // Get correct column length
                        'default' => null,
                        'COMMENT' => ' '
                    ]
                );
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
     * @throws Exception
     */
    public function matchEntity($pimKey, $entityTable, $entityKey, $import, $prefix = null)
    {
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
        $connection = $this->connection;
        /** @var string $tableName */
        $tableName = $this->getTableName($import);

        if ($connection->tableColumnExists($tableName, 'code')) {
            /** @var string $codeIndexName */
            $codeIndexName = $connection->getIndexName($tableName, 'code');
            $connection->query('CREATE INDEX ' . $codeIndexName . ' ON ' . $tableName . ' (code(255));');
        }

        $connection->delete($tableName, [$pimKey . ' = ?' => '']);
        /** @var string $akeneoConnectorTable */
        $akeneoConnectorTable = $this->getTable('akeneo_connector_entities');
        /** @var string $entityTable */
        $entityTable = $this->getTable($entityTable);

        if ($entityKey == 'entity_id') {
            $entityKey = $this->getColumnIdentifier($entityTable);
        }

        /* Update entity_id column from akeneo_connector_entities table */
        $connection->query(
            '
            UPDATE `' . $tableName . '` t
            SET `_entity_id` = (
                SELECT `entity_id` FROM `' . $akeneoConnectorTable . '` c
                WHERE ' . ($prefix ? 'CONCAT(t.`' . $prefix . '`, "_", t.`' . $pimKey . '`)' : 't.`' . $pimKey . '`') . ' = c.`code`
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
                'code'      => $prefix ? new Expr('CONCAT(`' . $prefix . '`, "_", `' . $pimKey . '`)') : $pimKey,
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
    public function setValues(
        $import,
        $entityTable,
        $values,
        $entityTypeId,
        $storeId,
        $mode = AdapterInterface::INSERT_ON_DUPLICATE
    ) {
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
            /** @var string $table */
            $table = $this->getTable($entityTable . '_' . $backendType);
            /** @var string $identifier */
            $identifier = $this->getColumnIdentifier($table);
            /** @var bool $rowIdExists */
            $rowIdExists = $this->rowIdColumnExists($table);

            if ($rowIdExists && $entityTable === $this->getTablePrefix() . 'catalog_product_entity') {
                /** @var Select $select */
                $select = $connection->select()->from(
                    $tableName,
                    [
                        'attribute_id' => new Expr($attribute[AttributeInterface::ATTRIBUTE_ID]),
                        'store_id'     => new Expr($storeId),
                        'value'        => $value,
                    ]
                );
                $this->addJoinForContentStaging($select, [$identifier => 'row_id']);
            } elseif ($rowIdExists && $entityTable === $this->getTablePrefix() . 'catalog_category_entity') {
                /** @var Select $select */
                $select = $connection->select()->from(
                    $tableName,
                    [
                        'attribute_id' => new Expr($attribute[AttributeInterface::ATTRIBUTE_ID]),
                        'store_id'     => new Expr($storeId),
                        'value'        => $value,
                    ]
                );
                $this->addJoinForContentStagingCategory($select, [$identifier => 'row_id']);
            } else {
                /** @var Select $select */
                $select = $connection->select()->from(
                    $tableName,
                    [
                        'attribute_id' => new Expr($attribute[AttributeInterface::ATTRIBUTE_ID]),
                        'store_id'     => new Expr($storeId),
                        'value'        => $value,
                        $identifier    => '_entity_id',
                    ]
                );
            }

            /** @var string $insert */
            $insert = $connection->insertFromSelect(
                $select,
                $this->getTable($entityTable . '_' . $backendType),
                ['attribute_id', 'store_id', 'value', $identifier],
                $mode
            );
            $connection->query($insert);

            if ($backendType === 'datetime') {
                $values = [
                    'value' => new Expr('NULL'),
                ];
                $where  = [
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
            $connection->select()->from(
                $this->getTable('eav_attribute'),
                [
                    AttributeInterface::ATTRIBUTE_ID,
                    AttributeInterface::BACKEND_TYPE,
                ]
            )->where(AttributeInterface::ENTITY_TYPE_ID . ' = ?', $entityTypeId)->where(
                AttributeInterface::ATTRIBUTE_CODE . ' = ?',
                $code
            )->limit(1)
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
        /** @var int $entityTypeId */
        $entityTypeId = $this->configHelper->getEntityTypeId(ProductAttributeInterface::ENTITY_TYPE_CODE);
        /** @var Select $select */
        $select = $connection->select()->from(['a' => $eavAttribute], ['attribute_code'])->where(
            'a.entity_type_id = ?',
            $entityTypeId
        )->joinInner(['c' => $catalogAttribute], 'c.attribute_id = a.attribute_id', ['is_global']);

        /** @var string[] $attributeScopes */
        $attributeScopes = $connection->fetchPairs($select);
        if (!empty($attributeScopes)) {
            $this->attributeScopeMapping = $attributeScopes;
        }

        return $this->attributeScopeMapping;
    }

    /**
     * Description rowIdColumnExists function
     *
     * @param string $table
     *
     * @return bool
     */
    public function rowIdColumnExists($table)
    {
        if (!isset($this->rowIdExists[$table])) {
            $this->rowIdExists[$table] = $this->connection->tableColumnExists($table, 'row_id');
        }

        return $this->rowIdExists[$table];
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
        if ($this->rowIdColumnExists($table)) {
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
     * @param string|null $family
     *
     * @return \Akeneo\Connector\Helper\Import\Entities
     */
    public function copyColumn(string $tableName, string $source, string $target, ?string $family = null)
    {
        /** @var AdapterInterface $connection */
        $connection = $this->getConnection();

        if ($connection->tableColumnExists($tableName, $source)) {
            $connection->addColumn(
                $tableName,
                $target,
                [
                    'type'    => 'text',
                    'length'  => $this->getAttributeColumnLength($family, $target), // Get correct column length
                    'default' => '',
                    'COMMENT' => ' '
                ]
            );
            $connection->update(
                $tableName,
                [$target => new Expr('`' . $source . '`')]
            );
        }

        return $this;
    }

    /**
     * Delete entity relation
     *
     * @param string $import
     * @param int    $entityId
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
            'entity_id = ?' => $entityId,
        ];

        return $connection->delete($pimTable, $data);
    }

    /**
     * Set prefix to lower case to avoid problems with values import
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
            $keyParts    = explode('-', $key ?? '', 2);
            $keyParts[0] = strtolower($keyParts[0]);
            if (count($keyParts) > 1) {
                $newValues[$keyParts[0] . '-' . $keyParts[1]] = $data;
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
    public function formatUrlKeyColumn($tmpTable, $local = null)
    {
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
        $filenameParts = explode('.', $filename ?? '');
        // Get the extention
        /** @var string $extension */
        $extension = array_pop($filenameParts);
        // Get the hash
        $filename = implode('.', $filenameParts);
        $filename = explode('_', $filename ?? '');
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

    /**
     * Description addJoinForContentStaging function
     *
     * @param Select   $select
     * @param string[] $cols
     *
     * @return void
     */
    public function addJoinForContentStaging($select, $cols)
    {
        $productTable = $this->getTable('catalog_product_entity');
        $stagingTable = $this->getTable('staging_update');

        $select->joinLeft(
        // retrieve each product entity for each row_id.
        // We use "left join" to be able to create new product from Akeneo (they are not yet in catalog_product_entity)
            ['p' => $productTable],
            '_entity_id = p.entity_id',
            $cols
        )
            // retrieve all the staging update for the givens entities. We use "join left" to get the original entity
            ->joinLeft(
                ['s' => $stagingTable],
                'p.created_in = s.id',
                []
            );

        if (!$this->configHelper->isAkeneoMaster()) {
            /**
             * filter to get only "default product entities"
             * ie. product with 2 stagings scheduled will appear 5 times in catalog_product_entity table.
             * We only want row not updated by the content staging (the first, the one between
             * the 2 scheduled and the last).
             */
            $select->where(
            // filter to get only "default product entities"
            // ie. product with 2 stagings scheduled will appear 5 times in catalog_product_entity table.
            // We only want row not updated by the content staging (the first, the one between the 2 scheduled and the last).
                's.is_rollback = 1 OR s.id IS NULL'
            );
        }

        try {
            /**
             * if possible, we remove behaviour of the ContentStaging override on FromRenderer
             * @see \Magento\Staging\Model\Select\FromRenderer
             */
            $select->setPart('disable_staging_preview', true);
        } catch (Zend_Db_Select_Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * Description addJoinForContentStaging function
     *
     * @param Select   $select
     * @param string[] $cols
     *
     * @return void
     */
    public function addJoinForContentStagingCategory($select, $cols)
    {
        /**
         * retrieve each category entity for each row_id.
         * We use "left join" to be able to create new category from Akeneo
         * (they are not yet in catalog_category_entity)
         */
        $select->joinLeft(
            ['p' => 'catalog_category_entity'],
            '_entity_id = p.entity_id',
            $cols
        )
            /**
             * retrieve all the staging update for the givens entities. We use "join left" to get the original entity
             */
            ->joinLeft(
                ['s' => 'staging_update'],
                'p.created_in = s.id',
                []
            );

        if (!$this->configHelper->getCategoriesIsOverrideContentStaging()) {
            /**
             * filter to get only "default category entities"
             * ie. category with 2 stagings scheduled will appear 5 times in catalog_category_entity table.
             * We only want row not updated by the content staging
             * (the first, the one between the 2 scheduled and the last).
             */
            $select->where(
            // filter to get only "default category entities"
            // ie. category with 2 stagings scheduled will appear 5 times in catalog_category_entity table.
            // We only want row not updated by the content staging (the first, the one between the 2 scheduled and the last).
                's.is_rollback = 1 OR s.id IS NULL'
            );
        }

        try {
            /**
             * if possible, we remove behaviour of the ContentStaging override on FromRenderer
             * @see \Magento\Staging\Model\Select\FromRenderer
             */
            $select->setPart('disable_staging_preview', true);
        } catch (Zend_Db_Select_Exception $e) {
            $this->_logger->error($e->getMessage());
        }
    }

    /**
     * Description getMysqlVersion function
     *
     * @return string
     * @throws Exception
     */
    public function getMysqlVersion(): string
    {
        /** @var AdapterInterface $connection */
        $connection = $this->getConnection();
        /** @var string $mysqlVersionQuery */
        $mysqlVersionQuery = $connection->query('SELECT VERSION() AS version');
        /** @var mixed $mysqlVersion */
        $mysqlVersion = $mysqlVersionQuery->fetch();

        if (!$mysqlVersion['version']) {
            throw new Exception((string)__('Mysql version not recognized'));
        }

        return $mysqlVersion['version'];
    }

    /**
     * Get family attributes database recommended length
     *
     * @param string $familyCode
     *
     * @return mixed[]
     */
    protected function getAttributesLength(string $familyCode): array
    {
        /** @var AkeneoPimClientInterface|false $akeneoClient */
        $akeneoClient = $this->authenticator->getAkeneoApiClient();

        if (isset($this->attributeLength[$familyCode]) || !$akeneoClient) {
            return $this->attributeLength[$familyCode];
        }

        $attributeTypesLength = self::ATTRIBUTE_TYPES_LENGTH;
        /** @var mixed[] $family */
        $family = $akeneoClient->getFamilyApi()->get($familyCode);
        /** @var string[] $familyAttributesCode */
        $familyAttributesCode = $family['attributes'] ?? [];
        /** @var string|int $paginationSize */
        $paginationSize = $this->configHelper->getPaginationSize();
        $searchAttributesResult = [];
        $searchAttributesCode = [];
        // Batch API calls to avoid too large request URI
        foreach ($familyAttributesCode as $attributeCode) {
            $searchAttributesCode[] = $attributeCode;
            if (count($searchAttributesCode) === $paginationSize) {
                $searchAttributesResult[] = $akeneoClient->getAttributeApi()->all($paginationSize, [
                    'search' => [
                        'code' => [
                            [
                                'operator' => 'IN',
                                'value' => $searchAttributesCode,
                            ],
                        ],
                    ],
                ]);
                $searchAttributesCode = [];
            }
        }
        // Don't forget last page of attributes
        if (count($searchAttributesCode) > 1) {
            $searchAttributesResult[] = $akeneoClient->getAttributeApi()->all($paginationSize, [
                'search' => [
                    'code' => [
                        [
                            'operator' => 'IN',
                            'value' => $searchAttributesCode,
                        ],
                    ],
                ],
            ]);
        }

        /** @var ResourceCursor $familyAttributes */
        foreach ($searchAttributesResult as $familyAttributes) {
            foreach ($familyAttributes as $attribute) {
                if (!isset($attribute['code'], $attribute['type'])) {
                    continue;
                }
                $attributeCode = strtolower($attribute['code']);
                $attributeType = $attribute['type'];

                $this->attributeLength[$familyCode][$attributeCode] =
                    $attributeTypesLength[$attributeType] ?? self::DEFAULT_ATTRIBUTE_LENGTH;
            }
        }

        return $this->attributeLength[$familyCode] ?? [];
    }

    /**
     * Get attribute column length with family ATTRIBUTE_TYPES_LENGTH
     *
     * @param string|null $familyCode
     * @param string $attributeCode
     *
     * @return string|null
     */
    public function getAttributeColumnLength(?string $familyCode, string $attributeCode): ?string
    {
        if (!$familyCode) {
            return null;
        }

        $attributesLength = $this->getAttributesLength($familyCode);
        $attributeColumnLength = $attributesLength[strtok($attributeCode, '-')] ?? self::LARGE_ATTRIBUTE_LENGTH; // Add 2M by default to ensure "fake" reference entity attributes correct length
        return $attributeColumnLength === self::DEFAULT_ATTRIBUTE_LENGTH ? null : $attributeColumnLength; // Return null value for default attributes length
    }

    /**
     * Retrieve product API endpoint with UUID behavior
     * @see https://api.akeneo.com/getting-started/from-identifiers-to-uuid-7x/welcome.html#from-product-identifier-to-product-uuid
     *
     * @return ProductApiInterface|ProductUuidApiInterface
     */
    public function getProductApiEndpoint(AkeneoPimClientInterface $akeneoClient)
    {
        if ($this->isProductUuidEdition()) {
            /** @var ProductUuidApiInterface $productApi */
            return $akeneoClient->getProductUuidApi();
        }

        return $akeneoClient->getProductApi();
    }

    /**
     * Check if Akeneo edition is set on Serenity, Growth or V7.0
     */
    public function isProductUuidEdition(): bool
    {
        $edition = $this->configHelper->getEdition();

        return $edition === Edition::SERENITY || $edition === Edition::GROWTH || $edition === Edition::SEVEN;
    }
}
