<?php

declare(strict_types=1);

namespace Akeneo\Connector\Model\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class MaxCharacter
 *
 * @package   Akeneo\Connector\Model\Source
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class MaxCharacter implements ArrayInterface
{
    /**
     * Seventy nine characters constant
     *
     * @var string SEVENTY_NINE_CHARACTERS
     */
    const SEVENTY_NINE_CHARACTERS = 'seventy_nine_characters';
    /**
     * One hundred eighty nine characters constant
     *
     * @var string ONE_HUNDRED_EIGHTY_NINE_CHARACTERS
     */
    const ONE_HUNDRED_EIGHTY_NINE_CHARACTERS = 'one_hundred_eighty_nine_characters';

    /**
     * Return array of options for the status mode filter
     *
     * @return array Format: array('<value>' => '<label>', ...)
     */
    public function toOptionArray()
    {
        return [
            self::SEVENTY_NINE_CHARACTERS            => __('79 characters (Magento 2.4.1 or inferior)'),
            self::ONE_HUNDRED_EIGHTY_NINE_CHARACTERS => __('189 characters (Magento 2.4.2 or superior)'),
        ];
    }
}
