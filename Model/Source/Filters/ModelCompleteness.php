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
class ModelCompleteness implements ArrayInterface
{
    /**
     * No confition code
     *
     * @var string NO_CONDITION
     */
    public const NO_CONDITION = 'no_condition';
    /**
     * At least one variant complete code
     *
     * @var string AT_LEAST_COMPLETE
     */
    public const AT_LEAST_COMPLETE = 'AT LEAST COMPLETE';
    /**
     * At least one variant incomplete code
     *
     * @var string AT_LEAST_INCOMPLETE
     */
    public const AT_LEAST_INCOMPLETE = 'AT LEAST INCOMPLETE';
    /**
     * All variant complete code
     *
     * @var string ALL_COMPLETE
     */
    public const ALL_COMPLETE = 'ALL COMPLETE';
    /**
     * All variant incomplete code
     *
     * @var string ALL_INCOMPLETE
     */
    public const ALL_INCOMPLETE = 'ALL INCOMPLETE';

    /**
     * Return array of options for the completeness filter
     *
     * @return array Format: array('<value>' => '<label>', ...)
     */
    public function toOptionArray()
    {
        return [
            self::NO_CONDITION        => __('No condition'),
            self::AT_LEAST_COMPLETE   => __('At least one variant complete'),
            self::AT_LEAST_INCOMPLETE => __('At least one variant incomplete'),
            self::ALL_COMPLETE        => __('All variant complete'),
            self::ALL_INCOMPLETE      => __('All variant incomplete'),
        ];
    }
}
