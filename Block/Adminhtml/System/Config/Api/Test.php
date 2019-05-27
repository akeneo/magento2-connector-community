<?php

namespace Akeneo\Connector\Block\Adminhtml\System\Config\Api;

use Akeneo\Connector\Helper\Config as ConfigHelper;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Widget\Button;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Test
 *
 * @category  Class
 * @package   Akeneo\Connector\Block\Adminhtml\System\Config\Api
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Test extends Field
{
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;

    /**
     * Test constructor
     *
     * @param Context $context
     * @param ConfigHelper $configHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->configHelper = $configHelper;
    }

    /**
     * Retrieve element HTML markup
     *
     * @param AbstractElement $element
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        /** @var \Magento\Backend\Block\Widget\Button $buttonBlock  */
        $buttonBlock = $this->getForm()->getLayout()->createBlock(Button::class);

        $website = $buttonBlock->getRequest()->getParam('website');
        $store   = $buttonBlock->getRequest()->getParam('store');

        $params = [
            'website' => $website,
            'store'   => $store
        ];

        $data = [
            'label' => $this->getLabel(),
            'onclick' => "setLocation('" . $this->getTestUrl($params) . "')",
        ];

        /** @var string $baseUri */
        $baseUri = $this->configHelper->getAkeneoApiBaseUrl();
        /** @var string $clientId */
        $clientId = $this->configHelper->getAkeneoApiClientId();
        /** @var string $secret */
        $secret = $this->configHelper->getAkeneoApiClientSecret();
        /** @var string $username */
        $username = $this->configHelper->getAkeneoApiUsername();
        /** @var string $password */
        $password = $this->configHelper->getAkeneoApiPassword();

        if (!$baseUri || !$clientId || !$secret || !$username || !$password) {
            $data['disabled'] = true;
        }

        $html = $buttonBlock->setData($data)->toHtml();

        return $html;
    }

    /**
     * Retrieve button label
     *
     * @return \Magento\Framework\Phrase
     */
    private function getLabel()
    {
        return  __('Test');
    }

    /**
     * Retrieve Button URL
     *
     * @param array
     * @return string
     */
    public function getTestUrl($params = [])
    {
        return $this->getUrl('akeneo_connector/test', $params);
    }
}
