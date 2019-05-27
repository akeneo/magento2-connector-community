<?php

namespace Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Factory as ElementFactory;
use Akeneo\Connector\Helper\Import\Attribute as AttributeHelper;

/**
 * Class Type
 *
 * @category  Class
 * @package   Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Type extends AbstractFieldArray
{
    /**
     * This variable contains an ElementFactory
     *
     * @var ElementFactory $elementFactory
     */
    protected $elementFactory;
    /**
     * This variable contains an AttributeHelper
     *
     * @var AttributeHelper $attributeHelper
     */
    protected $attributeHelper;

    /**
     * Type constructor
     *
     * @param Context $context
     * @param ElementFactory $elementFactory
     * @param AttributeHelper $attributeHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        ElementFactory $elementFactory,
        AttributeHelper $attributeHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->attributeHelper = $attributeHelper;
        $this->elementFactory  = $elementFactory;
    }

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('pim_type', ['label' => __('Akeneo')]);
        $this->addColumn('magento_type', ['label' => __('Magento')]);
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
     */
    public function renderCellTemplate($columnName)
    {
        if ($columnName != 'magento_type' || !isset($this->_columns[$columnName])) {
            return parent::renderCellTemplate($columnName);
        }

        /** @var array $options */
        $options = $this->attributeHelper->getAvailableTypes();
        /** @var AbstractElement $element */
        $element = $this->elementFactory->create('select');
        $element->setForm(
            $this->getForm()
        )->setName(
            $this->_getCellInputElementName($columnName)
        )->setHtmlId(
            $this->_getCellInputElementId('<%- _id %>', $columnName)
        )->setValues(
            $options
        );

        return str_replace("\n", '', $element->getElementHtml());
    }
}
