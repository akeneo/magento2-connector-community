<?php

namespace Akeneo\Connector\Job;

use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field\Configurable as TypeField;
use Magento\Catalog\Model\Product\Link;
use Magento\Catalog\Model\ProductLink\Link as ProductLink;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product as BaseProductModel;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\Cache\Type\Block;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\PageCache\Model\Cache\Type;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\Staging\Model\VersionManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product\Attribute\Backend\Media\ImageEntryConverter;
use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\Output as OutputHelper;
use Akeneo\Connector\Helper\Store as StoreHelper;
use Akeneo\Connector\Helper\ProductFilters;
use Akeneo\Connector\Helper\Serializer as JsonSerializer;
use Akeneo\Connector\Helper\Import\Product as ProductImportHelper;
use Akeneo\Connector\Job\Option as JobOption;
use Akeneo\Connector\Model\Source\Attribute\Metrics as AttributeMetrics;
use Zend_Db_Expr as Expr;
use Zend_Db_Statement_Pdo;

/**
 * Class Product
 *
 * @category  Class
 * @package   Akeneo\Connector\Job
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Product extends Import
{
    /**
     * @var string PIM_PRODUCT_STATUS_DISABLED
     */
    const PIM_PRODUCT_STATUS_DISABLED = '0';
    /**
     * @var string MAGENTO_PRODUCT_STATUS_DISABLED
     */
    const MAGENTO_PRODUCT_STATUS_DISABLED = '2';
    /**
     * @var int CONFIGURABLE_INSERTION_MAX_SIZE
     */
    const CONFIGURABLE_INSERTION_MAX_SIZE = 500;
    /**
     * This variable contains a string value
     *
     * @var string $code
     */
    protected $code = 'product';
    /**
     * This variable contains a string value
     *
     * @var string $name
     */
    protected $name = 'Product';
    /**
     * Akeneo default association types, reformatted as column names
     *
     * @var string[] $associationTypes
     */
    protected $associationTypes = [
        Link::LINK_TYPE_RELATED   => [
            'SUBSTITUTION-products',
            'SUBSTITUTION-product_models',
        ],
        Link::LINK_TYPE_UPSELL    => [
            'UPSELL-products',
            'UPSELL-product_models',
        ],
        Link::LINK_TYPE_CROSSSELL => [
            'X_SELL-products',
            'X_SELL-product_models',
        ],
    ];
    /**
     * list of allowed type_id that can be imported
     *
     * @var string[]
     */
    protected $allowedTypeId = ['simple', 'virtual'];
    /**
     * List of column to exclude from attribute value setting
     *
     * @var string[]
     */
    protected $excludedColumns = [
        '_entity_id',
        '_is_new',
        '_status',
        '_type_id',
        '_options_container',
        '_tax_class_id',
        '_attribute_set_id',
        '_visibility',
        '_children',
        '_axis',
        'identifier',
        'sku',
        'categories',
        'family',
        'groups',
        'parent',
        'enabled',
        'created',
        'updated',
        'associations',
        'PACK',
        'SUBSTITUTION',
        'UPSELL',
        'X_SELL',
    ];
    /**
     * This variable contains a ProductImportHelper
     *
     * @var ProductImportHelper $entitiesHelper
     */
    protected $entitiesHelper;
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;
    /**
     * This variable contains an EavConfig
     *
     * @var  EavConfig $eavConfig
     */
    protected $eavConfig;
    /**
     * This variable contains a ProductFilters
     *
     * @var ProductFilters $productFilters
     */
    protected $productFilters;
    /**
     * This variable contains product filters
     *
     * @var mixed[] $filters
     */
    protected $filters;
    /**
     * This variable contains a ScopeConfigInterface
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;
    /**
     * This variable contains a JsonSerializer
     *
     * @var JsonSerializer $serializer
     */
    protected $serializer;
    /**
     * This variable contains a ProductModel
     *
     * @var BaseProductModel $product
     */
    protected $product;
    /**
     * This variable contains a ProductUrlPathGenerator
     *
     * @var ProductUrlPathGenerator $productUrlPathGenerator
     */
    protected $productUrlPathGenerator;
    /**
     * This variable contains a TypeListInterface
     *
     * @var TypeListInterface $cacheTypeList
     */
    protected $cacheTypeList;
    /**
     * This variable contains a StoreHelper
     *
     * @var StoreHelper $storeHelper
     */
    protected $storeHelper;
    /**
     * This variable contains a JobOption
     *
     * @var JobOption $jobOption
     */
    protected $jobOption;
    /**
     * This variable contains an AttributeMetrics
     *
     * @var AttributeMetrics $attributeMetrics
     */
    protected $attributeMetrics;
    /**
     * This variable contains an StoreManagerInterface
     *
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * Product constructor.
     *
     * @param OutputHelper            $outputHelper
     * @param ManagerInterface        $eventManager
     * @param Authenticator           $authenticator
     * @param ProductImportHelper     $entitiesHelper
     * @param ConfigHelper            $configHelper
     * @param EavConfig               $eavConfig
     * @param ProductFilters          $productFilters
     * @param ScopeConfigInterface    $scopeConfig
     * @param JsonSerializer          $serializer
     * @param BaseProductModel        $product
     * @param ProductUrlPathGenerator $productUrlPathGenerator
     * @param TypeListInterface       $cacheTypeList
     * @param StoreHelper             $storeHelper
     * @param AttributeMetrics        $attributeMetrics
     * @param StoreManagerInterface   $storeManager
     * @param array                   $data
     */
    public function __construct(
        OutputHelper $outputHelper,
        ManagerInterface $eventManager,
        Authenticator $authenticator,
        ProductImportHelper $entitiesHelper,
        ConfigHelper $configHelper,
        EavConfig $eavConfig,
        ProductFilters $productFilters,
        ScopeConfigInterface $scopeConfig,
        JsonSerializer $serializer,
        BaseProductModel $product,
        ProductUrlPathGenerator $productUrlPathGenerator,
        TypeListInterface $cacheTypeList,
        StoreHelper $storeHelper,
        JobOption $jobOption,
        AttributeMetrics $attributeMetrics,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($outputHelper, $eventManager, $authenticator, $data);

        $this->entitiesHelper          = $entitiesHelper;
        $this->configHelper            = $configHelper;
        $this->eavConfig               = $eavConfig;
        $this->productFilters          = $productFilters;
        $this->scopeConfig             = $scopeConfig;
        $this->serializer              = $serializer;
        $this->product                 = $product;
        $this->cacheTypeList           = $cacheTypeList;
        $this->storeHelper             = $storeHelper;
        $this->jobOption               = $jobOption;
        $this->productUrlPathGenerator = $productUrlPathGenerator;
        $this->attributeMetrics        = $attributeMetrics;
        $this->storeManager            = $storeManager;
    }

    /**
     * Create temporary table
     *
     * @return void
     */
    public function createTable()
    {
        if (empty($this->configHelper->getMappedChannels())) {
            $this->setMessage(__('No website/channel mapped. Please check your configurations.'));
            $this->stop(true);

            return;
        }

        /** @var mixed[] $filters */
        $filters = $this->getFilters();
        $filters = reset($filters);
        /** @var PageInterface $products */
        $products = $this->akeneoClient->getProductApi()->listPerPage(1, false, $filters);
        /** @var mixed[] $products */
        $products = $products->getItems();
        $product  = reset($products);
        if (empty($products)) {
            $this->setMessage(__('No results from Akeneo'));
            $this->stop(true);

            return;
        }

        $this->entitiesHelper->createTmpTableFromApi($product, $this->getCode());
    }

    /**
     * Insert data into temporary table
     *
     * @return void
     */
    public function insertData()
    {
        /** @var string|int $paginationSize */
        $paginationSize = $this->configHelper->getPanigationSize();
        /** @var int $index */
        $index = 0;
        /** @var mixed[] $filters */
        $filters = $this->getFilters();
        /** @var mixed[] $metricsConcatSettings */
        $metricsConcatSettings = $this->configHelper->getMetricsColumns(null, true);
        /** @var string[] $metricSymbols */
        $metricSymbols = $this->getMetricsSymbols();
        /** @var string[] $attributeMetrics */
        $attributeMetrics = $this->attributeMetrics->getMetricsAttributes();
        /** @var mixed[] $filter */
        foreach ($filters as $filter) {
            /** @var Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface $products */
            $products = $this->akeneoClient->getProductApi()->all($paginationSize, $filter);
            /**
             * @var int     $index
             * @var mixed[] $product
             */
            foreach ($products as $product) {
                /**
                 * @var string $attributeMetric
                 */
                foreach ($attributeMetrics as $attributeMetric) {
                    if (!isset($product['values'][$attributeMetric])) {
                        continue;
                    }

                    foreach ($product['values'][$attributeMetric] as $key => $metric) {
                        /** @var string|float $amount */
                        $amount = $metric['data']['amount'];
                        $amount = floatval($amount);

                        $product['values'][$attributeMetric][$key]['data']['amount'] = $amount;
                    }
                }

                 /**
                 * @var mixed[] $metricsConcatSetting
                 */
                foreach ($metricsConcatSettings as $metricsConcatSetting) {
                    if (!isset($product['values'][$metricsConcatSetting])) {
                        continue;
                    }

                    /**
                     * @var int     $key
                     * @var mixed[] $metric
                     */
                    foreach ($product['values'][$metricsConcatSetting] as $key => $metric) {
                        /** @var string $unit */
                        $unit = $metric['data']['unit'];
                        /** @var string|false $symbol */
                        $symbol = array_key_exists($unit, $metricSymbols);

                        if (!$symbol) {
                            continue;
                        }

                        $product['values'][$metricsConcatSetting][$key]['data']['amount'] .= ' ' . $metricSymbols[$unit];
                    }
                }
                /** @var bool $result */
                $result = $this->entitiesHelper->insertDataFromApi($product, $this->getCode());
                if (!$result) {
                    $this->setMessage('Could not insert Product data in temp table');
                    $this->stop(true);

                    return;
                }

                $index++;
            }
        }

        if (empty($index)) {
            $this->setMessage('No Product data to insert in temp table');
            $this->stop(true);

            return;
        }

        $this->setMessage(__('%1 line(s) found', $index));
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
     * Create configurable products
     *
     * @return void
     * @throws LocalizedException
     */
    public function addRequiredData()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());

        $connection->addColumn(
            $tmpTable,
            '_type_id',
            [
                'type'     => 'text',
                'length'   => 255,
                'default'  => 'simple',
                'COMMENT'  => ' ',
                'nullable' => false,
            ]
        );
        $connection->addColumn(
            $tmpTable,
            '_options_container',
            [
                'type'     => 'text',
                'length'   => 255,
                'default'  => 'container2',
                'COMMENT'  => ' ',
                'nullable' => false,
            ]
        );
        $connection->addColumn(
            $tmpTable,
            '_tax_class_id',
            [
                'type'     => 'integer',
                'length'   => 11,
                'default'  => 0,
                'COMMENT'  => ' ',
                'nullable' => false,
            ]
        ); // None
        $connection->addColumn(
            $tmpTable,
            '_attribute_set_id',
            [
                'type'     => 'integer',
                'length'   => 11,
                'default'  => 4,
                'COMMENT'  => ' ',
                'nullable' => false,
            ]
        ); // Default
        $connection->addColumn(
            $tmpTable,
            '_visibility',
            [
                'type'     => 'integer',
                'length'   => 11,
                'default'  => Visibility::VISIBILITY_BOTH,
                'COMMENT'  => ' ',
                'nullable' => false,
            ]
        );
        $connection->addColumn(
            $tmpTable,
            '_status',
            [
                'type'     => 'integer',
                'length'   => 11,
                'default'  => 2,
                'COMMENT'  => ' ',
                'nullable' => false,
            ]
        ); // Disabled

        if (!$connection->tableColumnExists($tmpTable, 'url_key') && $this->configHelper->isUrlGenerationEnabled()) {
            $connection->addColumn(
                $tmpTable,
                'url_key',
                [
                    'type'     => 'text',
                    'length'   => 255,
                    'default'  => '',
                    'COMMENT'  => ' ',
                    'nullable' => false,
                ]
            );
            $connection->update($tmpTable, ['url_key' => new Expr('LOWER(`identifier`)')]);
        }

        if ($connection->tableColumnExists($tmpTable, 'enabled')) {
            $connection->update($tmpTable, ['_status' => new Expr('IF(`enabled` <> 1, 2, 1)')]);
        }

        /** @var string|null $groupColumn */
        $groupColumn = null;
        if ($connection->tableColumnExists($tmpTable, 'parent')) {
            $groupColumn = 'parent';
        }
        if ($connection->tableColumnExists($tmpTable, 'groups') && !$groupColumn) {
            $groupColumn = 'groups';
        }

        if ($groupColumn) {
            $connection->update(
                $tmpTable,
                [
                    '_visibility' => new Expr(
                        'IF(`' . $groupColumn . '` <> "", ' . Visibility::VISIBILITY_NOT_VISIBLE . ', ' . Visibility::VISIBILITY_BOTH . ')'
                    ),
                ]
            );
        }

        if ($connection->tableColumnExists($tmpTable, 'type_id')) {
            /** @var string $types */
            $types = $connection->quote($this->allowedTypeId);
            $connection->update(
                $tmpTable,
                [
                    '_type_id' => new Expr("IF(`type_id` IN ($types), `type_id`, 'simple')"),
                ]
            );
        }

        /** @var string|array $matches */
        $matches = $this->configHelper->getAttributeMapping();
        if (!is_array($matches)) {
            return;
        }

        /** @var array $stores */
        $stores = $this->storeHelper->getAllStores();

        /** @var array $match */
        foreach ($matches as $match) {
            if (!isset($match['akeneo_attribute'], $match['magento_attribute'])) {
                continue;
            }

            /** @var string $pimAttribute */
            $pimAttribute = $match['akeneo_attribute'];
            /** @var string $magentoAttribute */
            $magentoAttribute = $match['magento_attribute'];

            $this->entitiesHelper->copyColumn($tmpTable, $pimAttribute, $magentoAttribute);

            /**
             * @var string $local
             * @var array $affected
             */
            foreach ($stores as $local => $affected) {
                $this->entitiesHelper->copyColumn(
                    $tmpTable,
                    $pimAttribute . '-' . $local,
                    $magentoAttribute . '-' . $local
                );
            }
        }
    }

    /**
     * Create Temporary metric table and insert option
     *
     * @return void
     */
    public function createMetricsOptions()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var mixed[] $metricsVariantSettings */
        $metricsVariantSettings = $this->configHelper->getMetricsColumns(true);
        /** @var string[] $locales */
        $locales = $this->storeHelper->getMappedWebsitesStoreLangs();

        $this->jobOption->createTable();

        foreach ($metricsVariantSettings as $metricsVariantSetting) {
            $metricsVariantSetting = strtolower($metricsVariantSetting);
            $columnExist = $connection->tableColumnExists($tmpTable, $metricsVariantSetting);

            if (!$columnExist) {
                continue;
            }

            /** @var Select $select */
            $select = $connection->select()->from($tmpTable, [$metricsVariantSetting])->group([$metricsVariantSetting]);
            /** @var mixed[] $options */
            $options = $connection->fetchCol($select);

            foreach ($options as $option) {
                if (!$option) {
                    continue;
                }

                /** @var string[] $labels */
                $labels = array_fill_keys($locales, $option);

                /** @var mixed[] $insertedData */
                $insertedData = [
                    'code'      => $option,
                    'attribute' => $metricsVariantSetting,
                    'labels'    => $labels,
                ];

                $this->entitiesHelper->insertDataFromApi($insertedData, $this->jobOption->getCode());
            }
        }

        $this->jobOption->matchEntities();
        $this->jobOption->insertOptions();
        $this->jobOption->insertValues();
        $this->jobOption->dropTable();
    }

    /**
     * Description createConfigurable function
     *
     * @return void
     * @throws LocalizedException
     */
    public function createConfigurable()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());

        /** @var string|null $groupColumn */
        $groupColumn = null;
        if ($connection->tableColumnExists($tmpTable, 'parent')) {
            $groupColumn = 'parent';
        }
        if (!$groupColumn && $connection->tableColumnExists($tmpTable, 'groups')) {
            $groupColumn = 'groups';
        }
        if (!$groupColumn) {
            $this->setStatus(false);
            $this->setMessage(__('Columns groups or parent not found'));

            return;
        }

        $connection->addColumn($tmpTable, '_children', 'text');
        $connection->addColumn(
            $tmpTable,
            '_axis',
            [
                'type'    => 'text',
                'length'  => 255,
                'default' => '',
                'COMMENT' => ' ',
            ]
        );

        /** @var string $productModelTable */
        $productModelTable = $this->entitiesHelper->getTable('akeneo_connector_product_model');

        if ($connection->tableColumnExists($productModelTable, 'parent')) {
            $select = $connection->select()->from(false, [$groupColumn => 'v.parent'])->joinInner(
                ['v' => $productModelTable],
                'v.parent IS NOT NULL AND e.' . $groupColumn . ' = v.code',
                []
            );

            $connection->query(
                $connection->updateFromSelect($select, ['e' => $tmpTable])
            );
        }

        /** @var array $data */
        $data = [
            'identifier'         => 'e.' . $groupColumn,
            '_children'          => new Expr('GROUP_CONCAT(e.identifier SEPARATOR ",")'),
            '_type_id'           => new Expr('"configurable"'),
            '_options_container' => new Expr('"container1"'),
            '_status'            => 'e._status',
            '_axis'              => 'v.axis',
        ];
        if ($this->configHelper->isUrlGenerationEnabled()) {
            $data['url_key'] = 'e.' . $groupColumn;
        }

        if ($connection->tableColumnExists($tmpTable, 'family')) {
            $data['family'] = 'e.family';
        }

        if ($connection->tableColumnExists($tmpTable, 'categories')) {
            $data['categories'] = 'e.categories';
        }

        /** @var string[] $associationNames */
        foreach ($this->associationTypes as $associationNames) {
            if (empty($associationNames)) {
                continue;
            }
            /** @var string $associationName */
            foreach ($associationNames as $associationName) {
                if (!empty($associationName) && $connection->tableColumnExists(
                        $productModelTable,
                        $associationName
                    ) && $connection->tableColumnExists($tmpTable, $associationName)) {
                    $data[$associationName] = sprintf('v.%s', $associationName);
                }
            }
        }

        /** @var string|array $additional */
        $additional = $this->scopeConfig->getValue(ConfigHelper::PRODUCT_CONFIGURABLE_ATTRIBUTES);
        $additional = $this->serializer->unserialize($additional);
        if (!is_array($additional)) {
            $additional = [];
        }

        /** @var array $stores */
        $stores = $this->storeHelper->getAllStores();

        /** @var array $attribute */
        foreach ($additional as $attribute) {
            if (!isset($attribute['attribute'], $attribute['value'], $attribute['type'])) {
                continue;
            }

            /** @var string $name */
            $name = $attribute['attribute'];
            /** @var string $value */
            $value = $attribute['value'];
            /** @var string $type */
            $type = $attribute['type'];
            /** @var array $columns */
            $columns = [trim($name)];

            /**
             * @var string $local
             * @var string $affected
             */
            foreach ($stores as $local => $affected) {
                $columns[] = trim($name) . '-' . $local;
            }

            /** @var array $column */
            foreach ($columns as $column) {
                if ($column === 'enabled' && $connection->tableColumnExists($tmpTable, 'enabled')) {
                    $column = '_status';
                    if ($value === self::PIM_PRODUCT_STATUS_DISABLED) {
                        $value = self::MAGENTO_PRODUCT_STATUS_DISABLED;
                    }
                }

                if ($type !== TypeField::TYPE_MAPPING && !$connection->tableColumnExists($tmpTable, $column)) {
                    continue;
                }

                if ($type === TypeField::TYPE_VALUE) {
                    $data[$column] = new Expr('"' . $value . '"');
                }

                if ($type === TypeField::TYPE_QUERY) {
                    $data[$column] = new Expr($value);
                }

                if ($type === TypeField::TYPE_DEFAULT) {
                    if (!$connection->tableColumnExists($productModelTable, $column)) {
                        $this->setMessage(__('Warning: column %1 not found in product model', $column));
                        continue;
                    }
                    $data[$column] = 'v.' . $column;
                }

                if ($type === TypeField::TYPE_SIMPLE) {
                    $data[$column] = 'e.' . $column;
                }

                if ($type === TypeField::TYPE_MAPPING) {
                    if (!$connection->tableColumnExists($productModelTable, $column)) {
                        continue;
                    }
                    /** @var string $mapping */
                    $mapping = $value;
                    if (($pos = strpos($column, '-')) !== false) {
                        $mapping = $value . '-' . substr($column, $pos + 1);
                    }
                    if (!$connection->tableColumnExists($tmpTable, $mapping)) {
                        $connection->addColumn(
                            $tmpTable,
                            $mapping,
                            [
                                'type'     => 'text',
                                'length'   => 255,
                                'default'  => null,
                                'COMMENT'  => ' ',
                                'nullable' => true,
                            ]
                        );
                    }
                    $data[$mapping] = 'v.' . $column;
                }
            }
        }

        /** @var Select $configurable */
        $configurable = $connection->select()
            ->from(['e' => $tmpTable], $data)
            ->joinInner(['v' => $productModelTable], 'e.' . $groupColumn . ' = v.code', [])
            ->where('e.' . $groupColumn . ' <> ""')
            ->group('e.' . $groupColumn);

        /** @var string $query */
        $query = $connection->insertFromSelect($configurable, $tmpTable, array_keys($data));

        $connection->query($query);
    }

    /**
     * Check already imported entities are still in Magento
     *
     * @return void
     */
    public function checkEntities()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $akeneoConnectorTable */
        $akeneoConnectorTable = $this->entitiesHelper->getTable('akeneo_connector_entities');
        /** @var string $entityTable */
        $entityTable = $this->entitiesHelper->getTable('catalog_product_entity');
        /** @var \Magento\Framework\DB\Select $selectExistingEntities */
        $selectExistingEntities = $connection->select()->from($entityTable, 'entity_id');
        /** @var string[] $existingEntities */
        $existingEntities = array_column($connection->query($selectExistingEntities)->fetchAll(), 'entity_id');

        $connection->delete(
            $akeneoConnectorTable,
            ['import = ?' => 'product', 'entity_id NOT IN (?)' => $existingEntities]
        );
    }

    /**
     * Match code with entity
     *
     * @return void
     */
    public function matchEntities()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());

        /** @var array $duplicates */
        $duplicates = $connection->fetchCol(
            $connection->select()->from($tmpTable, ['identifier'])->group('identifier')->having('COUNT(identifier) > ?', 1)
        );

        if (!empty($duplicates)) {
            $this->setMessage(
                __('Duplicates sku detected. Make sure Product Model code is not used for a simple product sku. Duplicates: %1', join(', ', $duplicates))
            );
            $this->stop(true);

            return;
        }

        $this->entitiesHelper->matchEntity(
            'identifier',
            'catalog_product_entity',
            'entity_id',
            $this->getCode()
        );
    }

    /**
     * Update product attribute set id
     *
     * @return void
     */
    public function updateAttributeSetId()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());

        if (!$connection->tableColumnExists($tmpTable, 'family')) {
            $this->setStatus(false);
            $this->setMessage(__('Column family is missing'));

            return;
        }

        /** @var string $entitiesTable */
        $entitiesTable = $this->entitiesHelper->getTable('akeneo_connector_entities');
        /** @var Select $families */
        $families = $connection->select()->from(false, ['_attribute_set_id' => 'c.entity_id'])->joinLeft(['c' => $entitiesTable], 'p.family = c.code AND c.import = "family"', []);

        $connection->query($connection->updateFromSelect($families, ['p' => $tmpTable]));

        /** @var bool $noFamily */
        $noFamily = (bool)$connection->fetchOne(
            $connection->select()->from($tmpTable, ['COUNT(*)'])->where('_attribute_set_id = ?', 0)
        );
        if ($noFamily) {
            $this->setStatus(false);
            $this->setMessage(__('Warning: %1 product(s) without family. Please try to import families.', $noFamily));
        }

        $connection->update(
            $tmpTable,
            ['_attribute_set_id' => $this->product->getDefaultAttributeSetId()],
            ['_attribute_set_id = ?' => 0]
        );
    }

    /**
     * Replace option code by id
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function updateOption()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var string[] $columns */
        $columns = array_keys($connection->describeTable($tmpTable));
        /** @var string[] $except */
        $except = [
            'url_key',
        ];
        $except = array_merge($except, $this->excludedColumns);

        /** @var string $column */
        foreach ($columns as $column) {
            if (in_array($column, $except) || preg_match('/-unit/', $column)) {
                continue;
            }

            if (!$connection->tableColumnExists($tmpTable, $column)) {
                continue;
            }

            /** @var string[] $columnParts */
            $columnParts = explode('-', $column, 2);
            /** @var string $columnPrefix */
            $columnPrefix = reset($columnParts);
            $columnPrefix = sprintf('%s_', $columnPrefix);
            /** @var int $prefixLength */
            $prefixLength = strlen($columnPrefix) + 1;
            /** @var string $entitiesTable */
            $entitiesTable = $this->entitiesHelper->getTable('akeneo_connector_entities');

            // Sub select to increase performance versus FIND_IN_SET
            /** @var Select $subSelect */
            $subSelect = $connection->select()->from(
                ['c' => $entitiesTable],
                ['code' => sprintf('SUBSTRING(`c`.`code`, %s)', $prefixLength), 'entity_id' => 'c.entity_id']
            )->where(sprintf('c.code LIKE "%s%s"', $columnPrefix, '%'))->where('c.import = ?', 'option');

            // if no option no need to continue process
            if (!$connection->query($subSelect)->rowCount()) {
                continue;
            }

            //in case of multiselect
            /** @var string $conditionJoin */
            $conditionJoin = "IF ( locate(',', `" . $column . "`) > 0 , " . "`p`.`" . $column . "` like " . new Expr(
                    "CONCAT('%', `c1`.`code`, '%')"
                ) . ", `p`.`" . $column . "` = `c1`.`code` )";

            /** @var Select $select */
            $select = $connection->select()->from(
                ['p' => $tmpTable],
                ['identifier' => 'p.identifier', 'entity_id' => 'p._entity_id']
            )->joinInner(
                ['c1' => new Expr('(' . (string)$subSelect . ')')],
                new Expr($conditionJoin),
                [$column => new Expr('GROUP_CONCAT(`c1`.`entity_id` SEPARATOR ",")')]
            )->group('p.identifier');

            /** @var string $query */
            $query = $connection->insertFromSelect(
                $select,
                $tmpTable,
                ['identifier', '_entity_id', $column],
                AdapterInterface::INSERT_ON_DUPLICATE
            );

            $connection->query($query);
        }
    }

    /**
     * Create product entities
     *
     * @return void
     */
    public function createEntities()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());

        if ($connection->isTableExists($this->entitiesHelper->getTable('sequence_product'))) {
            /** @var array $values */
            $values = ['sequence_value' => '_entity_id'];
            /** @var Select $parents */
            $parents = $connection->select()->from($tmpTable, $values);
            /** @var string $query */
            $query = $connection->insertFromSelect(
                $parents,
                $this->entitiesHelper->getTable('sequence_product'),
                array_keys($values),
                AdapterInterface::INSERT_ON_DUPLICATE
            );

            $connection->query($query);
        }

        /** @var string $table */
        $table = $this->entitiesHelper->getTable('catalog_product_entity');
        /** @var string $columnIdentifier */
        $columnIdentifier = $this->entitiesHelper->getColumnIdentifier($table);
        /** @var array $values */
        $values = [
            'entity_id'        => '_entity_id',
            'attribute_set_id' => '_attribute_set_id',
            'type_id'          => '_type_id',
            'sku'              => 'identifier',
            'has_options'      => new Expr(0),
            'required_options' => new Expr(0),
            'updated_at'       => new Expr('now()'),
        ];

        if ($columnIdentifier == 'row_id') {
            $values['row_id'] = '_entity_id';
        }

        /** @var Select $parents */
        $parents = $connection->select()->from($tmpTable, $values);
        /** @var string $query */
        $query = $connection->insertFromSelect(
            $parents,
            $table,
            array_keys($values),
            AdapterInterface::INSERT_ON_DUPLICATE
        );
        $connection->query($query);

        $values = ['created_at' => new Expr('now()')];
        $connection->update($table, $values, 'created_at IS NULL');

        if ($columnIdentifier == 'row_id') {
            $values = [
                'created_in' => new Expr(1),
                'updated_in' => new Expr(VersionManager::MAX_VERSION),
            ];
            $connection->update($table, $values, 'created_in = 0 AND updated_in = 0');
        }
    }

    /**
     * Set values to attributes
     *
     * @return void
     * @throws LocalizedException
     */
    public function setValues()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var string[] $attributeScopeMapping */
        $attributeScopeMapping = $this->entitiesHelper->getAttributeScopeMapping();
        /** @var array $stores */
        $stores = $this->storeHelper->getAllStores();
        /** @var string[] $columns */
        $columns = array_keys($connection->describeTable($tmpTable));

        // Format url_key columns
        /** @var string|array $matches */
        $matches = $this->configHelper->getAttributeMapping();
        if (is_array($matches)) {
            /** @var array $stores */
            $stores = $this->storeHelper->getAllStores();

            /** @var array $match */
            foreach ($matches as $match) {
                if (!isset($match['akeneo_attribute'], $match['magento_attribute'])) {
                    continue;
                }
                /** @var string $magentoAttribute */
                $magentoAttribute = $match['magento_attribute'];

                /**
                 * @var string $local
                 * @var string $affected
                 */
                foreach ($stores as $local => $affected) {
                    if ($magentoAttribute === 'url_key') {
                        $this->entitiesHelper->formatUrlKeyColumn($tmpTable, $local);
                    }
                }
            }
            $this->entitiesHelper->formatUrlKeyColumn($tmpTable);
        }

        /** @var string $adminBaseCurrency */
        $adminBaseCurrency = $this->storeManager->getStore()->getBaseCurrencyCode();
        /** @var mixed[] $values */
        $values = [
            0 => [
                'options_container' => '_options_container',
                'tax_class_id'      => '_tax_class_id',
                'visibility'        => '_visibility',
            ],
        ];

        if ($connection->tableColumnExists($tmpTable, 'enabled')) {
            $values[0]['status'] = '_status';
        }

        /** @var mixed[] $taxClasses */
        $taxClasses = $this->configHelper->getProductTaxClasses();
        if (count($taxClasses)) {
            foreach ($taxClasses as $storeId => $taxClassId) {
                $values[$storeId]['tax_class_id'] = new Expr($taxClassId);
            }
        }

        /** @var string $column */
        foreach ($columns as $column) {
            /** @var string[] $columnParts */
            $columnParts = explode('-', $column, 2);
            /** @var string $columnPrefix */
            $columnPrefix = $columnParts[0];

            if (in_array($columnPrefix, $this->excludedColumns) || preg_match('/-unit/', $column)) {
                continue;
            }

            if (!isset($attributeScopeMapping[$columnPrefix])) {
                // If no scope is found, attribute does not exist
                continue;
            }

            if (empty($columnParts[1])) {
                // No channel and no locale found: attribute scope naturally is Global
                $values[0][$columnPrefix] = $column;

                continue;
            }

            /** @var int $scope */
            $scope = (int)$attributeScopeMapping[$columnPrefix];
            if ($scope === \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL && !empty($columnParts[1]) && $columnParts[1] === $adminBaseCurrency) {
                // This attribute has global scope with a suffix: it is a price with its currency
                // If Price scope is set to Website, it will be processed afterwards as any website scoped attribute
                $values[0][$columnPrefix] = $column;

                continue;
            }

            /** @var string $columnSuffix */
            $columnSuffix = $columnParts[1];
            if (!isset($stores[$columnSuffix])) {
                // No corresponding store found for this suffix
                continue;
            }

            /** @var mixed[] $affectedStores */
            $affectedStores = $stores[$columnSuffix];
            /** @var mixed[] $store */
            foreach ($affectedStores as $store) {
                // Handle website scope
                if ($scope === \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_WEBSITE && !$store['is_website_default']) {
                    continue;
                }

                if ($scope === \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE || empty($store['siblings'])) {
                    $values[$store['store_id']][$columnPrefix] = $column;

                    continue;
                }

                /** @var string[] $siblings */
                $siblings = $store['siblings'];
                /** @var string $storeId */
                foreach ($siblings as $storeId) {
                    $values[$storeId][$columnPrefix] = $column;
                }
            }
        }

        /** @var int $entityTypeId */
        $entityTypeId = $this->configHelper->getEntityTypeId(BaseProductModel::ENTITY);

        /**
         * @var string   $storeId
         * @var string[] $data
         */
        foreach ($values as $storeId => $data) {
            $this->entitiesHelper->setValues(
                $this->getCode(),
                'catalog_product_entity',
                $data,
                $entityTypeId,
                $storeId,
                AdapterInterface::INSERT_ON_DUPLICATE
            );
        }
    }

    /**
     * Link configurable with children
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     * @throws LocalizedException
     */
    public function linkConfigurable()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());

        /** @var string|null $groupColumn */
        $groupColumn = null;
        if ($connection->tableColumnExists($tmpTable, 'parent')) {
            $groupColumn = 'parent';
        }
        if ($connection->tableColumnExists($tmpTable, 'groups') && !$groupColumn) {
            $groupColumn = 'groups';
        }
        if (!$groupColumn) {
            $this->setStatus(false);
            $this->setMessage(__('Columns groups or parent not found'));

            return;
        }

        /** @var Select $configurableSelect */
        $configurableSelect = $connection->select()->from($tmpTable, ['_entity_id', '_axis', '_children'])->where('_type_id = ?', 'configurable')->where('_axis IS NOT NULL')->where(
            '_children IS NOT NULL'
        );

        /** @var int $stepSize */
        $stepSize = self::CONFIGURABLE_INSERTION_MAX_SIZE;
        /** @var array $valuesLabels */
        $valuesLabels = [];
        /** @var array $valuesRelations */
        $valuesRelations = []; // catalog_product_relation
        /** @var array $valuesSuperLink */
        $valuesSuperLink = []; // catalog_product_super_link
        /** @var Zend_Db_Statement_Pdo $query */
        $query = $connection->query($configurableSelect);
        /** @var array $stores */
        $stores = $this->storeHelper->getStores('store_id');

        /** @var array $row */
        while ($row = $query->fetch()) {
            if (!isset($row['_axis'])) {
                continue;
            }

            /** @var array $attributes */
            $attributes = explode(',', $row['_axis']);
            /** @var int $position */
            $position = 0;

            /** @var int $id */
            foreach ($attributes as $id) {
                if (!is_numeric($id) || !isset($row['_entity_id']) || !isset($row['_children'])) {
                    continue;
                }

                /** @var bool $hasOptions */
                $hasOptions = (bool)$connection->fetchOne(
                    $connection->select()->from($this->entitiesHelper->getTable('eav_attribute_option'), [new Expr(1)])->where('attribute_id = ?', $id)->limit(1)
                );

                if (!$hasOptions) {
                    continue;
                }

                /** @var array $values */
                $values = [
                    'product_id'   => $row['_entity_id'],
                    'attribute_id' => $id,
                    'position'     => $position++,
                ];
                $connection->insertOnDuplicate(
                    $this->entitiesHelper->getTable('catalog_product_super_attribute'),
                    $values,
                    []
                );

                /** @var string $superAttributeId */
                $superAttributeId = $connection->fetchOne(
                    $connection->select()->from($this->entitiesHelper->getTable('catalog_product_super_attribute'))->where('attribute_id = ?', $id)->where('product_id = ?', $row['_entity_id'])->limit(
                        1
                    )
                );

                /**
                 * @var int   $storeId
                 * @var array $affected
                 */
                foreach ($stores as $storeId => $affected) {
                    $valuesLabels[] = [
                        'product_super_attribute_id' => $superAttributeId,
                        'store_id'                   => $storeId,
                        'use_default'                => 0,
                        'value'                      => '',
                    ];
                }

                /** @var array $children */
                $children = explode(',', $row['_children']);
                /** @var string $child */
                foreach ($children as $child) {
                    /** @var int $childId */
                    $childId = (int)$connection->fetchOne(
                        $connection->select()->from($this->entitiesHelper->getTable('catalog_product_entity'), ['entity_id'])->where('sku = ?', $child)->limit(1)
                    );

                    if (!$childId) {
                        continue;
                    }

                    $valuesRelations[] = [
                        'parent_id' => $row['_entity_id'],
                        'child_id'  => $childId,
                    ];

                    $valuesSuperLink[] = [
                        'product_id' => $childId,
                        'parent_id'  => $row['_entity_id'],
                    ];
                }

                if (count($valuesSuperLink) > $stepSize) {
                    $connection->insertOnDuplicate(
                        $this->entitiesHelper->getTable('catalog_product_super_attribute_label'),
                        $valuesLabels,
                        []
                    );

                    $connection->insertOnDuplicate(
                        $this->entitiesHelper->getTable('catalog_product_relation'),
                        $valuesRelations,
                        []
                    );

                    $connection->insertOnDuplicate(
                        $this->entitiesHelper->getTable('catalog_product_super_link'),
                        $valuesSuperLink,
                        []
                    );

                    $valuesLabels    = [];
                    $valuesRelations = [];
                    $valuesSuperLink = [];
                }
            }
        }

        if (count($valuesSuperLink) > 0) {
            $connection->insertOnDuplicate(
                $this->entitiesHelper->getTable('catalog_product_super_attribute_label'),
                $valuesLabels,
                []
            );

            $connection->insertOnDuplicate(
                $this->entitiesHelper->getTable('catalog_product_relation'),
                $valuesRelations,
                []
            );

            $connection->insertOnDuplicate(
                $this->entitiesHelper->getTable('catalog_product_super_link'),
                $valuesSuperLink,
                []
            );
        }
    }

    /**
     * Set website
     *
     * @return void
     * @throws LocalizedException
     */
    public function setWebsites()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var string $websiteAttribute */
        $websiteAttribute = $this->configHelper->getWebsiteAttribute();
        if ($websiteAttribute != null) {
            $attribute = $this->eavConfig->getAttribute('catalog_product', $websiteAttribute);
            if ($attribute->getAttributeId() != null) {
                /** @var string[] $options */
                $options = $attribute->getSource()->getAllOptions();
                /** @var array $websites */
                $websites      = $this->storeHelper->getStores('website_code');
                /** @var string[] $optionMapping */
                $optionMapping = [];
                /** @var array $apiAttribute */
                $apiAttribute = $this->akeneoClient->getAttributeOptionApi()->all($websiteAttribute, 1);
                // Generate the option_id / website_id mapping
                /**
                 * @var int   $index
                 * @var array $optionApiAttribute
                 */
                foreach ($apiAttribute as $index => $optionApiAttribute) {
                    /** @var string[] $option */
                    foreach ($options as $option) {
                        if (isset($option['label']) && isset($optionApiAttribute['labels']) && isset($optionApiAttribute['code'])) {
                            if (in_array($option['label'], $optionApiAttribute['labels'])) {
                                $websiteMatch = false;
                                /**
                                 * @var string $websiteCode
                                 * @var array  $affected
                                 */
                                foreach ($websites as $websiteCode => $affected) {
                                    if ($optionApiAttribute['code'] == $websiteCode) {
                                        if (isset($affected[0]['website_id'])){
                                            $websiteMatch  = true;
                                            $optionMapping += [$option['value'] => $affected[0]['website_id']];
                                        }
                                    }
                                }

                                if ($websiteMatch === false && $option['label'] != ' ') {
                                    $this->setAdditionalMessage(
                                        __('Warning: The option %1 is not a website code.', $optionApiAttribute['code'])
                                    );
                                }
                            }
                        }
                    }
                }

                if ($connection->tableColumnExists($tmpTable, $websiteAttribute)) {
                    /** @var \Magento\Framework\DB\Select $select */
                    $select = $connection->select()->from(
                        $tmpTable,
                        [
                            'entity_id'          => '_entity_id',
                            'identifier'         => 'identifier',
                            'associated_website' => $websiteAttribute,
                        ]
                    );
                    /** @var \Magento\Framework\DB\Statement\Pdo\Mysql $query */
                    $query = $connection->query($select);
                    /** @var array $row */
                    while (($row = $query->fetch())) {
                        /** @var string[] $associatedWebsites */
                        $associatedWebsites = $row['associated_website'];
                        if ($associatedWebsites != null) {
                            /** @var Select $deleteSelect */
                            $deleteSelect = $connection->select()->from(
                                $this->entitiesHelper->getTable('catalog_product_website')
                            )->where('product_id = ?', $row['entity_id']);

                            $connection->query(
                                $connection->deleteFromSelect(
                                    $deleteSelect,
                                    $this->entitiesHelper->getTable('catalog_product_website')
                                )
                            );

                            $associatedWebsites = explode(',', $associatedWebsites);
                            /** @var string $associatedWebsite */
                            foreach ($associatedWebsites as $associatedWebsite) {
                                /** @var bool $websiteSet */
                                $websiteSet = false;
                                /**
                                 * @var string $optionId
                                 * @var string $websiteId
                                 */
                                foreach ($optionMapping as $optionId => $websiteId) {
                                    if ($associatedWebsite == $optionId) {
                                        $websiteSet = true;
                                        /** @var Select $insertSelect */
                                        $insertSelect = $connection->select()->from(
                                            $tmpTable,
                                            [
                                                'product_id' => new Expr($row['entity_id']),
                                                'website_id' => new Expr($websiteId),
                                            ]
                                        );

                                        $connection->query(
                                            $connection->insertFromSelect(
                                                $insertSelect,
                                                $this->entitiesHelper->getTable('catalog_product_website'),
                                                ['product_id', 'website_id'],
                                                AdapterInterface::INSERT_ON_DUPLICATE
                                            )
                                        );
                                    }
                                }

                                if ($websiteSet === false) {
                                    $optionLabel = $attribute->getSource()->getOptionText($associatedWebsite);
                                    $this->setAdditionalMessage(__('Warning: The product with Akeneo id %1 has an option (%2) that does not correspond to a Magento website.', $row['identifier'], $optionLabel));
                                }
                            }
                        } else {
                            $this->setAdditionalMessage( __('Warning: The product with Akeneo id %1 has no associated website in the custom attribute.', $row['identifier']));
                        }
                    }
                }
            } else {
                $this->setAdditionalMessage(__('Warning: The website attribute code given does not match any Magento attribute.'));
            }
        } else {
            /** @var array $websites */
            $websites = $this->storeHelper->getStores('website_id');
            /**
             * @var int   $websiteId
             * @var array $affected
             */
            foreach ($websites as $websiteId => $affected) {
                if ($websiteId == 0) {
                    continue;
                }

                /** @var Select $select */
                $select = $connection->select()->from(
                    $tmpTable,
                    [
                        'product_id' => '_entity_id',
                        'website_id' => new Expr($websiteId),
                    ]
                );

                $connection->query(
                    $connection->insertFromSelect(
                        $select,
                        $this->entitiesHelper->getTable('catalog_product_website'),
                        ['product_id', 'website_id'],
                        AdapterInterface::INSERT_ON_DUPLICATE
                    )
                );
            }
        }
    }

    /**
     * Set categories
     *
     * @return void
     */
    public function setCategories()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());

        if (!$connection->tableColumnExists($tmpTable, 'categories')) {
            $this->setStatus(false);
            $this->setMessage(__('Column categories not found'));

            return;
        }

        /** @var Select $select */
        $select = $connection->select()->from(['c' => $this->entitiesHelper->getTable('akeneo_connector_entities')], [])->joinInner(
            ['p' => $tmpTable],
            'FIND_IN_SET(`c`.`code`, `p`.`categories`) AND `c`.`import` = "category"',
            [
                'category_id' => 'c.entity_id',
                'product_id'  => 'p._entity_id',
            ]
        )->joinInner(
            ['e' => $this->entitiesHelper->getTable('catalog_category_entity')],
            'c.entity_id = e.entity_id',
            []
        );

        $connection->query(
            $connection->insertFromSelect(
                $select,
                $this->entitiesHelper->getTable('catalog_category_product'),
                ['category_id', 'product_id'],
                1
            )
        );

        /** @var Select $selectToDelete */
        $selectToDelete = $connection->select()->from(['c' => $this->entitiesHelper->getTable('akeneo_connector_entities')], [])->joinInner(
            ['p' => $tmpTable],
            '!FIND_IN_SET(`c`.`code`, `p`.`categories`) AND `c`.`import` = "category"',
            [
                'category_id' => 'c.entity_id',
                'product_id'  => 'p._entity_id',
            ]
        )->joinInner(
            ['e' => $this->entitiesHelper->getTable('catalog_category_entity')],
            'c.entity_id = e.entity_id',
            []
        );

        $connection->delete(
            $this->entitiesHelper->getTable('catalog_category_product'),
            '(category_id, product_id) IN (' . $selectToDelete->assemble() . ')'
        );
    }

    /**
     * Init stock
     *
     * @return void
     */
    public function initStock()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var int $websiteId */
        $websiteId = $this->configHelper->getDefaultScopeId();
        /** @var array $values */
        $values = [
            'product_id'                => '_entity_id',
            'stock_id'                  => new Expr(1),
            'qty'                       => new Expr(0),
            'is_in_stock'               => new Expr(0),
            'low_stock_date'            => new Expr('NULL'),
            'stock_status_changed_auto' => new Expr(0),
            'website_id'                => new Expr($websiteId),
        ];

        /** @var Select $select */
        $select = $connection->select()->from($tmpTable, $values);

        $connection->query(
            $connection->insertFromSelect(
                $select,
                $this->entitiesHelper->getTable('cataloginventory_stock_item'),
                array_keys($values),
                AdapterInterface::INSERT_IGNORE
            )
        );
    }

    /**
     * Update related, up-sell and cross-sell products
     *
     * @return void
     * @throws \Zend_Db_Exception
     */
    public function setRelated()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var string $entitiesTable */
        $entitiesTable = $this->entitiesHelper->getTable('akeneo_connector_entities');
        /** @var string $productsTable */
        $productsTable = $this->entitiesHelper->getTable('catalog_product_entity');
        /** @var string $linkTable */
        $linkTable = $this->entitiesHelper->getTable('catalog_product_link');
        /** @var string $linkAttributeTable */
        $linkAttributeTable = $this->entitiesHelper->getTable('catalog_product_link_attribute');
        /** @var mixed[] $related */
        $related = [];

        /** @var string $columnIdentifier */
        $columnIdentifier = $this->entitiesHelper->getColumnIdentifier($productsTable);

        /** @var int $linkType */
        /** @var string[] $associationNames */
        foreach ($this->associationTypes as $linkType => $associationNames) {
            if (empty($associationNames)) {
                continue;
            }
            /** @var string $associationName */
            foreach ($associationNames as $associationName) {
                if (!empty($associationName) && $connection->tableColumnExists($tmpTable, $associationName)) {
                    $related[$linkType][] = sprintf('`p`.`%s`', $associationName);
                }
            }
        }

        /** @var \Magento\Framework\DB\Select $productIds */
        $productIds = $connection->select()->from($tmpTable, ['product_id' => '_entity_id']);

        /**
         * @var int      $typeId
         * @var string[] $columns
         */
        foreach ($related as $typeId => $columns) {
            /** @var string $concat */
            $concat = sprintf('CONCAT_WS(",", %s)', implode(', ', $columns));
            /** @var \Magento\Framework\DB\Select $select */
            $select = $connection->select()->from(['c' => $entitiesTable], [])->joinInner(
                ['p' => $tmpTable],
                sprintf('FIND_IN_SET(`c`.`code`, %s) AND `c`.`import` = "%s"', $concat, $this->getCode()),
                [
                    'product_id'        => 'p._entity_id',
                    'linked_product_id' => 'c.entity_id',
                    'link_type_id'      => new Expr($typeId),
                ]
            )->joinInner(['e' => $productsTable], sprintf('c.entity_id = e.%s', $columnIdentifier), []);

            /* Remove old link */
            $connection->delete(
                $linkTable,
                ['(product_id, linked_product_id, link_type_id) NOT IN (?)' => $select, 'link_type_id = ?' => $typeId, 'product_id IN (?)' => $productIds]
            );

            /* Insert new link */
            $connection->query(
                $connection->insertFromSelect(
                    $select,
                    $linkTable,
                    ['product_id', 'linked_product_id', 'link_type_id'],
                    AdapterInterface::INSERT_ON_DUPLICATE
                )
            );

            /* Insert position */
            $attributeId = $connection->fetchOne(
                $connection->select()->from($linkAttributeTable, ['product_link_attribute_id'])->where('product_link_attribute_code = ?', ProductLink::KEY_POSITION)->where('link_type_id = ?', $typeId)
            );

            if ($attributeId) {
                $select = $connection->select()->from($linkTable, [new Expr($attributeId), 'link_id', 'link_id'])->where('link_type_id = ?', $typeId);

                $connection->query(
                    $connection->insertFromSelect(
                        $select,
                        $this->entitiesHelper->getTable('catalog_product_link_attribute_int'),
                        ['product_link_attribute_id', 'link_id', 'value'],
                        AdapterInterface::INSERT_ON_DUPLICATE
                    )
                );
            }
        }
    }

    /**
     * Set Url Rewrite
     *
     * @return void
     * @throws LocalizedException
     * @throws \Zend_Db_Exception
     */
    public function setUrlRewrite()
    {
        if (!$this->configHelper->isUrlGenerationEnabled()) {
            $this->setStatus(false);
            $this->setMessage(
                __('Url rewrite generation is not enabled')
            );

            return;
        }

        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tableName */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var array $stores */
        $stores = array_merge(
            $this->storeHelper->getStores(['lang']), // en_US
            $this->storeHelper->getStores(['lang', 'channel_code']) // en_US-channel
        );
        /** @var bool $isUrlKeyMapped */
        $isUrlKeyMapped = $this->configHelper->isUrlKeyMapped();

        /**
         * @var string $local
         * @var array  $affected
         */
        foreach ($stores as $local => $affected) {
            if (!$connection->tableColumnExists($tmpTable, 'url_key-' . $local)) {
                $connection->addColumn(
                    $tmpTable,
                    'url_key-' . $local,
                    [
                        'type'     => 'text',
                        'length'   => 255,
                        'default'  => '',
                        'COMMENT'  => ' ',
                        'nullable' => false,
                    ]
                );
                $connection->update($tmpTable, ['url_key-' . $local => new Expr('`url_key`')]);
            }

            /**
             * @var array $affected
             * @var array $store
             */
            foreach ($affected as $store) {
                if (!$store['store_id']) {
                    continue;
                }
                /** @var \Magento\Framework\DB\Select $select */
                $select = $connection->select()->from(
                    $tmpTable,
                    [
                        'entity_id' => '_entity_id',
                        'url_key'   => 'url_key-' . $local,
                        'store_id'  => new Expr($store['store_id']),
                    ]
                );

                /** @var \Magento\Framework\DB\Statement\Pdo\Mysql $query */
                $query = $connection->query($select);

                /** @var array $row */
                while (($row = $query->fetch())) {
                    /** @var BaseProductModel $product */
                    $product = $this->product;
                    $product->setData($row);

                    /** @var string $urlPath */
                    $urlPath = $this->productUrlPathGenerator->getUrlPath($product);

                    if (!$urlPath) {
                        continue;
                    }

                    /** @var string $requestPath */
                    $requestPath = $this->productUrlPathGenerator->getUrlPathWithSuffix(
                        $product,
                        $product->getStoreId()
                    );

                    $requestPath = $this->entitiesHelper->verifyProductUrl($requestPath, $product);

                    /** @var array $paths */
                    $paths = [
                        $requestPath => [
                            'request_path' => $requestPath,
                            'target_path'  => 'catalog/product/view/id/' . $product->getEntityId(),
                            'metadata'     => null,
                            'category_id'  => null,
                        ],
                    ];

                    /** @var bool $isCategoryUsedInProductUrl */
                    $isCategoryUsedInProductUrl = $this->configHelper->isCategoryUsedInProductUrl(
                        $product->getStoreId()
                    );

                    if ($isCategoryUsedInProductUrl) {
                        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $categories */
                        $categories = $product->getCategoryCollection();
                        $categories->addAttributeToSelect('url_key');

                        /** @var CategoryModel $category */
                        foreach ($categories as $category) {
                            /** @var string $requestPath */
                            $requestPath         = $this->productUrlPathGenerator->getUrlPathWithSuffix(
                                $product,
                                $product->getStoreId(),
                                $category
                            );
                            $paths[$requestPath] = [
                                'request_path' => $requestPath,
                                'target_path'  => 'catalog/product/view/id/' . $product->getEntityId() . '/category/' . $category->getId(),
                                'metadata'     => '{"category_id":"' . $category->getId() . '"}',
                                'category_id'  => $category->getId(),
                            ];
                            $parents             = $category->getParentCategories();
                            foreach ($parents as $parent) {
                                /** @var string $requestPath */
                                $requestPath = $this->productUrlPathGenerator->getUrlPathWithSuffix(
                                    $product,
                                    $product->getStoreId(),
                                    $parent
                                );
                                if (isset($paths[$requestPath])) {
                                    continue;
                                }
                                $paths[$requestPath] = [
                                    'request_path' => $requestPath,
                                    'target_path'  => 'catalog/product/view/id/' . $product->getEntityId() . '/category/' . $parent->getId(),
                                    'metadata'     => '{"category_id":"' . $parent->getId() . '"}',
                                    'category_id'  => $parent->getId(),
                                ];
                            }
                        }
                    }

                    foreach ($paths as $path) {
                        if (!isset($path['request_path'], $path['target_path'])) {
                            continue;
                        }
                        /** @var string $requestPath */
                        $requestPath = $path['request_path'];
                        /** @var string $targetPath */
                        $targetPath = $path['target_path'];
                        /** @var string $metadata */
                        $metadata = $path['metadata'];

                        /** @var string|null $rewriteId */
                        $rewriteId = $connection->fetchOne(
                            $connection->select()->from($this->entitiesHelper->getTable('url_rewrite'), ['url_rewrite_id'])->where('entity_type = ?', ProductUrlRewriteGenerator::ENTITY_TYPE)->where(
                                'target_path = ?',
                                $targetPath
                            )->where('entity_id = ?', $product->getEntityId())->where('store_id = ?', $product->getStoreId())
                        );

                        if ($rewriteId) {
                            $connection->update(
                                $this->entitiesHelper->getTable('url_rewrite'),
                                ['request_path' => $requestPath, 'metadata' => $metadata],
                                ['url_rewrite_id = ?' => $rewriteId]
                            );
                        } else {
                            /** @var array $data */
                            $data = [
                                'entity_type'      => ProductUrlRewriteGenerator::ENTITY_TYPE,
                                'entity_id'        => $product->getEntityId(),
                                'request_path'     => $requestPath,
                                'target_path'      => $targetPath,
                                'redirect_type'    => 0,
                                'store_id'         => $product->getStoreId(),
                                'is_autogenerated' => 1,
                                'metadata'         => $metadata,
                            ];

                            $connection->insertOnDuplicate(
                                $this->entitiesHelper->getTable('url_rewrite'),
                                $data,
                                array_keys($data)
                            );

                            if ($isCategoryUsedInProductUrl && $path['category_id']) {
                                /** @var int $rewriteId */
                                $rewriteId = $connection->fetchOne(
                                    $connection->select()
                                        ->from($this->entitiesHelper->getTable('url_rewrite'), ['url_rewrite_id'])
                                        ->where('entity_type = ?', ProductUrlRewriteGenerator::ENTITY_TYPE)
                                        ->where('target_path = ?', $targetPath)
                                        ->where('entity_id = ?', $product->getEntityId())
                                        ->where('store_id = ?', $product->getStoreId())
                                );
                            }
                        }

                        if ($isCategoryUsedInProductUrl && $rewriteId && $path['category_id']) {
                            $data = [
                                'url_rewrite_id' => $rewriteId,
                                'category_id'    => $path['category_id'],
                                'product_id'     => $product->getEntityId(),
                            ];
                            $connection->delete(
                                $this->entitiesHelper->getTable('catalog_url_rewrite_product_category'),
                                ['url_rewrite_id = ?' => $rewriteId]
                            );
                            $connection->insertOnDuplicate(
                                $this->entitiesHelper->getTable('catalog_url_rewrite_product_category'),
                                $data,
                                array_keys($data)
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Import the medias
     *
     * @return void
     */
    public function importMedia()
    {
        if (!$this->configHelper->isMediaImportEnabled()) {
            $this->setStatus(false);
            $this->setMessage(__('Media import is not enabled'));

            return;
        }

        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tableName */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var array $gallery */
        $gallery = $this->configHelper->getMediaImportGalleryColumns();

        if (empty($gallery)) {
            $this->setStatus(false);
            $this->setMessage(__('Akeneo Images Attributes is empty'));

            return;
        }

        /** @var string $table */
        $table = $this->entitiesHelper->getTable('catalog_product_entity');
        /** @var string $columnIdentifier */
        $columnIdentifier = $this->entitiesHelper->getColumnIdentifier($table);

        /** @var array $data */
        $data = [
            $columnIdentifier => '_entity_id',
            'sku'             => 'identifier',
        ];
        foreach ($gallery as $image) {
            if (!$connection->tableColumnExists($tmpTable, $image)) {
                $this->setMessage(__('Warning: %1 attribute does not exist', $image));
                continue;
            }
            $data[$image] = $image;
        }

        /** @var \Magento\Framework\DB\Select $select */
        $select = $connection->select()->from($tmpTable, $data);

        /** @var \Magento\Framework\DB\Statement\Pdo\Mysql $query */
        $query = $connection->query($select);

        /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $galleryAttribute */
        $galleryAttribute = $this->configHelper->getAttribute(BaseProductModel::ENTITY, 'media_gallery');
        /** @var string $galleryTable */
        $galleryTable = $this->entitiesHelper->getTable('catalog_product_entity_media_gallery');
        /** @var string $galleryEntityTable */
        $galleryEntityTable = $this->entitiesHelper->getTable('catalog_product_entity_media_gallery_value_to_entity');
        /** @var string $productImageTable */
        $productImageTable = $this->entitiesHelper->getTable('catalog_product_entity_varchar');

        /** @var array $row */
        while (($row = $query->fetch())) {
            /** @var array $files */
            $files = [];
            foreach ($gallery as $image) {
                if (!isset($row[$image])) {
                    continue;
                }

                if (!$row[$image]) {
                    continue;
                }

                /** @var array $media */
                $media = $this->akeneoClient->getProductMediaFileApi()->get($row[$image]);
                /** @var string $name */
                $name = $this->entitiesHelper->formatMediaName(basename($media['code']));

                if (!$this->configHelper->mediaFileExists($name)) {
                    $binary = $this->akeneoClient->getProductMediaFileApi()->download($row[$image]);
                    $this->configHelper->saveMediaFile($name, $binary);
                }

                /** @var string $file */
                $file = $this->configHelper->getMediaFilePath($name);

                /** @var int $valueId */
                $valueId = $connection->fetchOne(
                    $connection->select()->from($galleryTable, ['value_id'])->where('value = ?', $file)
                );

                if (!$valueId) {
                    /** @var int $valueId */
                    $valueId = $connection->fetchOne(
                        $connection->select()->from($galleryTable, [new Expr('MAX(`value_id`)')])
                    );
                    $valueId += 1;
                }

                /** @var array $data */
                $data = [
                    'value_id'     => $valueId,
                    'attribute_id' => $galleryAttribute->getId(),
                    'value'        => $file,
                    'media_type'   => ImageEntryConverter::MEDIA_TYPE_CODE,
                    'disabled'     => 0,
                ];
                $connection->insertOnDuplicate($galleryTable, $data, array_keys($data));

                /** @var array $data */
                $data = [
                    'value_id'        => $valueId,
                    $columnIdentifier => $row[$columnIdentifier],
                ];
                $connection->insertOnDuplicate($galleryEntityTable, $data, array_keys($data));

                /** @var array $columns */
                $columns = $this->configHelper->getMediaImportImagesColumns();

                foreach ($columns as $column) {
                    if ($column['column'] !== $image) {
                        continue;
                    }
                    /** @var array $data */
                    $data = [
                        'attribute_id'    => $column['attribute'],
                        'store_id'        => 0,
                        $columnIdentifier => $row[$columnIdentifier],
                        'value'           => $file,
                    ];
                    $connection->insertOnDuplicate($productImageTable, $data, array_keys($data));
                }

                $files[] = $file;
            }

            /** @var \Magento\Framework\DB\Select $cleaner */
            $cleaner = $connection->select()->from($galleryTable, ['value_id'])->where('value NOT IN (?)', $files);

            $connection->delete(
                $galleryEntityTable,
                [
                    'value_id IN (?)'          => $cleaner,
                    $columnIdentifier . ' = ?' => $row[$columnIdentifier],
                ]
            );
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
     * Clean cache
     *
     * @return void
     */
    public function cleanCache()
    {
        /** @var array $types */
        $types = [
            Block::TYPE_IDENTIFIER,
            Type::TYPE_IDENTIFIER,
        ];

        /** @var string $type */
        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }

        $this->setMessage(__('Cache cleaned for: %1', join(', ', $types)));
    }

    /**
     * Retrieve product filters
     *
     * @return mixed[]
     */
    protected function getFilters()
    {
        if (empty($this->filters)) {
            /** @var mixed[] $filters */
            $filters = $this->productFilters->getFilters();
            if (array_key_exists('error', $filters)) {
                $this->setMessage($filters['error']);
                $this->stop(true);
            }

            $this->filters = $filters;
        }

        return $this->filters;
    }
}
