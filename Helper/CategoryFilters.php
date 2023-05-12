<?php

declare(strict_types=1);

namespace Akeneo\Connector\Helper;

use Akeneo\Connector\Model\Source\Filters\Category as CategoryFilterSourceModel;
use Akeneo\Connector\Model\Source\Edition;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use Akeneo\Pim\ApiClient\Search\SearchBuilderFactory;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class CategoryFilters
{
    /**
     * Description $configHelper field
     *
     * @var Config $configHelper
     */
    protected $configHelper;
    /**
     * Description $searchBuilderFactory field
     *
     * @var SearchBuilderFactory $searchBuilderFactory
     */
    protected $searchBuilderFactory;
    /**
     * Description $searchBuilder field
     *
     * @var SearchBuilder $searchBuilder
     */
    protected $searchBuilder;
    /**
     * Description $categoryFilterSourceModel field
     *
     * @var CategoryFilterSourceModel $categoryFilterSourceModel ;
     */
    protected $categoryFilterSourceModel;

    /**
     * CategoryFilters constructor
     *
     * @param Config                    $configHelper
     * @param SearchBuilderFactory      $searchBuilderFactory
     * @param SearchBuilder             $searchBuilder
     * @param CategoryFilterSourceModel $categoryFilterSourceModel
     */
    public function __construct(
        Config $configHelper,
        SearchBuilderFactory $searchBuilderFactory,
        SearchBuilder $searchBuilder,
        CategoryFilterSourceModel $categoryFilterSourceModel
    ) {
        $this->configHelper              = $configHelper;
        $this->searchBuilderFactory      = $searchBuilderFactory;
        $this->searchBuilder             = $searchBuilder;
        $this->categoryFilterSourceModel = $categoryFilterSourceModel;
    }

    /**
     * Description getParentFilters function
     *
     * @return array|string[]
     */
    public function getParentFilters()
    {
        /** @var string[] $filters */
        $filters = [];
        /** @var string[] $search */
        $search = [];

        /** @var string $edition */
        $edition = $this->configHelper->getEdition();

        if ($edition === Edition::GREATER_OR_FOUR_POINT_ZERO_POINT_SIXTY_TWO
            || $edition === Edition::GREATER_OR_FIVE
            || $edition === Edition::SERENITY
            || $edition === Edition::GROWTH
            || $edition === Edition::SEVEN
        ) {
            $this->searchBuilder = $this->searchBuilderFactory->create();
            /** @var string[] $categoriesToImport */
            $categoriesToImport = $this->getCategoriesToImport();

            if ($categoriesToImport) {
                $this->searchBuilder->addFilter('code', 'IN', array_values($categoriesToImport));
            }

            $search  = $this->searchBuilder->getFilters();
            $filters = [
                'search' => $search,
            ];
        }

        return $filters;
    }

    /**
     * Return categories to import without excluded categories
     *
     * @return string[]
     */
    public function getCategoriesToImport()
    {
        return explode(',', $this->configHelper->getCategoriesFilter() ?? '');
    }

    /**
     * Description getChildFilters function
     *
     * @param string[] $parent
     *
     * @return string[]
     */
    public function getChildFilters(array $parent)
    {
        /** @var string[] $filters */
        $filters = [];
        /** @var string[] $search */
        $search = [];
        /** @var SearchBuilder searchBuilder */
        $this->searchBuilder = $this->searchBuilderFactory->create();

        $this->searchBuilder->addFilter('parent', '=', $parent['code']);
        /** @var string[] $search */
        $search = $this->searchBuilder->getFilters();
        $filters = [
            'search' => $search,
        ];

        return $filters;
    }
}
