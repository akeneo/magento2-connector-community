<?php

declare(strict_types=1);

namespace Akeneo\Connector\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class DocumentationLink extends Field
{
    /**
     * Description DOCUMENTATION_LINK constant
     *
     * @var string DOCUMENTATION_LINK
     */
    public const DOCUMENTATION_LINK = "https://help.akeneo.com/magento2-connector/v100/index.html";

    /**
     * Description _getElementHtml function
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        /** @var string $html */
        $html = $element->getElementHtml();

        $html .= '<a target="_blank" href="' . self::DOCUMENTATION_LINK . '" title="' . __('Akeneo Help Center') . '">' . __('Akeneo Help Center') . '</a>';

        return $html;
    }
}
