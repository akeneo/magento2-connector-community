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
    /** const keys */
    const THREE_POINT_TWO = 'three';
    const FOUR = 'four';
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
            self::FOUR => __('4.0 or greater'),
            self::SERENITY    => __('Serenity Edition'),
        ];
    }
}
