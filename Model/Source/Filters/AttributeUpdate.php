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
class AttributeUpdate implements ArrayInterface
{
    /**
     * Code for update greater than
     *
     * @var string GREATER_THAN
     */
    public const GREATER_THAN = '>';

    /**
     * Return array of options for the status filter
     *
     * @return array Format: array('<value>' => '<label>', ...)
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => __('Greater than'),
                'value' => self::GREATER_THAN,
            ],
        ];
    }
}
