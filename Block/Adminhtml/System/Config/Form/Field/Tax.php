<?php

namespace Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Select;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Tax\Model\TaxClass\Source\Product;

/**
 * Class Tax
 *
 * @category  Class
 * @package   Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Tax extends AbstractFieldArray
{
    /**
     * This variable contains a Factory
     *
     * @var Factory $elementFactory
     */
    protected $elementFactory;
    /**
     * This variable contains a Product
     *
     * @var Product $productTaxClassSource
     */
    protected $productTaxClassSource;

    /**
     * Tax constructor.
     *
     * @param Context $context
     * @param Factory $elementFactory
     * @param Product $productTaxClassSource
     * @param array $data
     */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        Product $productTaxClassSource,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->elementFactory        = $elementFactory;
        $this->productTaxClassSource = $productTaxClassSource;
    }

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('website', ['label' => __('Website')]);
        $this->addColumn('tax_class', ['label' => __('Tax Class')]);
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
        if (!in_array($columnName, ['website', 'tax_class']) || !isset($this->_columns[$columnName])) {
            return parent::renderCellTemplate($columnName);
        }

        /** @var array $options */
        $options = [];

        if ($columnName === 'website') {
            /** @var WebsiteInterface[] $websites */
            $websites = $this->_storeManager->getWebsites();

            /** @var WebsiteInterface $website */
            foreach ($websites as $website) {
                $options[$website->getId()] = $website->getCode();
            }
        }

        if ($columnName === 'tax_class' && isset($this->_columns[$columnName])) {
            $options = $this->productTaxClassSource->getAllOptions();
        }

        /** @var Select $element */
        $element = $this->elementFactory->create('select');
        $element->setForm($this->getForm())
            ->setName($this->_getCellInputElementName($columnName))
            ->setHtmlId($this->_getCellInputElementId('<%- _id %>', $columnName))
            ->setValues($options);

        return str_replace("\n", '', $element->getElementHtml());
    }
}
