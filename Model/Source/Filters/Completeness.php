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
class Completeness implements ArrayInterface
{
    /** const keys */
    public const NO_CONDITION = 'no_condition';
    public const LOWER_THAN = '<';
    public const LOWER_OR_EQUALS_THAN = '<=';
    public const GREATER_THAN = '>';
    public const GREATER_OR_EQUALS_THAN = '>=';
    public const EQUALS = '=';
    public const DIFFER = '!=';
    public const GREATER_THAN_ON_ALL_LOCALES = 'GREATER THAN ON ALL LOCALES';
    public const GREATER_OR_EQUALS_THAN_ON_ALL_LOCALES = 'GREATER OR EQUALS THAN ON ALL LOCALES';
    public const LOWER_THAN_ON_ALL_LOCALES = 'LOWER THAN ON ALL LOCALES';
    public const LOWER_OR_EQUALS_THAN_ON_ALL_LOCALES = 'LOWER OR EQUALS THAN ON ALL LOCALES';

    /**
     * Return array of options for the completeness filter
     *
     * @return array Format: array('<value>' => '<label>', ...)
     */
    public function toOptionArray()
    {
        return [
            self::NO_CONDITION => __('No condition'),
            self::LOWER_THAN  => __('Lower than'),
            self::LOWER_OR_EQUALS_THAN => __('Lower or equals than'),
            self::GREATER_THAN  => __('Greater than'),
            self::GREATER_OR_EQUALS_THAN => __('Greater or equals than'),
            self::EQUALS => __('Equals'),
            self::DIFFER => __('Differ'),
            self::GREATER_THAN_ON_ALL_LOCALES => __('Greater than on all locales'),
            self::GREATER_OR_EQUALS_THAN_ON_ALL_LOCALES => __('Greater or equals than on all locales'),
            self::LOWER_THAN_ON_ALL_LOCALES => __('Lower than on all locales'),
            self::LOWER_OR_EQUALS_THAN_ON_ALL_LOCALES => __('Lower or equals than on all locales')
        ];
    }
}
