<?php

namespace Akeneo\Connector\Model\Source\Filters;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Status
 *
 * @category  Class
 * @package   Akeneo\Connector\Model\Source\Filters
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Status implements ArrayInterface
{
    /** const keys */
    const STATUS_NO_CONDITION = 'no_condition';
    const STATUS_ENABLED = '1';
    const STATUS_DISABLED = '0';

    /**
     * Return array of options for the status filter
     *
     * @return array Format: array('<value>' => '<label>', ...)
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => __('No condition'),
                'value' => self::STATUS_NO_CONDITION
            ],
            [
                'label' => __('Enabled'),
                'value' => self::STATUS_ENABLED
            ],
            [
                'label' => __('Disabled'),
                'value' => self::STATUS_DISABLED
            ],
        ];
    }
}
