<?php

namespace Akeneo\Connector\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Edition
 *
 * @package   Akeneo\Connector\Model\Source
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Edition implements ArrayInterface
{
    /**
     * Version 3.2 constant
     *
     * @var string THREE_POINT_TWO
     */
    const THREE_POINT_TWO = 'three';
    /**
     * Version < 4.0.62 constant
     *
     * @var string LESS_FOUR_POINT_ZERO_POINT_SIXTY_TWO
     */
    const LESS_FOUR_POINT_ZERO_POINT_SIXTY_TWO = 'less_four_point_zero_point_sixty_two';
    /**
     * Version >= 4.0.62 and up constant
     *
     * @var string GREATER_OR_FOUR_POINT_ZERO_POINT_SIXTY_TWO
     */
    const GREATER_OR_FOUR_POINT_ZERO_POINT_SIXTY_TWO = 'greater_or_four_point_zero_point_sixty_two';
    /**
     * Version >= 5.0 and up constant
     *
     * @var string GREATER_OR_FOUR_POINT_ZERO_POINT_SIXTY_TWO
     */
    const GREATER_OR_FIVE = 'greater_or_five';
    /**
     * Version Serenity constant
     *
     * @var string SERENITY
     */
    const SERENITY = 'serenity';

    /**
     * Return array of options for the filter mode
     *
     * @return array Format: array('<value>' => '<label>', ...)
     */
    public function toOptionArray()
    {
        return [
            self::THREE_POINT_TWO => __('3.2'),
            self::LESS_FOUR_POINT_ZERO_POINT_SIXTY_TWO => __('Between 4.0.0 and 4.0.62'),
            self::GREATER_OR_FOUR_POINT_ZERO_POINT_SIXTY_TWO => __('4.0.62 or greater'),
            self::GREATER_OR_FIVE => __('5.0 or greater'),
            self::SERENITY => __('Serenity Edition'),
        ];
    }
}
