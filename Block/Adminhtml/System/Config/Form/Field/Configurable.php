<?php

declare(strict_types=1);

namespace Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Select;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Configurable extends AbstractFieldArray
{
    /**
     * Type default
     *
     * @var string TYPE_DEFAULT
     */
    public const TYPE_DEFAULT = 'default';
    /**
     * Type Simple
     *
     * @var string TYPE_SIMPLE
     */
    public const TYPE_SIMPLE = 'simple';
    /**
     * Type Mapping
     *
     * @var string TYPE_MAPPING
     */
    public const TYPE_MAPPING = 'mapping';
    /**
     * Type Query
     *
     * @var string TYPE_QUERY
     */
    public const TYPE_QUERY = 'query';
    /**
     * Type Value
     *
     * @var string TYPE_VALUE
     */
    public const TYPE_VALUE = 'value';

    /**
     * This variable contains a Factory
     *
     * @var Factory $elementFactory
     */
    protected $elementFactory;

    /**
     * Configurable constructor.
     *
     * @param Context $context
     * @param Factory $elementFactory
     * @param array   $data
     */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->elementFactory = $elementFactory;
    }

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('attribute', ['label' => __('Attribute')]);
        $this->addColumn('type', ['label' => __('Type')]);
        $this->addColumn('value', ['label' => __('Value')]);
        $this->_addAfter       = false;
        $this->_addButtonLabel = __('Add');

        parent::_construct();
    }

    /**
     * Render array cell for prototypeJS template
     *
     * @param string $columnName
     *
     * @return string
     * @throws \Exception
     */
    public function renderCellTemplate($columnName)
    {
        if (!in_array($columnName, ['type']) || !isset($this->_columns[$columnName])) {
            return parent::renderCellTemplate($columnName);
        }

        /** @var array $options */
        $options = [
            self::TYPE_MAPPING => __('Mapping'),
            self::TYPE_SIMPLE  => __('First Variation value'),
            self::TYPE_QUERY   => __('SQL Statement'),
            self::TYPE_VALUE   => __('Default value'),
        ];

        /** @var Select $element */
        $element = $this->elementFactory->create('select');
        $element->setForm($this->getForm())
            ->setName($this->_getCellInputElementName($columnName))
            ->setHtmlId($this->_getCellInputElementId('<%- _id %>', $columnName))
            ->setValues($options);

        return str_replace("\n", '', $element->getElementHtml());
    }
}
