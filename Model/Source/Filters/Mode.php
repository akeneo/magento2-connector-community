<?php

namespace Akeneo\Connector\Model\Source\Filters;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Mode
 *
 * @category  Class
 * @package   Akeneo\Connector\Model\Source\Filters
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Mode implements ArrayInterface
{
    /** const keys */
    const STANDARD = 'standard';
    const ADVANCED = 'advanced';

    /**
     * Return array of options for the filter mode
     *
     * @return array Format: array('<value>' => '<label>', ...)
     */
    public function toOptionArray()
    {
        return [
            self::STANDARD => __('Standard'),
            self::ADVANCED => __('Advanced'),
        ];
    }
}
