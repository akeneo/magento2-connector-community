<?php

namespace Akeneo\Connector\Job;

use Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field\Configurable as TypeField;
use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\FamilyVariant;
use Akeneo\Connector\Helper\Import\Entities;
use Akeneo\Connector\Helper\Import\Product as ProductImportHelper;
use Akeneo\Connector\Helper\Output as OutputHelper;
use Akeneo\Connector\Helper\ProductFilters;
use Akeneo\Connector\Helper\ProductModel;
use Akeneo\Connector\Helper\Serializer as JsonSerializer;
use Akeneo\Connector\Helper\Store as StoreHelper;
use Akeneo\Connector\Job\Import as JobImport;
use Akeneo\Connector\Job\Option as JobOption;
use Akeneo\Connector\Model\Source\Attribute\Metrics as AttributeMetrics;
use Akeneo\Connector\Model\Source\Edition;
use Akeneo\Connector\Model\Source\Filters\Mode;
use Akeneo\Connector\Model\Source\Filters\ModelCompleteness;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\Product as BaseProductModel;
use Magento\Catalog\Model\Product\Attribute\Backend\Media\ImageEntryConverter;
use Magento\Catalog\Model\Product\Link;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductLink\Link as ProductLink;
use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\ResourceModel\Entity\Attribute as EavAttribute;
use Magento\Framework\App\Cache\Type\Block;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Statement\Pdo\Mysql;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\PageCache\Model\Cache\Type;
use Magento\Staging\Model\VersionManager;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Zend_Db_Expr as Expr;
use Zend_Db_Statement_Exception;
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
class Product extends JobImport
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
     * Description CATALOG_PRODUCT_ENTITY_TABLE_NAME constant
     *
     * @var string CATALOG_PRODUCT_ENTITY_TABLE_NAME
     */
    const CATALOG_PRODUCT_ENTITY_TABLE_NAME = 'catalog_product_entity';
    /**
     * This variable contains a string value
     *
     * @var string $code
     */
    protected $code = 'product';
    /**
     * This variable contains entities
     *
     * @var Entities $entities
     */
    protected $entities;
    /**
     * This variable contains a string
     *
     * @var string $step
     */
    protected $family = null;
    /**
     * This variable contains a string value
     *
     * @var string $name
     */
    protected $name = 'Product';

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
     * This variable contains a ProductModel
     *
     * @var ProductModel $productModelHelper
     */
    protected $productModelHelper;
    /**
     * This variable contains a FamilyVariant
     *
     * @var FamilyVariant $familyVariantHelper
     */
    protected $familyVariantHelper;
    /**
     * This variable contains an EavConfig
     *
     * @var  EavConfig $eavConfig
     */
    protected $eavConfig;
    /**
     * This variable contains an EavAttribute
     *
     * @var  EavConfig $eavConfig
     */
    protected $eavAttribute;
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
     * @param ProductModel            $productModel
     * @param FamilyVariant           $familyVariant
     * @param EavConfig               $eavConfig
     * @param EavAttribute            $eavAttribute
     * @param ProductFilters          $productFilters
     * @param ScopeConfigInterface    $scopeConfig
     * @param JsonSerializer          $serializer
     * @param BaseProductModel        $product
     * @param ProductUrlPathGenerator $productUrlPathGenerator
     * @param TypeListInterface       $cacheTypeList
     * @param StoreHelper             $storeHelper
     * @param Entities                $entities
     * @param Option                  $jobOption
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
        ProductModel $productModel,
        FamilyVariant $familyVariant,
        EavConfig $eavConfig,
        EavAttribute $eavAttribute,
        ProductFilters $productFilters,
        ScopeConfigInterface $scopeConfig,
        JsonSerializer $serializer,
        BaseProductModel $product,
        ProductUrlPathGenerator $productUrlPathGenerator,
        TypeListInterface $cacheTypeList,
        StoreHelper $storeHelper,
        Entities $entities,
        JobOption $jobOption,
        AttributeMetrics $attributeMetrics,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($outputHelper, $eventManager, $authenticator, $data);

        $this->entitiesHelper          = $entitiesHelper;
        $this->configHelper            = $configHelper;
        $this->productModelHelper      = $productModel;
        $this->familyVariantHelper     = $familyVariant;
        $this->eavConfig               = $eavConfig;
        $this->eavAttribute            = $eavAttribute;
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
        $this->entities                = $entities;
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

        // Stop the import if the family is not imported
        if ($this->getFamily()) {
            /** @var AdapterInterface $connection */
            $connection = $this->entitiesHelper->getConnection();
            /** @var string $connectorEntitiesTable */
            $connectorEntitiesTable = $this->entities->getTable($this->entities::TABLE_NAME);
            /** @var bool $isFamilyImported */
            $isFamilyImported = (bool)$connection->fetchOne(
                $connection->select()
                    ->from($connectorEntitiesTable, ['code'])
                    ->where('code = ?', $this->getFamily())
                    ->limit(1)
            );

            if (!$isFamilyImported) {
                $this->setMessage(__('The family %1 is not imported yet, please run Family import.', $this->getFamily()));
                $this->stop(true);

                return;
            }
        }

        /** @var mixed[] $filters */
        $filters = $this->getFilters($this->getFamily());

        foreach ($filters as $filter) {
            /** @var PageInterface $products */
            $products = $this->akeneoClient->getProductApi()->listPerPage(1, false, $filter);
            /** @var mixed[] $products */
            $products = $products->getItems();

            if (!empty($products)) {
                break;
            }
        }

        if (empty($products)) {
            // No product were found and we're in a grouped family, we don't import product models for it, so we stop the import
            if ($this->entitiesHelper->isFamilyGrouped($this->getFamily())) {
                $this->setMessage(__('No results from Akeneo for the family: %1', $this->getFamily()))->stop();

                return;
            }

            /** @var mixed[] $modelFilters */
            $modelFilters = $this->getProductModelFilters($this->getFamily());
            foreach ($modelFilters as $filter) {
                /** @var PageInterface $productModels */
                $productModels = $this->akeneoClient->getProductModelApi()->listPerPage(1, false, $filter);
                /** @var array $productModel */
                $productModels = $productModels->getItems();

                if (!empty($productModels)) {
                    break;
                }
            }

            if (empty($productModels)) {
                $this->setMessage(__('No results from Akeneo for the family: %1', $this->getFamily()))->stop();

                return;
            }
            $productModel = reset($productModels);
            $this->entitiesHelper->createTmpTableFromApi($productModel, $this->getCode());
            $this->entitiesHelper->createTmpTableFromApi($productModel, 'product_model');
            $this->setMessage(
                __('No product found for family: %1 but product model found, process with import', $this->getFamily())
            );

            return;
        } else {
            $product = reset($products);
            // Make sure to delete product model table
            $this->entitiesHelper->dropTable('product_model');
            $this->entitiesHelper->createTmpTableFromApi($product, $this->getCode());

            /** @var string $message */
            $message = __('Family imported in this batch: %1', $this->getFamily());
            $this->setMessage($message);
        }
    }

    /**
     * Insert data into temporary table
     *
     * @return void
     */
    public function insertData()
    {
        /** @var string|int $paginationSize */
        $paginationSize = $this->configHelper->getPaginationSize();
        /** @var int $index */
        $index = 0;
        /** @var mixed[] $filters */
        $filters = $this->getFilters($this->getFamily());
        /** @var mixed[] $metricsConcatSettings */
        $metricsConcatSettings = $this->configHelper->getMetricsColumns(null, true);
        /** @var string[] $metricSymbols */
        $metricSymbols = $this->getMetricsSymbols();
        /** @var string[] $attributeMetrics */
        $attributeMetrics = $this->attributeMetrics->getMetricsAttributes();
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();

        if ($connection->isTableExists($this->entitiesHelper->getTableName('product_model'))) {
            return;
        }

        /** @var mixed[] $filter */
        foreach ($filters as $filter) {
            /** @var ResourceCursorInterface $products */
            $products = $this->akeneoClient->getProductApi()->all($paginationSize, $filter);

            /**
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
                        if ($amount != null) {
                            $amount = floatval($amount);
                        }

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

        // Remove declared file attributes columns if file import is disabled
        if (!$this->configHelper->isFileImportEnabled()) {
            /** @var array $attributesToImport */
            $attributesToImport = $this->configHelper->getFileImportColumns();

            if (!empty($attributesToImport)) {
                $attributesToImport = array_unique($attributesToImport);

                /** @var array $stores */
                $stores = array_merge(
                    $this->storeHelper->getStores(['lang']), // en_US
                    $this->storeHelper->getStores(['channel_code']), // channel
                    $this->storeHelper->getStores(['lang', 'channel_code']) // en_US-channel
                );

                /** @var string $tmpTable */
                $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
                /** @var array $data */
                foreach ($attributesToImport as $attribute) {
                    if ($connection->tableColumnExists($tmpTable, $attribute)) {
                        $connection->dropColumn($tmpTable, $attribute);
                    }

                    // Remove scopable colums
                    foreach ($stores as $suffix => $storeData) {
                        if ($connection->tableColumnExists($tmpTable, $attribute . '-' . $suffix)) {
                            $connection->dropColumn($tmpTable, $attribute . '-' . $suffix);
                        }
                    }
                }
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
     * Import product model
     *
     * @return void
     * @throws FileSystemException
     */
    public function productModelImport()
    {
        if ($this->entitiesHelper->isFamilyGrouped($this->getFamily())) {
            return;
        }

        /** @var string[] $messages */
        $messages = [];
        /** @var mixed[] $filters */
        $filters = $this->getProductModelFilters($this->getFamily());
        /** @var mixed[] $step */
        $step = $this->productModelHelper->createTable($this->akeneoClient, $filters);
        $messages[] = $step;
        if (array_keys(array_column($step, 'status'), false)) {
            $this->displayMessages($messages);

            return;
        }

        /** @var mixed[] $stepInsertData */
        $step = $this->productModelHelper->insertData($this->akeneoClient, $filters);
        $messages[] = $step;
        if (array_keys(array_column($step, 'status'), false)) {
            $this->displayMessages($messages);

            return;
        }
        // Add missing columns from product models in product tmp table
        $this->productModelHelper->addColumns($this->getCode());

        $this->displayMessages($messages);
    }

    /**
     * Import Family Variant : update temporrary product model table with the correct axis
     *
     * @return void
     */
    public function familyVariantImport()
    {
        if ($this->entitiesHelper->isFamilyGrouped($this->getFamily())) {
            return;
        }

        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        if ($connection->isTableExists($this->entitiesHelper->getTableName('product_model'))) {
            /** @var string[] $messages */
            $messages = [];

            /** @var mixed[] $step */
            $step       = $this->familyVariantHelper->createTable($this->akeneoClient, $this->getFamily());
            $messages[] = $step;
            if (array_keys(array_column($step, 'status'), false)) {
                $this->displayMessages($messages);

                return;
            }
            /** @var mixed[] $step */
            $step       = $this->familyVariantHelper->insertData($this->akeneoClient, $this->getFamily());
            $messages[] = $step;
            if (array_keys(array_column($step, 'status'), false)) {
                $this->displayMessages($messages);

                return;
            }
            $this->familyVariantHelper->updateAxis();
            $this->familyVariantHelper->updateProductModel();
            $this->familyVariantHelper->dropTable();
            $this->displayMessages($messages);
        }
    }

    /**
     * Generate array of metrics with unit in key and symbol for value
     *
     * @return string[]
     */
    public function getMetricsSymbols()
    {
        /** @var string|int $paginationSize */
        $paginationSize = $this->configHelper->getPaginationSize();
        /** @var mixed[] $measures */
        $measures = $this->akeneoClient->getMeasureFamilyApi()->all($paginationSize);
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

        /** @var string $edition */
        $edition = $this->configHelper->getEdition();
        // If family is grouped, create grouped products
        if (($edition === Edition::SERENITY || $edition === Edition::GREATER_OR_FIVE) && $this->entitiesHelper->isFamilyGrouped($this->getFamily())) {
            $connection->addColumn(
                $tmpTable,
                '_type_id',
                [
                    'type'     => 'text',
                    'length'   => 255,
                    'default'  => 'grouped',
                    'COMMENT'  => ' ',
                    'nullable' => false,
                ]
            );
        } else {
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
        }
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

        if (!$connection->tableColumnExists($tmpTable, 'is_returnable')) {
            $connection->addColumn(
                $tmpTable,
                'is_returnable',
                [
                    'type'     => 'integer',
                    'length'   => 11,
                    'default'  => 2,
                    'COMMENT'  => ' ',
                    'nullable' => false,
                ]
            );
        }

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

        if ($connection->tableColumnExists($tmpTable, 'enabled')) {
            $connection->update($tmpTable, ['_status' => new Expr('IF(`enabled` <> 1, 2, 1)')], ['_type_id = ?' => 'simple']);
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
             * @var string $affected
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

        if (!$connection->isTableExists($this->entitiesHelper->getTableName($this->jobOption->getCode()))) {
            $this->setMessage(__('No metric option to import'));

            return;
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
        if ($this->entitiesHelper->isFamilyGrouped($this->getFamily())) {
            return;
        }

        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());

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

        // No product models were imported during this import, skip
        if (!$connection->isTableExists($this->entitiesHelper->getTableName('product_model'))) {
            return;
        }
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

        /** @var string $productModelTable */
        $productModelTable = $this->entitiesHelper->getTableName('product_model');

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
            'identifier'         => 'v.code',
            '_type_id'           => new Expr('"configurable"'),
            '_options_container' => new Expr('"container1"'),
            '_axis'              => 'v.axis',
            'family'             => 'v.family',
            'categories'         => 'v.categories',
        ];

        if ($this->configHelper->isUrlGenerationEnabled()) {
            $data['url_key'] = 'v.code';
        }

        /** @var array $columnsModel */
        $columnsModel = array_keys($connection->describeTable($productModelTable));
        foreach ($columnsModel  as $columnModel) {
            if (!isset($data[$columnModel])) {
                $data[$columnModel] = 'v.' . $columnModel;
            }
        }

        /** @var string[] $associationTypes */
        $associationTypes = $this->configHelper->getAssociationTypes();
        /** @var string[] $associationNames */
        foreach ($associationTypes as $associationNames) {
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
            $name = strtolower($attribute['attribute']);
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
            ->from(['v' => $productModelTable], $data)
            ->joinLeft(['e' => $tmpTable], 'v.code = ' . 'e.' . $groupColumn, [])
            ->where('v.parent IS NULL')
            ->group('v.code');

        /** @var string $query */
        $query = $connection->insertFromSelect($configurable, $tmpTable, array_keys($data));

        $connection->query($query);

        // Update _children column if possible
        /** @var Select $childList */
        $childList = $connection->select()
            ->from(['v' => $productModelTable], ['v.identifier', '_children' => new Expr('GROUP_CONCAT(e.identifier SEPARATOR ",")')])
            ->joinInner(['e' => $tmpTable], 'v.code = ' . 'e.' . $groupColumn, [])
            ->group('v.identifier');

        /** @var string $queryChilds */
        $queryChilds = $connection->query($childList);
        /** @var array $row */
        while (($row = $queryChilds->fetch())) {
            /** @var array $values */
            $values = [
                'identifier' => $row['identifier'],
                '_children' => $row['_children'],
            ];

            $connection->insertOnDuplicate(
                $tmpTable,
                $values,
                []
            );
        }
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
        $entityTable = $this->entitiesHelper->getTable(self::CATALOG_PRODUCT_ENTITY_TABLE_NAME);
        /** @var Select $selectExistingEntities */
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
            $columnPrefix = sprintf('%s-', $columnPrefix);
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
            $conditionJoin = "IF ( locate(',', `" . $column . "`) > 0 , " . new Expr(
                "FIND_IN_SET(`c1`.`code`,`p`.`" . $column . "`) > 0"
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

        /** @var array $values */
        $values = [
            'entity_id'        => '_entity_id',
            'attribute_set_id' => '_attribute_set_id',
            'type_id'          => '_type_id',
            'sku'              => 'identifier',
            'updated_at'       => new Expr('now()'),
        ];

        /** @var Select $parents */
        $parents = $connection->select()->from($tmpTable, $values);

        /** @var bool $rowIdExists */
        $rowIdExists = $this->entitiesHelper->rowIdColumnExists($table);
        if ($rowIdExists) {
            $this->entities->addJoinForContentStaging($parents, ['p.row_id']);
            $values['row_id'] = 'IFNULL (p.row_id, _entity_id)'; // on product creation, row_id is null
        }

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

        if ($rowIdExists) {
            $values = [
                'created_in' => new Expr(1),
                'updated_in' => new Expr(VersionManager::MAX_VERSION),
            ];
            $connection->update($table, $values, 'created_in = 0 AND updated_in = 0');
        }
    }

    /**
     * Import the files
     *
     * @return void
     */
    public function importFiles()
    {
        if (!$this->configHelper->isFileImportEnabled()) {
            $this->setStatus(true);
            $this->setMessage(__('File import is not enabled'));

            return;
        }

        /** @var array $stores */
        $stores = array_merge(
            $this->storeHelper->getStores(['lang']), // en_US
            $this->storeHelper->getStores(['channel_code']), // channel
            $this->storeHelper->getStores(['lang', 'channel_code']) // en_US-channel
        );

        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var array $attributesMapped */
        $attributesMapped = $this->configHelper->getFileImportColumns();

        if (empty($attributesMapped)) {
            $this->setStatus(false);
            $this->setMessage(__('Akeneo Files Attributes is empty'));

            return;
        }

        $attributesMapped = array_unique($attributesMapped);

        /** @var string $table */
        $table = $this->entitiesHelper->getTable('catalog_product_entity');
        /** @var string $columnIdentifier */
        $columnIdentifier = $this->entitiesHelper->getColumnIdentifier($table);

        /** @var array $data */
        $data = [
            $columnIdentifier => '_entity_id',
            'sku'             => 'identifier',
        ];
        /** @var string[] $attributeToImport */
        $attributeToImport = [];

        // Get all attributes to import
        foreach ($attributesMapped as $attribute) {
            $attribute = strtolower($attribute);

            /** @var bool $attributeUsed */
            if ($connection->tableColumnExists($tmpTable, $attribute)) {
                $data[$attribute] = $attribute;
                $attributeToImport[] = $attribute;
            }
            // Get the scopable attributes
            foreach ($stores as $suffix => $storeData) {
                if ($connection->tableColumnExists($tmpTable, $attribute . '-' . $suffix)) {
                    $data[$attribute . '-' . $suffix] = $attribute . '-' . $suffix;
                    $attributeToImport[] = $attribute . '-' . $suffix;
                }
            }
        }
        /** @var bool $rowIdExists */
        $rowIdExists = $this->entitiesHelper->rowIdColumnExists($table);
        if ($rowIdExists) {
            $data[$columnIdentifier] = 'p.row_id';
        }

        /** @var Select $select */
        $select = $connection->select()->from($tmpTable, $data);

        if ($rowIdExists) {
            $this->entities->addJoinForContentStaging($select, []);
        }
        /** @var Mysql $query */
        $query = $connection->query($select);

        /** @var array $row */
        while (($row = $query->fetch())) {
            foreach ($attributeToImport as $attribute) {
                if (!isset($row[$attribute])) {
                    continue;
                }

                if (!$row[$attribute]) {
                    continue;
                }

                // Unset the filepath if it was used to work with later verifications
                unset($filePath);

                /** @var array $file */
                $file = $this->akeneoClient->getProductMediaFileApi()->get($row[$attribute]);
                /** @var string $name */
                $name = $this->entitiesHelper->formatMediaName(basename($file['code']));
                /** @var string $filePath */
                $filePath = $this->configHelper->getMediaFullPath($name, $this->configHelper->getFilesMediaDirectory());

                // Don't import the file if it was already imported
                if (!$this->configHelper->mediaFileExists($filePath)) {
                    /** @var ResponseInterface $binary */
                    $binary = $this->akeneoClient->getProductMediaFileApi()->download($row[$attribute]);
                    /** @var string $fileContents */
                    $fileContents = $binary->getBody()->getContents();
                    $this->configHelper->saveMediaFile($filePath, $fileContents);
                }

                // Change the Akeneo file path to Magento file path
                $connection->update($tmpTable, [$attribute => $filePath], ['identifier = ?' => $row['sku']]);
            }
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

        $values[0]['status'] = '_status';

        // Set products status
        /** @var string $statusAttributeId */
        $statusAttributeId = $this->eavAttribute->getIdByCode('catalog_product', 'status');
        /** @var string $identifierColumn */
        $identifierColumn = $this->entitiesHelper->getColumnIdentifier('catalog_product_entity_int');
        /** @var string $productTable */
        $productTable = $this->entitiesHelper->getTable('catalog_product_entity');
        /** @var string[] $pKeyColumn */
        $pKeyColumn = 'a._entity_id';
        /** @var string[] $columnsForStatus */
        $columnsForStatus = ['entity_id' => $pKeyColumn, '_entity_id', '_is_new' => 'a._is_new'];

        /** @var bool $rowIdExists */
        $rowIdExists = $this->entitiesHelper->rowIdColumnExists($productTable);
        if ($rowIdExists) {
            $pKeyColumn = 'p.row_id';
            $columnsForStatus['entity_id'] = $pKeyColumn;
        }

        /* Simple status management */
        /** @var Select $select */
        $select = $connection->select()->from(['a' => $tmpTable], $columnsForStatus);
        if ($rowIdExists) {
            $this->entities->addJoinForContentStaging($select, []);
        }

        $select->joinInner(
            ['b' => $this->entitiesHelper->getTable('catalog_product_entity_int')],
            $pKeyColumn . ' = b.' . $identifierColumn
        )->where('a._is_new = ?', 0)->where('a._status = ?', 1)->where('a._type_id = ?', 'simple')->where('b.attribute_id = ?', $statusAttributeId);

        // Update existing simple status
        /** @var Zend_Db_Statement_Pdo $oldStatus */
        $oldStatus = $connection->query($select);
        while (($row = $oldStatus->fetch())) {
            $valuesToInsert = [
                '_status' => $row['value'],
            ];
            $connection->update($tmpTable, $valuesToInsert, ['_entity_id = ?' => $row['_entity_id']]);
        }

        // Update new simple status
        $connection->update(
            $tmpTable,
            ['_status' => $this->configHelper->getProductActivation()],
            ['_is_new = ?' => 1, '_status = ?' => 1, '_type_id = ?' => 'simple']
        );

        /*  Configurable status management */
        $select = $connection->select()->from(['a' => $tmpTable], $columnsForStatus);
        if ($rowIdExists) {
            $this->entities->addJoinForContentStaging($select, []);
        }

        $select->joinInner(
            ['b' => $this->entitiesHelper->getTable('catalog_product_entity_int')],
            $pKeyColumn . ' = b.' . $identifierColumn
        )->where('a._is_new = ?', 0)->where('a._type_id = ?', 'configurable')->where('b.attribute_id = ?', $statusAttributeId);

        // Update existing configurable status
        /** @var Zend_Db_Statement_Pdo $oldConfigurableStatus */
        $oldConfigurableStatus = $connection->query($select);
        while (($row = $oldConfigurableStatus->fetch())) {
            $valuesToInsert = [
                '_status' => $row['value'],
            ];
            $connection->update($tmpTable, $valuesToInsert, ['_entity_id = ?' => $row['_entity_id']]);
        }

        // Update new configurable status
        $connection->update(
            $tmpTable,
            ['_status' => $this->configHelper->getProductActivation()],
            ['_is_new = ?' => 1, '_type_id = ?' => 'configurable']
        );

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
        if ($this->entitiesHelper->isFamilyGrouped($this->getFamily())) {
            return;
        }

        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());

        /** @var string $productEntityTable */
        $productEntityTable = $this->entitiesHelper->getTable('catalog_product_entity');
        /** @var string $eavAttrOptionTable */
        $eavAttrOptionTable = $this->entitiesHelper->getTable('eav_attribute_option');
        /** @var string $productSuperAttrTable */
        $productSuperAttrTable = $this->entitiesHelper->getTable('catalog_product_super_attribute');
        /** @var string $productSuperAttrLabelTable */
        $productSuperAttrLabelTable = $this->entitiesHelper->getTable('catalog_product_super_attribute_label');
        /** @var string $productRelationTable */
        $productRelationTable = $this->entitiesHelper->getTable('catalog_product_relation');
        /** @var string $productSuperLinkTable */
        $productSuperLinkTable = $this->entitiesHelper->getTable('catalog_product_super_link');

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
        $configurableSelect = $connection->select()->from($tmpTable, ['_entity_id', '_axis', '_children'])->where('_type_id = ?', 'configurable')->where('_axis IS NOT NULL');

        /** @var string $pKeyColumn */
        $pKeyColumn = '_entity_id';

        /** @var bool $rowIdExists */
        $rowIdExists = $this->entitiesHelper->rowIdColumnExists($productEntityTable);
        if ($rowIdExists) {
            $this->entities->addJoinForContentStaging($configurableSelect, ['p.row_id']);
            $pKeyColumn = 'row_id';
        }

        /** @var int $stepSize */
        $stepSize = self::CONFIGURABLE_INSERTION_MAX_SIZE;
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
                if (!is_numeric($id) || !isset($row['_entity_id'])) {
                    continue;
                }

                /** @var bool $hasOptions */
                $hasOptions = (bool)$connection->fetchOne(
                    $connection->select()
                        ->from($eavAttrOptionTable, [new Expr(1)])
                        ->where('attribute_id = ?', $id)
                        ->limit(1)
                );

                if (!$hasOptions) {
                    continue;
                }

                /** @var array $values */
                $values = [
                    'product_id'   => $row[$pKeyColumn],
                    'attribute_id' => $id,
                    'position'     => $position++,
                ];
                $connection->insertOnDuplicate(
                    $productSuperAttrTable,
                    $values,
                    []
                );

                /** @var array $valuesLabels */
                $valuesLabels = [];
                /** @var string $superAttributeId */
                $superAttributeId = $connection->fetchOne(
                    $connection->select()
                        ->from($productSuperAttrTable)
                        ->where('attribute_id = ?', $id)
                        ->where('product_id = ?', $row[$pKeyColumn])
                        ->limit(1)
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

                $connection->insertOnDuplicate($productSuperAttrLabelTable, $valuesLabels, []);

                if (!isset($row['_children'])) {
                    continue;
                }

                /** @var array $children */
                $children = explode(',', $row['_children']);
                /** @var string $child */
                foreach ($children as $child) {
                    /** @var int $childId */
                    $childId = (int)$connection->fetchOne(
                        $connection->select()
                            ->from($productEntityTable, ['entity_id'])
                            ->where('sku = ?', $child)
                            ->limit(1)
                    );

                    if (!$childId) {
                        continue;
                    }

                    $valuesRelations[] = [
                        'parent_id' => $row[$pKeyColumn],
                        'child_id'  => $childId,
                    ];

                    $valuesSuperLink[] = [
                        'product_id' => $childId,
                        'parent_id'  => $row[$pKeyColumn],
                    ];
                }

                if (count($valuesSuperLink) > $stepSize) {
                    $connection->insertOnDuplicate($productRelationTable, $valuesRelations, []);
                    $connection->insertOnDuplicate($productSuperLinkTable, $valuesSuperLink, []);

                    $valuesRelations = [];
                    $valuesSuperLink = [];
                }
            }
        }

        if (count($valuesSuperLink) > 0) {
            $connection->insertOnDuplicate($productRelationTable, $valuesRelations, []);
            $connection->insertOnDuplicate($productSuperLinkTable, $valuesSuperLink, []);
        }
    }

    /**
     * Link simple products to already existant product models
     *
     * @return void
     * @throws Zend_Db_Statement_Exception
     */
    public function linkSimple()
    {
        if ($this->entitiesHelper->isFamilyGrouped($this->getFamily())) {
            return;
        }

        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var string $entityTable */
        $entityTable = $this->entitiesHelper->getTable('catalog_product_entity');
        /** @var string $productRelationTable */
        $productRelationTable = $this->entitiesHelper->getTable('catalog_product_relation');
        /** @var string $productSuperLinkTable */
        $productSuperLinkTable = $this->entitiesHelper->getTable('catalog_product_super_link');

        /** @var Select $select */
        $select = $connection->select()->from($tmpTable, ['_entity_id', 'parent'])->where('parent IS NOT NULL');

        /** @var string $pKeyColumn */
        $pKeyColumn = 'entity_id';
        /** @var bool $rowIdExists */
        $rowIdExists = $this->entitiesHelper->rowIdColumnExists($entityTable);
        if ($rowIdExists) {
            $pKeyColumn = 'row_id';
        }

        /** @var Mysql $query */
        $query = $connection->query($select);

        /** @var array $row */
        while (($row = $query->fetch())) {
            if (!isset($row['parent']) || !isset($row['_entity_id'])) {
                continue;
            }

            /** @var string $productModelEntityId */
            $productModelEntityId = $connection->fetchOne(
                $connection->select()->from($entityTable, $pKeyColumn)->where('sku = ?', $row['parent'])->limit(1)
            );

            // A product model already has been imported, check that everything is in order
            if ($productModelEntityId != false) {
                /** @var string[] $valuesRelations */
                $valuesRelations = [];
                /** @var string[] $valuesSuperLink */
                $valuesSuperLink = [];
                // Check for relations and add them if they don't exist
                /** @var bool $hasRelation */
                $hasRelation = (bool)$connection->fetchOne(
                    $connection->select()->from($productRelationTable, [new Expr(1)])->where(
                        'parent_id = ?',
                        $productModelEntityId
                    )->where('child_id = ?', $row['_entity_id'])->limit(1)
                );

                if (!$hasRelation) {
                    $valuesRelations[] = [
                        'parent_id' => $productModelEntityId,
                        'child_id'  => $row['_entity_id'],
                    ];

                    $connection->insertOnDuplicate($productRelationTable, $valuesRelations, []);
                }

                // Do the same for super links
                /** @var bool $hasSuperLink */
                $hasSuperLink = (bool)$connection->fetchOne(
                    $connection->select()->from($productSuperLinkTable, [new Expr(1)])->where(
                        'parent_id = ?',
                        $productModelEntityId
                    )->where('product_id = ?', $row['_entity_id'])->limit(1)
                );

                if (!$hasSuperLink) {
                    $valuesSuperLink[] = [
                        'product_id' => $row['_entity_id'],
                        'parent_id'  => $productModelEntityId,
                    ];

                    $connection->insertOnDuplicate($productSuperLinkTable, $valuesSuperLink, []);
                }
            }
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
            $websiteAttribute = strtolower($websiteAttribute);
            $attribute = $this->eavConfig->getAttribute('catalog_product', $websiteAttribute);
            if ($attribute->getAttributeId() != null) {
                /** @var string[] $options */
                $options = $attribute->getSource()->getAllOptions();
                /** @var array $websites */
                $websites = $this->storeHelper->getStores('website_code');
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
                                        if (isset($affected[0]['website_id'])) {
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
                    /** @var Select $select */
                    $select = $connection->select()->from(
                        $tmpTable,
                        [
                            'entity_id'          => '_entity_id',
                            'identifier'         => 'identifier',
                            'associated_website' => $websiteAttribute,
                        ]
                    );
                    /** @var Mysql $query */
                    $query = $connection->query($select);
                    /** @var array $row */
                    while (($row = $query->fetch())) {
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
                        /** @var string[] $associatedWebsites */
                        $associatedWebsites = $row['associated_website'];
                        if ($associatedWebsites != null) {
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
                            $this->setAdditionalMessage(__('Warning: The product with Akeneo id %1 has no associated website in the custom attribute.', $row['identifier']));
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
        $selectToDelete = $connection->select()
            ->from($this->entitiesHelper->getTable('catalog_category_product'), [])
            ->joinInner(
                ['c' => $this->entitiesHelper->getTable('akeneo_connector_entities')],
                $this->entitiesHelper->getTable('catalog_category_product') . '.category_id = c.entity_id',
                []
            )
            ->joinInner(
                ['p' => $tmpTable],
                $this->entitiesHelper->getTable('catalog_category_product') . '.product_id = `p`.`_entity_id`',
                [
                    'category_id' => 'c.entity_id',
                    'product_id'  => 'p._entity_id',
                ]
            )
            ->joinInner(
                ['e' => $this->entitiesHelper->getTable('catalog_category_entity')],
                'c.entity_id = e.entity_id',
                []
            )
            ->where('!FIND_IN_SET(`c`.`code`, `p`.`categories`) AND `c`.`import` = "category"');

        $delete = $connection->deleteFromSelect($selectToDelete, $this->entitiesHelper->getTable('catalog_category_product'));
        $connection->query($delete);
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

        /** @var array $values */
        $values = ['product_id' => '_entity_id'];

        /** @var bool $rowIdExists */
        $rowIdExists = $this->entitiesHelper->rowIdColumnExists($productsTable);
        if ($rowIdExists) {
            $values['product_id'] = 'p.row_id';
        }
        /** @var Select $productIds */
        $productIds = $connection->select()->from($tmpTable, $values);

        if ($rowIdExists) {
            $this->entities->addJoinForContentStaging($productIds, []);
        }

        /** @var string[] $associationTypes */
        $associationTypes = $this->configHelper->getAssociationTypes();
        /** @var int $linkType */
        /** @var string[] $associationNames */
        foreach ($associationTypes as $linkType => $associationNames) {
            if (empty($associationNames)) {
                continue;
            }

            /* Remove old link */
            $connection->delete(
                $linkTable,
                ['link_type_id = ?' => $linkType, 'product_id IN (?)' => $productIds]
            );
            /** @var string $associationName */
            foreach ($associationNames as $associationName) {
                if (!empty($associationName) && $connection->tableColumnExists($tmpTable, $associationName)) {
                    $related[$linkType][] = sprintf('`d`.`%s`', $associationName);
                }
            }
        }

        /**
         * @var int      $typeId
         * @var string[] $columns
         */
        foreach ($related as $typeId => $columns) {
            /** @var mixed[] $columsToSelect */
            $columsToSelect = [
                'product_id'        => 'd._entity_id',
                'linked_product_id' => 'c.entity_id',
                'link_type_id'      => new Expr($typeId),
            ];
            if ($rowIdExists) {
                $columsToSelect = [
                    'product_id'        => 'p.row_id',
                    'linked_product_id' => 'c.entity_id',
                    'link_type_id'      => new Expr($typeId),
                ];
            }
            /** @var string $concat */
            $concat = sprintf('CONCAT_WS(",", %s)', implode(', ', $columns));
            /** @var Select $select */
            $select = $connection->select()->from(['c' => $entitiesTable], [])->joinInner(
                ['d' => $tmpTable],
                sprintf('FIND_IN_SET(`c`.`code`, %s) AND `c`.`import` = "%s"', $concat, $this->getCode()),
                $columsToSelect
            );

            if ($rowIdExists) {
                $this->entities->addJoinForContentStaging($select, []);
            } else {
                $select->joinInner(['e' => $productsTable], sprintf('c.entity_id = e.%s', $columnIdentifier), []);
            }

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
     * Update or set grouped products relations
     *
     * @return void
     * @throws \Zend_Db_Exception
     */
    public function setGrouped()
    {
        /** @var string $edition */
        $edition = $this->configHelper->getEdition();
        // Is family is not grouped or edition not Serenity, skip
        if (($edition != Edition::SERENITY && $edition != Edition::GREATER_OR_FIVE) || !$this->entitiesHelper->isFamilyGrouped($this->getFamily())) {
            return;
        }
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var string $entitiesTable */
        $entitiesTable = $this->entitiesHelper->getTable('akeneo_connector_entities');
        /** @var string $productsEntityTable */
        $productsEntityTable = $this->entitiesHelper->getTable(self::CATALOG_PRODUCT_ENTITY_TABLE_NAME);
        /** @var string $productsRelationTable */
        $productsRelationTable = $this->entitiesHelper->getTable('catalog_product_relation');
        /** @var string $productsLinkTable */
        $productsLinkTable = $this->entitiesHelper->getTable('catalog_product_link');
        /** @var string $productsLinkAttributeTable */
        $productsLinkAttributeTable = $this->entitiesHelper->getTable('catalog_product_link_attribute');
        /** @var string $productsLinkAttributeDecimalTable */
        $productsLinkAttributeDecimalTable = $this->entitiesHelper->getTable('catalog_product_link_attribute_decimal');
        /** @var string $productsLinkAttributeIntTable */
        $productsLinkAttributeIntTable = $this->entitiesHelper->getTable('catalog_product_link_attribute_int');
        /** @var string $productsLinkTypeTable */
        $productsLinkTypeTable = $this->entitiesHelper->getTable('catalog_product_link_type');
        /** @var string $columnIdentifier */
        $columnIdentifier = $this->entitiesHelper->getColumnIdentifier($productsEntityTable);
        // Product link attribute with code 'super'
        /** @var Select $selectSuperId */
        $selectSuperLinkType = $connection->select()->from($productsLinkTypeTable)->where('code = ?', 'super');
        /** @var mixed[] $attributeSuperLinkType */
        $attributeSuperLinkType = $connection->query($selectSuperLinkType)->fetch();
        /** @var string $selectProductLinkAttributeQty */
        $selectProductLinkAttributeQty = $connection->select()->from($productsLinkAttributeTable)->where(
            'product_link_attribute_code = ?',
            'qty'
        )->where(
            'link_type_id = ?',
            $attributeSuperLinkType['link_type_id']
        );
        /** @var mixed[] $productLinkAttributeQty */
        $productLinkAttributeQty = $connection->query($selectProductLinkAttributeQty)->fetch();
        /** @var string $selectProductLinkAttributePosition */
        $selectProductLinkAttributeQty = $connection->select()->from($productsLinkAttributeTable)->where(
            'product_link_attribute_code = ?',
            'position'
        )->where(
            'link_type_id = ?',
            $attributeSuperLinkType['link_type_id']
        );
        /** @var mixed[] $productLinkAttributePosition */
        $productLinkAttributePosition = $connection->query($selectProductLinkAttributeQty)->fetch();

        /** @var mixed[] $associationsCurrentFamily */
        $associationsCurrentFamily = $this->configHelper->getGroupedAssociationsForFamily($this->getFamily());

        /** @var string $entityIdFieldName */
        $entityIdFieldName = '_entity_id';
        /** @var bool $rowIdExists */
        $rowIdExists = $this->entitiesHelper->rowIdColumnExists($productsEntityTable);
        if ($rowIdExists) {
            $entityIdFieldName = 'p.row_id';
        }

        /** @var string[] $associationSelect */
        $associationSelect = [
            'identifier' => 'identifier',
            'entity_id'  => $entityIdFieldName,
        ];

        /**
         * @var int      $key
         * @var string[] $familyAssociation
         */
        foreach ($associationsCurrentFamily as $key => $familyAssociation) {
            /** @var string $associationColumnName */
            $associationColumnName = $familyAssociation['akeneo_quantity_association'] . '-products';
            /** @var string $associationColumnNameModels */
            $associationColumnNameModels = $familyAssociation['akeneo_quantity_association'] . '-product_models';
            if ($connection->tableColumnExists($tmpTable, $associationColumnName)) {
                $associationSelect[$familyAssociation['akeneo_quantity_association']] = $associationColumnName;
                if ($connection->tableColumnExists($tmpTable, $associationColumnNameModels)) {
                    $associationSelect[$familyAssociation['akeneo_quantity_association'] . '-models'] = $associationColumnNameModels;
                }
            } else {
                if ($familyAssociation['akeneo_quantity_association'] === "") {
                    $this->setAdditionalMessage(
                        __(
                            'The family %1 was mapped to an empty association, please check your configuration',
                            $this->getFamily()
                        )
                    );
                } else {
                    $this->setAdditionalMessage(
                        __('The grouped product association %1 has not been imported', $familyAssociation['akeneo_quantity_association'])
                    );
                }
                unset($associationsCurrentFamily[$key]);
            }
        }

        /** @var \Magento\Framework\DB\Select $select */
        $select = $connection->select()->from(
            $tmpTable,
            $associationSelect
        );

        if ($rowIdExists) {
            $this->entities->addJoinForContentStaging($select, []);
        }

        $query  = $connection->query($select);

        /** @var bool $badAssociationFlag */
        $badAssociationFlag = false;
        /** @var array $row */
        while (($row = $query->fetch())) {
            // Delete links for the product in catalog_product_link table
            $connection->delete(
                $productsLinkTable,
                ['product_id = ?' => $row['entity_id'], 'link_type_id = ?' => $attributeSuperLinkType['link_type_id']]
            );

            // Verify if the product exist in catalog_product_entity
            if (!$this->productExistInMagento($row['identifier'])) {
                $this->setAdditionalMessage(
                    __(
                        'The grouped product with identifier %1 does not exist in magento, links will not be imported',
                        $row['identifier']
                    )
                );
                continue;
            }

            // Initialize position
            /** @var int $position */
            $position = 0;

            /** @var string[] $familyAssociation */
            foreach ($associationsCurrentFamily as $familyAssociation) {
                if (isset($row[$familyAssociation['akeneo_quantity_association'] . '-models'])) {
                    /** @var string[] $skuModels */
                    $skuModels = [];
                    /** @var string[] $associationData */
                    $associationData = explode(
                        ',',
                        $row[$familyAssociation['akeneo_quantity_association'] . '-models']
                    );
                    /** @var string $association */
                    foreach ($associationData as $association) {
                        /** @var string[] $modelAssociation */
                        $modelAssociation = explode(';', $association);
                        $skuModels[] = $modelAssociation[0];
                    }
                    $skuModels = implode(', ', $skuModels);

                    $this->setAdditionalMessage(
                        __(
                            'The grouped product %1 is linked to the product(s) %2 but they are not simple product(s). The association has been skipped.',
                            $row['identifier'],
                            $skuModels
                        )
                    );
                }

                $affectedProductIds[] = $row['entity_id'];
                if ($row[$familyAssociation['akeneo_quantity_association']] == null) {
                    $this->setAdditionalMessage(
                        __(
                            'The grouped product with identifier %1 does not have values for its grouped association %2',
                            $row['identifier'],
                            $familyAssociation['akeneo_quantity_association']
                        )
                    );

                    continue;
                }
                /** @var string[] $associationProductInfo */
                $associationProductInfo = $this->formatGroupedAssociationData(
                    $row[$familyAssociation['akeneo_quantity_association']],
                    $row['identifier']
                );

                // Check if the assoication was correctly formated
                if ($associationProductInfo === false) {
                    if ($badAssociationFlag === false) {
                        $this->setAdditionalMessage(
                            __(
                                'The association %1 is not a quantified association, please check your configuration',
                                $familyAssociation['akeneo_quantity_association']
                            )
                        );

                        $badAssociationFlag = true;
                    }

                    continue;
                }

                /** @var string[] $productInfo */
                foreach ($associationProductInfo as $productInfo) {

                    // Verify if the product exist in catalog_product_entity
                    if (!$this->productExistInMagento($productInfo['identifier'])) {
                        $this->setAdditionalMessage(
                            __(
                                'The grouped product %1 is linked to the product %2 but it does not exist in Magento. The association has been skipped.',
                                $row['identifier'],
                                $productInfo['identifier']
                            )
                        );
                        continue;
                    }

                    /** @var string[] $linkedProductEntityId */
                    $linkedProductEntityId = $connection->query(
                        $connection->select()->from($entitiesTable, 'entity_id')->where(
                            'code = ?',
                            $productInfo['identifier']
                        )->where(
                            'import = ?',
                            'product'
                        )
                    )->fetch();

                    /** @var string[] $linkedProductType */
                    $linkedProductType = $connection->query(
                        $connection->select()->from($productsEntityTable, 'type_id')->where(
                            'entity_id = ?',
                            $linkedProductEntityId['entity_id']
                        )
                    )->fetch();

                    if ($linkedProductType['type_id'] != 'simple') {
                        $this->setAdditionalMessage(
                            __(
                                'The grouped product %1 is linked to the product %2 but it is not a simple product. The association has been skipped.',
                                $row['identifier'],
                                $productInfo['identifier']
                            )
                        );
                        continue;
                    }

                    // Start the inserts in the different tables

                    // Insert in catalog_product_link
                    /** @var string[] $linkedProduct */
                    $linkedProduct = [
                        'product_id'        => $row['entity_id'],
                        'linked_product_id' => $linkedProductEntityId['entity_id'],
                        'link_type_id'      => $attributeSuperLinkType['link_type_id'],
                    ];

                    $connection->insertOnDuplicate(
                        $productsLinkTable,
                        $linkedProduct,
                        array_keys($linkedProduct)
                    );

                    // Get the id of the created link
                    /** @var string[] $linkId */
                    $linkId = $connection->query(
                        $connection->select()->from($productsLinkTable, 'link_id')->where(
                            'product_id = ?',
                            $row['entity_id']
                        )->where(
                            'linked_product_id = ?',
                            $linkedProductEntityId['entity_id']
                        )->where(
                            'link_type_id = ?',
                            $attributeSuperLinkType['link_type_id']
                        )
                    )->fetch();

                    // Insert in catalog_product_link_attribute_int
                    $linkedProduct = [
                        'product_link_attribute_id' => $productLinkAttributePosition['product_link_attribute_id'],
                        'link_id'                   => $linkId['link_id'],
                        'value'                     => $position,
                    ];

                    $connection->insertOnDuplicate(
                        $productsLinkAttributeIntTable,
                        $linkedProduct,
                        array_keys($linkedProduct)
                    );

                    // Increment position
                    $position = $position + 1;

                    // Insert in catalog_product_link_attribute_decimal
                    $linkedProduct = [
                        'product_link_attribute_id' => $productLinkAttributeQty['product_link_attribute_id'],
                        'link_id'                   => $linkId['link_id'],
                        'value'                     => $productInfo['quantity'],
                    ];

                    $connection->insertOnDuplicate(
                        $productsLinkAttributeDecimalTable,
                        $linkedProduct,
                        array_keys($linkedProduct)
                    );
                }
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
                /** @var Select $select */
                $select = $connection->select()->from(
                    $tmpTable,
                    [
                        'entity_id' => '_entity_id',
                        'url_key'   => 'url_key-' . $local,
                        'store_id'  => new Expr($store['store_id']),
                        'visibility' => '_visibility',
                    ]
                );

                /** @var Mysql $query */
                $query = $connection->query($select);

                /** @var array $row */
                while (($row = $query->fetch())) {
                    /** @var BaseProductModel $product */
                    $product = $this->product;
                    $product->setData($row);

                    if (isset($store['website_id'])) {
                        /** @var Select $selectIsInWebsite */
                        $selectIsInWebsite = $connection->select()->from(
                            $this->entitiesHelper->getTable('catalog_product_website'),
                            [
                                'product_id' => 'product_id',
                            ]
                        )->where('website_id = ?', $store['website_id'])->where(
                            'product_id = ?',
                            $product->getEntityId()
                        );
                        /** @var Mysql $queryIsInWebsite */
                        $queryIsInWebsite = $connection->query($selectIsInWebsite);
                        /** @var string[] $isInWebsite */
                        $isInWebsite = $queryIsInWebsite->fetchAll();

                        if (empty($isInWebsite)) {
                            continue;
                        }
                    }

                    /** @var string $urlPath */
                    $urlPath = $this->productUrlPathGenerator->getUrlPath($product);

                    if (!$urlPath || $row['visibility'] < 2) {
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
                            try {
                                $connection->update(
                                    $this->entitiesHelper->getTable('url_rewrite'),
                                    ['request_path' => $requestPath, 'metadata' => $metadata],
                                    ['url_rewrite_id = ?' => $rewriteId]
                                );
                            } catch (\Exception $e) {
                                $this->setAdditionalMessage(
                                    __(
                                        sprintf(
                                            'Tried to update url_rewrite_id %s : request path (%s) already exists for the store_id.',
                                            $rewriteId,
                                            $requestPath
                                        )
                                    )
                                );
                            }
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
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Zend_Db_Statement_Exception
     */
    public function importMedia()
    {
        if (!$this->configHelper->isMediaImportEnabled()) {
            $this->setStatus(true);
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

        $gallery = array_unique($gallery);

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
            if (!$connection->tableColumnExists($tmpTable, strtolower($image))) {
                $this->setMessage(__('Info: No value found in the current batch for the attribute %1', $image));
                continue;
            }
            $data[$image] = strtolower($image);
        }

        /** @var bool $rowIdExists */
        $rowIdExists = $this->entitiesHelper->rowIdColumnExists($table);
        if ($rowIdExists) {
            $data[$columnIdentifier] = 'p.row_id';
        }

        /** @var Select $select */
        $select = $connection->select()->from($tmpTable, $data);

        if ($rowIdExists) {
            $this->entities->addJoinForContentStaging($select, []);
        }

        /** @var Mysql $query */
        $query = $connection->query($select);

        /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $galleryAttribute */
        $galleryAttribute = $this->configHelper->getAttribute(BaseProductModel::ENTITY, 'media_gallery');
        /** @var string $galleryTable */
        $galleryTable = $this->entitiesHelper->getTable('catalog_product_entity_media_gallery');
        /** @var string $galleryEntityTable */
        $galleryEntityTable = $this->entitiesHelper->getTable('catalog_product_entity_media_gallery_value_to_entity');
        /** @var string $galleryValueTable */
        $galleryValueTable = $this->entitiesHelper->getTable('catalog_product_entity_media_gallery_value');
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
                /** @var string $filePath */
                $filePath = $this->configHelper->getMediaFullPath($name);
                /** @var bool|string[] $databaseRecords */
                $databaseRecords = false;

                if (!$this->configHelper->mediaFileExists($filePath)) {
                    /** @var ResponseInterface $binary */
                    $binary = $this->akeneoClient->getProductMediaFileApi()->download($row[$image]);
                    /** @var string $imageContent */
                    $imageContent = $binary->getBody()->getContents();
                    $this->configHelper->saveMediaFile($filePath, $imageContent);
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

                // Get potential record_id from gallery value table
                /** @var Select $select */
                $select          = $connection->select()->from($galleryValueTable)->where('value_id = ?', $valueId)->where(
                    'store_id = ?',
                    0
                )->where($columnIdentifier . ' = ?', $row[$columnIdentifier]);
                $databaseRecords = $connection->fetchAll($select);
                /** @var int $recordId */
                $recordId = 0;
                if (!empty($databaseRecords)) {
                    foreach ($databaseRecords as $databaseRecord) {
                        if (isset($databaseRecord['record_id']) && $databaseRecord['record_id'] > $recordId) {
                            $recordId = $databaseRecord['record_id'];
                        }
                    }
                }

                /** @var array $data */
                $data = [
                    'value_id'        => $valueId,
                    'store_id'        => 0,
                    $columnIdentifier => $row[$columnIdentifier],
                    'label'           => '',
                    'position'        => 0,
                    'disabled'        => 0,
                ];

                if ($recordId != 0) {
                    $data['record_id'] = $recordId;
                }
                $connection->insertOnDuplicate($galleryValueTable, $data, array_keys($data));

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

            /** @var Select $cleaner */
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
        $this->productModelHelper->dropTable();
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
     * @param bool $isProductModel
     *
     * @return mixed[]
     */
    protected function getFilters($family = null, $isProductModel = false)
    {
        /** @var mixed[] $filters */
        $filters = $this->productFilters->getFilters($family, $isProductModel);
        if (array_key_exists('error', $filters)) {
            $this->setMessage($filters['error']);
            $this->stop(true);
        }

        $this->filters = $filters;

        return $this->filters;
    }

    /**
     * Retrieve product model filters
     *
     * @return mixed[]
     */
    protected function getProductModelFilters($family = null)
    {
        /** @var mixed[] $filters */
        $filters = $this->getFilters($family, true);
        /** bool|string[] $modelCompletenessFilter */
        $modelCompletenessFilter = false;
        /** @var string $mode */
        $mode = $this->configHelper->getFilterMode();
        if ($mode == Mode::STANDARD) {
            $modelCompletenessFilter = $this->getModelCompletenessFilter();
        }

        /**
         * @var string $key
         * @var string[Ø] $filter
         */
        foreach ($filters as $key => $filter) {
            if (isset($filter['search'])) {
                if (isset($filter['search']['enabled'])) {
                    unset($filters[$key]['search']['enabled']);
                }
                if (isset($filter['search']['group'])) {
                    unset($filters[$key]['search']['group']);
                }
                if (isset($filter['search']['parent'])) {
                    unset($filters[$key]['search']['parent']);
                }
                if (isset($filter['search']['completeness'])) {
                    unset($filters[$key]['search']['completeness']);
                }
                if ($modelCompletenessFilter) {
                    $filters[$key]['search']['completeness'] = $modelCompletenessFilter;
                }
            }
        }

        return $filters;
    }

    /**
     * Get product models completeness filter
     *
     * @return array|bool
     */
    protected function getModelCompletenessFilter()
    {
        /** @var string $scope */
        $scope = $this->configHelper->getAdminDefaultChannel();
        /** @var string $completenessType */
        $completenessType = $this->configHelper->getModelCompletenessTypeFilter();
        /** @var mixed $locales */
        $locales = $this->configHelper->getModelCompletenessLocalesFilter();
        /** @var string[] $locales */
        $locales = explode(',', $locales);
        if ($completenessType == ModelCompleteness::NO_CONDITION) {
            return false;
        }
        /** @var string[] $filter */
        $filter[] = ['operator' => $completenessType, 'scope' => $scope, 'locales' => $locales];

        return $filter;
    }

    /**
     * Get the families to imported based on the config
     *
     * @return array
     */
    public function getFamiliesToImport()
    {
        if (!$this->akeneoClient) {
            $this->akeneoClient = $this->getAkeneoClient();
            if (!$this->akeneoClient) {
                return [];
            }
        }
        /** @var string[] $families */
        $families = [];
        /** @var string|int $paginationSize */
        $paginationSize = $this->configHelper->getPaginationSize();
        /** @var string[] $apiFamilies */
        $apiFamilies = $this->akeneoClient->getFamilyApi()->all($paginationSize);

        /** @var mixed[] $family */
        foreach ($apiFamilies as $family) {
            if (!isset($family['code'])) {
                continue;
            }
            $families[] = $family['code'];
        }

        // If we are in serenity mode, place the mapped grouped families to the end of the imports
        /** @var string $edition */
        $edition = $this->configHelper->getEdition();
        if ($edition === Edition::SERENITY || $edition === Edition::GREATER_OR_FIVE) {
            /** @var string[] $groupedFamiliesToImport */
            $groupedFamiliesToImport = $this->configHelper->getGroupedFamiliesToImport();
            /**
             * @var int    $key
             * @var string $family
             */
            foreach ($families as $key => $family) {
                if (in_array($family, $groupedFamiliesToImport)) {
                    unset($families[$key]);
                    $families[] = $family;
                }
            }
        }

        /** @var string $mode */
        $mode = $this->configHelper->getFilterMode();
        if ($mode == Mode::ADVANCED) {
            /** @var string[] $filters */
            $advancedFilters = $this->configHelper->getAdvancedFilters();
            if (isset($advancedFilters['search']['family'])) {
                foreach ($advancedFilters['search']['family'] as $key => $familyFilter) {
                    if (isset($familyFilter['operator']) && $familyFilter['operator'] == 'NOT IN') {
                        foreach ($familyFilter['value'] as $familyToRemove) {
                            if (($familyKey = array_search($familyToRemove, $families)) !== false) {
                                unset($families[$familyKey]);
                            }
                        }
                    }
                }
            }
        }
        if ($mode == Mode::STANDARD) {
            /** @var mixed $filter */
            $familiesFilter = $this->configHelper->getFamiliesFilter();
            if ($familiesFilter) {
                $familiesFilter = explode(',', $familiesFilter);
                foreach ($familiesFilter as $familyFilter) {
                    if (($key = array_search($familyFilter, $families)) !== false) {
                        unset($families[$key]);
                    }
                }
            }
        }

        $families = array_values($families);

        return $families;
    }

    /**
     * Set current family
     *
     * @param string $family
     *
     * @return Import
     */
    public function setFamily($family)
    {
        $this->family = $family;

        return $this;
    }

    /**
     * Get current family
     *
     * @return string
     */
    public function getFamily()
    {
        return $this->family;
    }

    /**
     * Format grouped association string into an array
     *
     * @param string $productAssociationData
     *
     * @return void
     */
    public function formatGroupedAssociationData($productAssociationData, $productIdentifier)
    {
        /** @var string[] $productAssociations */
        $productAssociations = explode(',', $productAssociationData);
        /** @var string[] $formatedAssociations */
        $formatedAssociations = [];
        /**
         * @var int    $key
         * @var string $association
         */
        foreach ($productAssociations as $key => $association) {
            /** @var string[] $associationData */
            $associationData = explode(';', $association);
            if (!isset($associationData[1])) {
                return false;
            }
            if (is_float($associationData[1])) {
                $this->setAdditionalMessage(
                    __('The product %1 has a decimal value in its association, skipped', $productIdentifier)
                );

                continue;
            }
            $formatedAssociations[$key]['identifier'] = $associationData[0];
            $formatedAssociations[$key]['quantity'] = $associationData[1];
        }

        return $formatedAssociations;
    }

    /**
     * Description productExistInMagento function
     *
     * @param string $sku
     *
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function productExistInMagento(string $sku)
    {
        /** @var string $productsEntityTable */
        $productsEntityTable = $this->entitiesHelper->getTable(self::CATALOG_PRODUCT_ENTITY_TABLE_NAME);
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var Select $productExistenceSelect */
        $productExistenceSelect = $connection->select()->from($productsEntityTable, 'sku')->where('sku = ?', $sku);
        /** @var Mysql $query */
        $query = $connection->query($productExistenceSelect);
        /** @var mixed[] $magentoProduct */
        $magentoProduct = $query->fetch();

        if (is_array($magentoProduct) && count($magentoProduct) > 0) {
            return true;
        }

        return false;
    }
}
