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
class Update implements ArrayInterface
{
    /**
     * Description LOWER_THAN constant
     *
     * @var string LOWER_THAN
     */
    public const LOWER_THAN = '<';
    /**
     * Description GREATER_THAN constant
     *
     * @var string GREATER_THAN
     */
    public const GREATER_THAN = '>';
    /**
     * Description BETWEEN constant
     *
     * @var string BETWEEN
     */
    public const BETWEEN = 'BETWEEN';
    /**
     * Description SINCE_LAST_N_DAYS constant
     *
     * @var string SINCE_LAST_N_DAYS
     */
    public const SINCE_LAST_N_DAYS = 'SINCE LAST N DAYS';
    /**
     * Description SINCE_LAST_N_HOURS constant
     *
     * @var string SINCE_LAST_N_HOURS
     */
    public const SINCE_LAST_N_HOURS = 'SINCE LAST N HOURS';
    /**
     * Description SINCE_LAST_IMPORT constant
     *
     * @var string SINCE_LAST_IMPORT
     */
    public const SINCE_LAST_IMPORT = 'LAST_IMPORT';

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
                'value' => '',
            ],
            [
                'label' => __('Lower than'),
                'value' => self::LOWER_THAN,
            ],
            [
                'label' => __('Greater than'),
                'value' => self::GREATER_THAN,
            ],
            [
                'label' => __('Between'),
                'value' => self::BETWEEN,
            ],
            [
                'label' => __('Since last X days'),
                'value' => self::SINCE_LAST_N_DAYS,
            ],
            [
                'label' => __('Since last X hours'),
                'value' => self::SINCE_LAST_N_HOURS,
            ],
            [
                'label' => __('Since last successful import'),
                'value' => self::SINCE_LAST_IMPORT,
            ],
        ];
    }
}
