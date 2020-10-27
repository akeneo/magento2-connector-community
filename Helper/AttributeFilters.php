<?php

namespace Akeneo\Connector\Helper;

use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Model\Source\Edition;
use Akeneo\Connector\Model\Source\Filters\AttributeUpdate;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use Akeneo\Pim\ApiClient\Search\SearchBuilderFactory;

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
     * This variable contains a SearchBuilderFactory
     *
     * @var SearchBuilderFactory $searchBuilderFactory
     */
    protected $searchBuilderFactory;
    /**
     * This variable contains a SearchBuilder
     *
     * @var SearchBuilder $searchBuilder
     */
    protected $searchBuilder;

    /**
     * AttributeFilters constructor
     *
     * @param ConfigHelper $configHelper
     * @param SearchBuilderFactory $searchBuilderFactory
     */
    public function __construct(
        ConfigHelper $configHelper,
        SearchBuilderFactory $searchBuilderFactory
    ) {
        $this->configHelper = $configHelper;
        $this->searchBuilderFactory = $searchBuilderFactory;
    }

    /**
     * Create an attribute filter for a given types array
     *
     * @param string[] $attributeTypes
     *
     * @return void
     */
    public function createAttributeTypeFilter($attributeTypes)
    {
        /** @var string $edition */
        $edition = $this->configHelper->getEdition();
        /** @var string[] $attributeTypeFilter */
        $attributeTypeFilter = [];

        if ($edition == Edition::FOUR || $edition == Edition::SERENITY) {
            $attributeTypeFilter['search']['type'][] = [
                'operator' => 'IN',
                'value'    => $attributeTypes,
            ];
        }

        return $attributeTypeFilter;
    }

    /**
     * Get the filters for the attribute API query
     *
     * @return mixed[]|string[]
     */
    public function getFilters()
    {
        /** @var mixed[] $filters */
        $filters = [];
        /** @var mixed[] $search */
        $search              = [];
        $this->searchBuilder = $this->searchBuilderFactory->create();
        $this->addUpdatedFilter();
        $search  = $this->searchBuilder->getFilters();
        $filters = [
            'search' => $search,
        ];

        return $filters;
    }

    /**
     * Add updated filter for Akeneo API
     *
     * @return void
     */
    protected function addUpdatedFilter()
    {
        /** @var string $mode */
        $mode = $this->configHelper->getAttributeUpdatedMode();

        if ($mode == AttributeUpdate::GREATER_THAN) {
            $date = $this->configHelper->getAttributeUpdatedGreaterFilter();
            if (empty($date)) {
                return;
            }
            $date = $date . 'T00:00:00Z';
        }

        if (!empty($date)) {
            $this->searchBuilder->addFilter('updated', $mode, $date);
        }

        return;
    }
}
