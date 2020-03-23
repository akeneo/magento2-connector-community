<?php

namespace Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Stdlib\DateTime;

/**
 * Class Date
 *
 * @category  Class
 * @package   Akeneo\Connector\Block\Adminhtml\System\Config\Form\Field
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2018 Agence Dn'D
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Date extends Field
{
    public function render(AbstractElement $element)
    {
        $element->setDateFormat(DateTime::DATE_INTERNAL_FORMAT);
        $element->setTimeFormat(null);

        return parent::render($element);
    }
}
