<?php

namespace Akeneo\Connector\Model\Source\Filters;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Completeness
 *
 * @category  Class
 * @package   Akeneo\Connector\Model\Source\Filters
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2019 Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class Completeness implements ArrayInterface
{
    /** const keys */
    const NO_CONDITION = 'no_condition';
    const LOWER_THAN = '<';
    const LOWER_OR_EQUALS_THAN = '<=';
    const GREATER_THAN = '>';
    const GREATER_OR_EQUALS_THAN = '>=';
    const EQUALS = '=';
    const DIFFER = '!=';
    const GREATER_THAN_ON_ALL_LOCALES = 'GREATER THAN ON ALL LOCALES';
    const GREATER_OR_EQUALS_THAN_ON_ALL_LOCALES = 'GREATER OR EQUALS THAN ON ALL LOCALES';
    const LOWER_THAN_ON_ALL_LOCALES = 'LOWER THAN ON ALL LOCALES';
    const LOWER_OR_EQUALS_THAN_ON_ALL_LOCALES = 'LOWER OR EQUALS THAN ON ALL LOCALES';

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
