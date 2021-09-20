<?php

namespace Akeneo\Connector\Setup;

use Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field\Configurable as TypeField;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Class UpgradeSchema
 *
 * @category  Class
 * @package   Akeneo\Connector\Setup
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * This variable contains a ScopeConfigInterface
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;
    /**
     * Resource Config
     *
     * @var ConfigInterface $resourceConfig
     */
    protected $resourceConfig;
    /**
     * This variable contains a Json
     *
     * @var Json $jsonSerializer
     */
    protected $jsonSerializer;

    /**
     * UpgradeSchema constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigInterface      $resourceConfig
     * @param Json                 $jsonSerializer
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ConfigInterface $resourceConfig,
        Json $jsonSerializer
    ) {
        $this->scopeConfig    = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->jsonSerializer = $jsonSerializer;
    }

    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        /** @var SchemaSetupInterface $installer */
        $installer = $setup;

        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.0', '<')) {
            $setup->getConnection()->addIndex(
                $installer->getTable('eav_attribute_option_value'),
                $installer->getIdxName(
                    'eav_attribute_option_value',
                    ['option_id', 'store_id'],
                    AdapterInterface::INDEX_TYPE_UNIQUE
                ),
                ['option_id', 'store_id'],
                AdapterInterface::INDEX_TYPE_UNIQUE
            );
        }

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            /** @var string $configurable */
            $configurable = $this->scopeConfig->getValue(ConfigHelper::PRODUCT_CONFIGURABLE_ATTRIBUTES);

            if ($configurable) {
                /** @var mixed[] $configurable */
                $configurable = $this->jsonSerializer->unserialize($configurable);

                if (!is_array($configurable)) {
                    $configurable = [];
                }

                /** @var mixed[] $attribute */
                foreach ($configurable as $key => $attribute) {
                    if (!isset($attribute['attribute'], $attribute['value'])) {
                        continue;
                    }

                    if (strlen($attribute['value'])) {
                        $configurable[$key]['type'] = TypeField::TYPE_VALUE;
                    }

                    if (!strlen($attribute['value'])) {
                        $configurable[$key]['type'] = TypeField::TYPE_DEFAULT;
                    }
                }

                $this->resourceConfig->saveConfig(
                    ConfigHelper::PRODUCT_CONFIGURABLE_ATTRIBUTES,
                    $this->jsonSerializer->serialize($configurable)
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            if ($setup->getConnection()->isTableExists($setup->getTable('akeneo_connector_product_model'))) {
                $setup->getConnection()->dropTable($setup->getTable('akeneo_connector_product_model'));
            }
        }

        if (version_compare($context->getVersion(), '1.0.3', '<')) {
            /** @var string $configurable */
            $configurable = $this->scopeConfig->getValue(ConfigHelper::PRODUCT_CONFIGURABLE_ATTRIBUTES);

            if ($configurable) {
                /** @var mixed[] $configurable */
                $configurable = $this->jsonSerializer->unserialize($configurable);

                if (!is_array($configurable)) {
                    $configurable = [];
                }

                /** @var mixed[] $attribute */
                foreach ($configurable as $key => $attribute) {
                    if (!isset($attribute['attribute'], $configurable[$key]['type'])) {
                        continue;
                    }

                    if ($configurable[$key]['type'] == TypeField::TYPE_DEFAULT) {
                        unset($configurable[$key]);
                    }
                }

                $this->resourceConfig->saveConfig(
                    ConfigHelper::PRODUCT_CONFIGURABLE_ATTRIBUTES,
                    $this->jsonSerializer->serialize($configurable)
                );
            }
        }

        if (version_compare($context->getVersion(), '1.0.4', '<')) {
            /**
             * Create table 'akeneo_connector_job'
             */
            /** @var Table $table */
            $table = $installer->getConnection()->newTable($installer->getTable('akeneo_connector_job'))->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Job ID'
            )->addColumn(
                'code',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Code'
            )->addColumn(
                'status',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false, 'default' => 'PENDING'],
                'Status'
            )->addColumn(
                'scheduled_at',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => true],
                'Date scheduled to launch the job'
            )->addColumn(
                'last_executed_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => true],
                'Last executed date'
            )->addColumn(
                'last_success_date',
                Table::TYPE_DATETIME,
                null,
                ['nullable' => true],
                'Last date the job was executed correctly'
            )->addColumn(
                'job_class',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Job import class'
            )->addColumn(
                'name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Job import name'
            )->addColumn(
                'position',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Job position to priorize launch'
            )->setComment('Akeneo Connector Job');

            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}
