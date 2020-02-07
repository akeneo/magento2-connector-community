<?php

namespace Akeneo\Connector\Helper\Import;

use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\Serializer as JsonSerializer;
use Magento\Catalog\Model\Product as BaseProductModel;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Zend_Db_Expr as Expr;

/**
 * Class Product
 *
 * @category  Class
 * @package   Akeneo\Connector\Helper\Import
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Product extends Entities
{
    /**
     * @var array EXCLUDED_COLUMNS
     */
    const EXCLUDED_COLUMNS = ['_links'];
    /**
     * @var string ASSOCIATIONS_KEY
     */
    const ASSOCIATIONS_KEY = 'associations';
    /**
     * @var string VALUES_KEY
     */
    const VALUES_KEY = 'values';
    /**
     * This variable contains a JsonSerializer
     *
     * @var JsonSerializer $serializer
     */
    protected $serializer;
    /**
     * This variable contains a ProductUrlPathGenerator
     *
     * @var ProductUrlPathGenerator $productUrlPathGenerator
     */
    protected $productUrlPathGenerator;

    /**
     * Product constructor
     *
     * @param Context                 $context
     * @param ResourceConnection      $connection
     * @param DeploymentConfig        $deploymentConfig
     * @param BaseProductModel        $product
     * @param ProductUrlPathGenerator $productUrlPathGenerator
     * @param ConfigHelper            $configHelper
     * @param JsonSerializer          $serializer
     */
    public function __construct(
        Context $context,
        ResourceConnection $connection,
        DeploymentConfig $deploymentConfig,
        BaseProductModel $product,
        ProductUrlPathGenerator $productUrlPathGenerator,
        ConfigHelper $configHelper,
        JsonSerializer $serializer
    ) {
        $this->serializer              = $serializer;
        $this->productUrlPathGenerator = $productUrlPathGenerator;
        parent::__construct($context, $connection, $deploymentConfig, $product, $configHelper);
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

            if ($key === self::ASSOCIATIONS_KEY) {
                /** @var array $values */
                $values = $this->formatAssociations($value);
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
             * @var string|int $local
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
    private function formatValues(array $values)
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
                if (isset($attributeValue['data'][0]) && (!is_array($attributeValue['data'][0]) || !array_key_exists('amount', $attributeValue['data'][0]))) {
                    $columns[$key] = join(',', $attributeValue['data']);

                    continue;
                }
                // Attribute is a price
                /** @var array $price */
                foreach ($attributeValue['data'] as $price) {
                    if (!is_array($price) || !array_key_exists('currency', $price) || !array_key_exists('amount', $price)) {
                        continue;
                    }
                    /** @var string $priceKey */
                    $priceKey           = $key . '-' . $price['currency'];
                    $columns[$priceKey] = $price['amount'];
                }
            }
        }

        return $columns;
    }

    /**
     * Format associations field
     *
     * @param array $values
     *
     * @return array
     */
    private function formatAssociations(array $values)
    {
        /** @var array $associations */
        $associations = [];

        /**
         * @var string $group
         * @var array $types
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

                $associations[$name] = implode(',', $products);
            }
        }

        if (empty($associations)) {
            return [];
        }

        return $associations;
    }

    /**
     * Get attribute key to be inserted as a column
     *
     * @param string $attribute
     * @param array  $attributeValue
     *
     * @return string
     */
    private function getKey($attribute, array $attributeValue)
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
        /** @var string|array $matches */
        $matches = $this->scopeConfig->getValue(ConfigHelper::PRODUCT_ATTRIBUTE_MAPPING);
        $matches = $this->serializer->unserialize($matches);
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
     * @return \Akeneo\Connector\Helper\Import\Product
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

        /* Connect existing Magento products to new Akeneo items */
        // Get existing entities from Akeneo table
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
        $query  = $connection->query($select);
        /** @var mixed $row */
        while ($row = $query->fetch()) {
            // Create a row in Akeneo table for products present in Magento and Akeneo that were never imported before
            if (!in_array($row['entity_id'], $existingEntities)) {
                $values = [
                    'import' => 'product',
                    'code' => $row['sku'],
                    'entity_id' => $row['entity_id'],
                ];
                $connection->insertOnDuplicate($akeneoConnectorTable, $values);
            }
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
     * Verify product url during url rewrite returns the correct request path
     *
     * @param string $requestPath
     * @param Magento\Catalog\Model\Product $product
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
                    $product->setUrlKey(substr($product->getUrlKey(), 0, -(strlen($suffix - 1) + 1)) . '-' . $suffix);
                }
                /** @var string $requestPath */
                $requestPath = $this->productUrlPathGenerator->getUrlPathWithSuffix(
                    $product,
                    $product->getStoreId()
                );
            }
            $suffix += 1;
        } while ($exists);

        return $requestPath;
    }
}
