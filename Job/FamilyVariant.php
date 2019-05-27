<?php

namespace Akeneo\Connector\Job;

use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Cache\Type\Block as BlockCacheType;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface;
use Magento\PageCache\Model\Cache\Type as PageCacheType;
use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\Import\FamilyVariant as FamilyVariantHelper;
use Zend_Db_Expr as Expr;
use Akeneo\Connector\Helper\Output as OutputHelper;

/**
 * Class FamilyVariant
 *
 * @category  Class
 * @package   Akeneo\Connector\Job
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class FamilyVariant extends Import
{
    /**
     * @var int MAX_AXIS_NUMBER
     */
    const MAX_AXIS_NUMBER = 5;
    /**
     * This variable contains a string value
     *
     * @var string $code
     */
    protected $code = 'family_variant';
    /**
     * This variable contains a string value
     *
     * @var string $name
     */
    protected $name = 'Family Variant';
    /**
     * This variable contains an FamilyVariantHelper
     *
     * @var FamilyVariantHelper $entitiesHelper
     */
    protected $entitiesHelper;
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;
    /**
     * This variable contains a TypeListInterface
     *
     * @var TypeListInterface $cacheTypeList
     */
    protected $cacheTypeList;
    /**
     * This variable contains a Config
     *
     * @var Config $eavConfig
     */
    protected $eavConfig;

    /**
     * FamilyVariant constructor
     *
     * @param FamilyVariantHelper $entitiesHelper
     * @param ConfigHelper        $configHelper
     * @param OutputHelper        $outputHelper
     * @param ManagerInterface    $eventManager
     * @param Authenticator       $authenticator
     * @param TypeListInterface   $cacheTypeList
     * @param Config              $eavConfig
     * @param array               $data
     */
    public function __construct(
        FamilyVariantHelper $entitiesHelper,
        ConfigHelper $configHelper,
        OutputHelper $outputHelper,
        ManagerInterface $eventManager,
        Authenticator $authenticator,
        TypeListInterface $cacheTypeList,
        Config $eavConfig,
        array $data = []
    ) {
        parent::__construct($outputHelper, $eventManager, $authenticator, $data);

        $this->configHelper   = $configHelper;
        $this->entitiesHelper = $entitiesHelper;
        $this->cacheTypeList  = $cacheTypeList;
        $this->eavConfig      = $eavConfig;
    }

    /**
     * Create temporary table
     *
     * @return void
     */
    public function createTable()
    {
        /** @var PageInterface $families */
        $families = $this->akeneoClient->getFamilyApi()->all();
        /** @var bool $hasVariant */
        $hasVariant = false;
        /** @var array $family */
        foreach ($families as $family) {
            /** @var PageInterface $variantFamilies */
            $variantFamilies = $this->akeneoClient->getFamilyVariantApi()->listPerPage($family['code'], 1);
            if (count($variantFamilies->getItems()) > 0) {
                $hasVariant = true;

                break;
            }
        }
        if (!$hasVariant) {
            $this->setMessage(__('There is no family variant in Akeneo'));
            $this->stop();

            return;
        }
        /** @var array $variantFamily */
        $variantFamily = $variantFamilies->getItems();
        if (empty($variantFamily)) {
            $this->setMessage(__('No results retrieved from Akeneo'));
            $this->stop(1);

            return;
        }
        $variantFamily = reset($variantFamily);
        $this->entitiesHelper->createTmpTableFromApi($variantFamily, $this->getCode());
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
        /** @var PageInterface $families */
        $families = $this->akeneoClient->getFamilyApi()->all($paginationSize);
        /** @var int $count */
        $count = 0;
        /** @var array $family */
        foreach ($families as $family) {
            /** @var string $familyCode */
            $familyCode = $family['code'];
            $count      += $this->insertFamilyVariantData($familyCode, $paginationSize);
        }

        $this->setMessage(
            __('%1 line(s) found', $count)
        );
    }

    /**
     * Update Axis column
     *
     * @return void
     */
    public function updateAxis()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());

        $connection->addColumn($tmpTable, '_axis', [
            'type' => 'text',
            'length' => 255,
            'default' => '',
            'COMMENT' => ' '
        ]);
        /** @var array $columns */
        $columns = [];
        /** @var int $i */
        for ($i = 1; $i <= self::MAX_AXIS_NUMBER; $i++) {
            $columns[] = 'variant-axes_'.$i;
        }
        /**
         * @var int    $key
         * @var string $column
         */
        foreach ($columns as $key => $column) {
            if (!$connection->tableColumnExists($tmpTable, $column)) {
                unset($columns[$key]);
            }
        }

        if (!empty($columns)) {
            /** @var string $update */
            $update = 'TRIM(BOTH "," FROM CONCAT(COALESCE(`' . join('`, \'\' ), "," , COALESCE(`', $columns) . '`, \'\')))';
            $connection->update($tmpTable, ['_axis' => new Expr($update)]);
        }
        /** @var \Zend_Db_Statement_Interface $variantFamily */
        $variantFamily = $connection->query(
            $connection->select()->from($tmpTable)
        );
        /** @var array $attributes */
        $attributes = $connection->fetchPairs(
            $connection->select()->from(
                $this->entitiesHelper->getTable('eav_attribute'),
                ['attribute_code', 'attribute_id']
            )->where('entity_type_id = ?', $this->getEntityTypeId())
        );
        while (($row = $variantFamily->fetch())) {
            /** @var array $axisAttributes */
            $axisAttributes = explode(',', $row['_axis']);
            /** @var array $axis */
            $axis = [];
            /** @var string $code */
            foreach ($axisAttributes as $code) {
                if (isset($attributes[$code])) {
                    $axis[] = $attributes[$code];
                }
            }

            $connection->update($tmpTable, ['_axis' => join(',', $axis)], ['code = ?' => $row['code']]);
        }
    }

    /**
     * Update Product Model
     *
     * @return void
     */
    public function updateProductModel()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var Select $query */
        $query = $connection->select()->from(false, ['axis' => 'f._axis'])->joinLeft(
            ['f' => $tmpTable],
            'p.family_variant = f.code',
            []
        );

        $connection->query(
            $connection->updateFromSelect($query, ['p' => $this->entitiesHelper->getTable('akeneo_connector_product_model')])
        );
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
     * Insert the FamilyVariant data in the temporary table for each family
     *
     * @param string $familyCode
     * @param int    $paginationSize
     *
     * @return int
     */
    protected function insertFamilyVariantData($familyCode, $paginationSize)
    {
        /** @var ResourceCursorInterface $families */
        $families = $this->akeneoClient->getFamilyVariantApi()->all($familyCode, $paginationSize);
        /**
         * @var int   $index
         * @var array $family
         */
        foreach ($families as $index => $family) {
            $this->entitiesHelper->insertDataFromApi($family, $this->getCode());
        }

        if (!isset($index)) {
            return 0;
        }
        $index++;

        return $index;
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
