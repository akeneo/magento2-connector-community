<?php

declare(strict_types=1);

namespace Akeneo\Connector\Helper;

use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\Import\FamilyVariant as FamilyVariantHelper;
use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Zend_Db_Expr as Expr;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class FamilyVariant
{
    /**
     * @var int MAX_AXIS_NUMBER
     */
    public const MAX_AXIS_NUMBER = 5;
    /**
     * @var string CODE_JOB
     */
    public const CODE_JOB = 'family_variant';
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
     * @param TypeListInterface   $cacheTypeList
     * @param Config              $eavConfig
     */
    public function __construct(
        FamilyVariantHelper $entitiesHelper,
        ConfigHelper $configHelper,
        TypeListInterface $cacheTypeList,
        Config $eavConfig
    ) {
        $this->configHelper   = $configHelper;
        $this->entitiesHelper = $entitiesHelper;
        $this->cacheTypeList  = $cacheTypeList;
        $this->eavConfig      = $eavConfig;
    }

    /**
     * Create temporary table
     *
     * @param AkeneoPimClientInterface $akeneoClient
     * @param string $family
     *
     * @return array|string[]
     */
    public function createTable($akeneoClient, $family)
    {
        /** @var string[] $messages */
        $messages = [];
        /** @var string|int $paginationSize */
        $paginationSize = $this->configHelper->getPaginationSize();

        /** @var array $familyApi */
        $familyApi = $akeneoClient->getFamilyApi()->get($family);
        /** @var bool $hasVariant */
        $hasVariant = false;
        /** @var PageInterface $variantFamilies */
        $variantFamilies = $akeneoClient->getFamilyVariantApi()->listPerPage($familyApi['code'], 1);
        if (count($variantFamilies->getItems()) > 0) {
            $hasVariant = true;
        }
        if (!$hasVariant) {
            $messages[] = ['message' => __('There is no family variant in Akeneo'), 'status' => false];

            return $messages;
        }
        /** @var array $variantFamily */
        $variantFamily = $variantFamilies->getItems();
        if (empty($variantFamily)) {
            $messages[] = ['message' => __('No results from Akeneo'), 'status' => false];

            return $messages;
        }
        $variantFamily = reset($variantFamily);
        $this->entitiesHelper->createTmpTableFromApi($variantFamily, 'family_variant');

        return $messages;
    }

    /**
     * Insert data into temporary table
     *
     * @param AkeneoPimClientInterface $akeneoClient
     * @param string                   $family
     *
     * @return void
     */
    public function insertData($akeneoClient, $family)
    {
        /** @var string[] $messages */
        $messages = [];
        /** @var string|int $paginationSize */
        $paginationSize = $this->configHelper->getPaginationSize();
        /** @var array $familyApi */
        $familyApi = $akeneoClient->getFamilyApi()->get($family);
        /** @var string $familyCode */
        $familyCode = $familyApi['code'];
        /** @var int $count */
        $count = $this->insertFamilyVariantData($familyCode, $paginationSize, $akeneoClient);
        if ($count === 0) {
            $messages[] = ['message' => __('No Line found'), 'status' => false];

            return $messages;
        }
        $messages[] = ['message' => __('%1 line(s) found', $count), 'status' => true];

        return $messages;
    }

    /**
     * Update Axis column
     *
     * @return void
     */
    public function updateAxis()
    {
        /** @var string[] $messages */
        $messages = [];
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName(self::CODE_JOB);

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
        /** @var array $columns */
        $columns = [];
        /** @var int $i */
        for ($i = 1; $i <= self::MAX_AXIS_NUMBER; $i++) {
            $columns[] = 'variant-axes_' . $i;
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
            $update = 'TRIM(BOTH "," FROM CONCAT(COALESCE(`' . join(
                '`, \'\' ), "," , COALESCE(`',
                $columns
            ) . '`, \'\')))';
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
            $axisAttributes = explode(',', $row['_axis'] ?? '');
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

        return $messages;
    }

    /**
     * Update Product Model
     *
     * @return void
     */
    public function updateProductModel()
    {
        /** @var string[] $messages */
        $messages = [];
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var string $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName(self::CODE_JOB);
        /** @var Select $query */
        $query = $connection->select()->from(false, ['axis' => 'f._axis'])->joinLeft(
            ['f' => $tmpTable],
            'p.family_variant = f.code',
            []
        );

        $connection->query(
            $connection->updateFromSelect(
                $query,
                ['p' => $this->entitiesHelper->getTable($this->entitiesHelper->getTableName('product_model'))]
            )
        );

        return $messages;
    }

    /**
     * Drop temporary table
     *
     * @return void
     */
    public function dropTable()
    {
        $this->entitiesHelper->dropTable(self::CODE_JOB);
    }

    /**
     * Insert the FamilyVariant data in the temporary table for each family
     *
     * @param string                   $familyCode
     * @param int                      $paginationSize
     * @param AkeneoPimClientInterface $akeneoClient
     *
     * @return int
     */
    protected function insertFamilyVariantData($familyCode, $paginationSize, $akeneoClient)
    {
        /** @var ResourceCursorInterface $families */
        $families = $akeneoClient->getFamilyVariantApi()->all($familyCode, $paginationSize);
        /**
         * @var int   $index
         * @var array $family
         */
        foreach ($families as $index => $family) {
            $this->entitiesHelper->insertDataFromApi($family, self::CODE_JOB);
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
