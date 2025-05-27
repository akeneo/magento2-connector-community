<?php

declare(strict_types=1);

namespace Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field;

use Akeneo\Connector\Helper\Config;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\Factory as ElementFactory;

class Gallery extends AbstractFieldArray
{
    public function __construct(
        private ElementFactory $elementFactory,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    protected function _construct(): void
    {
        $this->addColumn('attribute', ['label' => __('Akeneo Attribute')]);
        $this->addColumn('type', ['label' => __('Assign to')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = (string)__('Add');

        parent::_construct();
    }

    public function renderCellTemplate($columnName): string
    {
        if ($columnName !== 'type') {
            return parent::renderCellTemplate($columnName);
        }

        $options = [
            Config::PRODUCT_IMAGE_TYPE_ALL => __('All'),
            Config::PRODUCT_IMAGE_TYPE_PARENT => __('Parent'),
            Config::PRODUCT_IMAGE_TYPE_CHILD => __('Child'),
        ];
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
