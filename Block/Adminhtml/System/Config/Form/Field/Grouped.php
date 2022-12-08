<?php

declare(strict_types=1);

namespace Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field;

use Akeneo\Connector\Helper\Authenticator;
use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Factory as ElementFactory;
use Psr\Log\LoggerInterface as Logger;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Grouped extends AbstractFieldArray
{
    /**
     * Description AKENEO_GROUPED_FAMILY_CODE constant
     *
     * @var string AKENEO_GROUPED_FAMILY_CODE
     */
    public const AKENEO_GROUPED_FAMILY_CODE = 'akeneo_grouped_family_code';
    /**
     * This variable contains a mixed value
     *
     * @var Authenticator $akeneoAuthenticator
     */
    protected $akeneoAuthenticator;
    /**
     * This variable contains a Logger
     *
     * @var Logger $logger
     */
    protected $logger;
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;
    /**
     * This variable contains an ElementFactory
     *
     * @var ElementFactory $elementFactory
     */
    protected $elementFactory;

    /**
     * Grouped constructor
     *
     * @param Authenticator  $akeneoAuthenticator
     * @param Logger         $logger
     * @param ConfigHelper   $configHelper
     * @param Context        $context
     * @param ElementFactory $elementFactory
     * @param array          $data
     */
    public function __construct(
        Authenticator $akeneoAuthenticator,
        Logger $logger,
        ConfigHelper $configHelper,
        Context $context,
        ElementFactory $elementFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->akeneoAuthenticator = $akeneoAuthenticator;
        $this->logger              = $logger;
        $this->configHelper        = $configHelper;
        $this->elementFactory      = $elementFactory;
    }

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn(self::AKENEO_GROUPED_FAMILY_CODE, ['label' => __('Grouped product family code')]);
        $this->addColumn('akeneo_quantity_association', ['label' => __('Quantity association code')]);
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
        if ($columnName != self::AKENEO_GROUPED_FAMILY_CODE || !isset($this->_columns[$columnName])) {
            return parent::renderCellTemplate($columnName);
        }

        /** @var array $options */
        $options = $this->getFamilies();
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

    /**
     * Get Families
     *
     * @return ResourceCursorInterface|array
     */
    public function getFamilies()
    {
        /** @var array $families */
        $families = [];

        try {
            /** @var AkeneoPimClientInterface $client */
            $client = $this->akeneoAuthenticator->getAkeneoApiClient();

            if (empty($client)) {
                return $families;
            }

            /** @var string|int $paginationSize */
            $paginationSize = $this->configHelper->getPaginationSize();
            /** @var ResourceCursorInterface $akeneoFamilies */
            $akeneoFamilies = $client->getFamilyApi()->all($paginationSize);
            /** @var mixed[] $family */
            foreach ($akeneoFamilies as $family) {
                if (!isset($family['code'])) {
                    continue;
                }
                $families[$family['code']] = $family['code'];
            }
        } catch (\Exception $exception) {
            $this->logger->warning($exception->getMessage());
        }

        return $families;
    }
}
