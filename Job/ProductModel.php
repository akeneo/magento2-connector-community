<?php

namespace Akeneo\Connector\Job;

use Akeneo\Connector\Helper\ProductFilters;
use Akeneo\Connector\Model\Source\Attribute\Metrics as AttributeMetrics;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Eav\Model\Config;
use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\Import\Entities as EntitiesHelper;
use Akeneo\Connector\Helper\Output as OutputHelper;
use Akeneo\Connector\Job\Import;

/**
 * Class ProductModel
 *
 * @category  Class
 * @package   Akeneo\Connector\Job
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class ProductModel extends Import
{
    /**
     * This variable contains a string value
     *
     * @var string $code
     */
    protected $code = 'product_model';
    /**
     * This variable contains a string value
     *
     * @var string $name
     */
    protected $name = 'Product Model';
    /**
     * This variable contains product filters
     *
     * @var mixed[] $filters
     */
    protected $filters;
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
     * Description $attributeMetrics field
     *
     * @var AttributeMetrics $attributeMetrics
     */
    protected $attributeMetrics;
    /**
     * This variable contains a ProductFilters
     *
     * @var ProductFilters $productFilters
     */
    protected $productFilters;

    /**
     * ProductModel constructor
     *
     * @param OutputHelper                            $outputHelper
     * @param ManagerInterface                        $eventManager
     * @param Authenticator                           $authenticator
     * @param \Akeneo\Connector\Helper\Import\Product $entitiesHelper
     * @param ConfigHelper                            $configHelper
     * @param Config                                  $eavConfig
     * @param ProductFilters                          $productFilters
     * @param AttributeMetrics                        $attributeMetrics
     * @param array                                   $data
     */
    public function __construct(
        OutputHelper $outputHelper,
        ManagerInterface $eventManager,
        Authenticator $authenticator,
        \Akeneo\Connector\Helper\Import\Product $entitiesHelper,
        ConfigHelper $configHelper,
        Config $eavConfig,
        ProductFilters $productFilters,
        AttributeMetrics $attributeMetrics,
        array $data = []
    ) {
        parent::__construct($outputHelper, $eventManager, $authenticator, $data);

        $this->entitiesHelper   = $entitiesHelper;
        $this->configHelper     = $configHelper;
        $this->eavConfig        = $eavConfig;
        $this->productFilters   = $productFilters;
        $this->attributeMetrics = $attributeMetrics;
    }

    /**
     * Create temporary table
     *
     * @return void
     */
    public function createTable()
    {
        /** @var mixed[] $filters */
        $filters = $this->getFilters();
        
        foreach ($filters as $filter) {
            /** @var PageInterface $productModels */
            $productModels = $this->akeneoClient->getProductModelApi()->listPerPage(1, false, $filter);
            /** @var array $productModel */
            $productModels = $productModels->getItems();

            if (!empty($productModels)) {
                break;
            }
        }

        if (empty($productModels)) {
            $this->setMessage(__('No results from Akeneo'));
            $this->stop(1);

            return;
        }

        $productModel = reset($productModels);
        $this->entitiesHelper->createTmpTableFromApi($productModel, $this->getCode());
    }

    /**
     * Insert data into temporary table
     *
     * @return void
     */
    public function insertData()
    {
        /** @var mixed[] $filters */
        $filters = $this->getFilters();
        /** @var string|int $paginationSize */
        $paginationSize = $this->configHelper->getPaginationSize();
        /** @var int $index */
        $index = 0;
        /** @var string[] $attributeMetrics */
        $attributeMetrics = $this->attributeMetrics->getMetricsAttributes();
        /** @var mixed[] $metricsConcatSettings */
        $metricsConcatSettings = $this->configHelper->getMetricsColumns(null, true);
        /** @var string[] $metricSymbols */
        $metricSymbols = $this->getMetricsSymbols();

        /** @var mixed[] $filter */
        foreach ($filters as $filter) {
            /** @var ResourceCursorInterface $productModels */
            $productModels = $this->akeneoClient->getProductModelApi()->all($paginationSize, $filter);

            /**
             * @var int   $index
             * @var array $productModel
             */
            foreach ($productModels as $productModel) {
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
                $this->entitiesHelper->insertDataFromApi($productModel, $this->getCode());
                $index++;
            }
        }

        if (empty($index)) {
            $this->setMessage('No Product data to insert in temp table');
            $this->stop(true);

            return;
        }

        $this->setMessage(
            __('%1 line(s) found', $index)
        );
    }

    /**
     * Generate array of metrics with unit in key and symbol for value
     *
     * @return string[]
     */
    public function getMetricsSymbols()
    {
        /** @var mixed[] $measures */
        $measures = $this->akeneoClient->getMeasureFamilyApi()->all();
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
     * Remove columns from product model table
     *
     * @return void
     */
    public function removeColumns()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var array $except */
        $except = ['code', 'axis'];
        /** @var array $variantTable */
        $variantTable = $this->entitiesHelper->getTable('akeneo_connector_product_model');
        /** @var array $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var array $columnsTmp */
        $columnsTmp = array_keys($connection->describeTable($tmpTable));
        /** @var array $columns */
        $columns = array_keys($connection->describeTable($variantTable));
        /** @var array $columnsToDelete */
        $columnsToDelete = array_diff($columns, $columnsTmp);

        /** @var string $column */
        foreach ($columnsToDelete as $column) {
            if (in_array($column, $except)) {
                continue;
            }
            $connection->dropColumn($variantTable, $column);
        }
    }

    /**
     * Add columns to product model table
     *
     * @return void
     */
    public function addColumns()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var array $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var array $except */
        $except = ['code', 'axis', 'type', '_entity_id', '_is_new'];
        /** @var array $variantTable */
        $variantTable = $this->entitiesHelper->getTable('akeneo_connector_product_model');
        /** @var array $columnsTmp */
        $columnsTmp = array_keys($connection->describeTable($tmpTable));
        /** @var array $columns */
        $columns = array_keys($connection->describeTable($variantTable));
        /** @var array $columnsToAdd */
        $columnsToAdd = array_diff($columnsTmp, $columns);

        /** @var string $column */
        foreach ($columnsToAdd as $column) {
            if (in_array($column, $except)) {
                continue;
            }
            $connection->addColumn($variantTable, $this->_columnName($column), 'text');
        }
        if (!$connection->tableColumnExists($tmpTable, 'axis')) {
            $connection->addColumn($tmpTable, 'axis', [
                'type' => 'text',
                'length' => 255,
                'default' => '',
                'COMMENT' => ' '
            ]);
        }
    }

    /**
     * Add or update data in product model table
     *
     * @return void
     */
    public function updateData()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var int $updateLength */
        $batchSize = $this->configHelper->getAdvancedPmBatchSize();
        /** @var array $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var array $variantTable */
        $variantTable = $this->entitiesHelper->getTable('akeneo_connector_product_model');
        /** @var array $variant */
        $variant = $connection->query(
            $connection->select()->from($tmpTable)
        );
        /** @var array $attributes */
        $attributes = $connection->fetchPairs(
            $connection->select()->from(
                $this->entitiesHelper->getTable('eav_attribute'),
                ['attribute_code', 'attribute_id']
            )->where('entity_type_id = ?', $this->getEntityTypeId())
        );
        /** @var array $columns */
        $columns = array_keys($connection->describeTable($tmpTable));
        /** @var array $values */
        $values = [];
        /** @var int $i */
        $i = 0;
        /** @var array $keys */
        $keys = [];
        while (($row = $variant->fetch())) {
            $values[$i] = [];
            /** @var int $column */
            foreach ($columns as $column) {
                if ($connection->tableColumnExists($variantTable, $this->_columnName($column))) {
                    if ($column != 'axis') {
                        $values[$i][$this->_columnName($column)] = $row[$column];
                    }
                    if ($column == 'axis' && !$connection->tableColumnExists($tmpTable, 'family_variant')) {
                        /** @var array $axisAttributes */
                        $axisAttributes = explode(',', $row['axis']);
                        /** @var array $axis */
                        $axis = [];
                        /** @var string $code */
                        foreach ($axisAttributes as $code) {
                            if (isset($attributes[$code])) {
                                $axis[] = $attributes[$code];
                            }
                        }
                        $values[$i][$column] = join(',', $axis);
                    }
                    $keys = array_keys($values[$i]);
                }
            }
            $i++;
            if (count($values) > $batchSize) {
                if (0 == $batchSize) {
                    $this->sliceInsertOnDuplicate($variantTable, $values);
                } else {
                    $connection->insertOnDuplicate($variantTable, $values, $keys);
                }
                $values = [];
                $i      = 0;
            }
        }
        if (count($values) > 0) {
            if (0 == $batchSize) {
                $this->sliceInsertOnDuplicate($variantTable, $values);
            } else {
                $connection->insertOnDuplicate($variantTable, $values, $keys);
            }
        }
    }

    /**
     * Drop temporary table
     *
     * @return void
     */
    public function dropTable()
    {
        $this->entitiesHelper->dropTable($this->getCode());
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
     * Retrieve product filters
     *
     * @return mixed[]
     */
    protected function getFilters()
    {
        /** @var mixed[] $filters */
        $filters = $this->productFilters->getModelFilters();
        if (array_key_exists('error', $filters)) {
            $this->setMessage($filters['error']);
            $this->stop(true);
        }

        $this->filters = $filters;

        return $this->filters;
    }
}
