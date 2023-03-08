<?php

declare(strict_types=1);

namespace Akeneo\Connector\Helper\Import;

use Akeneo\Connector\Helper\Config;
use Magento\Catalog\Model\Product\Attribute\Backend\Price;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Model\Entity\Attribute\Backend\Datetime;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Swatches\Model\Swatch;
use Magento\Weee\Model\Attribute\Backend\Weee\Tax;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Attribute
{
    /**
     * Description $serializer field
     *
     * @var Json $jsonSerializer
     */
    protected $jsonSerializer;
    /**
     * Description $eventManager field
     *
     * @var EventManager $eventManager
     */
    protected $eventManager;
    /**
     * Description $scopeConfig field
     *
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * Attribute constructor
     *
     * @param Json                 $jsonSerializer
     * @param EventManager         $eventManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Json $jsonSerializer,
        EventManager $eventManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->jsonSerializer = $jsonSerializer;
        $this->eventManager   = $eventManager;
        $this->scopeConfig    = $scopeConfig;
    }

    /**
     * Match Pim type with Magento attribute logic
     *
     * @param string $pimType
     *
     * @return array
     */
    public function getType($pimType = 'default')
    {
        /** @var string[] $types */
        $types = [
            'default'                      => 'text',
            'pim_catalog_identifier'       => 'text',
            'pim_catalog_text'             => 'text',
            'pim_catalog_metric'           => 'text',
            'pim_catalog_metric_select'    => 'select',
            'pim_catalog_number'           => 'text',
            'pim_catalog_textarea'         => 'textarea',
            'pim_catalog_date'             => 'date',
            'pim_catalog_boolean'          => 'boolean',
            'pim_catalog_simpleselect'     => 'select',
            'pim_catalog_multiselect'      => 'multiselect',
            'pim_catalog_price_collection' => 'price',
            'pim_catalog_tax'              => 'tax',
            'pim_catalog_table'            => 'textarea'
        ];

        $types = array_merge($types, $this->getAdditionalTypes());

        return isset($types[$pimType]) ? $this->getConfiguration($types[$pimType]) : $this->getConfiguration();
    }

    /**
     * Return swatch types attributes configuration, fallback to other types if attribute is not a swatch type
     */
    public function getSwatchType(string $attributeCode, string $pimType = 'default'): array
    {
        $types = $this->getAdditionalSwatchTypes();

        return isset($types[$attributeCode]) ? $this->getConfiguration($types[$attributeCode]) : $this->getType($pimType);
    }

    /**
     * Retrieve additional types
     *
     * @return array
     */
    public function getAdditionalTypes()
    {
        /** @var string $types */
        $types = $this->scopeConfig->getValue(Config::ATTRIBUTE_TYPES);
        /** @var mixed[] $additional */
        $additional = [];
        if (!$types) {
            return $additional;
        }
        /** @var mixed[] $types */
        $types = $this->jsonSerializer->unserialize($types);
        if (is_array($types)) {
            /** @var array $type */
            foreach ($types as $type) {
                $additional[$type['pim_type']] = $type['magento_type'];
            }
        }

        return $additional;
    }

    /**
     * Retrieve swatch types attributes
     */
    public function getAdditionalSwatchTypes(): array
    {
        /** @var string $types */
        $types = $this->scopeConfig->getValue(Config::ATTRIBUTE_SWATCH_TYPES);
        /** @var mixed[] $additional */
        $additional = [];
        if (!$types) {
            return $additional;
        }
        /** @var mixed[] $types */
        $types = $this->jsonSerializer->unserialize($types);
        if (is_array($types)) {
            /** @var mixed[] $type */
            foreach ($types as $type) {
                $additional[$type['pim_type']] = $type['magento_type'];
            }
        }

        return $additional;
    }

    /**
     * Retrieve configuration with input type
     *
     * @param string $inputType
     *
     * @return array
     */
    protected function getConfiguration($inputType = 'default')
    {
        /** @var array $types */
        $types = [
            'default'     => [
                'backend_type'   => 'varchar',
                'frontend_input' => 'text',
                'backend_model'  => null,
                'source_model'   => null,
                'frontend_model' => null,
            ],
            'text'        => [
                'backend_type'   => 'varchar',
                'frontend_input' => 'text',
                'backend_model'  => null,
                'source_model'   => null,
                'frontend_model' => null,
            ],
            'textarea'    => [
                'backend_type'   => 'text',
                'frontend_input' => 'textarea',
                'backend_model'  => null,
                'source_model'   => null,
                'frontend_model' => null,
            ],
            'date'        => [
                'backend_type'   => 'datetime',
                'frontend_input' => 'date',
                'backend_model'  => Datetime::class,
                'source_model'   => null,
                'frontend_model' => null,
            ],
            'boolean'     => [
                'backend_type'   => 'int',
                'frontend_input' => 'boolean',
                'backend_model'  => null,
                'source_model'   => Boolean::class,
                'frontend_model' => null,
            ],
            'multiselect' => [
                'backend_type'   => 'varchar',
                'frontend_input' => 'multiselect',
                'backend_model'  => ArrayBackend::class,
                'source_model'   => null,
                'frontend_model' => null,
            ],
            'select'      => [
                'backend_type'   => 'int',
                'frontend_input' => 'select',
                'backend_model'  => null,
                'source_model'   => Table::class,
                'frontend_model' => null,
            ],
            'price'       => [
                'backend_type'   => 'decimal',
                'frontend_input' => 'price',
                'backend_model'  => Price::class,
                'source_model'   => null,
                'frontend_model' => null,
            ],
            'tax'         => [
                'backend_type'   => 'static',
                'frontend_input' => 'weee',
                'backend_model'  => Tax::class,
                'source_model'   => null,
                'frontend_model' => null,
            ],
            Swatch::SWATCH_TYPE_VISUAL_ATTRIBUTE_FRONTEND_INPUT => [
                'type' => 'pim_catalog_swatch_visual',
                'backend_type' => 'int',
                'frontend_input' => 'select',
                'backend_model' => null,
                'source_model' => Table::class,
                'frontend_model' => null,
            ],
            Swatch::SWATCH_TYPE_TEXTUAL_ATTRIBUTE_FRONTEND_INPUT => [
                'type' => 'pim_catalog_swatch_text',
                'backend_type' => 'int',
                'frontend_input' => 'select',
                'backend_model' => null,
                'source_model' => Table::class,
                'frontend_model' => null,
            ],
        ];

        /** @var DataObject $response */
        $response = new DataObject();
        $response->setTypes($types);

        $this->eventManager->dispatch(
            'akeneo_connector_attribute_get_configuration_add_before',
            ['response' => $response]
        );

        $types = $response->getTypes();

        return isset($types[$inputType]) ? $types[$inputType] : $types['default'];
    }

    /**
     * Retrieve available Magento types
     *
     * @return array
     */
    public function getAvailableTypes()
    {
        /** @var array $types */
        $types = [
            'text'        => 'text',
            'textarea'    => 'textarea',
            'date'        => 'date',
            'boolean'     => 'boolean',
            'multiselect' => 'multiselect',
            'select'      => 'select',
            'price'       => 'price',
            'tax'         => 'tax',
        ];
        /** @var DataObject $response */
        $response = new DataObject();
        $response->setTypes($types);

        $this->eventManager->dispatch(
            'akeneo_connector_attribute_get_available_types_add_after',
            ['response' => $response]
        );

        $types = $response->getTypes();

        return $types;
    }

    /**
     * Retrieve available Magento Swatch types
     */
    public function getAvailableSwatchTypes(): array
    {
        $types = [
            Swatch::SWATCH_TYPE_VISUAL_ATTRIBUTE_FRONTEND_INPUT => 'Visual Swatch',
            Swatch::SWATCH_TYPE_TEXTUAL_ATTRIBUTE_FRONTEND_INPUT => 'Text Swatch',
        ];
        $response = new DataObject();
        $response->setSwatchTypes($types);

        $this->eventManager->dispatch(
            'akeneo_connector_attribute_get_available_swatch_types_add_after',
            ['response' => $response]
        );

        return $response->getSwatchTypes();
    }

    /**
     * Get the specific columns that depends on the attribute type
     *
     * @return array
     */
    public function getSpecificColumns()
    {
        /** @var array $columns */
        $columns = [
            'backend_type'   => [
                'type'      => [
                    'type'     => 'text',
                    'length'   => 255,
                    'default'  => '',
                    'COMMENT'  => ' ',
                    'nullable' => false,
                ],
                'only_init' => true,
            ],
            'frontend_input' => [
                'type'      => [
                    'type'     => 'text',
                    'length'   => 255,
                    'default'  => '',
                    'COMMENT'  => ' ',
                    'nullable' => false,
                ],
                'only_init' => true,
            ],
            'backend_model'  => [
                'type'      => [
                    'type'     => 'text',
                    'length'   => 255,
                    'default'  => '',
                    'COMMENT'  => ' ',
                    'nullable' => false,
                ],
                'only_init' => true,
            ],
            'source_model'   => [
                'type'      => [
                    'type'     => 'text',
                    'length'   => 255,
                    'default'  => '',
                    'COMMENT'  => ' ',
                    'nullable' => false,
                ],
                'only_init' => true,
            ],
            'frontend_model' => [
                'type'      => [
                    'type'     => 'text',
                    'length'   => 255,
                    'default'  => '',
                    'COMMENT'  => ' ',
                    'nullable' => false,
                ],
                'only_init' => false,
            ],
        ];

        /** @var DataObject $response */
        $response = new DataObject();
        $response->setColumns($columns);

        $this->eventManager->dispatch(
            'akeneo_connector_attribute_get_specific_columns_add_after',
            ['response' => $response]
        );

        $columns = $response->getColumns();

        return $columns;
    }
}
