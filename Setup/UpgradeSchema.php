<?php

namespace Akeneo\Connector\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
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
     * UpgradeSchema constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ConfigInterface      $resourceConfig
     * @param JsonSerializer       $serializer
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ConfigInterface $resourceConfig,
        JsonSerializer $serializer
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->resourceConfig = $resourceConfig;
        $this->serializer = $serializer;
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

        $installer->endSetup();
    }
}
