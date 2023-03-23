<?php

namespace Akeneo\Connector\Helper\Import;

use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Magento\Catalog\Model\Product as BaseProductModel;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Serialize\Serializer\Json;
use Zend_Db_Expr as Expr;
use Zend_Db_Statement_Exception;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Product extends Entities
{
    /**
     * @var array EXCLUDED_COLUMNS
     */
    public const EXCLUDED_COLUMNS = ['_links'];
    /**
     * @var string ASSOCIATIONS_KEY
     */
    public const ASSOCIATIONS_KEY = 'associations';
    /**
     * @var string VALUES_KEY
     */
    public const VALUES_KEY = 'values';
    /**
     * @var string COMPLETENESS_KEY
     */
    public const COMPLETENESS_KEY = 'completenesses';
    /**
     * QUANTIFIED_ASSOCIATIONS_KEY const
     *
     * @var string QUANTIFIED_ASSOCIATIONS_KEY
     */
    public const QUANTIFIED_ASSOCIATIONS_KEY = 'quantified_associations';
    /**
     * ALL_ASSOCIATIONS_KEY const
     *
     * @var string[] ALL_ASSOCIATIONS_KEY
     */
    public const ALL_ASSOCIATIONS_KEY = [self::QUANTIFIED_ASSOCIATIONS_KEY, self::ASSOCIATIONS_KEY];
    /**
     * This variable contains a Json
     *
     * @var Json $jsonSerializer
     */
    protected $jsonSerializer;
    /**
     * This variable contains a ProductUrlPathGenerator
     *
     * @var ProductUrlPathGenerator $productUrlPathGenerator
     */
    protected $productUrlPathGenerator;
    /**
     * Description $scopeConfig field
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;
    protected Authenticator $authenticator;

    /**
     * Product constructor
     *
     * @param ResourceConnection      $connection
     * @param DeploymentConfig        $deploymentConfig
     * @param BaseProductModel        $product
     * @param ProductUrlPathGenerator $productUrlPathGenerator
     * @param ConfigHelper            $configHelper
     * @param Authenticator           $authenticator
     * @param Json                    $jsonSerializer
     * @param ScopeConfigInterface    $scopeConfig
     */
    public function __construct(
        ResourceConnection $connection,
        DeploymentConfig $deploymentConfig,
        BaseProductModel $product,
        ProductUrlPathGenerator $productUrlPathGenerator,
        ConfigHelper $configHelper,
        Authenticator $authenticator,
        Json $jsonSerializer,
        ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($connection, $deploymentConfig, $product, $configHelper, $authenticator);

        $this->jsonSerializer          = $jsonSerializer;
        $this->productUrlPathGenerator = $productUrlPathGenerator;
        $this->scopeConfig             = $scopeConfig;
    }

    /**
     * Get columns from the api result
     *
     * @param array $result
     * @param array $keys
     *
     * @return array
     */
    protected function getColumnsFromResult(array $result, array $keys = [])
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

            if (in_array($key, self::ALL_ASSOCIATIONS_KEY, true)) {
                /** @var array $values */
                $values = $this->formatAssociations($value, $key);
                /** @var array $columns */
                $columns = $columns + $values;

                continue;
            }

            if ($key === self::COMPLETENESS_KEY) {
                /** @var array $values */
                $values = $this->formatCompleteness($value, $key);

                /** @var array $columns */
                $columns = $columns + $values;

                continue;
            }

            if ($key === self::VALUES_KEY) {
                /** @var array $values */
                $values = $this->formatValues($value);
                /** @var string[] $newValues */
                $newValues = $this->prefixToLowerCase($values); // Set prefix attribut to lower case
                /** @var array $columns */
                $columns = $columns + $newValues;

                continue;
            }
            $columns[$key] = $value;

            if (!is_array($value)) {
                continue;
            }
            if (empty($value)) {
                $columns[$key] = null;

                continue;
            }
            unset($columns[$key]);
            /**
             * @var string|int   $local
             * @var string|array $data
             */
            foreach ($value as $local => $data) {
                if (!is_numeric($local)) {
                    if (is_array($data)) {
                        $data = join(',', $data);
                    }
                    $columns[$key . '-' . $local] = $data;
                } else {
                    $columns[$key] = join(',', $value);
                }
            }
        }

        return $columns;
    }

    /**
     * Format values field containing all the attribute values
     *
     * @param array $values
     *
     * @return array
     */
    public function formatValues(array $values)
    {
        /** @var array $columns */
        $columns = [];
        /**
         * @var string $attribute
         * @var array  $value
         */
        foreach ($values as $attribute => $value) {
            if ($attribute === 'price' && !$this->isFieldInAttributeMapping('price')) {
                continue;
            }
            if ($attribute === 'special_price' && !$this->isFieldInAttributeMapping('special_price')) {
                continue;
            }
            if ($attribute === 'cost' && !$this->isFieldInAttributeMapping('cost')) {
                continue;
            }
            /** @var array $attributeValue */
            foreach ($value as $attributeValue) {
                /** @var string $key */
                $key = $this->getKey($attribute, $attributeValue);

                // Attribute is a text, textarea, number, date, yes/no, simpleselect, file
                if (!is_array($attributeValue['data'])) {
                    $columns[$key] = $attributeValue['data'];

                    continue;
                }
                // Attribute is a metric
                if (array_key_exists('amount', $attributeValue['data'])) {
                    $columns[$key] = $attributeValue['data']['amount'];

                    continue;
                }
                // Attribute is a multiselect
                if ((isset($attributeValue['data'][0]) && !is_array($attributeValue['data'][0]))) {
                    $columns[$key] = join(',', $attributeValue['data']);

                    continue;
                }
                // Attribute is a price or table
                /** @var string[] $table */
                $table = [];
                /** @var array $attr */
                foreach ($attributeValue['data'] as $attr) {
                    if (!is_array($attr)) {
                        continue;
                    }

                    // Table attribute
                    if (!array_key_exists('currency', $attr) || !array_key_exists('amount', $attr)) {
                        $table[] = $attr;

                        $columns[$key] = json_encode(
                            $table,
                            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                        );
                    } else { // Price attribute
                        /** @var string $priceKey */
                        $priceKey           = $key . '-' . $attr['currency'];
                        $columns[$priceKey] = $attr['amount'];
                    }
                }
            }
        }

        return $columns;
    }

    /**
     * Format associations field
     *
     * @param mixed[]     $values
     * @param string|null $assoKey
     *
     * @return array
     */
    public function formatAssociations(array $values, ?string $assoKey = null)
    {
        /** @var array $associations */
        $associations = [];

        /**
         * @var string $group
         * @var array  $types
         */
        foreach ($values as $group => $types) {
            /**
             * @var string $key
             * @var array  $product
             */
            foreach ($types as $key => $products) {
                if (empty(array_filter($products))) {
                    continue;
                }
                /** @var string $name */
                $name = $group . '-' . $key;

                if ($assoKey === self::QUANTIFIED_ASSOCIATIONS_KEY) {
                    /** @var string[] $finalProducts */
                    $finalProducts = [];
                    /** @var string[] $product */
                    foreach ($products as $product) {
                        $key = 'identifier';
                        if ($this->isProductUuidEdition()) {
                            $key = 'uuid';
                        }
                        $finalProducts[] = $product[$key] . ';' . $product['quantity'];
                    }
                    $products = $finalProducts;
                }

                $associations[$name] = implode(',', $products);
            }
        }

        if (empty($associations)) {
            return [];
        }

        return $associations;
    }

    /**
     * Format completeness field
     *
     * @param mixed[]     $values
     * @param string|null $assoKey
     *
     * @return array
     */
    public function formatCompleteness(array $values, ?string $assoKey = null)
    {
        /** @var array $completeness */
        $completeness = [];

        /** @var string[] $finalProducts */
        $finalProducts = [];

        /**
         * @var array    $values
         * @var string[] $product
         */
        foreach ($values as $product) {
            if (empty($product)) {
                continue;
            }
            if ($assoKey === self::COMPLETENESS_KEY) {
                $finalProducts[] = $product;
            }
        }

        if (isset($finalProducts)) {
            $completeness['completenesses_' . $product['scope']] = $this->jsonSerializer->serialize($finalProducts);
        }

        if (empty($completeness)) {
            return [];
        }

        return $completeness;
    }

    /**
     * Get attribute key to be inserted as a column
     *
     * @param string $attribute
     * @param array  $attributeValue
     *
     * @return string
     */
    public function getKey($attribute, array $attributeValue)
    {
        /** @var string $key */
        $key = strtolower($attribute);
        if (isset($attributeValue['locale']) && isset($attributeValue['scope'])) {
            $key = join('-', [$attribute, $attributeValue['locale'], $attributeValue['scope']]);
        } elseif (isset($attributeValue['locale'])) {
            $key = join('-', [$attribute, $attributeValue['locale']]);
        } elseif (isset($attributeValue['scope'])) {
            $key = join('-', [$attribute, $attributeValue['scope']]);
        }

        return (string)$key;
    }

    /**
     * Check if a given attribute is mapped magento-side
     *
     * @param string $field
     *
     * @return bool
     */
    public function isFieldInAttributeMapping($field)
    {
        /** @var string $matches */
        $matches = $this->scopeConfig->getValue(ConfigHelper::PRODUCT_ATTRIBUTE_MAPPING);
        /** @var mixed[] $matches */
        $matches = $this->jsonSerializer->unserialize($matches);
        if (!is_array($matches)) {
            return false;
        }
        /** @var string[] $match */
        foreach ($matches as $match) {
            if (!isset($match['akeneo_attribute'], $match['magento_attribute'])) {
                continue;
            }
            if ($match['magento_attribute'] === $field) {
                return true;
            }
        }

        return false;
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
     * @return Product
     * @throws Zend_Db_Statement_Exception
     */
    public function matchEntity($pimKey, $entityTable, $entityKey, $import, $prefix = null)
    {
        /** @var AdapterInterface $connection */
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

        /* Connect existing Magento products to new Akeneo items */ // Get existing entities from Akeneo table
        /** @var Select $select */
        $select = $connection->select()->from($akeneoConnectorTable, ['entity_id' => 'entity_id'])->where(
            'import = ?',
            'product'
        );
        /** @var string[] $existingEntities */
        $existingEntities = $connection->query($select)->fetchAll();
        $existingEntities = array_column($existingEntities, 'entity_id');

        // Get all entities that are being imported and already present in Magento
        $select = $connection->select()->from(['t' => $tableName], ['sku' => 't.identifier'])->joinInner(
            ['e' => $entityTable],
            't.identifier = e.sku'
        );
        /** @var string $query */
        $query = $connection->query($select);
        /** @var mixed $row */
        while ($row = $query->fetch()) {
            // Create a row in Akeneo table for products present in Magento and Akeneo that were never imported before
            if (!in_array($row['entity_id'], $existingEntities)) {
                $values = [
                    'import'    => 'product',
                    'code'      => $row['sku'],
                    'entity_id' => $row['entity_id'],
                ];
                $connection->insertOnDuplicate($akeneoConnectorTable, $values);
            }
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
     * Verify product url during url rewrite returns the correct request path
     *
     * @param string           $requestPath
     * @param BaseProductModel $product
     *
     * @return string
     */
    public function verifyProductUrl($requestPath, $product)
    {
        /** @var AdapterInterface $connection */
        $connection = $this->getConnection();
        /** @var int $suffix */
        $suffix = 1;
        /** @var string|null $exists */
        do {
            /** @var bool $exists */
            $exists = $connection->fetchOne(
                $connection->select()->from($this->getTable('url_rewrite'), new Expr(1))->where(
                    'entity_type = ?',
                    ProductUrlRewriteGenerator::ENTITY_TYPE
                )->where(
                    'request_path = ?',
                    $requestPath
                )->where('store_id = ?', $product->getStoreId())->where('entity_id <> ?', $product->getEntityId())
            );
            if ($exists) {
                if ($suffix == 1) {
                    $product->setUrlKey($product->getUrlKey() . '-' . $suffix);
                }
                if ($suffix >= 2) {
                    $product->setUrlKey(substr($product->getUrlKey(), 0, -(strlen((string)($suffix - 1)) + 1)) . '-' . $suffix);
                }
                /** @var string $requestPath */
                $requestPath = $this->productUrlPathGenerator->getUrlPathWithSuffix(
                    $product,
                    $product->getStoreId()
                );
            }
            ++$suffix;
        } while ($exists);

        return $requestPath;
    }

    /**
     * Check if given family is a grouped family
     *
     * @param string[] $family
     *
     * @return bool
     */
    public function isFamilyGrouped($family)
    {
        /** @var string[] $groupedFamilies */
        $groupedFamilies = $this->configHelper->getGroupedFamiliesToImport();
        if (in_array($family, $groupedFamilies)) {
            return true;
        }

        return false;
    }

    /**
     * Retrieve attributes that are filterable into imported family
     * Compare family attributes to product and product model filters
     * If filter is disabled or empty, return all uniques family attributes
     *
     * @param mixed[] $familyAttributesCode
     * @param mixed[] $productFilters
     * @param mixed[] $productModelFilters
     *
     * @return string[]
     */
    public function getFilterableFamilyAttributes(array $familyAttributesCode, array $productFilters, array $productModelFilters): array
    {
        // Manage product attribute mapping configuration for native price attributes
        /** @var mixed[] $attributesMapping */
        $attributesMapping = $this->configHelper->getAttributeMapping();
        $nativePriceAttributes = ['cost', 'price', 'special_price'];
        $familyAttributesCode = array_diff($familyAttributesCode, $nativePriceAttributes); // Remove native prices attributes from family attributes
        if (!empty($attributesMapping)) {
            foreach ($attributesMapping as $attributeMapping) {
                if (isset($attributeMapping['akeneo_attribute'], $attributeMapping['magento_attribute']) &&
                    in_array($attributeMapping['magento_attribute'], $nativePriceAttributes, true)
                ) {
                    $familyAttributesCode[] = $attributeMapping['akeneo_attribute']; // Add mapped price attribute
                }
            }
        }

        // Manage product and product model filters
        $filterableAttributes = [];
        $filters = array_merge($productFilters, $productModelFilters);
        /** @var mixed[] $filter */
        foreach ($filters as $filter) {
            $attributesCodes = explode(',', $filter['attributes'] ?? '');
            $filterableAttributes = array_merge($filterableAttributes, $attributesCodes);
        }

        $filterableFamilyAttributes = array_intersect($familyAttributesCode, $filterableAttributes); // Get only filterable family attribute

        return !empty($filterableFamilyAttributes) ? array_unique($filterableFamilyAttributes) : $familyAttributesCode;
    }

    /**
     * @param AkeneoPimClientInterface $akeneoClient
     *
     * @return string[]
     */
    public function getEnabledCurrencies(AkeneoPimClientInterface $akeneoClient): array
    {
        $currencies = $akeneoClient->getCurrencyApi()->all();
        $enabledCurrencies = [];
        /** @var mixed[] $currency */
        foreach ($currencies as $currency) {
            if (isset($currency['enabled'], $currency['code']) && $currency['enabled']) {
                $enabledCurrencies[] = $currency['code'];
            }
        }

        return $enabledCurrencies;
    }
}
