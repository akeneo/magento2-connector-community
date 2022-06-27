<?php

declare(strict_types=1);

namespace Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Backend\Block\Template\Context;
use Akeneo\Connector\Model\Source\Attribute\File as FileFilter;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class File extends AbstractFieldArray
{
    /**
     * This variable contains a Factory
     *
     * @var Factory $elementFactory
     */
    protected $elementFactory;
    /**
     * This variable contains a FileFilter
     *
     * @var FileFilter $fileFilter
     */
    protected $fileFilter;

    /**
     * File constructor
     *
     * @param Context    $context
     * @param Factory    $elementFactory
     * @param FileFilter $fileFilter
     * @param array      $data
     */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        FileFilter $fileFilter,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->elementFactory = $elementFactory;
        $this->fileFilter     = $fileFilter;
    }

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn(
            'file_attribute',
            [
                'label' => __('Akeneo Attribute'),
            ]
        );
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
        /** @var array $options */
        $options = [];

        if ($columnName === 'file_attribute') {
            /** @var ResourceCursorInterface[] $channels */
            $attributes = $this->fileFilter->getAttributes();

            /** @var ResourceCursorInterface $channel */
            foreach ($attributes as $attribute) {
                if ($attribute['type'] != 'pim_catalog_file') {
                    continue;
                }
                $options[$attribute['code']] = $attribute['code'];
            }
        }

        /** @var \Magento\Framework\Data\Form\Element\Select $element */
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
