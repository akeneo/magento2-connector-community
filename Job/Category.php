<?php

namespace Akeneo\Connector\Job;

use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\Output as OutputHelper;
use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Import\Entities;
use Akeneo\Connector\Helper\Store as StoreHelper;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Staging\Model\VersionManager;
use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;
use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Zend_Db_Expr as Expr;

/**
 * Class Category
 *
 * @category  Class
 * @package   Akeneo\Connector\Job
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Category extends Import
{
    /**
     * @var int MAX_DEPTH
     */
    const MAX_DEPTH = 10;
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
     * This variable contains an Entities
     *
     * @var Entities $entitiesHelper
     */
    protected $entitiesHelper;
    /**
     * This variable contains a StoreHelper
     *
     * @var StoreHelper $storeHelper
     */
    protected $storeHelper;
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;
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
     * Category constructor
     *
     * @param OutputHelper $outputHelper
     * @param ManagerInterface $eventManager
     * @param Authenticator $authenticator
     * @param TypeListInterface $cacheTypeList
     * @param Entities $entitiesHelper
     * @param StoreHelper $storeHelper
     * @param ConfigHelper $configHelper
     * @param CategoryModel $categoryModel
     * @param CategoryUrlPathGenerator $categoryUrlPathGenerator
     * @param array $data
     */
    public function __construct(
        OutputHelper $outputHelper,
        ManagerInterface $eventManager,
        Authenticator $authenticator,
        TypeListInterface $cacheTypeList,
        Entities $entitiesHelper,
        StoreHelper $storeHelper,
        ConfigHelper $configHelper,
        CategoryModel $categoryModel,
        CategoryUrlPathGenerator $categoryUrlPathGenerator,
        array $data = []
    ) {
        parent::__construct($outputHelper, $eventManager, $authenticator, $data);

        $this->storeHelper    = $storeHelper;
        $this->entitiesHelper = $entitiesHelper;
        $this->cacheTypeList  = $cacheTypeList;
        $this->configHelper   = $configHelper;
        $this->categoryModel  = $categoryModel;
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
    }

    /**
     * Create temporary table for family import
     *
     * @return void
     */
    public function createTable()
    {
        /** @var PageInterface $families */
        $categories = $this->akeneoClient->getCategoryApi()->listPerPage(1);
        /** @var array $category */
        $category = $categories->getItems();
        if (empty($category)) {
            $this->setMessage(__('No results retrieved from Akeneo'));
            $this->stop(1);

            return;
        }
        $category = reset($category);
        $this->entitiesHelper->createTmpTableFromApi($category, $this->getCode());
    }

    /**
     * Insert families in the temporary table
     *
     * @return void
     */
    public function insertData()
    {
        /** @var string|int $paginationSize */
        $paginationSize = $this->configHelper->getPanigationSize();
        /** @var ResourceCursorInterface $categories */
        $categories = $this->akeneoClient->getCategoryApi()->all($paginationSize);
        /** @var string $warning */
        $warning = '';
        /**
         * @var int $index
         * @var array $category
         */
        foreach ($categories as $index => $category) {
            /** @var string[] $lang */
            $lang = $this->storeHelper->getStores('lang');
            $warning = $this->checkLabelPerLocales($category, $lang, $warning);

            $this->entitiesHelper->insertDataFromApi($category, $this->getCode());
        }
        $index++;

        $this->setMessage(
            __('%1 line(s) found. %2', $index, $warning)
        );
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
            $this->getCode()
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
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());

        $connection->addColumn($tmpTable, 'level', [
            'type' => 'integer',
            'length' => 11,
            'default' => 0,
            'COMMENT' => ' ',
            'nullable' => false
        ]);
        $connection->addColumn($tmpTable, 'path', [
            'type' => 'text',
            'length' => 255,
            'default' => '',
            'COMMENT' => ' ',
            'nullable' => false
        ]);
        $connection->addColumn($tmpTable, 'parent_id', [
            'type' => 'integer',
            'length' => 11,
            'default' => 0,
            'COMMENT' => ' ',
            'nullable' => false
        ]);

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
            $connection->query('
                UPDATE `' . $tmpTable . '` c1
                INNER JOIN `' . $tmpTable . '` c2 ON c2.`code` = c1.`parent`
                SET c1.`level` = c2.`level` + 1,
                    c1.`path` = CONCAT(c2.`path`, "/", c1.`_entity_id`),
                    c1.`parent_id` = c2.`_entity_id`
                WHERE c1.`level` <= c2.`level` - 1
            ');
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
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var array $stores */
        $stores = $this->storeHelper->getStores('lang');

        /**
         * @var string $local
         * @var array $affected
         */
        foreach ($stores as $local => $affected) {
            /** @var array $keys */
            $keys = [];
            if ($connection->tableColumnExists($tmpTable, 'labels-' . $local)) {
                $connection->addColumn($tmpTable, 'url_key-' . $local, [
                    'type' => 'text',
                    'length' => 255,
                    'default' => '',
                    'COMMENT' => ' ',
                    'nullable' => false
                ]);

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

                    $connection->update(
                        $tmpTable,
                        ['url_key-' . $local => $finalKey],
                        ['_entity_id = ?' => $row['entity_id']]
                    );
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
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());

        $connection->addColumn($tmpTable, 'position', [
            'type' => 'integer',
            'length' => 11,
            'default' => 0,
            'COMMENT' => ' ',
            'nullable' => false
        ]);

        /** @var \Zend_Db_Statement_Interface $query */
        $query = $connection->query(
            $connection->select()
                ->from(
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
                $connection->select()
                    ->from(
                        $tmpTable,
                        ['position' => new Expr('MAX(`position`) + 1')]
                    )
                    ->where('parent_id = ?', $row['parent_id'])
                    ->group('parent_id')
            );
            /** @var array $values */
            $values = [
                'position' => $position
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
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());

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

        /** @var string $columnIdentifier */
        $columnIdentifier = $this->entitiesHelper->getColumnIdentifier($table);

        if ($columnIdentifier == 'row_id') {
            $values['row_id'] = '_entity_id';
        }

        /** @var \Magento\Framework\DB\Select $parents */
        $parents = $connection->select()->from($tmpTable, $values);
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
            'created_at' => new Expr('now()')
        ];
        $connection->update($table, $values, 'created_at IS NULL');

        if ($columnIdentifier === 'row_id') {
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
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
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
            $this->getCode(),
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
         * @var array $affected
         */
        foreach ($stores as $local => $affected) {
            if (!$connection->tableColumnExists($tmpTable, 'labels-' . $local)) {
                continue;
            }

            foreach ($affected as $store) {
                /** @var array $values */
                $values = [
                    'name'    => 'labels-' . $local,
                    'url_key' => 'url_key-' . $local,
                ];
                $this->entitiesHelper->setValues(
                    $this->getCode(),
                    'catalog_category_entity',
                    $values,
                    $entityTypeId,
                    $store['store_id']
                );
            }
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

        $connection->query('
            UPDATE `' . $this->entitiesHelper->getTable('catalog_category_entity') . '` c SET `children_count` = (
                SELECT COUNT(`parent_id`) FROM (
                    SELECT * FROM `' . $this->entitiesHelper->getTable('catalog_category_entity') . '`
                ) tmp
                WHERE tmp.`path` LIKE CONCAT(c.`path`,\'/%\')
            )
        ');
    }

    /**
     * Remove categories from category filter configuration
     *
     * @return void
     */
    public function removeCategoriesByFilter()
    {
        /** @var string|string[] $filteredCategories */
        $filteredCategories = $this->configHelper->getCategoriesFilter();
        if (!$filteredCategories || empty($filteredCategories)) {
            $this->setMessage(
                __('No category to ignore')
            );
            return;
        }
        /** @var string $tableName */
        $tableName = $this->entitiesHelper->getTableName($this->getCode());
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        $filteredCategories = explode(',', $filteredCategories);
        /** @var mixed[]|null $categoriesToDelete */
        $categoriesToDelete = $connection->fetchAll(
            $connection->select()->from($tableName)->where('code IN (?)', $filteredCategories)
        );
        if (!$categoriesToDelete) {
            $this->setMessage(
                __('No category found')
            );
            return;
        }
        foreach ($categoriesToDelete as $category) {
            if (!isset($category['_entity_id'])) {
                continue;
            }
            $connection->delete($tableName, ['path LIKE ?' => '%/' . $category['_entity_id'] . '/%']);
            $connection->delete($tableName, ['path LIKE ?'   => '%/' . $category['_entity_id']]);
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
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var array $stores */
        $stores = $this->storeHelper->getStores('lang');

        /**
         * @var string $local
         * @var array $affected
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
                $select = $connection->select()
                    ->from(
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
                            ->where('entity_type = ?', CategoryUrlRewriteGenerator::ENTITY_TYPE)
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
                        $connection->update(
                            $this->entitiesHelper->getTable('url_rewrite'),
                            ['request_path' => $requestPath],
                            ['url_rewrite_id = ?' => $rewriteId]
                        );
                    } else {
                        /** @var array $data */
                        $data = [
                            'entity_type'      => CategoryUrlRewriteGenerator::ENTITY_TYPE,
                            'entity_id'        => $category->getEntityId(),
                            'request_path'     => $requestPath,
                            'target_path'      => 'catalog/category/view/id/' . $category->getEntityId(),
                            'redirect_type'    => 0,
                            'store_id'         => $category->getStoreId(),
                            'is_autogenerated' => 1
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
            \Magento\Framework\App\Cache\Type\Block::TYPE_IDENTIFIER,
            \Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER
        ];

        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }

        $this->setMessage(
            __('Cache cleaned for: %1', join(', ', $types))
        );
    }
}
