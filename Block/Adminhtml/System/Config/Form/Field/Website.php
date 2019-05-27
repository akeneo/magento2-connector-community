<?php

namespace Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Backend\Block\Template\Context;
use Akeneo\Connector\Model\Source\Filters\Channel;

/**
 * Class Website
 *
 * @category  Class
 * @package   Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Website extends AbstractFieldArray
{
    /**
     * This variable contains a Factory
     *
     * @var Factory $elementFactory
     */
    protected $elementFactory;

    /**
     * This variable contains a mixed value
     *
     * @var Channel $channel
     */
    protected $channel;

    /**
     * Website constructor
     *
     * @param Context $context
     * @param Factory $elementFactory
     * @param Channel $channel,
     * @param array   $data
     */
    public function __construct(
        Context $context,
        Factory $elementFactory,
        Channel $channel,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->elementFactory = $elementFactory;
        $this->channel = $channel;
    }

    /**
     * Initialise form fields
     *
     * @return void
     */
    protected function _construct()
    {
        $this->addColumn(
            'website',
            [
                'label' => __('Website'),
            ]
        );
        $this->addColumn(
            'channel',
            [
                'label' => __('Channel'),
                'class' => 'required-entry',
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

        if ($columnName === 'website') {
            /** @var \Magento\Store\Api\Data\WebsiteInterface[] $websites */
            $websites = $this->_storeManager->getWebsites();

            /** @var \Magento\Store\Api\Data\WebsiteInterface $website */
            foreach ($websites as $website) {
                $options[$website->getCode()] = $website->getCode();
            }
        }

        if ($columnName === 'channel') {
            /** @var ResourceCursorInterface[] $channels */
            $channels = $this->channel->getChannels();

            /** @var ResourceCursorInterface $channel */
            foreach ($channels as $channel) {
                $options[$channel['code']] = $channel['code'];
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
