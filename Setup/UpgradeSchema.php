<?php

namespace Akeneo\Connector\Setup;

use Magento\Eav\Model\Config;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\TriggerFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Akeneo\Connector\Helper\Serializer as JsonSerializer;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field\Configurable as TypeField;

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
     * This variable contains a JsonSerializer
     *
     * @var JsonSerializer $serializer
     */
    protected $serializer;
    /**
     * This variable contains a TriggerFactory
     *
     * @var TriggerFactory $triggerFactory
     */
    protected $triggerFactory;
    /**
     * This variable contains a Config
     *
     * @var Config $config
     */
    protected $config;

    /**
     * UpgradeSchema constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigInterface      $resourceConfig
     * @param JsonSerializer       $serializez
     * @param TriggerFactory       $triggerFactory
     * @param Config               $config
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ConfigInterface $resourceConfig,
        JsonSerializer $serializer,
        TriggerFactory $triggerFactory,
        Config $config
    ) {
        $this->scopeConfig    = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->serializer     = $serializer;
        $this->triggerFactory = $triggerFactory;
        $this->config         = $config;
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
            /** @var string|array $configurable */
            $configurable = $this->scopeConfig->getValue(ConfigHelper::PRODUCT_CONFIGURABLE_ATTRIBUTES);

            if ($configurable) {
                $configurable = $this->serializer->unserialize($configurable);

                if (!is_array($configurable)) {
                    $configurable = [];
                }

                /** @var array $attribute */
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
                    json_encode($configurable)
                );
            }

        }

        if (version_compare($context->getVersion(), '1.0.2', '<')) {
            /* Create triggers for deletion in akeneo connector entity table */
            /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connection */
            $connection = $setup->getConnection();
            /** @var string $event */
            $event = 'DELETE';
            /** @var int $entityTypeId */
            $entityTypeId = $this->config->getEntityType(
                \Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE
            )->getEntityTypeId();

            /* Category trigger */
            /** @var string $triggerName */
            $triggerName = 'akeneo_connector_after_delete_category';
            /** @var \Magento\Framework\DB\Ddl\Trigger $trigger */
            $trigger = $this->triggerFactory->create()->setName($triggerName)->setTime(
                \Magento\Framework\DB\Ddl\Trigger::TIME_AFTER
            )->setEvent($event)->setTable('catalog_category_entity')->addStatement(
                'DELETE FROM akeneo_connector_entities WHERE OLD.entity_id = entity_id AND import = \'category\';'
            );
            $connection->dropTrigger($triggerName);
            $connection->createTrigger($trigger);

            /* Family trigger */
            $triggerName = 'akeneo_connector_after_delete_family';
            $trigger     = $this->triggerFactory->create()->setName($triggerName)->setTime(
                \Magento\Framework\DB\Ddl\Trigger::TIME_AFTER
            )->setEvent($event)->setTable('eav_attribute_set')->addStatement(
                'DELETE FROM akeneo_connector_entities WHERE OLD.attribute_set_id = entity_id AND import = \'family\' AND OLD.entity_type_id = ' . $entityTypeId . ';'
            );
            $connection->dropTrigger($triggerName);
            $connection->createTrigger($trigger);

            /* Attribute trigger */
            $triggerName = 'akeneo_connector_after_delete_attribute';
            $trigger     = $this->triggerFactory->create()->setName($triggerName)->setTime(
                \Magento\Framework\DB\Ddl\Trigger::TIME_AFTER
            )->setEvent($event)->setTable('eav_attribute')->addStatement(
                'DELETE FROM akeneo_connector_entities WHERE OLD.attribute_id = entity_id AND import = \'attribute\' AND OLD.entity_type_id = ' . $entityTypeId . ';'
            );
            $connection->dropTrigger($triggerName);
            $connection->createTrigger($trigger);

            /* Option trigger */
            $triggerName = 'akeneo_cæonnector_after_delete_option';
            $trigger     = $this->triggerFactory->create()->setName($triggerName)->setTime(
                \Magento\Framework\DB\Ddl\Trigger::TIME_AFTER
            )->setEvent($event)->setTable('eav_attribute_option')->addStatement(
                'DELETE FROM akeneo_connector_entities WHERE OLD.option_id = entity_id AND import = \'option\';'
            );
            $connection->dropTrigger($triggerName);
            $connection->createTrigger($trigger);

            $triggerName = 'akeneo_connector_after_delete_product';
            $trigger     = $this->triggerFactory->create()->setName($triggerName)->setTime(
                \Magento\Framework\DB\Ddl\Trigger::TIME_AFTER
            )->setEvent($event)->setTable('catalog_product_entity')->addStatement(
                'DELETE FROM akeneo_connector_entities WHERE OLD.entity_id = entity_id AND import = \'product\';'
            );
            $connection->dropTrigger($triggerName);
            $connection->createTrigger($trigger);
        }

        $installer->endSetup();
    }
}
