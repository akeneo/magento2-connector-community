<?php

namespace Akeneo\Connector\Job;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\App\Cache\Type\Block as BlockCacheType;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Eav\Model\Config;
use Magento\PageCache\Model\Cache\Type as PageCacheType;
use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\Import\Attribute as AttributeHelper;
use Akeneo\Connector\Helper\Import\Entities as EntitiesHelper;
use Akeneo\Connector\Helper\Import\Option as OptionHelper;
use Akeneo\Connector\Helper\Output as OutputHelper;
use Akeneo\Connector\Helper\Store as StoreHelper;
use \Zend_Db_Expr as Expr;

/**
 * Class Option
 *
 * @category  Class
 * @package   Akeneo\Connector\Job
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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
     * This variable contains an EntitiesHelper
     *
     * @var EntitiesHelper $entitiesHelper
     */
    protected $entitiesHelper;
    /**
     * This variable contains an OptionHelper
     *
     * @var OptionHelper $optionHelper
     */
    protected $optionHelper;
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
     * Option constructor
     *
     * @param OutputHelper      $outputHelper
     * @param ManagerInterface  $eventManager
     * @param Authenticator     $authenticator
     * @param EntitiesHelper    $entitiesHelper
     * @param OptionHelper      $optionHelper
     * @param ConfigHelper      $configHelper
     * @param Config            $eavConfig
     * @param AttributeHelper   $attributeHelper
     * @param TypeListInterface $cacheTypeList
     * @param StoreHelper       $storeHelper
     * @param EavSetup          $eavSetup
     * @param array             $data
     *
     * @throws LocalizedException
     */
    public function __construct(
        OutputHelper $outputHelper,
        ManagerInterface $eventManager,
        Authenticator $authenticator,
        EntitiesHelper $entitiesHelper,
        OptionHelper $optionHelper,
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
        $this->optionHelper    = $optionHelper;
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
        $attributes = $this->getAllAttributes();
        /** @var bool $hasOptions */
        $hasOptions = false;
        /** @var array $attribute */
        foreach ($attributes as $attribute) {
            if ($attribute['type'] == 'pim_catalog_multiselect' || $attribute['type'] == 'pim_catalog_simpleselect') {
                if (!$this->akeneoClient) {
                    $this->akeneoClient = $this->getAkeneoClient();
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
            $this->setMessage(__('No options found'));
            $this->stop();

            return;
        }
        /** @var array $option */
        $option = $options->getItems();
        if (empty($option)) {
            $this->setMessage(__('No results from Akeneo'));
            $this->stop(1);

            return;
        }
        $option = reset($option);
        $this->entitiesHelper->createTmpTableFromApi($option, $this->getCode());
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
        /** @var PageInterface $attributes */
        $attributes = $this->getAllAttributes();
        /** @var int $lines */
        $lines = 0;
        /** @var array $attribute */
        foreach ($attributes as $attribute) {
            if ($attribute['type'] == 'pim_catalog_multiselect' || $attribute['type'] == 'pim_catalog_simpleselect') {
                $lines += $this->processAttributeOption($attribute['code'], $paginationSize);
            }
        }
        $this->setMessage(
            __('%1 line(s) found', $lines)
        );

        /* Remove option without an admin store label */
        /** @var string $localeCode */
        $localeCode = $this->configHelper->getDefaultLocale();
        /** @var \Magento\Framework\DB\Select $select */
        $select = $connection->select()->from(
            $tmpTable,
            [
                'label'     => 'labels-' . $localeCode,
                'code'      => 'code',
                'attribute' => 'attribute',
            ]
        )->where('`labels-' . $localeCode . '` IS NULL');
        /** @var \Zend_Db_Statement_Interface $query */
        $query = $connection->query($select);
        /** @var array $row */
        while (($row = $query->fetch())) {
            if (!isset($row['label']) || $row['label'] === null) {
                $connection->delete($tmpTable, ['code = ?' => $row['code'], 'attribute = ?' => $row['attribute']]);
                $this->setAdditionalMessage(
                    __(
                        'The option %1 from attribute %2 was not imported because it did not have a translation in admin store language : %3',
                        $row['code'],
                        $row['attribute'],
                        $localeCode
                    )
                );
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
        $entityTable = $this->entitiesHelper->getTable('eav_attribute_option');
        /** @var \Magento\Framework\DB\Select $selectExistingEntities */
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
        $this->optionHelper->matchEntity('code', 'eav_attribute_option', 'option_id', $this->getCode(), 'attribute');
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
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
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
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var array $stores */
        $stores = $this->storeHelper->getStores('lang');
        /**
         * @var string $local
         * @var array  $data
         */
        foreach ($stores as $local => $data) {
            if (!$connection->tableColumnExists($tmpTable, 'labels-'.$local)) {
                continue;
            }
            /** @var array $store */
            foreach ($data as $store) {
                /** @var Select $options */
                $options = $connection->select()->from(
                        ['a' => $tmpTable],
                        [
                            'option_id' => '_entity_id',
                            'store_id'  => new Expr($store['store_id']),
                            'value'     => 'labels-'.$local,
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
            BlockCacheType::TYPE_IDENTIFIER,
            PageCacheType::TYPE_IDENTIFIER,
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
     * Retrieve options for the given attribute and insert their data in the temporary table
     *
     * @param string $attributeCode
     * @param int $paginationSize
     *
     * @return int
     */
    protected function processAttributeOption($attributeCode, $paginationSize)
    {
        if (!$this->akeneoClient) {
            $this->akeneoClient = $this->getAkeneoClient();
        }
        /** @var ResourceCursorInterface $options */
        $options = $this->akeneoClient->getAttributeOptionApi()->all($attributeCode, $paginationSize);
        /** @var int $index */
        $index = 0;
        /** @var array $option */
        foreach ($options as $index => $option) {
            $this->entitiesHelper->insertDataFromApi($option, $this->getCode());
        }
        $index++;

        return $index;
    }

    /**
     * Get all attributes from the API
     *
     * @return ResourceCursorInterface|mixed
     */
    public function getAllAttributes()
    {
        if (!$this->attributes) {
            if (!$this->akeneoClient) {
                $this->akeneoClient = $this->getAkeneoClient();
            }
            $this->attributes = $this->akeneoClient->getAttributeApi()->all();
        }

        return $this->attributes;
    }
}
