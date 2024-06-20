<?php

declare(strict_types=1);

namespace Akeneo\Connector\Job;

use Akeneo\Connector\Api\Data\AttributeTypeInterface;
use Akeneo\Connector\Helper\AttributeFilters;
use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\Import\Attribute as AttributeHelper;
use Akeneo\Connector\Helper\Import\Entities as EntitiesHelper;
use Akeneo\Connector\Helper\Import\Option as OptionHelper;
use Akeneo\Connector\Helper\Output as OutputHelper;
use Akeneo\Connector\Helper\Store as StoreHelper;
use Akeneo\Connector\Logger\Handler\OptionHandler;
use Akeneo\Connector\Logger\OptionLogger;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Indexer\Model\IndexerFactory;
use Magento\Swatches\Model\Swatch;
use Zend_Db_Expr as Expr;
use Zend_Db_Statement_Interface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Option extends Import
{
    /**
     * This variable contains a string value
     *
     * @var string $code
     */
    protected $code = 'option';
    /**
     * This variable contains a string value
     *
     * @var string $name
     */
    protected $name = 'Option';
    /**
     * This variable contains a AkeneoPimClientInterface
     *
     * @var AkeneoPimClientInterface $akeneoClient
     */
    protected $akeneoClient;
    /**
     * This variable contains an OptionHelper
     *
     * @var OptionHelper $optionHelper
     */
    protected $optionHelper;
    /**
     * This variable contains a Config
     *
     * @var Config $eavConfig
     */
    protected $eavConfig;
    /**
     * This variable contains an AttributeHelper
     *
     * @var AttributeHelper $attributeHelper
     */
    protected $attributeHelper;
    /**
     * This variable contains an AttributeFilters
     *
     * @var AttributeFilters $attributeFilters
     */
    protected $attributeFilters;
    /**
     * This variable contains a StoreHelper
     *
     * @var StoreHelper $storeHelper
     */
    protected $storeHelper;
    /**
     * This variable contains an EavSetup
     *
     * @var EavSetup $eavSetup
     */
    protected $eavSetup;
    /**
     * This variable contains a logger
     *
     * @var OptionLogger $logger
     */
    protected $logger;
    /**
     * This variable contains a handler
     *
     * @var OptionHandler $handler
     */
    protected $handler;
    /**
     * This variable contains a TypeListInterface
     *
     * @var TypeListInterface $cacheTypeList
     */
    protected $cacheTypeList;
    /**
     * This variable contains attributes from an API call
     *
     * @var PageInterface $attributes
     */
    protected $attributes;
    /**
     * This variable contains a IndexerInterface
     *
     * @var IndexerFactory $indexFactory
     */
    protected $indexFactory;

    /**
     * Option constructor
     *
     * @param OptionLogger      $logger
     * @param OptionHandler     $handler
     * @param OutputHelper      $outputHelper
     * @param ManagerInterface  $eventManager
     * @param Authenticator     $authenticator
     * @param EntitiesHelper    $entitiesHelper
     * @param OptionHelper      $optionHelper
     * @param ConfigHelper      $configHelper
     * @param Config            $eavConfig
     * @param AttributeHelper   $attributeHelper
     * @param AttributeFilters  $attributeFilters
     * @param TypeListInterface $cacheTypeList
     * @param StoreHelper       $storeHelper
     * @param EavSetup          $eavSetup
     * @param IndexerFactory    $indexFactory
     * @param array             $data
     *
     */
    public function __construct(
        OptionLogger $logger,
        OptionHandler $handler,
        OutputHelper $outputHelper,
        ManagerInterface $eventManager,
        Authenticator $authenticator,
        EntitiesHelper $entitiesHelper,
        OptionHelper $optionHelper,
        ConfigHelper $configHelper,
        Config $eavConfig,
        AttributeHelper $attributeHelper,
        AttributeFilters $attributeFilters,
        TypeListInterface $cacheTypeList,
        StoreHelper $storeHelper,
        EavSetup $eavSetup,
        IndexerFactory $indexFactory,
        array $data = []
    ) {
        parent::__construct($outputHelper, $eventManager, $authenticator, $entitiesHelper, $configHelper, $data);

        $this->logger           = $logger;
        $this->handler          = $handler;
        $this->optionHelper     = $optionHelper;
        $this->eavConfig        = $eavConfig;
        $this->attributeHelper  = $attributeHelper;
        $this->attributeFilters = $attributeFilters;
        $this->cacheTypeList    = $cacheTypeList;
        $this->storeHelper      = $storeHelper;
        $this->eavSetup         = $eavSetup;
        $this->indexFactory     = $indexFactory;
    }

    /**
     * Create temporary table
     *
     * @return void
     */
    public function createTable()
    {
        if ($this->configHelper->isAdvancedLogActivated()) {
            $this->jobExecutor->setAdditionalMessage(
                __('Path to log file : %1', $this->handler->getFilename()),
                $this->logger
            );
            $this->logger->debug(__('Import identifier : %1', $this->jobExecutor->getIdentifier()));
        }
        /** @var PageInterface $attributes */
        $attributes = $this->getAllAttributes(true);
        /** @var bool $hasOptions */
        $hasOptions = false;
        /** @var array $attribute */
        foreach ($attributes as $attribute) {
            if ($attribute['type'] === AttributeTypeInterface::PIM_CATALOG_MULTISELECT || $attribute['type'] === AttributeTypeInterface::PIM_CATALOG_SIMPLESELECT) {
                if (!$this->akeneoClient) {
                    $this->akeneoClient = $this->jobExecutor->getAkeneoClient();
                }
                /** @var PageInterface $options */
                $options = $this->akeneoClient->getAttributeOptionApi()->listPerPage($attribute['code']);
                if (empty($options->getItems())) {
                    continue;
                }

                $hasOptions = true;

                break;
            }
        }
        if ($hasOptions === false) {
            $this->jobExecutor->setMessage(__('No options found'), $this->logger);
            $this->jobExecutor->afterRun();

            return;
        }
        /** @var array $option */
        $option = $options->getItems();
        if (empty($option)) {
            $this->jobExecutor->setMessage(__('No results from Akeneo'), $this->logger);
            $this->jobExecutor->afterRun(true);

            return;
        }
        $option = reset($option);
        $this->entitiesHelper->createTmpTableFromApi($option, $this->jobExecutor->getCurrentJob()->getCode());
    }

    /**
     * Insert data into temporary table
     *
     * @return void
     */
    public function insertData()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());
        /** @var string|int $paginationSize */
        $paginationSize = $this->configHelper->getPaginationSize();
        /** @var PageInterface $attributes */
        $attributes = $this->getAllAttributes();
        /** @var int $lines */
        $lines = 0;
        /** @var array $attribute */
        foreach ($attributes as $attribute) {
            if ($attribute['type'] === AttributeTypeInterface::PIM_CATALOG_MULTISELECT || $attribute['type'] === AttributeTypeInterface::PIM_CATALOG_SIMPLESELECT) {
                $lines += $this->processAttributeOption($attribute['code'], $paginationSize);
            }
        }
        $this->jobExecutor->setMessage(
            __('%1 line(s) found', $lines),
            $this->logger
        );

        if ($this->configHelper->isAdvancedLogActivated()) {
            $this->logImportedEntities($this->logger);
        }

        /* Remove option without an admin store label */
        if (!$this->configHelper->getOptionCodeAsAdminLabel()) {
            /** @var string $localeCode */
            $localeCode = $this->configHelper->getDefaultLocale();
            /** @var Select $select */
            $select = $connection->select()->from(
                $tmpTable,
                [
                    'label'     => 'labels-' . $localeCode,
                    'code'      => 'code',
                    'attribute' => 'attribute',
                ]
            )->where('`labels-' . $localeCode . '` IS NULL');
            /** @var Zend_Db_Statement_Interface $query */
            $query = $connection->query($select);
            /** @var array $row */
            while (($row = $query->fetch())) {
                if (!isset($row['label']) || $row['label'] === null) {
                    $connection->delete($tmpTable, ['code = ?' => $row['code'], 'attribute = ?' => $row['attribute']]);
                    $this->jobExecutor->setAdditionalMessage(
                        __(
                            'The option %1 from attribute %2 was not imported because it did not have a translation in admin store language : %3',
                            $row['code'],
                            $row['attribute'],
                            $localeCode
                        ),
                        $this->logger
                    );
                }
            }
        }
    }

    /**
     * Map select and multiselect options from configuration
     *
     * @return void
     */
    public function mapOptions(): void
    {
        // Get attributes mapped from connector configiration
        $attributeMapping = $this->configHelper->getAttributeMapping();
        $connection = $this->entitiesHelper->getConnection();
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());
        $eavAttributeTable = $this->entitiesHelper->getTable('eav_attribute');
        $productEntityTypeId = $this->eavConfig->getEntityType(ProductAttributeInterface::ENTITY_TYPE_CODE)->getEntityTypeId();
        $selectTypes = [
            AttributeTypeInterface::PIM_CATALOG_MULTISELECT,
            AttributeTypeInterface::PIM_CATALOG_SIMPLESELECT,
        ];

        foreach ($attributeMapping as $mapping) {
            $magentoAttribute = $mapping['magento_attribute'];
            $akeneoAttribute = $mapping['akeneo_attribute'];

            try {
                $akeneoAttributeData = $this->akeneoClient->getAttributeApi()->get($akeneoAttribute);
            } catch (Exception $e) {
                $this->jobExecutor->displayInfo($e->getMessage());
                continue;
            }

            // Does the Akeneo attribute an attribute that contains options
            if (isset($akeneoAttributeData['type']) && in_array($akeneoAttributeData['type'], $selectTypes)) {
                $magentoEavAttribute = $connection->fetchRow(
                    $connection->select()
                        ->from($eavAttributeTable, ['attribute_code', 'source_model', 'is_user_defined'])
                        ->where('attribute_code = ?', $magentoAttribute)
                        ->where(AttributeInterface::ENTITY_TYPE_ID . ' = ?', $productEntityTypeId)
                );

                // Does the Magento attribute an existing attribute without a specific source model
                if (isset($magentoEavAttribute['attribute_code']) && $magentoEavAttribute['is_user_defined'] == 1 && ($magentoEavAttribute['source_model'] === null || $magentoEavAttribute['source_model'] === Table::class)) {
                    // If needed, delete all options currently imported from the Magento mapped attribute to prevent duplicates
                    $connection->delete($tmpTable, ['attribute = ?' => $magentoAttribute]);

                    $options = $connection->select()->from($tmpTable)->where('attribute = ?', $akeneoAttribute);
                    $query = $connection->query($options);

                    try {
                        $allOptions = $query->fetchAll();
                    } catch (Zend_Db_Statement_Exception $e) {
                        $this->jobExecutor->displayInfo($e->getMessage());
                        continue;
                    }

                    array_walk($allOptions, static function(&$value, $key, $magentoAttr) {
                        $value['attribute'] = $magentoAttr;
                    }, $magentoAttribute);

                    $connection->insertMultiple($tmpTable, $allOptions);
                }
            }
        }
    }

    /**
     * Check already imported entities are still in Adobe Commerce/Magento
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
        $entityTable = $this->entitiesHelper->getTable('eav_attribute_option');
        /** @var Select $selectExistingEntities */
        $selectExistingEntities = $connection->select()->from($entityTable, 'option_id');
        /** @var string[] $existingEntities */
        $existingEntities = array_column($connection->query($selectExistingEntities)->fetchAll(), 'option_id');

        $connection->delete(
            $akeneoConnectorTable,
            ['import = ?' => 'option', 'entity_id NOT IN (?)' => $existingEntities]
        );
    }

    /**
     * Match code with entity
     *
     * @return void
     */
    public function matchEntities()
    {
        $this->optionHelper->matchEntity(
            'code',
            'eav_attribute_option',
            'option_id',
            $this->jobExecutor->getCurrentJob()->getCode(),
            'attribute'
        );
        if ($this->configHelper->isAdvancedLogActivated()) {
            $this->logImportedEntities($this->logger, true);
        }
    }

    /**
     * Insert Option
     *
     * @return void
     */
    public function insertOptions()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());
        /** @var array $columns */
        $columns = [
            'option_id'  => 'a._entity_id',
            'sort_order' => new Expr('"0"'),
        ];
        if ($connection->tableColumnExists($tmpTable, 'sort_order')) {
            $columns['sort_order'] = 'a.sort_order';
        }
        /** @var Select $options */
        $options = $connection->select()->from(['a' => $tmpTable], $columns)->joinInner(
            ['b' => $this->entitiesHelper->getTable('akeneo_connector_entities')],
            'a.attribute = b.code AND b.import = "attribute"',
            [
                'attribute_id' => 'b.entity_id',
            ]
        );
        $connection->query(
            $connection->insertFromSelect(
                $options,
                $this->entitiesHelper->getTable('eav_attribute_option'),
                ['option_id', 'sort_order', 'attribute_id'],
                1
            )
        );
    }

    /**
     * Insert Values
     *
     * @return void
     */
    public function insertValues()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());
        /** @var array $stores */
        $stores = $this->storeHelper->getStores('lang');
        /**
         * @var string $local
         * @var array  $data
         */
        foreach ($stores as $local => $data) {
            if (!$connection->tableColumnExists($tmpTable, 'labels-' . $local)) {
                continue;
            }
            /** @var array $store */
            foreach ($data as $store) {
                /** @var string $value */
                $value = 'labels-' . $local;

                if ($this->configHelper->getOptionCodeAsAdminLabel() && $store['store_id'] == 0) {
                    $value = 'code';
                }

                /** @var Select $options */
                $options = $connection->select()->from(
                    ['a' => $tmpTable],
                    [
                        'option_id' => '_entity_id',
                        'store_id'  => new Expr($store['store_id']),
                        'value'     => $value,
                    ]
                )->joinInner(
                    ['b' => $this->entitiesHelper->getTable('akeneo_connector_entities')],
                    'a.attribute = b.code AND b.import = "attribute"',
                    []
                );
                $connection->query(
                    $connection->insertFromSelect(
                        $options,
                        $this->entitiesHelper->getTable('eav_attribute_option_value'),
                        ['option_id', 'store_id', 'value'],
                        1
                    )
                );
            }
        }
        $this->insertSwatchOption();
    }

    /**
     * Insert Swatch options Values for swatch attributes (visual swatch have no data on V1)
     *
     * @return void
     */
    public function insertSwatchOption(): void
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());
        /** @var array $stores */
        $stores = $this->storeHelper->getStores('lang');
        /**
         * @var string $local
         * @var array $data
         */

        // On récupère le mapping akeneo_attribute_code => swatch_type
        $swatchesAttributes = $this->attributeHelper->getAdditionalSwatchTypes();

        if (empty($swatchesAttributes)) {
            return;
        }

        foreach ($stores as $local => $data) {
            if (!$connection->tableColumnExists($tmpTable, 'labels-' . $local)) {
                continue;
            }
            /** @var array $store */
            foreach ($data as $store) {
                /** @var string $value */
                $value = 'labels-' . $local;

                if ($this->configHelper->getOptionCodeAsAdminLabel() && $store['store_id'] == 0) {
                    $value = 'code';
                }

                /** @var Select $options */
                $options = $connection->select()->from(
                    ['a' => $tmpTable],
                    [
                        'option_id' => '_entity_id',
                        'store_id' => new Expr($store['store_id']),
                        'attribute',
                        'value' => $value,
                    ]
                )->joinInner(
                    ['b' => $this->entitiesHelper->getTable('akeneo_connector_entities')],
                    'a.attribute = b.code AND b.import = "attribute"',
                    []
                )->where('a.attribute in (?)', array_keys($swatchesAttributes));

                $swatchesAttributesData = $connection->fetchAll($options);

                $dataToInsert = [];

                foreach ($swatchesAttributesData as $swatchesAttributeData) {
                    $dataToInsert[] = [
                        'option_id' => $swatchesAttributeData['option_id'],
                        'store_id' => $swatchesAttributeData['store_id'],
                        'type' => ($swatchesAttributes[$swatchesAttributeData['attribute']] === Swatch::SWATCH_TYPE_TEXTUAL_ATTRIBUTE_FRONTEND_INPUT) ? Swatch::SWATCH_TYPE_TEXTUAL : Swatch::SWATCH_TYPE_EMPTY,
                        'value' => ($swatchesAttributes[$swatchesAttributeData['attribute']] === Swatch::SWATCH_TYPE_TEXTUAL_ATTRIBUTE_FRONTEND_INPUT) ? $swatchesAttributeData['value'] : null,
                    ];
                }

                if (empty($dataToInsert)) {
                    continue;
                }

                $connection->insertOnDuplicate(
                    $this->entitiesHelper->getTable('eav_attribute_option_swatch'),
                    $dataToInsert,
                    ['option_id', 'store_id', 'type', 'value']
                );
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
        if (!$this->configHelper->isAdvancedLogActivated()) {
            $this->entitiesHelper->dropTable($this->jobExecutor->getCurrentJob()->getCode());
        }
    }

    /**
     * Clean cache
     *
     * @return void
     */
    public function cleanCache()
    {
        /** @var string $configurations */
        $configurations = $this->configHelper->getCacheTypeOption();

        if (!$configurations) {
            $this->jobExecutor->setMessage(__('No cache cleaned'), $this->logger);

            return;
        }

        /** @var string[] $types */
        $types = explode(',', $configurations ?? '');
        /** @var string[] $types */
        $cacheTypeLabels = $this->cacheTypeList->getTypeLabels();

        /** @var string $type */
        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }

        $this->jobExecutor->setMessage(
            __('Cache cleaned for: %1', join(', ', array_intersect_key($cacheTypeLabels, array_flip($types)))),
            $this->logger
        );
    }

    /**
     * Refresh index
     *
     * @return void
     * @throws \Exception
     */
    public function refreshIndex()
    {
        /** @var string $configurations */
        $configurations = $this->configHelper->getIndexOption();

        if (!$configurations) {
            $this->jobExecutor->setMessage(__('No index refreshed'), $this->logger);

            return;
        }

        /** @var string[] $types */
        $types = explode(',', $configurations ?? '');
        /** @var string[] $typesFlushed */
        $typesFlushed = [];

        /** @var string $type */
        foreach ($types as $type) {
            /** @var IndexerInterface $index */
            $index = $this->indexFactory->create()->load($type);
            $index->reindexAll();
            $typesFlushed[] = $index->getTitle();
        }

        $this->jobExecutor->setMessage(
            __('Index refreshed for: %1', join(', ', $typesFlushed)),
            $this->logger
        );
    }

    /**
     * Retrieve options for the given attribute and insert their data in the temporary table
     *
     * @param string $attributeCode
     * @param int    $paginationSize
     *
     * @return int
     */
    protected function processAttributeOption($attributeCode, $paginationSize)
    {
        if (!$this->akeneoClient) {
            $this->akeneoClient = $this->jobExecutor->getAkeneoClient();
        }
        /** @var ResourceCursorInterface $options */
        $options = $this->akeneoClient->getAttributeOptionApi()->all($attributeCode, $paginationSize);
        /** @var int $index */
        $index = 0;
        /** @var array $option */
        foreach ($options as $index => $option) {
            $this->entitiesHelper->insertDataFromApi($option, $this->jobExecutor->getCurrentJob()->getCode());
        }
        $index++;

        return $index;
    }

    /**
     * Get all attributes from the API
     *
     * @param bool $logging
     *
     * @return ResourceCursorInterface|mixed
     */
    public function getAllAttributes($logging = false)
    {
        if (!$this->attributes) {
            if (!$this->akeneoClient) {
                $this->akeneoClient = $this->jobExecutor->getAkeneoClient();
            }
            /** @var string|int $paginationSize */
            $paginationSize = $this->configHelper->getPaginationSize();
            /** @var string[] $filters */
            $filters = $this->attributeFilters->getFilters();
            if ($this->configHelper->isAdvancedLogActivated() && $logging) {
                $this->logger->debug(__('Attribute API call Filters : ') . print_r($filters, true));
            }
            $this->attributes = $this->akeneoClient->getAttributeApi()->all($paginationSize, $filters);
        }

        return $this->attributes;
    }

    /**
     * Description getLogger function
     *
     * @return OptionLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
