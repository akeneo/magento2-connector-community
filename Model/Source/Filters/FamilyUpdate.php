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
class FamilyUpdate implements ArrayInterface
{
    /**
     * Description GREATER_THAN public constant
     *
     * @var string GREATER_THAN
     */
    public const GREATER_THAN = '>';

    /**
     * Description toOptionArray function
     *
     * @return string[]
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
