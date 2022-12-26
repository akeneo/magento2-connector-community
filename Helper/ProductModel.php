<?php

declare(strict_types=1);

namespace Akeneo\Connector\Helper;

use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\Import\Entities;
use Akeneo\Connector\Helper\Import\Entities as EntitiesHelper;
use Akeneo\Connector\Helper\Import\Product;
use Akeneo\Connector\Helper\Store as StoreHelper;
use Akeneo\Connector\Model\Source\Attribute\Metrics as AttributeMetrics;
use Akeneo\Connector\Model\Source\Attribute\Tables as AttributeTables;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
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
     * This variable contains a Json
     *
     * @var Json $jsonSerializer
     */
    protected $jsonSerializer;
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
     * This variable contains an $attributeTables
     *
     * @var AttributeTables $attributeTables
     */
    protected $attributeTables;

    /**
     * ProductModel constructor
     *
     * @param Product $entitiesHelper
     * @param ConfigHelper $configHelper
     * @param Config $eavConfig
     * @param ProductFilters $productFilters
     * @param Store $storeHelper
     * @param Json $jsonSerializer
     * @param EntitiesHelper $entities
     * @param AttributeMetrics $attributeMetrics
     * @param AttributeTables $attributeTables
     */
    public function __construct(
        Product $entitiesHelper,
        ConfigHelper $configHelper,
        Config $eavConfig,
        ProductFilters $productFilters,
        StoreHelper $storeHelper,
        Json $jsonSerializer,
        Entities $entities,
        AttributeMetrics $attributeMetrics,
        AttributeTables $attributeTables
    ) {
        $this->entitiesHelper   = $entitiesHelper;
        $this->configHelper     = $configHelper;
        $this->eavConfig        = $eavConfig;
        $this->entities         = $entities;
        $this->productFilters   = $productFilters;
        $this->storeHelper      = $storeHelper;
        $this->jsonSerializer   = $jsonSerializer;
        $this->attributeMetrics = $attributeMetrics;
        $this->attributeTables         = $attributeTables;
    }

    /**
     * Description createTable function
     *
     * @param AkeneoPimClientInterface $akeneoClient
     * @param string[] $filters
     * @param string|null $family
     *
     * @return string[]
     */
    public function createTable(AkeneoPimClientInterface $akeneoClient, array $filters, ?string $family = null)
    {
        /** @var string[] $messages */
        $messages = [];

        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        if ($connection->isTableExists($this->entitiesHelper->getTableName('product_model'))) {
            return $messages;
        }

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
        $this->entitiesHelper->createTmpTableFromApi($productModel, 'product_model', $family);

        return $messages;
    }

    /**
     * Insert data into temporary table
     *
     * @param AkeneoPimClientInterface $akeneoClient
     * @param string[] $filters
     * * @param string|null $family
     *
     * @return void
     */
    public function insertData(AkeneoPimClientInterface $akeneoClient, array $filters, ?string $family = null)
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
        /** @var string[] $attributeTables */
        $attributeTables = $this->attributeTables->getTablesAttributes();
        /** @var string[] $localesAvailable */
        $localesAvailable = $this->storeHelper->getMappedWebsitesStoreLangs();
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

                /**
                 * @var string[] $attributeTable
                 */
                foreach ($attributeTables as $attributeTable) {
                    if (!isset($productModel['values'][$attributeTable['code']])) {
                        continue;
                    }
                    /** @var string[][][] $tableConfiguration */
                    $tableConfiguration = $attributeTable['table_configuration'];
                    /** @var bool $isTableLocalisable */
                    $isTableLocalisable = $attributeTable['localizable'];
                    /** @var bool $isTableScopable */
                    $isTableScopable = $attributeTable['scopable'];

                    if (!$isTableLocalisable) {
                        /** @var string[] $toInsert */
                        $toInsert = [];
                        if (!$isTableScopable) {
                            /** @var string[] $globalData */
                            $globalData = $productModel['values'][$attributeTable['code']][0];
                            /** @var int $i */
                            $i = 0;
                            /** @var string[] $localeAvailable */
                            foreach ($localesAvailable as $localeAvailable) {
                                $toInsert[$i] = $globalData;
                                $toInsert[$i]['locale'] = $localeAvailable;
                                $i++;
                            }
                        } else {
                            foreach ($productModel['values'][$attributeTable['code']] as $tableValuePerScope) {
                                /** @var int $i */
                                $i = 0;
                                /** @var string[] $localesPerChannel */
                                $localesPerChannel = $this->storeHelper->getChannelStoreLangs($tableValuePerScope['scope']);
                                /** @var string[] $localePerChannel */
                                foreach ($localesPerChannel as $localePerChannel) {
                                    $toInsert[$i] = $tableValuePerScope;
                                    $toInsert[$i]['locale'] = $localePerChannel;
                                    $i++;
                                }
                            }
                        }
                        $productModel['values'][$attributeTable['code']] = $toInsert;
                    }

                    /** @var string[][][] $table */
                    foreach ($productModel['values'][$attributeTable['code']] as $key => $table) {
                        /** @var string|null $locale */
                        $locale = $table['locale'];
                        /** @var int $i */
                        $i = 0;
                        /** @var string[] $data */
                        foreach ($table['data'] as $data) {
                            /** @var string $label */
                            foreach ($data as $label => $newData) {
                                /** @var string[] $config */
                                foreach ($tableConfiguration as $config) {
                                    if (isset($locale, $config['labels'][$locale])
                                        && $locale !== null && ($config['code'] === $label)
                                    ) {
                                        /** @var string $newLabel */
                                        $newLabel = $config['labels'][$locale];
                                        if (isset($table['data'][$i][$label], $newLabel)) {
                                            $table['data'][$i][$label] = [$newLabel => $table['data'][$i][$label]];
                                            if (isset($config['options'])) {
                                                /** @var string[][] $option */
                                                foreach ($config['options'] as $option) {
                                                    if ($option['code'] === $newData || $option['code'] === $label) {
                                                        $table['data'][$i][$label] = [$newLabel => $option['labels'][$locale]];
                                                    }
                                                }
                                            }
                                            $productModel['values'][$attributeTable['code']][$key]['data'] = $table['data'];
                                        }
                                    }
                                }
                            }
                            $i++;
                        }
                    }
                }

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
                        if (!isset($metric['data']['unit'])) {
                            continue;
                        }
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
                $this->entitiesHelper->insertDataFromApi($productModel, 'product_model', $family);
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
     * @return string[] $akeneoClient
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

    /**
     * Add columns to product table
     *
     * @param string $code
     * @param string|null $family
     * 
     * @return void
     */
    public function addColumns($code, ?string $family = null)
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var array $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($code);
        /** @var array $except */
        $except = ['axis', 'type', '_entity_id', '_is_new'];
        /** @var array $variantTable */
        $variantTable = $this->entitiesHelper->getTableName('product_model');
        /** @var array $columnsTmp */
        $columnsTmp = array_keys($connection->describeTable($tmpTable));
        /** @var array $columns */
        $columns = array_keys($connection->describeTable($variantTable));
        /** @var array $columnsToAdd */
        $columnsToAdd = array_diff($columns, $columnsTmp);

        /** @var string $column */
        foreach ($columnsToAdd as $column) {
            if (in_array($column, $except)) {
                continue;
            }
            $columnName = $this->_columnName($column);
            $connection->addColumn(
                $tmpTable,
                $columnName,
                [
                    'type'    => 'text',
                    $this->entitiesHelper->getAttributeColumnLength($family, $columnName), // Get correct column length
                    'default' => '',
                    'COMMENT' => ' '
                ]
            );
        }
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
    }
}
