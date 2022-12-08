<?php

declare(strict_types=1);

namespace Akeneo\Connector\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\View\Element\BlockInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class ExportPdf extends Field
{
    /**
     * Description $_template field
     *
     * @var string $template
     */
    protected $_template = 'Akeneo_Connector::system/config/export_pdf.phtml';

    /**
     * Description render function
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();

        return parent::render($element);
    }

    /**
     * Description _getElementHtml function
     *
     * @param AbstractElement $element
     *
     * @return mixed
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->_toHtml();
    }

    /**
     * Description getCustomUrl function
     *
     * @return mixed
     */
    public function getExportUrl()
    {
        return $this->getUrl('akeneo_connector/config/exportpdf');
    }
}
