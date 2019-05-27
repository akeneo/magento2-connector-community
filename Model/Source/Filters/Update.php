<?php

namespace Akeneo\Connector\Model\Source\Filters;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Update
 *
 * @category  Class
 * @package   Akeneo\Connector\Model\Source\Filters
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */

class Update implements ArrayInterface
{
    /** const keys */
    const LOWER_THAN = '<';
    const GREATER_THAN = '>';
    const BETWEEN = 'BETWEEN';
    const SINCE_LAST_N_DAYS = 'SINCE LAST N DAYS';

    /**
     * Return array of options for the status filter
     *
     * @return array Format: array('<value>' => '<label>', ...)
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => __('Lower than'),
                'value' => self::LOWER_THAN
            ],
            [
                'label' => __('Greater than'),
                'value' => self::GREATER_THAN
            ],
            [
                'label' => __('Between'),
                'value' => self::BETWEEN
            ],
            [
                'label' => __('Since last X days'),
                'value' => self::SINCE_LAST_N_DAYS
            ],
        ];
    }
}