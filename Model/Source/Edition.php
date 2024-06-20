<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Edition implements ArrayInterface
{
    /**
     * Version 3.2 public constant
     *
     * @var string THREE_POINT_TWO
     */
    public const THREE_POINT_TWO = 'three';
    /**
     * Version < 4.0.62 public constant
     *
     * @var string LESS_FOUR_POINT_ZERO_POINT_SIXTY_TWO
     */
    public const LESS_FOUR_POINT_ZERO_POINT_SIXTY_TWO = 'less_four_point_zero_point_sixty_two';
    /**
     * Version >= 4.0.62 and up public constant
     *
     * @var string GREATER_OR_FOUR_POINT_ZERO_POINT_SIXTY_TWO
     */
    public const GREATER_OR_FOUR_POINT_ZERO_POINT_SIXTY_TWO = 'greater_or_four_point_zero_point_sixty_two';
    /**
     * Version >= 5.0 and up public constant
     *
     * @var string GREATER_OR_FIVE
     */
    public const GREATER_OR_FIVE = 'greater_or_five';
    /**
     * Version Serenity public constant
     *
     * @var string SERENITY
     */
    public const SERENITY = 'serenity';
    /**
     * Version Growth public constant
     *
     * @var string GROWTH
     */
    public const GROWTH = 'growth';
    /**
     * Version 7.0 constant
     */
    public const SEVEN = 'seven';

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
            self::GROWTH => __('Growth Edition'),
            self::SEVEN => __('7.0'),
        ];
    }
}
