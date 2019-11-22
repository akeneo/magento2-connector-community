<?php

namespace Akeneo\Connector\Helper\Import;

use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\Serializer as JsonSerializer;
use Magento\Catalog\Model\Product as BaseProductModel;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;

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
     * Product constructor
     *
     * @param Context            $context
     * @param ResourceConnection $connection
     * @param DeploymentConfig   $deploymentConfig
     * @param BaseProductModel   $product
     * @param ConfigHelper       $configHelper
     * @param JsonSerializer     $serializer
     */
    public function __construct(
        Context $context,
        ResourceConnection $connection,
        DeploymentConfig $deploymentConfig,
        BaseProductModel $product,
        ConfigHelper $configHelper,
        JsonSerializer $serializer
    ) {
        $this->serializer = $serializer;
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
                    if (!array_key_exists('currency', $price) || !array_key_exists('amount', $price)) {
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
}
