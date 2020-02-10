<?php

namespace Akeneo\Connector\Job;

use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\Eav\Model\Config;
use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\Import\Attribute as AttributeHelper;
use Akeneo\Connector\Helper\Import\Entities as EntitiesHelper;
use Akeneo\Connector\Helper\Output as OutputHelper;
use Akeneo\Connector\Helper\Store as StoreHelper;
use \Zend_Db_Expr as Expr;

/**
 * Class Attribute
 *
 * @category  Class
 * @package   Akeneo\Connector\Job
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Attribute extends Import
{
    /**
     * This contains the default name for the attribute set
     *
     * @var string DEFAULT_ATTRIBUTE_SET_NAME
     */
    const DEFAULT_ATTRIBUTE_SET_NAME = 'Akeneo';
    /**
     * This variable contains a string value
     *
     * @var string $code
     */
    protected $code = 'attribute';
    /**
     * This variable contains a string value
     *
     * @var string $name
     */
    protected $name = 'Attribute';
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
     * This variable contains an AttributeHelper
     *
     * @var AttributeHelper $attributeHelper
     */
    protected $attributeHelper;
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
     * This variable contains an EavSetup
     *
     * @var EavSetup $eavSetup
     */
    protected $eavSetup;
    /**
     * List of attributes to exclude from attribute type validation
     *
     * @var string[]
     */
    protected $excludedAttributes = [
        'image',
        'thumbnail',
        'small_image',
        'weight',
    ];

    /**
     * Attribute constructor
     *
     * @param OutputHelper $outputHelper
     * @param ManagerInterface $eventManager
     * @param Authenticator $authenticator
     * @param EntitiesHelper $entitiesHelper
     * @param ConfigHelper $configHelper
     * @param Config $eavConfig
     * @param AttributeHelper $attributeHelper
     * @param TypeListInterface $cacheTypeList
     * @param StoreHelper $storeHelper
     * @param EavSetup $eavSetup
     * @param array $data
     */
    public function __construct(
        OutputHelper $outputHelper,
        ManagerInterface $eventManager,
        Authenticator $authenticator,
        EntitiesHelper $entitiesHelper,
        ConfigHelper $configHelper,
        Config $eavConfig,
        AttributeHelper $attributeHelper,
        TypeListInterface $cacheTypeList,
        StoreHelper $storeHelper,
        EavSetup $eavSetup,
        array $data = []
    ) {
        parent::__construct($outputHelper, $eventManager, $authenticator, $data);

        $this->entitiesHelper  = $entitiesHelper;
        $this->configHelper    = $configHelper;
        $this->eavConfig       = $eavConfig;
        $this->attributeHelper = $attributeHelper;
        $this->cacheTypeList   = $cacheTypeList;
        $this->storeHelper     = $storeHelper;
        $this->eavSetup        = $eavSetup;
    }

    /**
     * Create temporary table
     *
     * @return void
     */
    public function createTable()
    {
        /** @var PageInterface $attributes */
        $attributes = $this->akeneoClient->getAttributeApi()->listPerPage(1);
        /** @var array $attribute */
        $attribute = $attributes->getItems();
        if (empty($attribute)) {
            $this->setMessage(__('No results from Akeneo'));
            $this->stop(1);

            return;
        }
        $attribute = reset($attribute);
        $this->entitiesHelper->createTmpTableFromApi($attribute, $this->getCode());
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
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var string|int $paginationSize */
        $paginationSize = $this->configHelper->getPanigationSize();
        /** @var ResourceCursorInterface $attributes */
        $attributes = $this->akeneoClient->getAttributeApi()->all($paginationSize);
        /** @var [] $metricsSetting */
        $metricsSetting = $this->configHelper->getMetricsColumns(true);

        /**
         * @var int   $index
         * @var array $attribute
         */
        foreach ($attributes as $index => $attribute) {
            /** @var string $attributeCode */
            $attributeCode     = $attribute['code'];
            $attribute['code'] = strtolower($attributeCode);

            if ($attribute['type'] == 'pim_catalog_metric' && in_array(
                    $attributeCode,
                    $metricsSetting
                )) {
                if ($attribute['scopable'] || $attribute['localizable']) {
                    $this->setAdditionalMessage(__('Attribute %1 is scopable or localizable please change configuration at Stores > Configuration > Catalog > Akeneo Connector > Products > Metrics management.', $attributeCode));
                    continue;
                }
                $attribute['type'] .= '_select';
            }
            $this->entitiesHelper->insertDataFromApi($attribute, $this->getCode());
        }
        $index++;

        $this->setAdditionalMessage(
            __('%1 line(s) found', $index)
        );

        /* Remove attribute without a admin store label */
        /** @var string $localeCode */
        $localeCode = $this->configHelper->getDefaultLocale();
        /** @var \Magento\Framework\DB\Select $select */
        $select = $connection->select()->from(
            $tmpTable,
            [
                'label'     => 'labels-' . $localeCode,
                'code'      => 'code',
            ]
        )->where('`labels-' . $localeCode . '` IS NULL');

        /** @var \Zend_Db_Statement_Interface $query */
        $query = $connection->query($select);
        /** @var array $row */
        while (($row = $query->fetch())) {
            if (!isset($row['label']) || $row['label'] === null) {
                $this->setAdditionalMessage(
                    __(
                        'The attribute %1 was not imported because it did not have a translation in admin store language : %2',
                        $row['code'],
                        $localeCode
                    )
                );
                $connection->delete($tmpTable, ['code = ?' => $row['code']]);
            }
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
        $entityTable = $this->entitiesHelper->getTable('eav_attribute');
        /** @var \Magento\Framework\DB\Select $selectExistingEntities */
        $selectExistingEntities = $connection->select()->from($entityTable, 'attribute_id');
        /** @var string[] $existingEntities */
        $existingEntities = array_column($connection->query($selectExistingEntities)->fetchAll(), 'attribute_id');

        $connection->delete(
            $akeneoConnectorTable,
            ['import = ?' => 'attribute', 'entity_id NOT IN (?)' => $existingEntities]
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
        /** @var Select $select */
        $select = $connection->select()->from(
            $this->entitiesHelper->getTable('eav_attribute'),
            [
                'import'    => new Expr('"attribute"'),
                'code'      => 'attribute_code',
                'entity_id' => 'attribute_id',
            ]
        )->where('entity_type_id = ?', $this->getEntityTypeId());

        $connection->query(
            $connection->insertFromSelect(
                $select,
                $this->entitiesHelper->getTable('akeneo_connector_entities'),
                ['import', 'code', 'entity_id'],
                2
            )
        );

        $this->entitiesHelper->matchEntity('code', 'eav_attribute', 'attribute_id', $this->getCode());
    }

    /**
     * Match type with Magento logic
     *
     * @return void
     */
    public function matchType()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var array $columns */
        $columns = $this->attributeHelper->getSpecificColumns();
        /**
         * @var string $name
         * @var array $def
         */
        foreach ($columns as $name => $def) {
            $connection->addColumn($tmpTable, $name, $def['type']);
        }

        /** @var Select $select */
        $select = $connection->select()->from(
            $tmpTable,
            array_merge(
                ['_entity_id', 'type'],
                array_keys($columns)
            )
        );
        /** @var array $data */
        $data = $connection->fetchAssoc($select);
        /**
         * @var int $id
         * @var array $attribute
         */
        foreach ($data as $id => $attribute) {
            /** @var array $type */
            $type = $this->attributeHelper->getType($attribute['type']);

            $connection->update($tmpTable, $type, ['_entity_id = ?' => $id]);
        }
    }

    /**
     * Match family code with Magento group id
     *
     * @return void
     */
    public function matchFamily()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var string $familyAttributeRelationsTable */
        $familyAttributeRelationsTable = $this->entitiesHelper->getTable('akeneo_connector_family_attribute_relations');

        $connection->addColumn($tmpTable, '_attribute_set_id', 'text');
        /** @var string $importTmpTable */
        $importTmpTable = $connection->select()->from($tmpTable, ['code', '_entity_id']);
        /** @var string $queryTmpTable */
        $queryTmpTable = $connection->query($importTmpTable);

        while ($row = $queryTmpTable->fetch()) {
            /** @var string $attributeCode */
            $attributeCode = $row['code'];
            /** @var Select $importRelations */
            $importRelations = $connection->select()->from($familyAttributeRelationsTable, 'family_entity_id')->where(
                $connection->prepareSqlCondition('attribute_code', ['like' => $attributeCode])
            );
            /** @var \Zend_Db_Statement_Interface $queryRelations */
            $queryRelations = $connection->query($importRelations);
            /** @var string $attributeIds */
            $attributeIds = '';
            while ($innerRow = $queryRelations->fetch()) {
                $attributeIds .= $innerRow['family_entity_id'] . ',';
            }
            $attributeIds = rtrim($attributeIds, ',');

            $connection->update($tmpTable, ['_attribute_set_id' => $attributeIds], '_entity_id=' . $row['_entity_id']);
        }
    }

    /**
     * Update or add attributes if not exists
     *
     * @return void
     */
    public function addAttributes()
    {
        /** @var array $columns */
        $columns = $this->attributeHelper->getSpecificColumns();
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());

        /** @var string $adminLang */
        $adminLang = $this->storeHelper->getAdminLang();
        /** @var string $adminLabelColumn */
        $adminLabelColumn = sprintf('labels-%s', $adminLang);

        /** @var Select $import */
        $import = $connection->select()->from($tmpTable);
        /** @var \Zend_Db_Statement_Interface $query */
        $query = $connection->query($import);
        /** @var string[] $mapping */
        $mapping = $this->configHelper->getAttributeMapping();

        while (($row = $query->fetch())) {
            /* Verify attribute type if already present in Magento */
            /** @var string $attributeFrontendInput */
            $attributeFrontendInput = $connection->fetchOne(
                $connection->select()->from(
                    $this->entitiesHelper->getTable('eav_attribute'),
                    ['frontend_input']
                )->where('attribute_code = ?', $row['code'])
            );
            /** @var bool $skipAttribute */
            $skipAttribute = false;
            if ($attributeFrontendInput && $row['frontend_input']) {
                if ($attributeFrontendInput !== $row['frontend_input'] && !in_array($row['code'], $this->excludedAttributes)) {
                    $skipAttribute = true;
                    /* Verify if attribute is mapped to an ignored attribute */
                    if (is_array($mapping)) {
                        foreach ($mapping as $match) {
                            if (in_array($match['magento_attribute'], $this->excludedAttributes) && $row['code'] == $match['akeneo_attribute']) {
                                $skipAttribute = false;
                            }
                        }
                    }
                }
            }

            if ($skipAttribute === true) {
                /** @var string $message */
                $message = __('The attribute %1 was skipped because its type is not the same between Akeneo and Magento. Please delete it in Magento and try a new import', $row['code']);
                $this->setAdditionalMessage($message);

                continue;
            }

            /* Insert base data (ignore if already exists) */
            /** @var string[] $values */
            $values = [
                'attribute_id'   => $row['_entity_id'],
                'entity_type_id' => $this->getEntityTypeId(),
                'attribute_code' => $row['code'],
            ];
            $connection->insertOnDuplicate(
                $this->entitiesHelper->getTable('eav_attribute'),
                $values,
                array_keys($values)
            );

            $values = [
                'attribute_id' => $row['_entity_id'],
            ];
            $connection->insertOnDuplicate(
                $this->entitiesHelper->getTable('catalog_eav_attribute'),
                $values,
                array_keys($values)
            );

            /* Retrieve default admin label */
            /** @var string $frontendLabel */
            $frontendLabel = __('Unknown');
            if (!empty($row[$adminLabelColumn])) {
                $frontendLabel = $row[$adminLabelColumn];
            }

            /* Retrieve attribute scope */
            /** @var int $global */
            $global = ScopedAttributeInterface::SCOPE_GLOBAL; // Global
            if ($row['scopable'] == 1) {
                $global = ScopedAttributeInterface::SCOPE_WEBSITE; // Website
            }
            if ($row['localizable'] == 1) {
                $global = ScopedAttributeInterface::SCOPE_STORE; // Store View
            }
            /** @var array $data */
            $data = [
                'entity_type_id' => $this->getEntityTypeId(),
                'attribute_code' => $row['code'],
                'frontend_label' => $frontendLabel,
                'is_global'      => $global,
            ];
            foreach ($columns as $column => $def) {
                if (!$def['only_init']) {
                    $data[$column] = $row[$column];
                }
            }
            /** @var array $defaultValues */
            $defaultValues = [];
            if ($row['_is_new'] == 1) {
                $defaultValues = [
                    'backend_table'                 => null,
                    'frontend_class'                => null,
                    'is_required'                   => 0,
                    'is_user_defined'               => 1,
                    'default_value'                 => null,
                    'is_unique'                     => $row['unique'],
                    'note'                          => null,
                    'is_visible'                    => 1,
                    'is_system'                     => 1,
                    'input_filter'                  => null,
                    'multiline_count'               => 0,
                    'validate_rules'                => null,
                    'data_model'                    => null,
                    'sort_order'                    => 0,
                    'is_used_in_grid'               => 0,
                    'is_visible_in_grid'            => 0,
                    'is_filterable_in_grid'         => 0,
                    'is_searchable_in_grid'         => 0,
                    'frontend_input_renderer'       => null,
                    'is_searchable'                 => 0,
                    'is_filterable'                 => 0,
                    'is_comparable'                 => 0,
                    'is_visible_on_front'           => 0,
                    'is_wysiwyg_enabled'            => 0,
                    'is_html_allowed_on_front'      => 0,
                    'is_visible_in_advanced_search' => 0,
                    'is_filterable_in_search'       => 0,
                    'used_in_product_listing'       => 0,
                    'used_for_sort_by'              => 0,
                    'apply_to'                      => null,
                    'position'                      => 0,
                    'is_used_for_promo_rules'       => 0,
                ];

                foreach (array_keys($columns) as $column) {
                    $data[$column] = $row[$column];
                }
            }

            $data = array_merge($defaultValues, $data);
            $this->eavSetup->updateAttribute(
                $this->getEntityTypeId(),
                $row['_entity_id'],
                $data,
                null,
                0
            );

            /* Add Attribute to group and family */
            if ($row['_attribute_set_id'] && $row['group']) {
                $attributeSetIds = explode(',', $row['_attribute_set_id']);

                if (is_numeric($row['group'])) {
                    $row['group'] = 'PIM' . $row['group'];
                }

                foreach ($attributeSetIds as $attributeSetId) {
                    if (is_numeric($attributeSetId)) {
                        /* Verify if the group already exists */
                        /** @var int $setId */
                        $setId = $this->eavSetup->getAttributeSetId($this->getEntityTypeId(), $attributeSetId);
                        /** @var int $groupId */
                        $groupId = $this->eavSetup->getSetup()->getTableRow(
                            'eav_attribute_group',
                            'attribute_group_name',
                            ucfirst($row['group']),
                            'attribute_group_id',
                            'attribute_set_id',
                            $setId
                        );
                        /** @var bool $akeneoGroup */
                        $akeneoGroup = false;
                        /* Test if the default group was created instead */
                        if (!$groupId) {
                            $akeneoGroup = true;
                            $groupId     = $this->eavSetup->getSetup()->getTableRow(
                                'eav_attribute_group',
                                'attribute_group_name',
                                self::DEFAULT_ATTRIBUTE_SET_NAME,
                                'attribute_group_id',
                                'attribute_set_id',
                                $setId
                            );
                        }

                        /** @var bool $existingAttribute */
                        $existingAttribute = $connection->fetchOne(
                            $connection->select()->from(
                                $this->entitiesHelper->getTable('eav_entity_attribute'),
                                ['COUNT(*)']
                            )->where('attribute_set_id = ?', $setId)->where('attribute_id = ?', $row['_entity_id'])
                        );
                        /* The attribute was already imported at least once, skip it */
                        if ($existingAttribute) {
                            continue;
                        }
                        if ($groupId) {
                            /* The group already exists, update it */
                            /** @var string[] $dataGroup */
                            $dataGroup = [
                                'attribute_set_id'     => $setId,
                                'attribute_group_name' => ucfirst($row['group']),
                            ];
                            if ($akeneoGroup) {
                                $dataGroup = [
                                    'attribute_set_id'     => $setId,
                                    'attribute_group_name' => self::DEFAULT_ATTRIBUTE_SET_NAME,
                                ];
                            }

                            $this->eavSetup->updateAttributeGroup(
                                $this->getEntityTypeId(),
                                $setId,
                                $groupId,
                                $dataGroup
                            );

                            $this->eavSetup->addAttributeToSet(
                                $this->getEntityTypeId(),
                                $attributeSetId,
                                $groupId,
                                $row['_entity_id']
                            );
                        } else {
                            /* The group doesn't exists, create it */
                            $this->eavSetup->addAttributeGroup(
                                $this->getEntityTypeId(),
                                $attributeSetId,
                                self::DEFAULT_ATTRIBUTE_SET_NAME
                            );

                            $this->eavSetup->addAttributeToSet(
                                $this->getEntityTypeId(),
                                $attributeSetId,
                                self::DEFAULT_ATTRIBUTE_SET_NAME,
                                $row['_entity_id']
                            );
                        }
                    }
                }
            }

            /* Add store labels */
            /** @var array $stores */
            $stores = $this->storeHelper->getStores('lang');
            /**
             * @var string $lang
             * @var array $data
             */
            foreach ($stores as $lang => $data) {
                if (isset($row['labels-'.$lang])) {
                    /** @var array $store */
                    foreach ($data as $store) {
                        /** @var string $exists */
                        $exists = $connection->fetchOne(
                            $connection->select()->from($this->entitiesHelper->getTable('eav_attribute_label'))->where(
                                'attribute_id = ?',
                                $row['_entity_id']
                            )->where('store_id = ?', $store['store_id'])
                        );

                        if ($exists) {
                            /** @var array $values */
                            $values = [
                                'value' => $row['labels-'.$lang],
                            ];
                            /** @var array $where */
                            $where  = [
                                'attribute_id = ?' => $row['_entity_id'],
                                'store_id = ?'     => $store['store_id'],
                            ];

                            $connection->update($this->entitiesHelper->getTable('eav_attribute_label'), $values, $where);
                        } else {
                            $values = [
                                'attribute_id' => $row['_entity_id'],
                                'store_id'     => $store['store_id'],
                                'value'        => $row['labels-'.$lang],
                            ];
                            $connection->insert($this->entitiesHelper->getTable('eav_attribute_label'), $values);
                        }
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
        /** @var string[] $types */
        $types = [
            \Magento\Framework\App\Cache\Type\Block::TYPE_IDENTIFIER,
            \Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER,
        ];
        /** @var string $type */
        foreach ($types as $type) {
            $this->cacheTypeList->cleanType($type);
        }

        $this->setMessage(
            __('Cache cleaned for: %1', join(', ', $types))
        );
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
}
