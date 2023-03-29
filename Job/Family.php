<?php

declare(strict_types=1);

namespace Akeneo\Connector\Job;

use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\FamilyFilters;
use Akeneo\Connector\Helper\Import\Entities;
use Akeneo\Connector\Helper\Output as OutputHelper;
use Akeneo\Connector\Helper\Store as StoreHelper;
use Akeneo\Connector\Logger\FamilyLogger;
use Akeneo\Connector\Logger\Handler\FamilyHandler;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Set;
use Magento\Eav\Model\Entity\Attribute\SetFactory;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Indexer\Model\IndexerFactory;
use Zend_Db_Expr as Expr;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Family extends Import
{
    /**
     * This variable contains a string value
     *
     * @var string $code
     */
    protected $code = 'family';
    /**
     * This variable contains a string value
     *
     * @var string $name
     */
    protected $name = 'Family';
    /**
     * This variable contains a SetFactory
     *
     * @var SetFactory $attributeSetFactory
     */
    protected $attributeSetFactory;
    /**
     * This variable contains a TypeListInterface
     *
     * @var TypeListInterface $cacheTypeList
     */
    protected $cacheTypeList;
    /**
     * This variable contains an EavConfig
     *
     * @var Config $eavConfig
     */
    protected $eavConfig;
    /**
     * This variable contains a StoreHelper
     *
     * @var StoreHelper $storeHelper
     */
    protected $storeHelper;
    /**
     * This variable contains a logger
     *
     * @var FamilyLogger $logger
     */
    protected $logger;
    /**
     * This variable contains a handler
     *
     * @var FamilyHandler $handler
     */
    protected $handler;
    /**
     * Description $familyFilters field
     *
     * @var FamilyFilters $familyFilters
     */
    protected $familyFilters;
    /**
     * This variable contains a IndexerInterface
     *
     * @var IndexerFactory $indexFactory
     */
    protected $indexFactory;

    /**
     * Family constructor
     *
     * @param FamilyLogger      $logger
     * @param FamilyHandler     $handler
     * @param StoreHelper       $storeHelper
     * @param Entities          $entitiesHelper
     * @param ConfigHelper      $configHelper
     * @param OutputHelper      $outputHelper
     * @param ManagerInterface  $eventManager
     * @param Authenticator     $authenticator
     * @param SetFactory        $attributeSetFactory
     * @param TypeListInterface $cacheTypeList
     * @param Config            $eavConfig
     * @param FamilyFilters     $familyFilters
     * @param IndexerFactory    $indexFactory
     * @param array             $data
     */
    public function __construct(
        FamilyLogger $logger,
        FamilyHandler $handler,
        StoreHelper $storeHelper,
        Entities $entitiesHelper,
        ConfigHelper $configHelper,
        OutputHelper $outputHelper,
        ManagerInterface $eventManager,
        Authenticator $authenticator,
        SetFactory $attributeSetFactory,
        TypeListInterface $cacheTypeList,
        Config $eavConfig,
        FamilyFilters $familyFilters,
        IndexerFactory $indexFactory,
        array $data = []
    ) {
        parent::__construct($outputHelper, $eventManager, $authenticator, $entitiesHelper, $configHelper, $data);

        $this->logger              = $logger;
        $this->handler             = $handler;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->cacheTypeList       = $cacheTypeList;
        $this->eavConfig           = $eavConfig;
        $this->storeHelper         = $storeHelper;
        $this->familyFilters       = $familyFilters;
        $this->indexFactory        = $indexFactory;
    }

    /**
     * Create temporary table for family import
     *
     * @return void
     */
    public function createTable()
    {
        /** @var string[] $filters */
        $filters = $this->familyFilters->getFilters();
        if ($this->configHelper->isAdvancedLogActivated()) {
            $this->jobExecutor->setAdditionalMessage(
                __('Path to log file : %1', $this->handler->getFilename()),
                $this->logger
            );
            $this->logger->debug(__('Import identifier : %1', $this->jobExecutor->getIdentifier()));
            $this->logger->debug(__('Family API call Filters : ') . print_r($filters, true));
        }
        /** @var PageInterface $families */
        $families = $this->akeneoClient->getFamilyApi()->listPerPage(1, false, $filters);
        /** @var array $family */
        $family = $families->getItems();

        if (empty($family)) {
            $this->jobExecutor->setMessage(__('No results retrieved from Akeneo'), $this->logger);
            $this->jobExecutor->afterRun(true);

            return;
        }
        $family = reset($family);
        $this->entitiesHelper->createTmpTableFromApi($family, $this->jobExecutor->getCurrentJob()->getCode());
    }

    /**
     * Insert families in the temporary table
     *
     * @return void
     */
    public function insertData()
    {
        /** @var string[] $filters */
        $filters = $this->familyFilters->getFilters();
        /** @var string|int $paginationSize */
        $paginationSize = $this->configHelper->getPaginationSize();
        /** @var ResourceCursorInterface $families */
        $families = $this->akeneoClient->getFamilyApi()->all($paginationSize, $filters);
        /** @var string $warning */
        $warning = '';
        /** @var string[] $lang */
        $lang = $this->storeHelper->getStores('lang');
        /**
         * @var int   $index
         * @var array $family
         */
        foreach ($families as $index => $family) {
            $warning = $this->checkLabelPerLocales($family, $lang, $warning);

            $this->entitiesHelper->insertDataFromApi($family, $this->jobExecutor->getCurrentJob()->getCode());
        }
        $index++;

        $this->jobExecutor->setMessage(
            __('%1 line(s) found. %2', $index, $warning),
            $this->logger
        );

        if ($this->configHelper->isAdvancedLogActivated()) {
            $this->logImportedEntities($this->logger);
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
        $entityTable = $this->entitiesHelper->getTable('eav_attribute_set');
        /** @var \Magento\Framework\DB\Select $selectExistingEntities */
        $selectExistingEntities = $connection->select()->from($entityTable, 'attribute_set_id');
        /** @var string[] $existingEntities */
        $existingEntities = array_column($connection->query($selectExistingEntities)->fetchAll(), 'attribute_set_id');

        $connection->delete(
            $akeneoConnectorTable,
            ['import = ?' => 'family', 'entity_id NOT IN (?)' => $existingEntities]
        );

        if ($this->configHelper->isAdvancedLogActivated()) {
            $this->logImportedEntities($this->logger, true);
        }
    }

    /**
     * Match code with entity
     *
     * @return void
     */
    public function matchEntities()
    {
        $this->entitiesHelper->matchEntity(
            'code',
            'eav_attribute_set',
            'attribute_set_id',
            $this->jobExecutor->getCurrentJob()->getCode()
        );
    }

    /**
     * Insert families
     *
     * @return void
     */
    public function insertFamilies()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());
        /** @var string $label */
        $label = 'labels-' . $this->configHelper->getDefaultLocale();
        /** @var string $productEntityTypeId */
        $productEntityTypeId = $this->eavConfig->getEntityType(ProductAttributeInterface::ENTITY_TYPE_CODE)
            ->getEntityTypeId();

        /** @var array $values */
        $values = [
            'attribute_set_id'   => '_entity_id',
            'entity_type_id'     => new Expr($productEntityTypeId),
            'attribute_set_name' => new Expr('CONCAT("Pim", " ", IFNULL(`' . $label . '`, ""), " (", `code`, ")")'),
            'sort_order'         => new Expr(1),
        ];
        /** @var Select $families */
        $families = $connection->select()->from($tmpTable, $values);

        $connection->query(
            $connection->insertFromSelect(
                $families,
                $this->entitiesHelper->getTable('eav_attribute_set'),
                array_keys($values),
                1
            )
        );
    }

    /**
     * Insert relations between family and list of attributes
     *
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function insertFamiliesAttributeRelations()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());
        /** @var string $familyAttributeRelationsTable */
        $familyAttributeRelationsTable = $this->entitiesHelper->getTable('akeneo_connector_family_attribute_relations');

        $connection->delete($familyAttributeRelationsTable);
        /** @var array $values */
        $values = [
            'family_entity_id' => '_entity_id',
            'attribute_code'   => 'attributes',
        ];
        /** @var Select $relations */
        $relations = $connection->select()->from($tmpTable, $values);
        /** @var \Zend_Db_Statement_Interface $query */
        $query = $connection->query($relations);
        /** @var array $row */
        while ($row = $query->fetch()) {
            /** @var array $attributes */
            $attributes = explode(',', $row['attribute_code'] ?? '');
            /** @var string $attribute */
            foreach ($attributes as $attribute) {
                $connection->insert(
                    $familyAttributeRelationsTable,
                    ['family_entity_id' => $row['family_entity_id'], 'attribute_code' => $attribute]
                );
            }
        }
    }

    /**
     * Init group
     *
     * @return void
     * @throws \Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function initGroup()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());
        /** @var \Zend_Db_Statement_Interface $query */
        $query = $connection->query(
            $connection->select()->from($tmpTable, ['_entity_id'])->where('_is_new = ?', 1)
        );
        /** @var string $defaultAttributeSetId */
        $defaultAttributeSetId = $this->eavConfig->getEntityType(ProductAttributeInterface::ENTITY_TYPE_CODE)
            ->getDefaultAttributeSetId();
        /** @var int $count */
        $count = 0;
        /** @var array $row */
        while (($row = $query->fetch())) {
            /** @var Set $attributeSet */
            $attributeSet = $this->attributeSetFactory->create();
            $attributeSet->load($row['_entity_id']);

            if ($attributeSet->hasData()) {
                $attributeSet->initFromSkeleton($defaultAttributeSetId)->save();
            }
            $count++;
        }

        $this->jobExecutor->setMessage(
            __('%1 family(ies) initialized', $count),
            $this->logger
        );
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
        $configurations = $this->configHelper->getCacheTypeFamily();

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
        $configurations = $this->configHelper->getIndexFamily();

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
     * Description getLogger function
     *
     * @return FamilyLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
