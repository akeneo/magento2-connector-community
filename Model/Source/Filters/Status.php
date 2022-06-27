<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model\Source\Filters;

use Magento\Framework\Option\ArrayInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Status implements ArrayInterface
{
    /** public public const keys */
    public const STATUS_NO_CONDITION = 'no_condition';
    public const STATUS_ENABLED = '1';
    public const STATUS_DISABLED = '0';

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
