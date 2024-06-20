<?php

declare(strict_types=1);

namespace Akeneo\Connector\Setup\Patch\Data;

use Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field\Configurable as TypeField;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class UpdateConfigurableAttributes implements DataPatchInterface
{
    /**
     * $scopeConfig field
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    private $scopeConfig;
    /**
     * $jsonSerializer field
     *
     * @var SerializerInterface $jsonSerializer
     */
    private $jsonSerializer;
    /**
     * $resourceConfig field
     *
     * @var ConfigInterface $resourceConfig
     */
    private $resourceConfig;

    /**
     * UpdateConfigurableAttributes constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface  $serializer
     * @param ConfigInterface      $resourceConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer,
        ConfigInterface $resourceConfig
    ) {
        $this->scopeConfig     = $scopeConfig;
        $this->jsonSerializer  = $serializer;
        $this->resourceConfig  = $resourceConfig;
    }

    /**
     * Description apply function
     *
     * @return void
     */
    public function apply(): void
    {
        /** @var mixed|null $configurable */
        $configurable = $this->scopeConfig->getValue(ConfigHelper::PRODUCT_CONFIGURABLE_ATTRIBUTES);
        if ($configurable !== null) {
            /** @var mixed[] $configurable */
            $configurable = $this->jsonSerializer->unserialize($configurable);
            if (!is_array($configurable)) {
                $configurable = [];
            }
            foreach ($configurable as $key => $attribute) {
                if (!isset($attribute['attribute'], $attribute['value'])) {
                    continue;
                }
                if ($attribute['value'] && !$attribute['type']) {
                    $configurable[$key]['type'] = TypeField::TYPE_VALUE;
                }
                if ($configurable[$key]['type'] === TypeField::TYPE_DEFAULT) {
                    unset($configurable[$key]);
                }
                $this->resourceConfig->saveConfig(
                    ConfigHelper::PRODUCT_CONFIGURABLE_ATTRIBUTES,
                    $this->jsonSerializer->serialize($configurable)
                );
            }
        }
    }

    /**
     * Description getDependencies function
     *
     * @return array
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * Description getAliases function
     *
     * @return array
     */
    public function getAliases()
    {
        return [];
    }
}
