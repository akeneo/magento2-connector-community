<?php

declare(strict_types=1);

namespace Akeneo\Connector\Helper;

use Akeneo\Connector\Model\Source\Edition;
use Akeneo\Connector\Model\Source\Filters\Update;

/**
 * Class FamiliesFilters
 *
 * @package   Akeneo\Connector\Helper
 * @author    Agence Dn'D <contact@dnd.fr>
 * @copyright 2004-present Agence Dn'D
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @link      https://www.dnd.fr/
 */
class FamiliesFilters
{
    /**
     * Description $configHelper field
     *
     * @var Config $configHelper
     */
    protected $configHelper;

    /**
     * FamiliesFilters constructor
     *
     * @param Config $configHelper
     */
    public function __construct(
        Config $configHelper
    ) {
        $this->configHelper = $configHelper;
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
        /** @var string $edition */
        $edition = $this->configHelper->getEdition();

        if ($edition === Edition::FOUR || $edition === Edition::SERENITY) {
            $filters['search'] = $this->getUpdatedFilter();
        }

        return $filters;
    }

    /**
     * Description getUpdateFilter function
     *
     * @return string[]
     */
    public function getUpdatedFilter()
    {
        /** @var string[] $updatedFilter */
        $updatedFilter = [];
        /** @var string|null $mode */
        $mode = $this->configHelper->getFamiliesUpdatedMode();

        if ($mode === Update::GREATER_THAN) {
            /** @var string|null $date */
            $date = $this->configHelper->getFamiliesUpdatedGreater();
            if (empty($date)) {
                return [];
            }
            /** @var string $date */
            $date          = $date . 'T00:00:00Z';
            $updatedFilter['updated'][] = [
                'operator' => Update::GREATER_THAN,
                'value'    => $date,
            ];

            return $updatedFilter;
        }

        return [];
    }
}
