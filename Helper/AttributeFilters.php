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
     * Attribute type for catalog file
     *
     * @var string ATTRIBUTE_TYPE_CATALOG_FILE
     */
    const ATTRIBUTE_TYPE_CATALOG_FILE = 'pim_catalog_file';
    /**
     * Attribute type for catalog metric
     *
     * @var string ATTRIBUTE_TYPE_CATALOG_METRIC
     */
    const ATTRIBUTE_TYPE_CATALOG_METRIC = 'pim_catalog_metric';
    /**
     * This variable contains a ConfigHelper
     *
     * @var ConfigHelper $configHelper
     */
    protected $configHelper;

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
