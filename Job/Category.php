<?php

declare(strict_types=1);

namespace Akeneo\Connector\Job;

use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\CategoryFilters;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\Import\Entities;
use Akeneo\Connector\Helper\Output as OutputHelper;
use Akeneo\Connector\Helper\Store as StoreHelper;
use Akeneo\Connector\Logger\CategoryLogger;
use Akeneo\Connector\Logger\Handler\CategoryHandler;
use Akeneo\Connector\Model\Source\Edition;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;
use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Indexer\Model\IndexerFactory;
use Zend_Db_Expr as Expr;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Category extends Import
{
    /**
     * @var int MAX_DEPTH
     */
    public const MAX_DEPTH = 10;
    /**
     * This variable contains a string value
     *
     * @var string $code
     */
    protected $code = 'category';
    /**
     * This variable contains a string value
     *
     * @var string $name
     */
    protected $name = 'Category';
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
     * This variable contains CategoryModel
     *
     * @var CategoryModel $categoryModel
     */
    protected $categoryModel;
    /**
     * This variable containsCategoryUrlPathGenerator
     *
     * @var CategoryUrlPathGenerator $categoryUrlPathGenerator
     */
    protected $categoryUrlPathGenerator;
    /**
     * This variable contains a logger
     *
     * @var CategoryLogger $logger
     */
    protected $logger;
    /**
     * This variable contains a handler
     *
     * @var CategoryHandler $handler
     */
    protected $handler;
    /**
     * Description $categoryFilters field
     *
     * @var CategoryFilters $categoryFilters
     */
    protected $categoryFilters;
    /**
     * Description $editionSource field
     *
     * @var Edition $editionSource
     */
    protected $editionSource;
    /**
     * This variable contains entities
     *
     * @var Entities $entities
     */
    protected $entities;
    /**
     * This variable contains a IndexerInterface
     *
     * @var IndexerFactory $indexFactory
     */
    protected $indexFactory;

    /**
     * Category constructor
     *
     * @param CategoryLogger           $logger
     * @param CategoryHandler          $handler
     * @param OutputHelper             $outputHelper
     * @param ManagerInterface         $eventManager
     * @param Authenticator            $authenticator
     * @param TypeListInterface        $cacheTypeList
     * @param Entities                 $entitiesHelper
     * @param StoreHelper              $storeHelper
     * @param ConfigHelper             $configHelper
     * @param CategoryModel            $categoryModel
     * @param CategoryUrlPathGenerator $categoryUrlPathGenerator
     * @param CategoryFilters          $categoryFilters
     * @param Edition                  $editionSource
     * @param Entities                 $entities
     * @param IndexerFactory           $indexFactory
     * @param array                    $data
     */
    public function __construct(
        CategoryLogger $logger,
        CategoryHandler $handler,
        OutputHelper $outputHelper,
        ManagerInterface $eventManager,
        Authenticator $authenticator,
        TypeListInterface $cacheTypeList,
        Entities $entitiesHelper,
        StoreHelper $storeHelper,
        ConfigHelper $configHelper,
        CategoryModel $categoryModel,
        CategoryUrlPathGenerator $categoryUrlPathGenerator,
        CategoryFilters $categoryFilters,
        Edition $editionSource,
        Entities $entities,
        IndexerFactory $indexFactory,
        array $data = []
    ) {
        parent::__construct($outputHelper, $eventManager, $authenticator, $entitiesHelper, $configHelper, $data);

        $this->storeHelper              = $storeHelper;
        $this->logger                   = $logger;
        $this->handler                  = $handler;
        $this->cacheTypeList            = $cacheTypeList;
        $this->categoryModel            = $categoryModel;
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
        $this->categoryFilters          = $categoryFilters;
        $this->editionSource            = $editionSource;
        $this->entities                 = $entities;
        $this->indexFactory             = $indexFactory;
    }

    /**
     * Create temporary table for family import
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
            $this->logger->debug(
                __('Category API call Filters : ') . print_r($this->categoryFilters->getParentFilters(), true)
            );
        }
        if (!$this->categoryFilters->getCategoriesToImport()) {
            $this->jobExecutor->setMessage(
                __('No categories to import, check your category filter configuration'),
                $this->logger
            );
            $this->jobExecutor->afterRun(true);

            return;
        }
        /** @var PageInterface $families */
        $categories = $this->akeneoClient->getCategoryApi()->listPerPage(
            1,
            false,
            $this->categoryFilters->getParentFilters()
        );
        /** @var array $category */
        $category = $categories->getItems();

        if (empty($category)) {
            $this->jobExecutor->setMessage(__('No results retrieved from Akeneo'), $this->logger);
            $this->jobExecutor->afterRun(true);

            return;
        }
        $category = reset($category);
        $this->entitiesHelper->createTmpTableFromApi($category, $this->jobExecutor->getCurrentJob()->getCode());
    }

    /**
     * Insert families in the temporary table
     *
     * @return void
     */
    public function insertData()
    {
        /** @var string|int $paginationSize */
        $paginationSize = $this->configHelper->getPaginationSize();

        /** @var string $edition */
        $edition = $this->configHelper->getEdition();

        /** @var ResourceCursorInterface $categories */
        $categories = [];
        if ($edition === Edition::GREATER_OR_FOUR_POINT_ZERO_POINT_SIXTY_TWO
            || $edition === Edition::GREATER_OR_FIVE
            || $edition === Edition::SERENITY
            || $edition === Edition::GROWTH
            || $edition === Edition::SEVEN
        ) {
            /** @var ResourceCursorInterface $parentCategories */
            $parentCategories = $this->akeneoClient->getCategoryApi()->all(
                $paginationSize,
                $this->categoryFilters->getParentFilters()
            );

            /** @var string[] $categoriesToImport */
            $categoriesToImport = $this->categoryFilters->getCategoriesToImport();

            if (count($categoriesToImport) != iterator_count($parentCategories)) {
                /** @var string[] $editions */
                $editions = $this->editionSource->toOptionArray();
                $this->jobExecutor->setMessage(
                    __(
                        'Wrong Akeneo version selected in the Akeneo Edition configuration field: %1',
                        $editions[$edition]
                    ),
                    $this->logger
                );
                $this->jobExecutor->afterRun(true);

                return;
            }

            /** @var string[] $category */
            foreach ($parentCategories as $category) {
                $categories[] = $category;
                /** @var ResourceCursorInterface $childCategories */
                $childCategories = $this->akeneoClient->getCategoryApi()->all(
                    $paginationSize,
                    $this->categoryFilters->getChildFilters($category)
                );
                /** @var string[] $child */
                foreach ($childCategories as $child) {
                    $categories[] = $child;
                }
            }
        } else {
            $categories = $this->akeneoClient->getCategoryApi()->all($paginationSize);
        }

        /** @var string $warning */
        $warning = '';
        /** @var string[] $lang */
        $lang = $this->storeHelper->getStores('lang');
        /**
         * @var int   $index
         * @var array $category
         */
        foreach ($categories as $index => $category) {
            $warning = $this->checkLabelPerLocales($category, $lang, $warning);

            $this->entitiesHelper->insertDataFromApi($category, $this->jobExecutor->getCurrentJob()->getCode());
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
        $entityTable = $this->entitiesHelper->getTable('catalog_category_entity');
        /** @var \Magento\Framework\DB\Select $selectExistingEntities */
        $selectExistingEntities = $connection->select()->from($entityTable, 'entity_id');
        /** @var string[] $existingEntities */
        $existingEntities = array_column($connection->query($selectExistingEntities)->fetchAll(), 'entity_id');

        $connection->delete(
            $akeneoConnectorTable,
            ['import = ?' => 'category', 'entity_id NOT IN (?)' => $existingEntities]
        );
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
            'catalog_category_entity',
            'entity_id',
            $this->jobExecutor->getCurrentJob()->getCode()
        );
    }

    /**
     * Set Categories structure
     *
     * @return void
     */
    public function setStructure()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tableName */
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());

        $connection->addColumn(
            $tmpTable,
            'level',
            [
                'type'     => 'integer',
                'length'   => 11,
                'default'  => 0,
                'COMMENT'  => ' ',
                'nullable' => false,
            ]
        );
        $connection->addColumn(
            $tmpTable,
            'path',
            [
                'type'     => 'text',
                'length'   => 255,
                'default'  => '',
                'COMMENT'  => ' ',
                'nullable' => false,
            ]
        );
        $connection->addColumn(
            $tmpTable,
            'parent_id',
            [
                'type'     => 'integer',
                'length'   => 11,
                'default'  => 0,
                'COMMENT'  => ' ',
                'nullable' => false,
            ]
        );

        /** @var array $values */
        $values = [
            'level'     => 1,
            'path'      => new Expr('CONCAT(1, "/", `_entity_id`)'),
            'parent_id' => 1,
        ];
        $connection->update($tmpTable, $values, 'parent IS NULL');

        /** @var int $depth */
        $depth = self::MAX_DEPTH;
        for ($i = 1; $i <= $depth; $i++) {
            $connection->query(
                '
                UPDATE `' . $tmpTable . '` c1
                INNER JOIN `' . $tmpTable . '` c2 ON c2.`code` = c1.`parent`
                SET c1.`level` = c2.`level` + 1,
                    c1.`path` = CONCAT(c2.`path`, "/", c1.`_entity_id`),
                    c1.`parent_id` = c2.`_entity_id`
                WHERE c1.`level` <= c2.`level` - 1
            '
            );
        }
    }

    /**
     * Set categories Url Key
     *
     * @return void
     */
    public function setUrlKey()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tableName */
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());
        /** @var array $stores */
        $stores = $this->storeHelper->getStores('lang');

        /**
         * @var string $local
         * @var array  $affected
         */
        foreach ($stores as $local => $affected) {
            /** @var array $keys */
            $keys = [];
            if ($connection->tableColumnExists($tmpTable, 'labels-' . $local)) {
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

                $connection->addColumn(
                    $tmpTable,
                    'url_path-' . $local,
                    [
                        'type'     => 'text',
                        'length'   => 255,
                        'default'  => '',
                        'COMMENT'  => ' ',
                        'nullable' => false,
                    ]
                );

                $select = $connection->select()->from(
                    $tmpTable,
                    ['entity_id' => '_entity_id', 'name' => 'labels-' . $local, 'parent_id' => 'parent_id']
                );

                $updateUrl = true; // TODO retrieve update URL from config

                if (!$updateUrl) {
                    $select->where('_is_new = ?', 1);
                }

                /** @var \Zend_Db_Statement_Interface $query */
                $query = $connection->query($select);

                /** @var array $row */
                while (($row = $query->fetch())) {
                    /** @var string $urlKey */
                    $urlKey = $this->categoryModel->formatUrlKey($row['name']);
                    /** @var string $finalKey */
                    $finalKey = $urlKey;
                    /** @var int $increment */
                    $increment = 1;
                    while (isset($keys[$row['parent_id']]) && in_array($finalKey, $keys[$row['parent_id']])) {
                        $finalKey = $urlKey . '-' . $increment++;
                    }

                    $keys[$row['parent_id']][] = $finalKey;

                    /** @var bool $isRoot */
                    $isRoot = $row['parent_id'] <= CategoryModel::TREE_ROOT_ID;
                    /** @var string $urlPathCol */
                    $urlPathCol = $connection->quoteIdentifier('url_path-' . $local);

                    /** @var Select $subSelect */
                    $subSelect = $connection->select()->from(
                        false,
                        [
                            'url_path-' . $local => $isRoot ? null : "CONCAT(t." . $urlPathCol . ", IF(t." . $urlPathCol . " = '','','/'), '" . $finalKey . "')",
                            'url_key-' . $local  => "'$finalKey'",
                        ]
                    )->joinInner(['t' => $tmpTable], 'main.parent_id = t._entity_id', [])->where(
                        'main._entity_id = ?',
                        $row['entity_id']
                    );
                    /** @var string $update */
                    $update = $connection->updateFromSelect($subSelect, ['main' => $tmpTable]);
                    $connection->query($update);
                }
            }
        }
    }

    /**
     * Set categories position
     *
     * @return void
     */
    public function setPosition()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tableName */
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());

        $connection->addColumn(
            $tmpTable,
            'position',
            [
                'type'     => 'integer',
                'length'   => 11,
                'default'  => 0,
                'COMMENT'  => ' ',
                'nullable' => false,
            ]
        );

        /** @var \Zend_Db_Statement_Interface $query */
        $query = $connection->query(
            $connection->select()->from(
                $tmpTable,
                [
                    'entity_id' => '_entity_id',
                    'parent_id' => 'parent_id',
                ]
            )
        );

        /** @var array $row */
        while (($row = $query->fetch())) {
            /** @var int $position */
            $position = $connection->fetchOne(
                $connection->select()->from(
                    $tmpTable,
                    ['position' => new Expr('MAX(`position`) + 1')]
                )->where('parent_id = ?', $row['parent_id'])->group('parent_id')
            );
            /** @var array $values */
            $values = [
                'position' => $position,
            ];
            $connection->update($tmpTable, $values, ['_entity_id = ?' => $row['entity_id']]);
        }
    }

    /**
     * Create category entities
     *
     * @return void
     */
    public function createEntities()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tableName */
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());

        if ($connection->isTableExists($this->entitiesHelper->getTable('sequence_catalog_category'))) {
            /** @var array $values */
            $values = [
                'sequence_value' => '_entity_id',
            ];
            /** @var \Magento\Framework\DB\Select $parents */
            $parents = $connection->select()->from($tmpTable, $values);
            $connection->query(
                $connection->insertFromSelect(
                    $parents,
                    $this->entitiesHelper->getTable('sequence_catalog_category'),
                    array_keys($values),
                    AdapterInterface::INSERT_ON_DUPLICATE
                )
            );
        }

        /** @var string $table */
        $table = $this->entitiesHelper->getTable('catalog_category_entity');

        /** @var array $values */
        $values = [
            'entity_id'        => '_entity_id',
            'attribute_set_id' => new Expr($this->configHelper->getDefaultAttributeSetId(CategoryModel::ENTITY)),
            'parent_id'        => 'parent_id',
            'updated_at'       => new Expr('now()'),
            'path'             => 'path',
            'position'         => 'position',
            'level'            => 'level',
            'children_count'   => new Expr('0'),
        ];

        /** @var Select $parents */
        $parents = $connection->select()->from($tmpTable, $values);

        /** @var bool $rowIdExists */
        $rowIdExists = $this->entitiesHelper->rowIdColumnExists($table);
        if ($rowIdExists) {
            $this->entities->addJoinForContentStagingCategory($parents, ['p.row_id']);
            $values['row_id'] = 'IFNULL (p.row_id, _entity_id)'; // on category creation, row_id is null
        }

        $connection->query(
            $connection->insertFromSelect(
                $parents,
                $table,
                array_keys($values),
                AdapterInterface::INSERT_ON_DUPLICATE
            )
        );

        /** @var array $values */
        $values = [
            'created_at' => new Expr('now()'),
        ];
        $connection->update($table, $values, 'created_at IS NULL');

        if ($rowIdExists) {
            /** @var array $values */
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
     */
    public function setValues()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tableName */
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());
        /** @var array $values */
        $values = [
            'is_active'       => new Expr($this->configHelper->getIsCategoryActive()),
            'include_in_menu' => new Expr($this->configHelper->getIsCategoryInMenu()),
            'is_anchor'       => new Expr($this->configHelper->getIsCategoryAnchor()),
            'display_mode'    => new Expr('"' . CategoryModel::DM_PRODUCT . '"'),
        ];

        /** @var int $entityTypeId */
        $entityTypeId = $this->configHelper->getEntityTypeId(CategoryModel::ENTITY);

        $this->entitiesHelper->setValues(
            $this->jobExecutor->getCurrentJob()->getCode(),
            'catalog_category_entity',
            $values,
            $entityTypeId,
            0,
            AdapterInterface::INSERT_IGNORE
        );

        /** @var array $stores */
        $stores = $this->storeHelper->getStores('lang');

        /**
         * @var string $local
         * @var array  $affected
         */
        foreach ($stores as $local => $affected) {
            if (!$connection->tableColumnExists($tmpTable, 'labels-' . $local)) {
                continue;
            }

            foreach ($affected as $store) {
                /** @var array $values */
                $values = [
                    'name'     => 'labels-' . $local,
                    'url_key'  => 'url_key-' . $local,
                    'url_path' => 'url_path-' . $local,
                ];
                $this->entitiesHelper->setValues(
                    $this->jobExecutor->getCurrentJob()->getCode(),
                    'catalog_category_entity',
                    $values,
                    $entityTypeId,
                    $store['store_id']
                );
            }
        }

        if ($this->configHelper->isAdvancedLogActivated()) {
            $this->logImportedEntities($this->logger, true);
        }
    }

    /**
     * Update Children Count
     *
     * @return void
     */
    public function updateChildrenCount()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();

        $connection->query(
            '
            UPDATE `' . $this->entitiesHelper->getTable('catalog_category_entity') . '` c SET `children_count` = (
                SELECT COUNT(`parent_id`) FROM (
                    SELECT * FROM `' . $this->entitiesHelper->getTable('catalog_category_entity') . '`
                ) tmp
                WHERE tmp.`path` LIKE CONCAT(c.`path`,\'/%\')
            )
        '
        );
    }

    /**
     * Remove categories from category filter configuration
     *
     * @return void
     */
    public function removeCategoriesByFilter()
    {
        /** @var string $edition */
        $edition = $this->configHelper->getEdition();

        if ($edition === Edition::GREATER_OR_FOUR_POINT_ZERO_POINT_SIXTY_TWO
            || $edition === Edition::GREATER_OR_FIVE
            || $edition === Edition::SERENITY
            || $edition === Edition::GROWTH
            || $edition === Edition::SEVEN
        ) {
            return;
        }

        /** @var string|string[] $filteredCategories */
        $filteredCategories = $this->configHelper->getCategoriesFilter();
        if (!$filteredCategories || empty($filteredCategories)) {
            $this->jobExecutor->setMessage(
                __('No category to ignore'),
                $this->logger
            );

            return;
        }
        /** @var string $tableName */
        $tableName = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());
        /** @var AdapterInterface $connection */
        $connection         = $this->entitiesHelper->getConnection();
        $filteredCategories = explode(',', $filteredCategories ?? '');
        /** @var mixed[]|null $categoriesToDelete */
        $categoriesToDelete = $connection->fetchAll(
            $connection->select()->from($tableName)->where('code IN (?)', $filteredCategories)
        );
        if (!$categoriesToDelete) {
            $this->jobExecutor->setMessage(
                __('No category found'),
                $this->logger
            );

            return;
        }
        foreach ($categoriesToDelete as $category) {
            if (!isset($category['_entity_id'])) {
                continue;
            }
            $connection->delete($tableName, ['path LIKE ?' => '%/' . $category['_entity_id'] . '/%']);
            $connection->delete($tableName, ['path LIKE ?' => '%/' . $category['_entity_id']]);
        }
    }

    /**
     * Set Url Rewrite
     *
     * @return void
     */
    public function setUrlRewrite()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tableName */
        $tmpTable = $this->entitiesHelper->getTableName($this->jobExecutor->getCurrentJob()->getCode());
        /** @var array $stores */
        $stores = $this->storeHelper->getStores('lang');
        /** @var mixed[] $categoryPath */
        $categoryPath = $this->getCategoryPath();
        /** @var mixed[] $rootCatAndStore */
        $rootCatAndStore = $this->getRootCategoriesAndStores();

        /**
         * @var string $local
         * @var array  $affected
         */
        foreach ($stores as $local => $affected) {
            if (!$connection->tableColumnExists($tmpTable, 'url_key-' . $local)) {
                continue;
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
                        'url_path'  => 'path',
                        'store_id'  => new Expr($store['store_id']),
                        'parent_id' => 'parent_id',
                        'level'     => 'level',
                    ]
                );

                /** @var \Magento\Framework\DB\Statement\Pdo\Mysql $query */
                $query = $connection->query($select);

                /** @var array $row */
                while (($row = $query->fetch())) {
                    /** @var CategoryModel $category */
                    $category = $this->categoryModel;
                    $category->setData($row);

                    if (!$this->isCategoryIsInStore(
                        $rootCatAndStore,
                        $categoryPath,
                        $store['store_id'],
                        $category->getId()
                    )) {
                        continue;
                    }

                    /** @var string $urlPath */
                    $urlPath = $this->categoryUrlPathGenerator->getUrlPath($category);

                    if (!$urlPath) {
                        continue;
                    }

                    /** @var string $requestPath */
                    $requestPath = $this->categoryUrlPathGenerator->getUrlPathWithSuffix(
                        $category,
                        $category->getStoreId()
                    );

                    /** @var string|null $exists */
                    $exists = $connection->fetchOne(
                        $connection->select()
                            ->from($this->entitiesHelper->getTable('url_rewrite'), new Expr(1))
                            ->where(
                                'entity_type = ?',
                                CategoryUrlRewriteGenerator::ENTITY_TYPE
                            )
                            ->where('request_path = ?', $requestPath)
                            ->where('store_id = ?', $category->getStoreId())
                            ->where('entity_id <> ?', $category->getEntityId())
                    );

                    if ($exists) {
                        $category->setUrlKey($category->getUrlKey() . '-' . $category->getStoreId());
                        /** @var string $requestPath */
                        $requestPath = $this->categoryUrlPathGenerator->getUrlPathWithSuffix(
                            $category,
                            $category->getStoreId()
                        );
                    }

                    /** @var string|null $rewriteId */
                    $rewriteId = $connection->fetchOne(
                        $connection->select()
                            ->from($this->entitiesHelper->getTable('url_rewrite'), ['url_rewrite_id'])
                            ->where('entity_type = ?', CategoryUrlRewriteGenerator::ENTITY_TYPE)
                            ->where('entity_id = ?', $category->getEntityId())
                            ->where('store_id = ?', $category->getStoreId())
                    );

                    if ($rewriteId) {
                        try {
                            $connection->update(
                                $this->entitiesHelper->getTable('url_rewrite'),
                                ['request_path' => $requestPath],
                                ['url_rewrite_id = ?' => $rewriteId]
                            );
                        } catch (\Exception $e) {
                            $this->jobExecutor->setAdditionalMessage(
                                __(
                                    sprintf(
                                        'Tried to update url_rewrite_id %s : ' .
                                        'request path (%s) already exists for the store_id.',
                                        $rewriteId,
                                        $requestPath
                                    )
                                ),
                                $this->logger
                            );
                        }
                    } else {
                        /** @var array $data */
                        $data = [
                            'entity_type'      => CategoryUrlRewriteGenerator::ENTITY_TYPE,
                            'entity_id'        => $category->getEntityId(),
                            'request_path'     => $requestPath,
                            'target_path'      => 'catalog/category/view/id/' . $category->getEntityId(),
                            'redirect_type'    => 0,
                            'store_id'         => $category->getStoreId(),
                            'is_autogenerated' => 1,
                        ];

                        $connection->insertOnDuplicate(
                            $this->entitiesHelper->getTable('url_rewrite'),
                            $data,
                            array_keys($data)
                        );
                    }
                }
            }
        }
    }

    /**
     * Return array of store and his root category
     * [store_id => root_category_id]
     *
     * @return string[]
     */
    protected function getRootCategoriesAndStores()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();

        /** @var string $select */
        $select = $connection->select()->from(
            ['s' => $connection->getTableName('store')],
            ['store_id', 'g.root_category_id']
        )->join(
            ['g' => $connection->getTableName('store_group')],
            "s.group_id = g.group_id",
            []
        );

        return $connection->fetchPairs($select);
    }

    /**
     * Return category path with entity array
     * [entity_id => path]
     *
     * @return string[]
     */
    protected function getCategoryPath()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();

        /** @var string $select */
        $select = $connection->select()->from(
            ['c' => $connection->getTableName('catalog_category_entity')],
            ['entity_id', 'path']
        );

        return $connection->fetchPairs($select);
    }

    /**
     * Return true if current category is present on current store
     *
     * @param array $rootCategoriesAndStores
     * @param array $categoriesPath
     * @param int   $storeId
     * @param int   $categoryId
     *
     * @return bool
     */
    protected function isCategoryIsInStore(
        array $rootCategoriesAndStores,
        array $categoriesPath,
        $storeId,
        $categoryId
    ) {
        /** @var string $rootCategoryId */
        $currentRootCategoryId = $rootCategoriesAndStores[$storeId];
        /** @var string[] $currentCategoryPath */
        $currentCategoryPath = explode('/', $categoriesPath[$categoryId] ?? '');

        return in_array($currentRootCategoryId, $currentCategoryPath, false);
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
        $configurations = $this->configHelper->getCacheTypeCategory();

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
        $configurations = $this->configHelper->getIndexCategory();

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
     * @return CategoryLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
