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
    const FLEXIBILITY = 'flexibility';
    const SERENITY = 'serenity';

    /**
     * Return array of options for the filter mode
     *
     * @return array Format: array('<value>' => '<label>', ...)
     */
    public function toOptionArray()
    {
        return [
            self::FLEXIBILITY => __('Community or Flexibility Edition'),
            self::SERENITY    => __('Serenity Edition'),
        ];
    }
}
