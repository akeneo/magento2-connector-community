<?php

namespace Akeneo\Connector\Helper\Import;

use Magento\Catalog\Model\Product\Attribute\Backend\Price;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Model\Entity\Attribute\Backend\Datetime;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Model\Entity\Attribute\Source\Table;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Weee\Model\Attribute\Backend\Weee\Tax;
use Akeneo\Connector\Helper\Config;
use Akeneo\Connector\Helper\Serializer;

/**
 * Class Attribute
 *
 * @category  Class
 * @package   Akeneo\Connector\Helper\Import
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Attribute extends AbstractHelper
{
    /**
     * Description $serializer field
     *
     * @var Serializer $serializer
     */
    protected $serializer;

    /**
     * Attribute constructor
     *
     * @param Context $context
     * @param Serializer $serializer
     */
    public function __construct(
        Context $context,
        Serializer $serializer
    ) {
        parent::__construct($context);

        $this->serializer = $serializer;
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
        ];

        $types = array_merge($types, $this->getAdditionalTypes());

        return isset($types[$pimType]) ? $this->getConfiguration($types[$pimType]) : $this->getConfiguration();
    }

    /**
     * Retrieve additional types
     *
     * @return array
     */
    public function getAdditionalTypes()
    {
        /** @var string|array $types */
        $types = $this->scopeConfig->getValue(Config::ATTRIBUTE_TYPES);
        /** @var array $additional */
        $additional = [];
        if (!$types) {
            return $additional;
        }
        $types = $this->serializer->unserialize($types);
        if (is_array($types)) {
            /** @var array $type */
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
        ];

        /** @var DataObject $response */
        $response = new DataObject();
        $response->setTypes($types);

        $this->_eventManager->dispatch(
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

        $this->_eventManager->dispatch(
            'akeneo_connector_attribute_get_available_types_add_after',
            ['response' => $response]
        );

        $types = $response->getTypes();

        return $types;
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
                    'type' => 'text',
                    'length' => 255,
                    'default' => '',
                    'COMMENT' => ' ',
                    'nullable' => false
                ],
                'only_init' => true,
            ],
            'frontend_input' => [
                'type'      => [
                    'type' => 'text',
                    'length' => 255,
                    'default' => '',
                    'COMMENT' => ' ',
                    'nullable' => false
                ],
                'only_init' => true,
            ],
            'backend_model'  => [
                'type'      => [
                    'type' => 'text',
                    'length' => 255,
                    'default' => '',
                    'COMMENT' => ' ',
                    'nullable' => false
                ],
                'only_init' => true,
            ],
            'source_model'   => [
                'type'      => [
                    'type' => 'text',
                    'length' => 255,
                    'default' => '',
                    'COMMENT' => ' ',
                    'nullable' => false
                ],
                'only_init' => true,
            ],
            'frontend_model' => [
                'type'      => [
                    'type' => 'text',
                    'length' => 255,
                    'default' => '',
                    'COMMENT' => ' ',
                    'nullable' => false
                ],
                'only_init' => false,
            ],
        ];

        /** @var DataObject $response */
        $response = new DataObject();
        $response->setColumns($columns);

        $this->_eventManager->dispatch(
            'akeneo_connector_attribute_get_specific_columns_add_after',
            ['response' => $response]
        );

        $columns = $response->getColumns();

        return $columns;
    }
}
