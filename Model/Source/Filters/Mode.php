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
class Mode implements ArrayInterface
{
    /** const keys */
    public const STANDARD = 'standard';
    public const ADVANCED = 'advanced';

    /**
     * Return array of options for the filter mode
     *
     * @return array Format: array('<value>' => '<label>', ...)
     */
    public function toOptionArray()
    {
        return [
            self::STANDARD => __('Standard'),
            self::ADVANCED => __('Advanced'),
        ];
    }
}
