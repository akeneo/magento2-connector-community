<?php

namespace Akeneo\Connector\Job;

use Akeneo\Pim\ApiClient\Pagination\PageInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Eav\Model\Config;
use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Helper\Import\Entities as EntitiesHelper;
use Akeneo\Connector\Helper\Output as OutputHelper;

/**
 * Class ProductModel
 *
 * @category  Class
 * @package   Akeneo\Connector\Job
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class ProductModel extends Import
{
    /**
     * @var int BATCH_SIZE
     */
    const BATCH_SIZE = 500;

    /**
     * This variable contains a string value
     *
     * @var string $code
     */
    protected $code = 'product_model';
    /**
     * This variable contains a string value
     *
     * @var string $name
     */
    protected $name = 'Product Model';
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
     * ProductModel constructor
     *
     * @param OutputHelper                        $outputHelper
     * @param ManagerInterface                    $eventManager
     * @param Authenticator                       $authenticator
     * @param \Akeneo\Connector\Helper\Import\Product $entitiesHelper
     * @param ConfigHelper                        $configHelper
     * @param Config                              $eavConfig
     * @param array                               $data
     */
    public function __construct(
        OutputHelper $outputHelper,
        ManagerInterface $eventManager,
        Authenticator $authenticator,
        \Akeneo\Connector\Helper\Import\Product $entitiesHelper,
        ConfigHelper $configHelper,
        Config $eavConfig,
        array $data = []
    ) {
        parent::__construct($outputHelper, $eventManager, $authenticator, $data);

        $this->entitiesHelper  = $entitiesHelper;
        $this->configHelper    = $configHelper;
        $this->eavConfig       = $eavConfig;
    }

    /**
     * Create temporary table
     *
     * @return void
     */
    public function createTable()
    {
        /** @var PageInterface $productModels */
        $productModels = $this->akeneoClient->getProductModelApi()->listPerPage(1);
        /** @var array $productModel */
        $productModel = $productModels->getItems();
        if (empty($productModel)) {
            $this->setMessage(__('No results from Akeneo'));
            $this->stop(1);

            return;
        }
        $productModel = reset($productModel);
        $this->entitiesHelper->createTmpTableFromApi($productModel, $this->getCode());
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
        /** @var ResourceCursorInterface $productModels */
        $productModels = $this->akeneoClient->getProductModelApi()->all($paginationSize);
        /**
         * @var int   $index
         * @var array $productModel
         */
        foreach ($productModels as $index => $productModel) {
            $this->entitiesHelper->insertDataFromApi($productModel, $this->getCode());
        }
        $index++;
        $this->setMessage(
            __('%1 line(s) found', $index)
        );
    }

    /**
     * Remove columns from product model table
     *
     * @return void
     */
    public function removeColumns()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var array $except */
        $except = ['code', 'axis'];
        /** @var array $variantTable */
        $variantTable = $this->entitiesHelper->getTable('akeneo_connector_product_model');
        /** @var array $columns */
        $columns = array_keys($connection->describeTable($variantTable));
        /** @var string $column */
        foreach ($columns as $column) {
            if (in_array($column, $except)) {
                continue;
            }
            $connection->dropColumn($variantTable, $column);
        }
    }

    /**
     * Add columns to product model table
     *
     * @return void
     */
    public function addColumns()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var array $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var array $except */
        $except = ['code', 'axis', 'type', '_entity_id', '_is_new'];
        /** @var array $variantTable */
        $variantTable = $this->entitiesHelper->getTable('akeneo_connector_product_model');
        /** @var array $columns */
        $columns = array_keys($connection->describeTable($tmpTable));
        /** @var string $column */
        foreach ($columns as $column) {
            if (in_array($column, $except)) {
                continue;
            }
            $connection->addColumn($variantTable, $this->_columnName($column), 'text');
        }
        if (!$connection->tableColumnExists($tmpTable, 'axis')) {
            $connection->addColumn($tmpTable, 'axis', [
                'type' => 'text',
                'length' => 255,
                'default' => '',
                'COMMENT' => ' '
            ]);
        }
    }

    /**
     * sliceInsertOnDuplicate slices InsertOnDuplicate into separate updates to
     * prevent MySQL hitting row size max.
     *
     * @param string $table The table to insert data into.
     * @param array $data Column-value pairs or array of column-value pairs.
     * @return void
     */
    public function sliceInsertOnDuplicate($table, array $data, array $fields = [])
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var int $updateLength */
        $updateLength = $this->configHelper->getAdvancedPmUpdateLength();
        foreach ($data as $row) {
            // create empty row with primaryKey if not present
            $primaryKey = $row['code'];
            if (!$connection->select()->from($table)->where('code = ?', $primaryKey)) {
                $connection->insert($table, ['code = ?', $primaryKey]);
            }
            unset($row['code']);
            // slice the data in separate updates
            while (count($row)) {
                $sliceSize = 0;
                $slice = [];
                foreach ($row as $column => $value) {
                    $sliceSize += strlen($column) + strlen($value);
                    // Ignore "Update Length" on first column update to prevent
                    // possible endless loop if a column is bigger.
                    if (count($slice) && ($sliceSize >= $updateLength)) {
                        break;
                    }
                    $slice[$column] = $value;
                    unset($row[$column]);
                }
                $connection->update($table, $slice, ['code = ?' => $primaryKey]);
            }
        }
        return;
    }

    /**
     * Add or update data in product model table
     *
     * @return void
     */
    public function updateData()
    {
        /** @var AdapterInterface $connection */
        $connection = $this->entitiesHelper->getConnection();
        /** @var int $updateLength */
        $batchSize = $this->configHelper->getAdvancedPmBatchSize();
        /** @var array $tmpTable */
        $tmpTable = $this->entitiesHelper->getTableName($this->getCode());
        /** @var array $variantTable */
        $variantTable = $this->entitiesHelper->getTable('akeneo_connector_product_model');
        /** @var array $variant */
        $variant = $connection->query(
            $connection->select()->from($tmpTable)
        );
        /** @var array $attributes */
        $attributes = $connection->fetchPairs(
            $connection->select()->from(
                $this->entitiesHelper->getTable('eav_attribute'),
                ['attribute_code', 'attribute_id']
            )->where('entity_type_id = ?', $this->getEntityTypeId())
        );
        /** @var array $columns */
        $columns = array_keys($connection->describeTable($tmpTable));
        /** @var array $values */
        $values = [];
        /** @var int $i */
        $i = 0;
        /** @var array $keys */
        $keys = [];
        while (($row = $variant->fetch())) {
            $values[$i] = [];
            /** @var int $column */
            foreach ($columns as $column) {
                if ($connection->tableColumnExists($variantTable, $this->_columnName($column))) {
                    if ($column != 'axis') {
                        $values[$i][$this->_columnName($column)] = $row[$column];
                    }
                    if ($column == 'axis' && !$connection->tableColumnExists($tmpTable, 'family_variant')) {
                        /** @var array $axisAttributes */
                        $axisAttributes = explode(',', $row['axis']);
                        /** @var array $axis */
                        $axis = [];
                        /** @var string $code */
                        foreach ($axisAttributes as $code) {
                            if (isset($attributes[$code])) {
                                $axis[] = $attributes[$code];
                            }
                        }
                        $values[$i][$column] = join(',', $axis);
                    }
                    $keys = array_keys($values[$i]);
                }
            }
            $i++;
            if (count($values) > $batchSize) {
                if (0 == $batchSize) {
                    $this->sliceInsertOnDuplicate($variantTable, $values);
                } else {
                    $connection->insertOnDuplicate($variantTable, $values, $keys);
                }
                $values = [];
                $i      = 0;
            }
        }
        if (count($values) > 0) {
            if (0 == $batchSize) {
                $this->sliceInsertOnDuplicate($variantTable, $values);
            } else {
                $connection->insertOnDuplicate($variantTable, $values, $keys);
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
     * Replace column name
     *
     * @param string $column
     *
     * @return string
     */
    protected function _columnName($column)
    {
        /** @var array $matches */
        $matches = [
            'label' => 'name',
        ];
        /**
         * @var string $name
         * @var string $replace
         */
        foreach ($matches as $name => $replace) {
            if (preg_match('/^' . $name . '/', $column)) {
                /** @var string $column */
                $column = preg_replace('/^' . $name . '/', $replace, $column);
            }
        }

        return $column;
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
