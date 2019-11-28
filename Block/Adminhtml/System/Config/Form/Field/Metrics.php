<?php

namespace Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean as BooleanModel;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Data\Form\Element\Select;
use Akeneo\Connector\Model\Source\Attribute\Metrics as MetricsSource;

/**
 * Class Metrics
 *
 * @package   Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Metrics extends AbstractFieldArray
{
    /**
     * This variable contains a Factory
     *
     * @var Factory $elementFactory
     */
    protected $elementFactory;
    /**
     * This variable contains a SearchCriteriaBuilder
     *
     * @var SearchCriteriaBuilder $searchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * This variable contains a AttributeRepositoryInterface
     *
     * @var AttributeRepositoryInterface $attributeRepository
     */
    protected $attributeRepository;
    /**
     * This variable contains a BooleanModel
     *
     * @var BooleanModel $booleanModel
     */
    protected $booleanModel;
    /**
     * This variable contains a MetricsSource
     *
     * @var MetricsSource $metricsSource
     */
    protected $metricsSource;

    /**
     * Image constructor
     *
     * @param Context                      $context
     * @param Factory                      $elementFactory
     * @param SearchCriteriaBuilder        $searchCriteriaBuilder
     * @param AttributeRepositoryInterface $attributeRepository
     * @param MetricsSource                $metricsSource
     * @param array                        $data
     */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AttributeRepositoryInterface $attributeRepository,
        MetricsSource $metricsSource,
        BooleanModel $booleanModel,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->elementFactory        = $elementFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->attributeRepository   = $attributeRepository;
        $this->metricsSource         = $metricsSource;
        $this->booleanModel          = $booleanModel;
    }

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn('akeneo_metrics', ['label' => __('Akeneo Metric Attribute')]);
        $this->addColumn('is_variant', ['label' => __('Used As Variant')]);
        $this->addColumn('is_concat', ['label' => __('Concat Metric Unit')]);
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
        /** @var string[] $options */
        $options = [];

        if ($columnName == 'akeneo_metrics') {
            $options = $this->metricsSource->getAllOptions();
        }

        if ($columnName == 'is_variant' || $columnName == 'is_concat') {
            $options = $this->booleanModel->getAllOptions();
        }

        /** @var Select $element */
        $element = $this->elementFactory->create('select');
        $element->setForm($this->getForm())->setName($this->_getCellInputElementName($columnName))->setHtmlId(
            $this->_getCellInputElementId('<%- _id %>', $columnName)
        )->setValues($options);

        return str_replace("\n", '', $element->getElementHtml());
    }
}
