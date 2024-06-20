<?php

declare(strict_types=1);

namespace Akeneo\Connector\Helper;

use Akeneo\Connector\Helper\Config as ConfigHelper;
use Akeneo\Connector\Model\Source\Edition;
use Akeneo\Connector\Model\Source\Filters\AttributeUpdate;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use Akeneo\Pim\ApiClient\Search\SearchBuilderFactory;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class AttributeFilters
{
    /**
     * Attribute type for catalog file
     *
     * @var string ATTRIBUTE_TYPE_CATALOG_FILE
     */
    public const ATTRIBUTE_TYPE_CATALOG_FILE = 'pim_catalog_file';
    /**
     * Attribute type for catalog metric
     *
     * @var string ATTRIBUTE_TYPE_CATALOG_METRIC
     */
    public const ATTRIBUTE_TYPE_CATALOG_METRIC = 'pim_catalog_metric';
    /**
     * Attribute type for catalog table
     *
     * @var string ATTRIBUTE_TYPE_CATALOG_TABLE
     */
    public const ATTRIBUTE_TYPE_CATALOG_TABLE = 'pim_catalog_table';
    /**
     * Attribute type for reference entity
     *
     * @var string ATTRIBUTE_TYPE_REFERENCE_ENTITY
     */
    public const ATTRIBUTE_TYPE_REFERENCE_ENTITY = 'akeneo_reference_entity';
    /**
     * Attribute type for reference entity collection
     *
     * @var string ATTRIBUTE_TYPE_REFERENCE_ENTITY_COLLECTION
     */
    public const ATTRIBUTE_TYPE_REFERENCE_ENTITY_COLLECTION = 'akeneo_reference_entity_collection';
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
     * @param ConfigHelper         $configHelper
     * @param SearchBuilderFactory $searchBuilderFactory
     */
    public function __construct(
        ConfigHelper $configHelper,
        SearchBuilderFactory $searchBuilderFactory
    ) {
        $this->configHelper         = $configHelper;
        $this->searchBuilderFactory = $searchBuilderFactory;
    }

    /**
     * Create an attribute filter for a given types array
     *
     * @param string[] $attributeTypes
     * @param bool     $isConfig
     *
     * @return mixed[]
     */
    public function createAttributeTypeFilter($attributeTypes, $isConfig = false)
    {
        /** @var mixed[] $filters */
        $filters = [];
        /** @var mixed[] $search */
        $search              = [];
        $this->searchBuilder = $this->searchBuilderFactory->create();
        if (!$isConfig) {
            $this->addCodeFilter();
        }
        $search  = $this->searchBuilder->getFilters();
        $filters = [
            'search' => $search,
        ];

        /** @var string $edition */
        $edition = $this->configHelper->getEdition();

        if ($edition == Edition::GREATER_OR_FOUR_POINT_ZERO_POINT_SIXTY_TWO
            || $edition == Edition::GREATER_OR_FIVE
            || $edition === Edition::SERENITY
            || $edition === Edition::GROWTH
            || $edition === Edition::SEVEN
        ) {
            $filters['search']['type'][] = [
                'operator' => 'IN',
                'value'    => $attributeTypes,
            ];
        }

        return $filters;
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
        $this->addCodeFilter();
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
            /** @var string $date */
            $date = $this->configHelper->getAttributeUpdatedGreaterFilter();
            if (empty($date)) {
                return;
            }
            $date = $date . 'T00:00:00Z';
        }

        if (!empty($date)) {
            $this->searchBuilder->addFilter('updated', $mode, $date);
        }
    }

    /**
     * Add updated filter for Akeneo API
     *
     * @return void
     */
    protected function addCodeFilter()
    {
        if ($this->configHelper->getAttributeFilterByCodeMode() == false) {
            return;
        }
        /** @var string $codes */
        $codes = $this->configHelper->getAttributeFilterByCode();

        if (!$codes || empty($codes)) {
            return;
        }
        $codes = explode(',', $codes ?? '');
        $this->searchBuilder->addFilter('code', 'IN', $codes);
    }
}
