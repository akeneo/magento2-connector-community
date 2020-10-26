<?php

namespace Akeneo\Connector\Helper;

use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Model\Source\Edition;

/**
 * Class AttributeFilters
 *
 * @package   Akeneo\Connector\Helper
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class AttributeFilters
{
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;
    /**
     * This variable contains a StoreHelper
     *
     * @var StoreHelper $storeHelper
     */
    protected $storeHelper;

    /**
     * ProductFilters constructor
     *
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        ConfigHelper $configHelper
    ) {
        $this->configHelper = $configHelper;
    }

    /**
     * Create an attribute filter for a given types array
     *
     * @param string[] $attributeTypes
     *
     * @return void
     */
    public function createAttributeTypeFilter($attributeTypes) {

        /** @var string $edition */
        $edition = $this->configHelper->getEdition();
        /** @var string[] $attributeTypeFilter */
        $attributeTypeFilter = [];

        if ($edition == Edition::FOUR || $edition == Edition::SERENITY) {
            $attributeTypeFilter['search']['type'][] = [
                'operator' => 'IN',
                'value'    => $attributeTypes
            ];
        }

        return $attributeTypeFilter;
    }
}
