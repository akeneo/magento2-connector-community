<?php

declare(strict_types=1);

namespace Akeneo\Connector\Helper;

use Akeneo\Connector\Model\Source\Edition;
use Akeneo\Connector\Model\Source\Filters\FamilyUpdate;
use Akeneo\Pim\ApiClient\Search\SearchBuilder;
use Akeneo\Pim\ApiClient\Search\SearchBuilderFactory;

/**
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class FamilyFilters
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
     * FamilyFilters constructor
     *
     * @param Config               $configHelper
     * @param SearchBuilderFactory $searchBuilderFactory
     */
    public function __construct(
        Config $configHelper,
        SearchBuilderFactory $searchBuilderFactory
    ) {
        $this->configHelper         = $configHelper;
        $this->searchBuilderFactory = $searchBuilderFactory;
    }

    /**
     * Description getFilters function
     *
     * @return array
     */
    public function getFilters()
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
            $this->addUpdatedFilter();
            $search  = $this->searchBuilder->getFilters();
            $filters = [
                'search' => $search,
            ];
        }

        return $filters;
    }

    /**
     * Description addUpdatedFilter function
     *
     * @return void
     */
    public function addUpdatedFilter()
    {
        /** @var string|null $mode */
        $mode = $this->configHelper->getFamiliesUpdatedMode();

        if ($mode === FamilyUpdate::GREATER_THAN) {
            /** @var string|null $date */
            $date = $this->configHelper->getFamiliesUpdatedGreater();
            if (empty($date)) {
                return;
            }
            /** @var string $date */
            $date = $date . 'T00:00:00Z';

            if (!empty($date)) {
                $this->searchBuilder->addFilter('updated', $mode, $date);
            }
        }
    }
}
