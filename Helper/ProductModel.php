<?php

declare(strict_types=1);

namespace Akeneo\Connector\Helper;

use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\Import\Entities;
use Akeneo\Connector\Helper\Import\Entities as EntitiesHelper;
use Akeneo\Connector\Helper\Import\Product;
use Akeneo\Connector\Helper\Store as StoreHelper;
use Akeneo\Connector\Model\Source\Attribute\Metrics as AttributeMetrics;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\Serialize\Serializer\Json as MagentoJsonSerializer;

/**
 * Class ProductModel
 *
 * @package   Akeneo\Connector\Helper
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class ProductModel
{
    /**
     * This variable contains an EntitiesHelper
     *
     * @var EntitiesHelper $entitiesHelper
     */
    protected $entitiesHelper;
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;
    /**
     * This variable contains a Config
     *
     * @var Config $eavConfig
     */
    protected $eavConfig;
    /**
     * This variable contains a ProductFilters
     *
     * @var ProductFilters $productFilters
     */
    protected $productFilters;
    /**
     * This variable contains a MagentoJsonSerializer
     *
     * @var MagentoJsonSerializer $magentoSerializer
     */
    protected $magentoSerializer;
    /**
     * This variable contains entities
     *
     * @var Entities $entities
     */
    protected $entities;
    /**
     * This variable contains a StoreHelper
     *
     * @var StoreHelper $storeHelper
     */
    protected $storeHelper;
    /**
     * Description $attributeMetrics field
     *
     * @var AttributeMetrics $attributeMetrics
     */
    protected $attributeMetrics;

    /**
     * ProductModel constructor
     *
     * @param Product                         $entitiesHelper
     * @param \Akeneo\Connector\Helper\Config $configHelper
     * @param Config                          $eavConfig
     * @param ProductFilters                  $productFilters
     * @param Store                           $storeHelper
     * @param MagentoJsonSerializer           $magentoSerializer
     * @param EntitiesHelper                  $entities
     * @param AttributeMetrics                $attributeMetrics
     */
    public function __construct(
        Product $entitiesHelper,
        ConfigHelper $configHelper,
        Config $eavConfig,
        ProductFilters $productFilters,
        StoreHelper $storeHelper,
        MagentoJsonSerializer $magentoSerializer,
        Entities $entities,
        AttributeMetrics $attributeMetrics
    ) {
        $this->entitiesHelper          = $entitiesHelper;
        $this->configHelper            = $configHelper;
        $this->eavConfig               = $eavConfig;
        $this->entities                = $entities;
        $this->productFilters          = $productFilters;
        $this->storeHelper             = $storeHelper;
        $this->magentoSerializer       = $magentoSerializer;
        $this->attributeMetrics        = $attributeMetrics;
    }

    /**
     * Description createTable function
     *
     * @param AkeneoPimEnterpriseClientInterface $akeneoClient
     * @param string[]                           $filters
     *
     * @return string[]
     */
    public function createTable($akeneoClient, $filters)
    {
        /** @var string[] $messages */
        $messages = [];
        foreach ($filters as $filter) {
            /** @var PageInterface $productModels */
            $productModels = $akeneoClient->getProductModelApi()->listPerPage(1, false, $filter);
            /** @var array $productModel */
            $productModels = $productModels->getItems();

            if (!empty($productModels)) {
                break;
            }
        }

        if (empty($productModels)) {
            $messages[] = ['message' => __('No results from Akeneo'), 'status' => false];

            return $messages;
        }

        $productModel = reset($productModels);
        $this->entitiesHelper->createTmpTableFromApi($productModel, 'product_model');

        return $messages;
    }

    /**
     * Insert data into temporary table
     *
     * @param AkeneoPimEnterpriseClientInterface $akeneoClient
     * @param string[]                           $filters
     *
     * @return void
     */
    public function insertData($akeneoClient, $filters)
    {
        /** @var mixed[] $messages */
        $messages = [];

        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var array $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName('product_model');
        /** @var string|int $paginationSize */
        $paginationSize = $this->configHelper->getPaginationSize();
        /** @var int $index */
        $index = 0;

        /** @var string[] $attributeMetrics */
        $attributeMetrics = $this->attributeMetrics->getMetricsAttributes();
        /** @var mixed[] $metricsConcatSettings */
        $metricsConcatSettings = $this->configHelper->getMetricsColumns(null, true);
        /** @var string[] $metricSymbols */
        $metricSymbols = $this->getMetricsSymbols($akeneoClient);
        if (!$connection->tableColumnExists($tmpTable, 'axis')) {
            $connection->addColumn(
                $tmpTable,
                'axis',
                [
                    'type'    => 'text',
                    'length'  => 255,
                    'default' => '',
                    'COMMENT' => ' ',
                ]
            );
        }

        /** @var mixed[] $filter */
        foreach ($filters as $filter) {
            /** @var ResourceCursorInterface $productModels */
            $productModels = $akeneoClient->getProductModelApi()->all($paginationSize, $filter);

            /**
             * @var int   $index
             * @var array $productModel
             */
            foreach ($productModels as $productModel) {
                /** @var string $attributeMetric */
                foreach ($attributeMetrics as $attributeMetric) {
                    if (!isset($productModel['values'][$attributeMetric])) {
                        continue;
                    }

                    foreach ($productModel['values'][$attributeMetric] as $key => $metric) {
                        /** @var string|float $amount */
                        $amount = $metric['data']['amount'];
                        if ($amount != null) {
                            $amount = floatval($amount);
                        }

                        $productModel['values'][$attributeMetric][$key]['data']['amount'] = $amount;
                    }
                }

                /**
                 * @var mixed[] $metricsConcatSetting
                 */
                foreach ($metricsConcatSettings as $metricsConcatSetting) {
                    if (!isset($productModel['values'][$metricsConcatSetting])) {
                        continue;
                    }

                    /**
                     * @var int     $key
                     * @var mixed[] $metric
                     */
                    foreach ($productModel['values'][$metricsConcatSetting] as $key => $metric) {
                        /** @var string $unit */
                        $unit = $metric['data']['unit'];
                        /** @var string|false $symbol */
                        $symbol = array_key_exists($unit, $metricSymbols);

                        if (!$symbol) {
                            continue;
                        }

                        $productModel['values'][$metricsConcatSetting][$key]['data']['amount'] .= ' ' . $metricSymbols[$unit];
                    }
                }
                // Set identifier to work with data insertion
                if (isset($productModel['code'])) {
                    $productModel['identifier'] = $productModel['code'];
                }
                $this->entitiesHelper->insertDataFromApi($productModel, 'product_model');
                $index++;
            }
        }

        if (empty($index)) {
            $messages[] = ['message' => __('No Product data to insert in temp table'), 'status' => false];

            return $messages;
        }
        $messages[] = ['message' => __('%1 line(s) found', $index), 'status' => true];

        return $messages;
    }

    /**
     * Drop temporary table
     *
     * @return void
     */
    public function dropTable()
    {
        $this->entitiesHelper->dropTable('product_model');
    }

    /**
     * Replace column name
     *
     * @param string $column
     *
     * @return string
     */
    protected function _columnName($column)
    {
        /** @var array $matches */
        $matches = [
            'label' => 'name',
        ];
        /**
         * @var string $name
         * @var string $replace
         */
        foreach ($matches as $name => $replace) {
            if (preg_match('/^' . $name . '/', $column)) {
                /** @var string $column */
                $column = preg_replace('/^' . $name . '/', $replace, $column);
            }
        }

        return $column;
    }

    /**
     * Get the product entity type id
     *
     * @return string
     */
    protected function getEntityTypeId()
    {
        /** @var string $productEntityTypeId */
        $productEntityTypeId = $this->eavConfig->getEntityType(ProductAttributeInterface::ENTITY_TYPE_CODE)
            ->getEntityTypeId();

        return $productEntityTypeId;
    }

    /**
     * Generate array of metrics with unit in key and symbol for value
     *
     * @return string[]
     */
    public function getMetricsSymbols($akeneoClient)
    {
        /** @var string|int $paginationSize */
        $paginationSize = $this->configHelper->getPaginationSize();
        /** @var mixed[] $measures */
        $measures = $akeneoClient->getMeasureFamilyApi()->all($paginationSize);
        /** @var string[] $metricsSymbols */
        $metricsSymbols = [];
        /** @var mixed[] $measure */
        foreach ($measures as $measure) {
            /** @var mixed[] $unit */
            foreach ($measure['units'] as $unit) {
                $metricsSymbols[$unit['code']] = $unit['symbol'];
            }
        }

        return $metricsSymbols;
    }
}
