<?php

declare(strict_types=1);

namespace Akeneo\Connector\Block\Adminhtml\Grid\Column\Renderer;

use Akeneo\Connector\Api\Data\JobInterface;
use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
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
            case JobInterface::JOB_SUCCESS:
                $class = 'grid-severity-notice';
                $text  = __('Success');
                break;
            case JobInterface::JOB_ERROR:
                $class = 'grid-severity-critical';
                $text  = __('Error');
                break;
            case JobInterface::JOB_PROCESSING:
                $class = 'grid-severity-processing';
                $text  = __('Processing');
                break;
            case JobInterface::JOB_PENDING:
                $class = 'grid-severity-pending';
                $text  = __('Pending');
                break;
            case JobInterface::JOB_SCHEDULED:
                $class = 'grid-severity-minor';
                $text  = __('Scheduled');
                break;
        }

        return '<span class="' . $class . '"><span>' . $text . '</span></span>';
    }
}
