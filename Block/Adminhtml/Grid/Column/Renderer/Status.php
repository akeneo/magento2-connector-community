<?php

namespace Akeneo\Connector\Block\Adminhtml\Grid\Column\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use Akeneo\Connector\Api\Data\ImportInterface;

/**
 * Class Status
 *
 * @category  Class
 * @package   Akeneo\Connector\Block\Adminhtml\Grid\Column\Renderer
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Status extends AbstractRenderer
{
    /**
     * Render indexer status
     *
     * @param DataObject $row
     *
     * @return string
     */
    public function render(DataObject $row)
    {
        /** @var string $class */
        $class = '';
        /** @var string $text */
        $text = '';
        switch ($this->_getValue($row)) {
            case ImportInterface::IMPORT_SUCCESS:
                $class = 'grid-severity-notice';
                $text = __('Success');
                break;
            case ImportInterface::IMPORT_ERROR:
                $class = 'grid-severity-critical';
                $text = __('Error');
                break;
            case ImportInterface::IMPORT_PROCESSING:
                $class = 'grid-severity-minor';
                $text = __('Processing');
                break;
        }

        return '<span class="' . $class . '"><span>' . $text . '</span></span>';
    }
}
